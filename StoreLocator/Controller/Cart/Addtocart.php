<?php
declare(strict_types=1);
namespace Highgrove\StoreLocator\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadataFactory;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\QuantityCollector;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

class Addtocart extends Action
{

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

   /**
     * @var CookieManagerInterface
     */
    private $cookieManager;
    
    /**
     * @var PublicCookieMetadataFactory
     */
    private $cookieMetadata;

    /**
     * @var CustomerCart
     */
    private CustomerCart $cart;

    /**
     * @var QuantityCollector
     */
    private QuantityCollector $quantityCollector;

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

   /**
     * AddToCart constructor.
     * @param Context $context
     * @param CartRepositoryInterface $cartRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CookieManagerInterface $cookieManagerInterface
     * @param PublicCookieMetadataFactory $cookieMetadataFactory
     * @param CustomerCart $cart
     * @param QuantityCollector $quantityCollector
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     */
   public function __construct(
       Context $context,
       CartRepositoryInterface $cartRepository,
       ProductRepositoryInterface $productRepository,
       CookieManagerInterface $cookieManagerInterface,
       PublicCookieMetadataFactory $cookieMetadataFactory,
       CustomerCart $cart,
       QuantityCollector $quantityCollector,
       JsonFactory $resultJsonFactory,
       LoggerInterface $logger
   ) {
       $this->cartRepository = $cartRepository;
       $this->productRepository = $productRepository;
       $this->cookieManager = $cookieManagerInterface;
       $this->cookieMetadata = $cookieMetadataFactory;
       $this->cart = $cart;
       $this->quantityCollector = $quantityCollector;
       $this->resultJsonFactory = $resultJsonFactory;
       $this->logger = $logger;
       parent::__construct($context);
   }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $productSku = $this->cookieManager->getCookie('user_selected_product');
        $result = $this->resultJsonFactory->create();
        if($productSku) {
            $product = $this->productRepository->get($productSku);
            $qty = $this->cookieManager->getCookie('user_selected_qty') ?? '1';
            try {
                $this->cart->addProduct($product, ['qty' => $qty, 'product' => $product->getId()]);
                $this->cart->save();
                $quote = $this->cart->getQuote();
                $this->quantityCollector->collectItemsQtys($quote);
                $quote->collectTotals();
                $this->cartRepository->save($quote);
                $successMessages[] = __("You added %1 to your shopping cart.", $product->getSku());
                $dataArray= Array( 'success' => true );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__("Unable to add %1 to cart", $product->getSku()));
                $this->logger->error("Error add to cart: ".$e->getMessage());
                $dataArray=Array ( 'success' => false, 'errorMessage' =>  $e->getMessage());
            }
            return $result->setData($dataArray);  
        }
       
    }
    /**
     * @param string $name
     * @param string $value
     */
    private function setCookie(string $name, string $value)
    {
        $cookieMeta = $this->cookieMetadata->create();
        $cookieMeta->setPath('/');
        $cookieMeta->setDuration(3600 * 24 * 7);
        try {
            $this->cookieManager->setPublicCookie($name, $value, $cookieMeta);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
