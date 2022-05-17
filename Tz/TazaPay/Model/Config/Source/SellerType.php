<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Model\Config\Source;

class SellerType implements \Magento\Framework\Option\ArrayInterface
{
    const SINGLE_SELLER= 'single_seller';
    const MULTI_SELLER = 'multi_seller';

    /**
     * Possible environment types.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SINGLE_SELLER,
                'label' => __('Single Seller'),
            ],
            [
                'value' => self::MULTI_SELLER,
                'label' => __('Multi Seller'),
            ],
        ];
    }
}