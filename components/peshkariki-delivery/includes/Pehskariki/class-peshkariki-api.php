<?php

namespace WDPK\Delivery\Peshkariki;


use DateTime;
use WBCR\Delivery\Base\Helper;
use WBCR\Delivery\Peshkariki\Peshkariki;

class PeshkarikiApi
{

    const URL = 'https://api.peshkariki.ru/commonApi/';
    const TEST_URL = 'https://test-api.peshkariki.ru/commonApi/';
    const CITIES = [
        1 => 'Москва',
        2 => 'Санкт-Петербург',
        3 => 'Екатеринбург',
        4 => 'Краснодар',
        5 => 'Челябинск',
        6 => 'Нижний Новгород',
    ];
    private $token;


    public function __construct()
    {
        $this->makeToken();
    }

    private function makeToken(){
        $body = array(
            'login'     => Peshkariki::settings()['client_login'] ?? "",
            'password'  => Peshkariki::settings()['client_password'] ?? "",
        );
        $apiResponse = $this->request('login', $body);
        if($apiResponse->success){
            $this->token = $apiResponse->response->token;
        } else {
            $this->token = false;
        }
    }

    /**
     * @param $order_id
     * @param $data
     *
     * @return int
     */
    public function make_shipping_order($order_id, $data){
        $package = $this->make_package($order_id, $data);
        $order = $this->order_shipping(false, $package);
        if( $order ) {
            return $order->$order_id->id;
        } else {
            return 0;
        }
    }

    /**
     * @param array $package
     *
     * @return string
     */
    public function calculate_shipping(array $package){
        $order = $this->order_shipping(true, $package);
        if( $order ) {
            return $order->delivery_price;
        } else {
            return 0;
        }
    }

    private function getCityId($city_name){
        return array_search(mb_strtolower($city_name), array_map('mb_strtolower', self::CITIES));
    }

    /**
     * @param bool  $is_calculate_needed
     * @param array $package
     *
     * @return mixed | bool
     */
    private function order_shipping(bool $is_calculate_needed, array $package){

        $destination = $package['destination'];
        $city_id = $this->getCityId($destination['state']);
        if (!$city_id) return false;

        $body = [
            'orders' => [
                [
                    'inner_id'          => $package['inner_id'] ?? '345133',
                    'comment'           => $package['comment'] ?? '',
                    'cash'              => 0,
                    'courier_addition'  => 0,
                    'clearing'          => 0,
                    'city_id'           => $city_id,
                    'ewalletType'       => 0,
                    'ewallet'           => Peshkariki::settings()['ewallet'] ?? '',
                    'promo_code'        => '',
                    'calculate'         => $is_calculate_needed,
                    'route'             => [
                        [
                            'city_id'    => Peshkariki::settings()['shop_city_id'] ?? "",
                            'name'       => Peshkariki::settings()['client_name'] ?? "",
                            'phone'      => Peshkariki::settings()['client_phone'] ?? "",
                            'street'     => Peshkariki::settings()['shop_street'] ?? "",
                            'building'   => Peshkariki::settings()['shop_building'] ?? "",
                            'time_from'    => DateTime::createFromFormat('Y-m-d', date('Y-m-d'))
                                ->modify('+4 day')->format('Y-m-d') . ' 12:00:00',
                            'time_to'    => DateTime::createFromFormat('Y-m-d', date('Y-m-d'))
                                ->modify('+4 day')->format('Y-m-d') . ' 15:00:00',
                            'return_dot' => '1'
                        ],
                        [
                            'city_id'    => $city_id,
                            'name'       => $package['name'] ?? 'Василий Пупкин',
                            'phone'      => $package['phone'] ?? '79212222222',
                            'city'       => $destination['city'],
                            'street'     => $destination['street'] ?? 'Ленинградская',
                            'building'   => $destination['building'] ?? '24А',
                            'time_from'    => DateTime::createFromFormat('Y-m-d', date('Y-m-d'))
                                ->modify('+4 day')->format('Y-m-d') . '  16:00:00',
                            'time_to'    => DateTime::createFromFormat('Y-m-d', date('Y-m-d'))
                                ->modify('+4 day')->format('Y-m-d') . '  19:00:00',
                            'items'      => $this->getItemsFromPackage($package)
                        ],
                    ]
                ]
            ],
            'token' => $this->token
        ];

        $apiResponse = $this->request('addOrder', $body);
        if($apiResponse->success){
            return $apiResponse->response;
        } else {
            Helper::log(sprintf("[%s] Code: %s.Message: %s", WDPK_PLUGIN_SLUG, $apiResponse->code, $apiResponse->additional));
            return false;
        }
    }

    private function getItemsFromPackage($package){
        $items = [];

        foreach ($package['contents'] as $item){
            $product = wc_get_product($item['product_id']);

            $item_data = [
                'name'   =>   $product->get_name(),
                'price'  =>   ceil($product->get_price()),
                'weight' =>   $product->get_weight() * 1000,
                'quant'  =>   $item['quantity']
            ];

            array_push($items, $item_data);
        }

        return $items;

    }


    /**
     * @param $endpoint
     * @param $body
     *
     * @return \WDPK\Delivery\Peshkariki\PeshkarikiApiResponse
     */
    private function request($endpoint, $body){
        $isDevModeEnabled = Peshkariki::settings()['devmode_enabled'];
        $url = $isDevModeEnabled === "no" ? self::URL : self::TEST_URL;
        $response = wp_remote_post($url . $endpoint, [
            'headers'   => [
                'Content-Type' => 'application/json'
            ],
            'body'      => json_encode($body)
        ]);

        if (is_wp_error($response)) {

        }

        $apiResponse = new PeshkarikiApiResponse();
        $apiResponse->getObjectFromJson($response['body']);
        return $apiResponse;
    }

    private function make_package($order_id, $data)
    {
        $order = wc_get_order($order_id);
        $items = $order->get_items();

        $contents = [];

        foreach ($items as $item){
            array_push($contents, [
                'product_id' => $item->get_data()['product_id'],
                'quantity' => $item->get_data()['quantity'],
            ]);
        }

        return [
            'inner_id' => "$order_id",
            'comment' => $data['order_comments'],
            'destination' => [
                'city' => $data['billing_city'],
                'state' => $data['billing_state'],
                'street' => $data['billing_address_1'],
                'building' => $data['billing_address_2'],
            ],
            'name' => sprintf('%s %s', $data['shipping_first_name'], $data['shipping_last_name']),
            'phone' => $data['billing_phone'],
            'contents' => $contents,
        ];
    }

}
