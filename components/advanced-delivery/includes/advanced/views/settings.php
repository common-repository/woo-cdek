<?php

use WBCR\Delivery\Advanced\Condition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/* @var array $settings */

wp_nonce_field( 'wdad_settings_meta_box', 'wdad_settings_meta_box_nonce' );

?>
<div class='wdad-settings'>
    <div class="wdad-settings-group">

    <p class='wdad-option'>
                <label for='shipping_title'><?php _e( 'Shipping title', 'woocommerce-advanced-shipping' ); ?></label>
                <input
                        type='text'
                        class=''
                        id='shipping_title'
                        name='shipping_title'
                        style='width: 190px;'
                        value='<?php echo esc_attr( @$settings['shipping_title'] ); ?>'
                        placeholder='<?php _e( 'e.g. Shipping', 'woocommerce-advanced-shipping' ); ?>'
                >
            </p>


            <p class='wdad-option'>
                <label for='cost'><?php _e( 'Shipping cost', 'woocommerce-advanced-shipping' ); ?></label>
                <input
                        type='text'
                        step='any'
                        class='wc_input_price'
                        id='cost'
                        name='shipping_cost'
                        value='<?php echo esc_attr( wc_format_localized_price( @$settings['shipping_cost'] ) ); ?>'
                        placeholder='<?php _e( 'Shipping cost', 'woocommerce-advanced-shipping' ); ?>'>
                <span class='wdad-currency'><?php echo get_woocommerce_currency_symbol(); ?></span>
            </p>


            <p class='wdad-option'>
                <label for='handling_fee'><?php _e( 'Handling fee', 'woocommerce-advanced-shipping' ); ?></label>
                <input
                        type='text'
                        class='wc_input_price'
                        id='handling_fee'
                        name='handling_fee'
                        value='<?php echo esc_attr( wc_format_localized_price( @$settings['handling_fee'] ) ); ?>'
                        placeholder='<?php _e( 'Fixed or percentage', 'woocommerce-advanced-shipping' ); ?>'
                >
                <span class='wdad-currency'><?php echo get_woocommerce_currency_symbol(); ?>/%</span>
                <span class='wdad-description help_tip' data-tip="<?php _e( 'A fixed amount (e.g. 5) or percentage (e.g. 5%) which will always be charged.', 'woocommerce-advanced-shipping' ); ?>">
            </p>


            <p class='wdad-option'>
                <label for='cost-per-item'><?php _e( 'Cost per item', 'woocommerce-advanced-shipping' ); ?></label>
                <input
                        type='text'
                        class='wc_input_price'
                        id='cost-per-item'
                        name='cost_per_item'
                        value='<?php echo esc_attr( wc_format_localized_price( @$settings['cost_per_item'] ) ); ?>'
                        placeholder='<?php _e( 'Fixed or percentage', 'woocommerce-advanced-shipping' ); ?>'
                >
                <span class='wdad-currency'><?php echo get_woocommerce_currency_symbol(); ?>/%</span>
                <span class='wdad-description help_tip' data-tip="<?php _e( 'Add a fee for each item that is in the cart. <br/>Quantity is also calculated', 'woocommerce-advanced-shipping' ); ?>">
            </p>


            <p class='wdad-option'>
                <label for='cost-per-weight'><?php _e( 'Cost per weight', 'woocommerce-advanced-shipping' ); ?>
                    (<?php echo get_option( 'woocommerce_weight_unit' ); ?>)</label>
                <input
                        type='text'
                        class='wc_input_price'
                        id='cost-per-weight'
                        name='cost_per_weight'
                        value='<?php echo esc_attr( wc_format_localized_price( @$settings['cost_per_weight'] ) ); ?>'
                        placeholder='<?php _e( '0', 'woocommerce-advanced-shipping' ); ?>'
                >
                <span class='wdad-currency'><?php echo get_woocommerce_currency_symbol(); ?></span>
                <span class='wdad-description help_tip' data-tip="<?php echo sprintf( __( 'Add a fee multiplied by the amount of %s', 'woocommerce-advanced-shipping' ), get_option( 'woocommerce_weight_unit' ) ); ?>">
            </p>


            <p class='wdad-option'>
                <label for='tax'><?php _e( 'Tax status', 'woocommerce-advanced-shipping' ); ?></label>
                <select name='tax' style='width: 189px;'>
                    <option value='taxable' <?php @selected( $settings['tax'], 'taxable' ); ?>><?php _e( 'Taxable', 'woocommerce-advanced-shipping' ); ?></option>
                    <option value='not_taxable' <?php @selected( $settings['tax'], 'not_taxable' ); ?>><?php _e( 'Not taxable', 'woocommerce-advanced-shipping' ); ?></option>
                </select>
            </p><?php

			do_action( 'wdad_advanced_after_settings', $settings );

			?>

    </div>
</div>
