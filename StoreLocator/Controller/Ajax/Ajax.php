<?php
namespace Highgrove\StoreLocator\Controller\Ajax;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use MageWorx\Locations\Api\LocationRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class Ajax extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var LocationRepositoryInterface
     */
    private LocationRepositoryInterface $locationRepository;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     *
     * @param Context                     $context
     * @param PageFactory                 $resultPageFactory
     * @param JsonFactory                 $resultJsonFactory
     * @param LocationRepositoryInterface $locationRepository
     * @param ResourceConnection          $resourceConnection
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        LocationRepositoryInterface $locationRepository,
        ResourceConnection $resourceConnection
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->locationRepository = $locationRepository;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }
    /**
     * Index action
     *
     * @return $this
     */
    public function execute()
    {
        $loc_id          = $this->getRequest()->getParam('loc_id');
        if($loc_id)
        {
            $location = $this->locationRepository->getById($loc_id);
            $placeName = $location->getData('name');
            $placePostCode = $location->getData('postcode');
            $placeAddress = $location->getData('address');
            $placephone = $location->getData('phone_number');
            $placeRegion = $location->getData('region');
            $placeUrl = $location->getData('page_full_url');
            $placeDirection="//maps.google.com/maps?saddr=current+location&daddr=".$placeAddress.', '.$placeRegion;
            $result = $this->resultJsonFactory->create();

            $sourceCode= $location->getData('code');
            $productSku = $this->getRequest()->getParam('prodSku') ?: "";

            $productSku_updated = str_replace("**", "+", $productSku ?? '');
            $status ='0';
            if($sourceCode && $productSku)
            {
                $connection = $this->resourceConnection->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
                $collection = $connection->fetchrow("select * from inventory_source_item where source_code ='".$sourceCode."' and sku ='".$productSku_updated."' and quantity >0");
                if($collection)
                    $status ='1';
                else
                    $status ='0';
            }
            $dataArray=Array
            (
                'name' => $placeName,
                'postcode' => $placePostCode,
                'address' => $placeAddress,
                'phone' => $placephone,
                'region' => $placeRegion,
                'store_url' => $placeUrl,
                'direction' => $placeDirection,
                'status' => $status
            );

            return $result->setData($dataArray);
        }
        return '';
    }
}
