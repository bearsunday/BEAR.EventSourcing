<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity\Master;

/**
 * Payment method master entity (支払い方法)
 */
class PaymentMethod extends AbstractMasterEntity
{
    protected ?string $chargeFlg = null;
    protected ?string $fixFlg = null;

    public function getChargeFlg(): ?string
    {
        return $this->chargeFlg;
    }

    public function setChargeFlg(?string $chargeFlg): static
    {
        $this->chargeFlg = $chargeFlg;
        return $this;
    }

    public function getFixFlg(): ?string
    {
        return $this->fixFlg;
    }

    public function setFixFlg(?string $fixFlg): static
    {
        $this->fixFlg = $fixFlg;
        return $this;
    }
}
