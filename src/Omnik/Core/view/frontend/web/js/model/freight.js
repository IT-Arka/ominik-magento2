require([
    'jquery',
    'mage/storage',
    'loader'
], function ($, storage, loader) {
    "use strict";

    $(document).ready(function ($) {
        if ($('input[name="zipcode-cache"]').val()) {
            getShippingRates($('input[name="zipcode-cache"]').val().replace(/\D/g, ''));
        }
    });

    $(document).on('click', '#changeCEP', function () {
        $('div.box-shipping-list').hide();
        $('div.form-calculate').show();
    });

    $(document).on('click', '[name="submit-zipcode"]', function () {
        getShippingRates($('input.product-zipcode-field').val().replace(/\D/g, ''));
        $('span.zipcode-label').html($('input.product-zipcode-field').val().replace(/\D/g, ''));
    });

    $(document).on('click', '.swatch-option', function () {
        getShippingRates($('input.product-zipcode-field').val().replace(/\D/g, ''));
        $('span.zipcode-label').html($('input.product-zipcode-field').val().replace(/\D/g, ''));
    });

    function showLoader() {
        $("div.zip-code").append('<div class="loader"></div>');
    }

    function hideLoader() {
        $("div.zip-code").find('.loader').remove();
    }

    function setFreightHtml(html) {
        $('ul.shipping-list').html(html);
        $('div.box-shipping-list').show();
        $('div.form-calculate').hide();
    }

    /**
     *
     * @param zipcode
     * @returns {*}
     */
    function getShippingRates(zipcode) {
        showLoader();
        storage.post(
            'fc_quote/shipping/freight',
            JSON.stringify({
                product_id: $('input[name="product"]').val(),
                productOptions: $("#fc-products-options-form").serialize(),
                zipcode: zipcode,
                qty: $('input[name="qty"]').val()
            }),
            false
        ).done(function (response) {

            if (zipcode == '') {
                $('div.box-shipping-list').hide();
                $('div.form-calculate').show();
                hideLoader();
                return;
            } else {
                setFreightHtml(response.freight);
            }
            hideLoader();
        })
    }
});
