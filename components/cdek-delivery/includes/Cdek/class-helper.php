<?php

namespace WBCR\Delivery\Cdek;

use WBCR\Delivery\Base\Helper as BaseHelper;

/**
 * Class Helper
 *
 * @package WBCR\Delivery\Cdek
 *
 * @author  Artem Prihodko <webtemyk@yandex.ru>
 * @version 1.0.0
 * @since   1.0.0
 */
class Helper extends BaseHelper {
	const DELIVERY_OBJECT = Cdek::class;

	protected static function getPrefix() {
		return "[Cdek Delivery]";
	}
}
