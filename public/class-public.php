<?php
/**
 * La funcionalidad específica del área pública del plugin.
 */
class EnglishLine_Test_Public {

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
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Registrar acciones AJAX
        add_action('wp_ajax_englishline_form_submit', array($this, 'process_form_submission'));
        add_action('wp_ajax_nopriv_englishline_form_submit', array($this, 'process_form_submission'));
    }

    /**
     * Registra los estilos para el área pública.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/public-style.css', array(), $this->version, 'all');
    }

    /**
     * Registra los scripts para el área pública.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/public-script.js', array('jquery'), $this->version, false);
        
        wp_localize_script($this->plugin_name, 'englishline_test', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('englishline_test_public_nonce'),
        ));
    }

    /**
     * Registra los shortcodes del plugin
     */
    public function register_shortcodes() {
        add_shortcode('englishline_test', array($this, 'render_form_shortcode'));
    }

    /**
     * Renderiza el formulario a través del shortcode
     * 
     * @param array $atts Atributos del shortcode
     * @return string HTML del formulario
     */
    public function render_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'code' => '',
        ), $atts, 'englishline_form');
        
        // Buscar por ID o por código de shortcode
        global $wpdb;
        $table_name = $wpdb->prefix . 'englishline_forms';
        
        if (!empty($atts['id'])) {
            $form = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                intval($atts['id'])
            ));
        } elseif (!empty($atts['code'])) {
            $form = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE shortcode = %s",
                $atts['code']
            ));
        } else {
            return '<p>' . __('Error: Se requiere un ID o código de formulario válido.', 'englishline-test') . '</p>';
        }
        
        if (!$form) {
            return '<p>' . __('Error: El formulario solicitado no existe.', 'englishline-test') . '</p>';
        }
        
        // Construir el HTML del formulario
        ob_start();
        include_once 'partials/form-template.php';
        return ob_get_clean();
    }

    /**
     * Procesa el envío del formulario
     */
    public function process_form_submission() {

        
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_test_public_nonce')) {
            error_log('EnglishLine Test - Error de nonce');
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        // Validar datos básicos
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $user_data = isset($_POST['user_data']) ? $_POST['user_data'] : array(); 
        $user_email = isset($user_data['email']) ? sanitize_email($user_data['email']) : ''; 
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();
        
        error_log('EnglishLine Test - Form ID: ' . $form_id);
        error_log('EnglishLine Test - User Email: ' . $user_email);
        
        if ($form_id <= 0) {
            error_log('EnglishLine Test - ID de formulario inválido');
            wp_send_json_error(array('message' => 'ID de formulario inválido. Por favor, intenta de nuevo.'));
        }
        
        if (empty($user_email)) {
            error_log('EnglishLine Test - Email vacío');
            wp_send_json_error(array('message' => 'Por favor, proporciona un correo electrónico válido.'));
        }
        
        // Verificar si el formulario existe
        global $wpdb;
        $forms_table = $wpdb->prefix . 'englishline_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $form_id));
        
        if (!$form) {
            error_log('EnglishLine Test - Formulario no encontrado: ' . $form_id);
            wp_send_json_error(array('message' => 'El formulario no existe.'));
        }
        
        // Guardar los datos del formulario
        $results_table = $wpdb->prefix . 'englishline_results';
        $user_id = get_current_user_id(); 
        
        // Combinar datos de usuario y respuestas para un mejor almacenamiento
        $complete_data = array(
            'form_data' => $form_data,
            'user_data' => $user_data,
            'timestamp' => current_time('mysql')
        );
        
        $serialized_data = json_encode($complete_data);
        
        // Verificar si la tabla existe
        $table_check = $wpdb->get_var("SHOW TABLES LIKE '$results_table'");
        
        // Verificar si la tabla tiene la columna created_at
        $has_created_at = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $results_table");
        foreach ($columns as $column) {
            if ($column->Field === 'created_at') {
                $has_created_at = true;
                break;
            }
        }
        
        $data = array(
            'form_id' => $form_id,
            'user_id' => $user_id > 0 ? $user_id : null,
            'user_email' => $user_email,
            'form_data' => $serialized_data,
            'status' => 'pending'
        );
        
        $format = array('%d', '%d', '%s', '%s', '%s');
        
        if ($has_created_at) {
            $data['created_at'] = current_time('mysql');
            $format[] = '%s';
        }
        
        $result = $wpdb->insert(
            $results_table,
            $data,
            $format
        );
        
        
        $result_id = $wpdb->insert_id;

        
        try {
            // Notificar al administrador sobre el nuevo envío
            $this->send_admin_notification($form, $user_email, $result_id);
            
            // Enviar mensaje de confirmación al usuario
            $this->send_user_confirmation($user_email);
        } catch (Exception $e) {
            error_log('EnglishLine Test - Error al enviar correos: ' . $e->getMessage());

        }
        
        wp_send_json_success(array(
            'message' => '¡Tu formulario ha sido enviado correctamente! Recibirás los resultados por correo electrónico.',
            'result_id' => $result_id
        ));
    }
    
    /**
     * Envía una notificación al administrador sobre un nuevo envío
     */
    private function send_admin_notification($form, $user_email, $result_id) {
        $admin_email = get_option('englishline_notification_email', get_option('admin_email'));
        
        $subject = sprintf(__('Nuevo envío de formulario #%d: %s', 'englishline-test'), $form->id, $form->title);
        
        $message = sprintf(
            __('Se ha recibido un nuevo envío del formulario "%s".<br><br>Email del usuario: %s<br>ID del resultado: %d<br><br>Para revisar y calificar este envío, <a href="%s">haz clic aquí</a>.', 'englishline-test'),
            $form->title,
            $user_email,
            $result_id,
            admin_url('admin.php?page=englishline-test-results&action=view&id=' . $result_id)
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Envía un correo de confirmación al usuario
     */
    private function send_user_confirmation($user_email) {
        // Obtener la plantilla de correo
        global $wpdb;
        $templates_table = $wpdb->prefix . 'englishline_email_templates';
        
        $table_check = $wpdb->get_var("SHOW TABLES LIKE '$templates_table'");
        if ($table_check) {
            // Obtener la plantilla de confirmación 
            $template = $wpdb->get_row("SELECT * FROM $templates_table WHERE name = 'confirmation_template' LIMIT 1");
            
            if ($template) {
                $subject = $template->subject;
                $content = $template->content;

            } else {
                // Usar una plantilla predeterminada si no existe
                $subject = 'Confirmación de recepción de tu prueba de inglés';
                $content = 'Hola,<br><br>Gracias por completar nuestra prueba de nivel de inglés. Hemos recibido tu envío correctamente.<br><br>Nuestro equipo revisará tus respuestas y te enviaremos los resultados en breve.<br><br>Saludos,<br>El equipo de EnglishLine';

            }
        } else {
            // Usar una plantilla predeterminada si la tabla no existe
            $subject = 'Confirmación de recepción de tu prueba de inglés';
            $content = 'Hola,<br><br>Gracias por completar nuestra prueba de nivel de inglés. Hemos recibido tu envío correctamente.<br><br>Nuestro equipo revisará tus respuestas y te enviaremos los resultados en breve.<br><br>Saludos,<br>El equipo de EnglishLine';

        }
        
        // Configurar encabezados para correo HTML
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Obtener dirección de correo para envío
        $admin_email = get_option('englishline_notification_email', get_option('admin_email'));
        
        // Añadir remitente
        $headers[] = 'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>';
        
        // Enviar correo
        $sent = wp_mail($user_email, $subject, $content, $headers);
    }
}