<?php

namespace WPickpoint\Pack;

use WPickpoint\Delivery\ShippingMethod;
use WPickpoint\Delivery\ShippingMethod\Pickpoint;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disable comments
 *
 * @author        Artem Prihodko <webtemyk@yandex.ru>
 *
 * @copyright (c) 2020 Creative Motion
 */
class Plugin {

	const LOGGER_CONTEXT = 'pickpoint-delivery';

	/**
	 * @see self::app()
	 * @var \WDPC\Plugin
	 */
	private static $plugin;

	/**
	 * @see self::app()
	 * @var \WPickpoint\Pack\Plugin
	 */
	private static $app;

	/**
	 * @var string
	 */
	private static $domain;

	/**
	 * Конструктор
	 *
	 * Применяет конструктор родительского класса и записывает экземпляр текущего класса в свойство $app.
	 * Подробнее о свойстве $app см. self::app()
	 *
	 * @param string $plugin_path
	 *
	 * @throws \Exception
	 */
	public function __construct( $plugin_path ) {
		if ( ! class_exists( '\WDPC\Plugin' ) ) {
			throw new \Exception( 'Plugin Delivery Pack is not installed!' );
		}

		self::$plugin = \WDPC\Plugin::app();
		self::$app    = $this;

		$this->global_scripts();

		$domain = get_plugin_data( $plugin_path );
		if ( ! isset( $domain['TextDomain'] ) ) {
			$domain = 'pickpoint-delivery';
		} else {
			$domain = $domain['TextDomain'];
		}

		self::$domain = $domain;

		if ( is_admin() ) {
			//$this->init_activation();
			require( WPICKPOINT_PLUGIN_DIR . '/admin/boot.php' );
			require( WPICKPOINT_PLUGIN_DIR . '/admin/ajax/check-license.php' );

			add_filter( 'plugin_action_links', [ $this, 'add_plugin_action_link' ], 99999, 3 );
			add_filter( 'plugin_row_meta', [ $this, 'add_plugin_row_link' ], 10, 2 );
			add_action( 'woocommerce_order_edit_status', '\WPickpoint\Delivery\ShippingMethod\Pickpoint::order_status_updated', 10, 2 );
			add_action( 'woocommerce_delete_order', '\WPickpoint\Delivery\ShippingMethod\Pickpoint::order_deleted', 10, 1 );
			add_action( 'woocommerce_trash_order', '\WPickpoint\Delivery\ShippingMethod\Pickpoint::order_deleted', 10, 1 );
			add_action( 'wp_trash_post', '\WPickpoint\Delivery\ShippingMethod\Pickpoint::order_deleted', 10, 1 );
		}

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Статический метод для быстрого доступа к интерфейсу плагина.
	 *
	 * @return \WDPC\Plugin | \WPickpoint\Pack\Plugin
	 */
	public static function app( $plugin = true ) {
		if ( $plugin ) {
			return self::$plugin;
		} else {
			return self::$app;
		}
	}

	/**
	 * Метод проверяет активацию премиум плагина и наличие действующего лицензионнного ключа
	 *
	 * @return bool
	 */
	public function is_premium() {
		if ( $this->app()->premium->is_active() && $this->app()->premium->is_activate() ) {
			return true;
		} else {
			return false;
		}
	}

	public function is_debug() {
		$debug = Pickpoint::get_setting( 'debug' );
		if ( ! empty( $debug ) && 'yes' === $debug ) {
			return true;
		}

		return false;
	}

	public function log( $level, $message ) {
		wc_get_logger()->log( $level, $message, array( 'source' => Plugin::LOGGER_CONTEXT ) );
	}

	/**
	 * Выполняет php сценарии, когда все Wordpress плагины будут загружены
	 *
	 * Регистрируем страницы в хуке plugins_loaded для того, чтобы из классов страниц
	 * мы могли выполнять проверку уставнолен/активирован/загружен ли тот или иной аддон.
	 *
	 *
	 * @throws \Exception
	 * @since  1.0.0
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function plugins_loaded() {
		if ( is_admin() ) {
			$this->register_pages();
		}

		if ( $this->is_premium() && is_plugin_active( 'woocommerce/woocommerce.php' ) && class_exists( 'WC_Shipping_Method' ) ) {
			require_once( WPICKPOINT_PLUGIN_DIR . '/includes/pickpoint/boot.php' );
			$this->registerDeliveryMethods();
		}
	}

	/**
	 * Регистрирует классы страниц в плагине
	 *
	 * Мы указываем плагину, где найти файлы страниц и какое имя у их класса. Чтобы плагин
	 * выполнил подключение классов страниц. После регистрации, страницы будут доступные по url
	 * и в меню боковой панели администратора. Регистрируемые страницы будут связаны с текущим плагином
	 * все операции выполняемые внутри классов страниц, имеют отношение только текущему плагину.
	 *
	 * @throws \Exception
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	private function register_pages() {
	}


	private function registerDeliveryMethods() {
		require_once( WPICKPOINT_PLUGIN_DIR . '/includes/trait-deliver-method.php' );
		require_once( WPICKPOINT_PLUGIN_DIR . '/includes/functions.php' );

		add_action( 'admin_notices', [ $this, 'admin_notices_configured' ] );
		Pickpoint::register_hooks();
	}

	/**
	 * Выполняет глобальные php сценарии
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 */
	private function global_scripts() {

	}

	public function admin_notices_configured() {
		if ( $this->app()->getPopulateOption( 'plugin_configured', 'no' ) === 'yes' ) {
			return;
		}

		$notices = "";

		$section = isset( $_GET['section'] ) ? $_GET['section'] : '';
		if ( $section !== 'wbcr_pickpoint_delivery' ) {
			$text = sprintf(
				'<strong>%s</strong>: %s',
				__( 'Pickpoint Delivery', 'pickpoint-delivery' ),
				sprintf(
					'%s <a href="%s">%s</a>',
					__( 'The plugin does not work and requires configuration, please go to', 'wd-cdek-delivery' ),
					get_admin_url( null, \sprintf( '/admin.php?page=wc-settings&tab=shipping&section=%s',
						Pickpoint::SHIPPING_METHOD_ID ) ),
					__( 'plugin settings', 'pickpoint-delivery' )
				)
			);

			$notices = <<<NOTICE
        <div id="message" class="notice notice-error is-dismissible">
            <p>$text</p>
        </div>
NOTICE;
		}

		echo $notices;
	}

	/**
	 * @param $actions
	 * @param $plugin_file
	 * @param $plugin_data
	 */
	public function add_plugin_action_link( $actions, $plugin_file, $plugin_data ) {
		if ( false === strpos( $plugin_file, WPICKPOINT_PLUGIN_BASE ) ) {
			return $actions;
		}

		if ( $this->is_premium() ) {
			//$settings_link = sprintf( "<a href='%s'>%s</a>", admin_url( "admin.php?page=wc-settings&tab=shipping&section=" . Pickpoint::SHIPPING_METHOD_ID ), __( 'Settings', 'pickpoint-delivery' ) );
			//array_unshift( $actions, $settings_link );
		} else {
			$license_link = '<a href="' . $this->app()->getPluginPageUrl( 'license' ) . '" style="color: #FF5722;font-weight: bold;">' . __( 'License', 'pickpoint-delivery' ) . '</a>';
			array_unshift( $actions, $license_link );
		}

		return $actions;
	}

	public function add_plugin_row_link( $links, $file ) {
		if ( false === strpos( $file, WPICKPOINT_PLUGIN_BASE ) ) {
			return $links;
		}

		$docs_link = sprintf( "<a href='" . $this->app()->get_support()->get_docs_url( true, 'plugins_page' ) . "'>%s</a>", __( 'Documentation', 'pickpoint-delivery' ) );
		$links[]   = $docs_link;

		return $links;
	}
}
