# BEAR.Eccube

EC-CUBE ported to BEAR.Sunday with Event Sourcing.

## Overview

This project is a complete port of [EC-CUBE](https://github.com/EC-CUBE/ec-cube) e-commerce platform to the BEAR.Sunday framework, utilizing Event Sourcing for state management.

## Features

- **Resource-Oriented Architecture**: All entities exposed as RESTful resources
- **Event Sourcing**: All state changes are recorded as immutable events
- **Dependency Injection**: Clean separation of concerns using Ray.Di
- **AOP Integration**: Cross-cutting concerns handled via Ray.Aop

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      BEAR.Sunday                             │
├─────────────────────────────────────────────────────────────┤
│  Resource Layer                                              │
│  ├── /products      - 商品管理                               │
│  ├── /customers     - 会員管理                               │
│  ├── /orders        - 注文管理                               │
│  ├── /cart          - カート管理                             │
│  └── /categories    - カテゴリ管理                           │
├─────────────────────────────────────────────────────────────┤
│  Query Layer (Ray.Query)                                     │
│  ├── ProductQuery, CustomerQuery, OrderQuery, etc.           │
│  └── SQL files in var/sql/                                   │
├─────────────────────────────────────────────────────────────┤
│  Entity Layer                                                │
│  ├── Product, ProductClass, ProductImage, ProductCategory    │
│  ├── Customer, CustomerAddress                               │
│  ├── Order, OrderItem, Shipping                              │
│  ├── Cart, CartItem                                          │
│  ├── Category, Payment, Delivery                             │
│  └── Master entities (Pref, Sex, Status, etc.)               │
├─────────────────────────────────────────────────────────────┤
│  Event Sourcing Layer                                        │
│  ├── Event, Events, EventStore                               │
│  └── EventSourcingInterceptor                                │
└─────────────────────────────────────────────────────────────┘
```

## Installation

```bash
composer install
```

## Database Setup

```bash
mysql -u root -p < var/sql/schema.sql
```

Or for PostgreSQL, adjust the schema accordingly.

## Configuration

Set environment variables:

```bash
export DB_DRIVER=mysql    # or pgsql
export DB_HOST=localhost
export DB_PORT=3306
export DB_NAME=eccube
export DB_USER=root
export DB_PASSWORD=
export DB_CHARSET=utf8mb4
```

## API Endpoints

### Products

| Method | URI | Description |
|--------|-----|-------------|
| GET | /products | 商品一覧取得 |
| POST | /products | 商品登録 |
| GET | /products/{id} | 商品詳細取得 |
| PUT | /products/{id} | 商品更新 |
| DELETE | /products/{id} | 商品削除 |

### Customers

| Method | URI | Description |
|--------|-----|-------------|
| GET | /customers | 会員一覧取得 |
| POST | /customers | 会員登録 |
| GET | /customers/{id} | 会員詳細取得 |
| PUT | /customers/{id} | 会員更新 |
| DELETE | /customers/{id} | 会員削除 |

### Orders

| Method | URI | Description |
|--------|-----|-------------|
| GET | /orders | 注文一覧取得 |
| GET | /orders/{id} | 注文詳細取得 |
| PUT | /orders/{id} | 注文ステータス更新 |
| DELETE | /orders/{id} | 注文キャンセル |

### Cart

| Method | URI | Description |
|--------|-----|-------------|
| GET | /cart | カート取得 |
| POST | /cart | カート作成 |
| DELETE | /cart | カートクリア |
| GET | /cart/items | カート内商品取得 |
| POST | /cart/items | カートに商品追加 |
| PUT | /cart/items | 数量変更 |
| DELETE | /cart/items/{id} | 商品削除 |
| GET | /cart/checkout | 注文プレビュー |
| POST | /cart/checkout | 注文確定 |

### Categories

| Method | URI | Description |
|--------|-----|-------------|
| GET | /categories | カテゴリ一覧取得 |
| POST | /categories | カテゴリ登録 |
| GET | /categories/{id} | カテゴリ詳細取得 |
| PUT | /categories/{id} | カテゴリ更新 |
| DELETE | /categories/{id} | カテゴリ削除 |

## Event Sourcing

All POST, PUT, DELETE operations are automatically recorded as events:

```php
// Events are stored with:
{
    "id": "uuid",
    "timestamp": "2025-01-01 12:00:00.000000",
    "uri": "/products/1",
    "method": "PUT",
    "params": {"name": "Updated Product"},
    "result": {"id": 1, "name": "Updated Product", ...}
}
```

### Query Events

```php
// Get all events
$events = $eventStore->getEvents();

// Get events since timestamp
$events = $eventStore->getEventsSince(new DateTime('2025-01-01'));

// Get events by URI pattern
$events = $eventStore->getEventsByUri('/orders/*');

// Replay events
$events->replay(function(Event $e) {
    echo "Event: {$e->uri} {$e->method}\n";
});
```

## Directory Structure

```
src/
├── Entity/              # Domain entities
│   └── Master/          # Master data entities
├── Resource/
│   └── App/             # API resources
│       ├── Products/    # Product sub-resources
│       ├── Orders/      # Order sub-resources
│       └── Cart/        # Cart sub-resources
├── Query/               # Query interfaces
│   └── Impl/            # Query implementations
├── Module/              # DI modules
├── Service/             # Business logic services
├── EventSourcing/       # Event sourcing core
└── Interceptor/         # AOP interceptors

var/
├── sql/                 # SQL files
│   └── schema.sql       # Database schema
├── log/                 # Log files
└── tmp/                 # Temporary files
```

## Based On

- [EC-CUBE](https://github.com/EC-CUBE/ec-cube) - Original e-commerce platform
- [BEAR.Sunday](https://bearsunday.github.io/) - Resource-oriented framework
- [Ray.Di](https://github.com/ray-di/Ray.Di) - Dependency injection
- [Ray.Aop](https://github.com/ray-di/Ray.Aop) - Aspect-oriented programming

## License

MIT License
