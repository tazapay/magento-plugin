<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Controller\Index;

use Magento\Framework\App\Action\Context as Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\HistoryFactory;

class Auth extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected $curlFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    
    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $_currency;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $_quoteRepository;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Sales\Model\Service\OrderService
     */
    protected $orderService;

    /**
     * @var \Tz\TazaPay\Logger\Logger
     */
    protected $_logger;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var HistoryFactory
     */
    protected $orderHistoryFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $_json;

    /**
     * Tazapay auth
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Tz\TazaPay\Helper\Data $helper
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Magento\Quote\Model\QuoteFactory $quote
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Tz\TazaPay\Logger\Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param HistoryFactory $orderHistoryFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Tz\TazaPay\Helper\Data $helper,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Tz\TazaPay\Logger\Logger $logger,
        OrderRepositoryInterface $orderRepository,
        HistoryFactory $orderHistoryFactory
    ) {
        $this->helper = $helper;
        $this->curlFactory = $curlFactory;
        $this->jsonHelper = $jsonHelper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_currency = $currency;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->_quoteRepository = $quoteRepository;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->orderService = $orderService;
        $this->_json = $json;
        $this->_logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderHistoryFactory = $orderHistoryFactory;
        return parent::__construct($context);
    }

    /**
     * Tazapay execute
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $isEnable = $this->helper->isEnabled();

        // custom log
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/tazapay_custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        if ($isEnable == 1) {
            $environment = $this->helper->getEnvironment();
            if ($environment == "sandbox") {
                $apiKey = $this->helper->getSandboxApiKey();
                $apiSecretKey = $this->helper->getSandboxApiSecretKey();
                $apiUrl = $this->helper->getSandboxApiUrl();
                $currency= $this->getCurrentCurrencyCode();
            } else {
                $apiKey = $this->helper->getProductionApiKey();
                $apiSecretKey = $this->helper->getProductionApiSecretKey();
                $apiUrl = $this->helper->getProductionApiUrl();
                $currency= $this->getCurrentCurrencyCode();
            }
            $storeName = $this->getStoreName();
            // Get Base Url
            $baseUrl = $this->getBaseUrl();
            $isSupportedCurrency = false;

            // Get quote
            $quote = $this->_checkoutSession->getQuote();
            // Get shipping address
            $getShippingAddressData = $quote->getShippingAddress()->getData();
            // Get billling address
            $getBillingAddress = $quote->getBillingAddress();
            // Defines parameters
            $firstName = $getBillingAddress->getFirstName();
            $lastName = $getBillingAddress->getLastName();
            $customerName = $firstName.' '.$lastName;
            $customerEmail = $getBillingAddress->getEmail();
            $countryCode = $getBillingAddress->getCountryId();
            
            $dialNumber = $this->helper->getPhoneCode($countryCode);
            $telephone = $getBillingAddress->getTelephone();
            $grandTotal = $quote->getGrandTotal();
            /*
            * Before redirect placed order in magento
            */
            $customerEmail = $getBillingAddress->getEmail();
            $websiteId = $this->getWebsiteId();
            // Check email is not exist
            $isEmailNotExists = $this->customerAccountManagement->isEmailAvailable($customerEmail, $websiteId);

            $tazapay_seller = $supportedCurrency = [];
            $tazapay_buyer =  $this->helper->getTazaPayUserByEmail($customerEmail);
            
            if ($tazapay_buyer['status'] == "error") {
                $buyerCountryCode = $countryCode;
            }
            if ($tazapay_buyer['status'] == "success") {
                $buyerCountryCode = $tazapay_buyer['data']['country_code'];
            }

            /**
             *  ===============================================================
             *              Validation if buyer and seller are same
             *  ===============================================================
             */
            $sellerId = "";
            $sellerType = $this->helper->getSellerType();
            
            /*
             * Seller Type is single_seller
             */
            if ($sellerType == "single_seller") {
                // Get Seller Information
                $sellerEmail = $this->helper->getSellerEmail();
                $tazapay_seller = $this->helper->getTazaPayUserByEmail($sellerEmail);

                $this->validateSingleSellerAndBuyerEmailIsSame($tazapay_seller, $sellerEmail, $customerEmail, $logger);
            } elseif ($sellerType == "multi_seller") {
                $this->validateMultiSellerAndBuyerEmailIsSame($quote, $customerEmail, $logger);
            }
            $sellerCountryCode = null;

            if ($tazapay_seller['status'] == "success") {
                $sellerCountryCode = $tazapay_seller ['data']['country_code'];
            }
            
            $isSupportedCurrency = $this->checkBuyerCountrySupportedBySellerCountry($sellerCountryCode, $buyerCountryCode, $currency, $logger);
            
            if ($isSupportedCurrency == true) {
               
                // For Guest
                if ($isEmailNotExists) {
                    $quote->setCustomerFirstname($firstName);
                    $quote->setCustomerLastname($lastName);
                    $quote->setCustomerEmail($customerEmail);
                    $quote->setCustomerIsGuest(true);
                }
                // Create Order From Quote
                $order = $this->quoteManagement->submit($quote);
                // $order->setEmailSent(0);
                $increment_id = $order->getRealOrderId();

                $quote = $this->_quoteRepository->get($order->getQuoteId());
                $firstName = $getBillingAddress->getFirstName();
                $lastName = $getBillingAddress->getLastName();
                $countryCode = $getBillingAddress->getCountryId();
                $telephone = $getBillingAddress->getTelephone();
                $dialNumber = $this->helper->getPhoneCode($countryCode);
                $grandTotal = $quote->getGrandTotal();

                $quote_id = $order->getQuoteId();
                // it's require for redirect to order success page
                $this->_checkoutSession->setLastSuccessQuoteId($quote_id);
                $this->_checkoutSession->setLastQuoteId($quote_id);
                $this->_checkoutSession->setLastOrderId($order->getEntityId());
                if ($order) {
                    // it's require for get original order id to order success page
                    $this->_checkoutSession->setLastOrderId($order->getId())
                                    ->setLastRealOrderId($order->getIncrementId())
                                    ->setLastOrderStatus($order->getStatus());
                }
                $successPageUrl = $baseUrl.'tazapay/order/success/?order_id='.$increment_id;
                // $successPageUrl = "";
                $quote = $this->_quoteRepository->get($order->getQuoteId());
                // get order id
                $orderId = $order->getId();

                // ========================================================================================== //
                // Get billling address
                $getBillingAddress = $quote->getBillingAddress();
                $paymentQuote = $quote->getPayment();
                $buyerTazaPayAccountUUID = "";
                $userType = "buyer";
                // Get tazapay user by email
                
                $tazapay_buyer =  $this->helper->getTazaPayUserByEmail($customerEmail);
                
                if ($tazapay_buyer['status'] == "success") {
                    $buyerTazaPayAccountUUID = $tazapay_buyer ['data']['id'];
                }

                $customerEmail = $getBillingAddress->getEmail();
                $items = $quote->getAllItems();
                $storeName = $this->getStoreName();
                $transactionDescriptionArr = [];
                
                foreach ($items as $item) {
                    $qty_item = $item->getQty()." x ".$item->getName();
                    $transactionDescriptionArr [] = $qty_item;
                }
                
                $transactionDescriptionItems = implode(", ", $transactionDescriptionArr);
                $transactionDescription = $storeName.": ".$transactionDescriptionItems;
                

                if ($sellerType == "single_seller") {
                    $sellerId = $this->getSellerId();
                } elseif ($sellerType == "multi_seller") {
                    $sellerId = $this->getMultiSellerId($successPageUrl);
                }

                /*
                *==================================*
                *          Create checkout
                *==================================*
                */
                $method = "POST";
                
                $createCheckoutEndpoint = $this->helper->getCheckoutEndpoint();
                $createCheckoutApiUrl = $apiUrl.$createCheckoutEndpoint;
                // Get authorization
                $authorization = $this->basicAuthorization($apiKey, $apiSecretKey);
                $callBackUrl = $baseUrl.'tazapay/index/callback/';
                // Make array for passing parameter in request
                // pass country code instead of country name
                $checkoutData = [
                    "buyer" => [
                        "email"=> $customerEmail,
                        "first_name"=> $firstName,
                        "last_name"=> $lastName,
                        "contact_code"=> $dialNumber,
                        "contact_number"=> $telephone,
                        "country"=> $countryCode,
                        "ind_bus_type" => "Individual"
                    ],
                    "seller_id" => $sellerId,
                    "fee_paid_by" => $this->helper->getEscrowFeePaidBy(),
                    "invoice_currency" => $currency,
                    "invoice_amount" => $grandTotal,
                    "txn_description" => $transactionDescription,
                    "complete_url" => $successPageUrl,
                    "error_url" => $successPageUrl,
                    "callback_url" => $callBackUrl
                ];

                // Convert array to json
                $params = $this->getJsonEncode($checkoutData);
                
                $params = stripslashes($params);
                $params = str_replace('"invoice_amount":"'.$checkoutData['invoice_amount'].'"', '"invoice_amount":'.$checkoutData['invoice_amount'].'', $params);

                $logger->info('Checkout api with follwoing params'.$params);

                // Set header
                $setHeader = [
                    'Authorization: '.$authorization,
                    'Content-Type: application/json'
                ];
                /* Create curl factory */
                $httpAdapter = $this->curlFactory->create();
                
                // Initiate request
                $httpAdapter->write(
                    \Zend_Http_Client::POST, // POST method
                    $createCheckoutApiUrl, // api url
                    '1.1', // curl http client version
                    $setHeader, // set header
                    $params // pass parameter with json format
                );
               
                // execute api request
                $result = $httpAdapter->read();
                // get response
                $body = \Zend_Http_Response::extractBody($result);

                $this->_logger->info("OrderIncrementID:- ".$increment_id.". CreateCheckoutResponse:-".$body);
                /* convert JSON to Array */
                $response = $this->jsonHelper->jsonDecode($body);
                
                $status = $response['status'];
                if ($status == "error" && $status !="success") {
                    $create_checkout_error_msg = "";
                    $create_checkout_error_msg = "Create Tazapay Checkout Error: ".$response['message'];
                    foreach ($response['errors'] as $key => $error) {
                        if (isset($error['code'])) {
                            $create_checkout_error_msg .= ", code: ".$error['code'];
                        }
                        if (isset($error['message'])) {
                            $create_checkout_error_msg .= ", Message: ".$error['message'];
                        }
                        if (isset($error['remarks'])) {
                            $create_checkout_error_msg .= ", Remarks: ".$error['remarks'];
                        }
                    }
                    $create_checkout_error_msg;
                    /*
                    *===============================================================*
                    *          Create Tazapay Checkout Error save order comment
                    *===============================================================*
                    */
                    $comment = $create_checkout_error_msg;
                    $order = $this->orderRepository->get($orderId);
                    if ($order->canComment()) {
                        $history = $this->orderHistoryFactory->create()
                            ->setStatus($order->getStatus())
                            ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                            ->setComment(
                                __('Comment: %1.', $comment)
                            );
                        $history->setIsCustomerNotified(false)
                                ->setIsVisibleOnFront(true);

                        $order->addStatusHistory($history);
                        $this->orderRepository->save($order);
                    }
                    $this->messageManager->addError($create_checkout_error_msg);
                    $resultRedirect->setUrl($successPageUrl);
                    return $resultRedirect ;
                } elseif ($status == "success" && $status !="error") {
                    $customer_account_id = $response['data']['buyer']['id'];
                    $redirectionUrl = $response['data']['redirect_url'];
                    $txn_no = $response['data']['txn_no'];
                    $paymentOrder = $order->getPayment();

                    $paymentQuote->setData('tazapay_create_payment_redirect_url', $redirectionUrl);
                    $paymentOrder->setData('tazapay_create_payment_redirect_url', $redirectionUrl);

                    // set payment data to quote and order
                    $paymentQuote->setData('buyer_tazapay_account_uuid', $customer_account_id);
                    $paymentOrder->setData('buyer_tazapay_account_uuid', $customer_account_id);
                    $paymentQuote->setData('escrow_txn_no', $txn_no);
                    $paymentOrder->setData('escrow_txn_no', $txn_no);
                    $paymentQuote->setData('tazapay_payer_email', $customerEmail);
                    $paymentOrder->setData('tazapay_payer_email', $customerEmail);

                    // Save payment data to quote and order
                    $paymentQuote->save();
                    $paymentOrder->save();
                
                    $resultRedirect->setUrl($redirectionUrl);
                    return $resultRedirect ;
                } else {
                    $this->messageManager->addError(
                        __("Something went wrong. Please contact to store owner.")
                    );
                    $resultRedirect->setUrl($successPageUrl);
                    return $resultRedirect ;
                }

                if (!empty($customer_account_id)) {
                    /*
                    *===========================================================*
                    *          Tazapay Checkout Information save order comment
                    *===========================================================*
                    */
                    $tazapay_checkout_msg = "";
                    $tazapay_checkout_msg = "Tazapay E-Mail: ".$customerEmail;
                    $tazapay_checkout_msg .= ", Tazapay account UUID: ".$customer_account_id;
                    $comment = $tazapay_checkout_msg;
                    $order = $this->orderRepository->get($orderId);
                    if ($order->canComment()) {
                        $history = $this->orderHistoryFactory->create()
                            ->setStatus($order->getStatus())
                            ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                            ->setComment(
                                __('Comment: %1.', $comment)
                            );
                        $history->setIsCustomerNotified(false)
                                ->setIsVisibleOnFront(true);
                        $order->addStatusHistory($history);
                        $this->orderRepository->save($order);
                    }
                } else {
                    $this->messageManager->addError(
                        __("Something went wrong. Please contact to store owner")
                    );
                    $resultRedirect->setUrl($successPageUrl);
                    return $resultRedirect;
                }
            }
        }
    }

    /**
     * Check buyer country supported using seller country
     *
     * @param mixed $sellerCountryCode, $buyerCountryCode, $logger
     */
    public function checkBuyerCountrySupportedBySellerCountry($sellerCountryCode, $buyerCountryCode, $currency, $logger)
    {
        $contryConfig = $this->helper->getCountryConfig($sellerCountryCode);
        $resultRedirect = $this->resultRedirectFactory->create();
        
        if ($contryConfig['status'] == "error") {
            $this->messageManager->addErrorMessage($tazapay_seller['message']);
            $logger->info($contryConfig['message']);
            return $resultRedirect->setPath('checkout/cart');
        }
        $buyer_countries = [];
        $buyerCountryName = $sellerCountryName = null;
        $buyerCountryName = $this->helper->getCountryName($buyerCountryCode);
        $sellerCountryName = $this->helper->getCountryName($sellerCountryCode);
        
        if ($contryConfig['status'] == "success") {
            $buyer_countries = $contryConfig ['data']['buyer_countries'];
            if (in_array($buyerCountryCode, $buyer_countries)) {
                /**
                 *  ===============================================================
                 *                  Check supported invoice currency
                 *  ===============================================================
                 */
                $invoiceConfigCurrency = $this->helper->getInvoiceCurrencyConfig($buyerCountryCode, $sellerCountryCode);
                
                if ($invoiceConfigCurrency['status'] == "error") {
                    $invoiceConfigCurrency['message'];
                    $this->messageManager->addErrorMessage($invoiceConfigCurrency['message']);
                    $logger->info($invoiceConfigCurrency['message']);
                    return $resultRedirect->setPath('checkout/cart');
                }
                if ($invoiceConfigCurrency['status'] == "success") {
                    $supportedCurrency = $invoiceConfigCurrency['data']['currencies'];

                    if (!empty($supportedCurrency) && in_array($currency, $supportedCurrency)) {
                        return true;
                    } else {
                        $currencyNotSupport = "Transactions between buyers from ".$buyerCountryName." and sellers from ".$sellerCountryName." are currently not supported in ".$currency;
                        $this->messageManager->addErrorMessage($currencyNotSupport);
                        $logger->info($currencyNotSupport);
                        return $resultRedirect->setPath('checkout/cart');
                    }
                }
            } else {
                $countryNotSupport = "Transactions between buyers from ".$buyerCountryName." and sellers from ".$sellerCountryName." are currently not supported";
                $this->messageManager->addErrorMessage($countryNotSupport);
                $logger->info($countryNotSupport);
                return $resultRedirect->setPath('checkout/cart');
            }
        }
    }

    /**
     * Check if Seller and Buyer EmailId is same for Single Seller
     *
     * @param mixed $tazapay_seller, $sellerEmail, $customerEmail, $logger
     */
    public function validateSingleSellerAndBuyerEmailIsSame($tazapay_seller, $sellerEmail, $customerEmail, $logger)
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($sellerEmail == $customerEmail) {

            $this->messageManager->addErrorMessage(
                __("Buyer and seller email should not be identical, Please change buyer email address.")
            );
            $logger->info('Buyer and seller email should not be identical, Please change buyer email address.');
            return $resultRedirect->setPath('checkout/cart');
        }

        if ($tazapay_seller['status'] == "error") {
            $this->messageManager->addErrorMessage($tazapay_seller['message']);
            $logger->info($tazapay_seller['message']);
            return $resultRedirect->setPath('checkout/cart');
        }
    }

    /**
     * Check if Seller and Buyer EmailId is same for Multi Seller
     *
     * @param mixed $quote, $customerEmail, $logger
     */
    public function validateMultiSellerAndBuyerEmailIsSame($quote, $customerEmail, $logger)
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $multiSellerMarketPlaceExtension = $this->helper->getMultiSellerMarketplaceExtension();
                
        if ($multiSellerMarketPlaceExtension == "ced_marketplace_ext") {
            
            // Get cart items
            $items = $quote->getAllItems();
            $productSkus = [];
            // Get all products skus from quote items
            foreach ($items as $item) {
                $productSkus [] = $item->getSku();
            }
            // Get vendor collection
            $vendorProducts = $this->_objectManager->create('Ced\CsMarketplace\Model\Vproducts')
                    ->getCollection()
                    ->addFieldToFilter('sku', ['in'=> $productSkus]);

            // Get all vendor ids from products and make an array
            $vendorIds = $vendorProductSkus =  [];
            foreach ($vendorProducts as $VendorProduct) {
                $vendorIds [] = $VendorProduct->getVendorId();
                $vendorProductSkus [] = $VendorProduct->getSku();
            }
            
            $sortProductSkus = sort($productSkus);
            $sortVendorProductSkus = sort($vendorProductSkus);
            
            // If cart items and vendor items are same 
            if (($sortProductSkus == $sortVendorProductSkus) 
                && (count($productSkus) == count($vendorProductSkus))
            ) {
                // Check vendorIds is not empty and is array
                if (!empty($vendorIds) && is_array($vendorIds)) {
                    // Get unique vendor ids and array value reindexing
                    $vendorIds = array_values(array_unique($vendorIds));
                }
                
                $vendorId = "";
                if (count($vendorIds)==1) {
                    // Get vendor id from array
                    $vendorId = $vendorIds[0];
                    if (!empty($vendorId)) {
                        $vendor = $this->_objectManager->get('Ced\CsMarketplace\Model\Vendor')
                                    ->load($vendorId);
                        // Get Vendor Data
                        $vendor->getData();
                        // Get Vendor E-Mail
                        $vendorEmail = $vendor->getData('email');
                        
                        $tazapay_seller =  $this->helper->getTazaPayUserByEmail($vendorEmail);

                        if ($vendorEmail == $customerEmail) {

                            $this->messageManager->addErrorMessage(
                                __("Buyer and seller email should not be identical, Please change buyer email address.")
                            );
                            $logger->info('Buyer and seller email should not be identical, Please change buyer email address.');
                            return $resultRedirect->setPath('checkout/cart');
                        }
                        if ($tazapay_seller['status'] == "error") {
                            $this->messageManager->addErrorMessage($tazapay_seller['message']);
                            $logger->info($tazapay_seller['message']);
                            return $resultRedirect->setPath('checkout/cart');
                        }
                    }
                }
            } else {
                // Quote have admin and vendor products
                $this->messageManager->addError(
                    __("Something went wrong. Please contact to store owner.")
                );
                $resultRedirect->setUrl($successPageUrl);
                return $resultRedirect;
            } 
        }
    }

    /**
     * If Seller Type is Single Seller
     *
     */
    public function getSellerId()
    {
        // Get Seller Information
        $sellerEmail = $this->helper->getSellerEmail();
        $vendorEmail = $sellerEmail;
        $tazapay_seller =  $this->helper->getTazaPayUserByEmail($vendorEmail);
        $sellerId = $tazapay_seller['data']['id'];
        return $sellerId;
    }

    /**
     * If Seller Type is Multi Seller
     *
     * @param mixed $successPageUrl
     */
    public function getMultiSellerId($successPageUrl)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        /*
        * Seller Type is multi_seller
        */
        $multiSellerMarketPlaceExtension = $this->helper->getMultiSellerMarketplaceExtension();
        /*
        * CED Marketplace
        * multiSellerMarketPlaceExtension is ced_marketplace_ext
        * Get seller tazapay accocunt uuid from quote items
        */
        if ($multiSellerMarketPlaceExtension == "ced_marketplace_ext") {
            $sellerId = "";
            // Get cart items
            $quoteItems = $quote->getAllItems();
            $productSkus = [];
            // Get all products skus from quote items
            foreach ($quoteItems as $item) {
                $productSkus [] = $item->getSku();
            }

            // Get vendor collection
            $vendorProducts = $this->_objectManager->create('Ced\CsMarketplace\Model\Vproducts')
                    ->getCollection()
                    ->addFieldToFilter('sku', ['in'=> $productSkus]);
            
            // Get all vendor ids from products and make an array
            $vendorIds = $vendorProductSkus = [];
            foreach ($vendorProducts as $VendorProduct) {
                $vendorIds [] = $VendorProduct->getVendorId();
                $vendorProductSkus [] = $VendorProduct->getSku();
            }
            
            $sortProductSkus = sort($productSkus);
            $sortVendorProductSkus = sort($vendorProductSkus);

            // If cart items skus and vendor items skus are same
            if (($sortProductSkus == $sortVendorProductSkus)
                && (count($productSkus) == count($vendorProductSkus))
            ) {
                // Check vendorIds is not empty and is array
                if (!empty($vendorIds) && is_array($vendorIds)) {
                    // Get unique vendor ids and array value re-indexing
                    $vendorIds = array_values(array_unique($vendorIds));
                }
                $vendorId = $vendorIds[0];
                
                if (!empty($vendorId)) {
                    $vendor = $this->_objectManager->get('Ced\CsMarketplace\Model\Vendor')->load($vendorId);
                    // Get Vendor Data
                    $vendor->getData();
                    // Get Vendor E-Mail
                    $vendorEmail = $vendor->getData('email');
                    // Get tazapay user detail
                    $tazapay_seller =  $this->helper->getTazaPayUserByEmail($vendorEmail);
                    $sellerId = $tazapay_seller['data']['id'];
                } else {
                    $this->messageManager->addError(
                        __("Something went wrong. Please contact to store owner.")
                    );
                    $resultRedirect->setUrl($successPageUrl);
                    return $resultRedirect;
                }
            } elseif ((empty($vendorProductSkus))
                && (!empty($productSkus) && is_array($productSkus) )) {
                // Quote have only admin products
                $sellerEmail = $this->helper->getSellerEmail();
                $vendorEmail = $sellerEmail;
                $tazapay_seller =  $this->helper->getTazaPayUserByEmail($vendorEmail);
                $sellerId = $tazapay_seller['data']['id'];
                return $sellerId;
            } else {
                // Quote have admin and vendor products
                $this->messageManager->addError(
                    __("Something went wrong. Please contact to store owner.")
                );
                $resultRedirect->setUrl($successPageUrl);
                return $resultRedirect;
            }
        }
    }

    /**
     * Generate basicAuth
     * Encode the $basic_auth format into base64 encode
     * in document: Authorization = "Basic " + base64_encode($basic_auth);
     *
     * @param mixed $apiKey
     * @param mixed $apiSecretKey
     */

    public function basicAuthorization($apiKey, $apiSecretKey)
    {
        $basic_auth = $apiKey . ':' . $apiSecretKey;
		$authorization = "Basic " . base64_encode($basic_auth);
		return $authorization;
    }

    /**
     * Get jsonencode
     *
     * @param mixed $data
     * @return bool|false|string
     */
    public function getJsonEncode($data)
    {
        return $this->_json->serialize($data); // it's same as like json_encode
    }

    /**
     * Get jsondecode
     *
     * @param mixed $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function getJsonDecode($data)
    {
        return $this->_json->unserialize($data); // it's same as like json_decode
    }
    
    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
    
    /**
     * Get website identifier
     *
     * @return string|int|null
     */
    public function getWebsiteId()
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }
    
    /**
     * Get Store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }
    
    /**
     * Get Store name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }
    
    /**
     * Get current url for store
     *
     * @param bool|string $fromStore Include/Exclude from_store parameter from URL
     * @return string
     */
    public function getStoreUrl($fromStore = true)
    {
        return $this->_storeManager->getStore()->getCurrentUrl($fromStore);
    }
    
    /**
     * Check if store is active
     *
     * @return boolean
     */
    public function isStoreActive()
    {
        return $this->_storeManager->getStore()->isActive();
    }

    /**
     * Get default store currency code
     *
     * @return string
     */
    public function getDefaultCurrencyCode()
    {
        return $this->_storeManager->getStore()->getDefaultCurrencyCode();
    }
    
    /**
     * Get store base currency code
     *
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }
    
    /**
     * Get current store currency code
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get currency symbol for current locale and currency code
     *
     * @return string
     */
    public function getCurrentCurrencySymbol()
    {
        return $this->_currency->getCurrencySymbol();
    }

    /**
     * Checkout quote id
     *
     * @return int
     */
    public function getQuoteId()
    {
        return (int)$this->_checkoutSession->getQuote()->getId();
    }

    /**
     * Checkout quote id
     *
     * @return int
     */
    public function getBaseurl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_WEB
        );
    }
}
