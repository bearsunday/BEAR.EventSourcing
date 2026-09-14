<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\MediaQuery;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Resource\ParamsFilterInterface;
use BEAR\EventSourcing\Resource\SensitiveParamsFilter;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\MediaQuery\MediaQueryLoggerInterface;
use Stringable;
use Throwable;

use function base64_encode;
use function hrtime;
use function implode;
use function is_array;
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
 *
 * Bind values pass through the same #[Filtered] ParamsFilterInterface as
 * request params (SensitiveParamsFilter when unbound): a query that binds a
 * TOTP secret or a reset token records `[FILTERED]`, not the value. Only the
 * filtered params are kept; a `media_query` entry is a leaf, not an event,
 * so its replayability verdict has nowhere to go and is dropped.
 */
final class SemanticLogMediaQueryLogger implements MediaQueryLoggerInterface, Stringable
{
    private int $start = 0;

    /** @var list<string> */
    private array $lines = [];

    private readonly ParamsFilterInterface $paramsFilter;

    public function __construct(
        private readonly SemanticLoggerInterface $logger,
        #[Filtered] ParamsFilterInterface|null $paramsFilter = null,
    ) {
        // Secure by default, as in SemanticLogInvoker: unbound means the default filter, not none.
        $this->paramsFilter = $paramsFilter ?? new SensitiveParamsFilter();
    }

    public function start(): void
    {
        $this->start = hrtime(true);
    }

    /** @param array<string, mixed> $values */
    public function log(string $queryId, array $values): void
    {
        $durationMs = $this->start === 0 ? 0.0 : round(((float) hrtime(true) - (float) $this->start) / 1e6, 3);
        $this->start = 0; // an unbracketed log() must not measure from a previous query
        $this->lines[] = sprintf('query: %s', $queryId);
        // Observation must never break a completed query.
        try {
            /** @var array<string, mixed> $safeValues Top-level keys are the query's named parameters. */
            $safeValues = self::utf8Safe($values);
            $this->logger->event(new MediaQueryContext($queryId, $this->filterParams($safeValues), $durationMs));
        } catch (Throwable $e) {
            self::warn(sprintf('Media query observation failed: %s', $e->getMessage()));
        }
    }

    /**
     * Fail closed, as SemanticLogInvoker does: a filter that throws records nothing it failed
     * on, never the unfiltered values.
     *
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function filterParams(array $values): array
    {
        try {
            return ($this->paramsFilter)($values)->params;
        } catch (Throwable $e) {
            // The class only: a filter's message may quote the very values it was handed.
            self::warn(sprintf('Media query params filter failed, bind values withheld: %s', $e::class));

            return [];
        }
    }

    private static function warn(string $message): void
    {
        try {
            trigger_error($message, E_USER_WARNING);
        } catch (Throwable) {
            // A strict error handler may turn the warning into an exception; swallow it too.
        }
    }

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->lines);
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private static function utf8Safe(array $values): array
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = self::utf8Safe($value);
                continue;
            }

            if (is_string($value) && preg_match('//u', $value) !== 1) {
                $value = base64_encode($value); // binary-safe: the context must stay JSON-encodable
            }
        }

        return $values;
    }
}
