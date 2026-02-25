define([
    'ko',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils'
], function (ko, Component, customerData, priceUtils) {
    'use strict';
    var subtotalAmount;
    var maxPrice = maxpriceShipping;
    var freeShipping = isFreeShipping;
    var percentage;

    return Component.extend({
        displaySubtotal: ko.observable(true),
        maxprice: maxPrice.toFixed(2),
        freeShipping: isFreeShipping,

        initialize: function () {
            this._super();
            this.cart = customerData.get('cart');
        },

        getTotalCartItems: function () {
            return customerData.get('cart')().summary_count;
        },

        getpercentage: function () {
            if (isFreeShipping) {
                return 100;
            }
            subtotalAmount = customerData.get('cart')().subtotalAmount;
            if (subtotalAmount > maxPrice) {
                subtotalAmount = maxPrice;
            }
            percentage = ((subtotalAmount * 100) / maxPrice);
            return percentage;
        },

        getText: function () {
            if (this.getpercentage() < 100) {
                let total = this.maxprice - this.cart().subtotalAmount
                return 'Faltam R$ ' + this.getFormattedPrice(total) + ' para Frete Grátis'
            }
            return 'Frete Grátis'
        },

        getFormattedPrice: function (price) {
            return priceUtils.formatPrice(price);
        },
    });
});
