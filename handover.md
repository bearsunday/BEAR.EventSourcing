# Handover — BEAR.EventSourcing

クラウド (Claude Code on the web) のセッションからローカル開発へ引き継ぐためのドキュメント。

## このリポジトリの現状

- **ブランチ**: `claude/init-project-setup-49Jtg`
- **PR**: #3 (`bearsunday/BEAR.EventSourcing`、base `1.x`、ready for review)
- **最新コミット**: `c13aa41`
- **CodeRabbit**: 2 ラウンドのレビューを反映済み、merge ready 判定
- **テスト**: root 38 / vendor-slogger 6、PHPStan level 6 clean、PHPCS (PSR-12) clean

## アーキテクチャ (3 層)

```
koriym/semantic-logger     汎用の構造化ログ (open/close/event ツリー)
        ▲
bear/semantic-logger       vendor-slogger/ にモノレポ風に同梱。
                           BEAR\Resource\LoggerInterface 実装 (= SemanticLogger bridge) +
                           ResourceRequest/ResponseContext。
        ▲
bear/event-sourcing        本体。Events::fromSemanticLog() がログツリーを walk して
                           Event を抽出、EventStore が永続化。
```

記録は AOP インターセプタではなく、BEAR.Resource 既存の `LoggerInterface` フック経由。

## ローカルでの動かし方

```bash
# 1. root (bear/event-sourcing)
composer install
composer test          # PHPUnit 38 tests
composer stan          # PHPStan level 6
composer cs            # PHP_CodeSniffer (PSR-12)
php demo/record-and-replay.php   # end-to-end demo、exit 0 で成功

# 2. 同梱パッケージ (bear/semantic-logger)
cd vendor-slogger
composer install
composer test          # PHPUnit 6 tests
cd ..
```

- PHP 8.1+ 必須 (検証は 8.4.19 で実施)
- `vendor/` と `composer.lock` は gitignore 済。`vendor-slogger/` は composer の path
  repository として root から参照され、`composer install` 時に
  `vendor/bear/semantic-logger` へ symlink される。

## 重要な落とし穴

### SemanticLoggerModule は `override()` で入れる

`BEAR\Resource` の `ResourceClientModule` が先に `LoggerInterface → NullLogger` を
バインドしているため、プレーンな `install(new SemanticLoggerModule())` では
**無言で何も記録されない**。必ず:

```php
$this->override(new SemanticLoggerModule());
```

`tests/ResourceClientIntegrationTest.php` がこの配線を実機検証している。

## 残作業 — ローカルに移ると解消できるもの

クラウドセッションでは GitHub App のパーミッション制約で着地できなかった 3 件。
ローカルなら通常の `git push` 権限・リポジトリ作成権限で解決できる。

### #1 CI ワークフローの追加 (ローカルなら即解決)

クラウドの GitHub App は `.github/workflows/*.yml` を push できなかった。
ローカルで以下のファイルを作成してコミット・push するだけ。

`.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
  pull_request:
  workflow_dispatch:

jobs:
  test:
    name: Tests / PHP ${{ matrix.php-version }}
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php-version:
          - '8.1'
          - '8.2'
          - '8.3'
          - '8.4'
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP ${{ matrix.php-version }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          coverage: none
          tools: none

      - name: Get composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> "$GITHUB_OUTPUT"

      - name: Cache composer
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-php${{ matrix.php-version }}-composer-${{ hashFiles('composer.json', 'vendor-slogger/composer.json') }}
          restore-keys: |
            ${{ runner.os }}-php${{ matrix.php-version }}-composer-
            ${{ runner.os }}-composer-

      - name: Install root dependencies
        run: composer install --no-interaction --no-progress --prefer-dist

      - name: Install vendor-slogger dependencies
        working-directory: vendor-slogger
        run: composer install --no-interaction --no-progress --prefer-dist

      - name: Test (bear/event-sourcing)
        run: composer test

      - name: Test (bear/semantic-logger)
        working-directory: vendor-slogger
        run: composer test

  static-analysis:
    name: Static analysis (PHP 8.4)
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: none

      - name: Install root dependencies
        run: composer install --no-interaction --no-progress --prefer-dist

      - name: PHPStan
        run: composer stan

      - name: PHPCS
        run: composer cs
```

```bash
mkdir -p .github/workflows
# 上記内容を .github/workflows/ci.yml に保存
git add .github/workflows/ci.yml
git commit -m "Add CI workflow"
git push
```

### #2 vendor-slogger/ を独立リポジトリに分離

現状 `bear/semantic-logger` は `vendor-slogger/` に同梱され、root の `composer.json`
が path repository で参照している。外部から `composer require bear/event-sourcing`
できるようにするには独立リポジトリ + Packagist 登録が必要。

手順:

1. `bearsunday/BEAR.SemanticLogger` リポジトリを GitHub に新規作成
2. `vendor-slogger/` の中身をそのリポジトリにコピーして push
   (`composer.json`, `src/`, `tests/`, `phpunit.xml`, `README.md` が揃った自己完結状態)
3. Packagist に `bear/semantic-logger` を登録 (または VCS repository として参照)
4. root の `composer.json` を修正:
   - `repositories` の path 設定を削除 (または VCS に変更)
   - `bear/semantic-logger` の制約を `@dev` から実バージョンへ
5. root から `vendor-slogger/` ディレクトリを削除
6. `.gitignore` から `vendor-slogger/` 関連行を削除
7. `CLAUDE.md` / `README.md` の「monorepo-style development layout」記述を更新

### #3 minimum-stability の引き締め

`koriym/semantic-logger` がまだ 1.0 stable 未リリースのため、root と vendor-slogger
の両 `composer.json` が `minimum-stability: dev` / `^1.0@dev` になっている。
upstream が 1.0 を切ったら:

- `koriym/semantic-logger` の制約を `^1.0` に
- `minimum-stability` を `stable` に戻す (`prefer-stable` は残してよい)
- `bear/semantic-logger` も #2 完了後は実バージョン指定に

## 推奨する次の一手 (ローカル移行後)

1. `composer install` で動作確認 → `composer test` / `stan` / `cs` が緑
2. #1 CI ファイルを push し、Actions が緑になることを確認
3. #2 を実施して配布可能にする
4. #3 を upstream の状況に応じて
5. PR #3 をマージ

## スコープ外 (将来課題、認識のみ)

- `ResourceRequest/ResponseContext` の実 JSON Schema 公開 (`SCHEMA_URL` は現状 placeholder)
- `EventStoreInterface::getEvents()` のページネーション
- Snapshot 機構 / イベント購読 (pub-sub)
- `koriym/semantic-logger` の Profile/timing データ活用


## PR #2 EC-CUBE port note

PR #2's EC-CUBE port material is retained as prototype application-layer code. Its older EventSourcing core and AOP interceptor were not kept; the repository continues to use the current EventSourcing core and Semantic Logger bridge from `1.x`.

The root CI/quality scripts intentionally validate the current EventSourcing core paths, while leaving the retained EC-CUBE prototype available for a later application-focused hardening pass.
