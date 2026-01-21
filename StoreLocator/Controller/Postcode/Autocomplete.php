<?php
namespace Highgrove\StoreLocator\Controller\Postcode;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;
use MageWorx\Locations\Api\LocationRepositoryInterface;
use Highgrove\StoreLocator\Helper\Data as StoreLocatorHelper;

class Autocomplete extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var StoreLocatorHelper
     */
    protected $storeLocatorHelper;

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        LocationRepositoryInterface $locationRepository,
        StoreLocatorHelper $storeLocatorHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->locationRepository = $locationRepository;
        $this->storeLocatorHelper = $storeLocatorHelper;
        parent::__construct($context);
    }

    /**
     * Execute action
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $postcode = $this->getRequest()->getParam('postcode');
            
            if (empty($postcode)) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Postcode is required'
                ]);
            }

            // Step 1: Get address from Google Places API
            $addressData = $this->getAddressFromPostcode($postcode);
            
            if (!$addressData) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Could not find address for this postcode'
                ]);
            }

            // Step 2: Search for stores near this address
            $stores = $this->searchStoresNearAddress($addressData);
            
            if (empty($stores)) {
                return $result->setData([
                    'success' => false,
                    'message' => 'No stores found near this location'
                ]);
            }

            // Step 3: Select the first store as default
            $firstStore = reset($stores);
            $locationId = $firstStore['id'];
            
            // Set the location cookie
            $this->setLocationCookie($locationId);
            
            // Also set other relevant cookies
            $this->setAdditionalCookies($addressData, $firstStore);

            return $result->setData([
                'success' => true,
                'location_id' => $locationId,
                'store_name' => $firstStore['name'],
                'postcode' => $firstStore['postcode'],
                'address' => $firstStore['address'],
                'stores' => $stores
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get address from postcode using Google Places API
     */
    protected function getAddressFromPostcode($postcode)
    {
        $googleApiKey = $this->scopeConfig->getValue('store_locator/google/api_key');
        
        if (!$googleApiKey) {
            throw new \Exception('Google API key is not configured');
        }

        // Build the Google Places API URL
        $url = 'https://maps.googleapis.com/maps/api/place/js/AutocompletionService.GetPredictions';
        
        $params = [
            '1s' => $postcode,
            '4s' => 'en',
            '7s' => 'country:au',
            '9s' => '(regions)',
            '15e3' => '',
            '20s' => uniqid(),
            '21m1' => '',
            '2e1' => '',
            'r_url' => $this->storeLocatorHelper->getCurrentUrl(),
            'callback' => '_xdc_._' . uniqid(),
            'key' => $googleApiKey,
            'token' => rand(100000, 999999)
        ];

        $queryString = http_build_query($params);
        $fullUrl = $url . '?' . $queryString;

        // Set headers similar to your curl example
        $headers = [
            'accept' => '*/*',
            'accept-language' => 'en-GB,en-US;q=0.9,en;q=0.8',
            'referer' => $this->storeLocatorHelper->getBaseUrl(),
            'sec-ch-ua' => '"Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"macOS"',
            'sec-fetch-dest' => 'script',
            'sec-fetch-mode' => 'no-cors',
            'sec-fetch-site' => 'cross-site',
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            'x-requested-with' => 'XMLHttpRequest'
        ];

        $this->curl->setHeaders($headers);
        $this->curl->get($fullUrl);
        
        $response = $this->curl->getBody();
        
        // Parse the JSONP response
        $response = preg_replace('/^\/\*\*\/\s*/', '', $response);
        $response = preg_replace('/_xdc_\.[a-zA-Z0-9_]+ && _xdc_\.[a-zA-Z0-9_]+\(/', '', $response);
        $response = rtrim($response, ')');
        
        $data = json_decode($response, true);
        
        if (isset($data[1][0])) {
            $prediction = $data[1][0];
            
            // Extract address components
            $fullAddress = $prediction[0];
            $parts = $prediction[5];
            
            $addressData = [
                'full_address' => $fullAddress,
                'city' => '',
                'region' => '',
                'postcode' => $postcode,
                'country' => 'Australia',
                'country_id' => 'AU',
                'lat' => null,
                'lng' => null
            ];
            
            foreach ($parts as $part) {
                if (strpos($part[0], 'Forest Hill') !== false) {
                    $addressData['city'] = 'Forest Hill';
                } elseif ($part[0] === 'VIC') {
                    $addressData['region'] = 'Victoria';
                } elseif ($part[0] === 'Australia') {
                    $addressData['country'] = 'Australia';
                    $addressData['country_id'] = 'AU';
                }
            }
            
            // You might want to geocode to get lat/lng
            $coordinates = $this->geocodeAddress($fullAddress);
            if ($coordinates) {
                $addressData['lat'] = $coordinates['lat'];
                $addressData['lng'] = $coordinates['lng'];
            }
            
            return $addressData;
        }
        
        return null;
    }

    /**
     * Geocode address to get coordinates
     */
    protected function geocodeAddress($address)
    {
        $googleApiKey = $this->scopeConfig->getValue('store_locator/google/api_key');
        $url = 'https://maps.googleapis.com/maps/api/geocode/json';
        
        $params = [
            'address' => urlencode($address),
            'key' => $googleApiKey
        ];
        
        $queryString = http_build_query($params);
        $fullUrl = $url . '?' . $queryString;
        
        $this->curl->get($fullUrl);
        $response = json_decode($this->curl->getBody(), true);
        
        if ($response['status'] === 'OK' && isset($response['results'][0]['geometry']['location'])) {
            return $response['results'][0]['geometry']['location'];
        }
        
        return null;
    }

    /**
     * Search stores near address using your existing searchLocations endpoint
     */
    protected function searchStoresNearAddress($addressData)
    {
        $baseUrl = $this->storeLocatorHelper->getBaseUrl();
        $timestamp = time() . rand(100, 999);
        
        $url = $baseUrl . 'store_locator/location/searchLocations';
        $params = [
            '_' => $timestamp,
            'type' => 'by_radius',
            'current_page' => 'catalog_product_view',
            'current_products' => '',
            'radius' => '1000',
            'region' => ''
        ];
        
        $queryString = http_build_query($params);
        $fullUrl = $url . '?' . $queryString;
        
        // Prepare POST data
        $postData = [
            'search_text' => $addressData['full_address'],
            'current_location_clicked' => '0',
            'viewport_custom' => '',
            'autocomplete[lat]' => $addressData['lat'] ?: '-37.8239091',
            'autocomplete[lng]' => $addressData['lng'] ?: '145.1762197',
            'autocomplete[small_city]' => '',
            'autocomplete[city]' => $addressData['city'],
            'autocomplete[region]' => $addressData['region'],
            'autocomplete[postcode]' => $addressData['postcode'],
            'autocomplete[country_id]' => $addressData['country_id'],
            'search_radius' => '1000'
        ];
        
        $headers = [
            'accept' => 'text/html, */*; q=0.01',
            'accept-language' => 'en-GB,en-US;q=0.9,en;q=0.8',
            'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'origin' => $baseUrl,
            'referer' => $baseUrl,
            'sec-ch-ua' => '"Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"macOS"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            'x-requested-with' => 'XMLHttpRequest'
        ];
        
        $this->curl->setHeaders($headers);
        $this->curl->post($fullUrl, $postData);
        
        $response = $this->curl->getBody();
        
        // Parse the HTML response to extract store information
        return $this->parseStoresFromHtml($response);
    }

    /**
     * Parse stores from HTML response
     */
    protected function parseStoresFromHtml($html)
    {
        $stores = [];
        
        // Extract the stores list from the HTML
        if (preg_match('/<ul id="store_all_pages"[^>]*>(.*?)<\/ul>/s', $html, $matches)) {
            $storesHtml = $matches[1];
            
            // Parse each store item
            $pattern = '/<li class="stores-list-item location-info-block_(\d+)">(.*?)<\/li>/s';
            preg_match_all($pattern, $storesHtml, $storeMatches, PREG_SET_ORDER);
            
            foreach ($storeMatches as $match) {
                $storeId = $match[1];
                $storeHtml = $match[2];
                
                // Extract store name
                $namePattern = '/<span class="mw-sl__store__info__name">(.*?)<\/span>/s';
                preg_match($namePattern, $storeHtml, $nameMatch);
                $name = $nameMatch[1] ?? '';
                
                // Extract address and postcode
                $addressPattern = '/<br\/>\s*(.*?),<br\/>/s';
                preg_match($addressPattern, $storeHtml, $addressMatch);
                $address = $addressMatch[1] ?? '';
                
                // Extract postcode
                $postcodePattern = '/<span class="force-font-weight-normal">\s*.*?,\s*(\d+)\s*<\/span>/s';
                preg_match($postcodePattern, $storeHtml, $postcodeMatch);
                $postcode = $postcodeMatch[1] ?? '';
                
                // Check if this store is active (has active class)
                $isActive = strpos($storeHtml, 'hg-sl__store__select active') !== false;
                
                $stores[] = [
                    'id' => $storeId,
                    'name' => trim($name),
                    'address' => trim($address),
                    'postcode' => trim($postcode),
                    'is_active' => $isActive
                ];
            }
        }
        
        return $stores;
    }

    /**
     * Set location cookie
     */
    protected function setLocationCookie($locationId)
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(604800) // 7 days
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain())
            ->setHttpOnly(false);
        
        $this->cookieManager->setPublicCookie(
            'mageworx_location_id',
            $locationId,
            $metadata
        );
    }

    /**
     * Set additional cookies for address data
     */
    protected function setAdditionalCookies($addressData, $store)
    {
        // Set user_postcode cookie
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(604800)
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain())
            ->setHttpOnly(false);
        
        $this->cookieManager->setPublicCookie(
            'user_postcode',
            $addressData['postcode'],
            $metadata
        );
        
        // Set user_city cookie
        $this->cookieManager->setPublicCookie(
            'user_city',
            $addressData['city'],
            $metadata
        );
        
        // Set manual location cookie
        $this->cookieManager->setPublicCookie(
            'mage_manual_location',
            $store['id'],
            $metadata
        );
    }
}