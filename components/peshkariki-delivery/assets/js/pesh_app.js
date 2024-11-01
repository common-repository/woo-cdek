var shop_city;

jQuery(document).on('change', "[name='shipping_method[0]']", (function () {
    var method = jQuery("[name='shipping_method[0]']:checked").val();

    if (method.startsWith('wbcr_peshkariki_delivery')) {
        jQuery.get(wWoocommercePeshkarikiDeliveryIntegration.ajaxurl, {
            action: 'peshkariki_get_shop_city'
        }, function (response) {
            console.log(response.data.city)
            shop_city = response.data.city
            jQuery("[name='billing_state']").val(shop_city.toUpperCase());
            jQuery("[name='billing_state']").prop("readonly", true);
            jQuery("[name='billing_city']").val(shop_city.toUpperCase());
            jQuery("[name='billing_city']").prop("readonly", true);
        })
    } else {
        jQuery("[name='billing_state']").val("");
        jQuery("[name='billing_state']").prop("readonly", false);
        jQuery("[name='billing_city']").val("");
        jQuery("[name='billing_city']").prop("readonly", false);
    }
}))
