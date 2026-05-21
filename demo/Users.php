<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Demo;

use BEAR\Resource\ResourceObject;

/**
 * Minimal demo resource: tracks users in an in-process static store.
 *
 * onPost creates a user and is recorded as a state-change event.
 * onGet is a read-only operation that never produces an event.
 */
final class Users extends ResourceObject
{
    /** @var array<int, array{name: string, age: int}> */
    public static array $store = [];

    public function onPost(string $name, int $age): static
    {
        $id = count(self::$store) + 1;
        self::$store[$id] = ['name' => $name, 'age' => $age];
        $this->code = 201;
        $this->body = ['id' => $id, 'name' => $name, 'age' => $age];

        return $this;
    }

    public function onGet(int $id): static
    {
        $row = self::$store[$id] ?? null;
        $this->code = $row === null ? 404 : 200;
        $this->body = $row;

        return $this;
    }

    public static function reset(): void
    {
        self::$store = [];
    }
}
