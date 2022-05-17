define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function(
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push({
            type: 'tazapay',
            component: 'Tz_TazaPay/js/view/payment/method-renderer/tazapay'
        });
        return Component.extend({});
    }
);