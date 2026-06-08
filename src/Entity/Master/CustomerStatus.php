<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Customer status master entity (会員ステータス)
 */
class CustomerStatus extends AbstractMasterEntity
{
    /** 仮会員 */
    public const PROVISIONAL = 1;

    /** 本会員 */
    public const REGULAR = 2;

    /** 退会 */
    public const WITHDRAWING = 3;
}
