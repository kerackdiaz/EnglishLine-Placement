<?php

/**
 * La funcionalidad específica de administración del plugin.
 */
class EnglishLine_Test_Admin
{

    /**
     * El ID de este plugin.
     *
     * @var      string    $plugin_name    El ID del plugin.
     */
    private $plugin_name;

    /**
     * La versión del plugin.
     *
     * @var      string    $version    La versión actual del plugin.
     */
    private $version;

    /**
     * Inicializa la clase y establece sus propiedades.
     *
     * @param      string    $plugin_name       El nombre del plugin.
     * @param      string    $version    La versión del plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_init', array($this, 'handle_result_actions'), 5);
    }

    /**
     * Registra los estilos para el área de administración.
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/admin-style.css', array(), $this->version, 'all');
    }

    /**
     * Registra los scripts para el área de administración.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/admin-script.js', array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'), $this->version, false);
        wp_enqueue_script('englishline-test-form-builder-ui', plugin_dir_url(__FILE__) . 'js/components/form-builder-ui.js', array('jquery'), $this->version, false);
        wp_enqueue_script('englishline-test-form-builder-utils', plugin_dir_url(__FILE__) . 'js/components/form-builder-utils.js', array('jquery'), $this->version, false);
        wp_enqueue_script('englishline-test-form-builder-data', plugin_dir_url(__FILE__) . 'js/components/form-builder-data.js', array('jquery', 'englishline-test-form-builder-ui', 'englishline-test-form-builder-utils'), $this->version, false);
        wp_enqueue_script('englishline-test-form-builder-drag-and-drop', plugin_dir_url(__FILE__) . 'js/components/form-builder-drag-and-drop.js', array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'englishline-test-form-builder-ui', 'englishline-test-form-builder-data', 'englishline-test-form-builder-utils'), $this->version, false);
        wp_enqueue_script('englishline-test-form-builder-events', plugin_dir_url(__FILE__) . 'js/components/form-builder-events.js', array('jquery', 'englishline-test-form-builder-ui', 'englishline-test-form-builder-data', 'englishline-test-form-builder-utils'), $this->version, false);

        wp_enqueue_script('englishline-test-debug-helper', plugin_dir_url(__FILE__) . 'js/modules/debug-helper.js', array('jquery'), $this->version, true);

        wp_enqueue_script('englishline-test-form-builder', plugin_dir_url(__FILE__) . 'js/modules/form-builder.js', array(
            'jquery',
            'jquery-ui-sortable',
            'jquery-ui-draggable',
            'jquery-ui-droppable',
            'englishline-test-form-builder-ui',
            'englishline-test-form-builder-data',
            'englishline-test-form-builder-utils',
            'englishline-test-form-builder-drag-and-drop',
            'englishline-test-form-builder-events'
        ), $this->version, false);

        wp_enqueue_script('englishline-test-form-preview', plugin_dir_url(__FILE__) . 'js/modules/form-preview.js', array('jquery'), $this->version, false);
        wp_enqueue_script('englishline-test-results', plugin_dir_url(__FILE__) . 'js/modules/results.js', array('jquery'), $this->version, false);
        wp_enqueue_script('englishline-test-form-adapter', plugin_dir_url(__FILE__) . 'js/modules/form-adapter.js', array('jquery', 'englishline-test-form-builder'), $this->version, false);
        wp_enqueue_script('englishline-test-tabs', plugin_dir_url(__FILE__) . 'js/modules/tabs.js', array('jquery'), $this->version, false);

        // Cargar la API de medios de WordPress para el selector de imágenes
        wp_enqueue_media();

        // Localiza el script principal con algunos datos para este plugin
        wp_localize_script($this->plugin_name, 'englishline_test', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('englishline_test_nonce'),
            'plugin_url' => plugin_dir_url(__FILE__),
            'i18n' => array(
                'select_image' => __('Seleccionar imagen', 'englishline-test'),
                'use_this_image' => __('Usar esta imagen', 'englishline-test'),
                'no_image_selected' => __('Imagen no seleccionada', 'englishline-test'),
                'image_selected' => __('Imagen seleccionada', 'englishline-test'),
                'confirm_delete' => __('¿Estás seguro de que quieres eliminar este elemento?', 'englishline-test'),
            )
        ));
    }


    /**
     * Añade las opciones de menú para el plugin en el área de administración.
     */
    public function add_plugin_admin_menu()
    {
        // Menú principal
        add_menu_page(
            __('EnglishLine Placement', 'englishline-test'),
            __('EnglishLine Placement', 'englishline-test'),
            'manage_options',
            'englishline-test',
            array($this, 'display_plugin_dashboard_page'),
            'dashicons-welcome-learn-more',
            26
        );

        // Submenú - Dashboard
        add_submenu_page(
            'englishline-test',
            __('Dashboard', 'englishline-test'),
            __('Dashboard', 'englishline-test'),
            'manage_options',
            'englishline-test',
            array($this, 'display_plugin_dashboard_page')
        );

        // Submenú - Formularios
        add_submenu_page(
            'englishline-test',
            __('Formularios', 'englishline-test'),
            __('Formularios', 'englishline-test'),
            'manage_options',
            'englishline-test-forms',
            array($this, 'display_plugin_forms_page')
        );

        // Submenú - Editor de formularios
        add_submenu_page(
            'englishline-test',
            __('Editor de Formulario', 'englishline-test'),
            __('Editor de Formulario', 'englishline-test'),
            'manage_options',
            'englishline-test-form-editor',
            array($this, 'display_plugin_form_editor_page')
        );


        // Submenú - Resultados
        add_submenu_page(
            'englishline-test',
            __('Resultados', 'englishline-test'),
            __('Resultados', 'englishline-test'),
            'manage_options',
            'englishline-test-results',
            array($this, 'display_plugin_results_page')
        );

        // Submenú - Vista de resultado 
        add_submenu_page(
            'englishline-test',
            __('Vista de Resultado', 'englishline-test'),
            __('Vista de Resultado', 'englishline-test'),
            'manage_options',
            'englishline-test-result-view',
            array($this, 'display_plugin_result_view_page')
        );

        add_submenu_page(
            'englishline-test',
            esc_html__('Plantillas de Correo', 'englishline-test'),
            esc_html__('Plantillas de Correo', 'englishline-test'),
            'manage_options',
            'englishline-test-email-templates',
            array($this, 'display_email_templates_page')
        );


        add_action('admin_head', function () {
            echo '<style>
                #adminmenu .wp-submenu a[href="admin.php?page=englishline-test-form-editor"],
                #adminmenu .wp-submenu a[href="admin.php?page=englishline-test-result-view"],
                #adminmenu .wp-submenu a[href="admin.php?page=englishline-test-email-templates"] {
                    display: none;
                }
            </style>';
        });


