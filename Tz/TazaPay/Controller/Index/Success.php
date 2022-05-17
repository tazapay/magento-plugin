<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Controller\Index;

use Magento\Framework\App\Action\Context as Context;

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
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;
    
    /**
     * @var \Tz\TazaPay\Logger\Logger
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $_quoteRepository;

    /**
     * Success contruct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Tz\TazaPay\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Tz\TazaPay\Logger\Logger $logger
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Tz\TazaPay\Helper\Data $helper,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Tz\TazaPay\Logger\Logger $logger,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {
        $this->helper = $helper;
        $this->curl = $curl;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->customerFactory = $customerFactory;
        $this->orderFactory = $orderFactory;
        $this->_logger = $logger;
        $this->request = $request;
        $this->_quoteRepository = $quoteRepository;
        return parent::__construct($context);
    }
    /**
     * Success
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $is_enable = $this->helper->isEnabled();
        if ($is_enable == 1) {
            // $this->request->getParams(); // all params
            $orderIncrementId = $this->request->getParam('order_id');
            $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
            // Get quote
            $quote = $this->_quoteRepository->get($order->getQuoteId());
            // Get quote id
            $quote_id = $order->getQuoteId();
            $storeName = $this->getStoreName();
            // $order->setEmailSent(0);
            $increment_id = $order->getRealOrderId();
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
            $this->messageManager->addSuccess(__("Thank you for your purchase!"));
            // Redirect to order success page
            $resultRedirect->setPath('checkout/onepage/success');
            return $resultRedirect;
        } else {
            $this->messageManager->addError(
                __("Something went wrong. Please contact to store owner.")
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
}
