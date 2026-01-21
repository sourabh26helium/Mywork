<?php

namespace Highgrove\StoreLocator\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use MageWorx\Locations\Api\LocationRepositoryInterface;
use MageWorx\StoreLocator\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class Search extends \MageWorx\StoreLocator\Block\Search
{
    /**
     * @var LocationRepositoryInterface
     */
    private LocationRepositoryInterface $locationRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Data $helper
     * @param LocationRepositoryInterface $locationRepository
     * @param array $data
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Context $context,
        Data $helper,
        LocationRepositoryInterface $locationRepository,
        array $data = []
    ) {
        parent::__construct($storeManager, $context, $helper, $data);
        $this->locationRepository = $locationRepository;
    }

    /**
     * @param string $locationCode
     * @return int
     */
    public function getLocationId(string $locationCode = 'HBAL'): int
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
