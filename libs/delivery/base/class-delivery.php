<?php

namespace WBCR\Delivery\Base;

use Wbcr_Factory437_Plugin;
use WC_Admin_Settings;
use WC_Order;
use WC_Shipping_Method;
use WP_Screen;

/**
 * Class Delivery
 *
 * @package WBCR\Delivery
 */
abstract class Delivery extends WC_Shipping_Method {
	/**
	 * Объект плагина. Обязательно заполнять в наследуемом классе
	 *
	 * @var Wbcr_Factory437_Plugin[]
	 */
	public static $app = [];

	/**
	 * ID доставки. В наследуемых объектах менять _обязательно_
	 */
	const SHIPPING_DELIVERY_ID = 'wbcr_delivery';

	/**
	 * @see WC_Shipping_Method::$supports
	 */
	const SUPPORTS_ZONES = [
		'shipping-zones',
		'settings',
		'instance-settings',
	];

	/**
	 * @see WC_Shipping_Method::$enabled
	 */
	const ENABLED = 'yes';

	/**
	 * @type Checkout
	 */
	const CHECKOUT_HANDLER = Checkout::class;

	public static $_settings = [];

	/**
	 * @return array
	 */
	public static function settings() {
		return static::$_settings[ static::SHIPPING_DELIVERY_ID ];
	}

	public static function default_settings() {
		return [
			'show_delivery_date'     => 'yes',
			'adjust_delivery_date'   => 1,
			'product_declared_price' => 100,
			'debug'                  => 'no',
			'devmode'                => 'no',
			'default_height'         => 0,
			'default_weight'         => 0,
			'default_length'         => 0,
		];
	}

	/**
	 * @return Wbcr_Factory437_Plugin
	 * @noinspection PhpInconsistentReturnPointsInspection
	 */
	public static function app() { }

	/**
	 * @return bool
	 */
	public static function is_dev_mode() {
		return static::settings()['devmode'] === 'yes';
	}

	public static function register_hooks() {
		add_filter( 'woocommerce_shipping_methods', [ static::class, 'add_shipping_method' ] );
		add_action( 'admin_enqueue_scripts', [ static::class, 'admin_enqueue_scripts_hook_handler' ] );

		if ( is_admin() ) {
			add_action( 'woocommerce_order_edit_status', [ static::class, 'order_status_updated' ], 10, 2 );
			add_action( 'woocommerce_delete_order', [ static::class, 'order_deleted' ], 10, 1 );
			add_action( 'woocommerce_trash_order', [ static::class, 'order_deleted' ], 10, 1 );
			add_action( 'wp_trash_post', [ static::class, 'order_deleted' ], 10, 1 );

			add_action( 'woocommerce_admin_order_data_after_shipping_address', [ static::class, 'order_data_output' ], 10, 1 );
		}

		static::$_settings[ static::SHIPPING_DELIVERY_ID ] = get_option( sprintf( 'woocommerce_%s_settings', static::SHIPPING_DELIVERY_ID ), [] ) + static::default_settings();

		if ( static::settings()['devmode'] === 'yes' ) {
			static::settings()['adjust_delivery_date'] = 3;
		}

		$checkout_object = static::CHECKOUT_HANDLER;
		$checkout_object::register();
	}

	/**
	 * @param string[] $methods
	 *
	 * @return string[]
	 */
	public static function add_shipping_method( $methods ) {
		$methods[ static::SHIPPING_DELIVERY_ID ] = static::class;

		return $methods;
	}

	public static function admin_enqueue_scripts_hook_handler() {
		/** @var WP_Screen $current_screen */
		$current_screen = get_current_screen();

		if ( $current_screen->id === 'woocommerce_page_wc-settings' && isset( $_GET['tab'], $_GET['section'] ) && $_GET['tab'] === 'shipping' && $_GET['section'] === static::SHIPPING_DELIVERY_ID ) {
			static::admin_enqueue_scripts();
		}
	}

	/**
	 * Вызывается при смене статуса заказа
	 *
	 * @param $id
	 * @param $new_status
	 */
	public static function order_status_updated( $id, $new_status ) {
		switch ( $new_status ) {
			case 'cancelled':
				self::order_deleted( $id );
				break;
		}
	}

