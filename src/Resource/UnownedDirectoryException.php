<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

/**
 * The directory carries no `FileBodyStore::MARKER`, so the store refuses to touch it.
 *
 * Distinct from the other BodyStoreException cases (a lost race with a concurrent
 * prune, an unwritable file) so a caller can skip foreign directories on purpose
 * without also swallowing real failures.
 */
final class UnownedDirectoryException extends BodyStoreException
{
}
