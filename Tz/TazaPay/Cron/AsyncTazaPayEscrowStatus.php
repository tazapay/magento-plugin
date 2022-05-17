<?php

namespace Tz\TazaPay\Cron;

use \Psr\Log\LoggerInterface;

class AsyncTazaPayEscrowStatus
{
    /**
     * Tazapay logger
     *
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * Tazapay helper
     * @var \Tz\TazaPay\Helper\Data
     */
    protected $helper;

    /**
     * Magento CurlFactory
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected $curlFactory;

    /**
     * Magento storeManager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Magento checkout session
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    
    /**
     * Magento currency
     * @var \Magento\Directory\Model\Currency
     */
    protected $_currency;

    /**
     * Magento messageManager
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    
    /**
     * Magento customerFactory
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * Magento customerRepository
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    
    /**
     * Magento CartManagementInterface
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * Magento customer AccountManagementInterface
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * Magento Http
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * Magento Http
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * Magento Http
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
     * @param LoggerInterface $logger
     * @param \Tz\TazaPay\Helper\Data $helper
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
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagement
     */
    public function __construct(
        LoggerInterface $logger,
        \Tz\TazaPay\Helper\Data $helper,
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
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement
    ) {
        $this->logger = $logger;
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
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->orderFactory = $orderFactory;
        $this->request = $request;
        $this->transactionBuilder = $transactionBuilder;
        $this->_quoteRepository = $quoteRepository;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->orderHistoryFactory = $orderHistoryFactory;
        $this->orderManagement = $orderManagement;
    }

    /**
     * Update tazapay order status
     *
     * @return void
     */
    public function execute()
    {
        $is_enable = $this->helper->isEnabled();
        if ($is_enable == 1) {
            // Get Environment
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
            
            // Get orderCollection of last one day
            $statuses = ['pending'];
            $prev_date = date('Y-m-d', strtotime('-1 days'));

            $orderCollection =  $this->_orderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('status', ['in' => $statuses])
                ->addAttributeToFilter('created_at', ['gteq'=>$prev_date.' 00:00:00'])
                ->addAttributeToFilter('created_at', ['lteq'=>$prev_date.' 23:59:59']);
                   
            $paymentMethod = "tazapay";
            /* join with payment table */
            $orderCollection->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                [
                    'method',
                    'escrow_txn_no',
                    'tazapay_payer_email',
                    'buyer_tazapay_account_uuid',
                    'additional_information'
                ]
            )
            ->where('sop.method = ?', $paymentMethod)
            ->where('sop.escrow_txn_no != ?', null);
                        
            $orderCollection = $orderCollection->getData();
            
            foreach ($orderCollection as $key => $order) {
                
                $order_txn_no = $order['escrow_txn_no'];
                $method = "GET";
                $getEscrowEndpoint = "/v1/escrow/".$order_txn_no;

                $escrow_status_msg = "";
                $escrowStatus = $this->getEscrowStatus($apiKey, $apiSecretKey, $apiUrl, $method, $getEscrowEndpoint);
                $status = $escrowStatus['status'];
                if ($status == "success" && $status !="error") {
                    if (!empty($escrowStatus['data']['state'])) {
                        
                        foreach ($escrowStatus['data'] as $key => $value) {
                            if (!empty($value)) {
                                $escrow_status_msg .= $key.":".$value.". ";
                            }
                        }
                        $orderId = $order['entity_id'];
                        $orderObject = $this->orderRepository->get($orderId);

                        // Update order state and status
                        if (!empty($escrowStatus['data']['sub_state']) && ($escrowStatus['data']['sub_state'] == "Payment_Done")) {
                            $orderObject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
                            $orderObject->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                            $history = $this->orderHistoryFactory->create()
                                ->setStatus($orderObject->getStatus())
                                ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                                ->setComment(
                                    __('Order processed successfully.Order Status is %1.', $orderObject->getStatus())
                                );
                            $history->setIsCustomerNotified(false)
                                    ->setIsVisibleOnFront(true);

                            $orderObject->addStatusHistory($history);

                            $this->orderRepository->save($orderObject);
                        } elseif (!empty($escrowStatus['data']['state']) && ($escrowStatus['data']['state'] == "Payment_Received")) {
                            $orderObject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
                            $orderObject->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                            $history = $this->orderHistoryFactory->create()
                                ->setStatus($orderObject->getStatus())
                                ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                                ->setComment(
                                    __('Order processed successfully.Order Status is %1.', $orderObject->getStatus())
                                );
                            $history->setIsCustomerNotified(false)
                                    ->setIsVisibleOnFront(true);

                            $orderObject->addStatusHistory($history);

                            $this->orderRepository->save($orderObject);
                        }

                        if ($orderObject->canComment()) {
                            $history = $this->orderHistoryFactory->create()
                                ->setStatus($orderObject->getStatus())
                                ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                                ->setComment(
                                    __('Escrow Status Information: %1.', $escrow_status_msg)
                                );
                            $history->setIsCustomerNotified(false)
                                    ->setIsVisibleOnFront(true);

                            $orderObject->addStatusHistory($history);
                            $this->orderRepository->save($orderObject);
                            $this->logger->info(
                                "order_increment_id: ".$order['increment_id'].'. Tazapay Escrow Status Information: '.$escrow_status_msg
                            );
                            // Order cancel if escrow subState is Payment_Failed
                            if (!empty($escrowStatus['data']['subState']) && ($escrowStatus['data']['subState'] == "Payment_Failed")) {
                                $this->orderManagement->cancel($orderId);
                            }
                        }
                    }
                }
            }
        }
    }

    /*
     * Get escrow status by txn_no
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

    /*
     * generate signature
     * $hmacSHA256 is generate hmacSHA256
     * $signature is convert hmacSHA256 into base64 encode
     * in document: signature = Base64(hmacSHA256(to_sign, API-Secret))
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
     * Get jsonDecode
     *
     * @param mixed $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function getJsonDecode($data)
    {
        return $this->_json->unserialize($data); // it's same as like json_decode
    }
}