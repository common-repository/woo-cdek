<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/* @var WBCR\Delivery\Advanced\Advanced $this */
/* @var string $field_key */
/* @var array $methods */

$wc_status_options = wp_parse_args( get_option( 'woocommerce_status_options', array() ), array( 'shipping_debug_mode' => 0 ) );

$table_cols = $this->is_dev_mode() ? 8 : 7;
?>
<tr valign="top">
    <th scope="row" class="titledesc"><?php
		_e( 'Shipping rates', 'wd-advanced-delivery' ); ?>:<br/>
    </th>
    <td class="forminp" id="<?php echo esc_attr( $this->id ); ?>_shipping_methods">
        <input type='hidden'
               name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>"
               value='<?php echo json_encode( $methods ); ?>'/>

        <table class='wp-list-table wdad-advanced-shipping-methods widefat'>

            <thead>
            <tr>
                <th scope="col" style='width: 15px;' class="column-sort"></th>
                <th scope="col" style='padding-left: 0;' class="column-primary">
                    <div style='padding-left: 10px;'><?php _e( 'Title', 'wd-advanced-delivery' ); ?></div>
                </th>
                <th scope="col" style='padding-left: 10px;'
                    class="column-cost"><?php _e( 'Shipping cost', 'wd-advanced-delivery' ); ?></th>
                <th scope="col" style='padding-left: 10px;'
                    class="column-fee"><?php _e( 'Handling fee', 'wd-advanced-delivery' ); ?></th>
                <th scope="col" style='padding-left: 10px;'
                    class="column-per-item"><?php _e( 'Cost/item', 'wd-advanced-delivery' ); ?></th>
                <th scope="col" style='padding-left: 10px;'
                    class="column-per-weight"><?php _e( 'Cost/weight', 'wd-advanced-delivery' ); ?></th>
                <th scope="col" style='padding-left: 10px;'
                    class="column-tax"><?php _e( 'Taxable', 'wd-advanced-delivery' ); ?></th>
				<?php if ( $this->is_dev_mode() ) : ?>
                    <th scope="col" style='padding-left: 10px;width: 250px;'
                        class="column-conditions"><?php _e( 'Conditions', 'wd-advanced-delivery' ); ?></th>
				<?php endif; ?>
            </tr>
            </thead>
            <tbody class="wdad-advanced-shipping-methods-rows">
			<?php
			$i                   = 0;
			foreach ( $methods as $key => $method ) :
				$alt = ( $i ++ ) % 2 == 0 ? 'alternate' : '';
				$shipping_title  = isset( $method['shipping_title'] ) ? wp_kses_post( $method['shipping_title'] ) : '';
				$shipping_cost   = isset( $method['shipping_cost'] ) ? wp_kses_post( wc_price( $method['shipping_cost'] ) ) : '';
				$handling_fee    = isset( $method['handling_fee'] ) ? wp_kses_post( wc_price( $method['handling_fee'] ) ) : '';
				$cost_per_item   = isset( $method['cost_per_item'] ) ? wp_kses_post( $method['cost_per_item'] ) : '';
				$cost_per_weight = isset( $method['cost_per_weight'] ) ? wp_kses_post( $method['cost_per_weight'] ) : '';
				$tax             = isset( $method['tax'] ) ? wp_kses_post( $method['tax'] ) : '';
				$conditions      = isset( $method['conditions'] ) ? $method['conditions'] : [];
				?>
            <tr class='<?php echo $alt; ?>' data-id="<?php echo absint( $key ); ?>">

                <td class='sort'></td>
                <td class="column-primary">
                    <strong>
                        <a href='#' class='row-title wdad-advanced-shipping-edit-method'
                           title='<?php _e( 'Edit Method', 'wd-advanced-delivery' ); ?>'>
							<?php
							if ( empty( $shipping_title ) ) :
								_e( 'Shipping', 'wd-advanced-delivery' );
							else :
								echo wp_kses_post( $shipping_title );
							endif;
							?></a><?php
						if ( $wc_status_options['shipping_debug_mode'] || $this->is_dev_mode() ) {
							echo '<small> - #' . absint( $key ) . '</small>';
						}
						?></strong>
                    <div class='row-actions'>
								<span class='edit'>
									<a href='#' class="wdad-advanced-shipping-edit-method"
                                       title='<?php _e( 'Edit Method', 'wd-advanced-delivery' ); ?>'>
										<?php _e( 'Edit' ); ?>
									</a>
									|
								</span>
                        <span class='trash'>
									<a href='#' class="wdad-advanced-shipping-delete-method"
                                       title='<?php _e( 'Delete Method', 'wd-advanced-delivery' ); ?>'>
										<?php _e( 'Delete' ); ?>
									</a>
								</span>
                    </div>
                    <button type="button" class="toggle-row"><span
                                class="screen-reader-text"><?php _e( 'Show more details' ); ?></span></button>
                </td>
                <td class="column-cost"
                    data-colname="<?php _e( 'Shipping cost', 'wd-advanced-delivery' ); ?>">
					<?php echo $shipping_cost; ?>
                </td>
                <td class="column-fee">
					<?php echo $handling_fee; ?>
                </td>
                <td class="column-per-item">
					<?php echo $cost_per_item; ?>
                </td>
                <td class="column-per-weight">
					<?php echo $cost_per_weight; ?>
                </td>
                <td class="column-tax">
					<?php echo $tax; ?>
                </td>
				<?php if ( $this->is_dev_mode() ) : ?>
                <td class="column-conditions"
                    data-colname="<?php _e( 'Condition groups', 'wd-advanced-delivery' ); ?>">
					<?php
					$j = 0;
					foreach ( $conditions as $condition ) {
						if ( $j ++ !== 0 ) {
							echo "<br><b>&nbsp;&nbsp;OR</b><br>";
						}

						$i = 0;
						foreach ( $condition as $key => $item ) {
							if ( $i ++ !== 0 ) {
								echo "<br><b>AND</b><br>";
							}
							echo "{$item['condition']} {$item['operator']} {$item['value']}";
						}
					}
					?>
                </td>
			<?php endif; ?>

                </tr><?php

			endforeach;

			if ( empty( $methods ) ) :
				?>
                <tr>
                <td colspan='5'><?php _e( 'There are no Advanced Shipping rates. Yet...', 'wd-advanced-delivery' ); ?></td>
                </tr><?php
			endif;

			?></tbody>
            <tfoot>
            <tr>
                <th colspan='8' style='padding-left: 10px;'>
                    <button class='add button wdad-advanced-shipping-add-method'>
						<?php _e( 'Add Rate', 'wd-advanced-delivery' ); ?>
                    </button>
                </th>
            </tr>
            </tfoot>
        </table>
    </td>
</tr>

