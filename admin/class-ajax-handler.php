<?php
/**
 * La clase que maneja todas las solicitudes Ajax
 */
class EnglishLine_Test_Ajax_Handler {

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
    public function __construct($plugin_name = '', $version = '') {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_post_englishline_export_data', array($this, 'handle_export_data'));
    }

    /**
     * Guarda un formulario en la base de datos
     */
    public function save_form() {
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_test_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options') && !current_user_can('englishline_manager')) {
            wp_send_json_error(array('message' => 'No tienes permiso para realizar esta acción.'));
        }

        // Validar y sanitizar datos
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $form_data = isset($_POST['form_data']) ? json_encode($_POST['form_data']) : '';
        $form_style = isset($_POST['form_style']) ? json_encode($_POST['form_style']) : '';
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (empty($title)) {
            wp_send_json_error(array('message' => 'El título es obligatorio.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'englishline_forms';

        // Si tenemos un ID, actualizamos el formulario existente
        if ($form_id > 0) {
            $result = $wpdb->update(
                $table_name,
                array(
                    'title' => $title,
                    'description' => $description,
                    'form_data' => $form_data,
                    'form_style' => $form_style,
                ),
                array('id' => $form_id),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => 'Formulario actualizado correctamente.',
                    'form_id' => $form_id
                ));
            } else {
                wp_send_json_error(array('message' => 'Error al actualizar el formulario.'));
            }
        } 
        // Si no tenemos ID, creamos un nuevo formulario
        else {
            // Generar shortcode único
            $shortcode = 'et_form_' . time();
            
            $result = $wpdb->insert(
                $table_name,
                array(
                    'title' => $title,
                    'description' => $description,
                    'form_data' => $form_data,
                    'form_style' => $form_style,
                    'shortcode' => $shortcode,
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result) {
                $new_form_id = $wpdb->insert_id;
                wp_send_json_success(array(
                    'message' => 'Formulario creado correctamente.',
                    'form_id' => $new_form_id,
                    'shortcode' => $shortcode
                ));
            } else {
                wp_send_json_error(array('message' => 'Error al crear el formulario.'));
            }
        }
        
        // Si llegamos aquí, algo salió mal
        wp_send_json_error(array('message' => 'Ocurrió un error inesperado.'));
    }

    /**
     * Eliminar un formulario vía AJAX
     */
    public function delete_form() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_delete_form_' . $_POST['form_id'])) {
            wp_send_json_error(array('message' => __('Error de seguridad', 'englishline-test')));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso para realizar esta acción', 'englishline-test')));
        }
        
        $form_id = intval($_POST['form_id']);
        if ($form_id <= 0) {
            wp_send_json_error(array('message' => __('ID de formulario inválido', 'englishline-test')));
        }
        
        global $wpdb;
        
        // Iniciar transacción
        $wpdb->query('START TRANSACTION');
        
