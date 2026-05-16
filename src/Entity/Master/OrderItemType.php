<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Order item type master entity (受注明細種別)
 */
class OrderItemType extends AbstractMasterEntity
{
    /** 商品 */
    public const PRODUCT = 1;
    /** 送料 */
    public const DELIVERY_FEE = 2;
    /** 手数料 */
    public const CHARGE = 3;
    /** 値引き */
    public const DISCOUNT = 4;
    /** 税 */
    public const TAX = 5;
    /** ポイント */
    public const POINT = 6;
}
