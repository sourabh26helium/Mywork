<?php
namespace Highgrove\StoreLocator\Controller\Storechange;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use MageWorx\Locations\Api\LocationRepositoryInterface;
use Highgrove\StoreLocator\Model\StockcheckModel;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Highgrove\StoreLocator\Helper\Stockhelper;

class Storechange extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    /** 
     * @var JsonFactory
    */
    protected JsonFactory $resultJsonFactory;

    /** 
     * @var Stockhelper
    */
    private Stockhelper $stockHelper;

    /** 
     * @var LocationRepositoryInterface
    */
    private LocationRepositoryInterface $locationRepository;

    /** 
     * @var CookieManagerInterface
    */
    private CookieManagerInterface $cookieManagerInterface;

    /** 
     * @var StockcheckModel
    */
    private StockcheckModel $stockModel;


    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        LocationRepositoryInterface $locationRepository,
        StockcheckModel $stockModel,
        CookieManagerInterface $cookieManagerInterface,
        Stockhelper $stockHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->locationRepository = $locationRepository;
        $this->stockModel = $stockModel;
        $this->cookieManagerInterface = $cookieManagerInterface;
        $this->stockHelper = $stockHelper;
        parent::__construct($context);
    }
    /**
     * Index action
     *
     * @return $this
     */
    public function execute()
    {
        $store_id = $this->getRequest()->getParam('newlocation')!=''?$this->getRequest()->getParam('newlocation'):'';
        //if there are any invalid items, it will return invalid item ids as array
        $allProductsInStock=$this->stockHelper->checkQuoteProductsInStockForStore($store_id, true);
        $result = $this->resultJsonFactory->create();
        $dataArray=Array
        (
            'allProductsInStock' => (int)(count($allProductsInStock) === 0),
            'invalidItems' => is_array($allProductsInStock) ? $allProductsInStock : []
        );
        return $result->setData($dataArray);
    }
}
