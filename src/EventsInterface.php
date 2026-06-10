<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Countable;
use IteratorAggregate;

/**
 * Countable iterable stream of extracted events.
 *
 * @extends IteratorAggregate<int, Event>
 */
interface EventsInterface extends Countable, IteratorAggregate
{
}
