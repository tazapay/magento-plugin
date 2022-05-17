<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Model\Config\Source;

class FeePaidBy implements \Magento\Framework\Option\ArrayInterface
{
    const FEEPAIDBY_SELLER= 'seller';
    const FEEPAIDBY_BUYER = 'buyer';

    /**
     * Possible environment types.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::FEEPAIDBY_SELLER,
                'label' => __('seller'),
            ],
            [
                'value' => self::FEEPAIDBY_BUYER,
                'label' => __('buyer'),
            ],
        ];
    }
}
