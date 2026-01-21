<?php
namespace Highgrove\StoreLocator\Helper;

use Highgrove\StoreLocator\Model\StockcheckModel;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\CookieManagerInterface;
use MageWorx\Locations\Api\LocationRepositoryInterface;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Checkout\Model\Cart;

class Stockhelper extends AbstractHelper
{
    /**
     * @var StockcheckModel
     */
    private StockcheckModel $stockModel;
    /**
     * @var CookieManagerInterface
     */
    private CookieManagerInterface $cookieManagerInterface;
    /**
     * @var LocationRepositoryInterface
     */
    private LocationRepositoryInterface $locationRepository;
    /**
     * @var Cart
     */
    private Cart $cart;

    /**
     * @param Context $context
     * @param StockcheckModel $stockModel
     * @param CookieManagerInterface $cookieManagerInterface
     * @param LocationRepositoryInterface $locationRepository
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        StockcheckModel $stockModel,
        CookieManagerInterface $cookieManagerInterface,
        LocationRepositoryInterface $locationRepository,
        Cart $cart
    ) {
        parent::__construct($context);
        $this->stockModel = $stockModel;
        $this->cookieManagerInterface = $cookieManagerInterface;
        $this->locationRepository = $locationRepository;
        $this->cart = $cart;
    }

    /**
     * @param string $sku
     * @param int $qty
     * @param string $storeId store location id
     * @return int
     */
    public function checkProductInStockForStore(string $sku, int $qty = 0, string $storeId = ''): int
    {
        $cookieValue = empty($storeId)
            ? $this->cookieManagerInterface->getCookie('mageworx_location_id')
            : $storeId;

        //If the quantity passed is greater than zero,
        //subtract one to check if the stock has the exact same quantity
        $qty = $qty === 0 ? $qty: --$qty;

        if ($cookieValue) {
            $location = $this->locationRepository->getById($cookieValue);
            $storeCode = $location->getData('code');
            $productInStoreStock = 0;
            $result = $this->stockModel->getSourceItemDetailBySKU($sku, $storeCode);
			$qty_h001 = $this->stockModel->getProductQuantityBySource($sku, 'H001');
			$qty_h002 = $this->stockModel->getProductQuantityBySource($sku, 'H002');
            foreach ($result as $sourceItem) {
                if ($sourceItem->getData('source_code') == $storeCode) {
                    if ((int)$sourceItem->getData('quantity') > $qty) {
                        $productInStoreStock = 1;
                    } else if ($qty_h001>$qty  ||  $qty_h002>$qty) {
						$productInStoreStock = 1;
					}
				}
            }
        } else {
            $productInStoreStock = 1;
        }

        return $productInStoreStock;
    }

    /**
     * @param string $storeId
     * @param bool $showItems
     * @return array|int
     */
    public function checkQuoteProductsInStockForStore(string $storeId = '', bool $showItems = false)
    {
        $items = $this->cart->getQuote()->getAllItems();
        $allInStock = 1;
        $invalidItems = [];

        foreach($items as $item) {
            $productInStoreStock = $this->checkProductInStockForStore($item->getSku(), $item->getQty(), $storeId);
            if($productInStoreStock === 0) {
                $allInStock = 0;
                $invalidItems[] = $item->getItemId();
            }
        }

        return $showItems ? $invalidItems : $allInStock;
    }
}
