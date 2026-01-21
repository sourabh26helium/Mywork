<?php
namespace Highgrove\StoreLocator\Api\Data;

/**
 * Interface ReviewInterface
 */
interface ReviewInterface
{
    const LOCATION_ID = 'location_id';
    const STAR_RATING = 'star_rating';
    const REVIEW_COUNT = 'review_count';
    const REVIEW_URL = 'review_url';

    /**
     * @return int
     */
    public function getLocationId(): int;

    /**
     * @param int $locationId
     */
    public function setLocationId(int $locationId);

    /**
     * @return float
     */
    public function getStarRating():? float;

    /**
     * @param float $starRating
     */
    public function setStarRating(float $starRating);

    /**
     * @return int
     */
    public function getReviewCount():? int;

    /**
     * @param int $reviewCount
     */
    public function setReviewCount(int $reviewCount);

    /**
     * @return string
     */
    public function getReviewUrl():? string;

    /**
     * @param string $reviewUrl
     */
    public function setReviewUrl(string $reviewUrl);
}
