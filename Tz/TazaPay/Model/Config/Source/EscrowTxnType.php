<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Model\Config\Source;

class EscrowTxnType implements \Magento\Framework\Option\ArrayInterface
{
    const ESCROWTXNTYPE_GOODS = 'goods';
    const ESCROWTXNTYPE_SERVICE = 'service';

    /**
     * Possible txn_type types.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ESCROWTXNTYPE_GOODS,
                'label' => __('goods'),
            ],
            [
                'value' => self::ESCROWTXNTYPE_SERVICE,
                'label' => __('service'),
            ],
        ];
    }
}
