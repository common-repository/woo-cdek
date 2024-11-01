<?php


namespace WBCR\Delivery\Base;

use Wbcr_Factory437_Base;
use WC_Cart;
use WC_Customer;
use WC_Shipping_Method;

/**
 * Class Checkout
 *
 * @package WBCR\Delivery\Base
 *
 * @author  Alexander Gorenkov <g.a.androidjc2@ya.ru> <Tg:@alex_brin>
 * @version 1.1.0
 * @since   1.1.0
 */
abstract class Checkout
{
    #region Consts

    /**
     * URL до плагина. Необходимо для подключения js/css файлов
     */
    const PLUGIN_URL = '';

    /**
     * Объект обработчика AJAX запросов на странице чекаута
     *
     * @type CheckoutAjax
     */
    const AJAX_HANDLER = CheckoutAjax::class;

    /**
     * @type Order
     */
    const ORDER_OBJECT = Order::class;

    /**
     * @type Delivery
     */
    const DELIVERY_OBJECT = Delivery::class;

    /**
     * Название файла шаблона
     * Файл используется как шаблон на странице чекаут в правой части страницы (под таблицей)
     *
     * @type string
     */
    const TEMPLATE_FILENAME = 'checkout.php';

    /**
     * Название директории / путь до директории с шаблонами
     * Относительно корня плагина
     *
     * @type string
     */
    const TEMPLATE_FILENAME_PATH = 'views';

    /**
     * Директория с плагином
     *
     * @type string
     */
    const PLUGIN_DIR = '';

    /**
     * bool, используется ли шаблон на странице чекаута
     * @type bool
     */
    const USE_TEMPLATE = false;

    #endregion Consts

    public static function register() {
        $ajax_handler = static::AJAX_HANDLER;
        $ajax_handler::register();

        return new static;
    }

    #region Properties

    private $meta_cache = [];

    public $price_field = 'wbcr_price';

    /**
     * @var Wbcr_Factory437_Base
     */
    public static $app;

    #endregion Properties

