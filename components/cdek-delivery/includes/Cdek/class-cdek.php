<?php /** @noinspection PhpUndefinedClassInspection */

namespace WBCR\Delivery\Cdek;

use WBCR\Delivery\Base\Delivery;
use WBCR\Delivery\Cdek\Plugin;

class Cdek extends Delivery {
	static $delivery;

	const SHIPPING_DELIVERY_ID = 'wbcr_cdek_delivery';

	const CHECKOUT_HANDLER = Checkout::class;

	const courier_tariffs = [ 233, 137, 139, 16, 18, 11, 1, 3, 61, 60, 59, 58, 57, 83 ];

	const pickup_tariffs = [ 234, 136, 138, 15, 17, 62, 63, 5, 10, 12 ];

	public static function app() {
		return wdcd_get_current_plugin();
	}

	public function init() {
		$this->title              = __('Cdek Delivery', 'wd-cdek-delivery');
		$this->method_title       = __('Cdek Delivery', 'wd-cdek-delivery');
		$this->method_description = __('Cdek Delivery extension adds shipping method to Checkout page.', 'wd-cdek-delivery');

		parent::init();
	}

	/**
	 * @return array(
	 * 'account'  => string,
	 * 'password' => string,
	 * )
	 */
	public static function getApiCredentials() {
		$shippingMethods = WC()->shipping()->get_shipping_methods();

		$account  = '';
		$password = '';
		if ( isset( $shippingMethods[ Cdek::SHIPPING_DELIVERY_ID ] ) ) {
			$settings = $shippingMethods[ Cdek::SHIPPING_DELIVERY_ID ]->settings;
			$account  = isset( $settings['api_account'] ) ? $settings['api_account'] : '';
			$password = isset( $settings['api_password'] ) ? $settings['api_password'] : '';
		}

		return [
			'account'  => $account,
			'password' => $password,
		];

	}

	/**
	 * {@inheritDoc}
	 */
	public static function admin_enqueue_scripts() {
		wp_enqueue_script( self::SHIPPING_DELIVERY_ID . '-admin', WDCD_PLUGIN_URL . '/admin/assets/js/admin.js', [ 'jquery' ], static::app()->getPluginVersion() );

		wp_enqueue_style( self::SHIPPING_DELIVERY_ID . '-admin-css', WDCD_PLUGIN_URL . '/admin/assets/css/admin.css', [], static::app()->getPluginVersion() );

		wp_localize_script( self::SHIPPING_DELIVERY_ID . '-admin', 'localStrings', [
			'inner'   => __('Inner: ', 'wd-cdek-delivery'),
			'outer'   => __('Outer: ', 'wd-cdek-delivery'),
			'package' => __('Package: ', 'wd-cdek-delivery'),
			'max'     => __('Max: ', 'wd-cdek-delivery'),
		] );
		wp_localize_script( self::SHIPPING_DELIVERY_ID . '-admin', 'cdek', [
			'tariffs' => [
				'courier_tariffs' => static::courier_tariffs,
				'pickup_tariffs'  => static::pickup_tariffs,
			],
		] );
	}

