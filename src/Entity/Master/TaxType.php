<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Tax type master entity (税区分)
 */
class TaxType extends AbstractMasterEntity
{
    /** 課税 */
    public const TAXATION = 1;

    /** 不課税 */
    public const NON_TAXABLE = 2;

    /** 免税 */
    public const TAX_EXEMPT = 3;
}
