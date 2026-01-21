<?php
namespace Highgrove\StoreLocator\Block;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use MageWorx\Locations\Api\LocationRepositoryInterface;

class LocationsList extends \MageWorx\StoreLocator\Block\LocationsList
{
    /**
     * @var Session
     */
    private $session;
    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * LocationInfo constructor.
     *
     * @param Session $session
     * @param Context $context
     * @param LocationRepositoryInterface $locationRepository
     * @param array $data
     */
    public function __construct(
        Session $session,
        Context $context,
        LocationRepositoryInterface $locationRepository,
        array $data = []
    ) {
        $this->session = $session;
        $this->locationRepository = $locationRepository;
        parent::__construct($context, $data);
    }

    /**
     * Prepare layout
     *
     * @return \MageWorx\StoreLocator\Block\LocationsList
     */
    protected function _prepareLayout()
    {
        $this->setTemplate('MageWorx_StoreLocator::list.phtml');
        return $this;
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->session->isLoggedIn();
    }

    /**
     * @return bool
     */
    public function isCheckout()
    {
        if ($this->getIsCheckout()) {
            return true;
        }

        if ($this->getRequest()->getFullActionName() == 'paypal_express_review') {
            return true;
        }

        return strpos($this->getRequest()->getFullActionName(), 'checkout') !== false;
    }

    /**
     * @param $location
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLocationInfoForCMSHtml($location): string
    {
        $block = $this->getLayout()->createBlock(\MageWorx\StoreLocator\Block\LocationInfo::class)
                      ->setTemplate('MageWorx_StoreLocator::location_info_for_cms_list.phtml');
        $block->setData('location', $location);
        $block->setData('is_checkout', $this->getIsCheckout());

        return $block->toHtml();
    }

    /**
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl("highgrove_store_locator/ajax/ajax");
    }

    /**
     * @return string
     */
    public function getStockCheckUrl(): string
    {
        return $this->getUrl("highgrove_store_locator/stockcheck/stockcheck");
    }

    /**
     * @param string $locationCode
     * @return int
     */
    public function getLocationId(string $locationCode = 'HBUN'): int
    {
        try {
            if (!empty($locationCode)) {
                $location = $this->locationRepository->getByCode($locationCode);
                if ($locationId = $location->getEntityId()) {
                    return $locationId;
                }
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return false;
    }
    }
