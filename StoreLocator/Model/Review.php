<?php
namespace Highgrove\StoreLocator\Model;

use Highgrove\StoreLocator\Model\ResourceModel\Review as ReviewResource;
use Highgrove\StoreLocator\Api\Data\ReviewInterface;

/**
 * Class Review
 */
class Review extends \Magento\Framework\Model\AbstractModel implements ReviewInterface
{
    /**
     * Init resource model.
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(ReviewResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getLocationId(): int
    {
        return $this->getData(self::LOCATION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setLocationId(int $locationId)
    {
        $this->setData(self::LOCATION_ID, $locationId);
    }

    /**
     * @inheritDoc
     */
    public function getStarRating():? float
    {
        return $this->getData(self::STAR_RATING);
    }

    /**
     * @inheritDoc
     */
    public function setStarRating(float $starRating)
    {
        $this->setData(self::STAR_RATING, $starRating);
    }

    /**
     * @inheritDoc
     */
    public function getReviewCount():? int
    {
        return $this->getData(self::REVIEW_COUNT);
    }

    /**
     * @inheritDoc
     */
    public function setReviewCount(int $reviewCount)
    {
        $this->setData(self::REVIEW_COUNT, $reviewCount);
    }
    /**
     * @inheritDoc
     */
    public function getReviewUrl():? string
    {
        return $this->getData(self::REVIEW_URL);
    }

    /**
     * @inheritDoc
     */
    public function setReviewUrl(string $reviewUrl)
    {
        $this->setData(self::REVIEW_URL, $reviewUrl);
    }

}
