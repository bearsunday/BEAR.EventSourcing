# BEAR.Sunday Port Skill

大規模なPHPプロジェクトをBEAR.Sundayに移植するためのスキル。

## 前提

移植元プロジェクトには**動くコードとテスト**がある。これは：
- 実装の詳細がすべて明示されている
- 期待される動作がテストで定義されている
- 仕様書がなくても、コードが仕様である

「暗黙知」「設計意図」という言い訳は不要。**すべてはコードに書いてある。**

## Phase 1: 分析

### 1.1 ソースプロジェクトのクローン

```bash
git clone <source-repo> /tmp/source-project
```

### 1.2 構造分析

以下を特定する：

| 分析対象 | 確認項目 |
|---------|---------|
| エントリポイント | どこからリクエストが入るか |
| ルーティング | URLとコントローラのマッピング |
| エンティティ | ドメインモデル一覧 |
| リポジトリ/DAO | データアクセス層 |
| サービス | ビジネスロジック |
| テスト | 単体テスト、統合テスト |
| 設定 | 環境設定、DI設定 |

### 1.3 依存関係マップ

```
Controller → Service → Repository → Entity
                    → External API
```

## Phase 2: マッピング定義

### 2.1 構造変換ルール

| ソース (Symfony/Laravel等) | BEAR.Sunday |
|---------------------------|-------------|
| Controller | Resource (App) |
| Entity | Entity |
| Repository | Query Interface + Impl |
| Service | Service Interface + Impl |
| Form/Request | JsonSchema |
| EventListener | Interceptor |
| Command | Resource (App/Cli) |

### 2.2 BEAR.Sunday標準構成

```
src/
├── Module/           # DI設定
├── Resource/
│   └── App/          # APIリソース
├── Query/            # Queryインターフェース
│   └── Impl/         # SQL実装
├── Service/          # ビジネスロジック
├── Entity/           # ドメインモデル
└── Interceptor/      # AOP
var/
├── sql/              # SQLファイル
└── schema/           # JsonSchema
```

### 2.3 技術選定

| 機能 | 推奨ライブラリ |
|-----|--------------|
| DB Query | Ray.MediaQuery |
| DI | Ray.Di |
| AOP | Ray.Aop |
| Validation | BEAR.Resource + JsonSchema |
| Auth | 独自実装 or 既存ライブラリ |

## Phase 3: 移植順序

### 3.1 依存関係の逆順で移植

```
1. Entity（依存なし）
2. Query Interface（Entityに依存）
3. Query Impl + SQL（Interfaceに依存）
4. Service Interface
5. Service Impl（Query, Entityに依存）
6. Resource（Service, Queryに依存）
7. Module（全体をワイヤリング）
```

### 3.2 機能単位で完結させる

❌ 悪い例：全Entityを作成 → 全Queryを作成 → ...
✅ 良い例：Product機能を完全に移植 → Customer機能を完全に移植 → ...

理由：
- 早期に動作確認可能
- 問題の特定が容易
- 進捗が明確

## Phase 4: テスト移植

### 4.1 テストから仕様を読み取る

```php
// ソースのテスト
public function testCreateOrder(): void
{
    $order = $this->orderService->create($customer, $cart);
    $this->assertEquals(OrderStatus::NEW, $order->getStatus());
    $this->assertEquals($cart->getTotal(), $order->getTotal());
}
```

これから読み取れる仕様：
- 注文作成時のステータスは「新規」
- 注文合計はカート合計と一致

### 4.2 BEAR.Sundayテストへの変換

```php
// BEAR.Sundayのテスト
public function testCreateOrder(): void
{
    $ro = $this->resource->post('app://self/orders', [
        'customer_id' => 1,
        'cart_id' => 'xxx',
    ]);
    $this->assertSame(201, $ro->code);
    $this->assertSame(1, $ro->body['order_status_id']); // NEW
}
```

### 4.3 テストカバレッジの維持

```bash
# ソースのテスト数を確認
find /tmp/source-project/tests -name "*Test.php" | wc -l

# 同等以上のテストを移植先で作成
```

## Phase 5: 段階的検証

### 5.1 各フェーズでの確認

```bash
# 1. 静的解析
composer phpstan

# 2. テスト実行
composer test

# 3. 実際のリクエスト
./vendor/bin/bear.app get 'app://self/products'
```

### 5.2 移植完了の定義

- [ ] 全機能のResourceが存在する
- [ ] 全テストがパスする
- [ ] 静的解析エラーがない
- [ ] 元プロジェクトと同等のAPI応答

