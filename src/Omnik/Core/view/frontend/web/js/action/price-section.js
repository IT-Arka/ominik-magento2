define([
    'jquery',
    'mage/storage'
], function ($, storage) {
    'use strict';

    return function (productIds, dataOptionSwatchId, variantSeller) {
        return storage.post(
            'rest/V1/catalog/price-products-section',
            JSON.stringify({
                productIds: productIds,
                dataOptionSwatchId: dataOptionSwatchId,
                variantSeller: variantSeller
            }),
            false
        );
    };
});
