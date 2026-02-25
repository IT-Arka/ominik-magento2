define([
    'uiComponent',
    'jquery'
], function (Component, $) {
    'use strict';

    return Component.extend({

        initialize: function () {
            this._super();

            $(".price").text("");
            $("div.product-item-details").find("div#message-price-unit").text("");
            $("div.product-info-main").find("div#message-price-unit").text("");
            $('div.special-price').hide();
            $('div.old-price').hide();
        }
    });
});
