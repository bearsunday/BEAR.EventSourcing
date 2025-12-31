# 移植例: EC-CUBE → BEAR.Sunday

## 概要

EC-CUBE 4.x (Symfony/Doctrine) を BEAR.Sunday に移植する例。

## Phase 1: 分析

### 1.1 EC-CUBEの構造

```bash
git clone https://github.com/EC-CUBE/ec-cube.git /tmp/ec-cube
cd /tmp/ec-cube
```

### 1.2 ディレクトリ構造

```
ec-cube/
├── src/Eccube/
│   ├── Controller/      # → Resource/App/
│   ├── Entity/          # → Entity/
│   ├── Repository/      # → Query/
│   ├── Service/         # → Service/
│   ├── Form/            # → JsonSchema
│   └── EventListener/   # → Interceptor/
├── tests/               # テスト
└── app/config/          # 設定
```

### 1.3 主要エンティティ

```bash
ls src/Eccube/Entity/
```

| Entity | 説明 | 優先度 |
|--------|------|--------|
| Product | 商品 | 高 |
| ProductClass | 商品規格 | 高 |
| Customer | 顧客 | 高 |
| Order | 注文 | 高 |
| Cart | カート | 高 |
| Category | カテゴリ | 中 |
| Payment | 支払方法 | 中 |
| Delivery | 配送方法 | 中 |
| Member | 管理者 | 中 |

### 1.4 テストの確認

```bash
# テスト数の確認
find tests -name "*Test.php" | wc -l

# 主要なテストを確認
cat tests/Eccube/Tests/Service/CartServiceTest.php
```

## Phase 2: マッピング

### 2.1 Product機能のマッピング

**EC-CUBE:**
```
Controller/ProductController.php
  → index(), detail()
Entity/Product.php
  → id, name, status, description, ...
Repository/ProductRepository.php
  → findBy(), getQueryBuilderBySearchData()
```

**BEAR.Sunday:**
```
Resource/App/Products.php
  → onGet()
Entity/Product.php
  → 同様のプロパティ
Query/ProductQueryInterface.php
  → findById(), findByFilters()
Query/Impl/ProductQuery.php
  → SQL実装
var/sql/product_find_by_id.sql
var/sql/product_find_by_filters.sql
```

### 2.2 Repository → Query変換

**EC-CUBE Repository:**
```php
// src/Eccube/Repository/ProductRepository.php
public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
{
    return $this->findBy($criteria, $orderBy, $limit, $offset);
}

public function getQueryBuilderBySearchData(SearchData $searchData)
{
    $qb = $this->createQueryBuilder('p')
        ->select('p')
        ->leftJoin('p.ProductClasses', 'pc');

    if ($searchData->getName()) {
        $qb->andWhere('p.name LIKE :name')
           ->setParameter('name', '%' . $searchData->getName() . '%');
    }
    // ...
}
```

**BEAR.Sunday Query Interface:**
```php
// src/Query/ProductQueryInterface.php
interface ProductQueryInterface
{
    public function findById(int $id): ?array;

    public function findByFilters(
        ?string $name = null,
        ?int $categoryId = null,
        ?int $statusId = null,
        int $limit = 20,
        int $offset = 0
    ): array;
}
```

**SQL (Ray.MediaQuery):**
```sql
-- var/sql/product_find_by_id.sql
SELECT p.*, ps.name as status_name
FROM product p
JOIN mtb_product_status ps ON p.product_status_id = ps.id
WHERE p.id = :id
```

```sql
-- var/sql/product_find_by_filters.sql
SELECT p.*
FROM product p
WHERE 1=1
/* if name */ AND p.name LIKE /* name */'%keyword%' /* end */
/* if category_id */ AND EXISTS (
    SELECT 1 FROM product_category pc
    WHERE pc.product_id = p.id AND pc.category_id = /* category_id */1
) /* end */
/* if status_id */ AND p.product_status_id = /* status_id */1 /* end */
ORDER BY p.update_date DESC
LIMIT /* limit */20 OFFSET /* offset */0
```

### 2.3 Controller → Resource変換

**EC-CUBE Controller:**
```php
// src/Eccube/Controller/ProductController.php
class ProductController extends AbstractController
{
    /**
     * @Route("/products/list", name="product_list")
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $searchData = new SearchData();
        $form = $this->createForm(SearchProductType::class, $searchData);
        $form->handleRequest($request);

        $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
        $products = $paginator->paginate($qb, $request->query->getInt('page', 1));

        return $this->render('Product/list.twig', [
            'Products' => $products,
        ]);
    }
}
```

