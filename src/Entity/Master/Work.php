<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Work status master entity (稼働状態)
 */
class Work extends AbstractMasterEntity
{
    /** 稼働 */
    public const ACTIVE = 1;
    /** 非稼働 */
    public const INACTIVE = 0;
}
