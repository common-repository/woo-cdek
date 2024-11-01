<?php

namespace WBCR\Delivery\Cdek;

/**
 * Class Order
 *
 * @package WBCR\Delivery\Cdek
 *
 * @author  Artem Prihodko <webtemyk@yandex.ru>
 * @version 1.0.0
 * @since   1.0.0
 */
class Order extends \WBCR\Delivery\Base\Order {
	const DELIVERY_NAME_META_KEY = '_delivery_name';
	const ORDER_SHIPPING_METHOD_KEY = '_delivery_shipping_method';
}