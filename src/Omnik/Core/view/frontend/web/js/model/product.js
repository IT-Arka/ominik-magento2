define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'Omnik_Core/js/action/add-options-products',
    'Omnik_Core/js/customer-data/price-section-callback',
    'Omnik_Core/js/action/price-per-seller',
    'Omnik_Core/js/action/valid-select-combo',
    'Omnik_Core/js/view/price-view',
    'Omnik_Core/js/action/valid-swatch'
], function (
    $,
    priceUtils,
    addOptionsProducts,
    priceSectionCallback,
    pricePerSeller,
    validSelectCombo,
    priceView,
    validSwatch
) {
    'use strict';

    return {
        changePrices: function($parent) {
            let $optionLabel = $parent.find(".swatch-option.selected").attr("data-option-label");
            let dataOptionSwatchId = $parent.find(".swatch-option.selected").attr("data-option-id");

            if ($optionLabel !== undefined) {

                let productId = $parent.closest("div.product-item-details").find("div.price-box").attr("data-product-id");
                let variantSeller = $('select.variant_seller').val();

                if (productId == undefined) {
                    productId = $("div.price-box").attr("data-product-id");
                }

                addOptionsProducts(productId, dataOptionSwatchId);

                priceSectionCallback.callback(productId, dataOptionSwatchId, variantSeller);
                this.changePricesPdp(productId);
            }
        },

        changePricesPdp: function(productId) {
            let $seller = $("select.variant_seller");
            let that = this;

            $seller.off('change');
            $seller.on('change', function(e) {
                e.preventDefault();

                let sellerId = $(this).val();
                let swatchId = $('div.swatch-option.selected').attr('data-option-id');

                that.isValidSelectCombo();
                that.isValidSwatchOptions();

                if (sellerId == 0) {
                    return;
                }

                pricePerSeller(productId, sellerId, swatchId).done(function(result) {
                    let data = JSON.parse(result);
                    let $divPrice = $('span.normal-price').find('span.price');

                    if (data.price !== data.special_price) {
                        let $divSpecialPrice = $('span.old-price').find('span.price');
                        priceView.formatedValue($divSpecialPrice, data.price);
                        priceView.formatedValue($divPrice, data.special_price);
                        priceView.accountValueNearVariant(data.special_price, $divPrice);
                    } else {
                        priceView.formatedValue($divPrice, data.price);
                        priceView.accountValueNearVariant(data.price, $divPrice);
                    }
                });
            });
        },

        isValidSelectCombo: function() {
            let $sellerCombo = $('select.variant_seller');

            if ($sellerCombo.length > 0) {
                let productId = $("input[name='product']").val();
                let swatchId = $('div.swatch-option.selected').attr('data-option-id');

                $sellerCombo.find("option").each(function(idx, element) {
                    let sellerId = parseInt($(element).val(), 10);

                    validSelectCombo(productId, sellerId, swatchId).done(function(result) {
                        if (!result) {
                            $(element).attr('disabled', true).addClass('disabled');
                        }
                    });
                });
            }
        },

        isValidSwatchOptions: function() {
            let $sellerCombo = $('select.variant_seller');

            if ($sellerCombo.length > 0) {
                let productId = $("input[name='product']").val();
                let sellerId = $sellerCombo.val();
                let $swatchOption = $("div.swatch-option");

                $swatchOption.each(function(idx, element) {
                    let swatchId = $(element).attr('data-option-id');

                    validSwatch(productId, sellerId, swatchId).done(function(result) {
                        if (result) {
                            $(element).attr('disabled', true).addClass('disabled');
                        }
                    });
                });
            }
        }
    }
});
