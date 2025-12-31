<?php

declare(strict_types=1);

namespace BearEccube\Entity\Master;

/**
 * Authority master entity (権限)
 */
class Authority extends AbstractMasterEntity
{
    /** システム管理者 */
    public const ADMIN = 0;
    /** 店舗オーナー */
    public const OWNER = 1;
}
