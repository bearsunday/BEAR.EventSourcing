<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Mail template entity (メールテンプレート)
 */
class MailTemplate extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $creatorId = null;
    protected ?Member $creator = null;
    protected string $name = '';
    protected ?string $fileName = null;
    protected ?string $mailSubject = null;
    protected ?string $mailHeader = null;
    protected ?string $mailFooter = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }

    public function getCreatorId(): ?int { return $this->creatorId; }
    public function setCreatorId(?int $creatorId): static { $this->creatorId = $creatorId; return $this; }

    public function getCreator(): ?Member { return $this->creator; }
    public function setCreator(?Member $creator): static { $this->creator = $creator; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $fileName): static { $this->fileName = $fileName; return $this; }

    public function getMailSubject(): ?string { return $this->mailSubject; }
    public function setMailSubject(?string $mailSubject): static { $this->mailSubject = $mailSubject; return $this; }

    public function getMailHeader(): ?string { return $this->mailHeader; }
    public function setMailHeader(?string $mailHeader): static { $this->mailHeader = $mailHeader; return $this; }

    public function getMailFooter(): ?string { return $this->mailFooter; }
    public function setMailFooter(?string $mailFooter): static { $this->mailFooter = $mailFooter; return $this; }
}