        // Submenú - Configuración
        add_submenu_page(
            'englishline-test',
            __('Configuración', 'englishline-test'),
            __('Configuración', 'englishline-test'),
            'manage_options',
            'englishline-test-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Restringe el acceso al plugin sólo para administradores y rol personalizado
     */
    public function restrict_admin_access()
    {
        // Verificar si el usuario actual está en una página de nuestro plugin
        $current_screen = get_current_screen();
        if (!$current_screen) {
            return;
        }

        // Si la pantalla actual pertenece a nuestro plugin
        if (strpos($current_screen->id, 'englishline-test') !== false) {
            // Verificar si el usuario tiene los permisos adecuados
            if (!current_user_can('manage_options') && !current_user_can('englishline_manager')) {
                wp_die(__('No tienes permiso para acceder a esta página.', 'englishline-test'));
            }
        }
    }
    public function register_settings_page()
    {
        // Registrar opciones
        register_setting(
            'englishline_test_options',
            'englishline_test_settings',
            array(
                'sanitize_callback' => 'englishline_test_sanitize_settings',
                'default' => array()
            )
        );

        // Registrar la página como una página de opciones válida
        global $plugin_page;
        if ($plugin_page == 'englishline-test-settings') {
            global $whitelist_options;
            $whitelist_options['englishline_test_options'] = array('englishline_test_settings');
        }
    }

    /**
     * Renderiza la página de dashboard del plugin.
     */
    public function display_plugin_dashboard_page()
    {
        // Asegurarnos de que el archivo existe antes de incluirlo
        $file_path = plugin_dir_path(__FILE__) . 'partials/dashboard-page.php';
        if (file_exists($file_path)) {
            include_once $file_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Dashboard', 'englishline-test') . '</h1>';
            echo '<div class="notice notice-error"><p>' .
                esc_html__('El archivo de la página no se encuentra.', 'englishline-test') .
                '</p></div></div>';
        }
    }

    /**
     * Renderiza la página de formularios del plugin.
     */
    public function display_plugin_forms_page()
    {
        $file_path = plugin_dir_path(__FILE__) . 'partials/forms-page.php';
        if (file_exists($file_path)) {
            include_once $file_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Formularios', 'englishline-test') . '</h1>';
            echo '<div class="notice notice-error"><p>' .
                esc_html__('El archivo de la página no se encuentra.', 'englishline-test') .
                '</p></div></div>';
        }
    }

    /**
     * Renderiza la página del editor de formularios del plugin.
     */
    public function display_plugin_form_editor_page()
    {
        $file_path = plugin_dir_path(__FILE__) . 'partials/form-editor.php';
        if (file_exists($file_path)) {
            include_once $file_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Editor de Formulario', 'englishline-test') . '</h1>';
            echo '<div class="notice notice-error"><p>' .
                esc_html__('El archivo form-editor.php no se encuentra en la carpeta partials.', 'englishline-test') .
                '</p></div></div>';
        }
    }

    /**
     * Renderiza la página de resultados del plugin.
     */
    public function display_plugin_results_page()
    {
        $file_path = plugin_dir_path(__FILE__) . 'partials/results-page.php';
        if (file_exists($file_path)) {
            include_once $file_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Resultados', 'englishline-test') . '</h1>';
            echo '<div class="notice notice-error"><p>' .
                esc_html__('El archivo de la página no se encuentra.', 'englishline-test') .
                '</p></div></div>';
        }
    }

    /**
     * Renderiza la página de vista de un resultado específico.
     */
    public function display_plugin_result_view_page()
    {
        $file_path = plugin_dir_path(__FILE__) . 'partials/result-view.php';
        if (file_exists($file_path)) {
            include_once $file_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Vista de Resultado', 'englishline-test') . '</h1>';
            echo '<div class="notice notice-error"><p>' .
                esc_html__('El archivo de la página no se encuentra.', 'englishline-test') .
                '</p></div></div>';
        }
    }

    /**
     * Muestra la página de plantillas de correo electrónico
     */
    public function display_email_templates_page()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/email-templates-page.php';
    }

