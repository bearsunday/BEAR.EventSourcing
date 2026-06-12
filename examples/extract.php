#!/usr/bin/env php
<?php

declare(strict_types=1);

use BEAR\EventSourcing\Event;

use function BEAR\EventSourcing\Examples\eventToArray;
use function BEAR\EventSourcing\Examples\exampleEvents;
use function BEAR\EventSourcing\Examples\printJson;

require __DIR__ . '/bootstrap.php';

$events = exampleEvents();

printJson(array_map(
    static fn (Event $event): array => eventToArray($event),
    iterator_to_array($events, false),
));
