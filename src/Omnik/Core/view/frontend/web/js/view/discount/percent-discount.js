define([
    'jquery',
    'mage/translate'
], function ($,$t) {
    'use strict';
    return function (percent, $div) {
        $(document.body).trigger('processStart');

        let details = "div.product-item-details, div.product-info-main";
        let divPercentDiscount = "#value-percent-discount";

        $div.closest(details).find(divPercentDiscount).html("-" + percent + $t('% OFF'));

        $(document.body).trigger('processStop');
    };
});
