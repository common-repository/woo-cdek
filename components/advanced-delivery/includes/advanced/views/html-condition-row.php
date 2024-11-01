<?php
/** @var $wp_condition \WBCR\Delivery\Advanced\Condition */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>
<div class='wdad-condition-wrap'>

    <!-- Condition -->
    <span class='wdad-condition-wrap wdad-condition-wrap-<?php echo absint( $wp_condition->id ); ?>'><?php

		$condition_field_args = array(
			'type'        => 'select',
			'name'        => 'conditions[' . absint( $wp_condition->group ) . '][' . absint( $wp_condition->id ) . '][condition]',
			'class'       => array( 'wdad-condition' ),
			'options'     => $wp_condition->get_conditions(),
			'value'       => $wp_condition->condition,
			'custom_attr' => array(
				'data-group' => absint( $wp_condition->group ),
				'data-id'    => absint( $wp_condition->id ),
			),
		);
	    wdad_html_field( $condition_field_args );

	    ?></span>

    <!-- Description -->
	<?php
	if ( $desc = $wp_condition->get_description() ) :
		?><span class='wdad-description <?php echo $wp_condition->condition; ?>-description help_tip'
                data-tip="<?php echo esc_html( $desc ); ?>">
        </span><?php
	else :
		?><span
        class='wdad-description wdad-no-description <?php echo $wp_condition->condition; ?>-description'></span><?php
	endif;
	?>

    <!-- Operator -->
    <span class='wdad-operator-wrap wdad-operator-wrap-<?php echo absint( $wp_condition->id ); ?>'><?php

		$operator_field_args = array(
			'type'    => 'select',
			'name'    => 'conditions[' . absint( $wp_condition->group ) . '][' . absint( $wp_condition->id ) . '][operator]',
			'class'   => array( 'wdad-operator' ),
			'options' => $wp_condition->get_operators(),
			'value'   => $wp_condition->operator,
		);
		wdad_html_field( $operator_field_args );

		?></span>


    <!-- Value -->
    <span class='wdad-value-wrap wdad-value-wrap-<?php echo absint( $wp_condition->id ); ?>'><?php
		$value_field_args = wp_parse_args( array( 'value' => $wp_condition->value ), $wp_condition->get_value_field_args() );
		wdad_html_field( $value_field_args );
		?></span>

    <!-- Add/Delete-->
    <div class="button-group">
        <a class='button button-sm button-default wdad-condition-delete' href='javascript:void(0);'>-</a>
        <a class='button button-default wdad-condition-and-add' data-group='<?php echo absint( $this->group ); ?>'
           data-id='<?php echo absint( $this->id ); ?>'
           href='javascript:void(0);'><?php _e( 'AND', 'wd-advanced-delivery' ); ?></a>&nbsp;
    </div>
</div>
