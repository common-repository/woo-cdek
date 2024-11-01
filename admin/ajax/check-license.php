<?php
/**
 * Ajax handlers
 *
 * @author        Artem Prihodko <webtemyk@yandex.ru>
 * @copyright (c) 2020 Creative Motion
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Обработчик ajax запросов для проверки, активации, деактивации лицензионного ключа
 *
 * @since         2.0.7
 *
 */
function wdpc_check_license() {
	check_admin_referer( "wdpc_license_activate" );

	$plugin_instance = WDPC\Plugin::app();
	$action          = $plugin_instance->request->post( 'license_action', false, true );
	$license_key     = $plugin_instance->request->post( 'licensekey', null );

	if ( empty( $action ) || ! in_array( $action, [ 'activate', 'deactivate', 'sync', 'unsubscribe' ] ) ) {
		wp_send_json_error( [ 'error_message' => __( 'Licensing action not passed or this action is prohibited!', 'wbcr_factory_clearfy_228' ) ] );
		die();
	}

	$result          = null;
	$success_message = '';

	try {
		switch ( $action ) {
			case 'activate':
				if ( empty( $license_key ) || strlen( $license_key ) > 32 ) {
					wp_send_json_error( [ 'error_message' => __( 'License key is empty or license key too long (license key is 32 characters long)', 'wbcr_factory_clearfy_228' ) ] );
				} else {
					$plugin_instance->premium->activate( $license_key );
					$success_message = __( 'Your license has been successfully activated', 'wbcr_factory_clearfy_228' );
				}
				break;
			case 'deactivate':
				$plugin_instance->premium->deactivate();
				$success_message = __( 'The license is deactivated', 'wbcr_factory_clearfy_228' );
				break;
			case 'sync':
				$plugin_instance->premium->sync();
				$success_message = __( 'The license has been updated', 'wbcr_factory_clearfy_228' );
				break;
			case 'unsubscribe':
				$plugin_instance->premium->cancel_paid_subscription();
				$success_message = __( 'Subscription success cancelled', 'wbcr_factory_clearfy_228' );
				break;
		}
	} catch ( Exception $e ) {
		wp_send_json_error( [ 'error_message' => $e->getMessage() ] );
		die();
	}

	wp_send_json_success( [ 'message' => $success_message ] );

	die();
}

add_action( 'wp_ajax_wdpc_check_license', 'wdpc_check_license' );