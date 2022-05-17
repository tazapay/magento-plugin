<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Block\Info;

class TazaPay extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Tz_TazaPay::info/tazapay.phtml';

    /**
     * Returns action url for contact form
     *
     * @return string
     */
    public function getRefreshEscrowStatusUrl()
    {
        return $this->getUrl('tazapay/order/refreshescrowstatus/', ['_secure' => true]);
    }
    /**
     * Returns action url for contact form
     *
     * @return string
     */
    public function getRefreshEscrowStatusAdminUrl()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        return $this->getUrl('tz_tazapay/order/refreshescrowstatus/order_id/'.$order_id, ['_secure' => true]);
    }
}
