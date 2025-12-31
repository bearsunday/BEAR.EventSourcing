<?php

declare(strict_types=1);

namespace BearEccube\Service;

interface MailerInterface
{
    public function send(string $to, string $subject, string $body, ?string $htmlBody = null): bool;
}
