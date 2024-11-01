<?php

namespace WBCR\Delivery\ZaberiTovar;

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
			die( __( 'The plugin requires Woocommerce', 'wd-zaberi-tovar' ) );
		}

		if ( ! class_exists( 'WC_Shipping_Method' ) ) {
			die( __( 'WC_Shipping_Method class not found', 'wd-zaberi-tovar' ) );
		}

		$package = Plugin::app()->premium->get_package_data();
		if ( empty( $package ) ) {
			$plugin_data = get_file_data( WP_PLUGIN_DIR . '/' . WDZT_PLUGIN_BASE, [ 'Version' => 'Version', 'FrameworkVersion' => 'FrameworkVersion' ] );
			$package     = [
				'basename'          => WDZT_PLUGIN_BASE,
				'version'           => $plugin_data['Version'],
				'framework_version' => isset( $plugin_data['FrameworkVersion'] ) ? $plugin_data['FrameworkVersion'] : null,
			];

			Plugin::app()->premium->update_package_data( $package );
		}
	}

	/**
	 * Runs activation actions.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
		Plugin::app()->premium->delete_package();
	}
}
