<?php
namespace Highgrove\StoreLocator\Model\ResourceModel;

use Highgrove\StoreLocator\Api\Data\ReviewInterface;
use Highgrove\StoreLocator\Api\ReviewRepositoryInterface;
use Highgrove\StoreLocator\Model\Review;
use Highgrove\StoreLocator\Model\ReviewFactory;
use Highgrove\StoreLocator\Model\ResourceModel\Review as ReviewResource;

/**
 * Class ReviewRepository
 */
class ReviewRepository implements ReviewRepositoryInterface
{
    /**
     * @var \Highgrove\StoreLocator\Model\ResourceModel\Review
     */
    private $reviewResource;

    /**
     * @var ReviewFactory
     */
    private $reviewFactory;

    /**
     * ReviewRepository constructor.
     * @param \Highgrove\StoreLocator\Model\ResourceModel\Review $reviewResource
     * @param ReviewFactory $reviewFactory
     */
    public function __construct(
        ReviewResource $reviewResource,
        ReviewFactory $reviewFactory
    ) {
        $this->reviewResource = $reviewResource;
        $this->reviewFactory = $reviewFactory;
    }

    /**
     * @inheritDoc
     */
    public function getReviewByLocationId(int $locationId): Review
    {
        $review = $this->reviewFactory->create();
        $this->reviewResource->load($review, $locationId, 'location_id');
        return $review;
    }

    /**
     * @inheritDoc
     */
    public function save(ReviewInterface $review)
    {
        $this->reviewResource->save($review);
    }
}
