<?php

declare(strict_types=1);

namespace BearEccube\Tests\Schema;

use BearEccube\Query\Fake\FakeCartQuery;
use BearEccube\Query\Fake\FakeCategoryQuery;
use BearEccube\Query\Fake\FakeCustomerQuery;
use BearEccube\Query\Fake\FakeMemberQuery;
use BearEccube\Query\Fake\FakeOrderQuery;
use BearEccube\Query\Fake\FakeProductQuery;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Outside-In の核：FakeQuery のレスポンスが JsonSchema に適合することを保証する。
 *
 * このテストが通っている限り、Resource → 外部から見える契約 は常に正しい。
 * 後で Real 実装に切り替えても、同じ JsonSchema に通れば互換性が保たれる。
 */
class SchemaValidationTest extends TestCase
{
    private string $schemaDir;

    protected function setUp(): void
    {
        $this->schemaDir = dirname(__DIR__, 2) . '/var/schema';
    }

    public static function listProvider(): array
    {
        return [
            'products' => ['products.get.json', static fn() => (new FakeProductQuery())->findList()],
            'customers' => ['customers.get.json', static fn() => (new FakeCustomerQuery())->findList()],
            'orders' => ['orders.get.json', static fn() => (new FakeOrderQuery())->findList()],
            'categories' => ['categories.get.json', static fn() => (new FakeCategoryQuery())->findList()],
            'carts' => ['carts.get.json', static fn() => (new FakeCartQuery())->findList()],
            'members' => ['members.get.json', static fn() => (new FakeMemberQuery())->findList()],
        ];
    }

    public static function itemProvider(): array
    {
        return [
            'product' => ['product.get.json', static fn() => (new FakeProductQuery())->findById(1)],
            'customer' => ['customer.get.json', static fn() => (new FakeCustomerQuery())->findById(1)],
            'order' => ['order.get.json', static fn() => (new FakeOrderQuery())->findById(1)],
            'category' => ['category.get.json', static fn() => (new FakeCategoryQuery())->findById(1)],
            'cart' => ['cart.get.json', static fn() => (new FakeCartQuery())->findById(1)],
            'member' => ['member.get.json', static fn() => (new FakeMemberQuery())->findById(1)],
        ];
    }

    #[DataProvider('listProvider')]
    public function testListResponseMatchesSchema(string $schemaFile, callable $query): void
    {
        $this->assertSchemaMatch($schemaFile, $query());
    }

    #[DataProvider('itemProvider')]
    public function testItemResponseMatchesSchema(string $schemaFile, callable $query): void
    {
        $item = $query();
        $this->assertNotNull($item, "FakeQuery returned null for id=1");
        $this->assertSchemaMatch($schemaFile, $item);
    }

    private function assertSchemaMatch(string $schemaFile, array $response): void
    {
        $schemaPath = realpath($this->schemaDir . '/' . $schemaFile);
        $this->assertNotFalse($schemaPath, "Schema file not found: {$schemaFile}");

        $storage = new SchemaStorage();
        $schema = $storage->getSchema('file://' . $schemaPath);

        $data = json_decode(json_encode($response));

        $validator = new Validator(new Factory($storage));
        $validator->validate($data, $schema);

        $errors = array_map(
            static fn($e) => ($e['property'] ?: '(root)') . ': ' . $e['message'],
            $validator->getErrors()
        );

        $this->assertTrue(
            $validator->isValid(),
            "Schema validation failed for {$schemaFile}:\n" . implode("\n", $errors)
        );
    }
}
