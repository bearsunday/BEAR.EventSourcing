<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

/**
 * BEAR.EventSourcing domain types for static analysis.
 *
 * @psalm-type RecordedMethod = 'GET'|'POST'|'PUT'|'PATCH'|'DELETE'
 * @phpstan-type RecordedMethod 'GET'|'POST'|'PUT'|'PATCH'|'DELETE'
 *
 * @psalm-type RecordedMethodList = list<RecordedMethod>
 * @phpstan-type RecordedMethodList list<RecordedMethod>
 *
 * @psalm-type EventParams = array<string, mixed>
 * @phpstan-type EventParams array<string, mixed>
 *
 * @psalm-type EventInput = array{
 *   uri: string,
 *   method: string,
 *   params?: EventParams,
 *   result?: mixed,
 *   timestamp?: string
 * }
 * @phpstan-type EventInput array{
 *   uri: string,
 *   method: string,
 *   params?: EventParams,
 *   result?: mixed,
 *   timestamp?: string
 * }
 *
 * @psalm-type EventOutput = array{
 *   uri: string,
 *   method: string,
 *   params: EventParams,
 *   result: mixed,
 *   timestamp: string
 * }
 * @phpstan-type EventOutput array{
 *   uri: string,
 *   method: string,
 *   params: EventParams,
 *   result: mixed,
 *   timestamp: string
 * }
 *
 * @psalm-type EventList = list<Event>
 * @phpstan-type EventList list<Event>
 *
 * @psalm-type SemanticLog = array<array-key, mixed>
 * @phpstan-type SemanticLog array<array-key, mixed>
 *
 * @psalm-type SemanticEntry = array<array-key, mixed>
 * @phpstan-type SemanticEntry array<array-key, mixed>
 *
 * @psalm-type SemanticContext = array<array-key, mixed>
 * @phpstan-type SemanticContext array<array-key, mixed>
 */
final class Types
{
}
