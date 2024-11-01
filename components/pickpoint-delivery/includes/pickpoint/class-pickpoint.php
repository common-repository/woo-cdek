<?php

namespace WPickpoint\Delivery\ShippingMethod;

use WC_Shipping_Method;
use WPickpoint\Plugin;

/**
 * Используется в админке
 * Class Pickpoint
 *
 * @package WPickpoint\Delivery\ShippingMethod
 */
class Pickpoint extends WC_Shipping_Method {

	const SHIPPING_METHOD_ID = 'wpickpoint-delivery';

	public static $_settings = [];

	public function __construct( $instance_id = 0 ) {
		parent::__construct( $instance_id );

		$this->id                 = self::SHIPPING_METHOD_ID;
		$this->title              = __( 'Pickpoint', 'pickpoint-delivery' );
		$this->method_title       = __( 'Pickpoint', 'pickpoint-delivery' );
		$this->method_description = __( 'В этом разделе вы можете настроить плагин Pickpoint delivery.', 'pickpoint-delivery' );
		$this->enabled            = 'yes';
		$this->supports           = [ 'shipping-zones', 'settings' ];

		$this->init();
	}

	public static function register_hooks() {

		add_filter( 'woocommerce_shipping_methods', [ self::class, 'add_shipping_method' ] );

		Checkout::register_hooks();
	}

	public static function get_settings() {
		if ( ! empty( self::$_settings ) ) {
			return self::$_settings;
		}

		$settings = get_option( sprintf( 'woocommerce_%s_settings', self::SHIPPING_METHOD_ID ), [
			'ikn'      => null,
			'login'    => null,
			'password' => null,
			'debug'    => null
		], false );

		if ( false !== $settings && is_array( $settings ) ) {
			self::$_settings = $settings;
		}

		return $settings;
	}

	public static function get_setting( $name ) {
		$settings = static::get_settings();

		if ( ! empty( $settings[ $name ] ) ) {
			return $settings[ $name ];
		}

		return null;
	}

	/**
	 * @param array $methods
	 *
	 * @return array
	 */
	public static function add_shipping_method( $methods ) {
		$methods[ self::SHIPPING_METHOD_ID ] = self::class;

		return $methods;
	}

	public function init() {
		$this->init_settings();
		$this->init_instance_settings();
		$this->init_form_fields();

		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, [ $this, 'sanitizeSettings' ] );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Output the admin options table.
	 */
	public function admin_options() {
		?>
        <div class="wdyd-settings"><a
                href="<?= wdpp_get_current_plugin()->get_support()->get_docs_url( true, 'settings_page' ); ?>"
                class="button button-secondary" target="_blank"><?= __( 'Documentation', 'pickpoint-delivery' ) ?></a>
        </div><?php
		parent::admin_options();
	}

	public function init_form_fields() {
		$payment_gateways = [];

		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $availablePaymentGateway ) {
			$payment_gateways[ $availablePaymentGateway->id ] = $availablePaymentGateway->title;
		}

