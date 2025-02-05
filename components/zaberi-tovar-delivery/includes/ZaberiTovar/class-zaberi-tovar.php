<?php

namespace WBCR\Delivery\ZaberiTovar;

use WBCR\Delivery\Base\Delivery;

class ZaberiTovar extends Delivery
{
    /**
     * @inheritdoc
     */
    const SHIPPING_DELIVERY_ID = 'wbcr_zaberi_tovar_delivery';

    /**
     * @inheritdoc
     */
    const CHECKOUT_HANDLER = Checkout::class;

    public function init() {
        $this->title              = __('Zaberi Tovar', 'wd-zaberi-tovar');
        $this->method_title       = __('Zaberi Tovar', 'wd-zaberi-tovar');
        $this->method_description = __('Zaberi Tovar extension adds shipping method to Checkout page.', 'wd-zaberi-tovar');

        parent::init(); // TODO: Change the autogenerated stub
    }

    public static function app() {
        return wdzt_get_current_plugin();
    }

    public function calculate_shipping( $package = [] ) {
        $this->add_rate( [
            'label'   => $this->title,
            'cost'    => isset( $data['formData']['wdzt-price'] ) ? (float) $data['formData']['wdzt-price'] : 0,
            'taxes'   => false,
            'package' => $package,
        ] );
    }

    /**
     * @inheritDoc
     */
    public function sanitize_settings( $settings ) {
        foreach ( $settings as $key => $value ) {
            switch ( $key ) {
                case 'api_id':
                case 'api_login':
                case 'widget_api':
                    $settings[$key] = $value;
                    break;
            }
        }

        return parent::sanitize_settings( $settings );
    }

    public function settings_form_fields() {
        return array_merge( [
            'api_login' => [
                'type'        => 'text',
                'title'       => __('Zaberi Tovar Login', 'wd-zaberi-tovar'),
                'default'     => '',
                'description' => '',
            ],
            'api_id'    => [
                'type'        => 'text',
                'title'       => __('API ID', 'wd-zaberi-tovar'),
                'default'     => '',
                'description' => __('Issued when requesting support', 'wd-zaberi-tovar'),
            ],
            'widget_api' => [
                'type' => 'text',
                'title' => __('Widget API Key', 'wd-zaberi-tovar'),
                'default' => '',
                'description' => '',
            ]
        ], parent::settings_form_fields() );
    }
    
    	/**
	 * @param $order \WC_Order
	 */
	public static function order_data_output( $order ) {
		$order_meta      = $order->get_meta( Order::DELIVERY_CHECKOUT_META_KEY );
		$shipping_method = explode( ':', $order_meta['shipping_method'][0] );
		$shipping_method = $shipping_method[0];

		if ( $shipping_method == self::SHIPPING_DELIVERY_ID ) {
            $pvz             = isset( $order_meta['wdzt_data'] ) ? json_decode( $order_meta['wdzt_data'], true ) : '';
            $price           = isset( $order_meta['wdzt_price'] ) ? $order_meta['wdzt_price'] : '';
            /*
            if ( isset( $order_meta['ship_to_different_address'] ) && $order_meta['ship_to_different_address'] ) {
                $customer_data = 'shipping';
            } else {
                $customer_data = 'billing';
            }

            $country = isset( $order_meta[$customer_data.'_country'] ) ? $order_meta[$customer_data.'_country'] : '';
            $city    = isset( $order_meta[$customer_data.'_city'] ) ? $order_meta[$customer_data.'_city'] : '';
            $state   = isset( $order_meta[$customer_data.'_state'] ) ? $order_meta[$customer_data.'_state'] : '';
            $addr    = isset( $order_meta[$customer_data.'_address_1'] ) ? $order_meta[$customer_data.'_address_1'] : '';
            $room    = isset( $order_meta[$customer_data.'_address_2'] ) ? $order_meta[$customer_data.'_address_2'] : '';
            */
			?>
            </div></div>
            <div style='display: inline-block; width: 100%;'>
            <h3>Выбранный способ доставки</h3>
			<?php

			if ( ! empty( $pvz ) ) {
				$delivery_method_str = __('ПВЗ', 'wd-zaberi-tovar');
				$address             = "{$pvz['cityname']}<br>{$pvz['address']}";
				?>
                <table>
                <tr class="woocommerce-cdek-delivery">
                    <td colspan="2">
                        <div id='cdek-delivery-selected' class=''>
                            <div id='cdek-delivery-selected-content'>
                                <div class='delivery-selected'>
                                    <div class='delivery-flex-row'>
                                        <div class='delivery-flex-col'>
                                            <div class='delivery-selected-content'>
                                                <div class='delivery-selected-title'>Служба доставки</div>
                                                <div>
                                                    <div class='delivery-selected-image service-image service-image-cdek'></div>
                                                    <div class='delivery-selected-name'> <?= __('Zaberi Tovar', 'wd-zaberi-tovar'); ?> </div>
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
                </table>
                <?php
			}
		}
	}

}
