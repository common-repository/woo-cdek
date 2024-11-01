<?php
function wdcd_get_current_plugin() {
	if ( defined( 'LOADING_CDEK_DELIVERY_AS_ADDON' ) && LOADING_CDEK_DELIVERY_AS_ADDON ) {
		return \WDPC\Cdek\Plugin::app();
	} else {
		return \WBCR\Delivery\Cdek\Plugin::app();
	}
}