<?php
/**
 * Этот файл инициализирует этот плагин, как аддон для плагина Delivery pack.
 *
 * Файл будет подключен только в плагине Delivery pack, используя особый вариант загрузки. Это более простое решение
 * пришло на смену встроенной системы подключения аддонов в фреймворке.
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 2018 Webraftic Ltd
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WPICKPOINT_PLUGIN_ACTIVE' ) ) {

	define( 'WPICKPOINT_PLUGIN_VERSION', '1.0.4' );
	define( 'WPICKPOINT_TEXT_DOMAIN', 'pickpoint-delivery' );
	define( 'WPICKPOINT_PLUGIN_ACTIVE', true );

	// Этот плагин загружен, как аддон для плагина Clearfy
	define( 'LOADING_PICKPOINT_DELIVERY_AS_ADDON', true );

	if ( ! defined( 'WPICKPOINT_PLUGIN_DIR' ) ) {
		define( 'WPICKPOINT_PLUGIN_DIR', dirname( __FILE__ ) );
	}

	if ( ! defined( 'WPICKPOINT_PLUGIN_BASE' ) ) {
		define( 'WPICKPOINT_PLUGIN_BASE', plugin_basename( __FILE__ ) );
	}

	if ( ! defined( 'WPICKPOINT_PLUGIN_URL' ) ) {
		define( 'WPICKPOINT_PLUGIN_URL', plugins_url( null, __FILE__ ) );
	}

	try {
		// Global scripts
		require_once( WPICKPOINT_PLUGIN_DIR . '/includes/trait-deliver-method.php' );
		require_once( WPICKPOINT_PLUGIN_DIR . '/includes/3rd-party/class-delivery-pack-plugin.php' );
		require_once( WPICKPOINT_PLUGIN_DIR . '/includes/functions.php' );

		new \WPickpoint\Pack\Plugin( WPICKPOINT_PLUGIN_DIR . "/pickpoint-delivery.php" );
	} catch( Exception $e ) {
		$wpickpoint_plugin_error_func = function () use ( $e ) {
			$error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Delivery boilerplate', $e->getMessage(), $e->getCode() );
			echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
		};

		add_action( 'admin_notices', $wpickpoint_plugin_error_func );
		add_action( 'network_admin_notices', $wpickpoint_plugin_error_func );
	}
}