        try {
            // Eliminar primero los envíos asociados
            $submissions_table = $wpdb->prefix . 'englishline_submissions';
            $wpdb->delete($submissions_table, array('form_id' => $form_id), array('%d'));
            
            // Luego eliminar el formulario
            $forms_table = $wpdb->prefix . 'englishline_forms';
            $result = $wpdb->delete($forms_table, array('id' => $form_id), array('%d'));
            
            if ($result === false) {
                // Si hay algún error en la eliminación, revertir cambios
                $wpdb->query('ROLLBACK');
                wp_send_json_error(array('message' => __('Error al eliminar el formulario', 'englishline-test')));
                return;
            }
            
            // Confirmar cambios
            $wpdb->query('COMMIT');
            wp_send_json_success(array('message' => __('Formulario eliminado correctamente', 'englishline-test')));
        } catch (Exception $e) {
            // Si hay alguna excepción, revertir cambios
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => __('Error al eliminar el formulario', 'englishline-test')));
        }
    }
    
    /**
     * Duplicar un formulario vía AJAX
     */
    public function duplicate_form() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_duplicate_form_' . $_POST['form_id'])) {
            wp_send_json_error(array('message' => __('Error de seguridad', 'englishline-test')));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso para realizar esta acción', 'englishline-test')));
        }
        
        $form_id = intval($_POST['form_id']);
        if ($form_id <= 0) {
            wp_send_json_error(array('message' => __('ID de formulario inválido', 'englishline-test')));
        }
        
        global $wpdb;
        $forms_table = $wpdb->prefix . 'englishline_forms';
        
        // Obtener el formulario original
        $original_form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$forms_table} WHERE id = %d",
            $form_id
        ), ARRAY_A);
        
        if (!$original_form) {
            wp_send_json_error(array('message' => __('Formulario no encontrado', 'englishline-test')));
            return;
        }
        
        // Crear un nuevo título para la copia
        $new_title = sprintf(__('Copia de %s', 'englishline-test'), $original_form['title']);
        
        // Insertar la copia
        $result = $wpdb->insert(
            $forms_table,
            array(
                'title' => $new_title,
                'description' => $original_form['description'],
                'settings' => $original_form['settings'],
                'fields' => $original_form['fields'],
                'status' => 'draft', // La copia siempre comienza como borrador
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'author_id' => get_current_user_id()
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Error al duplicar el formulario', 'englishline-test')));
            return;
        }
        
        $new_form_id = $wpdb->insert_id;
        wp_send_json_success(array(
            'message' => __('Formulario duplicado correctamente', 'englishline-test'),
            'new_form_id' => $new_form_id
        ));
    }
    
    /**
     * Cambiar el estado de un formulario vía AJAX
     */
    public function toggle_form_status() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_toggle_form_status_' . $_POST['form_id'])) {
            wp_send_json_error(array('message' => __('Error de seguridad', 'englishline-test')));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso para realizar esta acción', 'englishline-test')));
        }
        
        $form_id = intval($_POST['form_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        
        if ($form_id <= 0) {
            wp_send_json_error(array('message' => __('ID de formulario inválido', 'englishline-test')));
        }
        
        // Validar el nuevo estado
        if (!in_array($new_status, array('published', 'draft', 'archived'))) {
            wp_send_json_error(array('message' => __('Estado no válido', 'englishline-test')));
        }
        
        global $wpdb;
        $forms_table = $wpdb->prefix . 'englishline_forms';
        
        // Actualizar el estado
        $result = $wpdb->update(
            $forms_table,
            array(
                'status' => $new_status,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $form_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Error al actualizar el estado del formulario', 'englishline-test')));
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Estado del formulario actualizado correctamente', 'englishline-test'),
            'status' => $new_status
        ));
    }

    /**
     * Guarda una plantilla de correo electrónico
     */
    public function save_email_template() {
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_test_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options') && !current_user_can('englishline_manager')) {
            wp_send_json_error(array('message' => 'No tienes permiso para realizar esta acción.'));
        }

        // Validar y sanitizar datos
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        
        if (empty($name) || empty($subject) || empty($content)) {
            wp_send_json_error(array('message' => 'Todos los campos son obligatorios.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'englishline_email_templates';

        // Si tenemos un ID, actualizamos la plantilla existente
        if ($template_id > 0) {
            $result = $wpdb->update(
                $table_name,
                array(
                    'name' => $name,
                    'subject' => $subject,
                    'content' => $content,
                ),
                array('id' => $template_id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => 'Plantilla de correo actualizada correctamente.',
                    'template_id' => $template_id
                ));
            } else {
                wp_send_json_error(array('message' => 'Error al actualizar la plantilla de correo.'));
            }
        } 
        // Si no tenemos ID, creamos una nueva plantilla
        else {
            $result = $wpdb->insert(
                $table_name,
                array(
                    'name' => $name,
                    'subject' => $subject,
                    'content' => $content,
                ),
                array('%s', '%s', '%s')
            );
            
            if ($result) {
                $new_template_id = $wpdb->insert_id;
                wp_send_json_success(array(
                    'message' => 'Plantilla de correo creada correctamente.',
                    'template_id' => $new_template_id
                ));
            } else {
                wp_send_json_error(array('message' => 'Error al crear la plantilla de correo.'));
            }
        }
        
        // Si llegamos aquí, algo salió mal
        wp_send_json_error(array('message' => 'Ocurrió un error inesperado.'));
    }

    /**
     * Guarda un resultado del formulario con calificación
     */
    public function save_result() {
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_test_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options') && !current_user_can('englishline_manager')) {
            wp_send_json_error(array('message' => 'No tienes permiso para realizar esta acción.'));
        }

        // Validar y sanitizar datos
        $result_id = isset($_POST['result_id']) ? intval($_POST['result_id']) : 0;
        $score = isset($_POST['score']) ? floatval($_POST['score']) : 0;
        $feedback = isset($_POST['feedback']) ? sanitize_textarea_field($_POST['feedback']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'graded';
        
        if ($result_id <= 0) {
            wp_send_json_error(array('message' => 'ID de resultado inválido.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'englishline_results';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'score' => $score,
                'feedback' => $feedback,
                'status' => $status
            ),
            array('id' => $result_id),
            array('%f', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Si se actualiza correctamente y el estado es "graded", enviamos el correo electrónico
            if ($status === 'graded') {
                // Obtener datos del resultado
                $result_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d",
                    $result_id
                ));
                
                if ($result_data && !empty($result_data->user_email)) {
                    // Enviar correo electrónico con los resultados
                    $this->send_result_email($result_data, $score, $feedback);
                }
            }
            
            wp_send_json_success(array(
                'message' => 'Resultado guardado y correo enviado correctamente.'
            ));
        } else {
            wp_send_json_error(array('message' => 'Error al guardar el resultado.'));
        }
    }

    /**
     * Maneja las acciones relacionadas con los resultados antes de cualquier salida HTML
     */
    public function handle_result_actions() {
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
     * Envía un correo electrónico con los resultados
     */
    private function send_result_email($result_data, $score, $feedback) {
        // Obtener la plantilla de correo
        global $wpdb;
        $templates_table = $wpdb->prefix . 'englishline_email_templates';
        
        // Obtener la plantilla de resultados (puedes tener una configuración para elegir cuál usar)
        $template = $wpdb->get_row("SELECT * FROM $templates_table WHERE name = 'results_template' LIMIT 1");
        
        if (!$template) {
            // Usar una plantilla predeterminada si no existe
            $subject = 'Resultados de tu prueba de inglés';
            $content = 'Hola,<br><br>Gracias por completar nuestra prueba de nivel de inglés.<br><br>Tu puntuación es: {score}/100<br><br>Comentarios: {feedback}<br><br>Saludos,<br>El equipo de EnglishLine';
        } else {
            $subject = $template->subject;
            $content = $template->content;
        }
        
        // Reemplazar variables en la plantilla
        $subject = str_replace('{score}', $score, $subject);
        $content = str_replace('{score}', $score, $content);
        $content = str_replace('{feedback}', nl2br($feedback), $content);
        
        // Configurar encabezados para correo HTML
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Obtener dirección de correo para envío
        $admin_email = get_option('englishline_notification_email', get_option('admin_email'));
        
        // Añadir remitente
        $headers[] = 'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>';
        
        // Enviar correo
        $mail_sent = wp_mail($result_data->user_email, $subject, $content, $headers);
        
        return $mail_sent;
    }
    /**
     * Verifica si hay actualizaciones disponibles en GitHub
     */
    public function check_github_updates() {
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_check_updates')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }
    
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'No tienes permiso para verificar actualizaciones.'));
        }
    
        // URL del repositorio en GitHub y nombre del plugin
        $github_repo = 'kerackdiaz/EnglishLine-Placement'; // Reemplazar con el usuario/repo correcto
        $current_version = $this->version;
    
        // Obtener información de la última versión desde GitHub
        $response = wp_remote_get("https://api.github.com/repos/{$github_repo}/releases/latest");
    
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Error al conectar con GitHub.'));
            return;
        }
    
        // Obtener el cuerpo de la respuesta y decodificarlo
        $body = wp_remote_retrieve_body($response);
        $release_info = json_decode($body);
    
        // Si no hay información de release o no hay tag_name, hay un error
        if (!$release_info || !isset($release_info->tag_name)) {
            wp_send_json_error(array('message' => 'No se pudo obtener información de la última versión.'));
            return;
        }
    
        // Remover el 'v' inicial si existe (v1.0.0 -> 1.0.0)
        $latest_version = preg_replace('/^v/', '', $release_info->tag_name);
    
        // Comparar versiones
        $update_available = version_compare($latest_version, $current_version, '>');
    
        // Devolver el resultado
        wp_send_json_success(array(
            'current_version' => $current_version,
            'latest_version' => $latest_version,
            'update_available' => $update_available,
            'release_notes' => isset($release_info->body) ? $release_info->body : '',
            'download_url' => isset($release_info->zipball_url) ? $release_info->zipball_url : '',
        ));
    }

    /**
     * Actualiza el plugin desde GitHub
     */
    public function update_from_github() {
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_update_plugin')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }
    
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'No tienes permiso para actualizar el plugin.'));
        }
    
        // URL de descarga proporcionada
        $download_url = isset($_POST['download_url']) ? esc_url_raw($_POST['download_url']) : '';
        
        if (empty($download_url)) {
            wp_send_json_error(array('message' => 'URL de descarga no proporcionada.'));
            return;
        }
    
        // Necesitaremos estas clases para el proceso de actualización
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
        
        // Iniciar buffer para capturar cualquier salida
        ob_start();
        
        // Configurar el actualizador
        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        
        // Deshabilitar las redirecciones
        add_filter('redirect_to', '__return_false');
        
        // Intentar descargar e instalar
        $result = $upgrader->install($download_url, array(
            'overwrite_package' => true // Sobrescribir si ya existe
        ));
        
        // Capturar cualquier mensaje de error
        $error_messages = ob_get_clean();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => 'Error al actualizar: ' . $result->get_error_message(),
                'debug_info' => $error_messages
            ));
            return;
        }
        
        if (false === $result) {
            wp_send_json_error(array(
                'message' => 'La actualización falló por una razón desconocida.',
                'debug_info' => $error_messages
            ));
            return;
        }
    
        // Activar el plugin actualizado
        $plugin_file = 'englishline-test/englishline-test.php'; 
        $activate_result = activate_plugin($plugin_file);
        
        if (is_wp_error($activate_result)) {
            wp_send_json_error(array(
                'message' => 'Error al activar: ' . $activate_result->get_error_message(),
                'debug_info' => $error_messages
            ));
            return;
        }
    
        // Éxito
        wp_send_json_success(array(
            'message' => 'El plugin se ha actualizado y activado correctamente.',
            'debug_info' => $error_messages
        ));
    }

