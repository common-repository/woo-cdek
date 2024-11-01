<?php
/**
 * @param bool $plugin
 */
function wdpp_get_current_plugin( $plugin = true ) {
	if ( defined( 'LOADING_PICKPOINT_DELIVERY_AS_ADDON' ) && LOADING_PICKPOINT_DELIVERY_AS_ADDON ) {
		return \WPickpoint\Pack\Plugin::app( $plugin );
	} else {
		return \WPICKPOINT\Plugin::app();
	}
}