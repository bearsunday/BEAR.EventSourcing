<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Mail template entity (メールテンプレート)
 */
class MailTemplate extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $creatorId = null;
    protected Member|null $creator = null;
    protected string $name = '';
    protected string|null $fileName = null;
    protected string|null $mailSubject = null;
    protected string|null $mailHeader = null;
    protected string|null $mailFooter = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getFileName(): string|null
    {
        return $this->fileName;
    }

    public function setFileName(string|null $fileName): static
    {
        $this->fileName = $fileName;

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

    public function getMailHeader(): string|null
    {
        return $this->mailHeader;
    }

    public function setMailHeader(string|null $mailHeader): static
    {
        $this->mailHeader = $mailHeader;

        return $this;
    }

    public function getMailFooter(): string|null
    {
        return $this->mailFooter;
    }

    public function setMailFooter(string|null $mailFooter): static
    {
        $this->mailFooter = $mailFooter;

        return $this;
    }
}
