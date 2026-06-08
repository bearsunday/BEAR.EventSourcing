<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\Service\CheckoutService;
use BEAR\EventSourcing\Service\CheckoutServiceInterface;
use BEAR\EventSourcing\Service\MailerInterface;
use BEAR\EventSourcing\Service\MailService;
use BEAR\EventSourcing\Service\MailServiceInterface;
use BEAR\EventSourcing\Service\SmtpMailer;
use BEAR\EventSourcing\Service\StockService;
use BEAR\EventSourcing\Service\StockServiceInterface;
use BEAR\EventSourcing\Service\TaxService;
use BEAR\EventSourcing\Service\TaxServiceInterface;
use Ray\Di\AbstractModule;

use function getenv;

/**
 * Service module - binds service interfaces to implementations
 */
class ServiceModule extends AbstractModule
{
    protected function configure(): void
    {
        // Checkout service
        $this->bind(CheckoutServiceInterface::class)->to(CheckoutService::class);

        // Tax service
        $this->bind(TaxServiceInterface::class)->to(TaxService::class);

        // Stock service
        $this->bind(StockServiceInterface::class)->to(StockService::class);

        // Mail service and mailer
        $this->bind(MailServiceInterface::class)->to(MailService::class);
        $this->bind(MailerInterface::class)->to(SmtpMailer::class);

        // SMTP configuration
        $this->bind()->annotatedWith('smtp_host')->toInstance(getenv('SMTP_HOST') ?: 'localhost');
        $this->bind()->annotatedWith('smtp_port')->toInstance((int) (getenv('SMTP_PORT') ?: 25));
        $this->bind()->annotatedWith('smtp_user')->toInstance(getenv('SMTP_USER') ?: '');
        $this->bind()->annotatedWith('smtp_pass')->toInstance(getenv('SMTP_PASS') ?: '');
        $this->bind()->annotatedWith('mail_from')->toInstance(getenv('MAIL_FROM') ?: 'noreply@example.com');
        $this->bind()->annotatedWith('mail_from_name')->toInstance(getenv('MAIL_FROM_NAME') ?: 'EC-CUBE');
    }
}
