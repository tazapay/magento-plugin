<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Controller\CedVendor;

use Magento\Framework\App\ObjectManager;

class TazaPayAccountInfo extends \Ced\CsMarketplace\Controller\Vendor
{
    /**
     * Tazapay accont information
     */
    public function execute()
    {
        $objectManager = ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if ($customerSession->isLoggedIn()) {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        } else {
            return $this->resultRedirectFactory->create()->setPath('csmarketplace/vendor/');
        }
    }
}
