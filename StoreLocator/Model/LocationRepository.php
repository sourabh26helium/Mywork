<?php
namespace Highgrove\StoreLocator\Model;
use MageWorx\Locations\Api\Data\LocationInterface;
class LocationRepository extends \MageWorx\Locations\Model\LocationRepository
{    
    public function getListLocationByProductIdsForCheckout($ids, $limit = null, $addOutOfStockItems = true, $filters = [])
    {
        /** @var Collection $locationCollection */
        $locationCollection = $this->getListLocationForFront($this->storeManager->getStore()->getId(), $filters);
        if (is_object($ids)) {
            $ids = $ids->getId();
        }
        $locationCollection->addProductIdsFilterForCheckout($ids, $addOutOfStockItems);
        if ($limit) {
            $locationCollection->setLimit($limit);
        }

        if (!empty($filters)) {
            $locationCollection->addSearchFilters($filters);
        }
        //echo $locationCollection->getSelect()->__toString();
        return $locationCollection;
    }

    /**
     * @param null|int $storeId
     * @param array $filters
     * @return Collection
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListLocationForFront($storeId = null, $filters = [])
    {
        /** @var Collection $locationCollection */
        $locationCollection = $this->locationCollectionFactory->create();
        $locationCollection->addAttributeToSelect('*');
        $locationCollection->addFieldToFilter(
            LocationInterface::IS_ACTIVE,
            LocationInterface::ACTIVE
        );

        if($filters){
            if(array_key_exists('set-type', $filters) && $filters['set-type'] !== "by_radius"){
                //sort store locations in Alphabetical order as default list
                $locationCollection->addAttributeToSort('name', 'ASC');
            }
        }

        $storeId = $storeId ?? $this->storeManager->getStore()->getId();
        $locationCollection->addStoreFilter($storeId);
        $locationCollection->setOrderByOrderField($filters);

        if (empty($filters)) {
         //   $locationCollection = $this->addFiltersFromSession($locationCollection);
        } else {
            $locationCollection->addDistanceField($filters);
            $locationCollection->addSearchFilters($filters);
        }

        return $locationCollection;
    }
}