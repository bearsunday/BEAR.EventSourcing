<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\Query\CartItemQueryInterface;
use BEAR\EventSourcing\Query\CartQueryInterface;
use BEAR\EventSourcing\Query\CategoryQueryInterface;
use BEAR\EventSourcing\Query\CouponQueryInterface;
use BEAR\EventSourcing\Query\CustomerQueryInterface;
use BEAR\EventSourcing\Query\DeliveryQueryInterface;
use BEAR\EventSourcing\Query\FavoriteQueryInterface;
use BEAR\EventSourcing\Query\Impl\CartItemQuery;
use BEAR\EventSourcing\Query\Impl\CartQuery;
use BEAR\EventSourcing\Query\Impl\CategoryQuery;
use BEAR\EventSourcing\Query\Impl\CouponQuery;
use BEAR\EventSourcing\Query\Impl\CustomerQuery;
use BEAR\EventSourcing\Query\Impl\DeliveryQuery;
use BEAR\EventSourcing\Query\Impl\FavoriteQuery;
use BEAR\EventSourcing\Query\Impl\MailTemplateQuery;
use BEAR\EventSourcing\Query\Impl\MasterQuery;
use BEAR\EventSourcing\Query\Impl\MemberQuery;
use BEAR\EventSourcing\Query\Impl\NewsQuery;
use BEAR\EventSourcing\Query\Impl\OrderItemQuery;
use BEAR\EventSourcing\Query\Impl\OrderQuery;
use BEAR\EventSourcing\Query\Impl\PaymentQuery;
use BEAR\EventSourcing\Query\Impl\ProductCategoryQuery;
use BEAR\EventSourcing\Query\Impl\ProductClassQuery;
use BEAR\EventSourcing\Query\Impl\ProductImageQuery;
use BEAR\EventSourcing\Query\Impl\ProductQuery;
use BEAR\EventSourcing\Query\Impl\ReviewQuery;
use BEAR\EventSourcing\Query\Impl\SearchQuery;
use BEAR\EventSourcing\Query\Impl\ShippingQuery;
use BEAR\EventSourcing\Query\Impl\TaxRuleQuery;
use BEAR\EventSourcing\Query\MailTemplateQueryInterface;
use BEAR\EventSourcing\Query\MasterQueryInterface;
use BEAR\EventSourcing\Query\MemberQueryInterface;
use BEAR\EventSourcing\Query\NewsQueryInterface;
use BEAR\EventSourcing\Query\OrderItemQueryInterface;
use BEAR\EventSourcing\Query\OrderQueryInterface;
use BEAR\EventSourcing\Query\PaymentQueryInterface;
use BEAR\EventSourcing\Query\ProductCategoryQueryInterface;
use BEAR\EventSourcing\Query\ProductClassQueryInterface;
use BEAR\EventSourcing\Query\ProductImageQueryInterface;
use BEAR\EventSourcing\Query\ProductQueryInterface;
use BEAR\EventSourcing\Query\ReviewQueryInterface;
use BEAR\EventSourcing\Query\SearchQueryInterface;
use BEAR\EventSourcing\Query\ShippingQueryInterface;
use BEAR\EventSourcing\Query\TaxRuleQueryInterface;
use Ray\Di\AbstractModule;

/**
 * Query module - binds query interfaces to implementations
 */
class QueryModule extends AbstractModule
{
    protected function configure(): void
    {
        // Product queries
        $this->bind(ProductQueryInterface::class)->to(ProductQuery::class);
        $this->bind(ProductClassQueryInterface::class)->to(ProductClassQuery::class);
        $this->bind(ProductImageQueryInterface::class)->to(ProductImageQuery::class);
        $this->bind(ProductCategoryQueryInterface::class)->to(ProductCategoryQuery::class);

        // Customer queries
        $this->bind(CustomerQueryInterface::class)->to(CustomerQuery::class);

        // Order queries
        $this->bind(OrderQueryInterface::class)->to(OrderQuery::class);
        $this->bind(OrderItemQueryInterface::class)->to(OrderItemQuery::class);
        $this->bind(ShippingQueryInterface::class)->to(ShippingQuery::class);

        // Cart queries
        $this->bind(CartQueryInterface::class)->to(CartQuery::class);
        $this->bind(CartItemQueryInterface::class)->to(CartItemQuery::class);

        // Category queries
        $this->bind(CategoryQueryInterface::class)->to(CategoryQuery::class);

        // Payment and Delivery queries
        $this->bind(PaymentQueryInterface::class)->to(PaymentQuery::class);
        $this->bind(DeliveryQueryInterface::class)->to(DeliveryQuery::class);

        // Member queries (admin)
        $this->bind(MemberQueryInterface::class)->to(MemberQuery::class);

        // Favorite queries
        $this->bind(FavoriteQueryInterface::class)->to(FavoriteQuery::class);

        // Review queries
        $this->bind(ReviewQueryInterface::class)->to(ReviewQuery::class);

        // Coupon queries
        $this->bind(CouponQueryInterface::class)->to(CouponQuery::class);

        // News queries
        $this->bind(NewsQueryInterface::class)->to(NewsQuery::class);

        // Search queries
        $this->bind(SearchQueryInterface::class)->to(SearchQuery::class);

        // Master data queries
        $this->bind(MasterQueryInterface::class)->to(MasterQuery::class);

        // Tax rule queries
        $this->bind(TaxRuleQueryInterface::class)->to(TaxRuleQuery::class);

        // Mail template queries
        $this->bind(MailTemplateQueryInterface::class)->to(MailTemplateQuery::class);
    }
}
