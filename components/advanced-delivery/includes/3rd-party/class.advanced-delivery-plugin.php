<?php

namespace WDPC\Advanced;

use WBCR\Delivery\Advanced\Activation;
use WBCR\Delivery\Advanced\Advanced;
use WBCR\Delivery\Advanced\Ajax;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core class for submodule
 *
 * @author        Artem Prihodko <webtemyk@yandex.ru>
 * @copyright (c) 2021, CreativeMotion
 * @version       1.0
 */
class Plugin {

	/**
	 * @see self::app()
	 * @var \WDPC\Plugin
	 */
	private static $app;

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

		self::$app = \WDPC\Plugin::app();

		$this->global_scripts();

		if ( is_admin() ) {
			$this->init_activation();
			require( WDAD_PLUGIN_DIR . '/admin/boot.php' );
			//require( WDAD_PLUGIN_DIR . '/admin/ajax/check-license.php' );

			add_filter( 'plugin_row_meta', [ $this, 'add_plugin_row_link' ], 10, 2 );
		}

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Статический метод для быстрого доступа к интерфейсу плагина.
	 *
	 * Позволяет разработчику глобально получить доступ к экземпляру класса плагина в любом месте
	 * плагина, но при этом разработчик не может вносить изменения в основной класс плагина.
	 *
	 * Используется для получения настроек плагина, информации о плагине, для доступа к вспомогательным
	 * классам.
	 *
	 * @return \WDPC\Plugin
	 */
	public static function app() {
		return self::$app;
	}

	/**
	 * Метод проверяет активацию премиум плагина и наличие действующего лицензионного ключа
	 *
	 * @return bool
	 */
	public function is_premium() {
		/*
		if ( $this->app()->premium->is_active() && $this->app()->premium->is_activate() ) {
			return true;
		} else {
			return false;
		}
		*/
		return false;
	}

	/**
	 * Выполняет php сценарии, когда все Wordpress плагины будут загружены
	 *
	 * Регистрируем страницы в хуке plugins_loaded для того, чтобы из классов страниц
	 * мы могли выполнять проверку установлен/активирован/загружен ли тот или иной аддон.
	 *
	 *
	 * @throws \Exception
	 * @since  1.0.0
	 */
	public function plugins_loaded() {
		if ( is_admin() ) {
			$this->register_pages();
		}

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && class_exists( 'WC_Shipping_Method' ) ) {
			require_once( WDPC_PLUGIN_DIR . '/libs/delivery/base/boot.php' );
			require_once( WDAD_ADVANCED_DIR . '/load.php' );

			$this->registerDeliveryMethods();

			if ( is_admin() ) {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

					/**
					 * Load ajax methods
					 */
					require_once WDAD_ADVANCED_DIR . '/includes/class.ajax.php';
					$this->ajax = new Ajax();

				}
			}

		}
	}

	/**
	 * Инициализируем класс активатор
	 * В этом классе вы можете реализовать функции, которые должны выполняться при активации
	 * и деактиваии плагина.
	 */
	protected function init_activation() {
		include_once( WDAD_PLUGIN_DIR . '/admin/activation.php' );
		self::app()->registerActivation( Activation::class );
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
	 */
	private function register_pages() {
	}


	private function registerDeliveryMethods() {
		require_once( WDAD_PLUGIN_DIR . '/includes/trait-deliver-method.php' );

		add_action( 'admin_notices', [ $this, 'admin_notices_configured' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices_dev' ] );
		Advanced::register_hooks();
	}

	public function admin_notices_configured() {
	}

	public function admin_notices_dev() {
	}

	/**
	 * Выполняет глобальные php сценарии
	 *
	 * @since  1.0.0
	 */
	private function global_scripts() {

	}

	public function add_plugin_row_link( $links, $file ) {
		if ( false === strpos( $file, WDAD_PLUGIN_BASE ) ) {
			return $links;
		}

		$docs_link = sprintf( "<a href='" . $this->app()->get_support()->get_docs_url( true, 'plugins_page' ) . "'>%s</a>", __( 'Documentation', 'wd-advanced-delivery' ) );
		$links[]   = $docs_link;

		return $links;
	}
}
