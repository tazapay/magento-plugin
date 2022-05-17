define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'mage/url',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/place-order',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data'
    ],
    function(
        Component, 
        $, 
        v, 
        url, 
        urlBuilder,
        fullScreenLoader, 
        placeOrderAction, 
        messageList, 
        quote, 
        customerData
        ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Tz_TazaPay/payment/tazapay-escrow-payment'
            },
            /**
             * @returns {exports.initialize}
             */
            initialize: function() {
                this._super();
                return this;
            },
            /** Returns send check to info */
            getMailingAddress: function() {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            
            /** Returns tazapay logo image path */
            getTazaPayLogoImagePath: function() {
                return window.checkoutConfig.payment.tazapay.tazapayDarkLogo;
            },

            getCode: function() {
                return 'tazapay';
            },
            
            /**
             * Get data
             *
             * @returns {Object}
             */
            getData: function() {
                console.log(window.checkoutConfig.payment.tazapay.createTazaPayAccountUrl);
                console.log(window.checkoutConfig.payment.tazapay.redirectUrl);

                var data = {
                    'method': this.getCode()
                };
                // var data = {
                //     'method': this.getCode(),
                //      'additional_data': {
                //         'customer_email': $('#tazapay_customer_email').val()
                //      }
                // };
                data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
                return data;
            },

            isActive: function() {
                return true;
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            getTazaPayPayMethodsButtonsLogoUrl: function() {
                return window.checkoutConfig.payment.tazapay.tazapayPaymentMethodsButtonsLogo;
            },

            getCreatetazaPayUrl: function() {
                return 'window.open("'+window.checkoutConfig.payment.tazapay.createTazaPayAccountUrl+'","Ratting","width=800,height=900,");';
            },

            /**
             * Prepare data to place order
             * @param {Object} data
             */
            placeOrder: function(data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this;
                if (this.validate()) {
                    self.isPlaceOrderActionAllowed(false);
                    $.mage.redirect(
                        window.checkoutConfig.payment.tazapay.redirectUrl
                    );
                } else {
                    return false
                }
            },

            /**
             * Show error message
             * @param {String} errorMessage
             */
            showError: function(errorMessage) {
                messageList.addErrorMessage({
                    message: errorMessage
                });
            },

            /**
             * Get value of instruction field.
             * @returns {String}
             */
            getInstructions: function () {
                return window.checkoutConfig.payment.tazapay.shortDescription;
            }
        });
    }
);