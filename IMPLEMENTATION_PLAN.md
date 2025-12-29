# BEAR.EventSourcing 実装計画

## パッケージ構成

```
koriym/semantic-logger (既存・汎用ロギング基盤)
        ↓
bear/semantic-logger (BEAR.Sunday用の観察レイヤー) ← 別リポジトリ
        ↓ EventExtractorInterface
bear/event-sourcing (状態変更の抽出・永続化) ← このリポジトリ
```

### 依存関係

```
bear/event-sourcing
├── requires: bear/semantic-logger
├── requires: bear/resource (ResourceObjectの型のため)
└── requires-dev: phpunit, phpstan, etc.
```

---

## Phase 1: BEAR.EventSourcing コア実装

### 1.1 Event（不変の事実）

```php
namespace BEAR\EventSourcing;

final class Event implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $timestamp,
        public readonly string $uri,
        public readonly string $method,
        public readonly array $params,
        public readonly mixed $result,
    ) {}

    public static function fromContexts(
        OpenContextInterface $open,
        CompleteContextInterface $complete
    ): self;

    public function toArray(): array;
    public function jsonSerialize(): array;
}
```

### 1.2 Events（イベントコレクション）

```php
namespace BEAR\EventSourcing;

final class Events implements EventsInterface
{
    /** @param list<Event> $events */
    public function __construct(
        private array $events = []
    ) {}

    public static function fromLogJson(LogJson $logJson): self;
    public static function fromJson(string $json): self;

    public function toJson(): string;
    public function getIterator(): \Traversable;
    public function count(): int;

    public function play(callable $handler): void;
    public function filter(callable $predicate): self;
}
```

### 1.3 EventStoreInterface

```php
namespace BEAR\EventSourcing;

interface EventStoreInterface
{
    public function append(Event $event): void;
    public function getEvents(string $uri): Events;
    public function getEventsSince(string $timestamp): Events;
    public function getAllEvents(): Events;
}
```

### 1.4 InMemoryEventStore

```php
namespace BEAR\EventSourcing;

final class InMemoryEventStore implements EventStoreInterface
{
    /** @var list<Event> */
    private array $events = [];

    public function append(Event $event): void;
    public function getEvents(string $uri): Events;
    public function getEventsSince(string $timestamp): Events;
    public function getAllEvents(): Events;
}
```

---

## Phase 2: SemanticLogger連携

### 2.1 EventExtractorInterface（bear/semantic-logger側で定義）

```php
namespace BEAR\SemanticLogger;

interface EventExtractorInterface
{
    public function extract(
        OpenContextInterface $open,
        CompleteContextInterface $complete
    ): void;
}
```

### 2.2 EventStoreExtractor（bear/event-sourcing側で実装）

```php
namespace BEAR\EventSourcing;

use BEAR\SemanticLogger\EventExtractorInterface;
use BEAR\SemanticLogger\OpenContextInterface;
use BEAR\SemanticLogger\CompleteContextInterface;

final class EventStoreExtractor implements EventExtractorInterface
{
    public function __construct(
        private EventStoreInterface $eventStore
    ) {}

    public function extract(
        OpenContextInterface $open,
        CompleteContextInterface $complete
    ): void {
        if ($open->method === 'GET') {
            return; // 状態変更なし
        }

        $this->eventStore->append(
            Event::fromContexts($open, $complete)
        );
    }
}
```

---

## Phase 3: Module

### 3.1 EventSourcingModule

```php
namespace BEAR\EventSourcing;

use BEAR\SemanticLogger\EventExtractorInterface;
use Ray\Di\AbstractModule;

class EventSourcingModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(EventStoreInterface::class)
            ->to(InMemoryEventStore::class);

        $this->bind(EventExtractorInterface::class)
            ->to(EventStoreExtractor::class);
    }
}
```

---

## Phase 4: 追加のEventStore実装（将来）

- `FileEventStore` - JSONファイルに永続化
- `PdoEventStore` - SQLデータベース
- `RedisEventStore` - Redis

---

## ディレクトリ構造

```
BEAR.EventSourcing/
├── src/
│   ├── Event.php
│   ├── Events.php
│   ├── EventsInterface.php
│   ├── EventStoreInterface.php
│   ├── EventStoreExtractor.php
│   ├── InMemoryEventStore.php
│   └── Module/
│       └── EventSourcingModule.php
├── tests/
│   ├── EventTest.php
│   ├── EventsTest.php
│   ├── InMemoryEventStoreTest.php
│   └── EventStoreExtractorTest.php
├── composer.json
├── phpunit.xml.dist
├── phpstan.neon
└── README.md
```

---

## 実装順序

1. **Event, Events** - コアのデータ構造
2. **EventStoreInterface, InMemoryEventStore** - 永続化の抽象と実装
3. **EventStoreExtractor** - SemanticLogger連携
4. **EventSourcingModule** - DIバインディング
5. **テスト** - 各コンポーネントのユニットテスト
6. **composer.json, CI設定** - パッケージ化

---

## BEAR.SemanticLogger との連携（別リポジトリ）

PR #338の内容を `bearsunday/BEAR.SemanticLogger` に移行：

```
BEAR.SemanticLogger/
├── src/
│   ├── Context/
│   │   ├── OpenContextInterface.php
│   │   ├── CompleteContextInterface.php
│   │   ├── ErrorContextInterface.php
│   │   └── Resource/
│   │       ├── OpenContext.php
│   │       ├── CompleteContext.php
│   │       └── ErrorContext.php
│   ├── Invoker/
│   │   ├── SemanticInvoker.php
│   │   └── DevSemanticInvoker.php
│   ├── EventExtractorInterface.php  ← ここで定義
│   └── Module/
│       └── SemanticLoggerModule.php
└── ...
```

SemanticInvokerがEventExtractorInterfaceをオプショナルに受け取り、
リアルタイムで抽出を実行。

---

## 未決事項

1. **BEAR.SemanticLoggerのリポジトリ作成** - 誰が、いつ？
2. **PR #338の扱い** - クローズして移行？最小限に縮小？
3. **パッケージのバージョニング** - 1.0.0 から開始？

---

## 備考

### 設計原則

| 原則 | 適用 |
|------|------|
| 単一責任 | Event=事実、EventStore=永続化、Extractor=抽出 |
| 依存性逆転 | InterfaceはSemanticLogger側、実装はEventSourcing側 |
| 開放閉鎖 | EventStoreInterface経由で実装を差し替え可能 |

### 非冪等操作について

- EventSourcingは「事実の記録」に専念
- 冪等性の判断はアプリケーション層の責務
- リプレイ時のフィルタリングは `Events::filter()` で対応
