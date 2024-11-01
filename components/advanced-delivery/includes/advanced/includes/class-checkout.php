<?php

namespace WBCR\Delivery\Advanced;

use Exception;
use WBCR\Delivery\Base\Checkout as BaseCheckout;

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
	public function __construct() {

	}

	/**
	 * @inheritDoc
	 */
	public function payment_complete_handler( $order_id ) {
	}

}
