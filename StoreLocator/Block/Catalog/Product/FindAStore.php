<?php
namespace Highgrove\StoreLocator\Block\Catalog\Product;

use Highgrove\StoreLocator\ViewModel\Location as LocationViewModel;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use MageWorx\Locations\Api\LocationRepositoryInterface;
use MageWorx\Locations\Model\MsiResolver\GetSourceItemsBySku;
use MageWorx\Locations\Model\ResourceModel\Location\Collection;
use MageWorx\StoreLocator\Helper\Data;
use MageWorx\StoreLocator\Model\Source\StoresDisplayMode;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;

/**
 * Class FindAStore
 * Apologies in advance for this. MageWorx make it extremely difficult to extend/modify their code.
 */
class FindAStore extends Template
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \MageWorx\Locations\Model\ResourceModel\Location\Collection
     */
    protected $locations;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var GetSourceItemsBySku
     */
    protected $getSourceItemsBySku;

    /**
     * @var array
     */
    protected $sourceItems = [];

    /**
     * @var Configuration
     */
    protected $inventoryConfig;

    /**
     * @var \Magento\CatalogInventory\Model\Stock\StockItemRepository
     */
    protected $stockItemRepository;

    /**
     * @var \Highgrove\StoreLocator\ViewModel\Location
     */
    private $vmLocation;

    /**
     * FindAStore constructor.
     *
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
     * @param Configuration $inventoryConfig
     * @param GetSourceItemsBySku $getSourceItemsBySku
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param LocationRepositoryInterface $locationRepository
     * @param Data $helper
     * @param Template\Context $context
     * @param LocationViewModel $vmLocation
     * @param array $data
     */
    public function __construct(
        StockItemRepository $stockItemRepository,
        Configuration $inventoryConfig,
        GetSourceItemsBySku $getSourceItemsBySku,
        \Magento\Framework\Module\Manager $moduleManager,
        StoreManagerInterface $storeManager,
        LocationRepositoryInterface $locationRepository,
        Data $helper,
        Template\Context $context,
        LocationViewModel $vmLocation,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->stockItemRepository = $stockItemRepository;
        $this->inventoryConfig     = $inventoryConfig;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->moduleManager       = $moduleManager;
        $this->locationRepository  = $locationRepository;
        $this->helper              = $helper;
        $this->storeManager        = $storeManager;
        $this->vmLocation = $vmLocation;
    }

    /**
     * @param int $productId
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStockItem($productId)
    {
        return $this->stockItemRepository->get($productId);
    }

    /**
     * Prepare layout
     *
     * @return \MageWorx\StoreLocator\Block\Catalog\Product\FindAStore
     */
    protected function _prepareLayout()
    {
        if (!$this->getTemplate()) {
            switch ($this->helper->getStoresDisplayModeOnProduct()) {
                case StoresDisplayMode::DETAILED:
                    $this->setTemplate('MageWorx_StoreLocator::catalog/product/find_a_store_detailed.phtml');
                    break;
                case StoresDisplayMode::SIMPLE:
                default:
                    $this->setTemplate('MageWorx_StoreLocator::catalog/product/find_a_store.phtml');
                    break;
            }
        }

        return parent::_prepareLayout();
    }

    /**
     * @return bool
     */
    public function isLocationOnProductEnabled()
    {
        return $this->helper->isLocationOnProductEnabled();
    }

    /**
     *
     * @return Collection
     */
    public function getLocations()
    {
        if ($this->locations === null) {
            $product           = $this->getCurrentProduct();
            $filters           = $this->helper->getSearchFiltersFromSession();
            $filters['radius'] = 0;
            if ($product) {
                $locations = $this->locationRepository->getListLocationByProductIds(
                    $this->getProductId(),
                    $this->helper->getDefaultStoresAmountOnProduct(),
                    $this->helper->getDisplayStockStatus(),
                    $filters
                );
                // load source items only if MSI enabled
                $getSourceItemsBySkuInstance = $this->getSourceItemsBySku->getInstance();
                if ($getSourceItemsBySkuInstance) {
                    $sourceItems = [];
                    if ($product instanceof \Magento\Catalog\Model\Product) {
                        $sourceItems = $getSourceItemsBySkuInstance->execute((string)$product->getSku());
                    } else {
                        foreach ($product as $item) {
                            $sourceItems = array_merge(
                                $sourceItems,
                                $getSourceItemsBySkuInstance->execute((string)$item->getSku())
                            );
                        }
                    }
                    $this->sourceItems = $sourceItems;
                }
            } else {
                $locations = $this->locationRepository->getListLocationForFront();
            }
            $this->locations = $locations;
        }

        return $this->locations;
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    protected function getCurrentProduct()
    {
        //ajax saved child or grouped product in block
        if ($this->getProduct()) {
            return $this->getProduct();
        }

        // parent product or simple available from registry
        return $this->helper->getCurrentProduct();
    }

    /**
     * @return int|null
     */
    public function getProductId()
    {
        $product = $this->getCurrentProduct();
        if ($product instanceof \Magento\Catalog\Api\Data\ProductInterface) {
            return $product->getId();
        }

        //for grouped product
        return $product->getAllIds();
    }

    /**
     *
     * @return int
     */
    public function getLocationsCount()
    {
        $count   = 0;
        $product = $this->getCurrentProduct();
        if ($product) {
            $count = $this->locationRepository->getLocationCountForProduct(
                $this->getProductId(),
                false
            );
        }

        return $count;
    }

    /**
     * @return string
     */
    public function getLocationsPlaces()
    {
        $locations = $this->getLocations();
        $scale     = $this->helper->getChildForScale($this->helper->getDefaultScale());
        $places    = $this->helper->getPlacesListByScale($locations, $scale);

        return implode(', ', $places);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLocationOnProductImageUrl()
    {
        $configImage = $this->helper->getLocationOnProductImage();
        if ($configImage) {
            return $this->storeManager->getStore()->getBaseUrl('media') . 'mageworx/location_image/' . $configImage;
        }

        return $this->getViewFileUrl('MageWorx_StoreLocator::images/svg/pin.svg');
    }

    /**
     * @return bool
     */
    public function isLocationsNamesEnabled()
    {
        return $this->helper->isLocationsNamesEnabled();
    }

    /**
     * @return string
     */
    public function getLocationOnProductText()
    {
        $configText = $this->helper->getLocationOnProductText();

        return str_replace('[count]', $this->getLocationsCount(), $configText);
    }

    /**
     * @return string
     */
    public function getLocationOnProductNotAvailableText()
    {
        return $this->helper->getLocationOnProductNotAvailableText();
    }

    /**
     * @param \MageWorx\Locations\Api\Data\LocationInterface $location
     * @param string $label
     * @return string
     */
    public function getLocationPageLink($location, $label)
    {
        if ($location->getPageFullUrl() && $this->moduleManager->isEnabled('MageWorx_LocationPages')) {
            $label = '<a href="' . $this->escapeUrl($location->getPageFullUrl()) .
                '"  target="_blank">' . $label . '</a>';
        }

        return $label;
    }

    /**
     * @param \MageWorx\Locations\Api\Data\LocationInterface $location
     * @return string
     */
    public function getRouteUrl($location)
    {
        $region = $location->getRegion() == \MageWorx\Locations\Model\Source\Region::NO_REGIONS ?
            '' : $location->getRegion();

        return "//maps.google.com/maps?saddr=current+location&amp;daddr=" . $location->getAddress() .
            ", " . $location->getCity() . ", " . $region . ", " . $location->getCountry();
    }

    /**
     * @param \MageWorx\Locations\Api\Data\LocationInterface $location
     * @return int
     */
    public function isProductInStock($location)
    {
        $status = 0;

        //$sourceCode = $location->getSourceCode();
        $sourceCode = $location->getCode(); //updated from SourceCode to Code because we using default source for all stores
        if ($sourceCode) {
            foreach ($this->sourceItems as $sourceItem) {
                if ($sourceItem->getSourceCode() == $sourceCode) {
                    if ($this->inventoryConfig->getManageStock()) {
                        $status = $sourceItem->getStatus() && $sourceItem->getQuantity() > 0;
                    } else {
                        $status = $sourceItem->getStatus();
                    }
                }
            }
        } else {
            if ($this->getCurrentProduct() instanceof \Magento\Catalog\Api\Data\ProductInterface) {
                $status = $this->getCurrentProduct()->getStatus();
            } else {
                foreach ($this->getCurrentProduct() as $product) {
                    $status += $product->getStatus();
                }

                $status = (bool)$status;
            }
        }

        return $status;
    }

    /**
     * @param \MageWorx\Locations\Api\Data\LocationInterface $location
     * @return int
     */
    public function getProductQty($location)
    {
        $status = 0;
        $qty    = 0;

        $sourceCode = $location->getSourceCode();
        if ($sourceCode) {
            foreach ($this->sourceItems as $sourceItem) {
                if ($sourceItem->getSourceCode() == $sourceCode) {
                    if ($this->inventoryConfig->getManageStock()) {
                        $qty    = $sourceItem->getQuantity();
                        $status = $sourceItem->getStatus() && $sourceItem->getQuantity() > 0;
                    } else {
                        $status = $sourceItem->getStatus();
                    }
                }
            }
        } else {
            if ($this->getCurrentProduct() instanceof \Magento\Catalog\Api\Data\ProductInterface) {
                $data   = $this->getCurrentProduct()->getQuantityAndStockStatus();
                $status = $data['is_in_stock'] ?? false;
                $qty    = $data['qty'] ?? 0;
            } else {
                foreach ($this->getCurrentProduct() as $product) {
                    $status                  += $data['is_in_stock'] ?? false;
                    $qty[$product->getSku()] = $data['qty'] ?? 0;
                }

                $status = (bool)$status;
            }
        }

        return $status ? $qty : 0;
    }

    /**
     * @param \MageWorx\Locations\Api\Data\LocationInterface $location
     * @return string|string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQtyMessage($location)
    {
        $text = $this->helper->getQtyMessageOnProductText();

        return str_replace('[stock]', $this->getProductQty($location), $text);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDisplayStockStatus(): int
    {
        return (int) $this->helper->getDisplayStockStatus();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSelectOptionText()
    {
        return $this->helper->getSelectOptionText();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isDetailedStoresDisplayMode()
    {
        return $this->helper->getStoresDisplayModeOnProduct() == StoresDisplayMode::DETAILED;
    }

    /**
     * @return DataObject|null
     */
    public function getCurrentStore():? DataObject
    {
        return $this->vmLocation->getCurrentLocation();
    }
    public function getCookieStore()
    {
        return $this->vmLocation->getCookieStore();
    }
}
