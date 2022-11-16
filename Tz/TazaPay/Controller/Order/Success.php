<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Controller\Order;

use Magento\Framework\App\Action\Context as Context;
use Magento\Sales\Model\Order\Invoice;

class Success extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    
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
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Order success construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Tz\TazaPay\Helper\Data $helper
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Tz\TazaPay\Logger\Logger $logger
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
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
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Tz\TazaPay\Logger\Logger $logger,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->helper = $helper;
        $this->curlFactory = $curlFactory;
        $this->jsonHelper = $jsonHelper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->_logger = $logger;
        $this->request = $request;
        $this->transactionBuilder = $transactionBuilder;
        $this->_quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->orderHistoryFactory = $orderHistoryFactory;
        $this->_invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->_invoiceRepository = $invoiceRepository;
        $this->_registry = $registry;
        $this->resourceConnection = $resourceConnection;
        return parent::__construct($context);
    }

    /**
     * Order success
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $is_enable = $this->helper->isEnabled();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        if ($is_enable == 1) {
            
            $orderIncrementId = $this->request->getParam('order_id');
            $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
            // Get quote
            $quote = $this->_quoteRepository->get($order->getQuoteId());
            // Get quote id
            $quote_id = $order->getQuoteId();
            $storeName = $this->getStoreName();
            // $order->setEmailSent(0);
            $increment_id = $order->getRealOrderId();
            // Get Order ID
            $orderId = $order->getId();
            
            // it's require for redirect order success page
            $this->_checkoutSession->setLastSuccessQuoteId($quote_id);
            $this->_checkoutSession->setLastQuoteId($quote_id);
            $this->_checkoutSession->setLastOrderId($order->getEntityId());
            if ($order) {
                // it's require for get original order id to order success page
                $this->_checkoutSession->setLastOrderId($order->getId())
                                    ->setLastRealOrderId($order->getIncrementId())
                                    ->setLastOrderStatus($order->getStatus());
            }

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

            if (empty($order_txn_no) || $order_txn_no == null) {
                $this->messageManager->addSuccess(__("Thank you for your purchase!"));
                // Redirect to order success page
                $resultRedirect->setPath('checkout/onepage/success');
                return $resultRedirect;
            }
            $sellerType = $this->helper->getSellerType();
            
            // get BillingAddress
            $getBillingAddress = $order->getBillingAddress();
            
            // Defines parameters
            $firstName = $getBillingAddress->getFirstName();
            $lastName = $getBillingAddress->getLastName();
            $customerName = $firstName.' '.$lastName;
            $customerEmail = $getBillingAddress->getEmail();
            
            $method = "GET";
            $getEscrowEndpoint = "/v1/escrow/".$order_txn_no;
            
            $escrow_status_msg = "";
            $escrowStatus = $this->getEscrowStatus($apiKey, $apiSecretKey, $apiUrl, $method, $getEscrowEndpoint);
            $status = $escrowStatus['status'];
            
            if ($status == "success" && $status !="error") {
                if (!empty($escrowStatus['data']['state'])) {
                    
                    // Get Ordercomment_save_after
                    $orderObject = $this->orderRepository->get($orderId);
                    // Get escrow state and subState
                    foreach ($escrowStatus['data'] as $key => $value) {
                        if (!empty($value)) {
                            @$escrow_status_msg .= $key.": ".$value.". ";
                        }
                    }
                    /*
                     * Save latest escrow status
                     */
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

                    /*
                     * If tazapay state is Payment_Received or sub_state is Payment_Done
                     * Update Magento 2 Order state and status
                     * Create Invoice Automatically
                     * Create Transaction Automatically
                     */
                    if ((!empty($escrowStatus['data']['state'])
                            && ($escrowStatus['data']['state'] == "Payment_Received")
                        ) || (!empty($escrowStatus['data']['sub_state'])
                            && ($escrowStatus['data']['sub_state'] == "Payment_Done")
                        )
                    ) {
                        // Prevent multiple time code execution
                        if (!$this->_registry->registry('order_processed')) {
                            $this->_registry->register('order_processed', 'Executed');

                            /*
                            * Create Invoice Automatically
                            */
                            $invoices = $this->_invoiceCollectionFactory
                                            ->create()
                                            ->addAttributeToFilter('order_id', array('eq' => $order->getId()));

                            $invoices->getSelect()->limit(1);

                            if ((int)$invoices->count() == 0) {

                                /*
                                 * Update order state and status
                                 */
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
                                
                                $invoice = $this->_invoiceService->prepareInvoice($order);
                                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                                $invoice->register();
                                $invoice->getOrder()->setCustomerNoteNotify(false);
                                $invoice->getOrder()->setIsInProcess(true);
                                // $order->addStatusHistoryComment(__('Automatically INVOICED'), false);
                                $transactionSave = $this->_transactionFactory
                                                        ->create()
                                                        ->addObject($invoice)
                                                        ->addObject($invoice->getOrder());
                                $transactionSave->save();
                                
                                // Invoice created save comment
                                $orderObject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
                                $orderObject->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                                $history = $this->orderHistoryFactory->create()
                                    ->setStatus($orderObject->getStatus())
                                    ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                                    ->setComment(
                                        __('Automatically INVOICED.Order Status is %1.', $orderObject->getStatus())
                                    );
                                $history->setIsCustomerNotified(false)
                                        ->setIsVisibleOnFront(true);

                                $orderObject->addStatusHistory($history);

                                $this->orderRepository->save($orderObject);
                                
                                // Formatted price
                                $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());
                                
                                /*
                                * Create Transaction
                                */
                                $paymentData['transactionId'] = $order_txn_no;
                                $paymentData['orderIncrementId'] = $increment_id;
                                $paymentData['Escrow state'] = $escrowStatus['data']['state'];
                                $paymentData['Escrow sub_state'] = $escrowStatus['data']['sub_state'];
                                $paymentData['customerName'] = $customerName;
                                $paymentData['customerEmail'] = $customerEmail;
                                $paymentData['amount'] = $formatedPrice;

                                $order->setAdditionalData($paymentData);
                                $paymentData['transactionId']=$order_txn_no;

                                // Prepare payment object
                                $payment = $order->getPayment();
                                $payment->setMethod('tazapay');
                                $method = $payment->getMethodInstance();
                                $methodTitle = $method->getTitle();
                                $paymentData['method_title'] = $methodTitle;
                                $order->getPayment()->setAdditionalInformation($paymentData);
                                $payment->setLastTransId($order_txn_no);
                                $payment->setTransactionId($order_txn_no);

                                // Prepare transaction
                                $transaction = $this->transactionBuilder->setPayment($payment)
                                    ->setOrder($order)
                                    ->setTransactionId($order_txn_no)
                                    ->setAdditionalInformation($paymentData)
                                    ->setFailSafe(true)
                                    ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);

                                // Add transaction to payment
                                // $payment->addTransactionCommentsToOrder(
                                //     $transaction,
                                //     __('The authorized amount is %1.', $formatedPrice)
                                // );
                                $payment->setParentTransactionId($paymentData);

                                // Change order status pending to processing order
                                $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                                $order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);

                                // Save payment, transaction and order
                                $payment->save();
                                $transaction->save();
                                $order->save();

                                // Transaction created save comment
                                $orderObject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
                                $orderObject->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                                $history = $this->orderHistoryFactory->create()
                                    ->setStatus($orderObject->getStatus())
                                    ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                                    ->setComment(
                                        __('The authorized amount is %1. Transaction ID: "%2"', $formatedPrice, $order_txn_no)
                                    );
                                $history->setIsCustomerNotified(false)
                                        ->setIsVisibleOnFront(true);

                                $orderObject->addStatusHistory($history);

                                $this->orderRepository->save($orderObject);

                                /*
                                 * Seller Type is multi_seller
                                 */
                                if ($sellerType == "multi_seller") {
                                    $multiSellerMarketPlaceExtension = $this->helper->getMultiSellerMarketplaceExtension();
                                    /*
                                     * CED Marketplace
                                     * Update vendor order payment state Pending to Paid
                                     */
                                    if ($multiSellerMarketPlaceExtension == "ced_marketplace_ext") {
                                        $vOrdersCollectionFactory = $objectManager->get("Ced\CsMarketplace\Model\ResourceModel\Vorders\CollectionFactory");
                                        $vOrder = $vOrdersCollectionFactory->create()
                                                        ->addFieldToFilter('order_id', ['eq' => $order->getIncrementId()])
                                                        ->getFirstItem();
                                        
                                        $vendorId = $vOrder->getVendorId();
                                        $connection = $this->resourceConnection->getConnection();
                                        $tableName = $this->resourceConnection
                                                          ->getTableName('ced_csmarketplace_vendor_sales_order');

                                        $sql = "Update " . $tableName . " Set order_payment_state = " .
                                                Invoice::STATE_PAID .
                                                " where order_id = '{$vOrder->getOrderId()}' and vendor_id = '{$vendorId}'";
                                        $connection->query($sql);
                                    }
                                }
                            }
                        }
                    } else {
                        $orderObject->setState($orderObject->getState());
                        $orderObject->setStatus($orderObject->getStatus());
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

                    $this->_logger->info(
                        "order_increment_id: ".$order['increment_id'].'. Tazapay Escrow Status Information: '.$escrow_status_msg
                    );
                }
            }
            
            $this->messageManager->addSuccess(__("Thank you for your purchase!"));
            // Redirect to order success page
            $resultRedirect->setPath('checkout/onepage/success');
            return $resultRedirect;
        } else {
            $this->messageManager->addError(
                __("Something went wrong.Please try again later or choose another payment method")
            );
            $resultRedirect->setPath('checkout/onepage/success');
            return $resultRedirect;
        }
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
     * Checkout quote id
     *
     * @return int
     */
    public function getQuoteId()
    {
        return (int)$this->_checkoutSession->getQuote()->getId();
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
        $getEscrowApiUrl = $apiUrl.$getEscrowEndpoint;
        // Get authorization
        $authorization = $this->basicAuthorization($apiKey, $apiSecretKey);
        // Set header
        $setHeader = [
            'Authorization: '.$authorization,
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
}