		$statused          = wc_get_order_statuses();
		$statused          = array_merge( [ 0 => __( 'Immediately', 'pickpoint-delivery' ) ], $statused );
		$this->form_fields = [
			'ikn'      => [
				'title'             => __( 'IKN (номер договора)', 'pickpoint-delivery' ),
				'type'              => 'text',
				'default'           => '',
				'desc_tip'          => false,
				'custom_attributes' => [ 'autocomplete' => 'off', 'required' => 'required' ],
			],
			'login'    => [
				'title'             => __( 'Логин', 'pickpoint-delivery' ),
				'type'              => 'text',
				'default'           => '',
				'desc_tip'          => false,
				'custom_attributes' => [ 'autocomplete' => 'off', 'required' => 'required' ],
			],
			'password' => [
				'title'             => __( 'Пароль', 'pickpoint-delivery' ),
				'type'              => 'text',
				'default'           => '',
				'desc_tip'          => false,
				'custom_attributes' => [ 'autocomplete' => 'off', 'required' => 'required' ],
			],
			'insuare'  => [
				'title'             => __( 'Объявленная стоимость (%)', 'pickpoint-delivery' ),
				'type'              => 'text',
				'default'           => 0,
				'desc_tip'          => __( 'Введите значение в процентах. Если установить 0, груз не будет застрахован. Если установить 100, груз будет застрахован полностью. Вы можете установить промежуточное значение, чтобы частично застраховать груз.', 'wc-yandex-delivery-integration' ),
				'custom_attributes' => [ 'autocomplete' => 'off' ],
			],
			'debug'    => [
				'title'       => esc_html__( 'Режим отладки', 'wc-yandex-delivery-integration' ),
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Включает логирование ошибок.', 'wc-yandex-delivery-integration' ),
				'description' => '',
				'default'     => 'no'
			],

			'packageTitle' => [
				'title' => __( 'Настройка упаковки', 'pickpoint-delivery' ),
				'type'  => 'title',
			],

			'packages' => [
				'type' => 'packages',
			]
		];
	}

	public function sanitizeSettings( $settings ) {
		foreach ( $settings as $key => $value ) {
			switch ( $key ) {
				case 'login':
				case 'password':
				case 'debug':
					$settings[ $key ] = $value;
					break;

				case 'insuare':
				case 'ikn':
					$settings[ $key ] = (int) $value;
					break;

				case 'packages':
					$settings['packages'] = [];

					if ( isset( $_POST['packages'] ) && is_array( $_POST['packages'] ) ) {
						foreach ( $_POST['packages'] as $dimension => $package_value ) {
							$settings['packages'][ $dimension ] = (int) $package_value;
						}
					}
					break;
			}
		}
		if ( $this->get_errors() ) {
			foreach ( $this->get_errors() as $error ) {
				\WC_Admin_Settings::add_error( $error );
			}
			wdpp_get_current_plugin()->updatePopulateOption( 'plugin_configured', 'no' );
		} else {
			wdpp_get_current_plugin()->updatePopulateOption( 'plugin_configured', 'yes' );
		}

		return $settings;
	}

	public function generate_packages_html() {
		ob_start();
		?>
        <tr valign="top">
            <td class="forminp" colspan="2">
                <fieldset>
                    <legend class="screen-reader-text">
                        <span>
                            <?php esc_html_e( 'Упаковка', 'pickpoint-delivery' ); ?>
                        </span>
                    </legend>
                    <table data-ui-component="packages">
                        <thead>
                        <tr>
                            <th>
								<?php esc_html_e( 'Глубина', 'pickpoint-delivery' ); ?>
                            </th>
                            <th>
								<?php esc_html_e( 'Длинна', 'pickpoint-delivery' ); ?>
                            </th>
                            <th>
								<?php esc_html_e( 'Ширина', 'pickpoint-delivery' ); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
							<?php
							foreach ( [ 'Depth', 'Length', 'Width' ] as $dimension ) {
								$value = ! empty( $this->settings['packages'] ) && ! empty( $this->settings['packages'][ $dimension ] ) ? $this->settings['packages'][ $dimension ] : 0;
								?>
                                <td>
									<input type="text"
									       required
									       class="small-text"
									       autocomplete="off"
									       value="<?php echo esc_attr( $value ); ?>"
									       name="<?php
									       echo esc_attr( 'packages[' . $dimension . ']' );
									       ?>">
								</td>
								<?php
							}
							?>
                        </tr>
                        </tbody>
                    </table>
                </fieldset>
                <br>
                <p class="description">
					<?php
					esc_html_e( 'Эти настройки используются, если для товара не заданы габариты и вес.', 'pickpoint-delivery' );
					?>
                </p>
            </td>
        </tr>
		<?php
		return ob_get_clean();
	}

	public static function order_status_updated( $id, $new_status ) {
		switch ( $new_status ) {
			case 'cancelled':
				self::order_deleted( $id );
				break;
		}
	}

	public function calculate_shipping( $package = [] ) {
		$data = WC()->session->get( self::SHIPPING_METHOD_ID );

		$this->add_rate( [
			'label'   => $this->title,
			'cost'    => 0,
			'taxes'   => false,
			'package' => $package,
		] );
	}

	public static function order_deleted( $id ) {
		if ( ! $id ) {
			return;
		}

		$post_type = get_post_type( $id );

		// If this is an order, trash any refunds too.
		if ( in_array( $post_type, wc_get_order_types( 'order-count' ), true ) ) {
			$invoice_number = get_post_meta( $id, '_pickpoint_invoice_number', true );

			if ( empty( $invoice_number ) ) {
				return;
			}

			$client = Client::get_intance();
			$client->cancel_order( $invoice_number );
		}
	}
}
