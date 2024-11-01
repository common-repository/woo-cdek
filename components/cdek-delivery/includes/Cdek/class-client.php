<?php

namespace WBCR\Delivery\Cdek;

use Exception;
use Requests;
use Requests_Response;
use RuntimeException;
use WC_Log_Levels;

/**
 * Class Client
 *
 * @package WBCR\Delivery\Cdek
 *
 * @author  Artem Prihodko <webtemyk@yandex.ru>
 * @version 1.0.0
 * @since   1.0.0
 */
class Client {
	const HOST = 'https://api.cdek.ru/v2';

	private $api_account;
	private $api_password;
	private $token;
	private $shopID;
	private $warehouseID;

	public function __construct( $settings = [] ) {
		$credentials = Cdek::getApiCredentials();
		$this->setCredentials( $credentials['account'], $credentials['password'] );

//		$shippingMethods = WC()->shipping()->get_shipping_methods();
//
//		if ( isset( $shippingMethods[ Cdek::SHIPPING_DELIVERY_ID ] ) ) {
//			$settings = $settings ?: $shippingMethods[ Cdek::SHIPPING_DELIVERY_ID ]->settings;
//
//			$account  = isset( $settings['api_account'] ) ? $settings['api_account'] : '';
//			$password = isset( $settings['api_password'] ) ? $settings['api_password'] : '';
//			$this->setCredentials( $account, $password );
//		}
	}

	/**
	 * @param $api_account
	 * @param $api_password
	 *
	 * @return $this
	 */
	public function setCredentials( $api_account, $api_password ) {
		$this->setApiAccount( $api_account );
		$this->setApiPassword( $api_password );
		$this->OauthToken();

		return $this;
	}

	/**
	 * @param $api_account
	 *
	 * @return $this
	 */
	public function setApiAccount( $api_account ) {
		$this->api_account = $api_account;

		return $this;
	}

	/**
	 * @param $api_password
	 *
	 * @return $this
	 */
	public function setApiPassword( $api_password ) {
		$this->api_password = $api_password;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getToken() {
		return $this->token;
	}

	/**
	 * Autorize. Get CDEK access token
	 * @return string
	 */
	public function OauthToken() {
		$response = $this->request( 'POST', 'oauth/token', [
			'grant_type'    => 'client_credentials',
			'client_id'     => $this->api_account,
			'client_secret' => $this->api_password,
		], [] );

		$token = '';
		if ( is_array( $response ) ) {
			if ( isset( $response['access_token'] ) ) {
				$token = $response['access_token'];
			}
		}

		$this->token = $token;

		return $token;
	}

	public function location( $term ) {
		$response = $this->request( 'GET', 'location/cities', [
			'city' => $term
		], [] );

		return $response;
	}

	public function pickupPoints( $locationId, $orderMethod, $pickupPointIds = null ) {
		$points    = is_array( $pickupPointIds ) ? implode( ',', $pickupPointIds ) : '';
		$cache_key = sprintf( "pickup_points_%s_%d_%s", $orderMethod, $locationId, $points );
		$response  = wp_cache_get( $cache_key, 'wd-cdek-delivery' );

		if ( ! $response ) {
			$response = $this->request( 'GET', 'deliverypoints', [
				'city_code' => $locationId
			], [] );

			wp_cache_set( $cache_key, $response, 'wd-cdek-delivery', 86400 ); // 86400 sec = 1 day
		}

		return $response;
	}

	public function deliveryOptions( $geo, $length, $height, $width, $weight, $deliveryType, $date, $cost ) {
		$response = $this->request( 'PUT', 'delivery-options', [], [
			'senderId'     => (int) $this->shopID,
			'to'           => [
				'geoId' => (int) $geo,
			],
			'dimensions'   => [
				'length' => (float) $length,
				'height' => (float) $height,
				'width'  => (float) $width,
				'weight' => (float) $weight,
			],
			'deliveryType' => $deliveryType,
			'shipment'     => [
				'date'              => $date,
				'warehouseId'       => $this->warehouseID,
				'includeNonDefault' => false,
			],
			'cost'         => $cost,
		] );

		foreach ( $response as $optionKey => $option ) {
			if ( isset( $option['cost'] ) ) {
				foreach ( $option['cost'] as $costKey => $_cost ) {
					$response[ $optionKey ]['cost'][ $costKey ] = ceil( $_cost );
				}
			}
		}

		return $response;
	}

	public function orders( $data ) {
		return $this->request( 'POST', 'orders', [], $data );
	}

	public function delete_order( $orderID ) {
		return $this->request( 'DELETE', "orders/$orderID", [], [] );
	}

	public function orderList( $ids ) {
		$ids = array_map( static function ( $id ) {
			return [
				'externalId' => $id,
			];
		}, $ids );

		return $this->request( 'PUT', 'orders/status', [], [
			'senderId' => $this->shopID,
			'orders'   => $ids,
		] );
	}

	/**
	 * @param string $method
	 * @param string $endpoint
	 * @param array $query
	 * @param array $body
	 * @param array $settings = [
	 *                         'host' => self::HOST,
	 *                         'timeout' => 10,
	 *                         ]
	 *
	 * @return array
	 * @throws RuntimeException
	 *
	 */
	public function request( $method, $endpoint, $query = [], $body = [], $settings = [] ) {
		Helper::precisionCheck( true );

		$settings += [
			'host'    => self::HOST,
			'timeout' => 10,
			'headers' => [],
		];

		$settings['headers'] = array_merge( $settings['headers'], [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->token,
		] );

		$uri = sprintf( "%s/%s", $settings['host'], $endpoint );

		Helper::log( 'Preparing API request', [
			$method,
			$endpoint,
			$query,
			$body,
			$settings
		], WC_Log_Levels::DEBUG );
		Helper::log( 'Request to Cdek.Delivery API', [ $uri ] );

		if ( ! empty( $query ) ) {
			$uri .= '?' . http_build_query( $query );
		}

		$response = wp_remote_request( $uri, [
			'method'  => strtoupper( $method ),
			'timeout' => $settings['timeout'],
			'headers' => $settings['headers'],
			'body'    => empty( $body ) ? '' : wp_json_encode( $body ),
		] );

		if ( is_wp_error( $response ) ) {
			Helper::log( 'Error when Cdek.Delivery API request', [], WC_Log_Levels::ERROR );
			throw new RuntimeException( $response->get_error_message() );
		}

		if ( ! empty( $response['body'] ) ) {
			$response = json_decode( (string) $response['body'], true );

			if ( isset( $response['error'] ) && isset( $response['error_description'] ) ) {
				Helper::log( "Cdek.Delivery API request Error: ({$response['error']}) {$response['error_description']}", [], WC_Log_Levels::ERROR );
			} else {
				Helper::log( 'Success Cdek.D API request' );
				Helper::log( 'Cdek response', $response, WC_Log_Levels::DEBUG );
			}

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new RuntimeException( json_last_error_msg() );
			}
		}

		Helper::precisionCheck( false );

		return $response;
	}
}
