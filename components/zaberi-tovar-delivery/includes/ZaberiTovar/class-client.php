<?php


namespace WBCR\Delivery\ZaberiTovar;


use SimpleXMLElement;

class Client
{
    const SERVICE_PICKUP = 1;
    const SERVICE_COURIER = 2;
    const SERVICE_POST = 3;
    const SERVICE_REFUND = 5;

    const ENDPOINT = 'http://lc.zaberi-tovar.ru/api/';

    /**
     * @var string
     */
    private $api_login;


    /**
     * @var string
     */
    private $api_id;

    public function __construct( $settings = [] ) {
        $shipping_methods = WC()->shipping()->get_shipping_methods();

        if( isset( $shipping_methods[ZaberiTovar::SHIPPING_DELIVERY_ID] ) ) {
            $settings        = $settings ?: $shipping_methods[ZaberiTovar::SHIPPING_DELIVERY_ID]->settings;
            $this->api_login = $settings['api_login'];
            $this->api_id    = $settings['api_id'];
        }
    }

    // wp.loc WBCR/ZABERY-TOVAR 1.0.0
    private static function build_user_agent() {
        return sprintf("%s Webcraftic/ZABERI-TOVAR %s", $_SERVER['HTTP_HOST'], ZaberiTovar::app()->getPluginVersion());
    }

    public function get_by_order_id( $order_id ) {
        return $this->request( 'get_orders_by_order_id', [
            'orders' => [
                'order_id' => $order_id,
            ]
        ] );
    }

    /**
     * @param int $order_id
     * @param int $order_amount Сумма к оплате
     * @param int $insurance Страховая стоимость
     * @param string $recipient ФИО получателя
     * @param string $phone Телефон получателя
     * @param string $comment Комментарий к заказу
     * @param int $pickup_point_id Код точки самовывоза
     * @param int $weight Вес, в целых кг
     */
    public function create_pickup_order( $order_id, $order_amount, $insurance, $recipient, $phone, $comment, $pickup_point_id, $weight ) {
        return $this->create_order( $order_id, self::SERVICE_PICKUP, $order_amount, $insurance, $recipient, $phone, $comment, $pickup_point_id, $weight );
    }

    public function create_courier_order( $order_id, $order_amount, $insurance, $recipient, $phone, $comment, $pickup_point_id, $weight ) {
        return $this->create_order( $order_id, self::SERVICE_COURIER, $order_amount, $insurance, $recipient, $phone, $comment, $pickup_point_id, $weight );
    }

    /**
     * @param int $order_id
     * @param int $service
     * @param int $order_amount Сумма к оплате
     * @param int $insurance Страховая стоимость
     * @param string $recipient ФИО получателя
     * @param string $phone Телефон получателя
     * @param string $comment Комментарий к заказу
     * @param int $pickup_point_id Код точки самовывоза
     * @param int $weight Вес, в целых кг
     */
    public function create_order( $order_id, $service, $order_amount, $insurance, $recipient, $phone, $comment, $pickup_point_id, $weight ) {
        return $this->request( 'add_new_order', [
            'orders' => [
                'item' => [
                    'order_id' => $order_id,
                    'int_number' => (int) $order_id,
                    'service' => $service,
                    'order_amount' => $order_amount,
                    'd_price' => $insurance,
                    'fio' => trim( $recipient ),
                    'phone' => $phone,
                    'comment' => $comment,
                    'final_pv' => $pickup_point_id,
                    'weight' => ceil( $weight ),
                ]
            ]
        ] );
    }

    /**
     * @param $method
     * @param $body
     *
     * @return SimpleXMLElement
     */
    public function request( $method, $body ) {
        $ch = curl_init();

        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><methodCall/>");
        $xml->addChild( 'methodName', $method );
        $xml->addChild( 'client_name', $this->api_login );
        $xml->addChild( 'client_api_id', $this->api_id );

        $params = $xml->addChild('params');
        Helper::array_to_xml( $body, $params );

        $xml = str_replace("\n", "", $xml->asXML());
        Helper::log("Request to Zaberi Tovar API", $xml);

        curl_setopt_array( $ch, [
            CURLOPT_URL => sprintf("%s", self::ENDPOINT),
            CURLOPT_USERAGENT => self::build_user_agent(),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => "xml=$xml",
        ] );

        $response = curl_exec( $ch );

        curl_close( $ch );

        Helper::log("Zaberi Tovar response: ", $response);
        $response = simplexml_load_string( $response );

        return $response;
    }
}
