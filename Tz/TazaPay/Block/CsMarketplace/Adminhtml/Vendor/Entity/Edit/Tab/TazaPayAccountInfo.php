<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Block\CsMarketplace\Adminhtml\Vendor\Entity\Edit\Tab;
 
class TazaPayAccountInfo extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Magento objectmanager
     *
     * @var \Magento\Framework\ObjectManagerInterface $objectManager
     */
    protected $_objectManager;

    /**
     * Tazapay helper
     *
     * @var \Tz\TazaPay\Helper\Data $helper
     */
    protected $helper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Tz\TazaPay\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Tz\TazaPay\Helper\Data $helper,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        $this->helper = $helper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $vendor = $this->_coreRegistry->registry('vendor_data');
        if ($vendor) {
            $methods = $vendor->getPaymentMethods();
            $form = $this->_formFactory->create();
            $fieldset = $form->addFieldset('csmarketplace_tazapay_account_info', array("legend"=> "Tazapay Account Information"));
            $supplierEmail = $vendor->getData('email');
            if (!empty($supplierEmail)) {
                
                $tazaPayUser = $this->helper->getTazaPayUserByEmail($supplierEmail);
               
                if ((!empty($supplierEmail)) && (is_array($tazaPayUser)) && ($tazaPayUser['status'] == 'success')) {

                    $fieldset->addField(
                        "account_id",
                        'label',
                        [
                        'label' => __('Tazapay Account UUID'),
                        'value' => $tazaPayUser['data']['id'],
                        'name' => 'account_id'
                        ]
                    );

                    $fieldset->addField(
                        "ind_bus_type",
                        'label',
                        [
                        'label' => __('Entity Type'),
                        'value' => $tazaPayUser['data']['ind_bus_type'],
                        'name' => 'ind_bus_type'
                        ]
                    );

                    if ($tazaPayUser['data']['ind_bus_type'] == "Individual") {
                        $fieldset->addField(
                            "first_name",
                            'label',
                            [
                            'label' => __('First Name'),
                            'value' => $tazaPayUser['data']['first_name'],
                            'name' => 'first_name'
                            ]
                        );
                        $fieldset->addField(
                            "last_name",
                            'label',
                            [
                            'label' => __('Last Name'),
                            'value' => $tazaPayUser['data']['last_name'],
                            'name' => 'last_name'
                            ]
                        );
                    } else {
                        $fieldset->addField(
                            "business_name",
                            'label',
                            [
                            'label' => __('Business Name'),
                            'value' => $tazaPayUser['data']['company_name'],
                            'name' => 'business_name'
                            ]
                        );
                    }
                    
                    $fieldset->addField(
                        "email",
                        'label',
                        [
                        'label' => __('Email'),
                        'value' => $tazaPayUser['data']['email'],
                        'name' => 'email'
                        ]
                    );
                    $fieldset->addField(
                        "contact_code",
                        'label',
                        [
                        'label' => __('Contact Code'),
                        'value' => $tazaPayUser['data']['contact_code'],
                        'name' => 'contact_code'
                        ]
                    );
                    if ($tazaPayUser['data']['contact_number']) {

                        $fieldset->addField(
                            "contact_number",
                            'label',
                            [
                            'label' => __('Contact Number'),
                            'value' => $tazaPayUser['data']['contact_number'],
                            'name' => 'contact_number'
                            ]
                        );
                    }
                    $fieldset->addField(
                        "country",
                        'label',
                        [
                        'label' => __('Country'),
                        'value' => $tazaPayUser['data']['country'],
                        'name' => 'country'
                        ]
                    );
                    if (!empty($tazaPayUser['data']['partners_customer_id'])) {

                        $fieldset->addField(
                            "partners_customer_id",
                            'label',
                            [
                            'label' => __('Partner Customer ID'),
                            'value' => $tazaPayUser['data']['partners_customer_id'],
                            'name' => 'partners_customer_id'
                            ]
                        );
                    }
                }
            }
            $this->setForm($form);
        }
        return $this;
    }
    /**
     * Retrieve label from value
     *
     * @param mixed $value
     * @param array $values
     */
    protected function getLabelByValue($value = '', $values = [])
    {
        foreach ($values as $key => $option) {
            
            if (is_array($option)) {
                if (isset($option['value']) && $option['value'] == $value && $option['label']) {
                    return $option['label'];
                    break;
                }
            } else {
                if ($key == $value && $option->getText()) {
                    return $option->getText();
                    break;
                }
            }
        }
        return $value;
    }
}
