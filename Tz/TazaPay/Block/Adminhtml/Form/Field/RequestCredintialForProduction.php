<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

class RequestCredintialForProduction extends Field
{
    /**
     * @inheritDoc
     */
    protected function _renderScopeLabel(AbstractElement $element): string
    {
        return '';
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        // Replace field markup with Request credentials button
        $title = __('Request credentials');
        $envId = 'select-groups-tazapay-section-groups-tazapay-groups-tazapay-'
            . 'required-fields-environment-value';
        $storeId = 0;

        if ($this->getRequest()->getParam('website')) {
            $website = $this->_storeManager->getWebsite($this->getRequest()->getParam('website'));
            if ($website->getId()) {
                /** @var Store $store */
                $store = $website->getDefaultStore();
                $storeId = $store->getStoreId();
            }
        }
        // @codingStandardsIgnoreStart
        $html = <<<TEXT
            <button
                type="button"
                title="{$title}"
                class="button"
                onclick="window.open('https://app.tazapay.com/signup')">
                <span>{$title}</span>
            </button>
        TEXT;
        // @codingStandardsIgnoreEnd
        return $html;
    }
}
