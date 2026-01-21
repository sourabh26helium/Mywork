<?php
namespace Highgrove\StoreLocator\Model\ResourceModel;

use Highgrove\StoreLocator\Api\Data\StoreLocationGroupInterface;
use Highgrove\StoreLocator\Exception\RestException;
use Highgrove\StoreLocator\Model\ResourceModel\StoreLocationGroup as StoreLocationGroupResource;
use Highgrove\StoreLocator\Model\ResourceModel\StoreLocationGroup\CollectionFactory;
use Highgrove\StoreLocator\Model\StoreLocationGroupFactory;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;

/**
 * Class StoreLocationGroupRepository
 */
class StoreLocationGroupRepository implements \Highgrove\StoreLocator\Api\StoreLocationGroupRepositoryInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var StoreLocationGroupResource
     */
    private $storeLocationGroupResource;

    /**
     * @var StoreLocationGroupFactory
     */
    private $storeLocationGroupFactory;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * StoreLocationGroupRepository constructor.
     * @param CollectionFactory $collectionFactory
     * @param StoreLocationGroupResource $storeLocationGroupResource
     * @param StoreLocationGroupFactory $storeLocationGroupFactory
     * @param Request $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreLocationGroupResource $storeLocationGroupResource,
        StoreLocationGroupFactory $storeLocationGroupFactory,
        Request $request,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeLocationGroupResource = $storeLocationGroupResource;
        $this->storeLocationGroupFactory = $storeLocationGroupFactory;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getStoreLocationGroupsByLocationCode(string $locationCode):? array
    {
        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter(StoreLocationGroupInterface::LOCATION_CODE, $locationCode);
            return $collection->getItems();
        } catch (\Exception $e) {
            $this->logger->critical(__("Unable to get store location groups: %1", $e->getMessage()));
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function deleteStoreLocationGroupsByLocationCode(string $locationCode)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(StoreLocationGroupInterface::LOCATION_CODE, $locationCode);

        try {
            if ($collection->getItems() && count($collection->getItems())) {
                $collection->walk('delete');
            }
        } catch (\Exception $e) {
            $this->logger->critical(__("Unable to delete store location group: %1", $e->getMessage()));
            throw new RestException(__("Error deleting store location group for %1", $locationCode), 500);
        }
    }

    /**
     * @inheritDoc
     */
    public function save(StoreLocationGroupInterface $storeLocationGroup)
    {
        try {
            $this->storeLocationGroupResource->save($storeLocationGroup);
        } catch (\Exception $e) {
            $this->logger->critical(__("Unable to save store location group: %1", $e->getMessage()));
            throw new RestException(__("Error saving store location %1", $storeLocationGroup->getLocationCode()), 500);
        }
    }

    /**
     * @inheritDoc
     */
    public function saveStoreLocationGroups()
    {
        $postData = $this->request->getBodyParams();
        foreach ($postData as $data) {
            $locationCode = $data['storeRef'];
            $locationLinks = explode(',', $data['affiliates']);

            $this->deleteStoreLocationGroupsByLocationCode($locationCode);

            foreach ($locationLinks as $locationLink) {
                if (!$locationLink) {
                    continue;
                }

                $storeLocationGroup = $this->storeLocationGroupFactory->create();
                $storeLocationGroup->setLocationCode($locationCode);
                $storeLocationGroup->setLocationLink($locationLink);
                $this->save($storeLocationGroup);
            }
        }
    }
}
