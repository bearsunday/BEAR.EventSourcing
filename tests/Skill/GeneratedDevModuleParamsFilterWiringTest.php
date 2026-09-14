<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Skill;

use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function preg_match;

/**
 * Proves the DevModule harness/setup.php writes into a consuming application maps the
 * `paramsFilter` constructor parameter to the #[Filtered] qualifier in its toConstructor call.
 *
 * Ray.Di's toConstructor name map does not fall back to the attribute on the parameter: with
 * the entry missing, an application that binds its own `#[Filtered] ParamsFilterInterface`
 * silently gets the library default instead — verified against a real injector before this
 * test was written. As with the #[Override] test, the generated file never lives in this
 * repository's own src/, so only the template source can be checked.
 */
final class GeneratedDevModuleParamsFilterWiringTest extends TestCase
{
    public function testDevModuleMapsParamsFilterToTheFilteredQualifier(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/skills/bear-observe/templates/DevModule.php');

        $this->assertStringContainsString(
            'use BEAR\EventSourcing\Filtered;',
            $source,
            'DevModule.php must import Filtered for the toConstructor map entry to resolve.',
        );

        $pattern = '/->toConstructor\(SemanticLogInvoker::class,\s*\[.*?'
            . '\'paramsFilter\'\s*=>\s*Filtered::class.*?\]\)/s';
        $this->assertSame(
            1,
            preg_match($pattern, $source),
            "DevModule.php must map 'paramsFilter' => Filtered::class in its toConstructor call, "
            . 'or an application-bound #[Filtered] ParamsFilterInterface never reaches SemanticLogInvoker.',
        );
    }
}
