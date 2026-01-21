<?php
namespace Highgrove\StoreLocator\Observer;

use Highgrove\StoreLocator\Model\CookieManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\Locations\Model\ResourceModel\Location\Collection as LocationCollection;
use MageWorx\Locations\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;

/**
 * Class SetLocation
 */
class SetLocation implements ObserverInterface
{
    /**
     * @var CookieManagement
     */
    private $cookieManagement;

    /**
     * @var LocationCollectionFactory
     */
    private $locationCollectionFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * SetLocation constructor.
     * @param CookieManagement $cookieManagement
     * @param LocationCollectionFactory $collectionFactory
     * @param Session $customerSession
     */
    public function __construct(
        CookieManagement $cookieManagement,
        LocationCollectionFactory $collectionFactory,
        Session $customerSession
    ) {
        $this->cookieManagement = $cookieManagement;
        $this->locationCollectionFactory = $collectionFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $storeId = $this->cookieManagement->getLocationCookie();
        if ($storeId) {
            return;
        }

        if (!$latlong = $this->cookieManagement->getInventoryLocationCookie()) {
            return;
        }

        $filters = $this->setupLocationFilters($latlong);
        $location = $this->getClosestLocation($filters);

        if ($location && $location->getId()) {
            $this->cookieManagement->setLocationCookie($location->getId());
            $this->cookieManagement->setCustomerSessionLocation($location);
        }
    }

    /**
     * @param string $latlong
     * @return array
     */
    private function setupLocationFilters(string $latlong): array
    {
        $filters = [];

        //'-27.580|153.100'
        if (!preg_match('/^\-?[0-9\.]*|\-?[0-9\.]*$/', $latlong)) {
            return $filters;
        }

        $arrCoords = explode('|', $latlong);
        $filters['autocomplete']['lat'] = $arrCoords[0];
        $filters['autocomplete']['lng'] = $arrCoords[1];
        $filters['unit'] = 'km';

        return $filters;
    }

    /**
     * @param array $filters
     * @return LocationCollection
     */
    private function getLocationCollection(array $filters): LocationCollection
    {
        $collection = $this->locationCollectionFactory->create();
        $collection->addDistanceField($filters);
        $collection->setOrderByOrderField($filters);
        return $collection;
    }

    /**
     * @param array $filters
     * @return \Magento\Framework\DataObject
     */
    private function getClosestLocation(array $filters): \Magento\Framework\DataObject
    {
        $collection = $this->getLocationCollection($filters);
        return $collection->getFirstItem();
    }
}
