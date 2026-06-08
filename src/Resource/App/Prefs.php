<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\EventSourcing\Query\MasterQueryInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

/**
 * Prefectures resource (都道府県)
 */
class Prefs extends ResourceObject
{
    #[Inject]
    public function __construct(private MasterQueryInterface $masterQuery)
    {
    }

    /**
     * Get all prefectures
     */
    public function onGet(): static
    {
        $this->body = $this->masterQuery->getPrefectures();

        return $this;
    }
}
