<?php
/**
 * Pickpoint
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce
 * @version 1.0.0
 */

use WPickpoint\Delivery\ShippingMethod\Pickpoint;

$checkout    = WC()->checkout();
$fields      = $checkout->get_checkout_fields( Pickpoint::SHIPPING_METHOD_ID );
$pvz_id      = $checkout->get_value( 'wpickpoint_pvz_id' );
$pvz_address = $checkout->get_value( 'wpickpoint_pvz_address' );
$data        = WC()->session->get( Pickpoint::SHIPPING_METHOD_ID );

?>

<tr class="woocommerce-pickpoint-delivery">
	<td colspan="2">
		<?php woocommerce_form_field( 'wpickpoint_pvz_id', $fields['wpickpoint_pvz_id'], $pvz_id ); ?>
		<?php woocommerce_form_field( 'wpickpoint_pvz_address', $fields['wpickpoint_pvz_address'], $pvz_address ); ?>

		<div class="woocomerce-pickpoint-delivery__choice-postamat">
			<a id='woocomerce-pickpoint-delivery__choice-postamat-button' class="button">
				<?php if ( empty( $pvz_id ) ): ?>
					<?php echo __( 'Выбрать пункт выдачи заказа', 'pickpoint-delivery' ); ?>
				<?php else: ?>
					<?php echo __( 'Изменить пункт выдачи закака', 'pickpoint-delivery' ); ?>
				<?php endif; ?>
			</a>
		</div>
		<?php if ( ! empty( $pvz_id ) ): ?>
        <div id="woocommerce-pickpoint-delivery__details">
            <h4><?php echo __( 'Доставка', 'pickpoint-delivery' ); ?></h4>

            <div class="delivery-selected">
                <div class="delivery-flex-row">
                    <div class="delivery-flex-col">
                        <div class="delivery-selected-content">
                            <div class="delivery-selected-title"><?php echo __( 'Служба доставки', 'pickpoint-delivery' ); ?></div>
                            <div>
                                <div class="delivery-selected-image service-image service-image-pickpoint"></div>
                                <div class="delivery-selected-name"></div>
                            </div>
                        </div>
                        <div class="delivery-selected-content">
                            <div class="delivery-selected-title"><?php echo __( 'Адрес', 'pickpoint-delivery' ); ?></div>
                            <div class="delivery-selected-value"><?php echo esc_html( $pvz_address ); ?></div>
                        </div>
                    </div>
                    <div class="delivery-flex-col">
                        <div class="delivery-selected-content">
                            <div class="delivery-selected-title"><?php echo __( 'Ориентировочные сроки доставки', 'pickpoint-delivery' ); ?></div>
                            <div class="delivery-item-date-value">
								<?php if ( isset( $data['delivery_period'] ) ): ?>
									<?php echo esc_html( $data['delivery_period'] ); ?>&nbsp;<?php echo __( 'дней', 'pickpoint-delivery' ); ?>
								<?php endif; ?>
                            </div>
                            <div class="delivery-flex-col">
                                <div class="delivery-selected-content">
                                    <div class="delivery-selected-title"><?php echo __( 'Стоимость доставки', 'pickpoint-delivery' ); ?></div>
                                    <div class="delivery-selected-value">
										<?php if ( isset( $data['delivery_cost'] ) ): ?>
											<?php echo esc_html( $data['delivery_cost'] ); ?>&nbsp;<?php echo __( 'руб.', 'pickpoint-delivery' ); ?>
										<?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			</div>

		</div>
	</td>
</tr>
<?php endif; ?>

