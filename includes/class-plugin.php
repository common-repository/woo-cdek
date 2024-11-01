<?php

namespace WDPC;

/**
 * Core class
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 19.02.2018, Webcraftic
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin extends \Wbcr_Factory437_Plugin {

	/**
	 * @see self::app()
	 * @var \Wbcr_Factory437_Plugin
	 */
	private static $app;

	public static $domain = "delivery-pack";


	/**
	 * Конструктор
	 *
	 * Применяет конструктор родительского класса и записывает экземпляр текущего класса в свойство $app.
	 * Подробнее о свойстве $app см. self::app()
	 *
	 * @param string $plugin_path
	 * @param array $data
	 *
	 * @throws \Exception
	 */
	public function __construct( $plugin_path, $data ) {
		self::$app = $this;
		parent::__construct( $plugin_path, $data );

		if ( is_admin() ) {
			require_once( WDPC_PLUGIN_DIR . '/admin/activation.php' );

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				require_once( WDPC_PLUGIN_DIR . '/admin/ajax/check-license.php' );
			}
			require_once( WDPC_PLUGIN_DIR . '/admin/boot.php' );

			$this->register_activator();
		}

		$this->global_scripts();

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
	 * @return \Wbcr_Factory437_Plugin|Plugin
	 */
	public static function app() {
		return self::$app;
	}

	public static function get_domain() {
		return static::$domain;
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
	}

	/**
	 * Метод проверяет активацию премиум плагина и наличие действующего лицензионнного ключа
	 *
	 * @return bool
	 */
	public function is_premium() {
		if ( $this->premium->is_active() && $this->premium->is_activate() && $this->premium->is_install_package() ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Исключаем загрузку отключенных компонентов плагина
	 *
	 * @return array
	 * @since  1.0.0
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	/*public function get_load_plugin_components()
	{
		$load_components = parent::get_load_plugin_components();

		$deactivate_components = $this->getPopulateOption('deactive_preinstall_components', []);

		if( !empty($deactivate_components) ) {
			foreach((array)$load_components as $component_ID => $component) {
				if( in_array($component_ID, $deactivate_components) ) {
					unset($load_components[$component_ID]);
				}
			}
		}

		if( is_plugin_active('gonzales/gonzales.php') ) {
			unset($load_components['assets_manager']);
		}

		return $load_components;
	}*/

	/**
	 * Регистрируем активатор плагина
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 */
	protected function register_activator() {
		include_once( WDPC_PLUGIN_DIR . '/admin/activation.php' );
		$this->registerActivation( '\WDPC\Activation' );
	}

	/**
	 * Регистрирует классы страниц в плагине
	 *
	 * @throws \Exception
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	private function register_pages() {
		try {
			$this->registerPage( 'WDPC_LicensePage', WDPC_PLUGIN_DIR . '/admin/pages/class-pages-license.php' );
		} catch ( \Exception $e ) {
			throw new \Exception( $e->getMessage() );
		}
	}

	/**
	 * Выполняет глобальные php сценарии
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.0
	 */
	private function global_scripts() {
	}


}
