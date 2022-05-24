<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;
use Magento\Backend\Model\Session\Quote as AdminQuoteSession;

class RestrictPaymentMethods implements ObserverInterface
{
    /**
     *
     * @var \Magento\Framework\App\State
     */
    protected $_state;

    /**
     *
     * @var Session
     */
    protected $_session;

    protected $_quote;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     *
     * @var \Tz\TazaPay\Helper\Data
     */
    protected $helper;

    /**
     *
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;
    
    /**
     * Restrict payment method constructor.
     *
     * @param \Magento\Framework\App\State $state
     * @param Session $checkoutSession
     * @param AdminQuoteSession $adminQuoteSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Tz\TazaPay\Helper\Data $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        \Magento\Framework\App\State $state,
        Session $checkoutSession,
        AdminQuoteSession $adminQuoteSession,
        \Psr\Log\LoggerInterface $logger,
        \Tz\TazaPay\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->_state = $state;
        $this->_objectManager = $objectManager;
        $this->helper = $helper;
        if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $this->_session = $adminQuoteSession;
        } else {
            $this->_session = $checkoutSession;
        }
        $this->_quote = $this->_session->getQuote();
        $this->_logger = $logger;
        $this->_moduleManager = $moduleManager;
    }
    /**
     * Payment_method_is_active event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(EventObserver $observer)
    {
        $checkResult = $observer->getEvent()->getResult();

        //Code of Current Payment Method--
        $code = $observer->getEvent()->getMethodInstance()->getCode();
        
        /*
         * This is disabling the payment method at admin side only
         */
        if ($this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML && $code == 'tazapay') {
            $checkResult->setData('is_available', false);
        }
        /*
         * This is disabling the payment method at checkout page in front-end only
         */
        if ($this->_state->getAreaCode() != \Magento\Framework\App\Area::AREA_ADMINHTML && $code == 'tazapay') {
            
            $environment = $this->helper->getEnvironment();
            $sellerId = "";
            $sellerType = $this->helper->getSellerType();
            
            /*
             * Seller Type is single_seller
             */
            if ($sellerType == "single_seller") {
                $sellerEmail = $this->helper->getSellerEmail();
                $sellerIndBusType = $this->helper->getSellerIndBusType();
                $tazaPayUser = $this->helper->getTazaPayUserByEmail($sellerEmail);
                if (@$tazaPayUser['status'] == "success") {
                    $sellerId = $tazaPayUser['data']['id'];
                } elseif (@$tazaPayUser['status'] == "error") {
                    $checkResult->setData('is_available', false);
                }
                // If sellerType is single seller and seller does not have tazapay account
                if (empty($sellerId)) {
                    $checkResult->setData('is_available', false);
                }
            } elseif ($sellerType == "multi_seller") {
                /*
                 * Seller Type is multi_seller
                 */
                $multiSellerMarketPlaceExtension = $this->helper->getMultiSellerMarketplaceExtension();
                /*
                 * If multiSellerMarketPlaceExtension is ced_marketplace_ext (CED Marketplace)
                 * CED Marketplace If seller does not have tazapay account
                 * Multiple seller items into cart
                 */
                if ($multiSellerMarketPlaceExtension == "ced_marketplace_ext") {
                    // Check Ced_CsMarketplace is enable
                    $IsCed_CsMarketplace_Enabled = $this->_moduleManager->isEnabled('Ced_CsMarketplace');
                    // If Ced_CsMarketplace enabled
                    if ($IsCed_CsMarketplace_Enabled) {
                        // Get cart items
                        $items = $this->_quote->getAllItems();
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
                        
                        $sortProductSkus = asort($productSkus);
                        $sortVendorProductSkus = asort($vendorProductSkus);
                        
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
                                    
                                    if (!empty($vendorEmail)) {
                                        // Get tazapay user detail of vendor
                                        $tazaPayUser = $this->helper->getTazaPayUserByEmail($vendorEmail);
                                        if ($tazaPayUser['status'] == "success") {
                                            $sellerId = $tazaPayUser['data']['id'];
                                            $this->_logger->debug('Vendor have tazapay account.E-Mail address:'.$vendorEmail).", Account UUID: ".$sellerId;
                                        } elseif ($tazaPayUser['status'] == "error") {
                                            $checkResult->setData('is_available', false);
                                        }
                                    }
                                }
                            } elseif (count($vendorIds)==0) {
                                // Disable tazapay payment method because vendors are zero 
                                $checkResult->setData('is_available', false);
                            } elseif (count($vendorIds) >= 1) {
                                // Disable tazapay payment method because vendors are multiples 
                                $checkResult->setData('is_available', false);
                            }
                        } elseif ((empty($vendorProductSkus))
                            && (!empty($productSkus) && is_array($productSkus) )) {
                            // Cart Have only admin products
                            $sellerEmail = $this->helper->getSellerEmail();
                            $tazaPayUser = $this->helper->getTazaPayUserByEmail($sellerEmail);
                            if ($tazaPayUser['status'] == "success") {
                                $sellerId = $tazaPayUser['data']['id'];
                            } elseif ($tazaPayUser['status'] == "error") {
                                $checkResult->setData('is_available', false);
                            }
                            // If sellerType is multi seller and admin seller does not have tazapay account
                            if (empty($sellerId)) {
                                $checkResult->setData('is_available', false);
                            }
                        } else {
                            // Disable tazapay payment method because cart have admin and vendor items
                            $checkResult->setData('is_available', false);
                        }
                    } else {
                        // Disable tazapay payment method because CED Marketplace module is not enabled
                        $checkResult->setData('is_available', false);
                    }
                } elseif ($multiSellerMarketPlaceExtension == "webkul_marketplace_ext") {
                /*
                 * If multiSellerMarketPlaceExtension is ced_marketplace_ext (CED Marketplace)
                 * Webkul Marketplace If seller does not have tazapay account
                 * Multiple seller items into cart
                 */
                    $checkResult->setData('is_available', false);
                }
            }
        }
    }
}
