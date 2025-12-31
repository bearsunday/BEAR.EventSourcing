<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\ContactQueryInterface;
use BearEccube\Service\MailServiceInterface;

class Contact extends ResourceObject
{
    public function __construct(
        private readonly ContactQueryInterface $query,
        private readonly MailServiceInterface $mailService
    ) {}

    public function onPost(
        string $name01,
        string $name02,
        string $email,
        string $subject,
        string $message,
        ?string $kana01 = null,
        ?string $kana02 = null,
        ?string $phone_number = null,
        ?string $postal_code = null,
        ?int $pref_id = null,
        ?string $addr01 = null,
        ?string $addr02 = null,
        ?int $customer_id = null
    ): static {
        $id = $this->query->create([
            'customer_id' => $customer_id,
            'name01' => $name01,
            'name02' => $name02,
            'kana01' => $kana01,
            'kana02' => $kana02,
            'email' => $email,
            'phone_number' => $phone_number,
            'postal_code' => $postal_code,
            'pref_id' => $pref_id,
            'addr01' => $addr01,
            'addr02' => $addr02,
            'subject' => $subject,
            'message' => $message,
        ]);

        // Send confirmation email
        $this->mailService->send(
            $email,
            "{$name01} {$name02}",
            "お問い合わせを受け付けました: {$subject}",
            "お問い合わせありがとうございます。\n\n" .
            "件名: {$subject}\n" .
            "内容:\n{$message}\n\n" .
            "担当者より折り返しご連絡いたします。"
        );

        $this->code = 201;
        $this->body = ['id' => $id, 'message' => 'お問い合わせを受け付けました'];
        return $this;
    }
}
