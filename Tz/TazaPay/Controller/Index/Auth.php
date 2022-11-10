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
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

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
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
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
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
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
        $this->_countryFactory = $countryFactory;
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
            
            // $countryName = $this->getCountryName($countryCode);
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
                $buyerCountryCode = $tazapay_buyer ['data']['country_code'];
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
                $tazapay_seller =  $this->helper->getTazaPayUserByEmail($sellerEmail);

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

            } elseif ($sellerType == "multi_seller") {
               
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
                    } elseif ((empty($vendorProductSkus))
                    && (!empty($productSkus) && is_array($productSkus) )) {
                        // Quote have only admin products
                        $sellerEmail = $this->helper->getSellerEmail();
                        $vendorEmail = $sellerEmail;
                        $tazapay_seller =  $this->helper->getTazaPayUserByEmail($vendorEmail);
                        $sellerId = $tazapay_seller['data']['id'];

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
            $sellerCountryCode = null;

            if ($tazapay_seller['status'] == "success") {
                $sellerCountryCode = $tazapay_seller ['data']['country_code'];
            }

            /**
             *  ===============================================================
             *         Check buyer country supported using seller country
             *  ===============================================================
             */
            $contryConfig = $this->helper->getCountryConfig($sellerCountryCode);
            
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
                            $isSupportedCurrency = true;
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
                
                if (!empty($buyerTazaPayAccountUUID)) {
                    $customer_account_id = $buyerTazaPayAccountUUID;
                } else {
                    $customerEmail = $getBillingAddress->getEmail();

                    /*
                    *==================================*
                    *          Create user
                    *==================================*
                    */
                    $method = "POST";
                    $createUserEndpoint = $this->helper->getCreateUserEndpoint();
                    $createUserApiUrl = $apiUrl.$createUserEndpoint;
                    // Get authorization
                    $authorization = $this->basicAuthorization($apiKey, $apiSecretKey);
                    // Make array for passing parameter in request
                    // pass country code instead of country name
                    $userData = [
                        "email"=> $customerEmail,
                        "first_name"=> $firstName,
                        "last_name"=> $lastName,
                        "contact_code"=> $dialNumber,
                        "contact_number"=> $telephone,
                        "country"=> $countryCode,
                        "ind_bus_type" => "Individual"
                    ];
                    // Convert array to json
                    $params = $this->getJsonEncode($userData);
                
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
                        $createUserApiUrl, // api url
                        '1.1', // curl http client version
                        $setHeader, // set header
                        $params // pass parameter with json format
                    );
                    // execute api request
                    $result = $httpAdapter->read();
                    // get response
                    $body = \Zend_Http_Response::extractBody($result);

                    $this->_logger->info("OrderIncrementID:- ".$increment_id.". CreateUserResponse:-".$body);
                    /* convert JSON to Array */
                    $response = $this->jsonHelper->jsonDecode($body);
                    $status = $response['status'];
                    if ($status == "error" && $status !="success") {
                        $create_user_error_msg = "";
                        $create_user_error_msg = "Create Tazapay User Error: ".$response['message'];
                        foreach ($response['errors'] as $key => $error) {
                            if (isset($error['code'])) {
                                $create_user_error_msg .= ", code: ".$error['code'];
                            }
                            if (isset($error['message'])) {
                                $create_user_error_msg .= ", Message: ".$error['message'];
                            }
                            if (isset($error['remarks'])) {
                                $create_user_error_msg .= ", Remarks: ".$error['remarks'];
                            }
                        }
                        $create_user_error_msg;
                        /*
                        *===============================================================*
                        *          Create Tazapay User Error save order comment
                        *===============================================================*
                        */
                        $comment = $create_user_error_msg;
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
                        $this->messageManager->addError($create_user_error_msg);
                        $resultRedirect->setUrl($successPageUrl);
                        return $resultRedirect ;
                    } elseif ($status == "success" && $status !="error") {
                        $customer_account_id = $response['data']['account_id'];
                    } else {
                        $this->messageManager->addError(
                            __("Something went wrong. Please contact to store owner.")
                        );
                        $resultRedirect->setUrl($successPageUrl);
                        return $resultRedirect ;
                    }
                }
                // If Tazapay account UUID get, it will create escrow between buyer and seller
                if (!empty($customer_account_id)) {
                    /*
                    *===========================================================*
                    *          Tazapay User Information save order comment
                    *===========================================================*
                    */
                    $tazapay_user_msg = "";
                    $tazapay_user_msg = "Tazapay E-Mail: ".$customerEmail;
                    $tazapay_user_msg .= ", Tazapay account UUID: ".$customer_account_id;
                    $comment = $tazapay_user_msg;
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
                    /*
                    *==================================*
                    *          Create Escrow
                    *==================================*
                    */
                    $method = "POST";
                    $createEscrowEndpoint = $this->helper->getCreateEscrowEndpoint();
                    $createEscrowApiUrl = $apiUrl.$createEscrowEndpoint;
                    
                    // Get authorization
                    $authorization = $this->basicAuthorization($apiKey, $apiSecretKey);
                    
                    $txnDescriptionForEscrow = $this->helper->getTxnDescriptionForEscrow();
                    $escrowTxnType = $this->helper->getEscrowTxnType();
                    $releaseMechanism = $this->helper->getReleaseMechanism();
                    $escrowFeePaidBy = $this->helper->getEscrowFeePaidBy();
                    
                    // retrieve quote items array
                    $items = $quote->getAllItems();
                    $storeName = $this->getStoreName();
                    $transactionDescriptionArr = [];
                    
                    foreach ($items as $item) {
                        $qty_item = $item->getQty()." x ".$item->getName();
                        $transactionDescriptionArr [] = $qty_item;
                    }
                    
                    $transactionDescriptionItems = implode(", ", $transactionDescriptionArr);
                    $transactionDescription = $storeName.": ".$transactionDescriptionItems;
                    $transactionDescription;
                    $sellerId = "";
                    $sellerType = $this->helper->getSellerType();
                    /*
                    * Seller Type is single_seller
                    */
                    if ($sellerType == "single_seller") {
                        // Get Seller Information
                        $sellerEmail = $this->helper->getSellerEmail();
                        $vendorEmail = $sellerEmail;
                        $tazapay_seller =  $this->helper->getTazaPayUserByEmail($vendorEmail);
                        $sellerId = $tazapay_seller['data']['id'];
                        
                    } elseif ($sellerType == "multi_seller") {
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
                    
                    if (!empty($sellerId)) {
                        // Make array for passing parameter in request
                        $escrowParams = [
                            "initiated_by" => $customer_account_id,
                            "buyer_id" => $customer_account_id,
                            "seller_id" => $sellerId,
                            "txn_description" => $transactionDescription,
                            "invoice_currency" => $currency,
                            "transaction_source" => 'magento',
                            "invoice_amount" => $grandTotal
                        ];
                        $this->_logger->info("escrowParams:- ".json_encode($escrowParams));
                    } else {
                        $this->messageManager->addError(
                            __("Something went wrong. Please contact to store owner.")
                        );
                        $resultRedirect->setUrl($successPageUrl);
                        return $resultRedirect;
                    }
                    
                    // array to json
                    $escrowParamsJson = $this->getJsonEncode($escrowParams);
                    // Convert invoice_amount string to number
                    $escrowParamsJson = str_replace('"invoice_amount":"'.$escrowParams['invoice_amount'].'"', '"invoice_amount":'.$escrowParams['invoice_amount'].'', $escrowParamsJson);
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
                        $createEscrowApiUrl, // api url
                        '1.1', // curl http client version
                        $setHeader, // set header
                        $escrowParamsJson // pass parameter with json format
                    );
                    // execute api request
                    $result = $httpAdapter->read();
                    // get response
                    $body = \Zend_Http_Response::extractBody($result);
                    $this->_logger->info("OrderIncrementID:- ".$increment_id.". CreateEscrowResponse:-".$body);
                    /* Convert JSON to Array */
                    $response = $this->jsonHelper->jsonDecode($body);
                    
                    $status = $response['status'];
                    if ($status == "error" && $status !="success") {
                        $create_escrow_error_msg = "";
                        $create_escrow_error_msg = "Create Escrow Error: ".$response['message'];
                        foreach ($response['errors'] as $key => $error) {
                            if (isset($error['code'])) {
                                $create_escrow_error_msg .= ", code: ".$error['code'];
                            }
                            if (isset($error['message'])) {
                                $create_escrow_error_msg .= ", Message: ".$error['message'];
                            }
                            if (isset($error['remarks'])) {
                                $create_escrow_error_msg .= ", Remarks: ".$error['remarks'];
                            }
                        }
                        $create_escrow_error_msg;
                        /*
                        *===========================================================*
                        *          Create Escrow Error save order comment
                        *===========================================================*
                        */
                        
                        $comment = $create_escrow_error_msg;
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
                        $this->messageManager->addError($create_escrow_error_msg);
                        $resultRedirect->setUrl($successPageUrl);
                        return $resultRedirect ;
                    
                    } elseif ($status == "success" && $status !="error") {
                        $txn_no = $response['data']['txn_no'];
                        /*
                        *===========================================================*
                        *          Create Escrow success save order comment
                        *===========================================================*
                        */

                        $escrow_success_msg = "";
                        $escrow_success_msg = "Create Escrow Success: ".$response['message'].". ";

                        foreach ($response['data'] as $key => $value) {
                            $escrow_success_msg .= $key.": ".$value.". ";
                        }
                        $escrow_success_msg;

                        $comment = $escrow_success_msg;
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
                        // Get quote and order payment data
                        $paymentQuote = $quote->getPayment();
                        $paymentOrder = $order->getPayment();
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
                        
                        /*
                        *==================================*
                        *          Create Payment
                        *==================================*
                        */
                        $method = "POST";
                        $createPaymentEndpoint = $this->helper->getCreatePaymentEndpoint();
                        $createPaymentApiUrl = $apiUrl.$createPaymentEndpoint;
                        // Get authorization
                        $authorization = $this->basicAuthorization($apiKey, $apiSecretKey);
                        // CallBackUrl
                        $callBackUrl = $baseUrl.'tazapay/index/callback/';
                        // Make array for passing parameter in request
                        // $successPageUrl = $completeUrl;
                        $createPaymentParams = [
                            "txn_no" => $txn_no,
                            "percentage" => 0,
                            "complete_url" => $successPageUrl,
                            "error_url" => $successPageUrl,
                            "callback_url" => $callBackUrl
                        ];
                        // array to json
                        $createPaymentParamsJson = $this->getJsonEncode($createPaymentParams);
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
                            $createPaymentApiUrl, // api url
                            '1.1', // curl http client version
                            $setHeader, // set header
                            $createPaymentParamsJson // pass parameter with json format
                        );
                        // execute api request
                        $result = $httpAdapter->read();
                        // get response
                        $body = \Zend_Http_Response::extractBody($result);
                        $this->_logger->info("OrderIncrementID:- ".$increment_id.". CreatePaymentResponse:-".$body);
                        /* convert JSON to Array */
                        $response = $this->jsonHelper->jsonDecode($body);
                    
                        $status = $response['status'];
                        if ($status == "error" && $status !="success") {
                            $create_payment_error_msg = "";
                            $create_payment_error_msg = "Create Payment Error: ".$response['message'];
                            foreach ($response['errors'] as $key => $error) {
                                if (isset($error['code'])) {
                                    $create_payment_error_msg .= ", code: ".$error['code'];
                                }
                                if (isset($error['message'])) {
                                    $create_payment_error_msg .= ", Message: ".$error['message'];
                                }
                                if (isset($error['remarks'])) {
                                    $create_payment_error_msg .= ", Remarks: ".$error['remarks'];
                                }
                            }
                            $create_payment_error_msg;
                            /*
                            *===========================================================*
                            *          Create Payment Error save order comment
                            *===========================================================*
                            */
                            $comment = $create_payment_error_msg;
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
                            $this->messageManager->addError($create_payment_error_msg);
                            $resultRedirect->setUrl($successPageUrl);
                            return $resultRedirect ;
                        } elseif ($status == "success" && $status !="error") {
                            /*
                            * Redirect to tazapay site for payment
                            */
                            $redirectionUrl = $response['data']['redirect_url'];

                            $paymentQuote->setData('tazapay_create_payment_redirect_url', $redirectionUrl);
                            $paymentOrder->setData('tazapay_create_payment_redirect_url', $redirectionUrl);
                            // Save payment data to quote and order
                            $paymentQuote->save();
                            $paymentOrder->save();
                        
                            $resultRedirect->setUrl($redirectionUrl);
                            return $resultRedirect ;
                        } else {
                            $this->messageManager->addError(
                                __("Something went wrong. Please contact to store owner")
                            );
                            $resultRedirect->setUrl($successPageUrl);
                            return $resultRedirect ;
                        }
                    } else {
                        $this->messageManager->addError(
                            __("Something went wrong. Please contact to store owner")
                        );
                        $resultRedirect->setUrl($successPageUrl);
                        return $resultRedirect ;
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
     * Get countryname
     *
     * @param mixed $countryCode
     */
    public function getCountryName($countryCode)
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
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
