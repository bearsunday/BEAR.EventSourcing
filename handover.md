# Handover

## Branch

- `codex/event-sourcing-quality-handover`

## Summary

- Added and refreshed quality tooling around PHPUnit, PHPCS, Psalm, PHPStan, PHPMD, Rector, ComposerRequireChecker, and GitHub Actions.
- Updated README to describe the current implementation while keeping Semantic Logger as the observation-layer concept.
- Kept `PATCH` support small by adding `onPatch` to the AOP matcher, matching the existing interceptor mapping.
- Hardened `EventStore` JSON hydration with `JSON_THROW_ON_ERROR`.
- Added `EventStoreTest` for append/get round trip and invalid stored JSON behavior.
- Removed local Vim swap file `src/EventSourcing/.EventStore.php.swp`.

## Validation

- `zsh -ic 'sphp85; composer tests'`
  - PHP_CodeSniffer passed.
  - PHPUnit passed: 14 tests, 35 assertions.
  - Psalm passed.
  - PHPStan passed.

## Notes

- The worktree already contained broad modernization and tooling changes before the final handover step; this branch preserves them together for review.
- `EventStore::createTable()` still uses MySQL-oriented DDL (`DATETIME(6)`, `JSON`, indexes). Tests use a SQLite-specific in-memory schema to cover event hydration without changing the public DDL contract.

## Suggested Next Steps

1. Decide whether `EventStore::createTable()` should remain MySQL-specific or become portable.
2. Add tests for `getEventsSince()`, `getEventsByUri()`, and `getEventsByAggregateId()`.
3. If Semantic Logger gets a concrete package/API, add a dedicated adapter instead of expanding `Event` itself.
