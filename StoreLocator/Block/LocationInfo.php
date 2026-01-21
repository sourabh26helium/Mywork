<?php
namespace Highgrove\StoreLocator\Block;

use Magento\CatalogInventory\Model\Configuration;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageWorx\Locations\Api\Data\LocationInterface;
use MageWorx\StoreLocator\Helper\Data;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Framework\Module\Manager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template\Context;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Filesystem\Driver\File;
use MageWorx\Locations\Model\ResourceModel\Location;
use Magento\Framework\Registry;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Item;

class LocationInfo extends \MageWorx\StoreLocator\Block\LocationInfo
{
    const OUT_OF_STOCK = 0;
    const IN_STOCK = 1;
    
    /**
     * @var Session
     */
    private $session;
    
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;
    
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    
    protected $regionFactory;
    
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;
    
    /**
     * @var Registry
     */
    private $registry;
    
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    
    /**
     * @var array
     */
    private $quoteProducts = [];

    /**
     * @param RegionFactory $regionFactory
     * @param Session $session
     * @param Configuration $inventoryConfig
     * @param Manager $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CheckoutSession $checkoutSession
     * @param Data $helper
     * @param Context $context
     * @param File $driver
     * @param Location $locationResource
     * @param CookieManagerInterface $cookieManager
     * @param Registry $registry
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        RegionFactory $regionFactory,
        Session $session,
        Configuration $inventoryConfig,
        Manager $moduleManager,
        StoreManagerInterface $storeManager,
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CheckoutSession $checkoutSession,
        Data $helper,
        Context $context,
        File $driver,
        Location $locationResource,
        CookieManagerInterface $cookieManager,
        Registry $registry,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct(
            $regionFactory,
            $driver,
            $locationResource,
            $inventoryConfig,
            $moduleManager,
            $storeManager,
            $helper,
            $context,
            $data
        );

        $this->session = $session;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->checkoutSession = $checkoutSession;
        $this->cookieManager = $cookieManager;
        $this->registry = $registry;
        $this->productRepository = $productRepository;
    }

    /**
     * Check if current product is in stock at given store
     *
     * @param LocationInterface $location
     * @return int
     */
    public function isProductInStock(LocationInterface $location)
    {
        $locationCode = $location->getCode();
        $products = $this->getProductsToCheck();
        
        if (empty($products)) {
            return self::OUT_OF_STOCK;
        }

        foreach ($products as $product) {
            $sku = $product->getSku();
            
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('sku', $sku)
                ->addFilter('source_code', $locationCode)
                ->create();

            $sourceItems = $this->sourceItemRepository
                ->getList($searchCriteria)
                ->getItems();

            foreach ($sourceItems as $item) {
                if ((float)$item->getQuantity() > 0 && (int)$item->getStatus() === 1) {
                    continue 2;
                }
            }
            
            return self::OUT_OF_STOCK;
        }
        
        return self::IN_STOCK;
    }
    
    /**
     * Get products to check stock for
     *
     * @return array
     */
    private function getProductsToCheck()
    {
        $product = $this->registry->registry('product');
        if ($product) {
            return [$product];
        }
        
        return $this->getProductsFromQuote();
    }
    
    /**
     * Get products from current quote
     *
     * @return array
     */
    private function getProductsFromQuote()
    {
        if (!empty($this->quoteProducts)) {
            return $this->quoteProducts;
        }
        
        try {
            $quote = $this->checkoutSession->getQuote();
            if ($quote && $quote->getId()) {
                foreach ($quote->getAllVisibleItems() as $item) {
                    try {
                        $product = $this->productRepository->getById(
                            $item->getProductId(),
                            false,
                            $this->_storeManager->getStore()->getId()
                        );
                        
                        if ($product && $product->getId()) {
                            $product->setQty($item->getQty());
                            $this->quoteProducts[] = $product;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error if needed
        }
        
        return $this->quoteProducts;
    }

    /**
     * @return string|null
     */
    public function getSelectedStore()
    {
        return $this->cookieManager->getCookie('mageworx_location_id');
    }

    /**
     * @return null
     */
    protected function getCacheLifetime()
    {
        return null;
    }
}