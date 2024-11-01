<?php

use WBCR\Delivery\Advanced\Condition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/* @var array $conditions */
$condition_groups = isset( $conditions['conditions'] ) ? $conditions['conditions'] : [];

wp_nonce_field( 'wdad_conditions_meta_box', 'wdad_conditions_meta_box_nonce' );

?>
<div class='wdad-conditions wdad-conditions-meta-box'>
    <p>
        <strong><?php _e( 'Match all of the following rules to allow this shipping method:', 'wd-advanced-delivery' ); ?></strong>
    </p>
	<?php

	if ( ! empty( $condition_groups ) ) :
		$i = 0;
		foreach ( $condition_groups as $condition_group => $conditions ) {
			if ( $i > 0 ) {
				?>
                <p class='or-text'><strong><?php _e( 'OR', 'woocommerce-advanced-shipping' ); ?></strong></p>
				<?php
			}
			?>
            <div class='wdad-condition-group wdad-condition-group-<?php echo absint( $condition_group ); ?>'
                 data-group='<?php echo absint( $condition_group ); ?>'>

            <?php
					foreach ( $conditions as $condition_id => $condition ) :
						$wp_condition = new Condition( $condition_id, $condition_group, $condition['condition'], $condition['operator'], $condition['value'] );
						$wp_condition->output_condition_row();
					endforeach;
					?>

            </div>
			<?php
			$i ++;
		}

	else :
		?>

        <div class='wdad-condition-group wdad-condition-group-0' data-group='0'><?php
				$wp_condition = new Condition();
				$wp_condition->output_condition_row();
				?>
            </div>

	<?php

	endif;

	?>
</div>
<div class="">
    <a class='button button-default wdad-condition-or-add'
       href='javascript:void(0);'><?php _e( 'OR', 'wd-advanced-delivery' ); ?></a>
</div>

