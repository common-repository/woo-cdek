jQuery(document).ready(function($) {
	$(document).on('click', '#woocomerce-pickpoint-delivery__choice-postamat-button', function() {
		if( undefined === wWoocommercePickpointDeliveryIntegration || !wWoocommercePickpointDeliveryIntegration.variables.ikn ) {
			throw new Error('Не объявлены настройки для виджета Pickpoint!');
		}

		PickPoint.open(function(result) {
			$('#billing_city').val(result.cityname);
			$('#billing_state').val(result.region);
			$('#wpickpoint_pvz_id').val(result.id);
			$('#wpickpoint_pvz_address').val(result.address);

			$(document.body).trigger('update_checkout');
		}, {
			ikn: wWoocommercePickpointDeliveryIntegration.variables.ikn,
			//fromcity: 'Ростов-на-Дону',
			city: $('#billing_city').val()
		},);

		return false;
	});
});
