define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'Omnik_Core/js/action/price-per-unit',
    'Omnik_Core/js/view/discount/percent-discount'
], function ($, priceUtils, pricePerUnit, percentDiscount) {
    'use strict';

    return {
        changeValueTotal: function (products, index) {
            let that = this;

            $.each(products, function (idx, value) {
                let $priceBox = $("div.price-box");
                $priceBox.each(function (id, element) {
                    if ($(element).attr("data-product-id") == idx) {
                        let elements = value["#"+index];

                        if (that.isPdp()) {
                            if (that.isPdpValid()) {
                                that.complete(elements, element, value);
                            }
                        } else {
                            that.complete(elements, element, value);
                        }
                    }
                });
            });
        },

        complete: function (elements, element, value) {
            if (elements.special_price !== undefined && elements.price !== elements.special_price) {
                this.changeSpecialPrice(elements, element, value);
            } else {
                this.changePrice(elements, element, value);
            }

            this.showButtonAndSwitchAddCart(element);
        },

        isPdp: function() {
            let $sellerCombo = $('select.variant_seller');
            if ($sellerCombo.length > 0) {
                return true;
            }
            return false;
        },

        isPdpValid: function() {
            let $sellerCombo = $('select.variant_seller');
            if (parseInt($sellerCombo.val(), 10) > 0) {
                return true;
            }

            return false;
        },

        changeSpecialPrice: function (elements, element, value) {
            let $elementPrice = $(element).find("span.normal-price").find("span.price");
            let $elementSpecialPrice = $(element).find("span.old-price").find("span.price");
            let price = elements.price;
            let special_price = elements.special_price;

            this.formatedValue($elementPrice, special_price);
            this.storeValuesVariants(Object.values(value)[1], $elementPrice);
            this.accountValueNearVariant(special_price, $elementPrice);

            this.formatedValue($elementSpecialPrice, price);
            this.storeValuesVariants(Object.values(value)[0], $elementSpecialPrice);

            if (price !== special_price) {
                let percent = ((price - special_price) / price) * 100;
                this.showPercentDiscount(percent.toFixed(0), $elementPrice);

                $(element).find("span.old-price").removeClass("no-display").removeAttr("style");
            }
        },

        changePrice: function (elements, element, value) {
            let $elementPrice = $(element).find("span.normal-price").find("span.price");
            let price = elements.price;

            this.formatedValue($elementPrice, price);
            this.storeValuesVariants(Object.values(value)[1], $elementPrice);
            this.accountValueNearVariant(price, $elementPrice);
        },

        showButtonAndSwitchAddCart: function (element) {
            $(element).closest('.product-item-details, .product-info-main')
                .find('div.swatch-attribute-options').show();

            $(element).closest('.product-item-details, .product-info-main')
                .find("button[type='submit']").removeClass('hide').show();
        },

        formatedValue($div, value) {
            let priceFormat = {
                decimalSymbol: ',',
                groupLength: 3,
                groupSymbol: ".",
                integerRequired: false,
                pattern: "R$ %s",
                precision: 2,
                requiredPrecision: 2
            };

            $('.product-info-price').show();

            let formattedValue =  priceUtils.formatPrice(value, priceFormat);
            $div.text(formattedValue);
        },

        storeValuesVariants(variants, $div) {
            $.each(variants, function (id, value) {
                let $options = $div.closest('div.product-item-details, div.product-info-main').find("div.swatch-option");
                $options.each(function (idx, element) {
                    if ($(element).attr("data-option-id") == id) {
                        $(element).data(id, value);
                    }
                });
            });
        },

        accountValueNearVariant(price, $div) {
            let $details = $div.closest("div.product-item-details, div.product-info-main");
            let $option = $details.find(".swatch-option.selected").attr("data-option-label");

            if ($option !== undefined && price !== undefined) {
                $('.product-item-details').show();
                let elements = $option.split("-");
                pricePerUnit(elements[1], price, $details.find("div.swatch-attribute"));
            }
        },

        showPercentDiscount(percent, $div) {
            let $details = $div.closest("div.product-item-details, div.product-info-main");
            percentDiscount(percent, $details.find("div.swatch-attribute"));
        }
    }
});
