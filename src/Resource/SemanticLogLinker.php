<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\LinkerInterface;
use BEAR\Resource\LinkType;
use BEAR\Resource\ResourceObject;
use DateTimeImmutable;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Throwable;

use function array_map;
use function hrtime;
use function round;
use function sprintf;
use function strtoupper;
use function trigger_error;

use const E_USER_WARNING;

/**
 * Records a request with links as one link_request, in which the requests made to follow the links nest
 *
 * The requests nest as a log tree, not as one handler's work: SemanticLogExtractor reads each as a boundary of its own.
 * A request whose method is not recorded opens none, so GET-only links leave the log as they would without links.
 * Like SemanticLogInvoker, it never lets a logging failure break the request it watches.
 */
final readonly class SemanticLogLinker implements LinkerInterface
{
    private const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.uP';

    private RecordedMethods $recordedMethods;

    public function __construct(
        private LinkerInterface $linker,
        private SemanticLoggerInterface $logger,
        #[Recorded] RecordedMethods $recordedMethods,
    ) {
        // Not promoted: #[Recorded] targets a parameter only
        $this->recordedMethods = $recordedMethods;
    }

    public function invoke(AbstractRequest $request): ResourceObject
    {
        if ($this->recordedMethods->normalize($request->method->value) === null) {
            return $this->linker->invoke($request);
        }

        $openId = $this->safeOpen(static fn (): LinkRequestContext => new LinkRequestContext(
            uri: self::canonicalUri($request->resourceObject),
            method: strtoupper($request->method->value),
            links: array_map(static fn (LinkType $link): string => $link->type . ':' . $link->key, $request->links),
            timestamp: (new DateTimeImmutable())->format(self::TIMESTAMP_FORMAT),
        ));

        $start = hrtime(true);
        try {
            $ro = $this->linker->invoke($request);
        } catch (Throwable $e) {
            $durationMs = self::elapsedMs($start);
            $this->safeClose(
                static fn (): LinkResponseContext => LinkResponseContext::failure($e, $durationMs),
                $openId,
                $durationMs,
            );

            throw $e;
        }

        $durationMs = self::elapsedMs($start);
        $this->safeClose(
            static fn (): LinkResponseContext => LinkResponseContext::ended($ro->code, $durationMs),
            $openId,
            $durationMs,
        );

        return $ro;
    }

    /** @param callable(): LinkRequestContext $contextFactory */
    private function safeOpen(callable $contextFactory): string|null
    {
        try {
            return $this->logger->open($contextFactory());
        } catch (Throwable $e) {
            self::warn(sprintf('Semantic log open failed: %s', $e->getMessage()));

            return null;
        }
    }

    /**
     * Closes the link_request whatever happens: left open, it would take every later entry in as its child.
     *
     * @param callable(): LinkResponseContext $contextFactory
     */
    private function safeClose(callable $contextFactory, string|null $openId, float $durationMs): void
    {
        if ($openId === null) {
            return;
        }

        try {
            $context = $contextFactory();
        } catch (Throwable $e) {
            self::warn(sprintf('Semantic log context build failed: %s', $e->getMessage()));
            $context = LinkResponseContext::failure($e, $durationMs);
        }

        try {
            $this->logger->close($context, $openId);
        } catch (Throwable $e) {
            self::warn(sprintf('Semantic log close failed: %s', $e->getMessage()));
        }
    }

    private static function warn(string $message): void
    {
        try {
            trigger_error($message, E_USER_WARNING);
        } catch (Throwable) {
            // A strict error handler may turn the warning into an exception; observation never breaks the request.
        }
    }

    private static function canonicalUri(ResourceObject $ro): string
    {
        $uri = $ro->uri;

        return "{$uri->scheme}://{$uri->host}{$uri->path}";
    }

    private static function elapsedMs(int|float $start): float
    {
        return round(((float) hrtime(true) - (float) $start) / 1e6, 3);
    }
}
