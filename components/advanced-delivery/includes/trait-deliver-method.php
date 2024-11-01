<?php


namespace WBCR\Delivery\Advanced;


/**
 * Trait DeliveryMethod
 *
 * @package WBCR\Delivery\Advanced
 *
 * @author  Alexander Gorenkov <g.a.androidjc2@ya.ru> <Tg:@alex_brin>
 * @version 1.0.0
 * @since   1.0.0
 */
trait DeliveryMethod {
	protected function registerDeliveryMethod( $shipping_method_id, $class ) {
		add_filter( 'woocommerce_shipping_methods', static function ( $methods ) use ( $shipping_method_id, $class ) {
			$methods[ $shipping_method_id ] = $class;

			return $methods;
		} );
	}
}