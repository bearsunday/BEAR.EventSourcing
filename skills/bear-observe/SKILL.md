---
user-invocable: true
name: bear-observe
description: BEAR.Sunday アプリに観測用の dev 文脈を設置し、1 リクエストが何をしたか(リソースの入れ子・キャッシュの hit/miss・保存と無効化)を木で読んでデバッグする。決着しなければ xstep に降りる。Use when user says "ログを見て", "キャッシュログを確認して", "何が起きたか", "observe", "リソースの動きを追って", "デバッグして", "遅い", "キャッシュが効いてない", "purge が効かない", or asks what a BEAR.Sunday request actually did.
---

# bear-observe — 1 リクエストが何をしたかを木で読む

BEAR.Sunday の欠陥の多くは沈黙する。**キャッシュが一度も効いていないリソースも、正しい答えを返し続ける**。
テストは緑のまま、遅いだけ、あるいは古いだけになる。見るのはコードではなくログで、順番は決まっている。

```text
1. 設置    観測用の dev 文脈をアプリに書く          (harness/setup.php)
2. 実証    配線が生きていることを束縛から確かめる    (harness/check.php)
3. 観測    リクエストを 1 本流して木を読む           (harness/tree.php)
4. 決着    木で足りなければ xstep で実引数を見る
5. 報告    根拠つきで、アプリかライブラリかを言う
```

以下 `<skill>` は、このファイルのあるディレクトリ(プロジェクトに入れたなら `.claude/skills/bear-observe`)。

## 0. 前提を 1 度だけ確かめる

```bash
composer show bear/event-sourcing bear/query-repository 2>&1 | head -3
```

どちらか無ければ入れる(未リリースの間は dev):

```bash
composer require --dev bear/event-sourcing:1.x-dev bear/query-repository:1.x-dev
```

## 1. 設置 — アプリに触るのはここだけ

```bash
php <skill>/harness/setup.php .
```

これが書くもの。すでにあるファイルは残す(`--force` で上書き):

| ファイル | 役目 |
|---|---|
| `src/Module/DevModule.php` | 観測文脈。invoker を包み、event sourcing とキャッシュログを install し、プールを永続にする |
| `src/Module/ObserveLoggerProvider.php` | `#[CacheLog]` の logger を無印の `SemanticLoggerInterface` にも配る |
| `src/Module/DevPoolProvider.php` | リクエストをまたいで生き残るファイルプール |
| `bin/dev.php` | 観測文脈で CLI から叩く入口 |

`public/index.php` は**変えない**。flush はキャッシュログモジュールの shutdown sink が持つので、
アプリが自分で書き出す行は要らない。

`setup.php` は文脈名(`cli-dev-hal-app` 等)とログのパスを出す。以降その文脈名を使う。

## 2. 実証 — 「入れた」で終わらせない

観測の失敗は全部無音だ。アプリは正しく答え、ログが薄くなるだけになる。束縛から確かめる:

```bash
php <skill>/harness/check.php . <context>
```

5 行すべて PASS でなければ観測は始まっていない。各 FAIL は「それが隠す欠落」を自分で書く:

| 点検 | 落ちると見えなくなるもの |
|---|---|
| one logger for both keys | `resource_request` は残り、`get` / `save_value` が消える(2 つ目の logger を誰も flush しない) |
| invoker is decorated | `resource_request` が 1 つも無く、キャッシュのスコープが宙に浮く |
| resource object / etag pool survives | 毎回 miss になり、hit も 304 も原理的に出ない |
| a sink owns the flush | 木は組み上がって shutdown で捨てられる。ファイルが 1 つも増えない |

FAIL が残るなら、アプリの `AppModule` が同じ束縛を後から上書きしていないかを見る
(`install()` は既存を上書きしないが、`bind()` は上書きする)。

## 3. 観測 — リクエストを流して木を読む

```bash
php bin/dev.php get '/orders?order_id=O-1'
php <skill>/harness/tree.php
```

`tree.php` は引数なしなら `var/log/*/observe/latest.json` の最新を読む。パスを渡せばそれを読む。
`ls -t var/log/<context>/observe/*.json` で過去のセッションが新しい順に並ぶ。

