# observe デモ — 観測機能の網羅ウォークスルー

## これが何か

ライブ BEAR.Resource アプリで、BEAR.EventSourcing の観測パイプライン全体を端から端まで動かす網羅デモ。

他の examples（`extract` / `store` / `replay` / `tree`）が手組みの fixture ログ（`examples/semantic-log.json`）を使うのに対し、これは実 DI 配線（`DevLogModule` が実 `InvokerInterface` を装飾）でリクエストを実行し、実際に観測する。

```text
BEAR.Resource 実行 → DevLogModule → Semantic Logger ツリー → SemanticLogExtractor → Events → EventStore → replay
```

## 実行

```bash
composer observe            # デフォルト PHP
php examples/observe/observe.php
```

- SQL 節（[8]）は aura/sql >= 6 なら PHP 8.4 でも動く。aura/sql < 6 の環境では PHP >= 8.4 でロード不可のため skip 表示になる（テストスイートと同じガード条件）。
- 動作確認済み: PHP 8.4 / 8.3 とも全節動作（stored rows: 1、recorded_at `+00:00`）。

## 構成

| ファイル | 役割 |
|---|---|
| `observe.php` | メイン駆動（10 節） |
| `Resource/App/Orders.php` | 実リソース。`onPost` が同一 invoker 経由で inventory を PUT（実ネスト）、`onGet`、失敗 `onDelete`(409) |
| `Resource/App/Inventory.php` | 子リソース（`onPut`） |
| `EventStoreModule.php` | アプリ所有の SQL EventStore 配線（MediaQuery/DB はアプリ側。`tests/Fixture/MediaQueryEventStoreAppModule` と同構成） |

`FakeApp` 名前空間は `observe.php` 冒頭の `$loader->addPsr4('FakeApp\\', __DIR__)` で解決。リソースは AppAdapter 規約 `{App}\Resource\App\{Class}`。

## 見どころ（各節が証明すること）

1. **ライブ観測**: `DevLogModule` が実 invoker を装飾。POST orders が内部で PUT inventory を呼ぶ構造が、開閉ツリーの**親子として実測でネスト**する（fixture では手で並べるだけ）。
2. **ツリー描画**: stree + `ResourceNodeFormatter`。意図（method+uri+params）はインライン、重い詳細は `body_ref` の背後。トークン最小・AI 可読。fixture の `tree.php` 出力（`examples/semantic-tree.txt`）と同型。
3. **body 外部化**: `FileBodyStore` が body を連番ファイル（`000001.json`…）に書き、ログには `body_ref` だけ。GET も `WITH_READS` で記録。
4. **記録と観測の境界**: 抽出はルート（境界）の書き込みだけ。POST orders の内部で走る PUT inventory は、POST を再実行すれば handler がもう一度発行するので**イベントにしない**（両方記録すると再生で二重適用になる）。GET は観測データ、409 の失敗 DELETE は抽出で落ちる（code >= 400）。3 つとも [2] のツリーには残る。
5. **決定的イベント ID**: 同一ログの再抽出で同一 id（sha256(method+uri+UTC timestamp+params)）。冪等の根拠。
6. **フィルタ**: `Events` にクエリメソッドは持たせず、標準イテレータ（`CallbackFilterIterator`）で選択。inventory で絞ると 0 件になるのが、入れ子 PUT がストリームではなく観測ログにいることの実測。
7. **InMemoryEventStore**: 同一イベントの再 append で重複しない（id キー）。
8. **SQL EventStore**: Ray.MediaQuery 経由の SQLite 永続化。timestamp は UTC 保存（`format('P')` で `+00:00` を表示）。
9. **リプレイ**: 保存ストリームを順序どおり反復。境界イベント（POST orders）1 件だけが再生の入力になる。
10. **スキーマ検証**: 各コンテキストが `schemaUrl` を宣言し、`docs/schemas/` に対してオフライン検証。契約が壊れるとデモが非ゼロで終了する。close context の `durationMs`（子を含む実測 wall time）もこのスキーマの一部。

## 足りないところ（別 AI が過剰に一般化しないために）

- **`ResourceObservationModule` / `NullBodyStore`（本番経路）は未演示**。デモは `DevLogModule`（開発）+ `FileBodyStore` のみ。本番は `module:` で BEAR.Resource モジュールを包み、既定で body を保存しない `NullBodyStore`。カスタム `BodyStoreInterface`（SQL / オブジェクトストレージ）も未カバー。
- **`EventSourcingModule`（`SemanticLogExtractorInterface` 束縛）は未使用**。デモは extractor を直接 `new` している。
- **EventCollector（request-end ハンドラー統合）は未演示**。空セッションは `flush()` が空ログを返す（semantic-logger 0.9）ので例外処理は不要になった。`EventCollector` の契約は `tests/EventCollectorTest.php` を参照。
- **例外パスを未演示**。ログに載る失敗は 409 レスポンス（正常 invoke）のみ。リソースが例外を投げた場合の exception context 記録はカバーしていない。
- **非 `resource_request` ノード（例: Ray.MediaQuery の `media_query` リーフイベント）はデモのツリーに登場しない**。`MediaQueryObservationModule` の配線例は `tree.php` fixture と README の Ray.MediaQuery 節を参照。
- **`Event::$result` が null になること（`body_ref` はイベントに持ち込まない）を明示表示していない**。[4] は method/uri/params のみ表示。この境界を実例で見たい場合は print を足す。
- **GET をイベントとして抽出（開発時リード追跡）は未演示**。ログには GET が記録されるが、extract は既定（書き込みのみ）。`replay.php` 例が `includeReads` を示す。
- **再実行による再生は未演示**。[9] はストリームを反復するだけで、POST orders を handler に再投入して入れ子 PUT が再発行されることは見せていない。再生の前提条件（handler の決定性、リクエスト = トランザクション境界）は README「Recording and observation」を参照。
- **イベント内容の assert はしない**。[10] でログの形はスキーマ検証される（違反で exit 非ゼロ）が、抽出イベントの値までは assert しない。効果 + ログの両面固定はテストスイートの仕事。
- **`appendAll` の非アトミック性・トランザクションでの包み方は未演示**（README 本文の運用注意の範囲）。
- **QueryRepository との統合ツリー（1 リクエスト = 1 本の木）はこのデモに含まれない**。共有 logger の束縛レシピは README「One tree with BEAR.QueryRepository」、証明は `tests/UnifiedLogTest.php`。

## 他 AI 向けメモ

- 公開スキーマの正典はこの repo の `docs/schemas/`（project pages で `https://bearsunday.github.io/BEAR.EventSourcing/schemas/` に公開）。
- 一時ディレクトリ（`sys_get_temp_dir()`）で body / sqlite を作り、終了時に `FileBodyStore::clearDirectory` で後始末。リポジトリを汚さない。
- `RenderConfig` は `TreeRenderer` の**コンストラクタ**に渡す（semantic-logger 0.9: `new TreeRenderer($config)->render($log)`）。`render()` に第 2 引数を渡しても黙って無視される。
- CI 固定: `tests/ExamplesTest.php` が本デモを exec し、exit code（[10] のスキーマ検証込み）と出力アンカー（実測ネスト行・抽出イベント数 1・`inventory events: 0`・冪等 append・replay 1 件・検証成功行）を assert する。
- `examples/` は `.gitattributes` で `export-ignore` のため、デモは repo に残るが dist パッケージには入らない（既存 examples と同じ扱い）。
