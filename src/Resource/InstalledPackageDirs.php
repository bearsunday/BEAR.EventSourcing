<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use Composer\InstalledVersions;
use ReflectionClass;
use Throwable;

use function array_keys;
use function dirname;
use function realpath;
use function str_starts_with;

/**
 * Where library code lives: the installed Composer packages other than the root one, and this package.
 *
 * @internal
 */
final readonly class InstalledPackageDirs
{
    /** @param list<string> $dirs */
    private function __construct(
        private array $dirs,
    ) {
    }

    /**
     * Null when Composer's runtime API cannot tell. Paths are real paths: PHP reports a file loaded
     * through a symlinked package (a Composer path repository) by its real path.
     */
    public static function find(): self|null
    {
        try {
            $vendorDir = dirname((string) (new ReflectionClass(InstalledVersions::class))->getFileName(), 2);
            $dirs = [dirname(__DIR__), $vendorDir];
            $root = InstalledVersions::getRootPackage()['name'];
            foreach (InstalledVersions::getInstalledPackages() as $package) {
                $path = $package === $root ? null : InstalledVersions::getInstallPath($package);
                if ($path !== null) {
                    $dirs[] = $path;
                }
            }
        } catch (Throwable) {
            return null;
        }

        $realDirs = [];
        foreach ($dirs as $dir) {
            $real = realpath($dir);
            if ($real !== false) {
                $realDirs[$real . '/'] = true;
            }
        }

        return new self(array_keys($realDirs));
    }

    public function contains(string $file): bool
    {
        foreach ($this->dirs as $dir) {
            if (str_starts_with($file, $dir)) {
                return true;
            }
        }

        return false;
    }
}
