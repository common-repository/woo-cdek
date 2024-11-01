<?php

namespace WBCR\Delivery\Cdek;

use Exception;
use WBCR\Delivery\Base\Checkout as BaseCheckout;
use WBCR\Delivery\Cdek\Plugin;
use WP_Error;

/**
 * Class Checkout
 *
 * @package WBCR\Delivery\Cdek
 *
 * @author  Artem Prihodko <webtemyk@yandex.ru>
 * @version 1.0.0
 * @since   1.0.0
 */
class Checkout extends BaseCheckout {
	const DELIVERY_OBJECT = Cdek::class;
	const AJAX_HANDLER = CheckoutAjax::class;
	const ORDER_OBJECT = Order::class;

	const PLUGIN_URL = WDCD_PLUGIN_URL;
	const PLUGIN_DIR = WDCD_PLUGIN_DIR;

	/**
	 * bool, используется ли шаблон на странице чекаута
	 * @type bool
	 */
	const USE_TEMPLATE = true;

	const TEMPLATE_FILENAME = 'checkout.php';
	const TEMPLATE_FILENAME_PATH = 'includes/Cdek/views';

	public $price_field = 'wdcd_price';

	private $selectedDeliveryMethod = [];

	public function __construct() {
		parent::__construct();

		add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate_delivery_method' ], 10, 2 );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'save_cdek_delivery_order' ], 9, 2 );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'create_cdek_delivery_order' ], 10, 2 );

		add_action( 'woocommerce_remove_cart_item', [ $this, 'remove_cart_item' ], 10, 2 );
		add_filter( 'woocommerce_update_cart_action_cart_updated', [
			$this,
			'update_cart_action_cart_updated'
		], 10, 1 );

		static::$app = wdcd_get_current_plugin();
	}

	protected function get_plugin_fields() {
		return [
			'wdcd_city',
			'wdcd_ship_city',
			'wdcd_geo',
			'wdcd_delivery_method',
			'wdcd_price',
			'wdcd_pickup_point',
			'wdcd_tariff_id',
		];
	}

	public function checkout_fields( $fields ) {
		$fields = parent::checkout_fields( $fields );


		$fields['billing']['wdcd_city'] = [
			'type'              => 'select',
			'options'           => [
				'' => '',
			],
			'label'             => __( 'City', 'wd-cdek-delivery' ),
			'placeholder'       => __( 'City', 'wd-cdek-delivery' ),
			'required'          => true,
			'class'             => [ 'form-row-wide', 'address-field', 'update_totals_on_change' ],
			'autocomplete'      => 'city',
			'priority'          => 70,
			'custom_attributes' => [
				'data-type' => 'locality',
			],
		];

		$fields['shipping']['wdcd_ship_city'] = [
			'type'              => 'select',
			'options'           => [
				'' => '',
			],
			'label'             => __( 'City', 'wd-cdek-delivery' ),
			'placeholder'       => __( 'City', 'wd-cdek-delivery' ),
			'required'          => true,
			'class'             => [ 'form-row-wide', 'address-field', 'update_totals_on_change' ],
			'autocomplete'      => 'city',
			'priority'          => 70,
			'custom_attributes' => [
				'data-type' => 'locality_shipping',
			],
		];

		$chosenShippingMethods = wc_get_chosen_shipping_method_ids();
		if ( in_array( Cdek::SHIPPING_DELIVERY_ID, $chosenShippingMethods ) ) {

			$fields['billing']['wdcd_delivery_method'] = [
				'type'              => 'hidden_wbcr_delivery',
				'priority'          => 35,
				'custom_attributes' => [
					'data-type' => 'delivery_method',
				],
			];

			$fields['billing']['wdcd_geo'] = [
				'type'              => 'hidden_wbcr_delivery',
				'priority'          => 42,
				'custom_attributes' => [
					'data-type' => 'geo_id',
				],
			];

			$fields['billing']['wdcd_tariff_id'] = [
				'type'              => 'hidden_wbcr_delivery',
				'priority'          => 45,
				'custom_attributes' => [
					'data-type' => 'tariff_id',
				],
			];

			$fields['billing']['wdcd_pickup_point'] = [
				'type'              => 'hidden_wbcr_delivery',
				'priority'          => 47,
				'custom_attributes' => [
					'data-type' => 'pickup_point',
				],
			];

		} else {
			$fields['billing']['wdcd_city']['required']       = false;
			$fields['shipping']['wdcd_ship_city']['required'] = false;
		}

		return $fields;
	}

