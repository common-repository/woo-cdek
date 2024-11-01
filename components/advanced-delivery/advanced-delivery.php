<?php
/**
 * Plugin Name: Woocommerce Advanced Delivery
 * Plugin URI: https://cmshippers.com/advanced-delivery
 * Description: This is a sample plugin that implements additional delivery functions for the Woocommerce store.
 * Author: CreativeMotion <support@cm-wp.com>
 * Version: 1.0.0
 * Text Domain: wd-advanced-delivery
 * Domain Path: /languages/
 * Author URI: https://cmshippers.com
 * Framework Version: FACTORY_437_VERSION
 * WC requires at least: 4.3
 * WC tested up to:      4.9

 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Developers who contributions in the development plugin:
 *
 * Artem Prihodko
 * ---------------------------------------------------------------------------------
 * Full plugin development.
 *
 * Email:         webtemyk@yandex.ru
 * Personal repo: https://github.com/temyk
 * ---------------------------------------------------------------------------------
 */

/**
 * -----------------------------------------------------------------------------
 * CHECK REQUIREMENTS
 * Check compatibility with php and wp version of the user's site. As well as checking
 * compatibility with other plugins from Webcraftic.
 * -----------------------------------------------------------------------------
 */


require_once( dirname( __FILE__ ) . '/libs/factory/core/includes/class-factory-requirements.php' );

// @formatter:off
$wdad_plugin_info = array(
	'prefix'               => 'wdad_',
	'plugin_name'          => 'wdelivery_advanced',
	'plugin_title'         => __( 'Woocommerce Advanced Delivery', 'wd-advanced-delivery' ),

	// PLUGIN SUPPORT
	'support_details'      => array(
		'url'       => 'https://cmshippers.com',
		'pages_map' => array(
			'support' => 'support',
			'docs'    => 'doc-advanced-delivery',
			'pricing' => 'advanced-delivery'
		)
	),

	// PLUGIN PREMIUM SETTINGS
	'has_premium'          => false,
	/*
	'license_settings'     => array(
		'provider'         => 'freemius',
		'slug'             => 'advanced-delivery',
		'plugin_id'        => '7020',
		'public_key'       => 'pk_243aecbb081aab0d3944789f5dd1a',
		'price'            => 39,
		'has_updates'      => true,
		'updates_settings' => array(
			'maybe_rollback'    => true,
			'rollback_settings' => array(
				'prev_stable_version' => '0.0.0'
			)
		)
	),
	*/

	// PLUGIN ADVERTS
	'render_adverts'       => true,
	'adverts_settings'     => array(
		'dashboard_widget' => true,            // show dashboard widget (default: false)
		'right_sidebar'    => true,               // show adverts sidebar (default: false)
		'notice'           => true,                      // show notice message (default: false)
	),

	// FRAMEWORK MODULES
	'load_factory_modules' => array(
		array( 'libs/factory/bootstrap', 'factory_bootstrap_437', 'admin' ),
		array( 'libs/factory/forms', 'factory_forms_434', 'admin' ),
		array( 'libs/factory/pages', 'factory_pages_436', 'admin' ),
		array( 'libs/factory/clearfy', 'factory_clearfy_228', 'all' ),
		array( 'libs/factory/freemius', 'factory_freemius_124', 'all' ),
		array( 'libs/factory/adverts', 'factory_adverts_115', 'admin' ),
		array( 'libs/factory/logger', 'factory_logger_100', 'all' ),
	)
);

$wdad_compatibility = new Wbcr_Factory437_Requirements( __FILE__, array_merge( $wdad_plugin_info, array(
	'plugin_already_activate'          => defined( 'WDAD_PLUGIN_ACTIVE' ),
	'required_php_version'             => '7.0',
	'required_wp_version'              => '5.3.0',
	'required_clearfy_check_component' => false
) ) );

/**
 * If the plugin is compatible, then it will continue its work, otherwise it will be stopped,
 * and the user will throw a warning.
 */
if ( ! $wdad_compatibility->check() ) {
	return;
}

/**
 * -----------------------------------------------------------------------------
 * CONSTANTS
 * Install frequently used constants and constants for debugging, which will be
 * removed after compiling the plugin.
 * -----------------------------------------------------------------------------
 */

// This plugin is activated
define( 'WDAD_PLUGIN_ACTIVE', true );
define( 'WDAD_PLUGIN_VERSION', $wdad_compatibility->get_plugin_version() );
define( 'WDAD_PLUGIN_DIR', __DIR__ );
define( 'WDAD_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'WDAD_PLUGIN_URL', plugins_url( null, __FILE__ ) );



/**
 * -----------------------------------------------------------------------------
 * PLUGIN INIT
 * -----------------------------------------------------------------------------
 */
define( 'WDAD_ADVANCED_URL', plugins_url( null, __FILE__ ) . "/includes/advanced" );
define( 'WDAD_ADVANCED_DIR', __DIR__ . "/includes/advanced" );

require_once( WDAD_PLUGIN_DIR . '/libs/factory/core/boot.php' );
require_once( WDAD_PLUGIN_DIR . '/includes/class-plugin.php' );
require_once( WDAD_PLUGIN_DIR . '/includes/functions.php' );

try {
	new \WBCR\Delivery\Advanced\Plugin( __FILE__, array_merge( $wdad_plugin_info, array(
		'plugin_version'     => WDAD_PLUGIN_VERSION,
		'plugin_text_domain' => $wdad_compatibility->get_text_domain(),
	) ) );
} catch ( Exception $e ) {
	// Plugin wasn't initialized due to an error
	define( 'WDAD_PLUGIN_THROW_ERROR', true );

	$wdad_plugin_error_func = function () use ( $e ) {
		$error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Delivery boilerplate', $e->getMessage(), $e->getCode() );
		echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
	};

	add_action( 'admin_notices', $wdad_plugin_error_func );
	add_action( 'network_admin_notices', $wdad_plugin_error_func );
}
// @formatter:on
