<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

/**
 * BEAR.EventSourcing Domain Types for Psalm
 *
 * This file contains Psalm type definitions for the BEAR.EventSourcing package.
 * These types enhance static analysis and provide better IDE support.
 *
 * @psalm-suppress UnusedClass
 *
 * Event Types
 * @psalm-type EventId = non-empty-string
 * @psalm-type Timestamp = non-empty-string
 * @psalm-type EventMethod = 'POST'|'PUT'|'PATCH'|'DELETE'
 * @psalm-type ResourceUri = non-empty-string
 * @psalm-type EventParams = array<string, mixed>
 * @psalm-type EventResult = mixed
 * @psalm-type EventData = array{
 *   id: EventId,
 *   timestamp: Timestamp,
 *   uri: ResourceUri,
 *   method: string,
 *   params: EventParams,
 *   result: EventResult
 * }
 * @psalm-type EventList = list<Event>
 * @psalm-type EventDataList = list<EventData>
 * @psalm-type StoreQuery = array{
 *   uri?: ResourceUri,
 *   since?: Timestamp,
 *   method?: EventMethod
 * }
 * @psalm-type EventHandler = callable(Event): void
 * @psalm-type EventPredicate = callable(Event): bool
 * @psalm-type ReplayState = array<string, mixed>
 * @psalm-type StateReducer = callable(ReplayState, Event): ReplayState
 */
final class Types
{
    /** @codeCoverageIgnore */
    private function __construct()
    {
    }
}
