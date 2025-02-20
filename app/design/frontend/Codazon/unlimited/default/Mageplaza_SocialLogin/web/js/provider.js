/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_SocialLogin
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    /**
     * @param url
     * @param windowObj
     */
    window.socialCallback = function (url, windowObj) {
        customerData.invalidate(['customer','checkout-data', 'cart']);
        customerData.reload(['customer', 'checkout-data', 'cart'], true);

        if (url !== '') {
            window.location.href = url;
        } else {
            window.location.reload(true);
        }

        windowObj.close();
    };
    
    if (typeof window.socialReloadCustomerData == 'undefined') {
        window.socialReloadCustomerData = function() {
            customerData.invalidate(['customer','checkout-data', 'cart']);
            customerData.reload(['customer', 'checkout-data', 'cart'], true);
        }
    }

    return function (config, element) {
        var model = {
            initialize: function () {
                var self = this;
                $(element).attr('onclick', 'this.socialWidget.openPopup()');
                // $(element).on('click', function () {
                    // self.openPopup();
                // });
                element.socialWidget = this;
            },

            openPopup: function () {
                window.open(config.url + '?cdzt='+Math.random().toString().substring(2,8), config.label, this.getPopupParams());
            },

            getPopupParams: function (w, h, l, t) {
                this.screenX = typeof window.screenX !== 'undefined' ? window.screenX : window.screenLeft;
                this.screenY = typeof window.screenY !== 'undefined' ? window.screenY : window.screenTop;
                this.outerWidth = typeof window.outerWidth !== 'undefined' ? window.outerWidth : document.body.clientWidth;
                this.outerHeight = typeof window.outerHeight !== 'undefined' ? window.outerHeight : (document.body.clientHeight - 22);
                this.width = w ? w : 800;
                this.height = h ? h : 620;
                this.left = l ? l : parseInt(this.screenX + ((this.outerWidth - this.width) / 2), 10);
                this.top = t ? t : parseInt(this.screenY + ((this.outerHeight - this.height) / 2.5), 10);

                return (
                    'width=' + this.width +
                    ',height=' + this.height +
                    ',left=' + this.left +
                    ',top=' + this.top
                );
            }
        };
        model.initialize();

        return model;
    };
});
