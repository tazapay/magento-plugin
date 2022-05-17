<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Model;
 
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
 
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'tazapay';

    /**
     * @var \Tz\TazaPay\Block\Info\TazaPay
     */
    protected $_infoBlockType = \Tz\TazaPay\Block\Info\TazaPay::class;
}
