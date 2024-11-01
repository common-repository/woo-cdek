<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Страница лицензирования плагина.
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 *
 * @copyright (c) 2020 CreativeMotion
 */
class WDCD_LicensePage extends Wbcr_FactoryPages436_AdminPage {

	/**
	 * {@inheritdoc}
	 */
	public $id = "license";

	/**
	 * {@inheritdoc}
	 */
	public $type = "page";

	/**
	 * {@inheritdoc}
	 */
	public $page_menu_dashicon = 'dashicons-admin-network';

	/**
	 * {@inheritdoc}
	 */
	public $menu_target = 'options-general.php';

	/**
	 * {@inheritdoc}
	 */
	public $internal = false;

	/**
	 * {@inheritdoc}
	 */
	public $plugin_name;

	/**
	 * @var string Name of the paid plan.
	 */
	public $plan_name;

	// PREMIUM SECTION
	// ------------------------------------------------------------------
	/**
	 * @since 2.0.7
	 * @var bool
	 */
	protected $is_premium;

	/**
	 * @since 2.0.7
	 * @var \WBCR\Factory_437\Premium\Provider
	 */
	protected $premium;

	/**
	 * @since 2.0.7
	 * @var bool
	 */
	protected $is_premium_active;

	/**
	 * @since 2.0.7
	 * @var bool
	 */
	protected $premium_has_subscription;

	/**
	 * @since 2.0.7
	 * @var \WBCR\Factory_437\Premium\Interfaces\License
	 */
	protected $premium_license;

	// END PREMIUM SECTION
	// ------------------------------------------------------------------

	/**
	 * {@inheritdoc}
	 * @param Wbcr_Factory437_Plugin $plugin
	 */
	public function __construct( Wbcr_Factory437_Plugin $plugin ) {
		$this->plugin = $plugin;

		parent::__construct( $plugin );

		$this->menu_title    = __( 'Cdek Delivery', 'wd-cdek-delivery' );
		$this->page_title    = __( 'License of Cdek Delivery Plugin', 'wd-cdek-delivery' );
		$this->template_name = "license";
		$this->capabilitiy   = "manage_options";

		$this->plugin_name              = $this->plugin->getPluginName();
		$this->premium                  = $plugin->premium;
		$this->is_premium               = $this->premium->is_activate();
		$this->is_premium_active        = $this->premium->is_active();
		$this->premium_has_subscription = $this->premium->has_paid_subscription();
		$this->premium_license          = $this->premium->get_license();
	}

