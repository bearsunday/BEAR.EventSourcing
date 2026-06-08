<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use function bccomp;

/**
 * Payment entity (支払方法)
 */
class Payment extends AbstractEntity
{
    protected int|null $id = null;
    protected string $method = '';
    protected string $charge = '0';
    protected string|null $ruleMin = null;
    protected string|null $ruleMax = null;
    protected int $sortNo = 0;
    protected bool $visible = true;
    protected string|null $methodClass = null;
    /** @var PaymentOption[] */
    protected array $paymentOptions = [];

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getCharge(): string
    {
        return $this->charge;
    }

    public function setCharge(string $charge): static
    {
        $this->charge = $charge;

        return $this;
    }

    public function getRuleMin(): string|null
    {
        return $this->ruleMin;
    }

    public function setRuleMin(string|null $ruleMin): static
    {
        $this->ruleMin = $ruleMin;

        return $this;
    }

    public function getRuleMax(): string|null
    {
        return $this->ruleMax;
    }

    public function setRuleMax(string|null $ruleMax): static
    {
        $this->ruleMax = $ruleMax;

        return $this;
    }

    public function getSortNo(): int
    {
        return $this->sortNo;
    }

    public function setSortNo(int $sortNo): static
    {
        $this->sortNo = $sortNo;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function getMethodClass(): string|null
    {
        return $this->methodClass;
    }

    public function setMethodClass(string|null $methodClass): static
    {
        $this->methodClass = $methodClass;

        return $this;
    }

    /** @return PaymentOption[] */
    public function getPaymentOptions(): array
    {
        return $this->paymentOptions;
    }

    /** @param PaymentOption[] $paymentOptions */
    public function setPaymentOptions(array $paymentOptions): static
    {
        $this->paymentOptions = $paymentOptions;

        return $this;
    }

    /**
     * Check if amount is within payment rules
     */
    public function isValidAmount(string $amount): bool
    {
        if ($this->ruleMin !== null && bccomp($amount, $this->ruleMin) < 0) {
            return false;
        }

        return $this->ruleMax === null || bccomp($amount, $this->ruleMax) <= 0;
    }
}
