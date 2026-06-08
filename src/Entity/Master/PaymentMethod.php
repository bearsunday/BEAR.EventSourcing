<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Payment method master entity (支払い方法)
 */
class PaymentMethod extends AbstractMasterEntity
{
    protected string|null $chargeFlg = null;
    protected string|null $fixFlg = null;

    public function getChargeFlg(): string|null
    {
        return $this->chargeFlg;
    }

    public function setChargeFlg(string|null $chargeFlg): static
    {
        $this->chargeFlg = $chargeFlg;

        return $this;
    }

    public function getFixFlg(): string|null
    {
        return $this->fixFlg;
    }

    public function setFixFlg(string|null $fixFlg): static
    {
        $this->fixFlg = $fixFlg;

        return $this;
    }
}