**BEAR.Sunday Resource:**
```php
// src/Resource/App/Products.php
class Products extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery
    ) {}

    #[JsonSchema('products.get.json')]
    public function onGet(
        ?string $name = null,
        ?int $category_id = null,
        ?int $status_id = null,
        int $limit = 20,
        int $offset = 0
    ): static {
        $products = $this->productQuery->findByFilters(
            $name, $category_id, $status_id, $limit, $offset
        );
        $total = $this->productQuery->countByFilters(
            $name, $category_id, $status_id
        );

        $this->body = [
            'products' => $products,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];

        return $this;
    }
}
```

## Phase 3: テスト移植

### 3.1 EC-CUBEテストの読解

```php
// tests/Eccube/Tests/Repository/ProductRepositoryTest.php
public function testFindBySearchDataWithName()
{
    $searchData = new SearchData();
    $searchData->setName('パーコ');

    $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
    $products = $qb->getQuery()->getResult();

    $this->assertCount(1, $products);
    $this->assertEquals('パーコレーター', $products[0]->getName());
}
```

**読み取れる仕様:**
- 名前で部分一致検索ができる
- 「パーコ」で検索すると「パーコレーター」がヒットする

### 3.2 BEAR.Sundayテストへの変換

```php
// tests/Resource/App/ProductsTest.php
public function testGetWithNameFilter(): void
{
    // テストデータをセットアップ
    $this->createProduct(['name' => 'パーコレーター']);
    $this->createProduct(['name' => 'コーヒーメーカー']);

    $ro = $this->resource->get('app://self/products', [
        'name' => 'パーコ',
    ]);

    $this->assertSame(200, $ro->code);
    $this->assertCount(1, $ro->body['products']);
    $this->assertSame('パーコレーター', $ro->body['products'][0]['name']);
}
```

## Phase 4: 実装順序

### 4.1 最小構成から開始

```
Week 1: 基盤
├── composer.json (BEAR.Sunday依存)
├── Module/AppModule.php
└── var/sql/schema.sql (基本テーブル)

Week 2: Product機能（完全に動作する状態まで）
├── Entity/Product.php
├── Query/ProductQueryInterface.php
├── Query/Impl/ProductQuery.php (Ray.MediaQuery)
├── Resource/App/Products.php
└── tests/Resource/App/ProductsTest.php

Week 3: Customer機能（完全に動作する状態まで）
├── Entity/Customer.php
├── Query/CustomerQueryInterface.php
├── ...

Week 4: Cart + Order機能
...
```

### 4.2 各週末のチェック

```bash
# 静的解析
./vendor/bin/phpstan analyse

# テスト実行
./vendor/bin/phpunit

# 実際に動かす
./vendor/bin/bear.app get 'app://self/products?name=パーコ'
```

## 失敗例と教訓

### ❌ 今回の失敗

1. **EC-CUBEのコードを読まなかった**
   - Repository の実装を見ずに想像で Query を書いた
   - テストを読まずに仕様を推測した

2. **Ray.MediaQuery を使わなかった**
   - 直接 PDO を使って BEAR.Sunday らしくないコードになった

3. **計画なしに「とりあえず書いた」**
   - Entity を全部作成 → Query を全部作成 → ...
   - 動作確認せずに大量のコードを生成

### ✅ 正しいアプローチ

```bash
# 1. まずソースを読む
cat /tmp/ec-cube/src/Eccube/Entity/Product.php
cat /tmp/ec-cube/src/Eccube/Repository/ProductRepository.php
cat /tmp/ec-cube/tests/Eccube/Tests/Repository/ProductRepositoryTest.php

# 2. 対応する BEAR.Sunday コードを書く
# 3. テストを書いて動作確認
# 4. 次の機能へ
```

## 参考: EC-CUBE主要機能一覧

移植対象の全体像：

| 機能カテゴリ | 含まれる機能 | 行数目安 |
|------------|------------|---------|
| 商品管理 | Product, ProductClass, Category, Tag | 〜2000行 |
| 顧客管理 | Customer, Address, Favorite | 〜1500行 |
| 注文管理 | Order, OrderItem, Shipping, Cart | 〜3000行 |
| 支払・配送 | Payment, Delivery, DeliveryFee | 〜1000行 |
| 管理機能 | Member, Authority, BaseInfo | 〜1000行 |
| その他 | News, Page, Plugin, MailTemplate | 〜1500行 |

**合計: 約10,000行のビジネスロジック**

これを機能単位で段階的に移植する。
