<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Model\Config\Source;

class SellerIndBusType implements \Magento\Framework\Option\ArrayInterface
{
    const SELLERINDBUSTYPE_INDIVIDUAL= 'Individual';
    const SELLERINDBUSTYPE_BUSINESS = 'Business';

    /**
     * Possible environment types.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SELLERINDBUSTYPE_INDIVIDUAL,
                'label' => __('Individual'),
            ],
            [
                'value' => self::SELLERINDBUSTYPE_BUSINESS,
                'label' => __('Business'),
            ],
        ];
    }
}
