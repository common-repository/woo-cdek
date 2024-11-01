<?php
/**
 * Plugin Name: Woocommerce Zaberi Tovar
 * Plugin URI: https://cmshippers.com/zaberi-tavar/
 * Description: This is a sample plugin that implements additional delivery functions for the Woocommerce store.
 * Author: CreativeMotion <info@cm-wp.com>
 * Version: 1.0.1
 * Text Domain: wd-zaberi-tovar
 * Domain Path: /languages/
 * Author URI: https://cmshippers.com
 * Framework Version: FACTORY_437_VERSION
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Developers who contributions in the development plugin:
 *
 * Alexander Kovalev
 * ---------------------------------------------------------------------------------
 * Full plugin development.
 *
 * Email:         alex.kovalevv@gmail.com
 * Personal card: https://alexkovalevv.github.io
 * Personal repo: https://github.com/alexkovalevv
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
$wdzt_plugin_info = array(
    'prefix'               => 'wdzt_',
    'plugin_name'          => 'wdelivery_zt',
    'plugin_title'         => __( 'Woocommerce Zaberi Tovar', 'wd-zaberi-tovar' ),

    // PLUGIN SUPPORT
    'support_details'      => array(
        'url'       => 'https://cmshippers.com',
        'pages_map' => array(
            'support' => 'support',
            'docs'    => 'doc-zaberi-tovar',
            'pricing'    => 'zaberi-tovar'
        )
    ),

    // PLUGIN PREMIUM SETTINGS
    'has_premium' => true,
    'license_settings' => array(
        'provider' => 'freemius',
        'slug' => 'zaberi-tovar-delivery',
        'plugin_id' => '7019',
        'public_key' => 'pk_3e4858f82a1bbe699eaa1659dd019',
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

$wdzt_compatibility = new Wbcr_Factory437_Requirements( __FILE__, array_merge( $wdzt_plugin_info, array(
    'plugin_already_activate'          => defined( 'WDZT_PLUGIN_ACTIVE' ),
    'required_php_version'             => '5.6',
    'required_wp_version'              => '4.2.0',
    'required_clearfy_check_component' => false
) ) );

/**
 * If the plugin is compatible, then it will continue its work, otherwise it will be stopped,
 * and the user will throw a warning.
 */
if ( ! $wdzt_compatibility->check() ) {
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
define( 'WDZT_PLUGIN_ACTIVE', true );
define( 'WDZT_PLUGIN_VERSION', $wdzt_compatibility->get_plugin_version() );
define( 'WDZT_PLUGIN_DIR', __DIR__ );
define( 'WDZT_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'WDZT_PLUGIN_URL', plugins_url( null, __FILE__ ) );



/**
 * -----------------------------------------------------------------------------
 * PLUGIN INIT
 * -----------------------------------------------------------------------------
 */

require_once( WDZT_PLUGIN_DIR . '/vendor/autoload.php' );
require_once( WDZT_PLUGIN_DIR . '/libs/factory/core/boot.php' );
require_once( WDZT_PLUGIN_DIR . '/includes/class-plugin.php' );

try {
    new \WBCR\Delivery\ZaberiTovar\Plugin( __FILE__, array_merge( $wdzt_plugin_info, array(
        'plugin_version'     => WDZT_PLUGIN_VERSION,
        'plugin_text_domain' => $wdzt_compatibility->get_text_domain(),
    ) ) );
} catch ( Exception $e ) {
    // Plugin wasn't initialized due to an error
    define( 'WDZT_PLUGIN_THROW_ERROR', true );

    $wdzt_plugin_error_func = function () use ( $e ) {
        $error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Woocommerce Zaberi Tovar', $e->getMessage(), $e->getCode() );
        echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
    };

    add_action( 'admin_notices', $wdzt_plugin_error_func );
    add_action( 'network_admin_notices', $wdzt_plugin_error_func );
}
// @formatter:on
