<?php

namespace WBCR\Delivery\Cdek;

use WBCR\Delivery\Base\CheckoutAjax as BaseCheckoutAjax;

/**
 * Class CheckoutAjax
 *
 * @package WBCR\Delivery\Cdek
 *
 * @author  Artem Prihodko <webtemyk@yandex.ru>
 * @version 1.0.0
 * @since   1.0.0
 */
class CheckoutAjax extends BaseCheckoutAjax {
	public function __construct() {
		parent::__construct();

		add_action( 'wp_ajax_wd-cdek-get-delivery-price', [ $this, 'get_delivery_price' ] );
		add_action( 'wp_ajax_nopriv_wd-cdek-get-delivery-price', [ $this, 'get_delivery_price' ] );
		add_action( 'wp_ajax_wd-cdek-delivery-city-autocomplete', [ $this, 'city_autocomplete' ] );
		add_action( 'wp_ajax_nopriv_wd-cdek-delivery-city-autocomplete', [ $this, 'city_autocomplete' ] );
		add_action( 'wp_ajax_wdcd_delivery_search', [ $this, 'delivery_search' ] );
		add_action( 'wp_ajax_nopriv_wdcd_delivery_search', [ $this, 'delivery_search' ] );
	}

	public function get_delivery_price() {
		$session = WC()->session->get( Cdek::SHIPPING_DELIVERY_ID );

		if ( ! isset( $session['formData']['wdcd_price'] ) ) {
			$session['formData']['wdcd_price'] = 0;
		}

		wp_send_json_success( [
			'price' => $session['formData']['wdcd_price'],
		] );
	}

	public function city_autocomplete() {
		if ( ! isset( $_POST['term'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Missing required field.', 'wc-cdek-delivery-integration' ) ] );
		}

		$term   = wp_unslash( $_POST['term'] );
		$client = new Client();

		try {
			$currentSuggestions = $client->location( $term );
			$suggestions        = [];

			foreach ( $currentSuggestions as $suggestion ) {
				$suggestions[ $suggestion['code'] ] = "{$suggestion['city']}, {$suggestion['region']}, {$suggestion['country']}";
			}

			wp_send_json_success( [
				'suggestions' => $suggestions,
			] );
		} catch ( \Exception $e ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Error during address request.', 'wc-cdek-delivery-integration' ),
			] );
		}
	}

	public function delivery_search() {
		if ( ! isset( $_POST['geo'], $_POST['method'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Missing required field.', 'wc-cdek-delivery-integration' ) ] );
		}

		$geo        = wp_unslash( $_POST['geo'] );
		$method     = wp_unslash( $_POST['method'] );
		$client     = new Client();
		$orderSizes = Helper::getOrderSizes();

		switch ( $method ) {
			case 'pickup':
				$orderMethod = 'PICKUP';
				break;
			case 'post':
				$orderMethod = 'POST';
				break;
			case 'todoor':
			default:
				$orderMethod = 'COURIER';
				break;
		}

		$assessedValue = 0;

		foreach ( WC()->cart->get_cart() as $item ) {
			$assessedValue += $item['line_total'];
		}
		$assessedValue = (int) round( $assessedValue * ( Cdek::$_settings['product_declared_price'] / 100 ) );

		if ( $assessedValue ) {
			$assessedValue = 1;
		}

		if ( $orderMethod === 'POST' ) {
			if ( ! isset( $_POST['index'] ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Missing required field.', 'wc-cdek-delivery-integration' ) ] );
			}

			try {
				$results = $client->deliveryOptions(
					$geo,
					$orderSizes['length'],
					$orderSizes['height'],
					$orderSizes['width'],
					$orderSizes['weight'],
					$orderMethod,
					date(
						'Y-m-d',
						strtotime( '+' . ( (int) Cdek::$_settings['adjust_delivery_date'] ) . ' days' )
					), [ 'assessedValue' => $assessedValue, 'itemsSum' => $assessedValue ] );
			} catch ( \Exception $exception ) {
				$results = [];
			}

			$pointsResults = $client->pickupPoints( $geo, $orderMethod );
			$results       = [
				'values' => $results,
				'points' => $pointsResults,
			];
		} else {
			try {
				$results = $client->deliveryOptions(
					$geo,
					$orderSizes['length'],
					$orderSizes['height'],
					$orderSizes['width'],
					$orderSizes['weight'],
					$orderMethod,
					date(
						'Y-m-d',
						strtotime( '+' . ( (int) Cdek::$_settings['adjust_delivery_date'] ) . ' days' )
					), [ 'assessedValue' => $assessedValue, 'itemsSum' => $assessedValue ] );
			} catch ( \Exception $exception ) {
				$results = [];
			}

			$partners = [];
			foreach ( $results as $result ) {
				$partners[ $result['delivery']['partner']['id'] ] = $result['delivery']['partner'];

			}
			if ( $orderMethod === 'PICKUP' ) {
				$pointsResults = $client->pickupPoints( $geo, $orderMethod );

				$results = [
					'values'   => $results,
					'points'   => $pointsResults,
					'partners' => $partners,
				];
			}
		}

		wp_send_json_success( $results );
	}
}