	/**
	 * Вызывается перед удалением заказа
	 *
	 * @param $id
	 */
	final public static function order_deleted( $id ) {
		if ( ! $id ) {
			return;
		}

		$post_type = get_post_type( $id );

		// If this is an order, trash any refunds too.
		if ( in_array( $post_type, wc_get_order_types( 'order-count' ), true ) ) {
			$order             = wc_get_order( $id );
			$external_order_id = $order->get_meta( Order::ORDER_ID_META_KEY );
			if ( $external_order_id ) {
				static::order_delete( $order, $external_order_id );
			}
		}
	}

	/**
	 * Вызывается для удаления заказа из сервиса доставки
	 *
	 * @param WC_Order $order
	 * @param int $external_order_id
	 */
	public static function order_delete( $order, $external_order_id ) { }

	/**
	 * Подключение скриптов и стилей на странице настроек текущего метода
	 * Никаких проверок проводить не нужно
	 */
	public static function admin_enqueue_scripts() {
	}

	public function __construct( $instance_id = 0 ) {
		parent::__construct( $instance_id );

		$this->id       = static::SHIPPING_DELIVERY_ID;
		$this->enabled  = static::ENABLED;
		$this->supports = static::SUPPORTS_ZONES;

		$this->init();
	}

	public function init() {
		$this->init_settings();
		$this->init_form_fields();

		if ( in_array( 'instance-settings', static::SUPPORTS_ZONES, false ) ) {
			$this->init_instance_settings();
		}

		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, [ $this, 'sanitize_settings' ] );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function sanitize_settings( $settings ) {
		foreach ( $settings as $key => $value ) {
			switch ( $key ) {
				case 'packages':
					$settings['packages'] = [];

					if ( isset( $_POST['packages'] ) && is_array( $_POST['packages'] ) ) {
						foreach ( $_POST['packages'] as $packageKey => $package ) {
							if ( ! empty( $package['length']['inner'] ) && ! empty( $package['length']['outer'] ) && ! empty( $package['width']['inner'] ) && ! empty( $package['width']['outer'] ) && ! empty( $package['height']['inner'] ) && ! empty( $package['height']['outer'] ) && ! empty( $package['weight']['package'] ) && ! empty( $package['weight']['max'] ) ) {
								$settings['packages'][ (int) $packageKey ] = (array) $package;
							}
						}
					}
					break;
			}
		}

		list( $success, $error_message ) = $this->validate_settings( $settings );
		if ( ! $success ) {
			if ( is_array( $error_message ) ) {
				foreach ( $error_message as $message ) {
					$this->add_error( $message );
				}
			} else {
				$this->add_error( $error_message );
			}
		}

		if ( $this->get_errors() ) {
			foreach ( $this->get_errors() as $error ) {
				WC_Admin_Settings::add_error( $error );
			}
			static::app()->updatePopulateOption( 'plugin_configured', 'no' );
		} else {
			static::app()->updatePopulateOption( 'plugin_configured', 'yes' );
		}

		return $settings;
	}

	/**
	 * Валидация настроек
	 * Возвращает массив с двумя значениями: bool (валидно ли), string или string[] (сообщение или массив сообщений с ошибками)
	 *
	 * @param array $settings
	 *
	 * @return array = [
	 *     0 => true | false,
	 *     1 => string | null
	 * ]
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function validate_settings( $settings ) {
		return [ true, '' ];
	}

	public function init_form_fields() {
		$this->instance_form_fields = $this->instance_form_fields();
		$this->form_fields          = $this->settings_form_fields();
	}

	/**
	 * @return array
	 */
	public function instance_form_fields() {
		$payment_gateways = [];

		if ( isset( $_GET['section'] ) && $_GET['section'] === 'cod' ) {
			return [];
		}

		WC()->payment_gateways()->get_available_payment_gateways();
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $availablePaymentGateway ) {
			$payment_gateways[ $availablePaymentGateway->id ] = $availablePaymentGateway->title;
		}

