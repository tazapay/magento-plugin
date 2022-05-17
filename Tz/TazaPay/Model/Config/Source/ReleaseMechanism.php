<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Model\Config\Source;

class ReleaseMechanism implements \Magento\Framework\Option\ArrayInterface
{
    const RELEASEMECHANISM_TAZAPAY = 'tazapay';
    const RELEASEMECHANISM_MARKETPLACE = 'marketplace';

    /**
     * Possible environment types.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::RELEASEMECHANISM_TAZAPAY,
                'label' => __('tazapay'),
            ],
            [
                'value' => self::RELEASEMECHANISM_MARKETPLACE,
                'label' => __('marketplace'),
            ],
        ];
    }
}
