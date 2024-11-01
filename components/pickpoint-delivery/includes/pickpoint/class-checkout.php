<?php

namespace WPickpoint\Delivery\ShippingMethod;

use Exception;
use WC_Cart;
use WC_Customer;
use WC_Order;
use WC_Shipping_Method;
use WPickpoint\Plugin;
use WP_Error;

/**
 * Используется на странице оформления заказа
 * Class Checkout
 *
 * @package WPickpoint\Delivery\ShippingMethod
 */
class Checkout {

	private $templateName = 'checkout-pickpoint-delivery.php';

	public static function register_hooks() {
		return new self();
	}

	private $pluginFields = [
		'wpickpoint_pvz_id'      => [],
		'wpickpoint_pvz_address' => []

	];

	private $selectedDeliveryMethod = [];

	public function __construct() {
		add_action( 'woocommerce_checkout_update_order_review', [ $this, 'saveDataOnOrderReview' ] );

		add_filter( 'woocommerce_checkout_fields', [ $this, 'checkout_fields' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 99999 );

		add_action( 'woocommerce_review_order_after_order_total', [ $this, 'displayShippingMethod' ] );

		add_action( 'woocommerce_checkout_process', [ $this, 'saveCheckoutFieldsValuesToSession' ] );
		add_filter( 'woocommerce_checkout_get_value', [ $this, 'getFieldsValues' ], 10, 2 );
		add_action( 'woocommerce_checkout_update_customer', [ $this, 'saveCheckoutFieldsValuesToCustomer' ], 10, 2 );
		add_action( 'woocommerce_form_field_hidden_pickpoint_delivery', [ $this, 'checkoutHiddenFieldType' ], 10, 4 );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'createPickpointDeliveryOrderDraft' ], 10, 2 );

		add_filter( 'woocommerce_shipping_packages', [ $this, 'updateShippingCost' ] );
		add_filter( 'woocommerce_update_cart_action_cart_updated', [ $this, 'clearShippingDataOnCartUpdate' ] );
		add_action( 'woocommerce_remove_cart_item', [ $this, 'clear_shipping_data_on_remove_cart_item' ] );