		return [
			'payment_when_receipt' => [
				'title'       => __( 'Payment when receipt', 'wbcr_factory_delivery_base' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the payment method(s) in which the buyer pays for the product and the delivery cost upon delivery, both by courier and upon pickup from the order pick-up Point', 'wbcr_factory_delivery_base' ),
				'options'     => $payment_gateways,
			],

			'delivery_discount'                  => [
				'title' => __( 'A discount on shipping', 'wbcr_factory_delivery_base' ),
				'type'  => 'title',
			],
			'delivery_discount_enable'           => [
				'title'    => __( 'Enable', 'wbcr_factory_delivery_base' ),
				'type'     => 'checkbox',
				'default'  => 'no',
				'disc_tip' => false,
			],
			'delivery_discount_min_order_amount' => [
				'title'             => __( 'Minimum order amount', 'wbcr_factory_delivery_base' ),
				'type'              => 'number',
				'default'           => 0,
				'disc_tip'          => false,
				'description'       => __( 'Minimum order amount for which the delivery discount is applied', 'wbcr_factory_delivery_base' ),
				'custom_attributes' => [
					'min' => 0,
				],
			],
			'delivery_discount_amount'           => [
				'title'             => __( 'Discount percentage', 'wbcr_factory_delivery_base' ),
				'type'              => 'number',
				'default'           => 0,
				'disc_tip'          => false,
				'description'       => __( 'Discount on delivery, specified as a percentage. If you need free shipping - specify 100', 'wbcr_factory_delivery_base' ),
				'custom_attributes' => [
					'min' => 0,
					'max' => 100,
				],
			],
		];
	}

