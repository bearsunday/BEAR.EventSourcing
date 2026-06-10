<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

/**
 * BEAR.EventSourcing domain types for Psalm static analysis.
 *
 * @psalm-type RecordedMethod = 'GET'|'POST'|'PUT'|'PATCH'|'DELETE'
 * @psalm-type RecordedMethodList = list<RecordedMethod>
 * @psalm-type EventParams = array<string, mixed>
 * @psalm-type EventList = list<Event>
 * @psalm-type SemanticEntry = array<array-key, mixed>
 * @psalm-type SemanticContext = array<array-key, mixed>
 */
final class Types
{
}
