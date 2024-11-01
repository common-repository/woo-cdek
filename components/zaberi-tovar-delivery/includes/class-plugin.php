<?php


namespace WBCR\Delivery\ZaberiTovar;


use Wbcr_Factory437_Plugin;

class Plugin extends Wbcr_Factory437_Plugin
{
    /**
     * @see self::app()
     * @var \Wbcr_Factory437_Plugin
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
     * @param array $data
     *
     * @throws \Exception
     */
    public function __construct( $plugin_path, $data ) {
        parent::__construct( $plugin_path, $data );
        self::$app = $this;

        $this->global_scripts();

        $domain = get_plugin_data( $plugin_path );
        if( ! isset( $domain['TextDomain'] ) ) {
            $domain = 'wd-zaberi-tovar';
        } else {
            $domain = $domain['TextDomain'];
        }

        self::$domain = $domain;

        if ( is_admin() ) {
            $this->init_activation();
            require( WDZT_PLUGIN_DIR . '/admin/boot.php' );
            require( WDZT_PLUGIN_DIR . '/admin/ajax/check-license.php' );

            add_filter( 'plugin_action_links', [ $this, 'add_plugin_action_link' ], 99999, 3 );
            add_filter( 'plugin_row_meta', [ $this, 'add_plugin_row_link' ], 10, 2 );
        }

        add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
    }

    public static function get_domain() {
        return static::$domain;
    }

    public static function __( $key ) {
        return __( $key, static::get_domain() );
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

    /**
     * Метод проверяет активацию премиум плагина и наличие действующего лицензионнного ключа
     *
     * @return bool
     */
    public function is_premium() {
        if ( $this->premium->is_active() && $this->premium->is_activate() ) {
            return true;
        } else {
            return false;
        }
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
            require_once( WDZT_PLUGIN_DIR . '/libs/delivery/base/boot.php' );
            require_once( WDZT_PLUGIN_DIR . '/includes/ZaberiTovar/load.php' );
            $this->registerDeliveryMethods();
        }
    }

    /**
     * Инициализируем класс активатор
     * В этом классе вы можете реализовать функции, которые должны выполняться при активации
     * и деактиваии плагина.
     */
    protected function init_activation() {
        include_once( WDZT_PLUGIN_DIR . '/admin/activation.php' );
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
     * @author Alexander Kovalev <alex.kovalevv@gmail.com>
     */
    private function register_pages() {
        self::app()->registerPage( 'WDZT_LicensePage', WDZT_PLUGIN_DIR . '/admin/pages/class-pages-license.php' );
    }


    private function registerDeliveryMethods() {
	    //require_once( WCD_PLUGIN_DIR . '/includes/trait-deliver-method.php' );
	    require_once( WDZT_PLUGIN_DIR . '/includes/functions.php' );

	    ZaberiTovar::register_hooks();
    }

    /**
     * Выполняет глобальные php сценарии
     *
     * @author Alexander Kovalev <alex.kovalevv@gmail.com>
     * @since  1.0.0
     */
    private function global_scripts() {

    }

    /**
     * @param $actions
     * @param $plugin_file
     * @param $plugin_data
     */
    public function add_plugin_action_link( $actions, $plugin_file, $plugin_data ) {
        if ( false === strpos( $plugin_file, WDZT_PLUGIN_BASE ) ) {
            return $actions;
        }

        if ( $this->is_premium() ) {
            //$settings_link = sprintf( "<a href='%s'>%s</a>", admin_url( "admin.php?page=wc-settings&tab=shipping&section=" . Yandex::SHIPPING_METHOD_ID ), __( 'Settings', 'wd-zaberi-tovar' ) );
            //array_unshift( $actions, $settings_link );
        } else {
            $license_link = '<a href="' . $this->getPluginPageUrl( 'license' ) . '" style="color: #FF5722;font-weight: bold;">' . __( 'License', 'wd-zaberi-tovar' ) . '</a>';
            array_unshift( $actions, $license_link );
        }

        return $actions;
    }

    public function add_plugin_row_link( $links, $file ) {
        if ( false === strpos( $file, WDZT_PLUGIN_BASE ) ) {
            return $links;
        }

        $docs_link = sprintf( "<a href='".$this->get_support()->get_docs_url( true, 'plugins_page' )."'>%s</a>", __( 'Documentation', 'wd-zaberi-tovar' ) );
        $links[]   = $docs_link;

        return $links;
    }
}
