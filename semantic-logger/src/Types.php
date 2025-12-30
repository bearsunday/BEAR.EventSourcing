<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger;

use BEAR\SemanticLogger\Context\AbstractCompleteContext;
use BEAR\SemanticLogger\Context\AbstractErrorContext;
use BEAR\SemanticLogger\Context\AbstractOpenContext;
use Koriym\SemanticLogger\AbstractContext;

/**
 * BEAR.SemanticLogger Domain Types for Psalm
 *
 * This file contains Psalm type definitions for the BEAR.SemanticLogger package.
 * These types enhance static analysis and provide better IDE support.
 *
 * @psalm-suppress UnusedClass
 *
 * Context Types
 * @psalm-type OpenContext = AbstractOpenContext
 * @psalm-type CompleteContext = AbstractCompleteContext
 * @psalm-type ErrorContext = AbstractErrorContext
 * @psalm-type Context = AbstractContext
 *
 * Resource Types
 * @psalm-type HttpMethod = 'get'|'post'|'put'|'patch'|'delete'|'head'|'options'
 * @psalm-type HttpCode = int<100, 599>
 * @psalm-type Headers = array<string, string>
 * @psalm-type RequestParams = array<string, mixed>
 * @psalm-type ResourceUri = non-empty-string
 *
 * Logging Types
 * @psalm-type LogId = non-empty-string
 * @psalm-type LogType = 'resource.open'|'resource.complete'|'resource.error'
 * @psalm-type LogEntry = array{type: LogType, context: Context, id: ?string}
 * @psalm-type LogEntries = list<LogEntry>
 *
 * Event Extraction Types
 * @psalm-type ExtractedEvent = array{open: OpenContext, complete: CompleteContext}
 * @psalm-type ExtractedEvents = list<ExtractedEvent>
 *
 * Profile Types
 * @psalm-type ProfileType = 'compact'|'verbose'
 * @psalm-type XHProfData = array<string, mixed>
 * @psalm-type TraceFile = non-empty-string
 *
 * Exception Types
 * @psalm-type ExceptionData = array{
 *   class: class-string,
 *   message: string,
 *   code: int,
 *   file: string,
 *   line: int
 * }
 *
 * JSON Serialization Types
 * @psalm-type ResourceOpenJson = array{
 *   method: HttpMethod,
 *   uri: ResourceUri,
 *   params: RequestParams
 * }
 * @psalm-type ResourceCompleteJson = array{
 *   uri: ResourceUri,
 *   code: HttpCode,
 *   headers: Headers,
 *   body: mixed,
 *   view?: string,
 *   profile?: array<string, mixed>
 * }
 * @psalm-type ResourceErrorJson = array{
 *   id: LogId,
 *   exception: ExceptionData,
 *   profile?: array<string, mixed>
 * }
 */
final class Types
{
    /** @codeCoverageIgnore */
    private function __construct()
    {
    }
}
