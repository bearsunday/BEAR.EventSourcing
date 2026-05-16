<?php

declare(strict_types=1);

namespace BearEccube\Tests\Schema;

use BearEccube\Query\Fake\FakeCustomerQuery;
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
            'products' => ['products.get.json', fn() => (new FakeProductQuery())->findList()],
            'customers' => ['customers.get.json', fn() => (new FakeCustomerQuery())->findList()],
            'orders' => ['orders.get.json', fn() => (new FakeOrderQuery())->findList()],
        ];
    }

    #[DataProvider('listProvider')]
    public function testListResponseMatchesSchema(string $schemaFile, callable $query): void
    {
        $response = $query();
        $data = json_decode(json_encode($response));

        $schemaPath = realpath($this->schemaDir . '/' . $schemaFile);
        $this->assertNotFalse($schemaPath, "Schema file not found: {$schemaFile}");

        $storage = new SchemaStorage();
        $schema = $storage->getSchema('file://' . $schemaPath);

        $validator = new Validator(new Factory($storage));
        $validator->validate($data, $schema);

        $errors = array_map(
            fn($e) => ($e['property'] ?: '(root)') . ': ' . $e['message'],
            $validator->getErrors()
        );

        $this->assertTrue(
            $validator->isValid(),
            "Schema validation failed for {$schemaFile}:\n" . implode("\n", $errors)
        );
    }
}
