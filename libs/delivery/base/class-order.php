<?php

namespace WBCR\Delivery\Base;

/**
 * Class Order
 *
 * @package WBCR\Delivery\Base
 *
 * @author  Alexander Gorenkov <g.a.androidjc2@ya.ru> <Tg:@alex_brin>
 * @version 1.0.0
 * @since   1.0.0
 */
abstract class Order
{
    const ORDER_ID_META_KEY = '_delivery_order_id';
    const DELIVERY_ADDRESS_META_KEY = '_delivery_address';
    const DELIVERY_CHECKOUT_META_KEY = '_delivery_checkout_data';

    const ORDER_DATA_META_KEY = '_delivery_order_data';
}