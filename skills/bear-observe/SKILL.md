---
user-invocable: true
name: bear-observe
description: BEAR.Sunday アプリのツリーログ(1 リクエストが何をしたかを表す木。var/log/<context>/events/latest.json)を読む。リソースの入れ子・キャッシュの hit/miss・保存と無効化が入っている。木が無ければ観測用の dev 文脈を 1 行で設置してから読む。宣言した意図と照合し、食い違えばアプリかライブラリかを切り分けて報告する。決着しなければ xstep に降りる。Use when user says "ツリーログを見て", "ツリーログ", "tree log", "ログを見て", "キャッシュログ", "cache log", "何が起きたか", "observe", "リソースの動きを追って", "デバッグして", "遅い", "キャッシュが効いてない", "キャッシュが古い", "purge が効かない", "304 が返らない", "アプリかライブラリか切り分けて", or asks what a BEAR.Sunday request actually did.
---

# bear-observe — 1 リクエストが何をしたかを木で読む

BEAR.Sunday の欠陥の多くは沈黙する。**キャッシュが一度も効いていないリソースも、正しい答えを返し続ける**。
テストは緑のまま、遅いだけ、あるいは古いだけになる。見るのはコードではなくログで、順番は決まっている。

```text
1. 設置     DevModule に DevObservationModule を 1 行で入れる
2. 実証     1 回叩いて、木の形で配線を確かめる
3. 観測     リクエストを 1 本流して木を読む           (composer tree / vendor/bin/stree)
4. 照合     宣言した意図が、どのイベント列で現れるか
5. 切り分け  食い違ったら、アプリの問題かライブラリの問題か
6. 報告     根拠つきで、どちらかを言う
```

**この手順を実行するのはエージェントで、ユーザーではない。** ユーザーがするのはスキルを入れることと、
「ツリーログを見て」と言うことだけ。コマンドをユーザーに貼って待たない。

## 呼ばれ方 — どこから入るか

「ツリーログを見て」「キャッシュログを確認して」「何が起きたか見て」「遅い理由を調べて」で入る。

1. **読める木があるなら、まず読む。** アプリに `composer tree` があり、`composer.json` で `stree` を呼ぶと確かめたらそれで、無いか失敗したら
   `ls -t var/log/*/*/latest.json` で最新を選んで `vendor/bin/stree <そのパス>` で読む(`events/` のほか、キャッシュの
   ログだけのアプリは `query-repository/`、以前のテンプレートは `observe/` に書いている)。どちらの道でも、読んだ `latest.json` のあるディレクトリを、以下「木のディレクトリ」と呼ぶ(`composer tree` なら、そのスクリプトが選ぶパスを `composer.json` で見る)。木の時刻を言い、どの
   リクエストの木かはユーザーに確かめるまで決めつけない。前回見たときから新しいセッションが増えていなければ
   (§2)、**そう言う** — 古い木を新しい実行の証拠にしない。根が `resource_request` なら設置は済んでいるので §1 は飛ばす。キャッシュの
   スコープしか無い木なら、リソースの回数や入れ子が要るとき §1 で設置する
2. 木が無いときだけ §1 の判定に降りる。設置してから、ユーザーに「どのリクエストを見ますか」と聞く
   (勝手にリクエストを選ばない)。木が薄い・不審なときは §2 の表で、木の形から配線を確かめる
3. 木を読み、§3 の表で問いに答える。ユーザーが問いを持っていなければ、木から言えることを言う:
   何回リソースが走ったか、hit したか、保存されたか、時間はどこに落ちたか
4. 宣言どおりに動いているかを問われたら §4、食い違ったら §5。降りるときは何を確かめに行くかを先に言う

## 0. 前提を 1 度だけ確かめる

```bash
composer show bear/event-sourcing bear/query-repository 2>&1 | head -3
```

どちらか無ければ入れる(未リリースの間は dev):

```bash
composer require --dev bear/event-sourcing:1.x-dev bear/query-repository:1.x-dev
```

## 正典ドキュメント(推測で補完しない)

BEAR.QueryRepository の `docs/` と `demo/` は `.gitattributes` で `export-ignore` — 通常の
`composer require` の vendor には**入らない**。GitHub で読むか
`composer reinstall bear/query-repository --prefer-source` で取る。