/**
 * Maneja la exportación de datos del plugin
 */
public function handle_export_data() {
    // Verificar el nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'englishline_export_data')) {
        wp_die('Error de seguridad. Por favor, intenta nuevamente.', 'Error', array('response' => 400));
    }

    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para realizar esta acción.', 'Error de permisos', array('response' => 403));
    }

    // Obtener todos los datos necesarios
    global $wpdb;
    $data = array(
        'plugin_name' => 'EnglishLine Placement',
        'version' => $this->version,
        'export_date' => current_time('mysql'),
        'settings' => get_option('englishline_test_settings', array()),
    );

    // Obtener formularios
    $forms_table = $wpdb->prefix . 'englishline_forms';
    if ($wpdb->get_var("SHOW TABLES LIKE '$forms_table'") == $forms_table) {
        $data['forms'] = $wpdb->get_results("SELECT * FROM $forms_table", ARRAY_A);
    }
    
    // Obtener resultados/calificaciones
    $results_table = $wpdb->prefix . 'englishline_results';
    if ($wpdb->get_var("SHOW TABLES LIKE '$results_table'") == $results_table) {
        $data['results'] = $wpdb->get_results("SELECT * FROM $results_table", ARRAY_A);
    }
    
    // Obtener envíos/submissions
    $submissions_table = $wpdb->prefix . 'englishline_submissions';
    if ($wpdb->get_var("SHOW TABLES LIKE '$submissions_table'") == $submissions_table) {
        $data['submissions'] = $wpdb->get_results("SELECT * FROM $submissions_table", ARRAY_A);
    }
    
    // Obtener plantillas de correo
    $email_templates_table = $wpdb->prefix . 'englishline_email_templates';
    if ($wpdb->get_var("SHOW TABLES LIKE '$email_templates_table'") == $email_templates_table) {
        $data['email_templates'] = $wpdb->get_results("SELECT * FROM $email_templates_table", ARRAY_A);
    }

    // Generar el nombre del archivo
    $filename = 'englishline-placement-export-' . date('Y-m-d') . '.json';

    // Configurar las cabeceras para descargar un archivo
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Enviar los datos como JSON
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

    /**
     * Exporta la configuración del plugin
     */
    public function export_settings() {
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_export_settings')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }
    
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'No tienes permiso para exportar la configuración.'));
        }
    
        // Obtener configuración
        $settings = get_option('englishline_test_settings', array());
        
        // Añadir información extra
        $export_data = array(
            'plugin_name' => 'EnglishLine Placement',
            'version' => $this->version,
            'export_date' => current_time('mysql'),
            'settings' => $settings
        );
        
        // Convertir a JSON y enviar
        wp_send_json_success(array(
            'settings_data' => json_encode($export_data),
            'filename' => 'englishline-test-settings-' . date('Y-m-d') . '.json'
        ));
    }

    /**
     * Importa la configuración y datos completos del plugin
     */
    public function import_settings() {
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_import_settings')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }
    
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'No tienes permiso para importar datos.'));
        }
    
        // Verificar que hay datos para importar
        if (!isset($_POST['settings_data']) || empty($_POST['settings_data'])) {
            wp_send_json_error(array('message' => 'No hay datos para importar.'));
            return;
        }
    
        // Decodificar datos
        $import_data = json_decode(stripslashes($_POST['settings_data']), true);
        
        // Verificar que los datos son válidos
        if (!$import_data) {
            wp_send_json_error(array('message' => 'Los datos de importación no son válidos o tienen formato incorrecto.'));
            return;
        }
        
        // Validar que pertenecen al plugin correcto
        if (!isset($import_data['plugin_name']) || $import_data['plugin_name'] !== 'EnglishLine Placement') {
            wp_send_json_error(array('message' => 'Los datos no pertenecen a EnglishLine Placement.'));
            return;
        }
        
        global $wpdb;
        $stats = array(
            'settings_imported' => false,
            'forms_imported' => 0,
            'forms_updated' => 0,
            'forms_skipped' => 0,
            'templates_imported' => 0,
            'results_imported' => 0,
            'submissions_imported' => 0
        );
        
        // Modo para manejar duplicados (skip, update, rename)
        $duplicate_mode = isset($_POST['duplicate_action']) ? sanitize_text_field($_POST['duplicate_action']) : 'skip';
        
        // 1. Importar configuración si existe
        if (isset($import_data['settings']) && is_array($import_data['settings'])) {
            update_option('englishline_test_settings', $import_data['settings']);
            $stats['settings_imported'] = true;
        }
        
        // 2. Importar formularios si existen
        if (isset($import_data['forms']) && is_array($import_data['forms'])) {
            $forms_table = $wpdb->prefix . 'englishline_forms';
            
            // Verificar si la tabla existe
            if ($wpdb->get_var("SHOW TABLES LIKE '$forms_table'") != $forms_table) {
                // La tabla no existe, informamos pero continuamos con otras importaciones
                $stats['forms_error'] = 'La tabla de formularios no existe';
            } else {
                // Obtener todos los shortcodes y títulos existentes para verificar duplicados
                $existing_shortcodes = $wpdb->get_col("SELECT shortcode FROM $forms_table");
                $existing_titles = $wpdb->get_col("SELECT title FROM $forms_table");
                
                foreach ($import_data['forms'] as $form) {
                    // Sanear datos críticos
                    $title = isset($form['title']) ? sanitize_text_field($form['title']) : '';
                    $description = isset($form['description']) ? sanitize_textarea_field($form['description']) : '';
                    $shortcode = isset($form['shortcode']) ? sanitize_text_field($form['shortcode']) : '';
                    $form_data = isset($form['form_data']) ? $form['form_data'] : '';
                    $form_style = isset($form['form_style']) ? $form['form_style'] : '';
                    
                    // Verificar si es un duplicado por shortcode o título
                    $is_duplicate_shortcode = in_array($shortcode, $existing_shortcodes);
                    $is_duplicate_title = in_array($title, $existing_titles);
                    
                    if ($is_duplicate_shortcode || $is_duplicate_title) {
                        // MODO ACTUALIZAR: Reemplazar el formulario existente
                        if ($duplicate_mode === 'update' && $is_duplicate_shortcode) {
                            $result = $wpdb->update(
                                $forms_table,
                                array(
                                    'title' => $title,
                                    'description' => $description,
                                    'form_data' => $form_data,
                                    'form_style' => $form_style,
                                    'updated_at' => current_time('mysql')
                                ),
                                array('shortcode' => $shortcode),
                                array('%s', '%s', '%s', '%s', '%s'),
                                array('%s')
                            );
                            
                            if ($result !== false) {
                                $stats['forms_updated']++;
                            }
                        }
                        // MODO RENOMBRAR: Crear copia con nuevo título y shortcode
                        else if ($duplicate_mode === 'rename') {
                            $new_shortcode = 'et_form_' . time() . '_' . mt_rand(1000, 9999);
                            $new_title = sprintf('Copia de %s', $title);
                            
                            $result = $wpdb->insert(
                                $forms_table,
                                array(
                                    'title' => $new_title,
                                    'description' => $description,
                                    'form_data' => $form_data,
                                    'form_style' => $form_style,
                                    'shortcode' => $new_shortcode,
                                    'created_at' => current_time('mysql'),
                                    'updated_at' => current_time('mysql')
                                ),
                                array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
                            );
                            
                            if ($result) {
                                $stats['forms_imported']++;
                                // Agregar a listas para evitar duplicados en siguientes iteraciones
                                $existing_shortcodes[] = $new_shortcode;
                                $existing_titles[] = $new_title;
                            }
                        }
                        // MODO SALTAR (default): No hacer nada con duplicados
                        else {
                            $stats['forms_skipped']++;
                        }
                    } 
                    // No es duplicado, importar directamente
                    else {
                        $result = $wpdb->insert(
                            $forms_table,
                            array(
                                'title' => $title,
                                'description' => $description,
                                'form_data' => $form_data,
                                'form_style' => $form_style,
                                'shortcode' => $shortcode,
                                'created_at' => isset($form['created_at']) ? $form['created_at'] : current_time('mysql'),
                                'updated_at' => current_time('mysql')
                            ),
                            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
                        );
                        
                        if ($result) {
                            $stats['forms_imported']++;
                            // Agregar a listas para evitar duplicados
                            $existing_shortcodes[] = $shortcode;
                            $existing_titles[] = $title;
                        }
                    }
                }
            }
        }
        
        // 3. Importar plantillas de correo si existen
        if (isset($import_data['email_templates']) && is_array($import_data['email_templates'])) {
            $templates_table = $wpdb->prefix . 'englishline_email_templates';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$templates_table'") != $templates_table) {
                $stats['templates_error'] = 'La tabla de plantillas no existe';
            } else {
                // Obtener nombres existentes para evitar duplicados
                $existing_names = $wpdb->get_col("SELECT name FROM $templates_table");
                
                foreach ($import_data['email_templates'] as $template) {
                    $name = isset($template['name']) ? sanitize_text_field($template['name']) : '';
                    $subject = isset($template['subject']) ? sanitize_text_field($template['subject']) : '';
                    $content = isset($template['content']) ? $template['content'] : '';
                    
                    // Verificar si ya existe
                    if (in_array($name, $existing_names)) {
                        if ($duplicate_mode === 'update') {
                            // Actualizar la plantilla existente
                            $result = $wpdb->update(
                                $templates_table,
                                array('subject' => $subject, 'content' => $content),
                                array('name' => $name),
                                array('%s', '%s'),
                                array('%s')
                            );
                        } else if ($duplicate_mode === 'rename') {
                            // Crear con nuevo nombre
                            $new_name = $name . '_' . date('Ymd');
                            $result = $wpdb->insert(
                                $templates_table,
                                array('name' => $new_name, 'subject' => $subject, 'content' => $content),
                                array('%s', '%s', '%s')
                            );
                            
                            if ($result) {
                                $stats['templates_imported']++;
                                $existing_names[] = $new_name;
                            }
                        }
                        // Si es 'skip', no hacemos nada
                    } else {
                        // No existe, importar directamente
                        $result = $wpdb->insert(
                            $templates_table,
                            array('name' => $name, 'subject' => $subject, 'content' => $content),
                            array('%s', '%s', '%s')
                        );
                        
                        if ($result) {
                            $stats['templates_imported']++;
                            $existing_names[] = $name;
                        }
                    }
                }
            }
        }
        
        // 4. Importar resultados si existen (opcional, generalmente no se importan resultados)
        if (isset($import_data['results']) && is_array($import_data['results']) && $duplicate_mode === 'full_import') {
            $results_table = $wpdb->prefix . 'englishline_results';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$results_table'") == $results_table) {
                foreach ($import_data['results'] as $result) {
                    // Omitir el ID para generar uno nuevo
                    unset($result['id']);
                    
                    $columns = array();
                    $formats = array();
                    
                    foreach ($result as $key => $value) {
                        $columns[$key] = $value;
                        $formats[] = is_numeric($value) ? '%d' : '%s';
                    }
                    
                    $result = $wpdb->insert($results_table, $columns, $formats);
                    
                    if ($result) {
                        $stats['results_imported']++;
                    }
                }
            }
        }
        
        // 5. Importar submissions si existen (opcional)
        if (isset($import_data['submissions']) && is_array($import_data['submissions']) && $duplicate_mode === 'full_import') {
            $submissions_table = $wpdb->prefix . 'englishline_submissions';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$submissions_table'") == $submissions_table) {
                foreach ($import_data['submissions'] as $submission) {
                    // Omitir el ID para generar uno nuevo
                    unset($submission['id']);
                    
                    $columns = array();
                    $formats = array();
                    
                    foreach ($submission as $key => $value) {
                        $columns[$key] = $value;
                        $formats[] = is_numeric($value) ? '%d' : '%s';
                    }
                    
                    $result = $wpdb->insert($submissions_table, $columns, $formats);
                    
                    if ($result) {
                        $stats['submissions_imported']++;
                    }
                }
            }
        }
        
        // Éxito con estadísticas
        wp_send_json_success(array(
            'message' => 'Importación completada correctamente.',
            'stats' => $stats,
            'imported_version' => isset($import_data['version']) ? $import_data['version'] : 'desconocida',
            'imported_date' => isset($import_data['export_date']) ? $import_data['export_date'] : 'desconocida'
        ));
    }

    
}