		add_action( 'woocommerce_order_status_cancelled', 'cancel_delivery_after_update_order_status' );
		add_action( 'before_delete_post', 'cancel_delivery_after_delete_order' );
		add_action( 'woocommerce_after_checkout_validation', [ $this, 'validateDeliveryMethod' ], 10, 2 );
	}

	public function displayShippingMethod() {
		$shippingMethods = WC()->session->get( 'chosen_shipping_methods' );

		if ( ! empty( $shippingMethods[0] ) ) {
			if ( strpos( $shippingMethods[0], Pickpoint::SHIPPING_METHOD_ID ) !== false ) {
				add_filter( 'woocommerce_locate_template', [ $this, 'locateTemplate' ], 10, 2 );
				wc_get_template( $this->templateName );
			}
		}
	}

	public function locateTemplate( $template, $templateName ) {
		if ( $templateName !== $this->templateName ) {
			return $template;
		}

		if ( file_exists( $template ) ) {
			return $template;
		}

		$template = WPICKPOINT_PLUGIN_DIR . '/templates/' . $templateName;

		return file_exists( $template ) ? $template : false;
	}

	/**
	 * Обрабатываем ошибки наших кастомных полей
	 *
	 * @param $fields
	 * @param WP_Error $errors
	 */
	public function validateDeliveryMethod( $fields, WP_Error $errors ) {
		$chosenShippingMethods = wc_get_chosen_shipping_method_ids();

		if ( ! empty( $chosenShippingMethods[0] ) && strpos( $chosenShippingMethods[0], Pickpoint::SHIPPING_METHOD_ID ) !== false ) {

			if ( $errors->has_errors() ) {
				$error_codes = $errors->get_error_codes();
				if ( in_array( 'wpickpoint_pvz_id_required', $error_codes ) || in_array( 'wpickpoint_pvz_address_required', $error_codes ) ) {
					$errors->remove( 'wpickpoint_pvz_id_required' );
					$errors->remove( 'wpickpoint_pvz_address_required' );

					$errors->add( 'wpickpoint_noselected_pvz', __( 'Чтобы воспользоваться доставкой Pickpoint, Вы должны выбрать пункт выдачи заказа!', 'pickpoint-delivery' ) );
				}
			}
		}
	}

	/**
	 * Очищаем сессию после того, как товар был удален из корзины
	 */
	public function clear_shipping_data_on_remove_cart_item() {
		$this->clear_session();
	}

	/**
	 * Очищаем сессию после того, как корзина была обновлена
	 */
	public function clearShippingDataOnCartUpdate( $cart_updated ) {
		$this->clear_session();

		return $cart_updated;
	}


	public function saveCheckoutFieldsValuesToSession() {
		$values    = [];
		$dataArray = WC()->checkout()->get_posted_data();

		foreach ( $this->pluginFields as $key => $_ ) {
			$values[ $key ] = isset( $dataArray[ $key ] ) ? wc_clean( wp_unslash( $dataArray[ $key ] ) ) : null;
		}

		$session             = WC()->session->get( Pickpoint::SHIPPING_METHOD_ID );
		$session['formData'] = $values;
		WC()->session->set( Pickpoint::SHIPPING_METHOD_ID, $session );
	}

	public function getFieldsValues( $inputValue, $inputName ) {
		if ( isset( $this->pluginFields[ $inputName ] ) ) {
			$sessionData = WC()->session->get( Pickpoint::SHIPPING_METHOD_ID );

			return isset( $sessionData['formData'][ $inputName ] ) ? $sessionData['formData'][ $inputName ] : null;
		}

		return $inputValue;
	}

	public function saveCheckoutFieldsValuesToCustomer( WC_Customer $customer, $data ) {
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $this->pluginFields, true ) ) {
				$customer->update_meta_data( $key, $value );
			}
		}
	}

	public function checkoutHiddenFieldType( $_, $key, $args, $value ) {
		$customAttributes = [];
		foreach ( $args['custom_attributes'] as $attr => $attrValue ) {
			$customAttributes[] = sprintf( '%s="%s"', esc_attr( $attr ), esc_attr( $attrValue ) );
		}

		return sprintf( '<input type="hidden" name="%s" id="%s" value="%s" %s />', esc_attr( $key ), esc_attr( $args['id'] ), esc_attr( $value ), implode( ' ', $customAttributes ) );
	}

	/**
	 * Создает заказ в сервисе Pickpoint
	 *
	 * @param $orderID
	 * @param $data
	 */
	public function createPickpointDeliveryOrderDraft( $orderID, $data, $is_change_status = false ) {
		$order     = wc_get_order( $orderID );
		$client    = Client::get_intance();
		$countries = new \WC_Countries();
		$insuare   = (int) Pickpoint::get_setting( 'insuare' );

		$pvz_id    = isset( $data['wpickpoint_pvz_id'] ) ? $data['wpickpoint_pvz_id'] : null;
		$firstName = isset( $data['billing_first_name'] ) ? $data['billing_first_name'] : '';
		$lastName  = isset( $data['billing_last_name'] ) ? $data['billing_last_name'] : '';
		$phone     = isset( $data['billing_phone'] ) ? $data['billing_phone'] : '';
		$email     = isset( $data['billing_email'] ) ? $data['billing_email'] : '';

		if ( isset( $data['ship_to_different_address'] ) && $data['ship_to_different_address'] ) {
			$firstName = isset( $data['shipping_first_name'] ) ? $data['shipping_first_name'] : $firstName;
			$lastName  = isset( $data['shipping_last_name'] ) ? $data['shipping_last_name'] : $lastName;
			$phone     = isset( $data['shipping_phone'] ) ? $data['shipping_phone'] : $phone;
			$email     = isset( $data['shipping_email'] ) ? $data['shipping_email'] : $email;
		}

		try {
			$items         = $is_change_status ? $data['items'] : WC()->cart->get_cart_contents();
			$order_items   = [];
			$assessedValue = 0;
			foreach ( $items as $item ) {
				$product       = wc_get_product( $item['product_id'] );
				$cost          = ceil( $item['line_total'] / $item['quantity'] );
				$order_items[] = [
					'ProductCode' => $product->get_sku(),
					'Name'        => $this->short_text( $product->get_title() ),
					'Price'       => $cost,
					'Quantity'    => $item['quantity'],
					'Vat'         => 0,
					'Description' => $this->short_text( $product->get_description() )
				];
				$assessedValue += $cost;
			}

			$request = $client->create_order( [
				"SenderCode"     => $orderID,
				"Description"    => "Заказ #{$orderID} в интернет магазине " . site_url() . ".",
				"RecipientName"  => $firstName . ' ' . $lastName,
				"PostamatNumber" => $pvz_id,
				"MobilePhone"    => $phone,
				"Email"          => $email,
				"PostageType"    => isset( $data['payment_method'] ) && "cod" === $data['payment_method'] ? '10003' : '10001',
				"GettingType"    => '102',
				"PayType"        => 1,
				"Sum"            => isset( $data['payment_method'] ) && "cod" === $data['payment_method'] ? $order->get_total() : 0,
				"DeliveryMode"   => 1,
				"InsuareValue"   => ! empty( $insuare ) ? ( ( $order->get_total() * $insuare ) / 100 ) : 0,
				"SenderCity"     => [
					"CityName"   => $countries->get_base_city(),
					"RegionName" => $countries->get_base_state(),
				],
				'Places'         => [
					[
						"BarCode"         => "",
						"Width"           => "0",
						"Height"          => "0",
						"Depth"           => "0",
						"Weight"          => "1",
						"CellStorageType" => 0,
						"SubEncloses"     => $order_items
					]

				]
			] );

			$edtn           = $request['CreatedSendings'][0]['EDTN'];
			$invoice_number = $request['CreatedSendings'][0]['InvoiceNumber'];

			update_post_meta( $orderID, '_pickpoint_edtn', $edtn );
			update_post_meta( $orderID, '_pickpoint_invoice_number', $invoice_number );
		} catch( \Exception $e ) {
			throw new \Exception( $e->getMessage(), $e->getCode() );
		}

		$clearSessionKeys = [
			'wpickpoint_pvz_id'      => null,
			'wpickpoint_pvz_address' => null
		];

		$session             = WC()->session->get( Pickpoint::SHIPPING_METHOD_ID );
		$session['formData'] = array_merge( $session['formData'], $clearSessionKeys );

		WC()->session->set( Pickpoint::SHIPPING_METHOD_ID, $session );
		WC()->customer->set_props( $clearSessionKeys );
		WC()->customer->save();
	}

	/**
	 * Обновление стоимости доставки
	 *
	 * Когда пользователь выберет пункт выдачи заказа или введет адрес доставки,
	 * произойдет расчет доставки и обновление стоимости.
	 *
	 * @param array $packages
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function updateShippingCost( $packages ) {
		foreach ( $packages as $index => &$package ) {
			foreach ( $package['rates'] as $key => &$rate ) {
				if ( strpos( $key, Pickpoint::SHIPPING_METHOD_ID ) !== false ) {
					$data = WC()->session->get( Pickpoint::SHIPPING_METHOD_ID );

					if ( isset( $data['formData'] ) && ! empty( $data['formData']['wpickpoint_pvz_id'] ) ) {
						$pvz_id = $data['formData']['wpickpoint_pvz_id'];
					}

					if ( ! empty( $pvz_id ) ) {
						try {
							$client                  = Client::get_intance();
							$cost                    = $client->get_tariff( $pvz_id );
							$delivery_period         = $client->get_delivery_period( $pvz_id );
							$data['delivery_cost']   = $cost;
							$data['delivery_period'] = $delivery_period;
							$rate->set_cost( $cost );

							WC()->session->set( Pickpoint::SHIPPING_METHOD_ID, $data );
						} catch( \Exception $e ) {
							throw new \Exception( $e->getMessage(), $e->getCode() );
						}
					}
				}
			}
		}

		return $packages;
	}

	/**
	 * Сохраняем кастомные поля на странице оформления заказа в сессию
	 *
	 * Это позволит избежать повторного заполнения полей пользователем,
	 * если он обновит страницу или столкнется с ошибкой при отправке заказа.
	 *
	 * @param string $post_data
	 */
	public function saveDataOnOrderReview( $post_data ) {
		$chosenShippingMethods = wc_get_chosen_shipping_method_ids();

		if ( ! empty( $chosenShippingMethods[0] ) && strpos( $chosenShippingMethods[0], Pickpoint::SHIPPING_METHOD_ID ) !== false ) {
			parse_str( $post_data, $post_data );
			$formData = [];

			foreach ( array_keys( $this->pluginFields ) as $field ) {
				if ( isset( $post_data[ $field ] ) ) {
					$formData[ $field ] = $post_data[ $field ];
				}
			}

			if ( ! empty( $formData ) ) {
				WC()->customer->set_props( $formData );
				$session             = WC()->session->get( Pickpoint::SHIPPING_METHOD_ID );
				$session['formData'] = $formData;
				WC()->session->set( Pickpoint::SHIPPING_METHOD_ID, $session );
				WC()->customer->save();
			}
		}
	}

	/**
	 * Подключем скрипты и стили для страницы оформления заказа
	 */
	public function enqueue_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_script( 'wpickpoint-postamat', '//pickpoint.ru/select/postamat.js', [ 'jquery' ] );

			wp_enqueue_script( 'wdyd-checkout-script', WPICKPOINT_PLUGIN_URL . '/assets/js/app.js', [ 'jquery' ], wdpp_get_current_plugin()->getPluginVersion() );
			wp_enqueue_style( 'wdyd-checkout-style', WPICKPOINT_PLUGIN_URL . '/assets/css/app.css', [], wdpp_get_current_plugin()->getPluginVersion() );

			wp_localize_script( 'wdyd-checkout-script', 'wWoocommercePickpointDeliveryIntegration', [
				'variables' => [
					'debug'   => Pickpoint::get_setting( 'debug' ) === 'yes',
					'ikn'     => Pickpoint::get_setting( 'ikn' ),
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				],
			] );
		}
	}

	/**
	 * Регистрируем кастомные поля для страницы оформления заказа
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function checkout_fields( $fields ) {
		$shippingMethods = WC()->session->get( 'chosen_shipping_methods' );

		if ( ! empty( $shippingMethods[0] ) ) {
			if ( strpos( $shippingMethods[0], Pickpoint::SHIPPING_METHOD_ID ) !== false ) {
				$this->pluginFields = [
					'wpickpoint_pvz_id'      => [
						'type'     => 'hidden_pickpoint_delivery',
						'priority' => 47,
						'required' => true
					],
					'wpickpoint_pvz_address' => [
						'type'     => 'hidden_pickpoint_delivery',
						'priority' => 47,
						'required' => true
					],
				];

				foreach ( $this->pluginFields as $field => $settings ) {
					$fields[ Pickpoint::SHIPPING_METHOD_ID ][ $field ] = $settings;
				}
			}
		}

		return $fields;
	}

	/**
	 * Очищает данные из сессии
	 *
	 * Требуется очистка после оформления заказа или обновления корзины.
	 */
	protected function clear_session() {
		$data = WC()->session->get( Pickpoint::SHIPPING_METHOD_ID );

		if ( isset( $data['formData']['wpickpoint_pvz_id'] ) ) {
			unset( $data['formData']['wpickpoint_pvz_id'] );
			unset( $data['formData']['wpickpoint_pvz_address'] );
			unset( $data['delivery_cost'] );
			unset( $data['delivery_period'] );
		}

		WC()->session->set( Pickpoint::SHIPPING_METHOD_ID, $data );
	}

	/**
	 *  Сокращает длинну текст до 190 символов
	 *
	 *  По документации Pickpoint, допускается передавать заголовок и описание товара,
	 *  размером не более 200 символов.
	 */
	protected function short_text( $text ) {
		$text = trim( strip_tags( $text ) );

		if ( mb_strlen( $text ) > 190 ) {
			$text = mb_substr( $text, 0, 190 );
			$text = preg_replace( '~(.*)\s[^\s]*$~s', '\\1...', $text ); // удаляем последнее слово, оно 99% неполное
		}

		return $text;
	}

}
