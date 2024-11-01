<?php

namespace WPickpoint\Delivery\ShippingMethod;

use Exception;
use WPICKPOINT\Plugin;

class Client {

	const HOST = 'https://e-solution.pickpoint.ru/api/';

	private $ikn;

	private $login;

	private $password;

	private $settings;

	/**
	 * @var Client
	 */
	private static $_instance;

	public function __construct( $settings = [] ) {
		$shippingMethods = WC()->shipping()->get_shipping_methods();

		if ( isset( $shippingMethods[ Pickpoint::SHIPPING_METHOD_ID ] ) ) {
			$this->settings = $settings ? $settings : $shippingMethods[ Pickpoint::SHIPPING_METHOD_ID ]->settings;

			if ( empty( $this->settings['ikn'] ) || empty( $this->settings['login'] ) || empty( $this->settings['password'] ) ) {
				wdpp_get_current_plugin( false )->log( \WC_Log_Levels::ERROR, 'Client::__construct (Не установлен обязательные настройки для службы доставки Pickpoint! Пожалуйста, введите IKN, Login, Password в настройках плагина.)' );
				throw new \Exception( 'Не установлен обязательные настройки для службы доставки Pickpoint! Пожалуйста, введите IKN, Login, Password в настройках плагина.' );
			}

			$this->ikn      = $this->settings['ikn'];
			$this->login    = $this->settings['login'];
			$this->password = $this->settings['password'];
		}
	}

	/**
	 * @param array $settings
	 *
	 * @return Client
	 * @throws Exception
	 */
	public static function get_intance( $settings = [] ) {
		if ( static::$_instance ) {
			return static::$_instance;
		}

		static::$_instance = new static( $settings );

		return static::$_instance;
	}

	/**
	 * Авторизациия в сервисе Pickpoint
	 *
	 * @return string
	 */
	public function login( $force = false ) {
		$session = get_transient( wdpp_get_current_plugin()->getPrefix() . 'session' );

		if ( ! empty( $session ) && ! $force ) {
			return $session;
		}

		try {
			$request_data = $this->request( 'POST', 'login', [
				'Login'    => $this->login,
				'Password' => $this->password
			] );
			if ( isset( $request_data['SessionId'] ) ) {
				set_transient( wdpp_get_current_plugin()->getPrefix() . 'session', $request_data['SessionId'], HOUR_IN_SECONDS * 12 );

				return $request_data['SessionId'];
			}
		} catch ( \Exception $e ) {
			throw new \Exception( $e->getMessage(), $e->getCode() );
		}

		wdpp_get_current_plugin( false )->log( \WC_Log_Levels::ERROR, 'Meтод Client::login (Провалена попытка авторизации)!' );
		throw new \Exception( 'Провалена попытка авторизации!' );
	}

	/**
	 * Получает стоимость доставки от Pickpoint
	 *
	 * Запрос кешируется на 1 час.
	 *
	 * @param string $pvz_id Id пункта выдачи заказа
	 *
	 * @return int
	 * @throws Exception
	 */
	public function get_tariff( $pvz_id ) {

		$countries = new \WC_Countries();
		$packages  = $this->settings['packages'];
		$length    = isset( $packages['Length'] ) ? $packages['Length'] : '';
		$depth     = isset( $packages['Depth'] ) ? $packages['Depth'] : '';
		$width     = isset( $packages['Width'] ) ? $packages['Width'] : '';
		$cache_key = $this->generate_cache_key( $depth . $width . $length . $countries->get_base_city() . $pvz_id );

		$tariff = get_transient( $cache_key );

		if ( ! empty( $tariff ) ) {
			return $tariff;
		}

		try {
			$tariff  = 0;
			$session = $this->login();

			if ( empty( $pvz_id ) ) {
				wdpp_get_current_plugin( false )->log( \WC_Log_Levels::ERROR, 'Meтод Client::get_tariff (Не передан ID пункта выдачи в Pickpoint)!' );
				throw new \Exception( 'Не передан ID пункта выдачи в Pickpoint!' );
			}

			$request_data = $this->request( 'POST', 'calctariff', [
				'SessionId'  => $session,
				'IKN'        => $this->ikn,
				'FromCity'   => $countries->get_base_city(),
				'FromRegion' => $countries->get_base_state(),
				'PTNumber'   => $pvz_id,
				'Length'     => $length,
				'Depth'      => $depth,
				'Width'      => $width,
				'Weight'     => 1,
			] );

			$services = ! empty( $request_data['Services'] ) ? $request_data['Services'] : [];

			if ( ! empty( $services ) && is_array( $services ) ) {
				$tariff = $services[0]['Tariff'];

				set_transient( $cache_key, $tariff, HOUR_IN_SECONDS );
			}
		} catch( \Exception $e ) {
			throw new \Exception( $e->getMessage(), $e->getCode() );
		}

		return $tariff;
	}

