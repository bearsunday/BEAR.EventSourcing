<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Fake;

use Koriym\SemanticLogger\AbstractContext;

final class FakeContext extends AbstractContext
{
    public function getSchemaUrl(): string
    {
        return 'https://example.com/schema/fake';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['type' => 'fake'];
    }
}