## Phase 6: 他スキルとの連携

移植作業では、BEAR.Skillsの他スキルを活用して品質を確保する。

### 6.1 スキル連携フロー

```
[ソース分析]
     ↓
[bear-resource-generator] ← 仕様からResource雛形を生成
     ↓
[sql-quality] ← SQL品質チェック
     ↓
[bear-resource-test] ← テスト自動生成
     ↓
[bear-hypermedia] ← Link属性追加
     ↓
[bear-review] ← 最終レビュー
```

### 6.2 各スキルの活用タイミング

| フェーズ | 使用スキル | 目的 |
|---------|-----------|------|
| Resource作成 | `bear-resource-generator` | 仕様からResource/Query/SQLを生成 |
| SQL作成後 | `sql-quality` | EXPLAIN分析、インデックス最適化 |
| Resource作成後 | `bear-resource-test` | dataProvider形式のテスト生成 |
| API設計後 | `bear-hypermedia` | Link属性追加、状態遷移定義 |
| 機能完成後 | `bear-review` | PHPMD/BEAR固有パターンレビュー |

### 6.3 連携の具体例

**Step 1: bear-resource-generator で雛形生成**

ソースの Controller/Repository を分析し、仕様を抽出：
```
# ソースから読み取った仕様
Product:
  - id: int
  - name: string (required)
  - status_id: int (1=公開, 2=非公開)
  - price: decimal

→ bear-resource-generator に渡して Resource/Query/SQL を生成
```

**Step 2: sql-quality でSQL品質チェック**

```bash
vendor/bin/sql-quality analyze var/sql/
```

問題があれば修正：
- フルテーブルスキャン → インデックス追加
- 関数使用 → 範囲クエリに書き換え

**Step 3: bear-resource-test でテスト生成**

```php
// 自動生成された dataProvider
public static function resourceProvider(): array
{
    return [
        ['GET', '/products', [], 200],
        ['GET', '/products?id=1', [], 200],
        ['POST', '/products', ['name' => 'test', 'status_id' => 1], 201],
    ];
}
```

**Step 4: bear-hypermedia でリンク追加**

```php
#[Link(rel: 'item', href: '/products/{id}')]
#[Link(rel: 'edit', href: '/products/{id}', method: 'PUT')]
public function onGet(): static
```

**Step 5: bear-review で最終チェック**

- Cyclomatic Complexity
- BEAR.Sunday固有パターン
- 層の責務分離

### 6.4 スキル不使用のリスク

| スキル未使用 | 発生するリスク |
|-------------|--------------|
| bear-resource-generator | 非標準的な構造、手作業による品質ばらつき |
| sql-quality | N+1問題、インデックス未使用 |
| bear-resource-test | テスト漏れ、回帰バグ |
| bear-hypermedia | REST Level 2止まり、遷移不明確 |
| bear-review | 技術的負債の蓄積 |

## アンチパターン

### ❌ やってはいけないこと

1. **ソースを読まずに想像で書く**
   - コードを読め。テストを読め。

2. **計画なしに実装を始める**
   - 最初にPhase 1-2を完了させる

3. **BEAR.Sundayのイディオムを無視する**
   - Ray.MediaQueryを使わずに直接PDO
   - Interceptorを使わずに各メソッドで認証チェック

4. **一度に全部移植しようとする**
   - 機能単位で完結させる

5. **テストを後回しにする**
   - テストも同時に移植する

### ✅ やるべきこと

1. **ソースコードを徹底的に読む**
2. **小さく始めて動作確認**
3. **BEAR.Sundayの規約に従う**
4. **不明点は既存スキルを参照**

## チェックリスト

### 移植開始前

- [ ] ソースプロジェクトをクローンした
- [ ] 構造分析を完了した
- [ ] マッピング定義を作成した
- [ ] 移植順序を決定した
- [ ] BEAR.Sunday標準構成を準備した

### 各機能移植時

- [ ] ソースのコードを読んだ
- [ ] ソースのテストを読んだ
- [ ] Entity を作成した
- [ ] Query Interface を作成した
- [ ] SQL / Query Impl を作成した
- [ ] Service を作成した（必要な場合）
- [ ] Resource を作成した
- [ ] テストを移植した
- [ ] 動作確認した

### 移植完了時

- [ ] 全機能が移植された
- [ ] 全テストがパスする
- [ ] 静的解析がパスする
- [ ] APIドキュメントがある
