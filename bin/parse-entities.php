#!/usr/bin/env php
<?php
/**
 * EC-CUBE Entity Parser
 *
 * EC-CUBE の Doctrine Entity から構造を抽出し、FakeJSON と schema.json を生成する。
 * Outside-In アプローチの起点ツール。
 *
 * Usage:
 *   php bin/parse-entities.php <eccube-src-dir> [<output-dir>]
 *
 * Example:
 *   git clone https://github.com/EC-CUBE/ec-cube /tmp/ec-cube
 *   php bin/parse-entities.php /tmp/ec-cube/src/Eccube/Entity var/fake
 *
 * 重要：このスクリプトは決定論的でなければならない（同じ入力 → 同じ出力）。
 * 乱数を使うとテストがフレイキーになる。
 */

declare(strict_types=1);

$entityDir = $argv[1] ?? '/tmp/ec-cube/src/Eccube/Entity';
$outputDir = $argv[2] ?? __DIR__ . '/../var/fake';

if (!is_dir($entityDir)) {
    fwrite(STDERR, "Error: entity dir not found: {$entityDir}\n");
    fwrite(STDERR, "Hint: git clone https://github.com/EC-CUBE/ec-cube /tmp/ec-cube\n");
    exit(1);
}

/**
 * 1つの Entity ファイルを解析し、プロパティとリレーションを抽出する。
 */
function parseEntity(string $file): array
{
    $content = file_get_contents($file);
    $className = basename($file, '.php');

    $properties = [];
    $relations = [];

    preg_match_all('/@ORM\\\\Column\s*\(([^)]+)\)/s', $content, $columnMatches, PREG_SET_ORDER);

    foreach ($columnMatches as $match) {
        $attrs = $match[1];
        $name = preg_match('/name\s*=\s*"([^"]+)"/', $attrs, $m) ? $m[1] : null;
        $type = preg_match('/type\s*=\s*"([^"]+)"/', $attrs, $m) ? $m[1] : null;
        $nullable = (bool) preg_match('/nullable\s*=\s*true/', $attrs);

        if ($name !== null && $type !== null) {
            $properties[] = ['name' => $name, 'type' => $type, 'nullable' => $nullable];
        }
    }

    if (preg_match('/@ORM\\\\Id/', $content)) {
        $hasId = false;
        foreach ($properties as $p) {
            if ($p['name'] === 'id') {
                $hasId = true;
                break;
            }
        }
        if (!$hasId) {
            array_unshift($properties, ['name' => 'id', 'type' => 'integer', 'nullable' => false]);
        }
    }

    preg_match_all(
        '/@ORM\\\\(ManyToOne|OneToMany|ManyToMany|OneToOne)\s*\(([^)]+)\)/s',
        $content,
        $relMatches,
        PREG_SET_ORDER
    );

    foreach ($relMatches as $match) {
        $relType = $match[1];
        $attrs = $match[2];

        if (preg_match('/targetEntity\s*=\s*"([^"]+)"/', $attrs, $m)) {
            $shortName = basename(str_replace('\\', '/', $m[1]));
            $relations[] = [
                'type' => $relType,
                'target' => $shortName,
                'propertyName' => lcfirst($shortName),
            ];
        }
    }

    return ['class' => $className, 'properties' => $properties, 'relations' => $relations];
}

/**
 * フィールド名と Doctrine 型から代表的なサンプル値を生成する。
 * 決定論的: 同じ入力に対し常に同じ値を返す。
 */
function generateFakeValue(string $ormType, string $name, bool $nullable): mixed
{
    if ($nullable) {
        return null;
    }

    if ($name === 'name' || str_ends_with($name, '_name')) {
        return 'サンプル商品';
    }
    if (str_contains($name, 'email')) {
        return 'sample@example.com';
    }
    if (str_contains($name, 'phone') || str_contains($name, 'tel')) {
        return '03-1234-5678';
    }
    if (str_contains($name, 'postal') || str_contains($name, 'zip')) {
        return '100-0001';
    }
    if (str_contains($name, 'price')) {
        return 1000;
    }
    if (str_contains($name, 'quantity') || str_contains($name, 'stock')) {
        return 10;
    }

    return match ($ormType) {
        'integer', 'smallint', 'bigint' => 1,
        'decimal', 'float' => 1000.00,
        'boolean' => false,
        'datetime', 'datetimetz' => '2024-01-01T00:00:00+09:00',
        'date' => '2024-01-01',
        'text' => "サンプル{$name}の説明文です。",
        default => "サンプル{$name}",
    };
}

function toSnakeCase(string $str): string
{
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $str));
}

$mainEntities = [
    'Product' => ['name' => 'サンプル商品', 'code' => 'SAMPLE-001'],
    'Customer' => ['name01' => '山田', 'name02' => '太郎', 'email' => 'customer@example.com'],
    'Order' => ['name01' => '田中', 'name02' => '花子', 'order_no' => 'ORDER-001'],
    'Cart' => [],
    'Category' => ['name' => 'カテゴリ1'],
    'Member' => ['name' => '管理者', 'login_id' => 'admin'],
];

echo "=== EC-CUBE Entity Parser ===\n";
echo "Source: {$entityDir}\n";
echo "Output: {$outputDir}\n\n";

foreach ($mainEntities as $entityName => $defaults) {
    $file = "{$entityDir}/{$entityName}.php";
    if (!file_exists($file)) {
        echo "Skip: {$entityName} not found\n";
        continue;
    }

    echo "Parsing: {$entityName}\n";
    $parsed = parseEntity($file);

    $fake = ['id' => 1];
    foreach ($defaults as $k => $v) {
        $fake[$k] = $v;
    }

    foreach ($parsed['properties'] as $prop) {
        if ($prop['name'] === 'id' || isset($fake[$prop['name']])) {
            continue;
        }
        $fake[$prop['name']] = generateFakeValue($prop['type'], $prop['name'], $prop['nullable']);
    }

    foreach ($parsed['relations'] as $rel) {
        $propName = toSnakeCase($rel['propertyName']);
        if (isset($fake[$propName])) {
            continue;
        }

        if ($rel['type'] === 'ManyToOne' || $rel['type'] === 'OneToOne') {
            $fake[$propName] = ['id' => 1, 'name' => "サンプル{$rel['target']}"];
        } else {
            $plural = str_ends_with($propName, 's') ? $propName : $propName . 's';
            $fake[$plural] = [['id' => 1, 'name' => "サンプル{$rel['target']}"]];
        }
    }

    $outDir = "{$outputDir}/" . strtolower($entityName);
    if (!is_dir($outDir)) {
        mkdir($outDir, 0755, true);
    }

    $json = static fn(mixed $data): string => json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );

    file_put_contents("{$outDir}/item.json", $json($fake));
    file_put_contents("{$outDir}/list.json", $json([
        'items' => [$fake],
        'total' => 1,
        'limit' => 20,
        'offset' => 0,
    ]));
    file_put_contents("{$outDir}/schema.json", $json($parsed));

    echo "  -> {$outDir}/\n";
}

echo "\nDone.\n";
