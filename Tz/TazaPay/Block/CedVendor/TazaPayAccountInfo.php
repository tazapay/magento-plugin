<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Block\CedVendor;

use Magento\Customer\Controller\RegistryConstants;

class TazaPayAccountInfo extends \Magento\Framework\View\Element\Template
{
    /**
     * Magento Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Ced marketplace vendor session
     *
     * @var \Ced\CsMarketplace\Model\Session $vendorSession
     */
    protected $_vendorSession;
    
    /**
     * Magento directoryBlock
     *
     * @var \Magento\Directory\Block\Data $directoryBlock
     */
    protected $directoryBlock;

    /**
     * Magento Form key
     *
     * @var \Magento\Framework\Data\Form\FormKey $formKey
     */
    protected $formKey;
    
    /**
     * Tazapay helper
     *
     * @var \Tz\TazaPay\Helper\Data $helper
     */
    protected $helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Ced\CsMarketplace\Model\Session $vendorSession
     * @param \Magento\Directory\Block\Data $directoryBlock
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Tz\TazaPay\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Model\Session $customerSession,
        \Ced\CsMarketplace\Model\Session $vendorSession,
        \Magento\Directory\Block\Data $directoryBlock,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Tz\TazaPay\Helper\Data $helper
    ) {
        $this->_coreRegistry = $registry;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_customerSession = $customerSession;
        $this->_vendorSession = $vendorSession;
        $this->directoryBlock = $directoryBlock;
        $this->formKey = $formKey;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Get customer id
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Get customer by id
     *
     * @param mixed $customerId
     */
    public function getCustomerById($customerId)
    {
        $customer = $this->_customerRepositoryInterface->getById($customerId);
        return $customer;
    }

    /**
     * Get current customer
     */
    public function getCurrentCustomer()
    {
        $customer = $this->_customerSession->getCustomer();
        return $customer;
    }

    /**
     * Get tazapay user by email
     *
     * @param string $email
     * @return array
     */
    public function getTazaPayUser($email)
    {
        return $tazapay_buyer =  $this->helper->getTazaPayUserByEmail($email);
    }

    /**
     * Get vendor id
     */
    public function getVendorId()
    {
        return $this->_vendorSession->getVendorId();
    }

    /**
     * Get vendor
     */
    public function getVendor()
    {
        return $this->_vendorSession->getVendor();
    }

    /**
     * Returns action url for contact form
     *
     * @return string
     */
    public function getCreateTazaPayUserFormAction()
    {
        return $this->getUrl('tazapay/cedvendor/createtazapayuser', ['_secure' => true]);
    }

    /**
     * Get countries
     */
    public function getCountries()
    {
        $country = $this->directoryBlock->getCountryHtmlSelect();
        return $country;
    }
    
    /**
     * Get regions
     */
    public function getRegion()
    {
        $region = $this->directoryBlock->getRegionHtmlSelect();
        return $region;
    }

    /**
     * Get formkey
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}
