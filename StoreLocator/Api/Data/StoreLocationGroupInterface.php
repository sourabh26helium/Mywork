<?php
namespace Highgrove\StoreLocator\Api\Data;

/**
 * Interface StoreLocationGroupInterface
 */
interface StoreLocationGroupInterface
{
    const ID = 'group_id';
    const LOCATION_CODE = 'location_code';
    const LOCATION_LINK = 'location_link';

    /**
     * @return int
     */
    public function getGroupId(): int;

    /**
     * @param int $id
     */
    public function setGroupId(int $id);

    /**
     * @return string
     */
    public function getLocationCode(): string;

    /**
     * @param string $locationCode
     */
    public function setLocationCode(string $locationCode);

    /**
     * @return string
     */
    public function getLocationLink(): string;

    /**
     * @param string $locationLink
     */
    public function setLocationLink(string $locationLink);
}
