<?php
namespace Highgrove\StoreLocator\Api;

use Highgrove\StoreLocator\Api\Data\StoreLocationGroupInterface;
use Highgrove\StoreLocator\Exception\RestException;
use Magento\Framework\DataObject;

/**
 * Interface StoreLocationGroupRepositoryInterface
 */
interface StoreLocationGroupRepositoryInterface
{
    /**
     * @param string $locationCode
     * @return DataObject[]
     */
    public function getStoreLocationGroupsByLocationCode(string $locationCode):? array;

    /**
     * @param string $locationCode
     * @return void
     * @throws RestException
     */
    public function deleteStoreLocationGroupsByLocationCode(string $locationCode);

    /**
     * @param StoreLocationGroupInterface $storeLocationGroup
     * @return void
     * @throws RestException
     */
    public function save(StoreLocationGroupInterface $storeLocationGroup);

    /**
     * @return void
     * @throws RestException
     */
    public function saveStoreLocationGroups();
}
