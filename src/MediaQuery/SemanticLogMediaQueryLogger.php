<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\MediaQuery;

use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\MediaQuery\MediaQueryLoggerInterface;
use Stringable;
use Throwable;

use function base64_encode;
use function hrtime;
use function implode;
use function is_string;
use function preg_match;
use function round;
use function sprintf;
use function trigger_error;

use const E_USER_WARNING;
use const PHP_EOL;

/**
 * Ray.MediaQuery logger seam -> one `media_query` leaf event per executed
 * query. SqlQuery calls start() before execution and log() after on this
 * same instance, so wall time is measured here; a failed query throws
 * before log() and is never recorded. getCount() and getPages() bypass the
 * seam entirely and stay unobserved.
 */
final class SemanticLogMediaQueryLogger implements MediaQueryLoggerInterface, Stringable
{
    private int $start = 0;

    /** @var list<string> */
    private array $lines = [];

    public function __construct(
        private readonly SemanticLoggerInterface $logger,
    ) {
    }

    public function start(): void
    {
        $this->start = hrtime(true);
    }

    /** @param array<string, mixed> $values */
    public function log(string $queryId, array $values): void
    {
        $durationMs = $this->start === 0 ? 0.0 : round(((float) hrtime(true) - (float) $this->start) / 1e6, 3);
        $this->lines[] = sprintf('query: %s', $queryId);
        // Observation must never break a completed query.
        try {
            $this->logger->event(new MediaQueryContext($queryId, self::utf8Safe($values), $durationMs));
        } catch (Throwable $e) {
            try {
                trigger_error(sprintf('Media query observation failed: %s', $e->getMessage()), E_USER_WARNING);
            } catch (Throwable) {
                // A strict error handler may turn the warning into an exception; swallow it too.
            }
        }
    }

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->lines);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function utf8Safe(array $values): array
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($values as &$value) {
            if (is_string($value) && preg_match('//u', $value) !== 1) {
                $value = base64_encode($value); // binary-safe: the context must stay JSON-encodable
            }
        }

        return $values;
    }
}
