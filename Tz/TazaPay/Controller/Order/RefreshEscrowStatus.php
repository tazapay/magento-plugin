<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Controller\Order;

use Magento\Framework\App\Action\Context as Context;
use Magento\Framework\Controller\ResultFactory;

class RefreshEscrowStatus extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

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
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;
    
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    
    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var \Tz\TazaPay\Logger\Logger
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $_quoteRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var HistoryFactory
     */
    protected $orderHistoryFactory;

    /**
     * Refresh escrow status contruct
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Tz\TazaPay\Helper\Data $helper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Magento\Quote\Model\QuoteFactory $quote
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Tz\TazaPay\Logger\Logger $logger
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagement
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Tz\TazaPay\Helper\Data $helper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Tz\TazaPay\Logger\Logger $logger,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement
    ) {
        $this->helper = $helper;
        $this->curl = $curl;
        $this->curlFactory = $curlFactory;
        $this->jsonHelper = $jsonHelper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_currency = $currency;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->orderFactory = $orderFactory;
        $this->_logger = $logger;
        $this->request = $request;
        $this->transactionBuilder = $transactionBuilder;
        $this->_quoteRepository = $quoteRepository;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->orderHistoryFactory = $orderHistoryFactory;
        $this->orderManagement = $orderManagement;
        return parent::__construct($context);
    }

    /**
     * Refresh escrow status
     */
    public function execute()
    {
       
        $is_enable = $this->helper->isEnabled();
        if ($is_enable == 1) {
            // $this->request->getParams(); // all params
            $orderId = $this->request->getParam('order_id');
            // Get Order
            $order = $this->orderRepository->get($orderId);

            $environment = $this->helper->getEnvironment();
            if ($environment == "sandbox") {
                $apiKey = $this->helper->getSandboxApiKey();
                $apiSecretKey = $this->helper->getSandboxApiSecretKey();
                $apiUrl = $this->helper->getSandboxApiUrl();
            } else {
                $apiKey = $this->helper->getProductionApiKey();
                $apiSecretKey = $this->helper->getProductionApiSecretKey();
                $apiUrl = $this->helper->getProductionApiUrl();
            }

            $order_txn_no =  $order->getPayment()->getEscrowTxnNo();

            $method = "GET";
            $getEscrowEndpoint = "/v1/escrow/".$order_txn_no;

            $escrow_status_msg = "";
            $escrowStatus = $this->getEscrowStatus($apiKey, $apiSecretKey, $apiUrl, $method, $getEscrowEndpoint);
           
            $status = $escrowStatus['status'];
            if ($status == "error" && $status !="success") {
                $get_escrow_status_error_msg = "";
                $get_escrow_status_error_msg = "Refreshed Escrow Status. Error: ".$escrowStatus['message'];
                if (isset($escrowStatus['errors'])) {
                    foreach ($escrowStatus['errors'] as $key => $error) {
                        if (isset($error['code'])) {
                            $get_escrow_status_error_msg .= ", code: ".$error['code'];
                        }
                        if (isset($error['message'])) {
                            $get_escrow_status_error_msg .= ", Message: ".$error['message'];
                        }
                        if (isset($error['remarks'])) {
                            $get_escrow_status_error_msg .= ", Remarks: ".$error['remarks'];
                        }
                    }
                }
                /*
                 *===============================================================*
                 *          Get Escrow Status Error save order comment
                 *===============================================================*
                 */
                $comment = $get_escrow_status_error_msg;
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
                
            } elseif ($status == "success" && $status !="error") {
                if (!empty($escrowStatus['data']['state'])) {
                    
                    // Get escrow state and subState
                    foreach ($escrowStatus['data'] as $key => $value) {
                        if (!empty($value)) {
                            $escrow_status_msg .= $key.": ".$value.". ";
                        }
                    }
                    
                    $history = $this->orderHistoryFactory->create()
                        ->setStatus($order->getStatus())
                        ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                        ->setComment(
                            __('Refreshed Escrow Status: %1', $escrow_status_msg)
                        );
                    $history->setIsCustomerNotified(false)
                            ->setIsVisibleOnFront(true);

                    $order->addStatusHistory($history);
                    $this->orderRepository->save($order);
                   
                    $this->_logger->info(
                        "order_increment_id: ".$order['increment_id'].'.Refreshed Tazapay escrow status: '.$escrow_status_msg
                    );
                }
                $this->messageManager->addSuccess(__("Refreshed Tazapay escrow status"));
            }
            
        } else {
            $this->messageManager->addError(
                __("Something went wrong.Please try again later.")
            );
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        // Redirect to order order page
        $redirectUrl = $this->_redirect->getRefererUrl();
        $resultRedirect->setUrl($redirectUrl);

        return $resultRedirect ;
    }

    /**
     * Get escrow status by txn_no
     *
     * @param mixed $apiKey
     * @param mixed $apiSecretKey
     * @param mixed $apiUrl
     * @param mixed $method
     * @param mixed $getEscrowEndpoint
     */
    public function getEscrowStatus($apiKey, $apiSecretKey, $apiUrl, $method, $getEscrowEndpoint)
    {
        $salt = $this->helper->generateSalt();
        $timestamp = time();
        // Generate to_sign
        $to_sign = $method.$getEscrowEndpoint.$salt.$timestamp.$apiKey.$apiSecretKey;
        // Get signature
        $signature = $this->getSignature($to_sign, $apiSecretKey);
        $getEscrowApiUrl = $apiUrl.$getEscrowEndpoint;
        
        // Set header
        $setHeader = [
            'accesskey: '.$apiKey,
            'salt: '.$salt,
            'signature: '.$signature,
            'timestamp: '.$timestamp,
            'Content-Type: application/json'
        ];
        /* Create curl factory */
        $httpAdapter = $this->curlFactory->create();
        
        // Initiate request
        $httpAdapter->write(
            \Zend_Http_Client::GET, // GET method
            $getEscrowApiUrl, // api url
            '1.1', // curl http client version
            $setHeader
        );
        // execute api request
        $result = $httpAdapter->read();
        // get response
        $body = \Zend_Http_Response::extractBody($result);
        
        return $response = $this->jsonHelper->jsonDecode($body);
    }

    /**
     * Generate signature
     * $hmacSHA256 is generate hmacSHA256
     * $signature is convert hmacSHA256 into base64 encode
     * in document: signature = Base64(hmacSHA256(to_sign, API-Secret))
     *
     * @param mixed $to_sign
     * @param mixed $apiSecretKey
     */
    public function getSignature($to_sign, $apiSecretKey)
    {
        $hmacSHA256 = hash_hmac('sha256', $to_sign, $apiSecretKey);
        $signature = base64_encode($hmacSHA256);
        return $signature;
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
}
