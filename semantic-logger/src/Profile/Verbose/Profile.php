<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use JsonSerializable;

/**
 * Aggregated profiling data from multiple profilers.
 *
 * Captures data from:
 * - XHProf: Function-level profiling
 * - Xdebug: Trace and coverage data
 * - PHP: Native backtrace information
 */
final class Profile implements JsonSerializable
{
    public function __construct(
        public readonly ?array $xhprof = null,
        public readonly ?string $xdebugTraceFile = null,
        public readonly ?array $backtrace = null,
        public readonly ?float $elapsedTime = null,
        public readonly ?int $memoryUsage = null,
        public readonly ?int $peakMemoryUsage = null,
    ) {
    }

    /**
     * Create profile by stopping XHProf and collecting data.
     */
    public static function collect(?float $startTime = null): self
    {
        $xhprof = null;
        $xdebugTraceFile = null;
        $backtrace = null;

        // Collect XHProf data if available
        if (function_exists('xhprof_disable')) {
            /** @var array|null $xhprof */
            $xhprof = xhprof_disable();
        }

        // Get Xdebug trace file if available
        if (function_exists('xdebug_get_tracefile_name')) {
            $xdebugTraceFile = xdebug_get_tracefile_name() ?: null;
        }

        // Capture backtrace
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        return new self(
            xhprof: $xhprof,
            xdebugTraceFile: $xdebugTraceFile,
            backtrace: $backtrace,
            elapsedTime: $startTime !== null ? microtime(true) - $startTime : null,
            memoryUsage: memory_get_usage(true),
            peakMemoryUsage: memory_get_peak_usage(true),
        );
    }

    /**
     * Create an empty profile (for error cases without profiling).
     */
    public static function empty(): self
    {
        return new self();
    }

    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->xhprof !== null) {
            $data['xhprof'] = $this->xhprof;
        }

        if ($this->xdebugTraceFile !== null) {
            $data['xdebug_trace_file'] = $this->xdebugTraceFile;
        }

        if ($this->backtrace !== null) {
            $data['backtrace'] = $this->backtrace;
        }

        if ($this->elapsedTime !== null) {
            $data['elapsed_time_ms'] = round($this->elapsedTime * 1000, 3);
        }

        if ($this->memoryUsage !== null) {
            $data['memory_usage'] = $this->memoryUsage;
        }

        if ($this->peakMemoryUsage !== null) {
            $data['peak_memory_usage'] = $this->peakMemoryUsage;
        }

        return $data;
    }
}
