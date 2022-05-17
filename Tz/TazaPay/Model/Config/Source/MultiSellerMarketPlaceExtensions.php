<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Model\Config\Source;

class MultiSellerMarketPlaceExtensions implements \Magento\Framework\Option\ArrayInterface
{
    const CED_MARKETPLACE = 'ced_marketplace_ext';
    const WEBKUL_MARKETPLACE = 'webkul_marketplace_ext';

    /**
     * Possible environment types.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CED_MARKETPLACE,
                'label' => __('CED MarketPlace Extension'),
            ]/*,
            [
                'value' => self::WEBKUL_MARKETPLACE,
                'label' => __('Webkul MarketPlace Extension'),
            ],*/
        ];
    }
}