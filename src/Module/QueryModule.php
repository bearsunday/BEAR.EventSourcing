<?php

declare(strict_types=1);

namespace BearEccube\Module;

use BearEccube\Query\CartItemQueryInterface;
use BearEccube\Query\CartQueryInterface;
use BearEccube\Query\CategoryQueryInterface;
use BearEccube\Query\CouponQueryInterface;
use BearEccube\Query\CustomerQueryInterface;
use BearEccube\Query\DeliveryQueryInterface;
use BearEccube\Query\FavoriteQueryInterface;
use BearEccube\Query\Impl\CartItemQuery;
use BearEccube\Query\Impl\CartQuery;
use BearEccube\Query\Impl\CategoryQuery;
use BearEccube\Query\Impl\CouponQuery;
use BearEccube\Query\Impl\CustomerQuery;
use BearEccube\Query\Impl\DeliveryQuery;
use BearEccube\Query\Impl\FavoriteQuery;
use BearEccube\Query\Impl\MailTemplateQuery;
use BearEccube\Query\Impl\MasterQuery;
use BearEccube\Query\Impl\MemberQuery;
use BearEccube\Query\Impl\NewsQuery;
use BearEccube\Query\Impl\OrderItemQuery;
use BearEccube\Query\Impl\OrderQuery;
use BearEccube\Query\Impl\PaymentQuery;
use BearEccube\Query\Impl\ProductCategoryQuery;
use BearEccube\Query\Impl\ProductClassQuery;
use BearEccube\Query\Impl\ProductImageQuery;
use BearEccube\Query\Impl\ProductQuery;
use BearEccube\Query\Impl\ReviewQuery;
use BearEccube\Query\Impl\SearchQuery;
use BearEccube\Query\Impl\ShippingQuery;
use BearEccube\Query\Impl\TaxRuleQuery;
use BearEccube\Query\MailTemplateQueryInterface;
use BearEccube\Query\MasterQueryInterface;
use BearEccube\Query\MemberQueryInterface;
use BearEccube\Query\NewsQueryInterface;
use BearEccube\Query\OrderItemQueryInterface;
use BearEccube\Query\OrderQueryInterface;
use BearEccube\Query\PaymentQueryInterface;
use BearEccube\Query\ProductCategoryQueryInterface;
use BearEccube\Query\ProductClassQueryInterface;
use BearEccube\Query\ProductImageQueryInterface;
use BearEccube\Query\ProductQueryInterface;
use BearEccube\Query\ReviewQueryInterface;
use BearEccube\Query\SearchQueryInterface;
use BearEccube\Query\ShippingQueryInterface;
use BearEccube\Query\TaxRuleQueryInterface;
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
