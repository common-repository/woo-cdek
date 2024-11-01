<?php
function wdpk_get_current_plugin() {
	if ( defined( 'LOADING_PESHKARIKI_DELIVERY_AS_ADDON' ) && LOADING_PESHKARIKI_DELIVERY_AS_ADDON ) {
		return \WDPC\Peshkariki\Plugin::app();
	} else {
		return \WDPK\Delivery\Peshkariki\Plugin::app();
	}
}