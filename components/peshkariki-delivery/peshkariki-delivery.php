<?php
/**
 * Plugin Name: Woocommerce Peshkariki Delivery
 * Plugin URI: https://cmshippers.com/peshkariki-delivery/
 * Description: This is a sample plugin that implements additional delivery functions for the Woocommerce store.
 * Author: CreativeMotion <info@cm-wp.com>
 * Version: 1.0.1
 * Text Domain: wd-peshkariki-delivery
 * Domain Path: /languages/
 * Author URI: https://cmshippers.com
 * Framework Version: FACTORY_437_VERSION
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * -----------------------------------------------------------------------------
 * CHECK REQUIREMENTS
 * Check compatibility with php and wp version of the user's site. As well as checking
 * compatibility with other plugins from Webcraftic.
 * -----------------------------------------------------------------------------
 */

require_once( dirname( __FILE__ ) . '/libs/factory/core/includes/class-factory-requirements.php' );

// @formatter:off
$wdpk_plugin_info = array(
    'prefix'               => 'wdpk_',
    'plugin_name'          => 'wdelivery-peshkariki',
    'plugin_title'         => __( 'Woocommerce Peshkariki Delivery', 'wd-peshkariki-delivery' ),

    // PLUGIN SUPPORT
    'support_details'      => array(
        'url'       => 'https://cmshippers.com',
        'pages_map' => array(
            'support' => 'support',
            'docs'    => 'doc-peshkariki-delivery',
            'pricing'    => 'peshkariki-delivery'
        )
    ),

    // PLUGIN PREMIUM SETTINGS
    'has_premium' => true,
    'license_settings' => array(
        'provider' => 'freemius',
        'slug' => 'peshkariki-delivery',
        'plugin_id' => '7066',
        'public_key' => 'pk_4f881c4e67c1016548f1e720ca972',
        'price' => 39,
        'has_updates' => true,
        'updates_settings' => array(
            'maybe_rollback' => true,
            'rollback_settings' => array(
                'prev_stable_version' => '0.0.0'
            )
        )
    ),

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
        array( 'libs/factory/adverts', 'factory_adverts_115', 'admin' )
    )
);

$wdpk_compatibility = new Wbcr_Factory437_Requirements( __FILE__, array_merge( $wdpk_plugin_info, array(
    'plugin_already_activate'          => defined( 'WDPK_PLUGIN_ACTIVE' ),
    'required_php_version'             => '5.6',
    'required_wp_version'              => '4.2.0',
    'required_clearfy_check_component' => false
) ) );

/**
 * If the plugin is compatible, then it will continue its work, otherwise it will be stopped,
 * and the user will throw a warning.
 */
if ( ! $wdpk_compatibility->check() ) {
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
define( 'WDPK_PLUGIN_ACTIVE', true );
define( 'WDPK_PLUGIN_SLUG', 'wd-peshkariki-delivery' );
define( 'WDPK_PLUGIN_VERSION', $wdpk_compatibility->get_plugin_version() );
define( 'WDPK_PLUGIN_DIR', __DIR__ );
define( 'WDPK_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'WDPK_PLUGIN_URL', plugins_url( null, __FILE__ ) );



/**
 * -----------------------------------------------------------------------------
 * PLUGIN INIT
 * -----------------------------------------------------------------------------
 */

require_once( WDPK_PLUGIN_DIR . '/vendor/autoload.php' );
require_once( WDPK_PLUGIN_DIR . '/libs/factory/core/boot.php' );
require_once( WDPK_PLUGIN_DIR . '/includes/class-plugin.php' );

try {
    new \WDPK\Delivery\Peshkariki\Plugin( __FILE__, array_merge( $wdpk_plugin_info, array(
        'plugin_version'     => WDPK_PLUGIN_VERSION,
        'plugin_text_domain' => $wdpk_compatibility->get_text_domain(),
    ) ) );
} catch ( Exception $e ) {
    // Plugin wasn't initialized due to an error
    define( 'WDPK_PLUGIN_THROW_ERROR', true );

    $wdpk_plugin_error_func = function () use ( $e ) {
        $error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Delivery boilerplate', $e->getMessage(), $e->getCode() );
        echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
    };

    add_action( 'admin_notices', $wdpk_plugin_error_func );
    add_action( 'network_admin_notices', $wdpk_plugin_error_func );
}
// @formatter:on
