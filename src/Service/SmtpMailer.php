<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Service;

use function array_keys;
use function array_map;
use function array_values;
use function implode;
use function mail;
use function md5;
use function uniqid;

/**
 * SMTP Mailer implementation
 */
class SmtpMailer implements MailerInterface
{
    public function __construct(
        private readonly string $fromEmail = 'noreply@example.com',
        private readonly string $fromName = 'EC-CUBE Shop',
    ) {
    }

    public function send(string $to, string $subject, string $body, string|null $htmlBody = null): bool
    {
        $headers = [
            'From' => "{$this->fromName} <{$this->fromEmail}>",
            'Reply-To' => $this->fromEmail,
            'X-Mailer' => 'BEAR.Eccube/1.0',
            'MIME-Version' => '1.0',
        ];

        if ($htmlBody !== null) {
            $boundary = md5(uniqid());
            $headers['Content-Type'] = "multipart/alternative; boundary=\"{$boundary}\"";

            $body = "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
                . $body . "\r\n\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                . $htmlBody . "\r\n\r\n"
                . "--{$boundary}--";
        } else {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        }

        $headerString = implode("\r\n", array_map(
            static fn ($k, $v) => "{$k}: {$v}",
            array_keys($headers),
            array_values($headers),
        ));

        return mail($to, $subject, $body, $headerString);
    }
}
