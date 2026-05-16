<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;

/**
 * Mail history entity (メール送信履歴)
 */
class MailHistory extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $orderId = null;
    protected ?Order $order = null;
    protected ?DateTimeImmutable $sendDate = null;
    protected ?int $mailTemplateId = null;
    protected ?MailTemplate $mailTemplate = null;
    protected ?string $sender = null;
    protected ?int $creatorId = null;
    protected ?Member $creator = null;
    protected ?string $mailSubject = null;
    protected ?string $mailBody = null;
    protected ?string $mailHtmlBody = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }

    public function getOrderId(): ?int { return $this->orderId; }
    public function setOrderId(?int $orderId): static { $this->orderId = $orderId; return $this; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): static { $this->order = $order; return $this; }

    public function getSendDate(): ?DateTimeImmutable { return $this->sendDate; }
    public function setSendDate(?DateTimeImmutable $sendDate): static { $this->sendDate = $sendDate; return $this; }

    public function getMailTemplateId(): ?int { return $this->mailTemplateId; }
    public function setMailTemplateId(?int $mailTemplateId): static { $this->mailTemplateId = $mailTemplateId; return $this; }

    public function getMailTemplate(): ?MailTemplate { return $this->mailTemplate; }
    public function setMailTemplate(?MailTemplate $mailTemplate): static { $this->mailTemplate = $mailTemplate; return $this; }

    public function getSender(): ?string { return $this->sender; }
    public function setSender(?string $sender): static { $this->sender = $sender; return $this; }

    public function getCreatorId(): ?int { return $this->creatorId; }
    public function setCreatorId(?int $creatorId): static { $this->creatorId = $creatorId; return $this; }

    public function getCreator(): ?Member { return $this->creator; }
    public function setCreator(?Member $creator): static { $this->creator = $creator; return $this; }

    public function getMailSubject(): ?string { return $this->mailSubject; }
    public function setMailSubject(?string $mailSubject): static { $this->mailSubject = $mailSubject; return $this; }

    public function getMailBody(): ?string { return $this->mailBody; }
    public function setMailBody(?string $mailBody): static { $this->mailBody = $mailBody; return $this; }

    public function getMailHtmlBody(): ?string { return $this->mailHtmlBody; }
    public function setMailHtmlBody(?string $mailHtmlBody): static { $this->mailHtmlBody = $mailHtmlBody; return $this; }
}
