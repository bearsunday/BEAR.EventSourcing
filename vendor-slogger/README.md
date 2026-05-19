# bear/semantic-logger (WIP)

BEAR.Sunday conventions on top of [koriym/semantic-logger](https://github.com/koriym/Koriym.SemanticLogger).

## Position

```
koriym/semantic-logger   Generic structured log primitives
        ▲
bear/semantic-logger     This package — bridges BEAR.Resource into the log
        ▲
bear/event-sourcing      Reads the log, extracts state-change Events
```

This package provides:

- `BEAR\SemanticLogger\SemanticLogger` — implements `BEAR\Resource\LoggerInterface`. Every
  non-GET resource call is opened/closed in the underlying Koriym SemanticLogger as a
  `resource_request` / `resource_response` pair.
- `BEAR\SemanticLogger\ResourceRequestContext` / `ResourceResponseContext` — typed
  contexts (extend `Koriym\SemanticLogger\AbstractContext`).
- `BEAR\SemanticLogger\Module\SemanticLoggerModule` — DI module that binds
  `BEAR\Resource\LoggerInterface → SemanticLogger`.

## Recorded methods

Only state-changing methods are recorded (matching `BEAR\Resource\ProdLogger`):
`POST`, `PUT`, `PATCH`, `DELETE`. `GET`/`HEAD`/`OPTIONS` are skipped.

## Parameter capture

`$ro->uri->query` is BEAR.Sunday's unified parameter store — URL query string, form
body, and JSON body all land here before the resource method is invoked. The bridge
records this field, so `Resource​RequestContext.query` captures the full input
regardless of HTTP method.

## Limitations

- `SCHEMA_URL` constants are placeholders. Real JSON Schemas will be published later.
- `BEAR\Resource\LoggerInterface` fires once per call with the completed `ResourceObject`,
  so `open()` and `close()` are emitted back-to-back with the same timestamp. Profile
  / timing data is not captured.

## Development

This package currently lives inside `BEAR.EventSourcing` at `vendor-slogger/` and is
loaded via a composer path repository. It will be split into its own repository
(`bearsunday/BEAR.SemanticLogger`) once stabilized.

```bash
composer install
composer test
```
