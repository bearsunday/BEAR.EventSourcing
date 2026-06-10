<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use InvalidArgumentException;

use function in_array;
use function is_string;
use function sprintf;
use function strtoupper;

/**
 * @psalm-import-type RecordedMethod from Types
 * @psalm-import-type RecordedMethodList from Types
 * @phpstan-import-type RecordedMethod from Types
 * @phpstan-import-type RecordedMethodList from Types
 */
final readonly class RecordedMethods
{
    /** @var RecordedMethodList */
    public const STATE_CHANGING = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** @var RecordedMethodList */
    public const WITH_READS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /** @var RecordedMethodList */
    private array $methods;

    /** @param list<mixed> $methods */
    public function __construct(array $methods = self::STATE_CHANGING)
    {
        $recordedMethods = [];
        foreach ($methods as $method) {
            if (! is_string($method)) {
                throw new InvalidArgumentException('Recorded method must be a string.');
            }

            $recordedMethod = strtoupper($method);
            if (! in_array($recordedMethod, self::WITH_READS, true)) {
                throw new InvalidArgumentException(sprintf('Unsupported recorded method: %s', $method));
            }

            /** @var RecordedMethod $recordedMethod */
            $recordedMethods[] = $recordedMethod;
        }

        $this->methods = $recordedMethods;
    }

    /** @return RecordedMethod|null */
    public function normalize(string $method): string|null
    {
        $recordedMethod = strtoupper($method);
        if (! in_array($recordedMethod, $this->methods, true)) {
            return null;
        }

        /** @var RecordedMethod $recordedMethod */
        return $recordedMethod;
    }

    /** @return RecordedMethodList */
    public function all(): array
    {
        return $this->methods;
    }
}
