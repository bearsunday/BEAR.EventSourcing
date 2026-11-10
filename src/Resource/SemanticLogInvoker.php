<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceObject;
use Composer\InstalledVersions;
use DateTimeImmutable;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\Aop\WeavedInterface;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

use function array_keys;
use function debug_backtrace;
use function dirname;
use function get_parent_class;
use function in_array;
use function is_a;
use function realpath;
use function str_starts_with;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

final readonly class SemanticLogInvoker implements InvokerInterface
{
    /** Binding name for the bool that adds the caller to each resource_request; off unless bound true. */
    public const RECORD_CALLER = 'bear_event_sourcing_record_caller';
    private const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.uP';
    private const INCLUDES = ['include', 'include_once', 'require', 'require_once'];

    private RecordedMethods $recordedMethods;
    private ParamsFilterInterface $paramsFilter;
    /** @var list<string>|null null when they could not be found: no caller is recorded */
    private array|null $libraryDirs;

    public function __construct(
        private InvokerInterface $invoker,
        private SemanticLoggerInterface $logger,
        private BodyStoreInterface $bodyStore,
        #[Recorded] RecordedMethods|null $recordedMethods = null,
        #[Filtered] ParamsFilterInterface|null $paramsFilter = null,
        bool $recordCaller = false,
    ) {
        $this->recordedMethods = $recordedMethods ?? new RecordedMethods();
        // Secure by default: an application opts out of redaction by binding its own
        // #[Filtered] ParamsFilterInterface, never opts in to get it.
        $this->paramsFilter = $paramsFilter ?? new SensitiveParamsFilter();
        $this->libraryDirs = $recordCaller ? self::libraryDirs() : null;
    }

    public function invoke(AbstractRequest $request): ResourceObject
    {
        $method = $this->recordedMethods->normalize($request->method->value);
        if ($method === null) {
            return $this->invoker->invoke($request);
        }

        $filtered = $this->filterParams($request->query);
        $openId = $this->safeOpen(fn (): ResourceRequestContext => new ResourceRequestContext(
            uri: self::canonicalUri($request),
            method: $method,
            params: $filtered->params,
            timestamp: (new DateTimeImmutable())->format(self::TIMESTAMP_FORMAT),
            replayable: $filtered->replayable,
            caller: $this->libraryDirs === null ? null : self::caller($this->libraryDirs),
        ));

        $start = hrtime(true);
        try {
            $ro = $this->invoker->invoke($request);
        } catch (Throwable $e) {
            if ($openId !== null) {
                $this->safeClose(
                    static fn (): ResourceResponseContext => ResourceResponseContext::failure(
                        $e,
                        code: self::httpCode($e),
                        durationMs: self::elapsedMs($start),
                    ),
                    $openId,
                );
            }

            throw $e;
        }

        if ($openId !== null) {
            $this->safeClose(
                fn (): ResourceResponseContext => $this->responseContext($request, $ro, self::elapsedMs($start)),
                $openId,
            );
        }

        return $ro;
    }

    /**
     * Open the observation scope without letting a logging failure escape.
     *
     * Mirrors safeClose(): the logger is an interface, and open() throwing here must
     * not break a request it is only meant to watch — the inner invoker still has to
     * run either way. $contextFactory is called inside the guard for the same reason
     * it is in safeClose(): building the request context could in principle fail too.
     * On failure this returns null instead of an openId, so invoke() skips the
     * matching safeClose() at the end of the request rather than closing an id the
     * logger never issued — the logger stays consistent because every close() it
     * receives still pairs with an open() it actually returned an id for.
     *
     * @param callable(): ResourceRequestContext $contextFactory
     */
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
     * Close the open observation without letting a logging failure escape.
     *
     * $contextFactory is called here, not before safeClose: building a
     * ResourceResponseContext can itself throw (an out-of-range or malformed
     * $ro->code from an ill-behaved ResourceObject, for one), and that
     * failure must still close the open scope — losing the close entirely
     * would leave the observation dangling open instead of just losing its
     * detail — so a context-build failure degrades to a bare
     * ResourceResponseContext::failure() recording the build failure itself.
     * The bundled SemanticLogger records an out-of-order close as a
     * diagnostic, but the binding is an interface: an implementation that
     * throws would, on the success path, destroy a completed response and,
     * on the error path, mask the real domain exception. Observation must
     * never break the request, so a close failure is downgraded to a
     * warning.
     *
     * @param callable(): ResourceResponseContext $contextFactory
     */
    private function safeClose(callable $contextFactory, string $openId): void
    {
        try {
            $context = $contextFactory();
        } catch (Throwable $e) {
            self::warn(sprintf('Semantic log context build failed: %s', $e->getMessage()));
            $context = ResourceResponseContext::failure($e);
        }

        try {
            $this->logger->close($context, $openId);
        } catch (Throwable $e) {
            self::warn(sprintf('Semantic log close failed: %s', $e->getMessage()));
        }
    }

    /**
     * Filter params without letting a filter failure escape — and without recording what
     * it failed to filter.
     *
     * The filter is an application-supplied boundary, so it can throw on a param shape it did
     * not expect. Observation must never break the request, but recording the unfiltered
     * params on failure would turn a logging bug into a credential leak: fail closed, record
     * nothing, and mark the request non-replayable so the gap is visible.
     *
     * @param array<string, mixed> $params
     */
    private function filterParams(array $params): FilteredParams
    {
        try {
            return ($this->paramsFilter)($params);
        } catch (Throwable $e) {
            // The class only: a filter's message may quote the very values it was handed.
            self::warn(sprintf('Params filter failed, params withheld: %s', $e::class));

            return new FilteredParams([], replayable: false);
        }
    }

    private static function warn(string $message): void
    {
        try {
            trigger_error($message, E_USER_WARNING);
        } catch (Throwable) {
            // A strict error handler (e.g. Symfony/Laravel) may turn the warning into
            // an exception; swallow it too so observation never breaks the request.
        }
    }

    private function responseContext(
        AbstractRequest $request,
        ResourceObject $ro,
        float $durationMs,
    ): ResourceResponseContext {
        try {
            $bodyRef = ($this->bodyStore)($request, $ro);
        } catch (Throwable $e) {
            // Observation must not break a completed request: keep the real code and record the failure.
            return ResourceResponseContext::failure($e, code: $ro->code, durationMs: $durationMs);
        }

        return ResourceResponseContext::success($ro->code, $bodyRef, durationMs: $durationMs);
    }

    /**
     * The first call site outside every installed Composer package except the root one.
     *
     * A call made from a woven (AOP) method is reported as the declaration of the method it overrides.
     *
     * @param list<string> $libraryDirs
     *
     * @return array{file: string, line: int, function?: string}|null
     */
    private static function caller(array $libraryDirs): array|null
    {
        try {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            foreach ($trace as $i => $frame) {
                $file = $frame['file'] ?? null;
                if ($file === null || self::isUnder($file, $libraryDirs)) {
                    continue;
                }

                $outer = $trace[$i + 1] ?? null;

                return self::callSite($file, $frame['line'] ?? 0, $outer['function'] ?? null, $outer['class'] ?? null);
            }
        } catch (Throwable) {
            // The caller is a debugging aid; losing it must not lose the request entry.
        }

        return null;
    }

    /**
     * Real paths: PHP reports a file loaded through a symlinked package (a Composer path repository)
     * by its real path.
     *
     * @return list<string>|null
     */
    private static function libraryDirs(): array|null
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

        return array_keys($realDirs);
    }

    /**
     * @param class-string|null $class
     *
     * @return array{file: string, line: int, function?: string}
     */
    private static function callSite(string $file, int $line, string|null $function, string|null $class): array
    {
        if ($function === null || in_array($function, self::INCLUDES, true)) {
            return ['file' => $file, 'line' => $line];
        }

        if ($class === null) {
            return ['file' => $file, 'line' => $line, 'function' => $function];
        }

        $parent = get_parent_class($class);
        if ($parent !== false && is_a($class, WeavedInterface::class, true)) {
            $method = new ReflectionMethod($parent, $function);

            return [
                'file' => (string) $method->getFileName(),
                'line' => (int) $method->getStartLine(),
                'function' => $method->class . '::' . $method->name,
            ];
        }

        return ['file' => $file, 'line' => $line, 'function' => $class . '::' . $function];
    }

    /** @param list<string> $dirs */
    private static function isUnder(string $file, array $dirs): bool
    {
        foreach ($dirs as $dir) {
            if (str_starts_with($file, $dir)) {
                return true;
            }
        }

        return false;
    }

    private static function elapsedMs(int|float $start): float
    {
        return round(((float) hrtime(true) - (float) $start) / 1e6, 3);
    }

    /**
     * The canonical uri: scheme, host and path, with the query left where it belongs, in params.
     *
     * Read off the uri object rather than via Request::toUri(), which assembles the query into
     * the string with http_build_query() only for the caller to cut it off again at the `?`.
     * That round trip cost the request itself: http_build_query() raises a ValueError on a value
     * it cannot stringify — an unbacked enum in the query is the shape that does it — and the
     * call sits ahead of every guard in invoke(), so observation broke a request it is supposed
     * only to watch. It also assigns the query onto the resource object's uri on the way past,
     * which is a mutation observation has no business making.
     */
    private static function canonicalUri(AbstractRequest $request): string
    {
        $uri = $request->resourceObject->uri;

        return "{$uri->scheme}://{$uri->host}{$uri->path}";
    }

    private static function httpCode(Throwable $e): int|null
    {
        $code = $e->getCode();

        // Only a status the exception itself declares (400-599) is reported: the invoker runs
        // before the application's error handler maps the exception to a real HTTP status, so a
        // fallback here would assert a code the client was never actually sent.
        return is_int($code) && $code >= 400 && $code < 600 ? $code : null;
    }
}
