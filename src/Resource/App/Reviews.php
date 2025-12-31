<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\ReviewQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Product reviews resource (レビュー一覧)
 */
class Reviews extends ResourceObject
{
    private ReviewQueryInterface $reviewQuery;

    #[Inject]
    public function __construct(ReviewQueryInterface $reviewQuery)
    {
        $this->reviewQuery = $reviewQuery;
    }

    /**
     * Get reviews for a product
     *
     * @param int $productId Product ID
     * @param int $page      Page number
     * @param int $limit     Items per page
     */
    public function onGet(int $productId, int $page = 1, int $limit = 10): static
    {
        $offset = ($page - 1) * $limit;

        $this->body = [
            'reviews' => $this->reviewQuery->findByProductId($productId, $limit, $offset),
            'stats' => $this->reviewQuery->getStats($productId),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $this->reviewQuery->countByProductId($productId),
            ],
        ];

        return $this;
    }

    /**
     * Create a new review
     *
     * @param int         $productId    Product ID
     * @param int         $rating       Rating (1-5)
     * @param string      $title        Review title
     * @param string      $comment      Review comment
     * @param string      $reviewerName Reviewer name
     * @param int|null    $customerId   Customer ID (if logged in)
     * @param string|null $reviewerUrl  Reviewer URL
     */
    public function onPost(
        int $productId,
        int $rating,
        string $title,
        string $comment,
        string $reviewerName,
        ?int $customerId = null,
        ?string $reviewerUrl = null
    ): static {
        if ($rating < 1 || $rating > 5) {
            $this->code = 400;
            $this->body = ['error' => 'Rating must be between 1 and 5'];
            return $this;
        }

        $id = $this->reviewQuery->create([
            'product_id' => $productId,
            'customer_id' => $customerId,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'reviewer_name' => $reviewerName,
            'reviewer_url' => $reviewerUrl,
            'status_id' => 1, // Pending approval
            'visible' => false,
        ]);

        $this->code = 201;
        $this->body = ['id' => $id, 'message' => 'Review submitted for approval'];

        return $this;
    }
}