	/**
	 * Получает сроки доставки от Pickpoint
	 *
	 * Запрос кешируется на 1 час.
	 *
	 * @param string $pvz_id Id пункта выдачи заказа
	 *
	 * @return string
	 * @throws Exception
	 */
	public function get_delivery_period( $pvz_id ) {
		$countries = new \WC_Countries();
		$cache_key = $this->generate_cache_key( $countries->get_base_city() . $countries->get_base_state() . $pvz_id );

		$period = get_transient( $cache_key );

		if ( ! empty( $period ) ) {
			return $period;
		}

		try {
			$session = $this->login();

			if ( empty( $pvz_id ) ) {
				wdpp_get_current_plugin( false )->log( \WC_Log_Levels::ERROR, 'Meтод Client::get_delivery_period (Не передан ID пункта выдачи в Pickpoint)!' );
				throw new \Exception( 'Не передан ID пункта выдачи в Pickpoint!' );
			}

			$request_data = $this->request( 'POST', 'getzone', [
				'SessionId'  => $session,
				'IKN'        => $this->ikn,
				'FromCity'   => $countries->get_base_city(),
				'FromRegion' => $countries->get_base_state(),
				'ToPT'       => $pvz_id
			] );

			if ( ! empty( $request_data['Zones'] ) ) {
				$period_min = $request_data['Zones'][0]['DeliveryMin'];
				$period_max = $request_data['Zones'][0]['DeliveryMax'];
				$period     = $period_max;

				if ( $period_min !== $period_max ) {
					$period = $period_min . '-' . $period_max;
				}

				set_transient( $cache_key, $period, HOUR_IN_SECONDS );

				return $period;
			}
		} catch( \Exception $e ) {
			throw new \Exception( $e->getMessage(), $e->getCode() );
		}

		throw new \Exception( 'Неизвестная ошибка, при попытке получить сроки доставки в Pickpoint!' );
	}

	public function create_order( $invoice = [] ) {
		try {
			$session = $this->login();

			wdpp_get_current_plugin( false )->log( \WC_Log_Levels::INFO, "Попытка создания заказа в Pickpoint! " );
			wdpp_get_current_plugin( false )->log( \WC_Log_Levels::INFO, var_export( $invoice, true ) );

			$request_data = $this->request( 'POST', 'CreateShipment', [
				'SessionId' => $session,
				'Sendings'  => [
					[
						'IKN'     => $this->ikn,
						'EDTN'    => ( new \DateTime( 'now' ) )->getTimestamp(),
						'Invoice' => $invoice,

					]
				]
			] );

			if ( empty( $request_data['CreatedSendings'] ) ) {
				wdpp_get_current_plugin( false )->log( \WC_Log_Levels::ERROR, "Meтод Client::get_delivery_period (невозможно зарегистрировать отправление в Pickpoint, из-за неизвестной ошибки.)" );
				throw new Exception( 'Невозможно зарегистрировать отправление в Pickpoint, из-за неизвестной ошибки.' );
			}

			wdpp_get_current_plugin( false )->log( \WC_Log_Levels::INFO, "Заказ #{$request_data['CreatedSendings'][0]['InvoiceNumber']} успешно создан! " );

			return $request_data;
		} catch( \Exception $e ) {
			throw new \Exception( $e->getMessage(), $e->getCode() );
		}
	}

