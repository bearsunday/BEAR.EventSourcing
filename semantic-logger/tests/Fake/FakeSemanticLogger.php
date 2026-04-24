<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Fake;

use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\EventEntry;
use Koriym\SemanticLogger\LogJson;
use Koriym\SemanticLogger\OpenCloseEntry;
use Koriym\SemanticLogger\SemanticLoggerInterface;

class FakeSemanticLogger implements SemanticLoggerInterface
{
    /** @var array<array{type: string, context: AbstractContext, id: ?string}> */
    public array $logs = [];

    /** @var array<array{type: string, context: AbstractContext, id: ?string}> */
    public array $allLogs = [];

    private int $idCounter = 0;

    public function open(AbstractContext $context): string
    {
        $id = 'open-' . ++$this->idCounter;
        $entry = ['type' => 'open', 'context' => $context, 'id' => null];
        $this->logs[] = $entry;
        $this->allLogs[] = $entry;

        return $id;
    }

    public function close(AbstractContext $context, string $id): void
    {
        $entry = ['type' => 'close', 'context' => $context, 'id' => $id];
        $this->logs[] = $entry;
        $this->allLogs[] = $entry;
    }

    public function event(AbstractContext $context): void
    {
        $entry = ['type' => 'event', 'context' => $context, 'id' => null];
        $this->logs[] = $entry;
        $this->allLogs[] = $entry;
    }

    /** @param array<array{rel: string, href: string, title?: string, type?: string}> $links */
    public function flush(array $links = []): LogJson
    {
        $open = new OpenCloseEntry('open-1', 'open', '', []);
        $close = new EventEntry('close-1', 'close', '', [], 'open-1');
        $this->logs = [];

        return new LogJson('', [$open], [$close], [], $links);
    }
}
