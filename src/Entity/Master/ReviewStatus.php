<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Review status master entity (レビューステータス)
 */
class ReviewStatus extends AbstractMasterEntity
{
    /** 未承認 */
    public const PENDING = 1;
    /** 承認済み */
    public const APPROVED = 2;
    /** 非承認 */
    public const REJECTED = 3;
}
