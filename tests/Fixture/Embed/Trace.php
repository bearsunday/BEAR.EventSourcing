<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Embed;

/** What the fixture app ran and rendered, in order. */
final class Trace
{
    /** @var list<string> */
    public static array $events = [];
}