	public function settings_form_fields() {
		$statused = wc_get_order_statuses();
		$statused = array_merge( [ 0 => __( 'Immediately', 'wbcr_factory_delivery_base' ) ], $statused );

		return [
			'status_send_to_delivery' => [
				'title'             => __( 'Status of the order to be sent for delivery', 'wbcr_factory_delivery_base' ),
				'type'              => 'select',
				'description'       => __( 'Order status when order data is sent to the delivery service', 'wbcr_factory_delivery_base' ),
				'desc_tip'          => false,
				'default'           => 0,
				'options'           => $statused,
				'custom_attributes' => [ 'autocomplete' => 'off', 'required' => 'required' ],
			],

			'product_declared_price' => [
				'title'             => __( 'Declared price of the product (%)', 'wbcr_factory_delivery_base' ),
				'type'              => 'number',
				'description'       => __( 'Declared price of the product as a percentage of the product price. Minimum 1 rouble', 'wbcr_factory_delivery_base' ),
				'default'           => 1,
				'custom_attributes' => [
					'min' => 1,
					'max' => 100,
				],
			],

			'developersTitle' => [
				'title' => __( 'Developers settings', 'wbcr_factory_delivery_base' ),
				'type'  => 'title',
			],

			'debug' => [
				'title'       => __( 'Debug mode', 'wbcr_factory_delivery_base' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable debug mode', 'wbcr_factory_delivery_base' ),
				'description' => __( 'Check to enable non-minified asset files.', 'wbcr_factory_delivery_base' ),
				'default'     => 'no',
			],

			'devmode' => [
				'title'       => __( 'Development mode', 'wbcr_factory_delivery_base' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable development mode', 'wbcr_factory_delivery_base' ),
				'description' => __( 'Development mode. Creates orders only in the draft status with delayed delivery for 3 days', 'wbcr_factory_delivery_base' ),
				'default'     => 'no',
			],
		];
	}

	public function generate_packages_html() {
		ob_start();
		?>
        <tr valign="top">
            <td class="forminp" colspan="2">
                <fieldset>
                    <legend class="screen-reader-text">
                        <span>
                            <?php _e( 'Packages', 'wbcr_factory_delivery_base' ); ?>
                        </span>
                    </legend>
                    <table data-ui-component="packages">
                        <thead>
                        <tr>
                            <th>
					            <?php echo __( 'Package', 'wbcr_factory_delivery_base' ); ?>
                            </th>
                            <th>
					            <?php echo __( 'Length', 'wbcr_factory_delivery_base' ) . " (см)"; ?>
                            </th>
                            <th>
					            <?php echo __( 'Width', 'wbcr_factory_delivery_base' ) . " (см)"; ?>
                            </th>
                            <th>
					            <?php echo __( 'Height', 'wbcr_factory_delivery_base' ) . " (см)"; ?>
                            </th>
                            <th>
					            <?php echo __( 'Weight', 'wbcr_factory_delivery_base' ) . " (кг)"; ?>
                            </th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
			            <?php
			            $packageKey = 0;

			            if ( isset( $this->settings['packages'] ) && is_array( $this->settings['packages'] ) ) {

							foreach ( $this->settings['packages'] as $key => $package ) {
								$packageKey = $key;
								?>
                                <tr>
                                    <th>
                                        #<?php echo (int) $key; ?>
                                    </th>
									<?php
									foreach ( [ 'length', 'width', 'height' ] as $dimension ) {
										?>
                                        <td>
                                            <label>
												<?php __( 'Outer: ', 'wbcr_factory_delivery_base' ); ?>
                                                <br>
                                                <input type="text"
                                                       required
                                                       class="small-text"
                                                       autocomplete="off"
                                                       value="<?php
												       esc_attr_e( $package[ $dimension ]['outer'] );
												       ?>"
                                                       name="<?php
												       echo esc_attr( 'packages[' . $key . '][' . $dimension . '][outer]' );
												       ?>">
                                            </label>
                                            <label>
												<?php __( 'Inner: ', 'wbcr_factory_delivery_base' ); ?>
                                                <br>
                                                <input type="text"
                                                       required
                                                       class="small-text"
                                                       autocomplete="off"
                                                       value="<?php
												       esc_attr_e( $package[ $dimension ]['inner'] );
												       ?>"
                                                       name="<?php
												       echo esc_attr( 'packages[' . $key . '][' . $dimension . '][inner]' );
												       ?>">
                                            </label>
                                        </td>
										<?php
									}
									?>
                                    <td>
                                        <label>
			                                <?php __( 'Package: ', 'wbcr_factory_delivery_base' ); ?>
                                            <br>
                                            <input type="text"
                                                   required
                                                   class="small-text"
                                                   autocomplete="off"
                                                   value="<?php
			                                       esc_attr_e( $package['weight']['package'] );
			                                       ?>"
                                                   name="<?= esc_attr( 'packages[' . $key . '][weight][package]' ) ?>">
                                        </label>
                                        <label>
			                                <?php __( 'Max: ', 'wbcr_factory_delivery_base' ); ?>
                                            <br>
                                            <input type="text"
                                                   required
                                                   class="small-text"
                                                   autocomplete="off"
                                                   value="<?php
			                                       esc_attr_e( $package['weight']['max'] );
			                                       ?>"
                                                   name="<?= esc_attr( 'packages[' . $key . '][weight][max]' ) ?>">
                                        </label>
                                    </td>
                                    <td>
                                        <span class="dashicons dashicons-no" data-ui-component="remove-package"></span>
                                    </td>
                                </tr>
								<?php
							}
						}
						?>
                        </tbody>
                    </table>
                    <button class="button button-default"
                            data-ui-component="add-package"
                            data-key="<?= (int) $packageKey ?>">
						<?= __( 'Add package', 'wbcr_factory_delivery_base' ) ?>
                    </button>
                </fieldset>
                <br>
                <p class="description">
					<?= __( 'These settings are used if the product does not have dimensions and weight set in the product Data-delivery section', 'wbcr_factory_delivery_base' ) ?>
                </p>
            </td>
        </tr>
		<?php
		return ob_get_clean();
	}

	public static function order_data_output( $order ) { }

	/**
	 * Алиас для функции __, но домен должен подставляется в наследуемом классе
	 *
	 * @param $key
	 *
	 * @return string|void
	 */
	public static function __( $key ) {
		/** @var Wbcr_Factory437_Plugin $app */
		$app = static::app();

		return __( $key, $app::get_domain() );
	}
}