**読むのは今回の実行が書いたものか。** `latest.json` はリクエストごとに入れ替わる。リポジトリに
commit されたサンプル出力を判定に使うと、自分の変更が反映されない結果を測ることになる。

コールドの GET:

```text
└── resource_request uri=page://self/orders method=GET params={"order_id":"O-1"} → resource_response code=200 body_ref=000002.json durationMs=7.653
    └── get uri=page://self/orders?order_id=O-1 → cache_miss layer=resource durationMs=6.346
        ├── pre_write_cleanup uri=page://self/orders?order_id=O-1
        ├── invalidate tags=["_orders_order_id=O-1"] roPool=invalidated etagPool=invalidated cdn=skipped
        ├── cache_policy uri=page://self/orders?order_id=O-1 expiry=never resolvedTtl=31536000
        ├── save_etag uri=page://self/orders?order_id=O-1 etag="3620929378" tags=[...] requestedTtl=31536000 saved=1
        ├── save_value uri=page://self/orders?order_id=O-1 tags=[...] requestedTtl=31536000 saved=1
        └── resource_request uri=app://self/orders method=GET params={"orderId":"O-1"} → resource_response code=200
            └── get uri=app://self/orders?orderId=O-1 → cache_miss layer=resource durationMs=2.818
```

同じ URI の 2 回目:

```text
└── resource_request uri=page://self/orders method=GET params={"order_id":"O-1"} → resource_response code=200 durationMs=0.93
    └── get uri=page://self/orders?order_id=O-1 → cache_hit layer=resource durationMs=0.296
```

木の文法は 3 つだけ:

- **`A → B`** — スコープ A が型 B で閉じた。**答えを運ぶのは閉じた型**(`get` を閉じるのが `cache_hit` か
  `cache_miss` か)。`vendor/bin/stree` はこの型を出さないので、判定には `tree.php` を使う
- **入れ子** — 外側の実行中に内側が起きた。**入れ子は依存の証拠ではない**(§5)
- **葉** — そのスコープの中で起きた出来事。`save_*` `invalidate` `cache_policy` など

`--full` 相当の生 JSON が要るなら `php -r` ではなく `vendor/bin/stree --json <file>`。

## 4. 木で答えられる問い

| 問い | 見るところ |
|---|---|
| キャッシュは効いたか | `get` を閉じる型。`cache_hit` / `cache_miss` |
| どの層で効いたか | 閉じた文脈の `layer`: `resource` / `donut` / `donut-view` / `etag`(304 判定) |
| 保存されたか、何秒、どのキーで | `save_value` / `save_view` / `save_etag` / `save_donut*` の `tags` `requestedTtl` `saved` |
| なぜ保存されていないか | `put_skipped` の `reason`(`etag-present` / `error-code` / `not-cacheable`) |
| purge は何を消したか | `invalidate` の `tags` `roPool` `etagPool` `cdn`。`cdn=skipped` は purger 未設定 |
| 誰が書いたか | `command` スコープの `source`。直接呼び出しは `manual_store` / `manual_purge` |
| 何回リソースが走ったか | `resource_request` の数。N+1 は同じ URI の兄弟が並ぶ形で出る |
| どこで時間を使ったか | `durationMs`。親から子を引いた残りがその層の自前のコスト |
| 何を返したか | `body_ref` のファイル(`var/log/<context>/es-bodies/`) |

読み間違えやすい 3 点:

1. **コールドの `get` にも `pre_write_cleanup` → `invalidate` が出る。** 保存の前に自分の古いエントリを
   消しているだけで、無効化ではない
2. **`cache_hit` で閉じたスコープの `put_skipped` は正常。** 欠陥として読むのは `cache_miss` で閉じた
   スコープに出たときだけ
3. **葉の `cache_miss` と、スコープを閉じる `cache_miss` は別。** donut は内側の層でも葉を出す

キャッシュの宣言(`#[Cacheable]` / `#[DonutCache]` / `#[Refresh]` …)ごとに**期待されるイベント列**と、
食い違ったときの切り分け表は `bear-cache-log` スキルにある。効いていない理由まで踏み込むならそちらへ。