    /**
     * Renderiza la página de configuración del plugin.
     */
    public function display_settings_page()
    {
        $file_path = plugin_dir_path(__FILE__) . 'partials/settings-page.php';
        if (file_exists($file_path)) {
            include_once $file_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Configuración', 'englishline-test') . '</h1>';
            echo '<div class="notice notice-error"><p>' .
                esc_html__('El archivo de la página no se encuentra.', 'englishline-test') .
                '</p></div></div>';
        }
    }

    /**
     * Registra las opciones de configuración
     */
    public function englishline_test_register_settings()
    {
        // Este método llama a la función global que definimos en settings-functions.php
        if (function_exists('englishline_test_register_settings')) {
            \englishline_test_register_settings();
        }
    }

    /**
     * Inicializa la funcionalidad de formularios
     */
    public function init_forms_functionality()
    {
        // Comprobar si las tablas de la base de datos existen, si no, crearlas
        $this->maybe_create_db_tables();
    }

    /**
     * Maneja las acciones relacionadas con los resultados antes de cualquier salida HTML
     */
    public function handle_result_actions()
    {
        // Solo procesar si estamos en nuestra página de resultados
        if (!isset($_GET['page']) || $_GET['page'] !== 'englishline-test-results') {
            return;
        }

        // Verificar si hay una acción para procesar
        if (!isset($_GET['action'])) {
            return;
        }

        $action = sanitize_text_field($_GET['action']);
        $result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

        // Manejar la acción eliminar
        if ($action === 'delete' && $result_id > 0) {
            // Verificar el nonce para seguridad
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_result_' . $result_id)) {
                global $wpdb;
                $results_table = $wpdb->prefix . 'englishline_results';

                // Eliminar el resultado
                $deleted = $wpdb->delete(
                    $results_table,
                    ['id' => $result_id],
                    ['%d']
                );

                // Redirigir con mensaje de estado
                wp_redirect(admin_url('admin.php?page=englishline-test-results&status=' . ($deleted ? 'deleted' : 'error')));
                exit;
            }
        }
    }

    /**
     * Crea las tablas necesarias en la base de datos si no existen
     */
    private function maybe_create_db_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla para almacenar formularios
        $table_forms = $wpdb->prefix . 'englishline_forms';

        // Verificar si la tabla ya existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_forms}'") != $table_forms) {
            $sql_forms = "CREATE TABLE $table_forms (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                title varchar(255) NOT NULL,
                description text,
                settings longtext,
                fields longtext,
                status varchar(20) NOT NULL DEFAULT 'draft',
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                author_id bigint(20) NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_forms);
        }

        // Tabla para almacenar envíos/resultados
        $table_submissions = $wpdb->prefix . 'englishline_submissions';

        // Verificar si la tabla ya existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_submissions}'") != $table_submissions) {
            $sql_submissions = "CREATE TABLE $table_submissions (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                form_id bigint(20) NOT NULL,
                user_id bigint(20),
                responses longtext NOT NULL,
                score decimal(5,2),
                status varchar(20) NOT NULL DEFAULT 'pending',
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                ip_address varchar(100),
                PRIMARY KEY  (id),
                KEY form_id (form_id),
                KEY user_id (user_id)
            ) $charset_collate;";

            dbDelta($sql_submissions);
        }
    }
}
