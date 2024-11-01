<?php /** @noinspection PhpUndefinedClassInspection */

namespace WBCR\Delivery\Advanced;

use WBCR\Delivery\Base\Delivery;

class Advanced extends Delivery {
	private static $delivery;

	const SHIPPING_DELIVERY_ID = 'wbcr_advanced_delivery';

	const CHECKOUT_HANDLER = Checkout::class;

	public $key = 'conditions';
	public $settings_key;

	/**
	 * @return Plugin|\Wbcr_Factory437_Plugin
	 */
	public static function app() {
		return wdad_get_current_plugin();
	}

	/**
	 * @return self
	 */
	public static function delivery() {
		return self::$delivery;
	}

	public function init() {
		$this->title              = __( 'Advanced Delivery', 'wd-advanced-delivery' );
		$this->method_title       = __( 'Advanced Delivery', 'wd-advanced-delivery' );
		$this->method_description = __( 'Advanced Delivery extension adds shipping method to Checkout page.', 'wd-advanced-delivery' );

		$this->settings_key = $this->get_field_key( $this->key );

		parent::init();

		self::$delivery = $this;
	}

	/**
	 * @param $json_decode
	 *
	 * @return mixed
	 */
	public function get_conditions( $json_decode = true ) {
		$conditions = $this->get_option( $this->key );
		if ( $json_decode ) {
			return json_decode( $conditions, true );
		} else {
			return $conditions;
		}


	}

	public function calculate_shipping( $package = [] ) {
		$enabled = $this->get_option( 'enabled' );
		$methods = $this->get_conditions();
		if ( ! $enabled || empty( $methods ) ) {
			return;
		}

		$conditions = new MatchConditions();
		foreach ( $methods as $key => $method ) {

			// Check if method conditions match
			$match = $conditions->match_conditions( $method['conditions'], $package );

			// Add match to array
			if ( true == $match ) {
				$cost = $this->calculate_shipping_cost( $package, $method );
				$args = apply_filters( 'wdad_advanced_rate_args', [
					'id'       => 'wdad-advanced-shipping-' . $key,
					'label'    => $method['shipping_title'],
					'cost'     => $cost,
					'taxes'    => ( 'taxable' == $method['tax'] ) ? '' : false,
					'calc_tax' => 'per_order',
					'package'  => $package,
				] );
				$this->add_rate( $args );
			}
		}
	}

	/**
	 * Calculate the costs per item.
	 *
	 * @param $package
	 * @param $method
	 *
	 * @return float          Shipping costs.
	 * @since 1.0.0
	 *
	 */
	public function calculate_cost_per_item( $package, $method ) {
		$cost = 0;
		// Shipping per item
		foreach ( $package['contents'] as $item_id => $values ) {
			$_product = $values['data'];
			if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {
				if ( strstr( $method['cost_per_item'], '%' ) ) {
					$cost = $cost + ( $values['line_total'] / 100 ) * (float) str_replace( '%', '', $method['cost_per_item'] );
				} else {
					$cost = $cost + $values['quantity'] * (float) $method['cost_per_item'];
				}
			}

		}

		return $cost;
	}


	/**
	 * Calculate the costs per weight.
	 *
	 * @param $package
	 * @param $method
	 *
	 * @return float          Shipping costs.
	 * @since 1.0.0
	 *
	 */
	public function calculate_cost_per_weight( $package, $method ) {
		$cost = 0;
		// Weight per item
		foreach ( $package['contents'] as $item_id => $values ) {
			$_product = $values['data'];
			if ( $values['quantity'] > 0 && $_product->needs_shipping() && $_product->get_weight() ) {
				$cost = $cost + ( ( $values['quantity'] * $_product->get_weight() ) * (float) $method['cost_per_weight'] );
			}
		}

		return $cost;
	}