## 5. 木で決着しないとき

木は「何が起きたか」を言う。「なぜその値になったか」は言わない。降りる前に、まず木で候補を 1 つに絞る。

**入れ子を依存の証拠にしない。** 親のスコープに子のスコープが現れるのは、親の実行中に子を読んだ事実だけを
言う。`ResourceInterface` を注入して手で読んでも同じ形になる。依存が生きているかは `save_*` の `tags` に
子の URI タグが入っているかで見て、子を purge → 親を読み直して `cache_miss` で閉じることで実証する。

候補が 1 つ残ったら `koriym/xdebug-mcp`(`composer global require koriym/xdebug-mcp`)。php 本体に Xdebug が
入っていなくてよい。**vendor は既定で記録から除外される**ので、ライブラリを疑うなら明示して入れる:

```bash
# 1) どの分岐まで届いているか
xtrace --include-vendor="bear/query-repository" --context="tags absent from parent save" -- php bin/dev.php get /orders

# 2) 判断している行を名前で探す(行番号は版ごとに動く)
grep -n "SURROGATE_KEY" vendor/bear/query-repository/src/CacheDependency.php

# 3) その行で実引数を見る
xstep --break="vendor/bear/query-repository/src/CacheDependency.php:<line>" \
  --steps=20 --context="what tags arrive" -- php bin/dev.php get /orders
```

`xtrace` はトレースを `/tmp/trace.*.xt` に書いて末尾に場所と規模を出す(1 リクエストで数万行)。
全部は読まない。`--context` に何を見たいかを書き、ファイルは目的の関数名で `grep` する。

**`xstep` の前に、ライブラリの既存テストを 1 本走らせる。** 同じシナリオを守るテストが既に赤なら、
切り分けはそこで終わる。スイート全体は走らせない。

**`var_dump` / `printf` を仕込まない。** 使い捨てスクリプトに同じロジックを書き写して覗くのは、対象ではなく
写しを見ている(写し間違いに気づけない)。再現スクリプトは**対象を呼ぶだけ**にする。

## 6. 呼ばれ方

「キャッシュログを確認して」「何が起きたか見て」「遅い理由を調べて」で入る。手順は毎回同じ:

1. `var/log/*/observe/latest.json` があるか。無ければ §1 → §2 を実行してから、ユーザーに
   「どのリクエストを見ますか」と聞く(勝手にリクエストを選ばない)
2. あれば `tree.php` を実行する。ファイルの mtime が前回見たときから動いていなければ、**そう言う** —
   古い木を新しい実行の証拠にしない
3. 木を読み、§4 の表で問いに答える。ユーザーが問いを持っていなければ、木から言えることを言う:
   何回リソースが走ったか、hit したか、保存されたか、時間はどこに落ちたか
4. 木で決着しなければ §5 に降りる。降りるときは何を確かめに行くかを先に言う

## 7. 報告

1. **どちらの問題か** — アプリ / ライブラリ / 設計判断のどれか
2. **観測の根拠** — 木の抜粋。ライブラリを疑うなら `xstep` で見た実引数。「トレースした」と書くのは
   実際にツールを使ったときだけ
3. **最小再現** — 対象を呼ぶだけのスクリプトかテスト 1 本
4. **外から見える言葉で** — 「在庫が最大 60 秒古い」のように
5. **修正の証明** — 直したなら、**どのイベントがどう変わったかを木で示す**。テストが緑になったことは
   証明ではない(テストは同じ盲点を持ち得る)

**ライブラリだと言うなら、ライブラリの fixture で落ちるテストを添える。添えられないなら、
まだアプリ側の疑いに戻す。**

## 範囲外

観測文脈は開発用だ。プールはファイルで、body は全部ディスクに落ち、記録は無条件。本番で同じことを
したいなら `ProdQueryRepositoryLogModule`(サンプリングと flush 時の判定を持つ)を見る。
CDN 側の実挙動・実時間での期限消滅は、どちらの文脈のログにも出ない。
