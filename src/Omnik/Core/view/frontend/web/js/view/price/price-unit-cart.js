define([
    'uiComponent',
    'jquery',
    'mage/translate'
], function (Component, $, $t) {
    'use strict';

    return Component.extend({

        initialize: function () {
            this._super();
            this.loadPricesPerUnit();
        },

        loadPricesPerUnit: function() {
            $('tr.item-info').each(function(idx, value) {
                let $element = $(value);

                let qty = parseInt($element.find("dd").text().split("-")[1], 10);
                let price = $element.find("input#price-children").val();
                let priceFrom = $element.find("input#price-children-by").val();
                let $result = $element.find("div#message-price-unit");

                if (priceFrom !== undefined) {
                    price = priceFrom;
                }

                let priceUnit = (price / qty).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
                if (priceUnit !== "") {
                    let message = $t("Approx. %1 the un").replace('%1', priceUnit);
                    $result.html(message);
                }
            });
        }
    });
});
