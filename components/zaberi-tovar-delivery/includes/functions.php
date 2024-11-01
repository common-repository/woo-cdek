<?php
function wdzt_get_current_plugin() {
	if ( defined( 'LOADING_ZABERITOVAR_DELIVERY_AS_ADDON' ) && LOADING_ZABERITOVAR_DELIVERY_AS_ADDON ) {
		return \WDPC\ZaberiTovar\Plugin::app();
	} else {
		return \WBCR\Delivery\ZaberiTovar\Plugin::app();
	}
}