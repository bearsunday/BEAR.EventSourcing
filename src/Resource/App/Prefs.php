<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\MasterQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Prefectures resource (都道府県)
 */
class Prefs extends ResourceObject
{
    private MasterQueryInterface $masterQuery;

    #[Inject]
    public function __construct(MasterQueryInterface $masterQuery)
    {
        $this->masterQuery = $masterQuery;
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