	public function enqueue_scripts_hook_handler() {
		if ( is_checkout() ) {
			$this->enqueue_scripts_checkout();
		}

		$this->enqueue_scripts();
	}

	public function enqueue_scripts_checkout() {
		$plugin = static::$app;

		//CDEK widget
		wp_enqueue_script(
			'wdcd-cdek-widget',
			static::PLUGIN_URL . '/assets/js/widget.js',
			[ 'jquery' ],
			$plugin->getPluginVersion()
		);

		wp_enqueue_script(
			'wdcd-checkout-script',
			static::PLUGIN_URL . '/assets/js/app.js',
			[ 'jquery' ],
			$plugin->getPluginVersion()
		);
		wp_enqueue_style(
			'wdcd-checkout-style',
			WDCD_PLUGIN_URL . '/assets/css/app.css',
			[],
			$plugin->getPluginVersion()
		);

		wp_enqueue_style(
			'wdcd-checkout-style-cdek',
			WDCD_PLUGIN_URL . '/assets/css/cdek.css',
			[],
			$plugin->getPluginVersion()
		);

		$goods = Helper::getOrderSizes();
		wp_localize_script(
			'wdcd-checkout-script',
			'wWoocommerceCdekDeliveryIntegration',
			[
				'widget'    => [
					'path'         => WDCD_PLUGIN_URL . "/includes/Cdek/scripts/",
					'servicepath'  => site_url( 'wdcd-cdek-service' ),
					'templatepath' => site_url( 'wdcd-cdek-tmpl' ),
					'goods_sizes'  => $goods,
					'maps_api_key' => Cdek::settings()['yamaps_api_key'],
					'city_from'    => WC()->countries->get_base_city(),
				],
				'variables' => [
					'debug'              => Cdek::settings()['debug'] === 'yes',
					'show_delivery_date' => Cdek::settings()['show_delivery_date'] === 'yes',
					'ajaxurl'            => admin_url( 'admin-ajax.php' ),
				],
				'strings'   => [
					'weekday' => [
						'monday'    => __( 'Monday', 'wd-cdek-delivery' ),
						'tuesday'   => __( 'Tuesday', 'wd-cdek-delivery' ),
						'wednesday' => __( 'Wednesday', 'wd-cdek-delivery' ),
						'thursday'  => __( 'Thursday', 'wd-cdek-delivery' ),
						'friday'    => __( 'Friday', 'wd-cdek-delivery' ),
						'saturday'  => __( 'Saturday', 'wd-cdek-delivery' ),
						'sunday'    => __( 'Sunday', 'wd-cdek-delivery' ),
					],

					'delivery_name'             => __( 'СДЭК', 'wd-cdek-delivery' ),
					'house_short'               => __( 'h.', 'wd-cdek-delivery' ),
					'housing_short'             => __( 'build.', 'wd-cdek-delivery' ),
					'apartment_short'           => __( 'apt./office', 'wd-cdek-delivery' ),
					'currency_short'            => __( 'rub.', 'wd-cdek-delivery' ),
					'courier'                   => __( 'Courier', 'wd-cdek-delivery' ),
					'pickup'                    => __( 'Pickup', 'wd-cdek-delivery' ),
					'post'                      => __( 'Post', 'wd-cdek-delivery' ),
					'est_delivery_date'         => __( 'Estimated Delivery Date', 'wd-cdek-delivery' ),
					'select'                    => __( 'Select', 'wd-cdek-delivery' ),
					'delivery_service'          => __( 'Delivery service', 'wd-cdek-delivery' ),
					'delivery_method'           => __( 'Delivery method', 'wd-cdek-delivery' ),
					'pickup_point_address'      => __( 'Pickup point address', 'wd-cdek-delivery' ),
					'pickup_point_schedule'     => __( 'Pickup point schedule', 'wd-cdek-delivery' ),
					'pickup_point_instruction'  => __( 'Pickup point instruction', 'wd-cdek-delivery' ),
					'delivery_address'          => __( 'Delivery address', 'wd-cdek-delivery' ),
					'delivery_price'            => __( 'Delivery price', 'wd-cdek-delivery' ),
					'change_delivery_terms'     => __( 'Try changing your shipping terms.', 'wd-cdek-delivery' ),
					'i18n_no_matches'           => __( 'No matches found', 'wd-cdek-delivery' ),
					'i18n_ajax_error'           => __( 'Loading failed', 'wd-cdek-delivery' ),
					'i18n_input_too_short_1'    => __( 'Please enter 1 or more characters', 'wd-cdek-delivery' ),
					'i18n_input_too_short_n'    => __( 'Please enter %qty% or more characters', 'wd-cdek-delivery' ),
					'i18n_input_too_long_1'     => __( 'Please delete 1 character', 'wd-cdek-delivery' ),
					'i18n_input_too_long_n'     => __( 'Please delete %qty% characters', 'wd-cdek-delivery' ),
					'i18n_selection_too_long_1' => __( 'You can only select 1 item', 'wd-cdek-delivery' ),
					'i18n_selection_too_long_n' => __( 'You can only select %qty% items', 'wd-cdek-delivery' ),
					'i18n_load_more'            => __( 'Loading more results...', 'wd-cdek-delivery' ),
					'i18n_searching'            => __( 'Searching...', 'wd-cdek-delivery' ),

					'i18n_where_is'       => __( 'Where is', 'wd-cdek-delivery' ),
					'i18n_timetable_work' => __( 'Timetable of work', 'wd-cdek-delivery' ),
				],
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function payment_complete_handler( $order_id ) {
		$order           = wc_get_order( $order_id );
		$shipping_method = get_post_meta( $order_id, Order::ORDER_SHIPPING_METHOD_KEY, true );
		Helper::log( "Payment Complete Handler", [ $shipping_method ] );

		$shipping_method = $this->get_shipping_method( $shipping_method );
		Helper::log( "", [ $shipping_method ] );
		if ( ! $shipping_method ) {
			return;
		}

		$payment_when_receipt = $shipping_method->get_instance_option( 'payment_when_receipt', [ 'cod' ] );
		if ( in_array( $order->get_payment_method(), $payment_when_receipt, false ) ) {
			return;
		}

		$data   = get_post_meta( $order_id, Order::ORDER_DATA_META_KEY, true );
		$result = $this->sendCdek( $order_id, $data );

		$this->clear_session();
	}

	/**
	 * @inheritDoc
	 */
	public function get_total_handler( $amount, $meta ) {
		return $amount;
	}

	public function validate_delivery_method( $fields, $errors ) {
		$chosenShippingMethods = wc_get_chosen_shipping_method_ids();

		if ( ! empty( $chosenShippingMethods[0] )
		     && strpos( $chosenShippingMethods[0], Cdek::SHIPPING_DELIVERY_ID ) !== false ) {
			$orderSizes = Helper::getOrderSizes();

			if ( $fields['wdcd_delivery_method'] === 'courier' ) {
				$delivery_method = 'courier';
			} else {
				$delivery_method = 'pickup';
			}

			try {
				$service  = new \WBCR\Delivery\Cdek\ISDEKservice();
				$cityFrom = $service::getCity( [ 'city' => WC()->countries->get_base_city() ], true );
				if ( is_array( $cityFrom ) && isset( $cityFrom['id'] ) ) {
					$cityFrom = $cityFrom['id'];
				} else {
					$cityFrom = 0;
				}
				$data = [
					'shipment' => [
						'type'       => $delivery_method,
						'goods'      => [
							'0' => [
								'length' => (int) $orderSizes['length'],
								'height' => (int) $orderSizes['height'],
								'width'  => (int) $orderSizes['width'],
								'weight' => (float) $orderSizes['weight'],
							],
						],
						'cityFromId' => $cityFrom,
						'cityToId'   => intval( $fields['wdcd_geo'] ),
						'timestamp'  => time(),
					],
				];

				$calc = $service::validate_calc( $data );
			} catch ( Exception $exception ) {
				Helper::log( $exception->getMessage() );
				$result = [];
			}

			if ( isset( $calc['result'] ) && is_array( $calc['result'] ) ) {
				$result = $calc['result'];
			}

			if ( $result['price'] == $fields['wdcd_price'] && $result['tariffId'] == $fields['wdcd_tariff_id'] ) {
				return;
			} else {
				$errors->add(
					'incorrect-delivery-method',
					esc_html__(
						'The delivery cost or rate is incorrect.',
						'wd-cdek-delivery'
					)
				);
			}
		}

	}

	/**
	 * Save checkout data to post meta
	 *
	 * @param $orderID
	 * @param $data
	 */
	public function save_cdek_delivery_order( $orderID, $data ) {
		$orderSizes         = Helper::getOrderSizes();
		$items              = WC()->cart->get_cart_contents();
		$data['orderSizes'] = $orderSizes;
		$data['items']      = $items;

		$data['selectedDeliveryMethod'] = $this->selectedDeliveryMethod;

		update_post_meta( $orderID, Order::DELIVERY_CHECKOUT_META_KEY, $data );
	}

	/**
	 * @param $orderID
	 * @param $data
	 *
	 * @return array
	 */
	private function sendCdek( $orderID, $data ) {
		if ( $this::$app->is_premium() ) {
			$client = new Client();

			$order = $client->orders( $data );

			if ( is_array( $order ) ) {
				Helper::log( "Заказ отправлен:" );
				Helper::log( print_r( $order, true ) );

				$checkout_meta                      = get_post_meta( $orderID, Order::DELIVERY_CHECKOUT_META_KEY, true );
				$checkout_meta['delivery_response'] = $order;
				update_post_meta( $orderID, Order::DELIVERY_CHECKOUT_META_KEY, $checkout_meta );

				return $order;
			} else {
				update_post_meta( $orderID, Order::ORDER_ID_META_KEY, $order );
				update_post_meta( $orderID, Order::DELIVERY_NAME_META_KEY, __( 'СДЭК', 'wd-cdek-delivery' ) );
			}
		}

		return [];
	}

	/**
	 * @param $order_id
	 * @param $data
	 */
	public function create_cdek_delivery_order( $order_id, $data ) {
		$shipping_method = $this->get_shipping_method( $data['shipping_method'][0] );
		if ( isset( $shipping_method->id ) && $shipping_method->id !== Cdek::SHIPPING_DELIVERY_ID ) {
			return;
		}

		$orderSizes    = Helper::getOrderSizes();
		$delivery_cost = isset( $data['wdcd_price'] ) ? $data['wdcd_price'] : '';

		if ( isset( $data['ship_to_different_address'] ) && $data['ship_to_different_address'] ) {
			$customer_data = 'shipping';
		} else {
			$customer_data = 'billing';
		}

		$firstName = isset( $data[ $customer_data . '_first_name' ] ) ? $data[ $customer_data . '_first_name' ] : '';
		$lastName  = isset( $data[ $customer_data . '_last_name' ] ) ? $data[ $customer_data . '_last_name' ] : '';
		$phone     = isset( $data[ $customer_data . '_phone' ] ) ? $data[ $customer_data . '_phone' ] : '';
		$email     = isset( $data[ $customer_data . '_email' ] ) ? $data[ $customer_data . '_email' ] : '';

		$country_to   = isset( $data[ $customer_data . '_country' ] ) ? $data[ $customer_data . '_country' ] : '';
		$post_code_to = isset( $data[ $customer_data . '_postcode' ] ) ? $data[ $customer_data . '_postcode' ] : '';
		$state_to     = isset( $data[ $customer_data . '_state' ] ) ? $data[ $customer_data . '_state' ] : '';
		$city_to      = isset( $data[ $customer_data . '_city' ] ) ? $data[ $customer_data . '_city' ] : '';
		$addr_to      = isset( $data[ $customer_data . '_address_1' ] ) ? $data[ $customer_data . '_address_1' ] : '';
		$room_to      = isset( $data[ $customer_data . '_address_2' ] ) ? $data[ $customer_data . '_address_2' ] : '';

		$payment_method = $data['payment_method'];

		$payment_when_receipt = $shipping_method->get_instance_option( 'payment_when_receipt', [ 'cod' ] );
		$payment_upon_receipt = in_array( $payment_method, $payment_when_receipt, false );

		preg_match( '/(\+?[0-9]{11,})/', str_replace( [ ' ', '-', '(', ')' ], '', $phone ), $is_phone );
		if ( empty( $is_phone ) ) {
			$phone = [
				'number'     => "+71234567890",
				'additional' => (string) $phone,
			];
		} else {
			$phone = [
				'number' => (string) $phone,
			];

		}

		$items         = WC()->cart->get_cart_contents();
		$orderItems    = [];
		$assessedValue = 0;

		foreach ( $items as $item ) {
			$product       = wc_get_product( $item['product_id'] );
			$cost          = ceil( $item['line_total'] / $item['quantity'] );
			$sku           = ! empty( $product->get_sku() ) ? $product->get_sku() : $product->get_slug();
			$assessedValue = round( $cost * ( Cdek::$_settings['product_declared_price'] / 100 ) );
			$prod_weight   = Helper::getProductSizes( $product );

			if ( $assessedValue < 1 ) {
				$assessedValue = 1.0;
			}

			$orderItems[] = [
				'name'     => $product->get_title(),
				'ware_key' => $sku,
				'amount'   => $item['quantity'],
				'payment'  => [
					'value' => $payment_upon_receipt ? $cost : 0,
				],
				'value'    => $payment_upon_receipt ? $item['line_total'] : 0,
				'cost'     => $assessedValue,
				'weight'   => $prod_weight['weight'] ? (int) ( $prod_weight['weight'] * 1000 ) : 1,
			];
		}

		$services               = [];
		$selectedDeliveryMethod = $this->selectedDeliveryMethod;

		foreach ( $selectedDeliveryMethod['services'] as $service ) {
			$services[] = [
				'code'        => $service['code'],
				'cost'        => round( $service['cost'], 4 ),
				'customerPay' => $service['customerPay'],
			];
		}

		try {
			if ( $delivery_cost < $this->selectedDeliveryMethod['cost']['deliveryForCustomer']
			     && $delivery_cost < $this->selectedDeliveryMethod['cost']['delivery'] ) {
				wp_send_json_error( [ 'message' => 'err' ] );
			}

			$delivery_cost = $this->calculate_delivery_discount( $delivery_cost, $data['shipping_method'][0] );

			$address   = WC()->countries->get_base_address() . " " . WC()->countries->get_base_address_2();
			$city      = WC()->countries->get_base_city();
			$post_code = WC()->countries->get_base_postcode();
			$state     = WC()->countries->get_base_state();
			$country   = WC()->countries->get_base_country();

			if ( $data['wdcd_delivery_method'] == 'courier' ) {
				$cdek_data_additional = [
					'to_location' => [
						"country_code" => $country_to,
						"postal_code"  => $post_code_to,
						"region"       => $state_to,
						"city"         => $city_to,
						"address"      => "{$addr_to}, {$room_to}"
					]
				];
			} else {
				$cdek_data_additional = [
					'delivery_point' => $data['wdcd_delivery_method'],
				];

			}

			$cdek_data_base = [
				'type'                    => 1,
				'tariff_code'             => $data['wdcd_tariff_id'],
				'recipient'               => [
					'name'   => "{$firstName} {$lastName}",
					'email'  => $email,
					'phones' => [ $phone ],
				],
				'packages'                => [
					[
						'number' => $order_id,
						'weight' => intval( $orderSizes['weight'] * 1000 ),
						'length' => $orderSizes['length'],
						'width'  => $orderSizes['width'],
						'height' => $orderSizes['height'],
						'items'  => $orderItems,
					]
				],
				'delivery_recipient_cost' => [
					'value' => $delivery_cost
				],
				"from_location"           => [
					"country_code" => $country,
					"postal_code"  => $post_code,
					"region"       => $state,
					"city"         => $city,
					"address"      => $address
				],
			];

			$cdek_data = array_merge( $cdek_data_base, $cdek_data_additional );

			list( $shipping_method, $instance_id ) = explode( ':', $data['shipping_method'][0] );
			Helper::log( "Shipping method:", [
				$data['shipping_method'][0],
				[ $shipping_method, $instance_id ]
			] );

			$this->save_order_data( $order_id, $cdek_data );

			$update_post_meta = update_post_meta( $order_id, Order::ORDER_SHIPPING_METHOD_KEY, [
				$shipping_method,
				(int) $instance_id
			] );
			Helper::log( "Update post meta", [ $update_post_meta ] );

			if ( $payment_upon_receipt ) {
				$result = $this->sendCdek( $order_id, $cdek_data );
			}

			$this->clear_session();
		} catch ( Exception $exception ) {
			Helper::log( $exception->getMessage() );
		}
	}

	public function remove_cart_item( $cart_item_key, $_this ) {
		$this->clear_session();
	}

	public function update_cart_action_cart_updated( $cart_updated ) {
		$this->clear_session();

		return $cart_updated;
	}

	/**
	 * Очищает данные из сессии
	 *
	 * Требуется очистка после оформления заказа или обновления корзины.
	 */
	protected function clear_session() {
		$data = WC()->session->get( Cdek::SHIPPING_DELIVERY_ID );

		if ( isset( $data['formData']['wdcd_delivery_method'] ) ) {
			unset( $data['formData']['wdcd_delivery_method'] );
			unset( $data['formData']['wdcd_pickup_point'] );
			unset( $data['formData']['wdcd_tariff_id'] );
			unset( $data['formData']['wdcd_price'] );
		}

		WC()->session->set( Cdek::SHIPPING_DELIVERY_ID, $data );
	}

}
