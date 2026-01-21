<?php
namespace Highgrove\StoreLocator\Controller\Location;
use MageWorx\Locations\Api\LocationRepositoryInterface;
use MageWorx\StoreLocator\Model\Source\Layout;
use MageWorx\Locations\Model\ResourceModel\Location\Collection as LocationCollection;
class SearchLocations extends \MageWorx\StoreLocator\Controller\Location\SearchLocations
{
    /*
    ** @var LocationCollection
    */
    protected $locations;
    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getFilters($currentPage)
    {
        $filters = [];

        $radius                  = $this->getRequest()->getParam('radius');
        $filters['radius']       = $radius;
        $filters['autocomplete'] = $this->getRequest()->getParam('autocomplete');
        $type = $this->getRequest()->getParam('type');
        
        
        $filters['unit']         = $this->helper->getRadiusUnit();
        if ((empty($filters['autocomplete']['lat']) || $filters['autocomplete']['lat'] == '0')
            && (empty($filters['autocomplete']['lng']) || $filters['autocomplete']['lng'] == '0')) {
            if (
                $this->helper->getDefaultMapView() == \MageWorx\Locations\Model\Source\DefaultMapView::DEFAULT_LOCATION
            ) {
                $filters[$this->helper->getFilterBy()] = $this->helper->getDefaultPlace();
                $filters['autocomplete']                   = $this->helper->getDefaultPlaceCoordinates();
            } else {
                $geoIpCoord              = $this->helper->getCoordinatesByGeoIp();
                $filters['autocomplete'] = $geoIpCoord;
            }
        }
        if (empty($filters['autocomplete']['lat']) && empty($filters['autocomplete']['lng'])) {
            $filters[$this->helper->getFilterBy()] = $this->helper->getDefaultPlace();
            $filters['autocomplete']                   = $this->helper->getDefaultPlaceCoordinates();
        }

        $filters['city'] = $this->getRequest()->getParam('autocomplete')['city'] ?? '';
        $filters['skip_radius'] = $this->helper->skipRadiusFilter($currentPage);
        $filters['set-type'] = 'by_radius';
        if($type=='by_country') {
            $filters['radius'] = '';
            $filters['region'] = '';
            $filters['skip_radius'] = true;
            $filters['set-type'] = 'by_country';
        } else if($type=='by_region') {
            $filters['set-type'] = 'by_region';
            $filters['skip_radius'] = true;
            $filters['autocomplete']['region'] = $filters['region'] = $filters['autocomplete']['city'];
            $filters['autocomplete']['postcode']='';
        } 
        $this->customerSession->setData(LocationRepositoryInterface::LOCATOR_COORDINATES, $filters);
        return $filters;
    }



    /**
     * @return \MageWorx\StoreLocator\Block\Filter
     */
    private function prepareFilterBlock()
    {
        $block = $this->_view->getLayout()->createBlock(\MageWorx\StoreLocator\Block\Filter::class)
                             ->setTemplate('MageWorx_StoreLocator::filter_for_list.phtml');
        $block->setData('locations', $this->locations);

        return $block;
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function prepareBlock()
    {
        $currentPage   = $this->getRequest()->getParam('current_page');
        $currentLayout = $this->getLayout($currentPage);

        $this->loadLocations($currentPage);

        if ($currentLayout == Layout::FILTER_LEFT_MAP || $currentLayout == Layout::FILTER_ON_MAP) {
            $template = 'MageWorx_StoreLocator::filter.phtml';
            $block    = $this->_view->getLayout()->createBlock(\MageWorx\StoreLocator\Block\Filter::class);
        } else {
            $template = 'MageWorx_StoreLocator::list.phtml';
            $block    = $this->_view->getLayout()->createBlock(\MageWorx\StoreLocator\Block\LocationsList::class);
        }

        $block->setTemplate($template)
              ->setPlace($this->getRequest()->getParam('search_text'))
              ->setLocations($this->locations);

        $block->setData('is_checkout', strpos($currentPage, 'checkout') !== false);

        $html = $block->toHtml();
        if ($currentLayout !== Layout::FILTER_LEFT_MAP && $currentLayout !== Layout::FILTER_ON_MAP) {
            $filterBlock = $this->prepareFilterBlock();
            $html        .= '|||' . $filterBlock->toHtml();
        }

        return $html;
    }

    /**
     * @param string currentPage
     * @return \MageWorx\Locations\Model\ResourceModel\Location\Collection|string[]
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loadLocations($currentPage)
    {
        $filters = $this->getFilters($currentPage);

        switch ($currentPage) {
            case 'mageworx_store_locator_location_updatemainpage':
            case 'cms_page_view':
                $locations = $this->locationRepository->getListLocationForFront(
                    $this->helper->getStoreId(),
                    $filters
                );
                break;
            case 'mageworx_store_locator_location_updatepopupcontent':
            case 'catalog_product_view':
                $product = $this->getRequest()->getParam('current_products');
                if ($product) {
                    $locations = $this->locationRepository->getListLocationByProductIds(
                        $product,
                        null,
                        $this->helper->getDisplayStockStatus(),
                        $filters
                    );
                } else {
                    $locations = $this->locationRepository->getListLocationForFront(
                        $this->helper->getStoreId(),
                        $filters
                    );
                }
                break;
            case 'checkout_index_index':
            default:
                $locations = $this->helper->getLocationsForCurrentQuote($filters);
                break;
        }

        $this->locations = $locations;
    }
}