| 知りたいこと | どこ |
|---|---|
| **語彙全部と読み方(まずこれ)** | [docs/reading-the-log.md](https://github.com/bearsunday/BEAR.QueryRepository/blob/1.x/docs/reading-the-log.md) |
| ログが答える問いと保証の境界 | [docs/what-the-log-proves.md](https://github.com/bearsunday/BEAR.QueryRepository/blob/1.x/docs/what-the-log-proves.md) |
| 設計根拠・コスト実測・既定オフの理由 | [docs/why-the-log-records-everything.md](https://github.com/bearsunday/BEAR.QueryRepository/blob/1.x/docs/why-the-log-records-everything.md) |
| 各 context のスキーマ(28 種) | [docs/schemas/context/](https://github.com/bearsunday/BEAR.QueryRepository/tree/1.x/docs/schemas/context) — 各イベントの `schemaUrl` が正典 |
| 動く実例(自己検証つき) | [demo/](https://github.com/bearsunday/BEAR.QueryRepository/tree/1.x/demo) |

スキーマ検証: `vendor/bin/validate-semantic-log.php <log.json> <schema-dir>`(`--prefer-source` なら
`vendor/bear/query-repository/docs/schemas/context`。dist ならスキーマをアプリ側にコピーして
リポジトリ管理する — スキーマは公開契約なので固定して持つことに意味がある)。

## 1. 設置 — 要るかどうかを先に決める

**この観測文脈は、常駐ホスト(1 プロセスが複数のリクエストを跨いで生き続ける実行モデル)では現時点で非対応。**
差し込み口(`SessionStoreInterface` / `LogSinkInterface`)は BEAR.QueryRepository 側にあるが、動く実装は同梱されていない。

**分類するのは、`dev-` 文脈を起動するプロセス。** §3 の CLI 入口は 1 プロセス 1 リクエストなので、
本番がどのホストでも、そのまま下へ進んでよい。問題になるのは、`DevModule` が `dev-` を含む文脈すべてに
載るため、`public/index.php` などを `dev-` 文脈のまま常駐ホストで起動している場合。その起動を分類する:

| `dev-` 文脈を起動するホスト | すること |
|---|---|
| CLI、PHP-FPM | そのまま下へ |
| RoadRunner(`RR_MODE`)、コルーチン内で logger が作られる Swoole | 入れても記録されない(QueryRepository の sink が arm を拒否し、理由を `error_log` に出す)。観測は CLI 入口で行う |
| FrankenPHP worker mode、ReactPHP、Amp、常駐 CLI consumer、boot 時に logger を作る Swoole worker | **検出されないので、その起動に `dev-` 文脈を載せない。** 拒否が働かず、リクエスト境界が 1 本の木に混ざる(処理が同じ logger 上で重なると、close が `close_id_mismatch` として拒否・診断記録され、スコープが閉じないまま木が崩れる。例外は出ない) |
| 分からない | 入れる前に問う |

常駐ホストでも観測するなら、ホスト側で request context をキーにした `SessionStoreInterface` と、request end で
flush する `LogSinkInterface` を**両方**束縛する(片方だけだとセッションを共有するか、排出されずに溜まる)。
根拠は BEAR.QueryRepository の
[docs/what-the-log-proves.md](https://github.com/bearsunday/BEAR.QueryRepository/blob/1.x/docs/what-the-log-proves.md)
"Concurrent sessions"。`DevObservationModule` はそれを束縛しない。

EventSourcing のイベント抽出だけなら常駐ホストでも使えるが、`DevModule` のままでは使えない。invoker は
`#[CacheLog]` の logger に記録するので、sink が拒否すると `resource_request` も記録されない。
`DevObservationModule` を使わない別の配線で、`EventCollector` をランタイムの request end から呼ぶ
(README "The application owns the flush")。

`SafeSemanticLogger` の "Safe" は、セッションをリクエストごとに解決し、直列化の境界を越えて持ち越さず、
sink が拒否したら黙る、という意味。並行実行時にセッションを分離する保証ではない。

ホストが通っても、束縛の形で無音のまま欠ける配線が 2 つある。ここで目視する:

| 点検 | 落ちると起きること |
|---|---|
| `SemanticLoggerInterface` `#[CacheLog]` が別の場所で `Scope::SINGLETON` 無しに束縛されていないか | invoker とキャッシュが別々の logger に記録し、木から `resource_request` かキャッシュのスコープが欠ける(§2 の表)。sink 側も拾い、`error_log` に `a second logger armed an already-armed sink` と出す — 読みに行かなければ気づかない |
| injector を直列化する文脈(コンパイル済みアプリ)に `PsrLogWriter` が無いか | ホストの logger(Monolog はクロージャを持つ)を抱えるので、コンパイルしたグラフが unserialize できなくなる。その文脈は `LogFileWriter` / `LogStreamWriter` で書く |

観測はもう動いているかもしれない。動いているなら**アプリに 1 文字も書かない**。
`src/Module/DevModule.php` を見て 3 つに分ける:

| 見えたもの | すること |
|---|---|
| `DevObservationModule` を install している | 設置済み。§2 へ |
| `DevModule` が無い | 下の 1 行だけの `DevModule` を書く。何を書くかを先に 1 行で言う |
| ある、`DevObservationModule` を含まない | アプリ自身の `DevModule`。下の 1 行を足す。`DevQueryRepositoryLogModule` の行があれば、その行と置き換える(両方あると、先に install した方が木の置き場所を決める)。その行で `keep` を変えていたなら、同じ数を `DevObservationModule` の第 2 引数に渡す(ボディは 1 世代以上を残すので、0 なら 1)。第 3 引数でモジュールを包んでいたなら、そのモジュールは `DevObservationModule` の行の後に `install()` して残す。古い置き場所を読む `tree` スクリプトがあれば、下の形に直す |

書くのはこの 1 行だけ。invoker の包み方、logger、書き出し、body の保存はモジュールの中にある:

```php
$this->install(new DevObservationModule($this->appMeta->logDir . '/events'));
```

木もボディも `var/log/<context>/events/` に落ちる(ボディは `bodies/` の下。直近 10 セッション分を残す)。
`DevModule` が無いアプリでは、`src/Module/DevModule.php` を `BEAR\Package\AbstractAppModule` の子として書く。
namespace は `composer.json` の psr-4 で `src/` に対応する接頭辞に `\Module` を付けたもの。
`public/index.php` は**変えない**。flush はキャッシュログモジュールの shutdown sink が持つので、
アプリが自分で書き出す行は要らない。

`InvokerInterface` がアプリの文脈で自前のクラスに束縛されている(`src/Module` の `bind(InvokerInterface::class)` のほか、
install したモジュールも束縛する — BEAR.DevTools の `HaloModule` は `DevInvoker` に束縛する)なら、この 1 行とは
両立しない — モジュールは内側に素の `Invoker` を名指しするので、その束縛か記録のどちらかが外れる。効いている束縛は、
1 行を入れる前の文脈で `get_class($injector->getInstance(InvokerInterface::class))` が `BEAR\Resource\Invoker` 以外を
返すかで見る。束縛がアプリの鎖(`AppModule` 側)にあるなら、README "One tree with BEAR.QueryRepository" の手順で
自分の文脈モジュールに配線する。同じ文脈モジュールに install したモジュール(`DevModule` の `HaloModule` など)の
束縛にはその手順の `rename()` が届かないので、そのモジュールを入れない文脈で観測する。

**dev 文脈で叩く。** 文脈名はアプリの文脈の前に `dev-` を付けたもの(`cli-hal-app` なら `dev-cli-hal-app`)。
CLI 入口(`bin/page.php` など)を読み、どの文脈で動くかを先に確かめる:

| 入口の文脈 | すること |
|---|---|
| CLI の既定が `dev-`(`getenv('BEAR_CONTEXT') ?: (PHP_SAPI === 'cli' ? 'dev-cli-hal-app' : 'hal-app')`) | そのまま叩く |
| `BEAR_CONTEXT` を読むが、既定が `dev-` でない | `BEAR_CONTEXT=dev-cli-hal-app php bin/page.php get /` |
| 文脈を式で固定(`PHP_SAPI === 'cli' ? 'cli-hal-app' : 'hal-app'`) | その式を `getenv('BEAR_CONTEXT') ?: (元の式)` に直し、上の行の形で叩く(括弧が無いと PHP 8 ではエラー) |

以下の例の `php bin/page.php` は、dev 文脈で動く入口を指す。

**読む口を 1 つ用意する。** アプリの `composer.json` の `scripts` に `tree` が無ければ足す(別の用途の `tree` があれば触らず、`vendor/bin/stree` を直接使う)。以降は `composer tree` で、
文脈を変えてもいちばん新しい木を読める:

```json
"scripts": {
    "tree": "vendor/bin/stree $(ls -t var/log/*/events/latest.json | head -1)"
}
```

キャッシュの保存先は変えない。アプリが保存先を束縛していなければ(QueryRepository の既定は何も残さない
`NullAdapter`)、毎回 miss になる。hit を見るなら、`#[ResourceObjectPool]` にリクエストをまたいで残る保存先を
束縛する文脈を重ねる(例: その束縛だけの `CacheModule` を `cache-` 文脈にして、`dev-cache-cli-hal-app` で叩く)。

畳み込むときの罠が 2 つある。

- **包んだモジュールは「足す」のではなく「置き換える」**。アプリが既に `QueryRepositoryModule` を持つ
  (BEAR.Package 経由など)のに、上から `module: new QueryRepositoryModule()` を足すと **Ray.Aop の pointcut が
  累積してインターセプタが 2 回走る** — 1 リクエストで参照 2 回・書き込み 2 回、木には同じ URI が自分の中に
  入れ子で現れる。既存グラフに記録だけ足すなら、包まずに
  `bind(SemanticLoggerInterface::class)->annotatedWith(CacheLog::class)` の 1 本だけを差し替える
- **リクエストの途中で flush するものと logger を共有しない**。Be Framework の `BecomingInterface` は becoming の
  たびに semantic logger を flush するので、`DevModule`(またはそれが install / override する別のモジュール)が
  同じ logger を共有していると、1 リクエストの木が途中で切れる。その場合は `dev` ではなく、観測専用の文脈語
  (例: `observe-`)でモジュールを作る

## 2. 実証 — 「入れた」で終わらせない

観測の失敗は全部無音だ。アプリは正しく答え、ログが薄くなるだけになる。叩く前と後で、木のディレクトリ(§1 の 1 行を
入れたなら `var/log/<context>/events`、設置を飛ばしたなら木を見つけたディレクトリ)のいちばん新しいセッション
(`ls <木のディレクトリ> | grep -E '^[0-9]{8}-' | tail -1`)の名前を比べ、変わってから木の形で確かめる。
セッションの名前は UTC の時刻とマイクロ秒なので、名前の順が書いた順になる。何も記録されないとファイルは
書かれず、前のセッションの `latest.json` が残る(mtime は分解能が粗いと、書かれても同じに見える):

| 見えたもの | 意味 |
|---|---|
| 根が `resource_request`(キャッシュするリソースなら、その中に `get` などのスコープ) | 配線は生きている |
| いちばん新しいセッションの名前が変わらない(`latest.json` も無いか、前のまま) | その文脈に `DevModule` が載っていない(文脈名に `dev` が無い)か、sink が書き出しを拒否した。何も記録されなかったセッションはファイルにならないので、invoker が包まれておらず叩いたリソースにキャッシュの宣言も無いときも変わらない(次の行の原因を見る) |
| 根に `resource_request` が無く、キャッシュのスコープが宙に浮く | invoker が包まれていないか、別の logger に記録している |
| 木が `DevObservationModule` に渡したディレクトリではなく、別の場所(`query-repository/` など)に出る | 先に install された `DevQueryRepositoryLogModule` の行が残っている。その行を消す(第 3 引数で包んでいたモジュールは、§1 のとおり `install()` して残す) |

崩れているなら、アプリの `AppModule` が `InvokerInterface` か `#[CacheLog]` の logger を後から上書きしていないかを見る
(`install()` は既存を上書きしないが、`bind()` は上書きする)。到達しない理由が束縛に無ければ
`error_log`(FPM なら SAPI のエラーログ)を見る。sink の拒否・二重 arm・書けない先は全部そこに出る。

### 木が薄いとき

`resource_request` が出ていても、**インターセプタが織られたかは別の話**。ここを外すと、
測っているものが別物になる:

- **`final class` はインターセプタを受け取れない**。Ray.Aop はクラスを継承して織るので、`final` なリソースの
  `#[Cacheable]` は静かに無効になり、**そのリソースの `get` スコープが miss すら出さずに消える**。
  木全体が空になるとは限らない — final でない他のリソースは普通に出るので、欠けているのは
  1 本の枝だけのことがある。検査:

```php
get_class($injector->getInstance($class)) !== $class;  // false なら織られていない
$injector->getInstance($class)->bindings;               // メソッド名 => 付いたインターセプタ
```

- **qualifier は `BEAR\RepositoryModule\Annotation\*`**(`CacheLog`, `ResourceObjectPool`, `TagsPool`, `EtagPool`)。
  `BEAR\QueryRepository\Annotation\` にも同名クラスがあり、**間違えても例外は出ない** — 自分の
  `getInstance()` が同じ間違った key で答えるので「束縛は効いている」ように見える。プールの中身を数えて確かめる
- **weave が変わったら `composer clean`**(コンパイル済み DI が残っていると直した後も織られない)
- **時間差で判定しない**。プロセス内では refill も 1ms 台で hit と区別できない。判定は必ずイベントで行う
- **プリセットの実数**は `Expiry`: `short` 60 / `medium` 3600 / `long` 86400 / `never` 31536000 秒。
  アプリが `StorageExpiryModule` で上書きするので、数値を書き写さず injector から `Expiry` を取って
  `getTime()` で解決する

## 3. 観測 — リクエストを流して木を読む

```bash
php bin/page.php get '/orders?order_id=O-1'
composer tree
```

`composer tree` が無いか、別の用途か、失敗したなら(手順 1・§1)、`vendor/bin/stree <木のディレクトリ>/latest.json`。`latest.json` は直近の
セッションで、`ls -t <木のディレクトリ>/*.json` で過去のセッションが新しい順に並ぶ。`body_ref` は応答
ボディのファイルを `file://` の絶対パスで指す(長いと省略される。全部見るなら `stree --full`)。
閉じの行は、開いたときから変わったキーを先頭から 2 つだけ出し、残りは印を付けずに落とす(body を保存した
`resource_response` は `code` と `body_ref` が出て `durationMs` が落ちる)。「記録されていない」と言う前に
`stree --full` で確かめる。

**読むのは今回の実行が書いたものか。** `latest.json` はリクエストごとに入れ替わる。叩く前後で、いちばん新しい
セッションの名前が変わったかを見る(§2)— 変わらなければ今回のリクエストは何も記録していない(§2 の表の 2 行目)。別の文脈も
動かしているなら、`composer tree` はそちらの木を選ぶことがあるので、文脈のパスを `vendor/bin/stree` に直接渡す。
リポジトリに commit されたサンプル出力を判定に使うと、自分の変更が反映されない結果を測ることになる。

コールドの GET(並び順は版で変わる。§4 の表が基準):

```text
resource_request get 'page://self/orders?order_id=O-1'
├── get uri=page://self/orders?order_id=O-1
│   ├── pre_write_cleanup uri=page://self/orders?order_id=O-1 [event]
│   ├── invalidate tags=[_orders_order_id=O-1] roPool=invalidated etagPool=invalidated cdn=skipped [event]
│   ├── cache_policy uri=page://self/orders?order_id=O-1 expiry=never resolvedTtl=31536000 [event]
│   ├── save_etag uri=page://self/orders?order_id=O-1 etag="3620929378" tags=[_orders_order_id=O-1] requestedTtl=31536000 (+1 more) [event]
│   ├── save_value uri=page://self/orders?order_id=O-1 tags=[_orders_order_id=O-1] requestedTtl=31536000 saved=true [event]
│   ├── resource_request get 'app://self/orders?orderId=O-1'
│   │   ├── get uri=app://self/orders?orderId=O-1
│   │   │   └── cache_miss layer=resource durationMs=2.818
│   │   └── resource_response code=200
│   └── cache_miss layer=resource durationMs=6.346
└── resource_response code=200 body_ref=file:///…/000002.json
```

同じ URI の 2 回目:

```text
resource_request get 'page://self/orders?order_id=O-1'
├── get uri=page://self/orders?order_id=O-1
│   └── cache_hit layer=resource durationMs=0.296
└── resource_response code=200 durationMs=0.93
```

`resource_request` の行は、BEAR の CLI がそのまま受け取る形をしている。`get '…'` を `php bin/page.php`(`app://` なら
`bin/app.php`)の後ろに貼れば、同じリクエストを叩き直せる。値は符号化した形で 1 つずつ 40 文字に切る(`...(+N)`)ので、切れた行は
`stree --full` から写す。末尾に `[not replayable]` が付いた行は `replayable: false` と記録された要求(伏せた資格情報を含むものなど)なので、そのままでは叩き直せない。

木の文法は 3 つだけ:

- **閉じの型** — 開いたスコープの最後の子として現れる行が、そのスコープを閉じた型を運ぶ
  (`get` を閉じるのが `cache_hit` か `cache_miss` か)。閉じの行だけの特別な記号は無いので、
  それが閉じの型かどうかは §4 の表にある語彙(`cache_hit` / `cache_miss` / `resource_response` /
  `command_result` など)で見分ける
- **入れ子** — 外側の実行中に内側が起きた。**入れ子は依存の証拠ではない**(§4)
- **葉**(`[event]` の印つき)— そのスコープの中で起きた出来事。`save_*` `invalidate` `cache_policy` など

生の JSON が要るなら `vendor/bin/stree --json <file>`。

### ファイルが無い現場もある

テストやオラクルは `DevObservationModule` を使わず、`SemanticLoggerInterface` `#[CacheLog]` に
`SafeSemanticLogger`(sink 無し)を束縛して、自分で `flush()` を受け取るのが普通だ。この形では
`var/log` に何も作られない。観測する側が sink になるので、読み方はファイルではなく戻り値:

```php
$logger = $injector->getInstance(SemanticLoggerInterface::class, CacheLog::class);
$resource->get($uri);                       // 操作
$log = $logger->flush();                    // その場で受け取る(以降は次のセッション)
```

`flush()` は 1 回で使い切る。操作ごとに呼べばステップ単位で見られるが、**呼んだ後に前のイベントは
残らない**。`latest.json` を探して「ログが無い」と結論する前に、対象のテストやスクリプトが自分で
束縛していないかを読む。

### 木で答えられる問い

| 問い | 見るところ |
|---|---|
| キャッシュは効いたか | `get` を閉じる型。`cache_hit` / `cache_miss` |
| どの層で効いたか | 閉じた文脈の `layer`: `resource`(値) / `donut` / `donut-view` / `etag`(304 判定) |
| 保存されたか、何秒、どのキーで | `save_value` / `save_view` / `save_etag` / `save_donut*` の `tags` `requestedTtl` `saved` |
| なぜ保存されていないか | `put_skipped` の `reason`(`etag-present` / `error-code` / `not-cacheable`) |
| CDN に何を何秒キャッシュさせたか | `cdn_headers` の `headers`(応答の literal ヘッダ)。lifetime ヘッダが無い = 応答が CDN に寿命を指示しなかった(`putDonut` の種類)。エッジが実際に何を保持したかはこのログの外にある。長すぎるときは **`sMaxAge` 未指定の既定値**(generic 10 秒 / Fastly・Akamai 31536000 秒)を疑う — setter の既定値もここに literal で出る |
| エッジの再検証(304)はヒットしたか | `conditional_request` スコープの閉じ方。`cache_hit{layer: etag}` = リソースを走らせず 304、`cache_miss{layer: etag}` = 検証子が古い |
| purge は何を消したか | `invalidate` の `tags` `roPool` `etagPool` `cdn`。`cdn` は `purged`/`failed`/`skipped` の三値、fail-closed |
| miss は空だったのか、店が読めなかったのか | 同じスコープに `cache_error{operation: read}` があれば縮退。無ければコールド |
| 誰が書いたか | `command` スコープの `source`。直接呼び出しは `manual_store` / `manual_purge` / `manual_invalidate` |
| 依存の伝播 | `save_*` の `tags` ↔ `invalidate` の `tags` の突き合わせ。`depends_on` は `#[Cacheable]` の親だけでなく、donut の親もデータ依存の子と描画中の `depends()` 呼び出しについて出す。`ETag` も `Surrogate-Key` も持たない子には代わりに `dependency_skipped` を出し、`reason` は子の `$body` で決まり、`null` なら `no_body`(donut ではおおむね穴)、値があれば `no_etag_or_surrogate_key`(タグの無いデータ依存の子か、`$body` に既定値を持つ穴)(§4) |
| 何回リソースが走ったか | `resource_request` の数。N+1 は同じ URI の兄弟が並ぶ形で出る |
| どこで時間を使ったか | `durationMs`。親から子を引いた残りがその層の自前のコスト |
| 何を返したか | `body_ref` のファイル(`var/log/<context>/events/bodies/<generation>/`。`<generation>` は初回保存時に作られるディレクトリ) |

読み間違えやすい 4 点(いずれも実測):

1. **コールドの `get` にも `pre_write_cleanup` → `invalidate` が出る。** 保存の前に自分の古いエントリを
   消しているだけで、無効化ではない
2. **`cache_hit` で閉じたスコープの `put_skipped` は正常**(既に保存済みのものを再保存しない)。
   欠陥として読むのは `cache_miss` で閉じたスコープに出たときだけ
3. **葉の `cache_miss` と、スコープを閉じる `cache_miss` は別。** donut は内側の層でも葉を出す。
   「miss のイベントがある」は「そのスコープが miss で閉じた」ではない
4. **`#[Cacheable]` は自分の書き込みでは落ちるが、他所の書き込みでは落ちない。** 他のリソースが自分の
   タグを announce しない限り、TTL が唯一の eviction になる

## 4. 照合 — 宣言した意図と実際

宣言は意図の表明でしかない。**効いている証拠はイベント列**。下表は BEAR.QueryRepository の PR #274 の head
(`d7afbe2`)で `demo/run*.php`、`tests/Fake/fake-app/src/Resource/Page/Mx`・`Html` の fixture、
`tests/DonutMaterialDependencyTest.php` / `tests/DonutQueryInterceptorPurgeTest.php` を実行して得た列 =
**その版で期待される形**。手元の出力がこれと違えば、それが所見であって表の誤りではない。

| 宣言 | コールド read | 2 回目 | 自分への書き込み |
|---|---|---|---|
| `#[Cacheable]` | close `cache_miss`。`cache_policy{expiry, resolvedTtl}` → `save_value` → `save_etag` | close `cache_hit`、イベント 0 件 | `command{source: CommandInterceptor}` に `purge` → `invalidate`、続いて**入れ子の `get` で再生成**(`RefreshSameCommand`)。close は `command_result` |
| `#[CacheableResponse]` | 途中に event `cache_miss{layer: donut}`(テンプレート未ヒット)、close は `cache_miss{layer: donut-view}`。データ依存の子があれば `depends_on`(`put_donut` の前)、描画中の `depends()` 呼び出しがあれば同じく `depends_on`(`put_donut` の後)。`put_donut` → `cdn_headers` → **`save_donut_view`** → `save_etag` → `save_donut`(全体を保存し ETag を持つ = 304 が返る)。`save_etag` は 200 の応答で `save_donut_view` が `saved: true` のときだけ出る(204・3xx は ETag を持たない) | close `cache_hit{layer: donut-view}`、イベント 0 件。穴が無効化された回だけ `refresh_donut` →(内容が変われば先に `save_donut`)→ `save_donut_view` → `save_etag`(`save_etag` の条件は左と同じ) | 属性が無くても `command{source: DonutCommandInterceptor}` に `purge` → `invalidate`、続いて入れ子の `get` で再生成(close `cache_miss{layer: donut-view}`)。close は `command_result`、次の read は `cache_hit{layer: donut-view}`。`#[RefreshCache]` も同じ形。ただし `onPost` の引数が `onGet` の必須の引数を満たさないときは、この `command` に `cache_error{operation: write}` だけが出て purge も再生成も起きず、書き込みが成功しても既存のページが残る(次の read は `cache_hit{layer: donut-view}`)。`#[Refresh]` / `#[Purge]` はその後に `command{source: RefreshInterceptor}` をもう 1 つ出し、`#[Refresh]` は purge して再生成する(次の read は `cache_hit`)、`#[Purge]` は `uri` の先を purge するだけ。次の read が `cache_miss{layer: donut-view}` になるのは、その `invalidate` の `tags` がこのページのタグと交わるとき(`uri` がこのページを指すなど)で、交わらなければ自動の再生成が残り `cache_hit{layer: donut-view}`。**1.16.2 以前は `#[RefreshCache]` だけ書き込みが実行されない**(下の注) |
| `#[DonutCache]` | close `cache_miss`。`put_donut` → `cdn_headers` → `save_donut`。**`save_etag` は出ない** — 全体を保存しないので ETag も無く、304 は返らない | close `cache_hit` + `refresh_donut` → `put_skipped{not-cacheable}`。毎回 donut を組み直すのが正常 | 書き込み側に `#[Purge]`/`#[Refresh]`/`#[RefreshCache]` を書かないと、`command` も `invalidate` も出ない |
| `#[Refresh]` / `#[Purge]` | — | — | `command` スコープに `purge` + `invalidate`。`pre_write_cleanup` 隣接**だけ**なら誰にも告げていない |
| `#[HttpCache]` | `cdn_headers` に literal ヘッダ | 同じ | — |

**1.16.2 以前は、書き込みメソッドに `#[RefreshCache]` を付けるとキャッシュが温まっている間その書き込みが
実行されない。** `DonutCacheModule` が `#[RefreshCache]` に **query 用の `DonutCacheInterceptor`** を束縛し、
それは `donutRepository->get()` が当たった時点で `proceed()` を呼ばずにキャッシュ済み表現を返す(HTTP
メソッドを見ていない)。実測: 先に GET してから DELETE すると `onDelete` の本体は **0 回**、応答は宣言した
204 ではなく **200 + キャッシュ済みの表現**。GET せずに DELETE すれば 1 回走る。

[#215](https://github.com/bearsunday/BEAR.QueryRepository/pull/215) で修正済み(次のリリースに入る)。
古い版を使っているなら書き込みは `#[Purge]` / `#[Refresh]` で宣言する。なお `#[Cacheable]` クラスでは
`#[RefreshCache]` は何も足さない — `CommandInterceptor` が既に purge して再生成する。

食い違いの読み方: **期待した `save_*` が無い**なら宣言が届いていない(§2 の「木が薄いとき」を
先に潰す)。**`invalidate` があるのに `cache_miss` にならない**ならタグが交わっていない(下記)。

### 依存が効いているかを確かめる手順

「親が子を埋め込んでいるから、子を purge すれば親も落ちる」は**コードを読んでも分からない**。
そのうえ**子との関係ごとに、記録される依存も purge 後の形も違う** — donut の親(`#[CacheableResponse]` /
`#[DonutCache]`)は同じ宣言のまま、子をデータ依存・描画中の `depends()` 呼び出し・穴の 3 通りに
分けて扱う:

| 親の宣言 / 子との関係 | 依存の記録 | 子を purge した後の親の read |
|---|---|---|
| `#[Cacheable]` | `depends_on` + `save_value` / `save_view` と `save_etag` の `tags` に子の URI タグ | close `cache_miss` — 値ごと作り直す |
| donut のデータ依存 — `onGet()` が読んだ子(`#[CacheableResponse]` / `#[DonutCache]` 共通) | `depends_on`(`put_donut` の**前**。`DonutRepository::classifyMaterials()` が呼ぶ `CacheDependency::depends()` が記録)+ `save_donut`(テンプレート)の `tags` に子の URI タグ。`#[CacheableResponse]` はさらに `save_donut_view` / `save_etag` にも | close `cache_miss` — **テンプレートごと**作り直す。`#[Cacheable]` と同じ形で、`refresh_donut` は出ない |
| donut の描画中の `depends()` — テンプレートのヘルパーなどが、注入した `CacheDependencyInterface` の `depends($page, $child)` を自分で呼ぶ | `depends_on`(`put_donut` の**後**、`save_*` の**前**)+ 上と同じ `tags` | 上と同じ — close `cache_miss` |
| donut の穴 — テンプレートだけが読み、`onGet()` は読まない(子のクラスが既定の `ETag` / `Surrogate-Key` ヘッダを持つときは表のすぐ下) | `dependency_skipped` を記録し(`reason` は `no_body`。`$body` に既定値を持つ子なら `no_etag_or_surrogate_key`、表のすぐ下)、`save_donut` には**乗らない**。子のタグは `#[CacheableResponse]` なら `save_donut_view` / `save_etag` に、`#[DonutCache]` なら応答の `Surrogate-Key`(`cdn_headers` の `surrogateKeys`)にだけ乗る | close は `cache_hit{layer: donut-view}` のまま。中に `refresh_donut` と子の入れ子 `get` が現れる。`refresh_donut` は `#[DonutCache]` なら warm な read でも毎回出るので証拠にならない。子の入れ子 `get` が `cache_miss` で閉じたうえで、手順 3 の条件がそろえば証拠になる |

テンプレートだけが読む子でも、子のクラスが既定値として `ETag` か `Surrogate-Key` のヘッダを持っていれば、
穴ではなくデータ依存の行の形になる。`classifyMaterials()` は `put_donut` の前に、その時点で子が持つヘッダで
分類する。まだ走っていない穴の子が持つのはクラスの既定値だけで、それがあればデータ依存に数えられる。
`depends_on` が `put_donut` の前に出て、`save_donut` の `tags` に子のタグが乗り、子を purge した後の親は
`cache_miss` で閉じる。

`dependency_skipped` の `reason` も、まだ走っていない子の `$body`(クラスの既定値)で決まる。既定値が `null` なら
`no_body`、値があれば `no_etag_or_surrogate_key` になる。どちらでも穴の形は変わらず、`save_donut` に乗らないし、
子を purge した後の親は `cache_hit{layer: donut-view}` で閉じる。

`#[DonutCache]` の穴について「依存が無い」と欠陥として報告するのは誤り。穴はテンプレートに入らないので
落とすものが無く、毎回組み直すのが設計判断だ。同じ `#[DonutCache]` でも、データ依存と描画中の `depends()` の
子なら `depends_on` が記録され、`save_donut` に子のタグが乗る。

手順も子との関係で分かれる:

1. 親をコールドで読む。**その関係が使う `save_*`** の `tags` に子の URI タグが入っていることを見る
   (`#[Cacheable]` なら `save_value`。donut のデータ依存と描画中の `depends()` なら `save_donut`
   (`#[CacheableResponse]` はさらに `save_donut_view` / `save_etag`)。donut の穴(既定のヘッダを持つ子を
   除く)は `save_donut` に乗らない仕様なので、`#[CacheableResponse]` なら `save_donut_view` /
   `save_etag`、`#[DonutCache]` なら `cdn_headers` の `surrogateKeys` を見る。入っていなければ `#[Embed]`
   ではなく値をコピーしている疑い)
2. 子の URI を purge する(`QueryRepositoryInterface::purge(new Uri(...))`)
3. 親をもう一度読む。`#[Cacheable]` と、データ依存または描画中の `depends()` の donut 親は
   **`cache_miss` で閉じれば依存は生きている**。穴だけは**閉じる型が `cache_hit` のまま**で、子の入れ子
   `get` が `cache_miss` で閉じる。それを purge が穴に届いた証拠と読めるのは、次がそろうときだけ
   (`refresh_donut` だけでは足りない):
   - 1 の子の `save_*` が `saved: true` で、その `tags` が 2 の `invalidate` の `tags` と交わる
   - 2 の `invalidate` の `roPool` が `invalidated`(`failed` ではない)
   - 3 の子の `get` スコープに `cache_error{operation: read}` が無い(あれば店を読めなかっただけ)
   - 子が実際に持つ寿命が、1 の保存から 3 の入れ子の `get` までの経過より長い。`requestedTtl` が `0` / `null`
     でも期限なしとは限らない — QueryRepository は期限を付けず、pool の既定の寿命(Symfony の adapter の
     `defaultLifetime`)が効く。既定が `0` なら期限なし

`#[Cacheable]` では `depends_on` が 1 の裏付け、2→3 が実証。効果だけ(値が変わった)で判定すると、
TTL で落ちただけの場合と区別できない。

**入れ子の `get` スコープは依存の証拠にならない。** 親のスコープの中に子のスコープが現れるのは、
親の実行中に子を読んだという事実だけを言う。`ResourceInterface` を注入して手で読んでも同じ形になり、
`depends_on` もタグ伝播も起きない。見るのは入れ子ではなく、`#[Cacheable]` と、データ依存または描画中の
`depends()` の donut 親なら `depends_on` と `save_donut` を含む `tags`、穴なら子の入れ子 `get` の close と
手順 3 の条件。

**`#[Embed]` を付けただけでは足りない。** データ依存を登録するのは `#[Cacheable]` では
`QueryRepository::setCacheDependency()`、donut では `DonutRepository::classifyMaterials()`(`put_donut`
の前に `$ro->body` を走査する)で、どちらも保存時に **`$ro->body` に `AbstractRequest` のインスタンスが
残っているものだけ**を辿る。埋め込んだ値をスカラーに解決して body を作り直すと、その時点で Request は
body から消えており、宣言がどれでも依存は登録されない。body に Request を残すか、announce 側で解決する。
`ResourceObject` を body に置いても辿られないので、その子は注入した `CacheDependencyInterface` で
`$cacheDependency->depends($page, $child)` を明示的に呼ぶ。描画中の `depends()` は自動では登録されず、
アプリ自身がテンプレートのヘルパーなどから呼んで初めて記録される。穴は `ResourceDonut::create()`
が `DonutRequest` でプレースホルダに包んで遅延実行するだけで、依存としては登録されない。

もう 1 つ条件がある。`#[Cacheable]` の `setCacheDependency()` は子を走らせたあと、子に `ETag` も
`Surrogate-Key` も無ければ `dependency_skipped` を記録して `continue` する — どちらか一方があれば足りるが、
子自身にキャッシュ宣言が無くどちらも持たなければ、Request が残っていても `depends_on` は出ない。donut の
`classifyMaterials()` も同じ条件で `continue` する。その条件を満たしたデータ依存の子のタグは
`#[CacheableResponse]` でも `#[DonutCache]` でも `save_donut`(テンプレート)に残り、`#[CacheableResponse]`
はさらに `save_etag` / `save_donut_view` にも残す。店の `save_donut` に残らないのは穴のタグだけで、
`#[DonutCache]` の穴は応答の `Surrogate-Key` に載せて終わる。

### タグと TTL のどちらが要るかは、ログでは決まらない

タグだけ宣言しても、**そのタグを announce する書き込み経路がある値**しか新しく保てない。body に他所の値を
コピーしていて announce 経路が無ければ、タグは届かず TTL だけが床になる。コードに問う:

1. この body に入る値ごとに、**それを変える書き込み経路**は何か
2. その経路は invalidate を呼ぶか(呼ばないなら TTL が唯一の eviction)
3. 同じデータの**別の複製**があるなら、複製の寿命は原本以下か(長いと、原本が捨てた値を複製が返す)

3 は attribute から機械的に検査できる。実例:
[BeMart `tests/Resource/AgentCorpusCacheTest.php`](https://github.com/be-framework/BeMart/blob/1.x/tests/Resource/AgentCorpusCacheTest.php)

## 5. 切り分け — アプリの問題か、ライブラリの問題か

木は「何が起きたか」を言うが、「誰の落ち度か」は言わない。**先に観測で候補を 1 つに絞り、
それから該当行の変数を見る**。順番を逆にすると、写しを覗くだけで終わる。

| 観測 | アプリ側 | ライブラリ側 | 決め手 |
|---|---|---|---|
| 期待した `get` スコープが無い(miss すら出ない。木全体が空とは限らず、その 1 本だけ欠けることがある) | `final`(`ReflectionClass::isFinal()` が true)、属性そのものが無い、文脈が店を束縛していない | sink が arm を拒否 | 織られたか(`get_class`)→ 真なら `isFinal()` と属性の有無で二分 / `error_log` |
| 期待した `save_*` が無い | `put_skipped` の `reason` がアプリ由来(自前 ETag・非 200) | `put_skipped` も無いのに保存されない | `put_skipped` の有無と `reason` |
| `save_*` の `tags` に子が無い | 値をコピーしている(`#[Embed]` でない)、`#[Cacheable]` または donut のデータ依存の子が `ETag` も `Surrogate-Key` も持たない(子自身にキャッシュ宣言が無い。`dependency_skipped{reason: no_etag_or_surrogate_key}` が出る)、または子が穴で `onGet()` が読んでいない(`dependency_skipped` の `reason` は `no_body`、`$body` に既定値を持つ子なら `no_etag_or_surrogate_key`。`save_donut` に乗らないのが仕様) | 伝播の欠陥 — `#[Cacheable]` で `depends_on` はあるのにタグが乗らない、donut のデータ依存または描画中の `depends()` で `depends_on` はあるのに `save_donut` にタグが乗らない(`CacheDependency::depends()` が親の `Surrogate-Key` に子タグを積む) | まず親と子の関係。`#[Cacheable]` なら `depends_on` の有無と `save_value` / `save_view`(と `save_etag`)の `tags`。データ依存か描画中の `depends()` の donut 親なら `depends_on` の有無と `save_donut` の `tags`。穴なら `save_donut` には最初から乗らない仕様なので、`cdn_headers` の `surrogateKeys` と(`#[CacheableResponse]` のみ)`save_donut_view` / `save_etag` の `tags` を並べる: `surrogateKeys` にあって `tags` に無い = ライブラリ側、どちらにも無い = アプリ側(Request が body に残っていない) |
| 書き込みが `invalidate` を出さない | 書き込み経路に `#[Refresh]`/`#[Purge]` が無い | 属性はあるのにマッチャがそのメソッドを拾わない | 織られたオブジェクトの `bindings` にそのメソッドがあるか |
| `invalidate` は出るが親が hit のまま(子が穴なら正常。§4 の手順 3 で見る) | タグの選び方が違う(URI タグと共有サロゲートキーの混同) | タグ集合の交差計算の欠陥 | 2 つのタグ集合を並べて交わりを見る |
| `invalidate` のタグがどのリソースの宣言とも一致しない | **手書きの `invalidateTags()` が定数からドリフトしている** — リソースは `SURROGATE_KEY` 定数を宣言し、無効化側は生文字列を持ったまま取り残された | — | `invalidate` の `tags` を、リソースが宣言する定数の実値と 1 文字ずつ突き合わせる。docblock だけ正しいことがある |
| `conditional_request` スコープが 1 つも無い | — | — | **まず要求側を見る**。`If-None-Match` を送っていない要求なら正常で、CLI からの要求は送らない。送ったと確かめた上で無いときだけ、プロキシ・WAF・CDN が落としている |
| 値が古い | TTL が床(設計判断) | — | `save_*` の `requestedTtl` |
| `cache_error` / `pool_error` | 店の設定・接続 | 縮退の扱い | 例外か縮退かは `docs/what-the-log-proves.md` |

判断点の在処: `CacheInterceptor`(hit/miss と保存)、`CommandInterceptor`(書き込み後の無効化)、
`QueryRepository`(put/get/purge)、`ResourceStorage`(タグと 2 プール)、`CacheDependency`(`depends_on` の記録と
親の `Surrogate-Key` への積み込み — `#[Cacheable]`・donut のデータ依存・描画中の `depends()` 呼び出し、
いずれの経路でも)、`DonutRepository`(`classifyMaterials()` がデータ依存を分類し、`templateKeys()` が
`save_donut` に乗るタグを決める)/ `SurrogateKeys`(穴の子タグと URI→タグ変換)、
`HttpCache` / `CliHttpCache`(304 判定)、`EtagSetter`(ETag 生成)。

**「ライブラリ側」に振った候補が 1 つ残ったときだけ**、該当クラスの分岐を観測する。道具は
`koriym/xdebug-mcp`(`composer global require koriym/xdebug-mcp`)。**php 本体に Xdebug が入っていなくてよい**
— ラッパーが自分で読み込む。**vendor は既定で記録から除外される**ので、明示して入れる:

```bash
# 1) どの分岐まで届いているか(--include-vendor が無いとライブラリのフレームは空)
xtrace --include-vendor="bear/query-repository" --context="tags absent from parent save" -- php bin/page.php get /orders

# 2) 判断している行を名前で探す(行番号は版ごとに動く)
grep -n "SURROGATE_KEY" vendor/bear/query-repository/src/CacheDependency.php

# 3) その行で実引数を見る
xstep --break="vendor/bear/query-repository/src/CacheDependency.php:<line>" \
  --steps=20 --context="what tags arrive" -- php bin/page.php get /orders
```

`xtrace` はスクリプトの stdout をそのまま流し、**トレース自体は `/tmp/trace.*.xt` に書いて**末尾に場所と
規模を出す(実測: 1 リクエストで 63688 行 / 534 関数)。読むのはそのファイルで、全部は読まない —
`--context` に何を見たいかを書き、ファイルは目的の関数名で `grep` する。

**`xstep` の前に、ライブラリの既存テストを 1 本走らせる。** 同じシナリオを守るテストが既に赤なら、
切り分けはそこで終わる(実測: `--filter testMultipleParentsDependOnSameChild tests/CacheDependencyTest.php`
が伝播の欠陥をそのまま赤で再現した)。スイート全体は走らせない。

**`var_dump` / `printf` を仕込まない。** 使い捨てスクリプトに同じロジックを書き写して覗くのは、対象ではなく
写しを見ている(写し間違いに気づけない)。再現スクリプトは**対象を呼ぶだけ**にする。

## 6. 報告

切り分けの結論は、次の 6 つで書く。1 つでも欠けたら、まだ報告できていない:

1. **どちらの問題か** — アプリ / ライブラリ / 設計判断のどれか
2. **観測の根拠** — 木の抜粋(`save_value{tags: [...]}` → `invalidate{tags: [...]}` の形)と、
   ライブラリを疑うなら `xstep` で見た実引数。「トレースした」と書くのは実際にツールを使ったときだけ
3. **最小再現** — 対象を呼ぶだけのスクリプトかテスト 1 本
4. **ユーザーが何を感じるか** — 「在庫が最大 60 秒古い」のように、外から見える言葉で
5. **修正の証明** — 直したなら、**どのイベントがどう変わったかを木で示す**。テストが緑になったことは
   証明ではない(テストは同じ盲点を持ち得る)
6. **既存の検証はこれを検出できたか** — テスト・オラクル・CI を実際に走らせて答える。緑だったなら
   **その検証がアプリのコードパスを通っているか**を確かめる。ライブラリの同じ能力を直接叩いて
   代替検証しているだけなら、アプリ側の誤りは原理的に見えない(実例: オラクルが `purgeTags` を
   `ResourceStorageInterface::invalidateTags()` に直接渡すと、アプリの無効化サービスを一度も通らない)

**ライブラリだと言うなら、ライブラリの fixture で落ちるテストを添える。添えられないなら、
まだアプリ側の疑いに戻す。**

## 範囲外

観測文脈は開発用だ。body は全部ディスクに落ち、記録は無条件。本番で同じことを
したいなら `ProdQueryRepositoryLogModule`(サンプリングと flush 時の判定を持つ)を見る。
セッションは URI(クエリ文字列込み)・client validator・生の例外文を含むので、本番の出力先に何を流すかは
先に決める(scrub や流量制限は `LogWriterInterface` を decorate する)。

CDN 側の実挙動(伝播遅延・エッジ側 eviction)、`#[HttpCache]` の静的ヘッダ設定、独自ヘッダ名のカスタム
setter、実時間での期限消滅は、どちらの文脈のログにも出ない。詳細は `what-the-log-proves.md` の
"What the log does not record"。
