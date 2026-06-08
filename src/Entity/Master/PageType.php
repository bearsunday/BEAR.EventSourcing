<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Page type master entity (ページタイプ)
 */
class PageType extends AbstractMasterEntity
{
    /** トップページ */
    public const TOP = 1;

    /** 商品一覧 */
    public const PRODUCT_LIST = 2;

    /** 商品詳細 */
    public const PRODUCT_DETAIL = 3;

    /** マイページ */
    public const MYPAGE = 4;

    /** その他 */
    public const OTHER = 5;
}