	/**
	 * Calculate costs.
	 *
	 * Calculate the shipping costs for this method.
	 *
	 * @param mixed $package List containing all products for this method.
	 * @param string $method_id Shipping method ID.
	 *
	 * @return float           Shipping costs.
	 * @since 1.0.0
	 *
	 */
	public function calculate_shipping_cost( $package, $method ) {

		$cost = (float) $method['shipping_cost'];
		$cost = $cost + (float) $this->get_fee( $method['handling_fee'], $package['contents_cost'] );
		$cost = $cost + (float) $this->calculate_cost_per_item( $package, $method );
		$cost = $cost + (float) $this->calculate_cost_per_weight( $package, $method );

		return apply_filters( 'wdad_advanced_calculate_shipping_cost', $cost, $package, $method, $this );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function admin_enqueue_scripts() {
		wp_enqueue_style( self::SHIPPING_DELIVERY_ID . '-admin-css', WDAD_PLUGIN_URL . '/admin/assets/css/admin.css', [], static::app()->getPluginVersion() );
		wp_enqueue_style( self::SHIPPING_DELIVERY_ID . '-condition-css', WDAD_ADVANCED_URL . '/assets/css/conditions.css', [], static::app()->getPluginVersion() );

		wp_enqueue_script( self::SHIPPING_DELIVERY_ID . '-admin', WDAD_PLUGIN_URL . '/admin/assets/js/admin.js', [ 'jquery' ], static::app()->getPluginVersion() );
		wp_localize_script( self::SHIPPING_DELIVERY_ID . '-admin', 'localStrings', [
			'inner'   => __( 'Inner: ', 'wd-advanced-delivery' ),
			'outer'   => __( 'Outer: ', 'wd-advanced-delivery' ),
			'package' => __( 'Package: ', 'wd-advanced-delivery' ),
			'max'     => __( 'Max: ', 'wd-advanced-delivery' ),
		] );

		wp_enqueue_script( self::SHIPPING_DELIVERY_ID . '-admin-shipping-methods', WDAD_ADVANCED_URL . '/assets/js/advanced-shipping-methods.js', [
			'jquery',
			'wp-util',
			'underscore',
			'backbone',
			'jquery-ui-sortable',
			'wc-backbone-modal'
		], static::app()->getPluginVersion() );
		wp_localize_script( self::SHIPPING_DELIVERY_ID . '-admin-shipping-methods', 'shippingZoneMethodsLocalizeScript', array(
			//'methods'                 => $zone->get_shipping_methods( false, 'json' ),
			'nonce'         => wp_create_nonce( 'wdad-ajax-nonce' ),
			'action_prefix' => 'wdad_',
			'conditions_id' => 'woocommerce_' . self::SHIPPING_DELIVERY_ID . '_conditions',

			'wdad_shipping_method_nonce' => wp_create_nonce( 'wdad_shipping_method_nonce' ),
			'strings'                    => array(
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
				'save_changes_prompt'     => __( 'Do you wish to save your changes first? Your changed data will be discarded if you choose to cancel.', 'woocommerce' ),
				'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce' ),
				'add_method_failed'       => __( 'Shipping method could not be added. Please retry.', 'woocommerce' ),
				'yes'                     => __( 'Yes', 'woocommerce' ),
				'no'                      => __( 'No', 'woocommerce' ),
				'default_zone_name'       => __( 'Zone', 'woocommerce' ),
			),
		) );
	}

	public static function admin_enqueue_scripts_hook_handler() {
		parent::admin_enqueue_scripts_hook_handler();
		/** @var \WP_Screen $current_screen */
		$current_screen = get_current_screen();

		if ( $current_screen->id === 'shop_order' && $current_screen->post_type == 'shop_order' ) {
			wp_enqueue_style( self::SHIPPING_DELIVERY_ID . '-admin-order-css', WDAD_PLUGIN_URL . '/admin/assets/css/order.css', [], static::app()->getPluginVersion() );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function validate_settings( $settings ) {
		try {
			//validate
			$is_ok = true;
		} catch ( \Exception $exception ) {
			Helper::log( "Error while validation: " . $exception->getMessage(), $exception->getTrace() );

			// Если не удалось проверить, то нужно просто сохранить настройки
			return [ true, '' ];
		}

		if ( ! $is_ok ) {
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
		return [
			/*
			'developersTitle'           => [
				'title' => __( 'Developers settings', 'wd-advanced-delivery' ),
				'type'  => 'title',
			],
			*/ 'enabled'                => [
				'title'   => __( 'Advanced Delivery method', 'wd-advanced-delivery' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', 'wd-advanced-delivery' ),
				'default' => 'no',
			],
			/*
            'hide_other_when_free' => [
				'title'   => __( 'Hide other shipping', 'wd-advanced-delivery' ),
				'type'    => 'checkbox',
				'label'   => __( 'Hide other shipping methods when free shipping is available', 'wd-advanced-delivery' ),
				'default' => 'no'
			],
			*/ 'conditions' => [
				'type' => 'wdad_delivery_table',
			],

			'devmode' => [
				'title'       => __( 'Development mode', 'wd-advanced-delivery' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable', 'wd-advanced-delivery' ),
				'description' => __( 'Development mode.', 'wd-advanced-delivery' ),
				'default'     => 'no',
			],
		];
	}

	/**
	 * Output the admin options table.
	 */
	public function admin_options() {
		?>
        <div class="wdad-settings"><a
                href="<?= static::app()->get_support()->get_docs_url( true, 'settings_page' ) ?>"
                class="button button-secondary"
                target="_blank"><?= __( 'Documentation', 'wd-advanced-delivery' ) ?></a>
        </div><?php

		parent::admin_options();
	}

	/**
	 * Settings tab table.
	 *
	 * Load and render the table on the Advanced Shipping settings tab.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function generate_wdad_delivery_table_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title' => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();

		//$advanced = &$this;
		$methods = $this->get_conditions();

		/**
		 * Load conditions table file
		 */
		require WDAD_ADVANCED_DIR . '/views/delivery-table.php';

		require WDAD_ADVANCED_DIR . '/views/html-admin-page-shipping-add-template.php';

		return ob_get_clean();
	}

	/**
	 * Render meta box.
	 *
	 * Get conditions meta box contents.
	 *
	 * @since 1.0.0
	 */
	public function render_wdad_conditions( $conditions = [] ) {

		/**
		 * Load meta box conditions view
		 */
		require WDAD_ADVANCED_DIR . '/views/conditions.php';

	}


	/**
	 * Render meta box.
	 *
	 * Get settings meta box contents.
	 *
	 * @since 1.0.0
	 */
	public function render_wdad_settings( $settings = [] ) {

		/**
		 * Load meta box settings view
		 */
		require WDAD_ADVANCED_DIR . '/views/settings.php';

	}

	/**
	 * @param $order \WC_Order
	 */
	public static function order_data_output( $order ) {

	}

}
