<?php
namespace Highgrove\StoreLocator\ViewModel;

use Highgrove\StoreLocator\Model\CookieManagement;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use MageWorx\Locations\Api\LocationRepositoryInterface;
use MageWorx\StoreLocator\Helper\Data;

/**
 * Class Location
 */
class Location implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var CookieManagement
     */
    private $cookieManagement;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var Data
     */
    private $locationHelper;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * Location constructor.
     * @param CookieManagement $cookieManagement
     * @param LocationRepositoryInterface $locationRepository
     * @param Data $locationHelper
     * @param UrlInterface $url
     */
    public function __construct(
        CookieManagement $cookieManagement,
        LocationRepositoryInterface $locationRepository,
        Data $locationHelper,
        UrlInterface $url
    ) {
        $this->cookieManagement = $cookieManagement;
        $this->locationRepository = $locationRepository;
        $this->locationHelper = $locationHelper;
        $this->url = $url;
    }

    /**
     * @return DataObject|null
     */
    public function getCurrentLocation():? DataObject
    {
        /*
        //Commeneted this section as store finder is mostly using cookie value and this is messing the available nearest store on product page.
        $sessionLocation = $this->cookieManagement->getCustomerSessionLocation();
        if ($sessionLocation) {
            return $sessionLocation;
        }*/

        $locationId = $this->cookieManagement->getLocationCookie();
        if (!$locationId) {
            return null;
        }

        $location = $this->locationRepository->getById($locationId);
        if (!$location || !$location->getId()) {
            return null;
        }

        $this->cookieManagement->setCustomerSessionLocation($location);
        return $this->cookieManagement->getCustomerSessionLocation();
    }
    //created this function specifically to use cookie value in set my location in header as session value is not required.
    public function getCookieStore()
    {
        $locationId = $this->cookieManagement->getLocationCookie();
        if (!$locationId) {
            return null;
        }

        $location = $this->locationRepository->getById($locationId);
        if (!$location || !$location->getId()) {
            return null;
        }
        return $location;
    }

    /**
     * @return string
     */
    public function getStoreLocatorLink(): string
    {
        try {
            $path = $this->locationHelper->getLinkUrl();
            return $this->url->getUrl($path);
        } catch (\Exception $e) {
            return $this->url->getUrl();
        }
    }
}
