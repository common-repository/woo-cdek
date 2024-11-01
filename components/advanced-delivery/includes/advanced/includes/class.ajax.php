<?php

namespace WBCR\Delivery\Advanced;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * AJAX class.
 *
 * Handles all AJAX related calls.
 *
 * @version        1.0.0
 */
class Ajax {


	/**
	 * Constructor.
	 *
	 * Add ajax actions in order to work.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add elements
		add_action( 'wp_ajax_wdad_add_condition', array( $this, 'add_condition' ) );
		add_action( 'wp_ajax_wdad_add_condition_group', array( $this, 'add_condition_group' ) );

		// Update elements
		add_action( 'wp_ajax_wdad_update_condition_value', array( $this, 'update_condition_value' ) );
		add_action( 'wp_ajax_wdad_update_condition_description', array( $this, 'update_condition_description' ) );

		// Save post ordering
		add_action( 'wp_ajax_wdad_save_post_order', array( $this, 'save_post_order' ) );

		add_action( 'wp_ajax_wdad_shipping_methods_save_changes', [ $this, 'save_changes' ] );
	}


	/**
	 * Add condition.
	 *
	 * Output the HTML of a new condition row.
	 *
	 * @since 1.0.0
	 */
	public function add_condition() {
		check_ajax_referer( 'wdad-ajax-nonce', 'nonce' );

		$group = absint( $_POST['group'] );
		$id    = absint( $_POST['id'] );

		$wp_condition = new Condition( ++ $id, $group );
		$wp_condition->output_condition_row();

		die();
	}


	/**
	 * Condition group.
	 *
	 * Output the HTML of a new condition group.
	 *
	 * @since 1.0.0
	 */
	public function add_condition_group() {
		check_ajax_referer( 'wdad-ajax-nonce', 'nonce' );
		$group = absint( $_POST['group'] );

		?>
            <p class='or-text'><strong><?php _e( 'OR', 'woocommerce-advanced-shipping' ); ?></strong></p>
            <div class='wdad-condition-group wdad-condition-group-<?php echo $group; ?>'
                 data-group='<?php echo $group; ?>'>
				<?php
				$wp_condition = new Condition( null, $group );
				$wp_condition->output_condition_row();
				?>
            </div>
		<?php
		die();

	}


	/**
	 * Update condition value field.
	 *
	 * Output the HTML of the value field according to the condition key..
	 *
	 * @since 1.0.0
	 */
	public function update_condition_value() {

		check_ajax_referer( 'wdad-ajax-nonce', 'nonce' );

		$wp_condition     = new Condition( $_POST['id'], $_POST['group'], $_POST['condition'] );
		$value_field_args = $wp_condition->get_value_field_args();

		?><span class='wdad-value-wrap wdad-value-wrap-<?php echo absint( $wp_condition->id ); ?>'><?php
		wdad_html_field( $value_field_args );
		?></span><?php

		die();

	}


	/**
	 * Update description.
	 *
	 * Render the corresponding description for the condition key.
	 *
	 * @since 1.0.0
	 */
	public function update_condition_description() {

		check_ajax_referer( 'wdad-ajax-nonce', 'nonce' );

		$condition    = sanitize_text_field( $_POST['condition'] );
		$wp_condition = new Condition( null, null, $condition );

		if ( $desc = $wp_condition->get_description() ) {
			?><span class='wdad-description <?php echo $wp_condition->condition; ?>-description'>
            <img class='help_tip' src='<?php echo WC()->plugin_url(); ?>/assets/images/help.png' height='24' width='24'
                 data-tip="<?php echo esc_html( $wp_condition->get_description() ); ?>"/>
            </span><?php

			die();
		}


	}


	/**
	 * Save order.
	 *
	 * Save the order of the posts in the overview table.
	 *
	 * @since 1.0.4
	 */
	public function save_post_order() {

		global $wpdb;

		check_ajax_referer( 'wdad-ajax-nonce', 'nonce' );

		$args = wp_parse_args( $_POST['form'] );

		$menu_order = 0;
		foreach ( $args['sort'] as $sort ) :

			//$wpdb->update( $wpdb->posts, array( 'menu_order' => $menu_order ), array( 'ID' => $sort ), array( '%d' ), array( '%d' ) );

			$menu_order ++;

		endforeach;

		die;

	}

	/**
	 * Save changes.
	 *
	 * Save changes.
	 *
	 * @since 1.0.0
	 */
	public function save_changes() {
		if ( ! isset( $_POST['wdad_shipping_method_nonce'], $_POST['condition'], $_POST['methods'] ) ) {
			wp_send_json_error( 'missing_fields' );
			wp_die();
		}

		if ( ! wp_verify_nonce( wp_unslash( $_POST['wdad_shipping_method_nonce'] ), 'wdad_shipping_method_nonce' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_send_json_error( 'bad_nonce' );
			wp_die();
		}

		// Check User Caps.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'missing_capabilities' );
			wp_die();
		}

		$conditions = json_decode( wp_unslash( $_POST['condition'] ), true );// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$methods    = json_decode( wp_unslash( $_POST['methods'] ), true );// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$result = [];
		foreach ( $conditions as $key => $value ) {
			if ( strpos( $key, '[' ) ) {
				preg_match_all( '/conditions\[(\d*)\].*\[(\d*)\].*\[(.*)\]/', $key, $arr );
				if ( is_array( $arr ) && ! empty( $arr ) ) {
					$result['conditions'][ $arr[1][0] ][ $arr[2][0] ][ $arr[3][0] ] = $value;
				}
			} else {
				$result[ $key ] = $value;
			}
		}

		if ( isset( $_POST['id'] ) ) { //if isset id - then EDIT
			$id             = absint( $_POST['id'] );
			$methods[ $id ] = $result;
		} else {
			$methods[] = $result;
		}

		wp_send_json_success( array(
			'methods' => $methods,
		) );

	}

}