	public static function admin_enqueue_scripts_hook_handler() {
	    parent::admin_enqueue_scripts_hook_handler();
		/** @var \WP_Screen $current_screen */
		$current_screen = get_current_screen();

		if ( $current_screen->id === 'shop_order' && $current_screen->post_type == 'shop_order' ) {
    		wp_enqueue_style( self::SHIPPING_DELIVERY_ID . '-admin-order-css', WDCD_PLUGIN_URL . '/admin/assets/css/order.css', [], static::app()->getPluginVersion() );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public static function order_delete( $order, $external_order_id ) {
		$client = new Client();

		try {
			$client->delete_order( $external_order_id );
		} catch ( \Exception $exception ) {
			Helper::log( "Error when delete order: " . $exception->getMessage(), $exception->getTrace() );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function validate_settings( $settings ) {
		try {
			$client = new Client();
		} catch ( \Exception $exception ) {
			Helper::log( "Error while validation: " . $exception->getMessage(), $exception->getTrace() );

			// Если не удалось проверить, то нужно просто сохранить настройки
			return [ true, '' ];
		}

		if ( empty( $client->getToken() ) ) {
			return [
				false,
				[ static::__( "The entered data is incorrect. Check API account and API password" ) ]
			];
		}

		return [ true, '' ];
	}

	/**
	 * @inheritDoc
	 */
	public function sanitize_settings( $settings ) {
		return parent::sanitize_settings( $settings );
	}

	public function settings_form_fields() {
		$courier_tariffs = [
			124 => "Магистральный супер-экспресс дверь-дверь",
			125 => "Магистральный супер-экспресс склад-дверь",
			121 => "Магистральный экспресс дверь-дверь",
			122 => "Магистральный экспресс склад-дверь",
			8   => "Международный экспресс грузы дверь-дверь",
			179 => "Международный экспресс грузы склад-дверь",
			7   => "Международный экспресс документы дверь-дверь",
			182 => "Международный экспресс документы склад-дверь",
			139 => "Посылка дверь-дверь",
			137 => "Посылка склад-дверь",
			58  => "Супер-экспресс до 10",
			59  => "Супер-экспресс до 12",
			60  => "Супер-экспресс до 14",
			61  => "Супер-экспресс до 16",
			3   => "Супер-экспресс до 18",
			57  => "Супер-экспресс до 9",
			231 => "Экономичная посылка дверь-дверь",
			233 => "Экономичная посылка склад-дверь",
			118 => "Экономичный экспресс дверь-дверь",
			119 => "Экономичный экспресс склад-дверь",
			1   => "Экспресс лайт дверь-дверь",
			11  => "Экспресс лайт склад-дверь",
			18  => "Экспресс тяжеловесы дверь-дверь",
			16  => "Экспресс тяжеловесы склад-дверь",
			293 => "CDEK Express дверь-дверь",
			294 => "CDEK Express склад-дверь",
		];

		$pickup_tariffs = [
			126 => "Магистральный супер-экспресс дверь-склад",
			63  => "Магистральный супер-экспресс склад-склад",
			123 => "Магистральный экспресс дверь-склад",
			62  => "Магистральный экспресс склад-склад",
			180 => "Международный экспресс грузы дверь-склад",
			178 => "Международный экспресс грузы склад-склад",
			183 => "Международный экспресс документы дверь-склад",
			181 => "Международный экспресс документы склад-склад",
			366 => "Посылка дверь-постамат",
			138 => "Посылка дверь-склад",
			368 => "Посылка склад-постамат",
			136 => "Посылка склад-склад",
			376 => "Экономичная посылка дверь-постамат",
			232 => "Экономичная посылка дверь-склад",
			378 => "Экономичная посылка склад-постамат",
			234 => "Экономичная посылка склад-склад",
			120 => "Экономичный экспресс дверь-склад",
			5   => "Экономичный экспресс склад-склад",
			361 => "Экспресс лайт дверь-постамат",
			12  => "Экспресс лайт дверь-склад",
			363 => "Экспресс лайт склад-постамат",
			10  => "Экспресс лайт склад-склад",
			17  => "Экспресс тяжеловесы дверь-склад",
			15  => "Экспресс тяжеловесы склад-склад",
			295 => "CDEK Express дверь-склад",
			291 => "CDEK Express склад-склад",
		];

		return array_merge( [
			'api_account'        => [
				'title'             => __( 'API Account', 'wd-cdek-delivery' ),
				'type'              => 'text',
				'default'           => '',
				'desc_tip'          => false,
				'custom_attributes' => [ 'autocomplete' => 'off', 'required' => 'required' ],
				'description'       => sprintf( '%1$s <a href="' . static::app()->get_support()->get_docs_url() . '" target="_blank">%2$s</a>', esc_html__( 'To find the API Account, see the ', 'wd-cdek-delivery' ), esc_html__( 'Documentation', 'wd-cdek-delivery' ) ),
			],
			'api_password'       => [
				'title'             => __( 'API Password', 'wd-cdek-delivery' ),
				'type'              => 'text',
				'default'           => '',
				'desc_tip'          => false,
				'description'       => sprintf( '%1$s <a href="' . static::app()->get_support()->get_docs_url() . '" target="_blank">%2$s</a>', esc_html__( 'To find the API Password, see the ', 'wd-cdek-delivery' ), esc_html__( 'Documentation', 'wd-cdek-delivery' ) ),
				'custom_attributes' => [ 'autocomplete' => 'off', 'required' => 'required' ],
			],
			'yamaps_api_key'     => [
				'title'             => __( 'Yandex Maps API key', 'wd-cdek-delivery' ),
				'type'              => 'text',
				'default'           => '',
				'desc_tip'          => false,
				'custom_attributes' => [ 'autocomplete' => 'off' ],
				'description'       => sprintf( '%1$s <a href="https://yandex.ru/dev/maps/jsapi/doc/2.1/quick-start/index.html/#get-api-key" target="_blank">%2$s</a>', esc_html__( 'Yandex Maps API key for the Cdek Shopping cart widget', 'wd-cdek-delivery' ), esc_html__( 'Get here', 'wd-cdek-delivery' ) ),
			],
			'tariffsTitle_start' => [
				'title' => '<hr>' . __('Tariff settings', 'wd-cdek-delivery'),
				'type'  => 'title',
			],
			'courier_tariffs'    => [
				'title'          => __('Tariffs for courier delivery', 'wd-cdek-delivery'),
				'type'           => 'multiselect_default',
				'description'    => __('Choose the tariffs that will be used for courier delivery.', 'wd-cdek-delivery'),
				'select_buttons' => true,
				'options'        => $courier_tariffs,
				'default'        => self::courier_tariffs,
			],

			'pickup_tariffs'   => [
				'title'          => __('Tariffs for delivery to the pick-up point', 'wd-cdek-delivery'),
				'type'           => 'multiselect_default',
				'description'    => __('Choose the tariffs that will be used for delivery to the pick-up point.', 'wd-cdek-delivery'),
				'select_buttons' => true,
				'options'        => $pickup_tariffs,
				'default'        => self::pickup_tariffs,
			],
			'tariffsTitle_end' => [
				'title' => '<hr>',
				'type'  => 'title',
			],
		], parent::settings_form_fields() );
	}

	public function calculate_shipping( $package = [] ) {
		$data = WC()->session->get( self::SHIPPING_DELIVERY_ID );

		$this->add_rate( [
			'label'   => $this->title,
			'cost'    => isset( $data['formData']['wdcd_price'] ) ? (float) $data['formData']['wdcd_price'] : 0,
			'taxes'   => false,
			'package' => $package,
		] );
	}

	/**
	 * Output the admin options table.
	 */
	public function admin_options() {
		?>
        <div class="wdcd-settings"><a href="<?= static::app()->get_support()->get_docs_url( true, 'settings_page' ) ?>"
                                      class="button button-secondary"
                                      target="_blank"><?= __( 'Documentation', 'wd-cdek-delivery' ) ?></a>
        </div><?php
		$client = new Client();
		parent::admin_options();
	}

	/**
	 * Generate Multiselect HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public function generate_multiselect_default_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'select_buttons'    => false,
			'options'           => array(),
			'default'           => array(),
		);

		$data  = wp_parse_args( $data, $defaults );
		$value = (array) $this->get_option( $key, $data['default'] );

		ob_start();
		?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?><?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
                    </legend>
                    <select multiple="multiple" class="multiselect <?php echo esc_attr( $data['class'] ); ?>"
                            name="<?php echo esc_attr( $field_key ); ?>[]" id="<?php echo esc_attr( $field_key ); ?>"
                            style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); // WPCS: XSS ok. ?>>
						<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>
							<?php if ( is_array( $option_value ) ) : ?>
                                <optgroup label="<?php echo esc_attr( $option_key ); ?>">
									<?php
									foreach ( $option_value as $option_key_inner => $option_value_inner ) : ?>
                                        <option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( in_array( (string) $option_key_inner, $value, true ), true ); ?>><?php echo esc_html( $option_value_inner ); ?></option>
									<?php endforeach; ?>
                                </optgroup>
							<?php else : ?>
                                <option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( in_array( (string) $option_key, $value, true ), true ); ?>><?php echo esc_html( $option_value ); ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
                    </select>
					<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
					<?php if ( $data['select_buttons'] ) : ?>
                        <br/>
                        <a class="select_all button" href="#"><?php esc_html_e( 'Select all', 'woocommerce' ); ?></a>
                        <a class="select_none button" href="#"><?php esc_html_e( 'Select none', 'woocommerce' ); ?></a>
                        <a class="select_cdek_default button" href="#" data-key="<?= $key; ?>">
							<?php echo self::__( 'Select default' ); ?>
                        </a>
					<?php endif; ?>
                </fieldset>
            </td>
        </tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Validate Multiselect Field.
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 *
	 * @return string|array
	 */
	public function validate_multiselect_default_field( $key, $value ) {
		return is_array( $value ) ? array_map( 'wc_clean', array_map( 'stripslashes', $value ) ) : '';
	}

	/**
	 * @param $order \WC_Order
	 */
	public static function order_data_output( $order ) {
		$order_meta      = $order->get_meta( Order::DELIVERY_CHECKOUT_META_KEY );
		$shipping_method = explode( ':', $order_meta['shipping_method'][0] );
		$shipping_method = $shipping_method[0];

		if ( $shipping_method == self::SHIPPING_DELIVERY_ID ) {
            $delivery_method   = isset( $order_meta['wdcd_delivery_method'] ) ? $order_meta['wdcd_delivery_method'] : '';
            $price             = isset( $order_meta['wdcd_price'] ) ? $order_meta['wdcd_price'] : '';
            $pvz               = isset( $order_meta['wdcd_pickup_point'] ) ? json_decode( $order_meta['wdcd_pickup_point'], true ) : '';
            $delivery_response = isset( $order_meta['delivery_response'] ) ? $order_meta['delivery_response'] : [];
            $delivery_uid      = isset( $delivery_response['entity']['uuid'] ) ? $delivery_response['entity']['uuid'] : '';

            if ( isset( $order_meta['ship_to_different_address'] ) && $order_meta['ship_to_different_address'] ) {
                $customer_data = 'shipping';
            } else {
                $customer_data = 'billing';
            }

            $country = isset( $order_meta[$customer_data.'_country'] ) ? $order_meta[$customer_data.'_country'] : '';
            $city    = isset( $order_meta[$customer_data.'_city'] ) ? $order_meta[$customer_data.'_city'] : '';
            $state   = isset( $order_meta[$customer_data.'_state'] ) ? $order_meta[$customer_data.'_state'] : '';
            $addr    = isset( $order_meta[$customer_data.'_address_1'] ) ? $order_meta[$customer_data.'_address_1'] : '';
            $room    = isset( $order_meta[$customer_data.'_address_2'] ) ? $order_meta[$customer_data.'_address_2'] : '';
			?>
            </div></div>
            <div style='display: inline-block; width: 100%;'>
            <h3>Выбранный способ доставки</h3>
			<?php

			if ( ! empty( $delivery_method ) ) {
				if ( $delivery_method == 'courier' ) {
					$delivery_method_str = __('Курьер', 'wd-cdek-delivery');
					$address             = "{$state}, {$city}, {$addr}, {$room}";
				} else {
					$delivery_method_str = __('ПВЗ', 'wd-cdek-delivery');
					$address             = "{$pvz['Address']}<br>{$pvz['AddressComment']}";
				}
				?>
                <table>
                <tr class="woocommerce-cdek-delivery">
                    <td colspan="2">
                        <div id='cdek-delivery-selected' class=''>
                            <div id='cdek-delivery-selected-content'>
                                <div class='delivery-selected'>
                                    <div class='delivery-flex-row'>
                                        <div class='delivery-flex-col'>
                                            <div class='delivery-selected-content'>
                                                <div class='delivery-selected-title'>Служба доставки</div>
                                                <div>
                                                    <div class='delivery-selected-image service-image service-image-cdek'></div>
                                                    <div class='delivery-selected-name' data-delivery-uid="<?=$delivery_uid;?>"> <?= __('CDEK', 'wd-cdek-delivery'); ?> </div>
                                                </div>
                                            </div>
                                            <div class='delivery-selected-content'>
                                                <div class='delivery-selected-title'>Метод доставки</div>
                                                <div class='delivery-selected-value'><?= $delivery_method_str; ?></div>
                                            </div>
                                        </div>
                                        <div class='delivery-flex-col'>
                                            <div class='delivery-selected-content'>
                                                <div class='delivery-selected-title'>Адрес доставки</div>
                                                <div class='delivery-selected-value'><?= $address; ?></div>
                                            </div>
                                        </div>
                                        <div class='delivery-flex-col'>
                                            <div class='delivery-selected-content'>
                                                <div class='delivery-selected-title'>Стоимость доставки</div>
                                                <div class='delivery-selected-value'><?php echo $price . " " . get_woocommerce_currency_symbol(); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                </table>
                <?php
			}
		}
	}

}