    public function __construct() {
        add_filter( 'woocommerce_checkout_fields', [ $this, 'checkout_fields' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts_hook_handler' ] );

        add_filter( 'woocommerce_checkout_get_value', [ $this, 'get_fields_values' ], 10, 2 );
        add_action( 'woocommerce_checkout_update_customer', [ $this, 'save_checkout_fields_value_to_customer' ], 10, 2 );
        add_filter( 'woocommerce_shipping_packages', [ $this, 'update_shipping_cost' ] );
        add_action( 'woocommerce_update_cart_action_cart_updated', [ $this, 'clear_shipping_data_on_cart_update' ] );

        add_action( 'woocommerce_checkout_update_order_review', [ $this, 'save_data_on_order_review_hook_handler' ] );
        add_action( 'woocommerce_checkout_process', [ $this, 'save_checkout_fields_values_to_session' ] );

        add_action( 'woocommerce_form_field_hidden_wbcr_delivery', [ $this, 'checkout_hidden_field_type' ], 10, 4 );

        add_filter( 'woocommerce_order_get_total', [ $this, 'get_total_hook_handler' ], 999, 2 );

        add_action( 'woocommerce_payment_complete', [ $this, 'payment_complete_handler' ] );

        if( static::USE_TEMPLATE ) {
            add_action( 'woocommerce_review_order_before_order_total', [ $this, 'review_order_before_handler' ] );
        }
    }

    public function review_order_before_handler() {
        $shipping_method = WC()->session->get( 'chosen_shipping_methods' );

        $delivery_object = static::DELIVERY_OBJECT;
        if( ! empty( $shipping_method[0] )
            && strpos( $shipping_method[0], $delivery_object::SHIPPING_DELIVERY_ID ) !== false) {
            add_filter( 'woocommerce_locate_template', [ $this, 'locate_template' ], 10, 2 );
            wc_get_template( static::TEMPLATE_FILENAME );
        }
    }

    public function locate_template( $template, $template_name ) {
        if( ! static::USE_TEMPLATE ) {
            return $template;
        }

        if( $template_name !== static::TEMPLATE_FILENAME ) {
            return $template;
        }

        $template = realpath( sprintf( "%s/%s/%s", static::PLUGIN_DIR, static::TEMPLATE_FILENAME_PATH, $template_name ) );

        return file_exists( $template ) ? $template : false;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function checkout_hidden_field_type( $_, $key, $args, $value ) {
        $customAttributes = [];
        foreach ( $args['custom_attributes'] as $attr => $attrValue ) {
            $customAttributes[] = sprintf( '%s="%s"', esc_attr( $attr ), esc_attr( $attrValue ) );
        }

        /** @noinspection HtmlUnknownAttribute */
        return sprintf( '<input type="hidden" name="%s" id="%s" value="%s" %s />',
            esc_attr( $key ), esc_attr( $args['id'] ), esc_attr( $value ), implode( ' ', $customAttributes ) );
    }

    public function update_shipping_cost( $packages ) {
        $delivery_object = static::DELIVERY_OBJECT;

        foreach ( $packages as $index => $package ) {
            foreach ( $package['rates'] as $key => $rate ) {
                /** @var \WC_Shipping_Rate $rate */
                if ( strpos( $key, $delivery_object::SHIPPING_DELIVERY_ID ) !== false ) {
                    $data = WC()->session->get( $delivery_object::SHIPPING_DELIVERY_ID );
                    $cost = isset( $data['formData'][$this->price_field] )
                        ? $data['formData'][$this->price_field]
                        : 0;

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

    public function clear_shipping_data_on_cart_update() {
        $delivery_object = static::DELIVERY_OBJECT;
        $data = WC()->session->get( $delivery_object::SHIPPING_DELIVERY_ID );

        foreach($this->get_plugin_fields() as $field) {
            unset($data['formData'][$field]);
        }

        WC()->session->set( $delivery_object::SHIPPING_DELIVERY_ID, $data );
    }

    #region Sessions

    /**
     * @param $post_data
     */
    final public function save_data_on_order_review_hook_handler( $post_data ) {
        $chosen_shipping_method_ids = wc_get_chosen_shipping_method_ids();
        $delivery_object            = static::DELIVERY_OBJECT;

        if ( ! empty( $chosen_shipping_method_ids[0] ) && strpos( $chosen_shipping_method_ids[0],
                $delivery_object::SHIPPING_DELIVERY_ID ) !== false ) {
            parse_str( $post_data, $post_data );

            if ( $this->save_data_on_order_review_zero_price_condition( $post_data ) ) {
                $post_data[$this->price_field] = "0";
            }

            $post_data[$this->price_field] = $this->calculate_delivery_discount( $post_data[$this->price_field], $post_data['shipping_method'][0] );

            $post_data = $this->save_data_on_order_review( $post_data );

            $form_data = [];
            foreach ( $this->get_plugin_fields() as $field ) {
                if ( isset( $post_data[$field] ) ) {
                    $form_data[$field] = $post_data[$field];
                }
            }

            if ( ! empty( $form_data ) ) {
                WC()->customer->set_props( $form_data );
                $session             = WC()->session->get( $delivery_object::SHIPPING_DELIVERY_ID );
                $session['formData'] = $form_data;
                WC()->session->set( $delivery_object::SHIPPING_DELIVERY_ID, $session );
                WC()->customer->save();
            }
        }
    }

    /**
     * Возвращает true или false. Если true, то в качестве цены доставки будет установлен 0
     * Необходимо проверять, что какие-то важные данные не заполнены, чтобы сбросить цену в таблице на фронте возле названия доставки
     * @param array $post_data
     *
     * @return bool
     */
    public function save_data_on_order_review_zero_price_condition( $post_data ) {
        return false;
    }

    /**
     * Дополнительная обработка пришедших данных при необходимости
     * Вызывается после применения скидки на доставку
     *
     * @param array $post_data
     * @return array
     */
    public function save_data_on_order_review( $post_data ) {
        return $post_data;
    }

    public function save_checkout_fields_values_to_session() {
        $values    = [];
        $dataArray = WC()->checkout()->get_posted_data();

        foreach ( $this->get_plugin_fields() as $field ) {
            $values[ $field ] = isset( $dataArray[ $field ] ) ? wc_clean( wp_unslash( $dataArray[ $field ] ) ) : null;
        }

        $delivery_object = static::DELIVERY_OBJECT;

        $session             = WC()->session->get( $delivery_object::SHIPPING_DELIVERY_ID );
        $session['formData'] = $values;
        WC()->session->set( $delivery_object::SHIPPING_DELIVERY_ID, $session );
    }

    #endregion Sessions

    public function checkout_fields( $fields ) {
        $fields['billing'][$this->price_field] = [
            'type'              => 'hidden_wbcr_delivery',
            'priority'          => 44,
            'custom_attributes' => [
                'data-type' => 'price',
            ],
        ];

        $delivery_object = static::DELIVERY_OBJECT;
        $fields[$delivery_object::SHIPPING_DELIVERY_ID] = [];

        return $fields;
    }

    #region Enqueue scripts

    public function enqueue_scripts_hook_handler() {
        if ( is_checkout() ) {
            $this->enqueue_scripts_checkout();
        }

        $this->enqueue_scripts();
    }

    public function enqueue_scripts_checkout() {
    }

    public function enqueue_scripts() {
    }

    #endregion Enqueue scripts

    #region Helpers

    public function save_checkout_fields_value_to_customer( WC_Customer $customer, $data ) {
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $this->get_plugin_fields(), true ) ) {
                $customer->update_meta_data( $key, $value );
            }
        }
    }

    public function get_fields_values( $inputValue, $inputName ) {
        $delivery_object = static::DELIVERY_OBJECT;
        if ( in_array( $inputName, $this->get_plugin_fields(), true ) ) {
            $sessionData = WC()->session->get( $delivery_object::SHIPPING_DELIVERY_ID );

            return isset( $sessionData['formData'][ $inputName ] ) ? $sessionData['formData'][ $inputName ] : null;
        }

        return $inputValue;
    }

    /**
     * @return float
     */
    protected function get_cart_cost() {
        return (float) WC()->cart->get_total( 'float' );
    }

    /**
     * @param int $order_id
     *
     * @return mixed
     */
    public function get_order_data( $order_id ) {
        $order_object = static::ORDER_OBJECT;

        return get_post_meta( $order_id, $order_object::ORDER_DATA_META_KEY, true );
    }

    /**
     * @param int   $order_id
     * @param mixed $data
     *
     * @return bool|int
     */
    public function save_order_data( $order_id, $data ) {
        $order_object = static::ORDER_OBJECT;

        return update_post_meta( $order_id, $order_object::ORDER_DATA_META_KEY, $data );
    }

    /**
     * @param string|array $selected_shipping_method
     *
     * @return WC_Shipping_Method
     */
    protected function get_shipping_method( $selected_shipping_method ) {
        if ( is_string( $selected_shipping_method ) ) {
            $selected_shipping_method = explode( ':', $selected_shipping_method );
        }

        Helper::log("Shipping method", [$selected_shipping_method]);

        $shipping_method              = WC()->shipping()->get_shipping_methods()[$selected_shipping_method[0]];
        $shipping_method->instance_id = (int) $selected_shipping_method[1];

        return $shipping_method;
    }

    #endregion Helpers

    #region Abstract functions

    /**
     * @param int $order_id
     */
    abstract public function payment_complete_handler( $order_id );

    /**
     * Возвращает список полей плагина
     * Эти поля попадают в сессионное хранилище
     *
     * @return string[]
     */
    protected function get_plugin_fields() {
        return [
            $this->price_field,
        ];
    }

    protected function get_clear_session_keys() {
        $session = [];

        foreach($this->get_plugin_fields() as $field) {
            $session[$field] = null;
        }

        return $session;
    }

    #endregion Abstract functions

    protected function get_hook_prefix() {
        return "woocommerce_wbcr_delivery_";
    }

    final public function calculate_delivery_discount( $delivery_real_cost, $selected_shipping_method ) {
        $shipping_method = $this->get_shipping_method( $selected_shipping_method );

        if( $shipping_method instanceof \stdClass ) {
            return $delivery_real_cost;
        }

        $delivery_cost = $delivery_real_cost;
        $cart_cost     = $this->get_cart_cost();

        $is_enable     = $shipping_method->get_instance_option( 'delivery_discount_enable', 'no' ) === 'yes';
        $min_cart_cost = (float) $shipping_method->get_instance_option( 'delivery_discount_min_order_amount', 0 );
        $discount      = (float) $shipping_method->get_instance_option( 'delivery_discount_amount', 0 );

        if ( $discount < 0 ) {
            $discount = 0;
        } elseif ( $discount > 100 ) {
            $discount = 100;
        }

        if ( $is_enable && $cart_cost >= $min_cart_cost ) {
            $delivery_cost -= $delivery_cost * ($discount / 100);
        }

        /**
         * Delivery price filter
         *
         * @param float $delivery_cost      The applicable delivery charge
         * @param float $delivery_real_cost Real cost of delivery
         * @param WC_Cart
         *
         * @since 1.0.7
         *
         */
        return apply_filters( $this->get_hook_prefix() . 'cost', $delivery_cost, $delivery_real_cost, WC()->cart );
    }

    /**
     * Обработка хук-фильтра, который возвращает текущую стоимость заказа
     * Если мета данных заказа нет, то вернется исходное значение
     * В остальных случаях вызывается get_total_handler
     *
     * @param string    $amount
     * @param \WC_Order $order
     *
     * @return string
     * @see Checkout::get_total_handler
     */
    final public function get_total_hook_handler( $amount, $order ) {
        if ( empty( $this->meta_cache ) ) {
            $meta = $this->get_order_data( $order->get_id() );

            if ( ! $meta ) {
                return $meta;
            }

            $this->meta_cache = $meta;
        }

        return (string) $this->get_total_handler( (float) $amount, $this->meta_cache );
    }

    /**
     * Возвращает полную стоимость заказа включая стоимость доставки
     *
     * @param float $amount Текущая стоимость
     * @param mixed $meta   Мета данные
     *
     * @return float
     */
    public function get_total_handler( $amount, $meta ) {
        return $amount;
    }
}
