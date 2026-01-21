<?php
namespace Highgrove\StoreLocator\Controller\Location;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use MageWorx\Locations\Model\LocationRepository;
use MageWorx\Locations\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;

/**
 * Class Selected
 */
class Selected extends Action
{
    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * @var LocationCollectionFactory
     */
    private $locationCollectionFactory;

    /**
     * Selected constructor.
     * @param Context $context
     * @param LocationRepository $locationRepository
     * @param LocationCollectionFactory $locationCollectionFactory
     */
    public function __construct(
        Context $context,
        LocationRepository $locationRepository,
        LocationCollectionFactory $locationCollectionFactory
    ) {
        $this->locationRepository = $locationRepository;
        $this->locationCollectionFactory = $locationCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $locationId = $this->getRequest()->getParam('location');
        if (!$locationId) {
            return '';
        }

        $location = $this->locationRepository->getById($locationId);
        $locationCollection = $this->getLocationCollection($location);

        $block = $this->_view->getLayout()->createBlock(\MageWorx\StoreLocator\Block\LocationsList::class);
        $block->setLocations($locationCollection);
        $block->setIsCheckout(true);

        $this->getResponse()->setHeader('Content-Type', 'text/html', true);
        return $this->getResponse()->setContent($block->toHtml());
    }

    /**
     * @param \MageWorx\Locations\Api\Data\LocationInterface $location
     * @return \MageWorx\Locations\Model\ResourceModel\Location\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getLocationCollection(\MageWorx\Locations\Api\Data\LocationInterface $location)
    {
        $filters = [
            'type' => 'by_radius',
            'radius' => '',
            'unit' => 'km',
            'autocomplete' => [
                'lat' => $location->getLatitude(),
                'lng' => $location->getLongitude()
            ]
        ];
        $locationCollection = $this->locationCollectionFactory->create();
        $locationCollection->addDistanceField($filters);
        $locationCollection->addSearchFilters($filters);
        $locationCollection->setOrderByOrderField($filters);
        $locationCollection->setLimit(5);
        $locationCollection->addAttributeToSelect(
            [
                'address',
                'city',
                'code',
                'country_id',
                'name',
                'phone_number',
                'postcode',
                'region',
            ]
        );
        return $locationCollection;
    }
}
