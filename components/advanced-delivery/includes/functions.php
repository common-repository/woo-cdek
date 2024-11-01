<?php
function wdad_get_current_plugin() {
	if ( defined( 'LOADING_ADVANCED_DELIVERY_AS_ADDON' ) && LOADING_ADVANCED_DELIVERY_AS_ADDON ) {
		return \WDPC\Advanced\Plugin::app();
	} else {
		return \WBCR\Delivery\Advanced\Plugin::app();
	}
}