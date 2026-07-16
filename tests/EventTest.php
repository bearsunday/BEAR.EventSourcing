<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testConstructNormalizesMethod(): void
    {
        $timestamp = new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00');
        $event = new Event(
            uri: 'app://self/users',
            method: 'post',
            timestamp: $timestamp,
            params: ['name' => 'Ada'],
            result: ['id' => 1],
        );

        $this->assertSame('app://self/users', $event->uri);
        $this->assertSame('POST', $event->method);
        $this->assertSame($timestamp, $event->timestamp);
        $this->assertSame(['name' => 'Ada'], $event->params);
        $this->assertSame(['id' => 1], $event->result);
    }

    public function testIdIsDeterministicForTheSameObservedFact(): void
    {
        $timestamp = new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00');

        $event = new Event('app://self/users', 'POST', $timestamp, ['name' => 'Ada'], ['id' => 1]);
        $same = new Event('app://self/users', 'post', $timestamp, ['name' => 'Ada']);

        // Same operation at the same instant is the same event; result and method
        // case do not change identity.
        $this->assertSame($event->id, $same->id);
    }

    public function testIdIgnoresTimezoneRepresentationAndParamOrder(): void
    {
        $utc = new Event(
            'app://self/users',
            'POST',
            new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'),
            ['name' => 'Ada', 'id' => 1],
        );
        $jst = new Event(
            'app://self/users',
            'POST',
            new DateTimeImmutable('2026-06-10T21:34:56.123456+09:00'),
            ['id' => 1, 'name' => 'Ada'],
        );

        $this->assertSame($utc->id, $jst->id);
    }

    public function testIdDistinguishesDifferentFacts(): void
    {
        $timestamp = new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00');
        $event = new Event('app://self/users', 'POST', $timestamp, ['name' => 'Ada']);

        $differentUri = new Event('app://self/admins', 'POST', $timestamp, ['name' => 'Ada']);
        $differentParams = new Event('app://self/users', 'POST', $timestamp, ['name' => 'Grace']);
        $differentInstant = new Event(
            'app://self/users',
            'POST',
            new DateTimeImmutable('2026-06-10T12:34:56.123457+00:00'),
            ['name' => 'Ada'],
        );

        $this->assertNotSame($event->id, $differentUri->id);
        $this->assertNotSame($event->id, $differentParams->id);
        $this->assertNotSame($event->id, $differentInstant->id);
    }

    public function testExplicitIdIsKept(): void
    {
        $event = new Event(
            uri: 'app://self/users',
            method: 'POST',
            timestamp: new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'),
            id: 'restored-id',
        );

        $this->assertSame('restored-id', $event->id);
    }
}
