<?php
/**
 * Этот файл инициализирует этот плагин, как аддон для плагина Delivery pack.
 *
 * Файл будет подключен только в плагине Delivery pack, используя особый вариант загрузки. Это более простое решение
 * пришло на смену встроенной системы подключения аддонов в фреймворке.
 *
 * @author        Artem Prihodko <webtemyk@yandex.ru>
 * @copyright (c) 2020 CreativeMotion
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WDAD_PLUGIN_ACTIVE' ) ) {

	define( 'WDAD_PLUGIN_VERSION', '1.0.0' );
	define( 'WDAD_TEXT_DOMAIN', 'wd-advanced-delivery' );
	define( 'WDAD_PLUGIN_ACTIVE', true );

	// Этот плагин загружен, как аддон для плагина Delivery pack
	define( 'LOADING_ADVANCED_DELIVERY_AS_ADDON', true );

	if ( ! defined( 'WDAD_PLUGIN_DIR' ) ) {
		define( 'WDAD_PLUGIN_DIR', dirname( __FILE__ ) );
	}

	if ( ! defined( 'WDAD_PLUGIN_BASE' ) ) {
		define( 'WDAD_PLUGIN_BASE', plugin_basename( __FILE__ ) );
	}

	if ( ! defined( 'WDAD_PLUGIN_URL' ) ) {
		define( 'WDAD_PLUGIN_URL', plugins_url( null, __FILE__ ) );
	}

	define( 'WDAD_ADVANCED_URL', plugins_url( null, __FILE__ ) . "/includes/advanced" );
	define( 'WDAD_ADVANCED_DIR', __DIR__ . "/includes/advanced" );

	try {
		// Global scripts
		require_once( WDAD_PLUGIN_DIR . '/includes/trait-deliver-method.php' );
		require_once( WDAD_PLUGIN_DIR . '/includes/3rd-party/class.advanced-delivery-plugin.php' );
		require_once( WDAD_PLUGIN_DIR . '/includes/functions.php' );

		new \WDPC\Advanced\Plugin( WDAD_PLUGIN_DIR . "/advanced-delivery.php" );
	} catch ( Exception $e ) {
		$wdad_plugin_error_func = function () use ( $e ) {
			$error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Delivery boilerplate', $e->getMessage(), $e->getCode() );
			echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
		};

		add_action( 'admin_notices', $wdad_plugin_error_func );
		add_action( 'network_admin_notices', $wdad_plugin_error_func );
	}
}


