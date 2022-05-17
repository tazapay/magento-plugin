define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'Magento_Ui/js/modal/modal'
], function (_, uiRegistry, select, modal) {
    'use strict';
    return select.extend({      

        initialize: function (){

            var first_name = uiRegistry.get('index = first_name');
            var last_name = uiRegistry.get('index = last_name');
            var business_name = uiRegistry.get('index = business_name');
            var ind_bus_type = this._super().initialValue;    
            if (ind_bus_type == "Individual") {
                first_name.show();
                last_name.show();
                business_name.hide();
            } else{
                business_name.show();
                first_name.hide();
                last_name.hide();
            }
            return this;

        },      

        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
         
            var first_name = uiRegistry.get('index = first_name');
            var last_name = uiRegistry.get('index = last_name');
            var business_name = uiRegistry.get('index = business_name');

            if (value == "Individual") {
                first_name.show();
                last_name.show();
                business_name.hide();
            } else {
                business_name.show();
                first_name.hide();
                last_name.hide();
            }           
            return this._super();
        },
    });
});