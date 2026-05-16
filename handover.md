# Handover: BEAR.EventSourcing

## 現状

このリポジトリは**Event Sourcingライブラリ**として整理されました。

- Package: `bear/event-sourcing`
- Namespace: `BEAR\EventSourcing`
- Type: library

## 完了済み

1. EC-CUBEポートコード（src-v2/）を削除
2. namespaceを `BEAR\EventSourcing` に統一
3. composer.jsonをライブラリ用に修正
4. README.mdをライブラリドキュメントに更新

## 次のステップ

### 1. BEAR.EventSourcingライブラリの整理

現在の `src/` には以下が混在している：

| ディレクトリ | 内容 | ライブラリに必要？ |
|-------------|------|------------------|
| `EventSourcing/` | Event, EventStore | ✅ 必要 |
| `Interceptor/EventSourcingInterceptor.php` | AOP | ✅ 必要 |
| `Module/EventSourcingModule.php` | DI Module | ✅ 必要 |
| `Entity/` | EC-CUBE Entity | ❌ 削除すべき |
| `Query/` | EC-CUBE Query | ❌ 削除すべき |
| `Resource/` | EC-CUBE Resource | ❌ 削除すべき |
| `Service/` | EC-CUBE Service | ❌ 削除すべき |
| `Auth/` | 認証 | ⚠️ 別ライブラリ化検討 |
| `Validation/` | バリデーション | ⚠️ 別ライブラリ化検討 |

**推奨**: Event Sourcing以外のコードを削除し、純粋なライブラリにする

### 2. EC-CUBEポートを継続する場合

新規リポジトリ `bearsunday/BEAR.Eccube` を作成：

```bash
# 新規リポジトリ作成
mkdir BEAR.Eccube && cd BEAR.Eccube
composer init --name=bear/eccube

# BEAR.EventSourcingを依存に追加
composer require bear/event-sourcing

# Outside-Inアプローチで開発
# 1. FakeJSON作成
# 2. JsonSchema定義
# 3. FakeQuery実装
# 4. Resource実装
# 5. テスト
```

参考リポジトリ: https://github.com/bearsunday/MyVendor.Cms

### 3. テスト追加

現在テストがない。最低限以下を追加：

- `tests/EventSourcing/EventTest.php`
- `tests/EventSourcing/EventStoreTest.php`
- `tests/Interceptor/EventSourcingInterceptorTest.php`

### 4. CI設定

- GitHub Actions for PHPUnit, PHPStan
- Packagist登録（公開する場合）

## PR

- https://github.com/bearsunday/BEAR.EventSourcing/pull/2

## 関連情報

- EC-CUBEソース: https://github.com/EC-CUBE/ec-cube
- BEAR.Sunday: https://bearsunday.github.io/
- MyVendor.Cms（参考）: https://github.com/bearsunday/MyVendor.Cms
