<?php
/**
 * La clase principal del plugin que define la funcionalidad del lado administrativo y público
 */
class EnglishLine_Test {

    /**
     * El cargador que mantiene todos los hooks del plugin.
     *
     * @var      EnglishLine_Test_Loader    $loader    Mantiene y registra todos los hooks para el plugin
     */
    protected $loader;

    /**
     * El identificador único de este plugin.
     *
     * @var      string    $plugin_name    El string utilizado para identificar este plugin
     */
    protected $plugin_name;

    /**
     * La versión actual del plugin.
     *
     * @var      string    $version    La versión actual del plugin
     */
    protected $version;

    /**
     * Define la funcionalidad principal del plugin.
     */
    public function __construct() {
        if (defined('ENGLISHLINE_TEST_VERSION')) {
            $this->version = ENGLISHLINE_TEST_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'englishline-test';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Carga las dependencias requeridas para este plugin.
     */
    private function load_dependencies() {

        /**
         * La clase responsable de orquestar las acciones y filtros del
         * núcleo del plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-loader.php';

        /**
         * La clase responsable de definir la funcionalidad de internacionalización
         * del plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-i18n.php';

        /**
         * La clase responsable de definir toda la funcionalidad del área de administración
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin.php';
        
        /**
         * La clase responsable de manejar las peticiones Ajax en el admin
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ajax-handler.php';

        /**
         * La clase responsable de definir toda la funcionalidad del área pública
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-public.php';

        /**
         * Funciones relacionadas con la configuración del plugin
         */
        require_once plugin_dir_path(__FILE__) . 'settings-functions.php';
        

        $this->loader = new EnglishLine_Test_Loader();
    }

    /**
     * Define la configuración local del plugin para internacionalización.
     */
    private function set_locale() {
        $plugin_i18n = new EnglishLine_Test_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Registra todos los hooks relacionados con la funcionalidad del área de administración
     * del plugin.
     */
    private function define_admin_hooks() {
        $plugin_admin = new EnglishLine_Test_Admin($this->get_plugin_name(), $this->get_version());
        $plugin_ajax = new EnglishLine_Test_Ajax_Handler($this->get_plugin_name(), $this->get_version());

        // Encolado de estilos y scripts
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Menús de administración
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Restricción de acceso al plugin solo a roles específicos
        $this->loader->add_action('admin_init', $plugin_admin, 'restrict_admin_access');

        $this->loader->add_action('admin_init', $plugin_admin, 'englishline_test_register_settings');
        
        // Manejadores de Ajax
        $this->loader->add_action('wp_ajax_englishline_save_form', $plugin_ajax, 'save_form');
        $this->loader->add_action('wp_ajax_englishline_delete_form', $plugin_ajax, 'delete_form');
        $this->loader->add_action('wp_ajax_englishline_duplicate_form', $plugin_ajax, 'duplicate_form');
        $this->loader->add_action('wp_ajax_englishline_toggle_form_status', $plugin_ajax, 'toggle_form_status');
        $this->loader->add_action('wp_ajax_englishline_save_email_template', $plugin_ajax, 'save_email_template');
        $this->loader->add_action('wp_ajax_englishline_save_result', $plugin_ajax, 'save_result');
        $this->loader->add_action('wp_ajax_englishline_check_github_updates', $plugin_ajax, 'check_github_updates');
        $this->loader->add_action('wp_ajax_englishline_update_from_github', $plugin_ajax, 'update_from_github');
        $this->loader->add_action('wp_ajax_englishline_export_settings', $plugin_ajax, 'export_settings');
        $this->loader->add_action('wp_ajax_englishline_import_settings', $plugin_ajax, 'import_settings');
    }

    /**
     * Registra todos los hooks relacionados con la funcionalidad del área pública
     * del plugin.
     */
    private function define_public_hooks() {
        $plugin_public = new EnglishLine_Test_Public($this->get_plugin_name(), $this->get_version());

        // Encolado de estilos y scripts
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Registrar shortcode
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // Manejador para enviar formularios
        $this->loader->add_action('wp_ajax_englishline_submit_form', $plugin_public, 'process_form_submission');
        $this->loader->add_action('wp_ajax_nopriv_englishline_submit_form', $plugin_public, 'process_form_submission');
    }

    /**
     * Ejecuta el cargador para ejecutar todos los hooks con WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * El nombre del plugin utilizado para identificarlo dentro de WordPress.
     *
     * @return    string    El nombre del plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Referencia a la clase que orquesta los hooks del plugin.
     *
     * @return    EnglishLine_Test_Loader    Orquesta los hooks del plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retorna el número de versión del plugin.
     *
     * @return    string    El número de versión del plugin.
     */
    public function get_version() {
        return $this->version;
    }
}