	/**
	 * [MAGIC] Magic method that configures assets for a page.
	 */
	public function assets( $scripts, $styles ) {
		parent::assets( $scripts, $styles );

		$this->styles->request( array(
			'bootstrap.core',
			'bootstrap.form-groups',
			'bootstrap.separator',
		), 'bootstrap' );

		$this->styles->add( WDCD_PLUGIN_URL . '/admin/assets/css/license-manager.css' );
		$this->scripts->add( WDCD_PLUGIN_URL . '/admin/assets/js/license-manager.js' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function indexAction() {
		?>
        <div id="wdcd-license-wrapper" class="wbcr-factory-content"
             data-loader="<?php echo WDCD_PLUGIN_URL . '/assets/img/loader.gif'; ?>"
             data-plugin-name="<?php echo esc_attr( $this->plugin_name ); ?>"
             data-nonce="<?php echo wp_create_nonce( "wdcd_license_activate" ) ?>">
			<?php $this->show_license_form(); ?>
        </div>
		<?php
	}

	/**
	 * Get before content.
	 *
	 * @return string Before content.
	 */
	protected function get_plan_description() {
		return '';
	}

	/**
	 * @return string
	 */
	protected function get_hidden_license_key() {
		if ( ! $this->is_premium ) {
			return '';
		}

		return $this->premium_license->get_hidden_key();
	}

	/**
	 * @return string
	 */
	protected function get_plan() {
		if ( ! $this->is_premium ) {
			return 'free';
		}

		return $this->premium->get_plan();
	}

	/**
	 * @return mixed
	 */
	protected function get_expiration_days() {
		if ( ! $this->is_premium ) {
			return '';
		}

		return $this->premium_license->get_expiration_time( 'days' );
	}

	/**
	 * @return string
	 */
	protected function get_billing_cycle_readable() {
		if ( ! $this->is_premium ) {
			return '';
		}

		$billing_cycle = $this->premium->get_billing_cycle();
		$billing       = 'lifetime';

		if ( 1 == $billing_cycle ) {
			$billing = 'month';
		} else if ( 12 == $billing_cycle ) {
			$billing = 'year';
		}

		return $billing;
	}

	/**
	 * Тип лицензии, цветовое оформление для формы лицензирования
	 * free - бесплатная
	 * gift - пожизненная лицензия, лицензия на особых условиях
	 * trial - красный цвет, применяется для триалов, если лиценизия истекла или заблокирована
	 * paid - обычная оплаченная лицензия, в данный момент активна.
	 *
	 * @return string
	 */
	protected function get_license_type() {
		if ( ! $this->is_premium ) {
			return 'free';
		}

		$license = $this->premium_license;

		if ( $license->is_lifetime() ) {
			return 'gift';
		} else if ( $license->get_expiration_time( 'days' ) < 1 ) {
			return 'trial';
		}

		return 'paid';
	}

	protected function render_learnmore_section() {
		if ( $this->is_premium ):
			?>
            <p style="margin-top: 10px;">
				<?php printf( __( '<a href="%s" target="_blank" rel="noopener">Lean more</a> about the premium version and get the license key to activate it now!', 'wd-cdek-delivery' ), $this->plugin->get_support()->get_pricing_url( true, 'license_page' ) ); ?>
            </p>
		<?php else: ?>
            <p style="margin-top: 10px;">
				<?php printf( __( 'Can’t find your key? Go to <a href="%s" target="_blank" rel="noopener">this page</a> and login using the e-mail address associated with your purchase.', 'wd-cdek-delivery' ), "https://users.freemius.com" ) ?>
            </p>
		<?php endif;
	}

	/**
	 * @param bool|WP_Error $notice
	 */
	public function show_license_form( $notice = false ) {
		?>
        <div id="license-manager"
             class="factory-bootstrap-437 onp-page-wrap <?= $this->get_license_type() ?>-license-manager-content">
            <div>
                <h3><?php printf( __( 'Activate %s', 'wd-cdek-delivery' ), $this->plugin->getPluginTitle() ) ?></h3>
				<?php echo $this->get_plan_description() ?>
            </div>
            <br>
			<?php if ( is_wp_error( $notice ) ) : ?>
                <div class="license-message <?= $this->get_license_type() ?>-license-message">
                    <div class="alert <?php echo esc_attr( $notice->get_error_code() ); ?>">
                        <h4 class="alert-heading"><?php _e( $notice->get_error_message(), 'wd-cdek-delivery' ) ?></h4>
                    </div>
                </div>
			<?php endif; ?>
            <div class="onp-container">
                <div class="license-details">
					<?php if ( $this->get_license_type() == 'free' ): ?>
                        <a href="<?php echo $this->plugin->get_support()->get_pricing_url( true, 'license_page' ); ?>"
                           class="purchase-premium" target="_blank" rel="noopener">
                            <span class="btn btn-gold btn-inner-wrap">
                            <?php _e( 'Buy Premium', 'wd-cdek-delivery' ) ?>
                            </span>
                        </a>
                        <p>&nbsp;</p>
					<?php endif; ?>
					<?php if ( $this->is_premium ): ?>
                        <div class="license-details-block <?= $this->get_license_type() ?>-details-block">
							<?php if ( $this->is_premium ): ?>
                                <a data-action="deactivate" href="#"
                                   class="btn btn-default btn-small license-delete-button wdcd-control-btn">
									<?php _e( 'Delete Key', 'wd-cdek-delivery' ) ?>
                                </a>
                                <a data-action="sync" href="#"
                                   class="btn btn-default btn-small license-synchronization-button wdcd-control-btn">
									<?php _e( 'Synchronization', 'wd-cdek-delivery' ) ?>
                                </a>
							<?php endif; ?>
                            <h3>
								<?php echo ucfirst( $this->get_plan() ); ?>

								<?php if ( $this->is_premium && $this->premium_has_subscription ): ?>
                                    <span style="font-size: 15px;">
                                    (<?php printf( __( 'Automatic renewal, every %s', '' ), esc_attr( $this->get_billing_cycle_readable() ) ); ?>)
                                </span>
								<?php endif; ?>
                            </h3>
							<?php if ( $this->is_premium ): ?>
                                <div class="license-key-identity">
                                    <code><?= esc_attr( $this->get_hidden_license_key() ) ?></code>
                                </div>
							<?php endif; ?>
                            <div class="license-key-description">
								<?php if ( $this->is_premium ): ?>
                                    <p><?php _e( 'Сommercial license, only to the premium add-on to this free plugin. You cannot distribute or modify the premium add-on. But free plugin is a GPLv3 compatible license allowing you to change and use this version of the plugin for free.', 'wd-cdek-delivery' ) ?></p>
								<?php endif; ?>
								<?php if ( $this->is_premium && $this->premium_has_subscription ): ?>
                                    <p class="activate-trial-hint">
										<?php _e( 'You use a paid subscription for the plugin updates. In case you don’t want to receive paid updates, please, click <a data-action="unsubscribe" class="wdcd-control-btn" href="#">cancel subscription</a>', 'wd-cdek-delivery' ) ?>
                                    </p>
								<?php endif; ?>

	                            <?php if ( $this->get_license_type() == 'trial' ): ?>
                                    <p class="activate-error-hint">
			                            <?php printf( __( 'Your license has expired, please extend the license to get updates and support.', 'wd-cdek-delivery' ), '' ) ?>
                                    </p>
	                            <?php endif; ?>
                            </div>
                            <table class="license-params" colspacing="0" colpadding="0">
                                <tr>
                                    <td class="license-param license-param-days">
                                        <span class="license-value"><?= $this->get_plan() ?></span>
                                        <span class="license-value-name"><?php _e( 'plan', 'wd-cdek-delivery' ) ?></span>
                                    </td>
			                        <?php if ( $this->is_premium ) : ?>
                                        <td class="license-param license-param-sites">
                                        <span class="license-value">
                                            <?php echo esc_attr( $this->premium_license->get_count_active_sites() ); ?>
                                            <?php _e( 'of', 'wd-cdek-delivery' ) ?>
                                            <?php echo esc_attr( $this->premium_license->get_sites_quota() ); ?></span>
                                            <span class="license-value-name"><?php _e( 'active sites', 'wd-cdek-delivery' ) ?></span>
                                        </td>
			                        <?php endif; ?>
                                    <td class="license-param license-param-version">
                                        <span class="license-value"><?= $this->plugin->getPluginVersion() ?></span>
                                        <span class="license-value-name"><span>version</span></span>
                                    </td>
									<?php if ( $this->is_premium ): ?>
                                        <td class="license-param license-param-days">
											<?php if ( $this->get_license_type() == 'trial' ): ?>
                                                <span class="license-value"><?php _e( 'EXPIRED!', 'wd-cdek-delivery' ) ?></span>
                                                <span class="license-value-name"><?php _e( 'please update the key', 'wd-cdek-delivery' ) ?></span>
											<?php else: ?>
                                                <span class="license-value">
													<?php
													if ( $this->premium_license->is_lifetime() ) {
														echo 'infiniate';
													} else {
														echo $this->get_expiration_days();
													}
													?>
                                                <small> <?php _e( 'day(s)', 'wd-cdek-delivery' ) ?></small>
                                             </span>
                                                <span class="license-value-name"><?php _e( 'remained', 'wd-cdek-delivery' ) ?></span>
											<?php endif; ?>
                                        </td>
									<?php endif; ?>
                                </tr>
                            </table>
                        </div>
					<?php endif; ?>
                </div>
                <div class="license-input">
                    <form action="" method="post">
			            <?php if ( $this->is_premium ): ?>
                    <p><?php _e( 'Have a key to activate the premium version? Paste it here:', 'wd-cdek-delivery' ) ?><p>
		            <?php else: ?>
                        <p><?php _e( 'Have a key to activate the plugin? Paste it here:', 'wd-cdek-delivery' ) ?>
                        <p>
				            <?php endif; ?>
                            <button data-action="activate" class="button button-primary wdcd-control-btn" type="button"
                                    id="license-submit">
			                    <?php _e( 'Submit Key', 'wd-cdek-delivery' ) ?>
                            </button>
                        <div class="license-key-wrap">
                            <input type="text" id="license-key" name="licensekey" value="" class="form-control"/>
                        </div>
			            <?php $this->render_learnmore_section(); ?>
                    </form>
                </div>
            </div>
        </div>
		<?php
	}
}
