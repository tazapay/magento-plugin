<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Controller\Transaction;

use Magento\Framework\App\Action\Context as Context;

class Error extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Error construct
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        return parent::__construct($context);
    }

    /**
     * Error
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->messageManager->addError(
            __("Something went wrong.Please contact to store owner.")
        );
        $resultRedirect->setPath('checkout/onepage/success');
        return $resultRedirect;
    }
}
