<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Logger;

use Monolog\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var Logger
     */
    protected $loggerType = Logger::INFO;
    
    /**
     * Payment log file name
     *
     * @var string
     */
    protected $fileName = '/var/log/tazapay_payment.log';
}
