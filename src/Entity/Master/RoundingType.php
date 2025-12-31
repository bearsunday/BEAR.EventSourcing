<?php

declare(strict_types=1);

namespace BearEccube\Entity\Master;

/**
 * Rounding type master entity (端数処理)
 */
class RoundingType extends AbstractMasterEntity
{
    /** 四捨五入 */
    public const ROUND = 1;
    /** 切り捨て */
    public const FLOOR = 2;
    /** 切り上げ */
    public const CEIL = 3;
}
