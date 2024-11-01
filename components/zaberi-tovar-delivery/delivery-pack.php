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

if ( ! defined( 'WDZT_PLUGIN_ACTIVE' ) ) {

	define( 'WDZT_PLUGIN_VERSION', '1.0.0' );
	define( 'WDZT_TEXT_DOMAIN', 'wd-zaberi-tovar' );
	define( 'WDZT_PLUGIN_SLUG', 'wd-zaberi-tovar' );
	define( 'WDZT_PLUGIN_ACTIVE', true );

	// Этот плагин загружен, как аддон для плагина Delivery pack
	define( 'LOADING_ZABERITOVAR_DELIVERY_AS_ADDON', true );

	if ( ! defined( 'WDZT_PLUGIN_DIR' ) ) {
		define( 'WDZT_PLUGIN_DIR', dirname( __FILE__ ) );
	}

	if ( ! defined( 'WDZT_PLUGIN_BASE' ) ) {
		define( 'WDZT_PLUGIN_BASE', plugin_basename( __FILE__ ) );
	}

	if ( ! defined( 'WDZT_PLUGIN_URL' ) ) {
		define( 'WDZT_PLUGIN_URL', plugins_url( null, __FILE__ ) );
	}

	// Global scripts
	require_once( WDZT_PLUGIN_DIR . '/vendor/autoload.php' );
	require_once( WDZT_PLUGIN_DIR . '/includes/functions.php' );
	require_once( WDZT_PLUGIN_DIR . '/includes/3rd-party/class-delivery-pack-plugin.php' );

	try {
		new \WDPC\ZaberiTovar\Plugin( WDZT_PLUGIN_DIR . "/zaberi-tovar-delivery.php" );
	} catch ( Exception $e ) {
		$WDZT_plugin_error_func = function () use ( $e ) {
			$error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Delivery boilerplate', $e->getMessage(), $e->getCode() );
			echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
		};

		add_action( 'admin_notices', $WDZT_plugin_error_func );
		add_action( 'network_admin_notices', $WDZT_plugin_error_func );
	}
}


