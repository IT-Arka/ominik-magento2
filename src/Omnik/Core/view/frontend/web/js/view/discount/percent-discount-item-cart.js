define([
    'uiComponent',
    'jquery'
], function (Component, $) {
    'use strict';

    return Component.extend({

        initialize: function () {
            this._super();
            this.showPercentDiscountCart();
        },

        showPercentDiscountCart: function () {
            $('tr.item-info').each(function (idx, value) {
                let $element = $(value);
                let price = $element.find("input#price-children-from").val();
                let specialPrice = $element.find("input#price-children-by").val();
                let $result = $element.find("div#value-percent-discount");

                if (price !== undefined && specialPrice !== undefined) {
                    let percent = ((price - specialPrice) / price) * 100;
                    $result.html(percent.toFixed(0) + '% OFF');
                }
            });
        }
    });
});
