<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\ReviewStatus;

/**
 * Product review entity (商品レビュー)
 */
class Review extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $productId = null;
    protected ?Product $product = null;
    protected ?int $customerId = null;
    protected ?Customer $customer = null;
    protected ?ReviewStatus $status = null;
    protected string $reviewerName = '';
    protected ?string $reviewerUrl = null;
    protected int $rating = 5;
    protected string $title = '';
    protected string $comment = '';
    protected bool $visible = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): static
    {
        $this->productId = $productId;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): static
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    public function getStatus(): ?ReviewStatus
    {
        return $this->status;
    }

    public function setStatus(?ReviewStatus $status): static
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

    public function getReviewerUrl(): ?string
    {
        return $this->reviewerUrl;
    }

    public function setReviewerUrl(?string $reviewerUrl): static
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
