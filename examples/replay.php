#!/usr/bin/env php
<?php

declare(strict_types=1);

use BEAR\EventSourcing\Event;

use function BEAR\EventSourcing\Examples\eventToArray;
use function BEAR\EventSourcing\Examples\exampleEvents;
use function BEAR\EventSourcing\Examples\printJson;

require __DIR__ . '/bootstrap.php';

$events = exampleEvents(includeReads: true);

$forUser = new CallbackFilterIterator(
    $events->getIterator(),
    static fn (Event $event): bool => ($event->params['id'] ?? null) === 'koriym',
);

$replayEvents = [];
foreach ($forUser as $event) {
    $replayEvents[] = eventToArray($event);
}

printJson($replayEvents);
