<?php

namespace WBCR\Delivery\Advanced;

/**
 * Activator for the clearfy
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 09.09.2017, Webcraftic
 * @see           Factory437_Activator
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation extends \Wbcr_Factory437_Activator {

	/**
	 * Runs activation actions.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			die( __( 'The plugin requires Woocommerce', 'wd-advanced-delivery' ) );
		}

		if ( ! class_exists( 'WC_Shipping_Method' ) ) {
			die( __( 'WC_Shipping_Method class not found', 'wd-advanced-delivery' ) );
		}
	}

	/**
	 * Runs activation actions.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
	}
}
