define([
    'jquery',
    'mage/storage',
    'mage/translate'
], function ($, storage, $t) {
    'use strict';
    return function (qty, price, $div) {

        $(document.body).trigger('processStart');

        const details = "div.product-item-details, div.product-info-main",
            messagePrice = "#message-price-unit";
        let priceUnit = (price / qty).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        if (priceUnit !== "") {
            let message = $t("Approx. %1 the unit").replace('%1', priceUnit);
            $div.closest(details).find(messagePrice).html(message);
        }

        $(document.body).trigger('processStop');
    };
});
