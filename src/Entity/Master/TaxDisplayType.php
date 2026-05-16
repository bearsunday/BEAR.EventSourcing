<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Tax display type master entity (税表示区分)
 */
class TaxDisplayType extends AbstractMasterEntity
{
    /** 税込価格 */
    public const INCLUDED = 1;
    /** 税抜価格 */
    public const EXCLUDED = 2;
}
