<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Product status master entity (商品ステータス)
 */
class ProductStatus extends AbstractMasterEntity
{
    /** 公開 */
    public const DISPLAY_SHOW = 1;
    /** 非公開 */
    public const DISPLAY_HIDE = 2;
    /** 廃止 */
    public const DISPLAY_ABOLISHED = 3;
}