	public function cancel_order( $invoice_number ) {
		try {
			$session = $this->login();

			wdpp_get_current_plugin( false )->log( \WC_Log_Levels::INFO, "Попытка отмены заказа #{$invoice_number} в Pickpoint! " );

			if ( empty( $invoice_number ) ) {
				throw new \Exception( 'Не передан invoice_number для отмены заказа Pickpoint!' );
			}

			$request_data = $this->request( 'POST', 'rejectInvoice', [
				'SessionId'     => $session,
				'IKN'           => $this->ikn,
				'InvoiceNumber' => $invoice_number
			] );

			wdpp_get_current_plugin( false )->log( \WC_Log_Levels::INFO, "Заказ #{$invoice_number} отменен! " );
		} catch( \Exception $e ) {
			throw new \Exception( $e->getMessage(), $e->getCode() );
		}
	}

	protected function generate_cache_key( $salt ) {
		return wdpp_get_current_plugin()->getPrefix() . 'delivery_period_' . md5( $salt );
	}

	/**
	 * Осуществляет запрос к сервису Pickpoint, с обработкой ошибок
	 *
	 * @param string $method
	 * @param string $endpoint
	 * @param array $params
	 *
	 * @return array
	 * @throws Exception
	 */
	public function request( $method, $endpoint, array $params = [] ) {

		if ( empty( $endpoint ) ) {
			wdpp_get_current_plugin( false )->log( \WC_Log_Levels::ERROR, 'Не передана конечная точка запроса!' );
			throw new \Exception( 'Не передана конечная точка запроса!' );
		}

		$request_url = self::HOST . '/' . $endpoint;

		wdpp_get_current_plugin( false )->log( \WC_Log_Levels::INFO, "Попытка отправить {$method} запрос на {$request_url}! " );

		$request = wp_remote_request( $request_url, [
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => json_encode( $params ),
			'method'      => $method,
			'data_format' => 'body'
		] );

		if ( is_wp_error( $request ) ) {
			throw new \Exception( $request->get_error_message(), $request->get_error_code() );
		}

		$request_body = wp_remote_retrieve_body( $request );

		if ( ! empty( $request_body ) ) {
			$request_data = json_decode( $request_body, ARRAY_A );

			if ( isset( $request_data['ErrorCode'] ) && $request_data['ErrorCode'] != 0 && ! isset( $params['loop_request'] ) ) {
				if ( - 2014 === $request_data['ErrorCode'] ) {
					wdpp_get_current_plugin( false )->log( \WC_Log_Levels::ERROR, "Сессия истекла! Попытка повторной авторизации. " );

					try {
						$session                = $this->login( true );
						$params['SessionId']    = $session;
						$params['loop_request'] = true;

						return $this->request( $method, $endpoint, $params );
					} catch ( \Exception $e ) {
						delete_transient( wdpp_get_current_plugin()->getPrefix() . 'session' );
						throw new \Exception( $e->getMessage(), $e->getCode() );
					}
				}
				wdpp_get_current_plugin( false )->log( \WC_Log_Levels::ERROR, $request_data['Error'] );
				throw new \Exception( $request_data['Error'], $request_data['ErrorCode'] );
			}

			wdpp_get_current_plugin( false )->log( \WC_Log_Levels::INFO, "Запрос выполнен успешно! " );
			wdpp_get_current_plugin( false )->log( \WC_Log_Levels::INFO, "Результат: " . var_export( $request_data, true ) );

			return $request_data;
		}

		wdpp_get_current_plugin( false )->log( \WC_Log_Levels::ERROR, 'Запрос выполнен с ошибкой!' );
		throw new \Exception( 'Запрос выполнен с ошибкой!' );
	}
}
