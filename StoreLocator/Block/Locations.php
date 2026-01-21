<?php
namespace Highgrove\StoreLocator\Block;

use MageWorx\StoreLocator\Model\Source\Layout;
class Locations extends \MageWorx\StoreLocator\Block\Locations
{
    protected function _prepareLayout()
    {
        if (!$this->helper->canShowMap()) {
            $this->setTemplate('MageWorx_StoreLocator::locations/list_without_map.phtml');
        } else {
            switch ($this->getRequest()->getFullActionName()) {
                case 'mageworx_store_locator_location_updatepopupcontent':
                case 'catalog_product_view':
                    $layoutType = $this->helper->getPopupLayout();
                    break;
                case 'mageworx_store_locator_location_updatemainpage':
                case 'cms_page_view':
                    $layoutType = $this->helper->getLocationsPageLayout();
                    break;
                case 'checkout_index_index':
                default:
                    $layoutType = $this->helper->getCheckoutLayout();
                    break;
            }

            switch ($layoutType) {
                case Layout::FILTER_ON_MAP:
                    $this->setTemplate('MageWorx_StoreLocator::locations/filter_on_map.phtml');
                    break;
                case Layout::FILTER_LEFT_MAP:
                    $this->setTemplate('MageWorx_StoreLocator::locations/filter_left_map.phtml');
                    break;
                case Layout::LIST_AFTER_MAP:
                    $this->setTemplate('MageWorx_StoreLocator::locations/list_after_map.phtml');
                    break;
                case Layout::LIST_BEFORE_MAP:
                    $this->setTemplate('MageWorx_StoreLocator::locations/list_before_map.phtml');
                    break;
                case Layout::LIST_WITHOUT_MAP:
                    $this->setTemplate('MageWorx_StoreLocator::locations/list_without_map.phtml');
                    break;
                default:
                    $this->setTemplate('MageWorx_StoreLocator::locations/list_without_map.phtml');
                    break;
            }
        }

        return $this;
    }
    public function getLocations()
    {
        if ($this->locations === null) {
            $filters = $this->helper->getSearchFiltersFromSession();
            //forcefully load data as per selected store in cookie (overwrite session value) for all pages except stores
            //---Start Here -----
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $vmLocation = $objectManager->create('\Highgrove\StoreLocator\ViewModel\Location');
            $cookieDetails = $vmLocation->getCookieStore();
            if($cookieDetails && array_key_exists('set-type', $filters) && $filters['set-type'] == 'by_radius')
            {
                $filters['type'] = 'by_radius';
                $filters['radius'] = '';
                $filters['city'] = $cookieDetails->getData('city');
                $filters['unit'] = 'km';
                $filters['autocomplete']['lat'] = $cookieDetails->getData('latitude');
                $filters['autocomplete']['lng'] = $cookieDetails->getData('longitude');
                $filters['autocomplete']['city'] = $cookieDetails->getData('city');
                $filters['autocomplete']['region'] = $cookieDetails->getData('region');
                $filters['autocomplete']['postcode'] = $cookieDetails->getData('postcode');
                $filters['autocomplete']['country_id'] = $cookieDetails->getData('country_id');
            }
            //---Ends Here -----
            if($this->getRequest()->getFullActionName()=='mageworx_locationpages_locationList_view')
            {
                $filters['type'] = 'by_country';
                $filters['country'] = 'AU';
                $filters['region'] = '';
                $filters['city'] = '';
                $filters['autocomplete']['lat'] = '';
            }

            //echo '<pre>getLocations';print_r($filters);die;
            switch ($this->getRequest()->getFullActionName()) {
                case 'mageworx_store_locator_location_updatepopupcontent':
                case 'catalog_product_view':
                    $product = $this->getCurrentProduct();
                    if ($product) {
                        $locations = $this->locationRepository->getListLocationByProductIds(
                            $this->getCurrentProductId(),
                            null,
                            $this->helper->getDisplayStockStatus(),
                            $filters
                        );
                    } else {
                        $locations = $this->locationRepository->getListLocationForFront(
                            $this->getCurrentStoreId(),
                            $filters
                        );
                    }
                    break;
                case 'mageworx_store_locator_location_updatemainpage':
                case 'cms_page_view':
                    $locations = $this->locationRepository->getListLocationForFront(
                        $this->getCurrentStoreId(),
                        $filters
                    );
                    break;
                case 'checkout_index_index':
                default:
                    $locations = $this->locationRepository->getListLocationForFront(
                            $this->helper->getStoreId(),
                            $filters
                        );
                    break;
            }

            $this->locations = $locations;
        }
        return $this->locations;
    }
    public function getMapLocations()
    {
        if ($this->locations === null) {
            $filters = $this->helper->getSearchFiltersFromSession();
            $filters['type'] = 'by_country';
            $filters['country'] = 'AU';
            $filters['region'] = '';
            $filters['city'] = '';
            $filters['autocomplete']['lat'] = '';
            //echo '<pre>getMapLocations';print_r($filters);die;
            $locations = $this->locationRepository->getListLocationForFront(
                            $this->helper->getStoreId(),
                            $filters
                        );

            $this->locations = $locations;
        }
        return $this->locations;
    }
    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLocationsFilterHtmlCMS()
    {
        $block = $this->getLayout()->createBlock(\MageWorx\StoreLocator\Block\Filter::class)
                      ->setTemplate('MageWorx_StoreLocator::filter_store_locator.phtml');
        $block->setData('locations', $this->getLocations());

        return $block->toHtml();
    }
}
