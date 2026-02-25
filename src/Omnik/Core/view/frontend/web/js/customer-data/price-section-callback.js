define([
    'jquery',
    'Omnik_Core/js/action/price-section',
    'Omnik_Core/js/view/price-view'
], function ($, priceSection, priceView) {
    'use strict';

    return {

        callback: function(productsIds, dataOptionSwatchId, variantSeller) {
            priceSection(productsIds, dataOptionSwatchId, variantSeller).done(function (result) {

                if (result !== "") {
                    let prices = JSON.parse(result);
                    let products = prices.products;

                    priceView.changeValueTotal(products, dataOptionSwatchId);
                }
            });
        }
    }
});
