<?php
namespace Highgrove\StoreLocator\Helper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageWorx\Locations\Api\Data\LocationInterface;
use MageWorx\StoreLocator\Model\Source\Scale;
use Magento\Store\Model\ScopeInterface;

class Data extends \MageWorx\StoreLocator\Helper\Data
{
    const XML_PATH_FORCE_POPUP = 'syncerrorreport/general/enable_force_popup';
    const XML_PATH_DEFAULT_STORE = 'syncerrorreport/general/default_store_id';

    /**
     * @param array $filters
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getLocationsForCheckout(array $filters = [])
    {
        $ids = [];
        foreach ($this->getQuote()->getAllItems() as $item) {
            if ($item->getProductType() !== Configurable::TYPE_CODE) {
                $ids[] = $item->getProductId();
            }
        }

        return $this->locationRepository->getListLocationByProductIdsForCheckout(
            array_unique($ids),
            null,
            $this->isPossibleToOrderOutOfStockItem(),
            $filters
        );
    }

    /**
     * @param LocationInterface $location
     * @param string $scale
     * @return string
     */
    public function getLocationPlaceIdByScale(LocationInterface $location, string $scale): string
    {
        switch ($scale) {
            case Scale::REGION:
                $scaleId = self::SCALE_REGION;
                break;
            case Scale::STORE:
            case Scale::CITY:
                $scaleId = self::SCALE_CITY;
                break;
            case Scale::WORLD:
            case Scale::COUNTRY:
                $scaleId = self::SCALE_COUNTRY;
                break;
            default:
                $scaleId = self::SCALE_CITY;
        }
        if(is_string($location->getLocationPagePath())) {
            $placeIds = explode('/', $location->getLocationPagePath());
        } else {
            $placeIds = $location->getLocationPagePath();
        }
        return $placeIds[$scaleId] ?? '0';
    }

    public function isForcePopupEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FORCE_POPUP,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getDefaultStoreCode()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_STORE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param string $code
     * @return string|null
     */
    public function prepareCode($code)
    {
        $code = str_replace(' ', '_', $code);

        return preg_replace('/[^A-Za-z0-9\_]/', '', $code);
    }
}
