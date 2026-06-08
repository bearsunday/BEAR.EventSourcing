<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Service;

interface MailServiceInterface
{
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation(int $orderId): bool;

    /**
     * Send shipping notification email
     */
    public function sendShippingNotification(int $orderId, int $shippingId): bool;

    /**
     * Send password reset email
     */
    public function sendPasswordReset(string $email, string $resetUrl): bool;

    /**
     * Send registration complete email
     */
    public function sendRegistrationComplete(int $customerId): bool;
}
