<?php
/** @var $this \WBCR\Delivery\Advanced\Advanced */
/** @var $methods array */

foreach ( $methods as $key => $condition ) {
	?>
    <script type="text/template" id="tmpl-wdad-modal-edit-shipping-method-<?= $key; ?>">
        <div class="wc-backbone-modal">
            <div class="wc-backbone-modal-content">
                <section class="wc-backbone-modal-main" role="main">
                    <header class="wc-backbone-modal-header">
                        <h1><?php esc_html_e( 'Add shipping method', 'wd-advanced-delivery' ); ?></h1>
                        <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                            <span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'wd-advanced-delivery' ); ?></span>
                        </button>
                    </header>
                    <article>
                        <form action="" method="post">
                            <h2><?php _e( 'Conditions', 'wd-advanced-delivery' ); ?></h2>
						    <?php $this->render_wdad_conditions( $condition ); ?>
                            <p></p>
                            <h2><?php _e( 'Settings', 'wd-advanced-delivery' ); ?></h2>
						    <?php $this->render_wdad_settings( $condition ); ?>
                        </form>
                    </article>
                    <footer>
                        <div class="inner">
                            <button id="btn-ok"
                                    class="button button-primary button-large"><?php esc_html_e( 'Save shipping rate', 'wd-advanced-delivery' ); ?></button>
                        </div>
                    </footer>
                </section>
            </div>
        </div>
        <div class="wc-backbone-modal-backdrop modal-close"></div>
    </script>
	<?php
}
?>
<script type="text/template" id="tmpl-wdad-modal-add-shipping-method">
    <div class="wc-backbone-modal">
        <div class="wc-backbone-modal-content">
            <section class="wc-backbone-modal-main" role="main">
                <header class="wc-backbone-modal-header">
                    <h1><?php esc_html_e( 'Add shipping method', 'wd-advanced-delivery' ); ?></h1>
                    <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                        <span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'wd-advanced-delivery' ); ?></span>
                    </button>
                </header>
                <article>
                    <form action="" method="post">
                        <h2><?php _e( 'Conditions', 'wd-advanced-delivery' ); ?></h2>
						<?php $this->render_wdad_conditions(); ?>
                        <p></p>
                        <h2><?php _e( 'Settings', 'wd-advanced-delivery' ); ?></h2>
						<?php $this->render_wdad_settings(); ?>
                    </form>
                </article>
                <footer>
                    <div class="inner">
                        <button id="btn-ok"
                                class="button button-primary button-large"><?php esc_html_e( 'Add shipping rate', 'wd-advanced-delivery' ); ?></button>
                    </div>
                </footer>
            </section>
        </div>
    </div>
    <div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/html" id="tmpl-wdad-advanced-shipping-method-row-blank">
    <tr>
        <td class="wc-shipping-zone-method-blank-state" colspan="4">
            <p><?php esc_html_e( 'There are no Advanced Shipping rates. Yet...', 'wd-advanced-delivery' ); ?></p>
        </td>
    </tr>
</script>

<script type="text/html" id="tmpl-wdad-advanced-shipping-method-row">
    <tr class='' data-id="{{ data.ID }}">
        <td class='sort'></td>
        <td class="column-primary">
            <strong>
                <a href='#' class='row-title wdad-advanced-shipping-edit-method'
                   title='<?php _e( 'Edit Method', 'wd-advanced-delivery' ); ?>'>
                    <# if ( data.shipping_title ) { #>
                    {{ data.shipping_title }}
                    <# } else {
					<?php _e( 'Shipping', 'wd-advanced-delivery' ); ?>
                    } #>
                </a>
            </strong>
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
            {{ data.shipping_cost }}₽
        </td>
        <td class="column-fee">
            {{ data.handling_fee }}₽
        </td>
        <td class="column-per-item">
            {{ data.cost_per_item }}
        </td>
        <td class="column-per-weight">
            {{ data.cost_per_weight }}
        </td>
        <td class="column-tax">
            {{ data.tax }}
        </td>
        <# if ( <?php echo $this->is_dev_mode() ? 1 : 0; ?> ) { #>
        <td class="column-conditions"></td>
        <# } #>

    </tr>
</script>
