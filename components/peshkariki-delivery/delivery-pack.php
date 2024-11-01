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

if ( ! defined( 'WDPK_PLUGIN_ACTIVE' ) ) {

	define( 'WDPK_PLUGIN_VERSION', '1.0.0' );
	define( 'WDPK_TEXT_DOMAIN', 'wd-peshkariki-delivery' );
	define( 'WDPK_PLUGIN_SLUG', 'wd-peshkariki-delivery' );
	define( 'WDPK_PLUGIN_ACTIVE', true );

	// Этот плагин загружен, как аддон для плагина Delivery pack
	define( 'LOADING_PESHKARIKI_DELIVERY_AS_ADDON', true );

	if ( ! defined( 'WDPK_PLUGIN_DIR' ) ) {
		define( 'WDPK_PLUGIN_DIR', dirname( __FILE__ ) );
	}

	if ( ! defined( 'WDPK_PLUGIN_BASE' ) ) {
		define( 'WDPK_PLUGIN_BASE', plugin_basename( __FILE__ ) );
	}

	if ( ! defined( 'WDPK_PLUGIN_URL' ) ) {
		define( 'WDPK_PLUGIN_URL', plugins_url( null, __FILE__ ) );
	}

	// Global scripts
	require_once( WDPK_PLUGIN_DIR . '/includes/3rd-party/class-delivery-pack-plugin.php' );
	require_once( WDPK_PLUGIN_DIR . '/includes/functions.php' );

	try {
		new \WDPC\Peshkariki\Plugin( WDPK_PLUGIN_DIR . "/peshkariki-delivery.php" );
	} catch ( Exception $e ) {
		$WDPK_plugin_error_func = function () use ( $e ) {
			$error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Delivery boilerplate', $e->getMessage(), $e->getCode() );
			echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
		};

		add_action( 'admin_notices', $WDPK_plugin_error_func );
		add_action( 'network_admin_notices', $WDPK_plugin_error_func );
	}
}


