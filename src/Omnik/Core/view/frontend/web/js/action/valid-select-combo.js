define([
    'jquery',
    'mage/storage'
], function ($, storage) {
    'use strict';

    return function (productId, sellerId, swatchId) {
        return storage.post(
            'rest/V1/catalog/valid-select-combo',
            JSON.stringify({
                productId: productId,
                sellerId: sellerId,
                swatchId: swatchId
            }),
            false
        );
    };
});
