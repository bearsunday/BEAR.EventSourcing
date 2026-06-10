<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use Koriym\SemanticLogger\AbstractContext;

final class ResourceResponseContext extends AbstractContext
{
    public const TYPE = 'resource_response';
    public const SCHEMA_URL = 'https://bearsunday.github.io/schemas/semantic-logger/resource-response.json';

    public function __construct(
        public int $code,
        public mixed $body,
    ) {
    }
}
