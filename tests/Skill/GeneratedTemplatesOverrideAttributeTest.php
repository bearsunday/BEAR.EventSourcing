<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Skill;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function preg_match;
use function preg_quote;

/**
 * Proves the three files harness/setup.php writes into a consuming application (DevModule,
 * ObserveLoggerProvider, DevPoolProvider) carry #[Override] on every method that overrides a
 * parent class method or implements an interface method — the attribute a strict downstream
 * Psalm config (`ensureOverrideAttribute="true"`) requires, even though this repository's own
 * psalm.xml disables that check for itself. A missing #[Override] here is invisible to
 * `composer sa`: the generated files never live in this repository's own src/, so nothing short
 * of reading the emitted template catches a regression.
 *
 * Asserted directly against the template source rather than by shelling out to Psalm against a
 * rendered copy: a prior version of this test ran Psalm in a scratch directory and passed even
 * with #[Override] removed from the source templates (a config/autoload resolution gap that
 * made the check vacuous — verified by reverting the fix and observing the test still pass), so
 * a plain regex against these small, known-shape files is the check this repository can trust.
 */
final class GeneratedTemplatesOverrideAttributeTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function templates(): array
    {
        return [
            'DevModule.php::configure' => ['DevModule.php', 'configure'],
            'ObserveLoggerProvider.php::get' => ['ObserveLoggerProvider.php', 'get'],
            'DevPoolProvider.php::get' => ['DevPoolProvider.php', 'get'],
        ];
    }

    #[DataProvider('templates')]
    public function testEveryOverridingMethodCarriesTheOverrideAttribute(string $file, string $method): void
    {
        $path = dirname(__DIR__, 2) . '/skills/bear-observe/templates/' . $file;
        $source = (string) file_get_contents($path);

        $this->assertStringContainsString(
            'use Override;',
            $source,
            "{$file} must import Override for the #[Override] attribute to resolve.",
        );

        $pattern = '/#\[Override\]\s+(?:public|protected|private)\s+function\s+'
            . preg_quote($method, '/') . '\s*\(/';
        $this->assertSame(
            1,
            preg_match($pattern, $source),
            "{$file}::{$method}() must be immediately preceded by #[Override] so a strict "
            . 'downstream Psalm config (ensureOverrideAttribute=true) accepts the generated file.',
        );
    }
}
