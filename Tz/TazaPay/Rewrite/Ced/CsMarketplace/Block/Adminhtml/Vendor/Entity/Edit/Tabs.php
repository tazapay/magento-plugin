<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Rewrite\Ced\CsMarketplace\Block\Adminhtml\Vendor\Entity\Edit;

 class Tabs extends \Ced\CsMarketplace\Block\Adminhtml\Vendor\Entity\Edit\Tabs
 {
    /**
     * Tazapay information tab
     */
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
        
        if ($vendor_id = $this->getRequest()->getParam('vendor_id', 0)) {
            $this->addTab(
                'tazapay_information',
                [
                'label'     => __('Tazapay Information'),
                'content'   => $this->getLayout()
                                    ->createBlock('Tz\TazaPay\Block\CsMarketplace\Adminhtml\Vendor\Entity\Edit\Tab\TazaPayAccountInfo')
                                    ->toHtml(),
                ]
            );
        }
        return \Magento\Backend\Block\Widget\Tabs::_beforeToHtml();
    }
}
