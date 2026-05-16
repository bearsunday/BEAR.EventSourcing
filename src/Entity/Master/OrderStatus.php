<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Order status master entity (注文ステータス)
 */
class OrderStatus extends AbstractMasterEntity
{
    /** 新規受付 */
    public const NEW = 1;
    /** 注文取消し */
    public const CANCEL = 3;
    /** 対応中 */
    public const IN_PROGRESS = 4;
    /** 発送済み */
    public const DELIVERED = 5;
    /** 入金済み */
    public const PAID = 6;
    /** 決済処理中 */
    public const PENDING = 7;
    /** 購入処理中 */
    public const PROCESSING = 8;
    /** 返品 */
    public const RETURNED = 9;
}
