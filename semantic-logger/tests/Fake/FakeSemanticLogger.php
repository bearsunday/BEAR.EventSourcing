<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Fake;

use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\EventEntry;
use Koriym\SemanticLogger\LogJson;
use Koriym\SemanticLogger\OpenCloseEntry;
use Koriym\SemanticLogger\SemanticLoggerInterface;

final class FakeSemanticLogger implements SemanticLoggerInterface
{
    /** @var array<array{type: string, context: AbstractContext, id: ?string}> */
    public array $logs = [];

    private int $idCounter = 0;

    public function open(AbstractContext $context): string
    {
        $id = 'open-' . ++$this->idCounter;
        $this->logs[] = ['type' => 'open', 'context' => $context, 'id' => null];

        return $id;
    }

    public function close(AbstractContext $context, string $id): void
    {
        $this->logs[] = ['type' => 'close', 'context' => $context, 'id' => $id];
    }

    public function event(AbstractContext $context): void
    {
        $this->logs[] = ['type' => 'event', 'context' => $context, 'id' => null];
    }

    /** @param array<array{rel: string, href: string, title?: string, type?: string}> $links */
    public function flush(array $links = []): LogJson
    {
        $open = new OpenCloseEntry('open-1', 'open', '', []);
        $close = new EventEntry('close-1', 'close', '', []);
        $this->logs = [];

        return new LogJson('', $open, $close, [], $links);
    }
}
