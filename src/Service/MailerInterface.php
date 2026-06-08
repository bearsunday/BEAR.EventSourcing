<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Service;

interface MailerInterface
{
    public function send(string $to, string $subject, string $body, string|null $htmlBody = null): bool;
}
