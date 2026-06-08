<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;

/**
 * Mail history entity (メール送信履歴)
 */
class MailHistory extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $orderId = null;
    protected Order|null $order = null;
    protected DateTimeImmutable|null $sendDate = null;
    protected int|null $mailTemplateId = null;
    protected MailTemplate|null $mailTemplate = null;
    protected string|null $sender = null;
    protected int|null $creatorId = null;
    protected Member|null $creator = null;
    protected string|null $mailSubject = null;
    protected string|null $mailBody = null;
    protected string|null $mailHtmlBody = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getOrderId(): int|null
    {
        return $this->orderId;
    }

    public function setOrderId(int|null $orderId): static
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getOrder(): Order|null
    {
        return $this->order;
    }

    public function setOrder(Order|null $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getSendDate(): DateTimeImmutable|null
    {
        return $this->sendDate;
    }

    public function setSendDate(DateTimeImmutable|null $sendDate): static
    {
        $this->sendDate = $sendDate;

        return $this;
    }

    public function getMailTemplateId(): int|null
    {
        return $this->mailTemplateId;
    }

    public function setMailTemplateId(int|null $mailTemplateId): static
    {
        $this->mailTemplateId = $mailTemplateId;

        return $this;
    }

    public function getMailTemplate(): MailTemplate|null
    {
        return $this->mailTemplate;
    }

    public function setMailTemplate(MailTemplate|null $mailTemplate): static
    {
        $this->mailTemplate = $mailTemplate;

        return $this;
    }

    public function getSender(): string|null
    {
        return $this->sender;
    }

    public function setSender(string|null $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getCreatorId(): int|null
    {
        return $this->creatorId;
    }

    public function setCreatorId(int|null $creatorId): static
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    public function getCreator(): Member|null
    {
        return $this->creator;
    }

    public function setCreator(Member|null $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getMailSubject(): string|null
    {
        return $this->mailSubject;
    }

    public function setMailSubject(string|null $mailSubject): static
    {
        $this->mailSubject = $mailSubject;

        return $this;
    }

    public function getMailBody(): string|null
    {
        return $this->mailBody;
    }

    public function setMailBody(string|null $mailBody): static
    {
        $this->mailBody = $mailBody;

        return $this;
    }

    public function getMailHtmlBody(): string|null
    {
        return $this->mailHtmlBody;
    }

    public function setMailHtmlBody(string|null $mailHtmlBody): static
    {
        $this->mailHtmlBody = $mailHtmlBody;

        return $this;
    }
}
