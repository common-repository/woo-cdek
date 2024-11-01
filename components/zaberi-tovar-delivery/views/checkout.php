<?php
/**
 * Zaberi Tovar
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce
 * @version 1.0.0
 */

$checkout = WC()->checkout();
$fields   = $checkout->get_checkout_fields( \WBCR\Delivery\ZaberiTovar\ZaberiTovar::SHIPPING_DELIVERY_ID );

$post_data     = '';
$delivery_data = [];

if ( isset( $_POST['post_data'] ) && ! empty( $_POST['post_data'] ) ) {
	parse_str( $_POST['post_data'], $post_data );

	if ( isset( $post_data['wdzt_data'] ) && ! empty( $post_data['wdzt_data'] ) ) {
		$delivery_data = json_decode( $post_data['wdzt_data'], true );
	}
}
?>
<script src="//api.zaberi-tovar.ru/widget/pvz.js"></script>
<script src="//api.zaberi-tovar.ru/widget/postmessage.js"></script>

<script src="<?= WDZT_PLUGIN_URL . '/assets/js/checkout-template.js' ?>"></script>
<style>
    .woocommerce-zaberi-tovar-delivery > td {
        padding-left: 0 !important;
    }
</style>

<?php if ( ! empty( $delivery_data ) ): ?>
    <tr class="woocommerce-zaberi-tovar-delivery">
        <td colspan="2">
            <div class="form-row form-row-wide">
                <div id="zaberi-tovar-delivery-selected" class="">
                    Выбранный вариант доставки
                    <div id="zaberi-tovar-delivery-selected-content">
                        <div class="delivery-selected">
                            <table>
                                <tr>
                                    <th>Ориентировочный срок доставки</th>
                                    <td><?= $delivery_data['srok'] ?: 1 ?> раб. дн.</td>
                                </tr>
                                <tr>
                                    <th>Адрес доставки</th>
                                    <td><?= $delivery_data['cityname'] ?>, <?= $delivery_data['address'] ?></td>
                                </tr>
                                <tr>
                                    <th>Стоимость доставки</th>
                                    <td><?= $delivery_data['price'] ?> р.</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
<?php endif; ?>
<tr class="woocommerce-zaberi-tovar-delivery-btn">
    <td colspan="2">
        <div class="form-row form-row-wide">
            <div class="group">
                <div class="group-item">
                    <button id="wdzt_select_pickup_point"
                            type="button"
                            style="display: block;"><?= __( 'Select pickup point', 'wd-zaberi-tovar' ) ?></button>
                </div>
            </div>
        </div>
    </td>
</tr>
