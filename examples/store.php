#!/usr/bin/env php
<?php

declare(strict_types=1);

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Store\InMemoryEventStore;

use function BEAR\EventSourcing\Examples\eventToArray;
use function BEAR\EventSourcing\Examples\exampleEvents;
use function BEAR\EventSourcing\Examples\printJson;

require __DIR__ . '/bootstrap.php';

$store = new InMemoryEventStore();
$store->appendAll(exampleEvents());

$storedEvents = iterator_to_array($store->all(), false);

printJson([
    'stored' => count($storedEvents),
    'events' => array_map(
        static fn (Event $event): array => eventToArray($event),
        $storedEvents,
    ),
]);
