<?php

declare(strict_types=1);

namespace BearEccube\Service;

use BearEccube\Query\MailTemplateQueryInterface;
use BearEccube\Query\OrderQueryInterface;
use DateTimeImmutable;

/**
 * Mail service
 */
class MailService implements MailServiceInterface
{
    public function __construct(
        private readonly MailTemplateQueryInterface $templateQuery,
        private readonly OrderQueryInterface $orderQuery,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sendOrderConfirmation(int $orderId): bool
    {
        $order = $this->orderQuery->findById($orderId);
        if ($order === null || empty($order['email'])) {
            return false;
        }

        $template = $this->templateQuery->findByName('order_confirm');
        if ($template === null) {
            return false;
        }

        $subject = $this->renderTemplate($template['mail_subject'], $order);
        $body = $this->renderTemplate($template['mail_header'] . "\n" . $template['mail_footer'], $order);

        return $this->mailer->send(
            $order['email'],
            $subject,
            $body
        );
    }

    /**
     * @inheritDoc
     */
    public function sendShippingNotification(int $orderId, int $shippingId): bool
    {
        $order = $this->orderQuery->findById($orderId);
        if ($order === null || empty($order['email'])) {
            return false;
        }

        $template = $this->templateQuery->findByName('shipping_notify');
        if ($template === null) {
            return false;
        }

        $subject = $this->renderTemplate($template['mail_subject'], $order);
        $body = $this->renderTemplate($template['mail_header'] . "\n" . $template['mail_footer'], $order);

        return $this->mailer->send(
            $order['email'],
            $subject,
            $body
        );
    }

    /**
     * @inheritDoc
     */
    public function sendPasswordReset(string $email, string $resetUrl): bool
    {
        $template = $this->templateQuery->findByName('password_reset');
        if ($template === null) {
            return false;
        }

        $data = ['email' => $email, 'reset_url' => $resetUrl];

        $subject = $this->renderTemplate($template['mail_subject'], $data);
        $body = $this->renderTemplate($template['mail_header'] . "\n" . $template['mail_footer'], $data);

        return $this->mailer->send($email, $subject, $body);
    }

    /**
     * @inheritDoc
     */
    public function sendRegistrationComplete(int $customerId): bool
    {
        // Implementation
        return true;
    }

    private function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{{' . $key . '}}', (string)$value, $template);
            }
        }
        return $template;
    }
}
