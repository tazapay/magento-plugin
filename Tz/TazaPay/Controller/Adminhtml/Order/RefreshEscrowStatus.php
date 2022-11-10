<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;

class RefreshEscrowStatus extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

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
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Tz\TazaPay\Helper\Data $helper
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagement
     * @param \Tz\TazaPay\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Tz\TazaPay\Helper\Data $helper,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Tz\TazaPay\Logger\Logger $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->curlFactory = $curlFactory;
        $this->jsonHelper = $jsonHelper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->_json = $json;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->orderHistoryFactory = $orderHistoryFactory;
        $this->orderManagement = $orderManagement;
        $this->_logger = $logger;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        
        $orderId = $this->getRequest()->getParam('order_id');
        // Get Order
        $order = $this->orderRepository->get($orderId);
      
        try {
           
            // Get environment
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
            $orderState = $order->getState();
            $OrderStatus = $order->getStatus();
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
                $get_escrow_status_error_msg;
                
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
                    
                    // Update order state and status
                    if (!empty($escrowStatus['data']['sub_state']) 
                        && ($escrowStatus['data']['sub_state'] == "Payment_Done")
                        && ($orderState == "new")
                        && ($OrderStatus == "pending")
                    ) {
                        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
                        $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                        $history = $this->orderHistoryFactory->create()
                            ->setStatus($order->getStatus())
                            ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                            ->setComment(
                                __('Order processed successfully.Order Status is %1.', $order->getStatus())
                            );
                        $history->setIsCustomerNotified(false)
                                ->setIsVisibleOnFront(true);

                        $order->addStatusHistory($history);

                        $this->orderRepository->save($order);
                    } elseif (!empty($escrowStatus['data']['state']) 
                        && ($escrowStatus['data']['state'] == "Payment_Received")
                        && ($orderState == "new")
                        && ($OrderStatus == "pending")
                    ) {
                        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
                        $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                        $history = $this->orderHistoryFactory->create()
                            ->setStatus($order->getStatus())
                            ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                            ->setComment(
                                __('Order processed successfully.Order Status is %1.', $order->getStatus())
                            );
                        $history->setIsCustomerNotified(false)
                                ->setIsVisibleOnFront(true);
                        $order->addStatusHistory($history);
                        $this->orderRepository->save($order);
                    }
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
                $this->messageManager->addSuccessMessage(__("Refreshed Tazapay escrow status"));
                $resultRedirect = $this->resultRedirectFactory->create();
                // Redirect to order order page
                $redirectUrl = $this->_redirect->getRefererUrl();
                $resultRedirect->setUrl($redirectUrl);

                return $resultRedirect ;
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            // Redirect to order order page
            $redirectUrl = $this->_redirect->getRefererUrl();
            $resultRedirect->setUrl($redirectUrl);

            return $resultRedirect ;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong.Please try again later.').$e->getMessage()
            );
            $resultRedirect = $this->resultRedirectFactory->create();
            // Redirect to order order page
            $redirectUrl = $this->_redirect->getRefererUrl();
            $resultRedirect->setUrl($redirectUrl);

            return $resultRedirect ;
        }
    }

    /**
     * Postrequest
     *
     * @return bool
     */
    private function isPostRequest()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        return !empty($request->getPostValue());
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
