<?php
/**
 * Factory Delivery Base
 *
 * @author        Alexander Gorenkov
 * @author        Artem Prihodko <webtemyk@yandex.ru>
 * @since         1.0.0
 *
 * @package       factory-delivery-base
 * @copyright (c) 2020, CreativeMotion
 *
 * @version       1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

# Регистрируем текстовый домен, для интернализации интерфейса модуля
load_plugin_textdomain( 'wbcr_factory_delivery_base', false, dirname( plugin_basename( __FILE__ ) ) . '/langs' );

if ( ! class_exists( "WBCR\\Delivery\\Base\\Delivery" ) ) {
	require_once "class-checkout-ajax.php";
	require_once "class-checkout.php";
	require_once "class-delivery.php";
	require_once "class-helper.php";
	require_once "class-order.php";
}
