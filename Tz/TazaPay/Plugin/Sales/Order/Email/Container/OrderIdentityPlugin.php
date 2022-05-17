<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Plugin\Sales\Order\Email\Container;

class OrderIdentityPlugin
{
    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * IsEnabled
     *
     * @param \Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject
     * @param callable $proceed
     * @return bool
     */
    public function aroundIsEnabled(
        \Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject,
        callable $proceed
    ) {
        $returnValue = $proceed();
        $forceOrderMailSentOnSuccess = $this->checkoutSession->getForceOrderMailSentOnSuccess();
        if (isset($forceOrderMailSentOnSuccess) && $forceOrderMailSentOnSuccess) {
            if ($returnValue) {
                $returnValue = false;
            } else {
                $returnValue = true;
            }
            $this->checkoutSession->unsForceOrderMailSentOnSuccess();
        }
        return $returnValue;
    }
}
