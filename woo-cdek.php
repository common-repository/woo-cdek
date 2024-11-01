<?php
/**
 * Plugin Name: Woocommerce Shipping Details
 * Description: Added Shipping methods to your Woocommerce store
 * Plugin URI: https://wordpress.org/plugins/woo-cdek/
 * Description:
 * Author: Creative Motion
 * Version: 1.1.0
 * Text Domain: delivery-pack
 * Domain Path: /languages/
 * Author URI: https://cm-wp.com/
 * WC requires at least: 4.3
 * WC tested up to: 4.9
 * Framework Version: FACTORY_437_VERSION
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// @formatter:off
/**
 * -----------------------------------------------------------------------------
 * CHECK REQUIREMENTS
 * Check compatibility with php and wp version of the user's site. As well as checking
 * compatibility with other plugins from Webcraftic.
 * -----------------------------------------------------------------------------
 */

require_once( dirname( __FILE__ ) . '/libs/factory/core/includes/class-factory-requirements.php' );

// @formatter:off
$plugin_info = array(
	'prefix'          => 'wdpack_',
	'plugin_name'     => 'wdelivery_pack',
	'plugin_title'    => __( 'Delivery pack', 'delivery-pack' ),
	// PLUGIN SUPPORT
	'support_details' => array(
		'url'       => 'https://cmshippers.com/',
		'pages_map' => array(
			'features' => 'woocommerce-shipping-details', // {site}/premium-features
			'pricing'  => 'woocommerce-shipping-details', // {site}/woocommerce-shipping-details
			'support'  => 'support',                      // {site}/support
			'docs'     => 'doc-shipping-details'          // {site}/doc-shipping-details
		)
	),

	'has_updates'            => true,
	'updates_settings'       => array(
		'repository'        => 'wordpress',
		'slug'              => 'woo-cdek',
		'maybe_rollback'    => true,
		'rollback_settings' => array(
			'prev_stable_version' => '0.0.0'
		)
	),

	// PLUGIN PREMIUM SETTINGS
	'has_premium'            => true,
	'license_settings'       => array(
		'provider'         => 'freemius',
		'slug'             => 'woo-cdek-premium',
		'plugin_id'        => '7171',
		'public_key'       => 'pk_1ef70f36db8f4f0c65e86b8feb5db',
		'price'            => 29,
		'has_updates'      => true,
		'updates_settings' => array(
			'maybe_rollback'    => true,
			'rollback_settings' => array(
				'prev_stable_version' => '0.0.0'
			)
		)
	),
	// PLUGIN ADVERTS
	'render_adverts'         => true,
	'adverts_settings'       => array(
		'dashboard_widget' => true, // show dashboard widget (default: false)
		'right_sidebar'    => true, // show adverts sidebar (default: false)
		'notice'           => true, // show notice message (default: false)
	),
	// FRAMEWORK MODULES
	'load_factory_modules'   => array(
		array( 'libs/factory/bootstrap', 'factory_bootstrap_437', 'admin' ),
		array( 'libs/factory/forms', 'factory_forms_434', 'admin' ),
		array( 'libs/factory/pages', 'factory_pages_436', 'admin' ),
		array( 'libs/factory/clearfy', 'factory_clearfy_228', 'all' ),
		array( 'libs/factory/freemius', 'factory_freemius_124', 'all' ),
		array( 'libs/factory/adverts', 'factory_adverts_115', 'admin' ),
		array( 'libs/factory/logger', 'factory_logger_100', 'all' )
	),
	'load_plugin_components' => array(
		'cdek-delivery'         => array(
			'autoload'      => 'components/cdek-delivery/delivery-pack.php',
			'plugin_prefix' => 'WDCD_'
		),
		'peshkariki-delivery'   => array(
			'autoload'      => 'components/peshkariki-delivery/delivery-pack.php',
			'plugin_prefix' => 'WDPK_'
		),
		'zaberi-tovar-delivery' => array(
			'autoload'      => 'components/zaberi-tovar-delivery/delivery-pack.php',
			'plugin_prefix' => 'WDZT_'
		),
		'advanced-delivery'     => array(
			'autoload'      => 'components/advanced-delivery/delivery-pack.php',
			'plugin_prefix' => 'WDAD_'
		),
	)
);

$wdpack_compatibility = new Wbcr_Factory437_Requirements( __FILE__, array_merge( $plugin_info, array(
	'plugin_already_activate'          => defined( 'WDPC_PLUGIN_ACTIVE' ),
	'required_php_version'             => '7.0',
	'required_wp_version'              => '5.3.0',
	'required_clearfy_check_component' => false
) ) );

/**
 * If the plugin is compatible, then it will continue its work, otherwise it will be stopped,
 * and the user will throw a warning.
 */
if ( ! $wdpack_compatibility->check() ) {
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
define( 'WDPC_PLUGIN_ACTIVE', true );

// Plugin version
define( 'WDPC_PLUGIN_VERSION', $wdpack_compatibility->get_plugin_version() );
define( 'WDPC_FRAMEWORK_VER', 'FACTORY_437_VERSION' );

define( 'WDPC_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'WDPC_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'WDPC_PLUGIN_URL', plugins_url( null, __FILE__ ) );



/**
 * -----------------------------------------------------------------------------
 * PLUGIN INIT
 * -----------------------------------------------------------------------------
 */
try {

	// creating a plugin via the factory
	require_once( WDPC_PLUGIN_DIR . '/libs/factory/core/boot.php' );
	require_once( WDPC_PLUGIN_DIR . '/includes/class-plugin.php' );

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		new \WDPC\Plugin( __FILE__, array_merge( $plugin_info, array(
			'plugin_version'     => WDPC_PLUGIN_VERSION,
			'plugin_text_domain' => $wdpack_compatibility->get_text_domain(),
		) ) );
	} else {
		throw new Exception( __( 'The Woocommerce plugin is not installed', 'wd-cdek-delivery' ) );
	}
} catch ( Exception $e ) {
	// Plugin wasn't initialized due to an error
	define( 'WDPC_PLUGIN_THROW_ERROR', true );

	$wdpack_plugin_error_func = function () use ( $e ) {
		$error = sprintf( "<b>%s</b> plugin has stopped.<br><b>Error:</b> %s <br><b>Code</b>: %s", 'Woocommerce Shipping Details', $e->getMessage(), $e->getCode() );
		echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
	};

	add_action( 'admin_notices', $wdpack_plugin_error_func );
	add_action( 'network_admin_notices', $wdpack_plugin_error_func );
}
// @formatter:on

