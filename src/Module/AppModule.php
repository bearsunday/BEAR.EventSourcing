<?php

declare(strict_types=1);

namespace BearEccube\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use BearEccube\Query\CartItemQueryInterface;
use BearEccube\Query\CartQueryInterface;
use BearEccube\Query\CategoryQueryInterface;
use BearEccube\Query\CustomerQueryInterface;
use BearEccube\Query\DeliveryQueryInterface;
use BearEccube\Query\OrderItemQueryInterface;
use BearEccube\Query\OrderQueryInterface;
use BearEccube\Query\PaymentQueryInterface;
use BearEccube\Query\ProductCategoryQueryInterface;
use BearEccube\Query\ProductClassQueryInterface;
use BearEccube\Query\ProductImageQueryInterface;
use BearEccube\Query\ProductQueryInterface;
use BearEccube\Query\ShippingQueryInterface;
use BearEccube\Service\CheckoutService;
use BearEccube\Service\CheckoutServiceInterface;
use Ray\Di\AbstractModule;

/**
 * Application module
 */
class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        // Install package module
        $this->install(new PackageModule());

        // Install database module
        $this->install(new DbModule($this->appMeta));

        // Install query module
        $this->install(new QueryModule());

        // Install event sourcing module
        $this->install(new EventSourcingModule());

        // Bind services
        $this->bind(CheckoutServiceInterface::class)->to(CheckoutService::class);
    }
}
