<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

/**
 * Transforms recorded request params before they reach the observation log, and declares
 * whether that transformation still leaves the operation replayable.
 *
 * Removing a key does not automatically mean the request became non-replayable — see
 * {@see FilteredParams} for the transport/credential distinction that decides it.
 */
interface ParamsFilterInterface
{
    /** @param array<string, mixed> $params */
    public function __invoke(array $params): FilteredParams;
}
