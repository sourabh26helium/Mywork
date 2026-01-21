<?php
namespace Highgrove\StoreLocator\Controller\Stockcheck;

use Highgrove\StoreLocator\Model\StockcheckModel;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use MageWorx\Locations\Api\LocationRepositoryInterface;

class Stockcheck extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var StockcheckModel
     */
    private $stockModel;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LocationRepositoryInterface $locationRepository
     * @param StockcheckModel $stockModel
     * @param CookieManagerInterface $cookieManagerInterface
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LocationRepositoryInterface $locationRepository,
        StockcheckModel $stockModel,
        CookieManagerInterface $cookieManagerInterface
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->locationRepository = $locationRepository;
        $this->stockModel = $stockModel;
        $this->cookieManager = $cookieManagerInterface;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $sku = $this->getRequest()->getParam('sku');
        $productSku_updated = str_replace("**", "+", $sku);

        $stockSources = $this->stockModel->getSourceItemDetailBySKU($productSku_updated);
        $cookieValue = $this->cookieManager->getCookie('mageworx_location_id');
        $productInStock = 0;

        if ($cookieValue) {
            $location = $this->locationRepository->getById($cookieValue);
            $store_code = $location->getData('code');

            foreach ($stockSources as $item) {
                if ($item->getData('source_code') == $store_code) {
                    if ($item->getData('quantity') > 0) {
                        $productInStock = 1;
                    }
                }
            }
        }

        $result = $this->resultJsonFactory->create();
        $dataArray = [
            'productInStock' => $productInStock
        ];

        $result->setData($dataArray);
        return $result;
    }
}
