<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Examples;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\EventsInterface;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\SemanticLogExtractor;
use Koriym\SemanticLogger\LogJson;
use Koriym\SemanticLogger\SemanticLogger;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/ResourceRequestContext.php';
require_once __DIR__ . '/ResourceResponseContext.php';

function exampleSemanticLog(): LogJson
{
    $logger = new SemanticLogger();

    $userCreate = $logger->open(new ResourceRequestContext(
        uri: 'app://self/users',
        method: 'POST',
        query: ['id' => 'koriym', 'name' => 'Akihito'],
        timestamp: '2026-06-10T12:34:56.123456+00:00',
    ));
    $logger->close(new ResourceResponseContext(201, ['id' => 'koriym', 'status' => 'created']), $userCreate);

    $userRead = $logger->open(new ResourceRequestContext(
        uri: 'app://self/users/koriym',
        method: 'GET',
        query: ['id' => 'koriym'],
        timestamp: '2026-06-10T12:35:00.000000+00:00',
    ));
    $logger->close(new ResourceResponseContext(200, ['id' => 'koriym', 'name' => 'Akihito']), $userRead);

    $orderCreate = $logger->open(new ResourceRequestContext(
        uri: 'app://self/orders',
        method: 'POST',
        query: ['order_id' => 'O-1000'],
        timestamp: '2026-06-10T12:36:00.000000+00:00',
    ));
    $inventoryReserve = $logger->open(new ResourceRequestContext(
        uri: 'app://self/inventory/SKU-1',
        method: 'PUT',
        query: ['sku' => 'SKU-1', 'quantity' => 1],
        timestamp: '2026-06-10T12:36:01.000000+00:00',
    ));
    $logger->close(new ResourceResponseContext(200, ['reserved' => true]), $inventoryReserve);
    $logger->close(new ResourceResponseContext(201, ['order_id' => 'O-1000', 'status' => 'accepted']), $orderCreate);

    $failedDelete = $logger->open(new ResourceRequestContext(
        uri: 'app://self/users/koriym',
        method: 'DELETE',
        query: ['id' => 'koriym'],
        timestamp: '2026-06-10T12:37:00.000000+00:00',
    ));
    $logger->close(new ResourceResponseContext(409, ['error' => 'user has active orders']), $failedDelete);

    return $logger->flush();
}

function exampleEvents(bool $includeReads = false): EventsInterface
{
    $methods = $includeReads ? new RecordedMethods(RecordedMethods::WITH_READS) : null;

    return (new SemanticLogExtractor($methods))->extract(exampleSemanticLog());
}

/** @return array{uri: string, method: string, params: array<string, mixed>, result: mixed, timestamp: string} */
function eventToArray(Event $event): array
{
    return [
        'uri' => $event->uri,
        'method' => $event->method,
        'params' => $event->params,
        'result' => $event->result,
        'timestamp' => $event->timestamp->format('Y-m-d\TH:i:s.uP'),
    ];
}

function printJson(mixed $value): void
{
    echo json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
}
