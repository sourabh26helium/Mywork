<?php
namespace Highgrove\StoreLocator\Model;

use Magento\Customer\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use MageWorx\Locations\Api\Data\LocationInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CookieManagement
 */
class CookieManagement
{
    const LOCATION_COOKIE = 'mageworx_location_id';
    const INVENTORY_LOCATION_COOKIE = 'inventory-source';

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var PublicCookieMetadataFactory
     */
    private $cookieMetadata;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CookieManagement constructor.
     * @param CookieManagerInterface $cookieManager
     * @param PublicCookieMetadataFactory $cookieMetadataFactory
     * @param Session $customerSession
     * @param DataObjectFactory $dataObjectFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        PublicCookieMetadataFactory $cookieMetadataFactory,
        Session $customerSession,
        DataObjectFactory $dataObjectFactory,
        LoggerInterface $logger
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadata = $cookieMetadataFactory;
        $this->customerSession = $customerSession;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->logger = $logger;
    }

    /**
     * @param string $locationId
     */
    public function setLocationCookie(string $locationId)
    {
        try {
            $this->setCookie(self::LOCATION_COOKIE, $locationId);
        } catch (\Exception $e) {
            $this->logger->error(__("Error setting location cookie"));
        }
    }

    /**
     * @return string|null
     */
    public function getLocationCookie():? string
    {
        return $this->cookieManager->getCookie(self::LOCATION_COOKIE);
    }

    /**
     * @return string|null
     */
    public function getInventoryLocationCookie():? string
    {
        return $this->cookieManager->getCookie(self::INVENTORY_LOCATION_COOKIE);
    }

    /**
     * @param string $name
     * @param string $value
     */
    private function setCookie(string $name, string $value)
    {
        $cookieMeta = $this->cookieMetadata->create();
        $cookieMeta->setPath('/');
        $cookieMeta->setDuration(3600 * 24 * 7);
        try {
            $this->cookieManager->setPublicCookie($name, $value, $cookieMeta);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param LocationInterface $location
     */
    public function setCustomerSessionLocation(LocationInterface $location)
    {
        $dataObject = $this->dataObjectFactory->create();
        $dataObject->setData($location->getData());
        $this->customerSession->setLocation($dataObject);
    }

    /**
     * @return DataObject|null
     */
    public function getCustomerSessionLocation():? DataObject
    {
        return $this->customerSession->getLocation();
    }
}
