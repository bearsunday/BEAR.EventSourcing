<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\ReviewStatus;

use function max;
use function min;

/**
 * Product review entity (商品レビュー)
 */
class Review extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $productId = null;
    protected Product|null $product = null;
    protected int|null $customerId = null;
    protected Customer|null $customer = null;
    protected ReviewStatus|null $status = null;
    protected string $reviewerName = '';
    protected string|null $reviewerUrl = null;
    protected int $rating = 5;
    protected string $title = '';
    protected string $comment = '';
    protected bool $visible = false;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getProductId(): int|null
    {
        return $this->productId;
    }

    public function setProductId(int|null $productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    public function getProduct(): Product|null
    {
        return $this->product;
    }

    public function setProduct(Product|null $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getCustomerId(): int|null
    {
        return $this->customerId;
    }

    public function setCustomerId(int|null $customerId): static
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getCustomer(): Customer|null
    {
        return $this->customer;
    }

    public function setCustomer(Customer|null $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getStatus(): ReviewStatus|null
    {
        return $this->status;
    }

    public function setStatus(ReviewStatus|null $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getReviewerName(): string
    {
        return $this->reviewerName;
    }

    public function setReviewerName(string $reviewerName): static
    {
        $this->reviewerName = $reviewerName;

        return $this;
    }

    public function getReviewerUrl(): string|null
    {
        return $this->reviewerUrl;
    }

    public function setReviewerUrl(string|null $reviewerUrl): static
    {
        $this->reviewerUrl = $reviewerUrl;

        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = max(1, min(5, $rating));

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }
}
