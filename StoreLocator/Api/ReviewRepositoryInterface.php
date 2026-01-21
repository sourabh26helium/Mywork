<?php
namespace Highgrove\StoreLocator\Api;

use Highgrove\StoreLocator\Api\Data\ReviewInterface;
use Highgrove\StoreLocator\Model\Review;

/**
 * Interface ReviewRepositoryInterface
 */
interface ReviewRepositoryInterface
{
    /**
     * @param int $locationId
     * @return Review
     */
    public function getReviewByLocationId(int $locationId): Review;

    /**
     * @param ReviewInterface $review
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(ReviewInterface $review);
}
