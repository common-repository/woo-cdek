<?php

use WBCR\Delivery\Cdek\Cdek;

/**
 * CDEK template
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce
 * @version 1.0.0
 */

$checkout = WC()->checkout();
$data     = WC()->session->get( Cdek::SHIPPING_DELIVERY_ID );
$data     = isset( $data['formData'] ) ? $data['formData'] : [];

$delivery_method = isset( $data['wdcd_delivery_method'] ) ? $data['wdcd_delivery_method'] : '';
$price           = isset( $data['wdcd_price'] ) ? $data['wdcd_price'] : '';
$pvz             = isset( $data['wdcd_pickup_point'] ) ? json_decode( $data['wdcd_pickup_point'], true ) : '';

if ( isset( $data['ship_to_different_address'] ) && $data['ship_to_different_address'] ) {
	$customer_data = 'shipping';
} else {
	$customer_data = 'billing';
}

$country = $checkout->get_value( $customer_data . '_country' );
$city    = $checkout->get_value( $customer_data . '_city' );
$state   = $checkout->get_value( $customer_data . '_state' );
$addr    = $checkout->get_value( $customer_data . '_address_1' );
$room    = $checkout->get_value( $customer_data . '_address_2' );

if ( ! empty( $delivery_method ) ) {
	if ( $delivery_method == 'courier' ) {
		$delivery_method_str = __( 'Курьер', 'wd-cdek-delivery' );
		$address             = "{$state}, {$city}, {$addr}, {$room}";
	} else {
		$delivery_method_str = __( 'ПВЗ', 'wd-cdek-delivery' );
		$address             = "{$pvz['Address']}<br>{$pvz['AddressComment']}";
	}
	?>
    <tr class="woocommerce-cdek-delivery">
        <td colspan="2">
            <div id='cdek-delivery-selected' class=''>
                Выбранный способ доставки
                <div id='cdek-delivery-selected-content'>
                    <div class='delivery-selected'>
                        <div class='delivery-flex-row'>
                            <div class='delivery-flex-col'>
                                <div class='delivery-selected-content'>
                                    <div class='delivery-selected-title'>Служба доставки</div>
                                    <div>
                                        <div class='delivery-selected-image service-image service-image-cdek'></div>
                                        <div class='delivery-selected-name'> <?= __( 'CDEK', 'wd-cdek-delivery' ); ?> </div>
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
    <tr class="woocommerce-cdek-delivery-button">
        <td colspan="2">
            <div class="form-row form-row-wide">
                <div class="group">
                    <div class="group-item">
                        <span class="wdcd_loader" id="wdcd_widget_loader" style="display: none;"></span>
                        <button id='wdcd_btn_delivery_method' class='wdcd_btn_delivery_method'
                                style='display: none;'><?= __( 'Choose a delivery method', 'wd-cdek-delivery' ); ?></button>
                    </div>
                </div>
            </div>
        </td>
    </tr>    <?php
} else {
	?>
    <tr class="woocommerce-cdek-delivery-button">
        <td colspan="2">
            <div class="form-row form-row-wide">
                <div class="group">
                    <div class="group-item">
                        <span class="wdcd_loader" id="wdcd_widget_loader" style="display: none;"></span>
                        <button id='wdcd_btn_delivery_method' class='wdcd_btn_delivery_method'
                                style='display: none;'><?= __( 'Choose a delivery method', 'wd-cdek-delivery' ); ?></button>
                    </div>
                </div>
            </div>
        </td>
    </tr>
	<?php
}
?>
