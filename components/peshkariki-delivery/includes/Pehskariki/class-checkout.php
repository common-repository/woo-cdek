<?php


namespace WDPK\Delivery\Peshkariki;

use WBCR\Delivery\Base\Checkout as BaseCheckout;
use WBCR\Delivery\Base\Helper;
use WBCR\Delivery\Peshkariki\CheckoutAjax;
use WBCR\Delivery\Peshkariki\Order;
use WBCR\Delivery\Peshkariki\Peshkariki;
use WP_Error;

/**
 * Class Checkout
 *
 * @package WDPK\Delivery\Peshkariki
 *
 * @author  Alexander Gorenkov <g.a.androidjc2@ya.ru> <Tg:@alex_brin>
 * @version 1.0.0
 * @since   1.0.0
 */
class Checkout extends BaseCheckout
{

    const DELIVERY_OBJECT = Peshkariki::class;

    const AJAX_HANDLER = CheckoutAjax::class;

    const ORDER_OBJECT = Order::class;

    const PLUGIN_URL = WDPK_PLUGIN_URL;

    const PLUGIN_DIR = WDPK_PLUGIN_DIR;

    const USE_TEMPLATE = false;

    const PRICE_FIELD = 'wdpk_price';

    public $price_field = self::PRICE_FIELD;

    private $selectedDeliveryMethod = [];

    private $api;


    public function __construct() {
	    parent::__construct();

	    add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate_delivery_method' ], 10, 2 );
	    add_action( 'woocommerce_checkout_order_processed', [ $this, 'save_peshkariki_delivery_order' ], 9, 2 );
	    add_action( 'woocommerce_checkout_order_processed', [ $this, 'create_peshkariki_delivery_order' ], 10, 2 );
	    add_filter( 'woocommerce_form_field_button', [ $this, 'generate_button_html' ], 10, 4 );
	    add_action( 'woocommerce_checkout_after_order_review', [ $this, 'add_after_checkout_form' ], - 10, 1 );

	    add_action( 'woocommerce_remove_cart_item', [ $this, 'remove_cart_item' ], 10, 2 );
	    add_filter( 'woocommerce_update_cart_action_cart_updated', [
		    $this,
		    'update_cart_action_cart_updated'
	    ], 10, 1 );

	    add_action( 'wp_ajax_peshkariki_get_shop_city', [ $this, 'get_shop_city' ] );
	    add_action( 'wp_ajax_nopriv_peshkariki_get_shop_city', [ $this, 'get_shop_city' ] );

	    $this->api = new PeshkarikiApi();

	    static::$app = wdpk_get_current_plugin();
    }

    public function validate_delivery_method( $fields, WP_Error $errors){

    }

    public function enqueue_scripts_checkout()
    {
        wp_enqueue_script(
            'wdpk-checkout-script',
            self::PLUGIN_URL . '/assets/js/pesh_app.js',
            ['jquery']
        );

        wp_localize_script(
            'wdpk-checkout-script',
            'wWoocommercePeshkarikiDeliveryIntegration',
            [
                'ajaxurl' => admin_url( 'admin-ajax.php' )
            ]
        );
    }

    public function get_shop_city(){
        $city_id = Peshkariki::settings()['shop_city_id'];
        wp_send_json_success([
	        'city' => PeshkarikiApi::CITIES[ $city_id ]
        ] );
    }

	public function create_peshkariki_delivery_order( $orderID, $data ) {
		$shipping_method = $this->get_shipping_method( $data['shipping_method'][0] );
		if ( isset( $shipping_method->id ) && $shipping_method->id !== Peshkariki::SHIPPING_DELIVERY_ID ) {
			return;
		}

		$payment_method = $data['payment_method'];

		$payment_when_receipt = $shipping_method->get_instance_option( 'payment_when_receipt', [ 'cod' ] );
		$payment_upon_receipt = in_array( $payment_method, $payment_when_receipt, false );

		$this->save_order_data( $orderID, $data );

		if ( $payment_upon_receipt ) {
			$this->send_peshkariki( $orderID, $data );
		}

		$this->clear_session();
	}

	public function save_peshkariki_delivery_order( $orderID, $data ) {
		$items         = WC()->cart->get_cart_contents();
		$data['items'] = $items;

		$data['selectedDeliveryMethod'] = $this->selectedDeliveryMethod;

		update_post_meta( $orderID, Order::DELIVERY_CHECKOUT_META_KEY, $data );
	}

	public function update_shipping_cost( $packages ) {

		foreach ( $packages as $index => $package ) {
			foreach ( $package['rates'] as $key => $rate ) {
				/** @var \WC_Shipping_Rate $rate */
				if ( strpos( $key, Peshkariki::SHIPPING_DELIVERY_ID ) !== false ) {
					$cost = $this->api->calculate_shipping( $package );

					// Если цена 0, то цена не выводится самим Woocommerce'ом
                    // Цена на фронтенде округляется до 2 знаков. Будет видно цену "0.00"
                    if ( empty( $cost ) ) {
                        $cost = 0.000001;
                    }

                    $rate->set_cost( (string) $cost );
                    $packages[ $index ]['rates'][ $key ] = $rate;
                }
            }
        }

        return $packages;
    }

    public function payment_complete_handler($order_id)
    {
        $order           = wc_get_order( $order_id );
        $shipping_method = get_post_meta( $order_id, Order::DELIVERY_CHECKOUT_META_KEY, true );
        Helper::log( "Payment Complete Handler", [ $shipping_method ] );

        $shipping_method = $this->get_shipping_method( $shipping_method['shipping_method'][0] );
        Helper::log( "", [ $shipping_method ] );
        if ( ! $shipping_method ) {
	        return;
        }

	    $payment_when_receipt = $shipping_method->get_instance_option( 'payment_when_receipt', [ 'cod' ] );
	    if ( in_array( $order->get_payment_method(), $payment_when_receipt, false ) ) {
		    return;
	    }

	    $data = get_post_meta( $order_id, Order::ORDER_DATA_META_KEY, true );

	    $this->send_peshkariki( $order_id, $data );

	    $this->clear_session();
    }

	public function send_peshkariki( $orderID, $data ) {
		if ( $this::$app->is_premium() ) {
			$pesh_order_id = $this->api->make_shipping_order( $orderID, $data );
			if ( $pesh_order_id ) {
				update_post_meta( $orderID, Order::ORDER_ID_META_KEY, $pesh_order_id );
				update_post_meta( $orderID, Order::DELIVERY_NAME_META_KEY, __( 'Пешкарики', 'wd-peshkariki-delivery' ) );
			}
		}
	}

	public function add_after_checkout_form( $checkout ) {

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
		$data = WC()->session->get( Peshkariki::SHIPPING_DELIVERY_ID );

		if ( isset( $data['formData']['wdpk_price'] ) ) {
			unset( $data['formData']['wdpk_price'] );
		}

		WC()->session->set( Peshkariki::SHIPPING_DELIVERY_ID, $data );
	}

}
