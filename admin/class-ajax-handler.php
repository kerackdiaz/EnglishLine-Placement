<?php

class EnglishLine_Test_Ajax_Handler
{
    private function clear_directory($dir)
    {
        global $wp_filesystem;

        if (!$wp_filesystem->is_dir($dir) || $dir == ABSPATH || $dir == WP_CONTENT_DIR || $dir == WP_PLUGIN_DIR) {
            return false;
        }

        $files = $wp_filesystem->dirlist($dir);
        if ($files === false) {
            return false;
        }

        foreach ($files as $file => $fileinfo) {
            $path = $dir . '/' . $file;

            if ($fileinfo['type'] === 'd') {
                $this->clear_directory($path);
                $wp_filesystem->rmdir($path);
            } else {
                $wp_filesystem->delete($path);
            }
        }

        return true;
    }

    private function copy_directory($src, $dst)
    {
        global $wp_filesystem;

        if (!$wp_filesystem->is_dir($src)) {
            return false;
        }

        if (!$wp_filesystem->is_dir($dst)) {
            if (!$wp_filesystem->mkdir($dst, 0755)) {
                return false;
            }
        }

        $files = $wp_filesystem->dirlist($src);
        if ($files === false) {
            return false;
        }

        foreach ($files as $file => $fileinfo) {
            $src_file = $src . '/' . $file;
            $dst_file = $dst . '/' . $file;

            if ($fileinfo['type'] === 'd') {
                $this->copy_directory($src_file, $dst_file);
            } else {
                $wp_filesystem->copy($src_file, $dst_file);
            }
        }

        return true;
    }

    private function remove_directory($dir)
    {
        global $wp_filesystem;

        if (!$wp_filesystem->is_dir($dir)) {
            return;
        }

        $this->clear_directory($dir);
        $wp_filesystem->rmdir($dir);
    }

    private $plugin_name;
    private $version;

    public function __construct($plugin_name = '', $version = '')
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_post_englishline_export_data', array($this, 'handle_export_data'));
    }

    public function handle_form_submit() 
    {
    }

    public function save_form()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_test_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        if (!current_user_can('manage_options') && !current_user_can('englishline_manager')) {
            wp_send_json_error(array('message' => 'No tienes permiso para realizar esta acción.'));
        }

        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : '';
        $form_style = isset($_POST['form_style']) ? json_encode($_POST['form_style']) : '';
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;

        if (empty($title)) {
            wp_send_json_error(array('message' => 'El título es obligatorio.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'englishline_forms';

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
        } else {
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

        wp_send_json_error(array('message' => 'Ocurrió un error inesperado.'));
    }

    public function delete_form()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_delete_form_' . $_POST['form_id'])) {
            wp_send_json_error(array('message' => __('Error de seguridad', 'englishline-test')));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso para realizar esta acción', 'englishline-test')));
        }

        $form_id = intval($_POST['form_id']);
        if ($form_id <= 0) {
            wp_send_json_error(array('message' => __('ID de formulario inválido', 'englishline-test')));
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $submissions_table = $wpdb->prefix . 'englishline_submissions';
            $wpdb->delete($submissions_table, array('form_id' => $form_id), array('%d'));

            $forms_table = $wpdb->prefix . 'englishline_forms';
            $result = $wpdb->delete($forms_table, array('id' => $form_id), array('%d'));

            if ($result === false) {
                $wpdb->query('ROLLBACK');
                wp_send_json_error(array('message' => __('Error al eliminar el formulario', 'englishline-test')));
                return;
            }

            $wpdb->query('COMMIT');
            wp_send_json_success(array('message' => __('Formulario eliminado correctamente', 'englishline-test')));
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => __('Error al eliminar el formulario', 'englishline-test')));
        }
    }

    public function duplicate_form()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_duplicate_form_' . $_POST['form_id'])) {
            wp_send_json_error(array('message' => __('Error de seguridad', 'englishline-test')));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso para realizar esta acción', 'englishline-test')));
        }

        $form_id = intval($_POST['form_id']);
        if ($form_id <= 0) {
            wp_send_json_error(array('message' => __('ID de formulario inválido', 'englishline-test')));
        }

        global $wpdb;
        $forms_table = $wpdb->prefix . 'englishline_forms';

        $original_form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$forms_table} WHERE id = %d",
            $form_id
        ), ARRAY_A);

        if (!$original_form) {
            wp_send_json_error(array('message' => __('Formulario no encontrado', 'englishline-test')));
            return;
        }

        $new_title = sprintf(__('Copia de %s', 'englishline-test'), $original_form['title']);

        $result = $wpdb->insert(
            $forms_table,
            array(
                'title' => $new_title,
                'description' => $original_form['description'],
                'settings' => $original_form['settings'],
                'fields' => $original_form['fields'],
                'status' => 'draft', 
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

    public function toggle_form_status()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_toggle_form_status_' . $_POST['form_id'])) {
            wp_send_json_error(array('message' => __('Error de seguridad', 'englishline-test')));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso para realizar esta acción', 'englishline-test')));
        }

        $form_id = intval($_POST['form_id']);
        $new_status = sanitize_text_field($_POST['new_status']);

        if ($form_id <= 0) {
            wp_send_json_error(array('message' => __('ID de formulario inválido', 'englishline-test')));
        }

        if (!in_array($new_status, array('published', 'draft', 'archived'))) {
            wp_send_json_error(array('message' => __('Estado no válido', 'englishline-test')));
        }

        global $wpdb;
        $forms_table = $wpdb->prefix . 'englishline_forms';

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

    public function save_email_template()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_test_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        if (!current_user_can('manage_options') && !current_user_can('englishline_manager')) {
            wp_send_json_error(array('message' => 'No tienes permiso para realizar esta acción.'));
        }

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;

        if (empty($name) || empty($subject) || empty($content)) {
            wp_send_json_error(array('message' => 'Todos los campos son obligatorios.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'englishline_email_templates';

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
        } else {
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

        wp_send_json_error(array('message' => 'Ocurrió un error inesperado.'));
    }

    public function save_result()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_test_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        if (!current_user_can('manage_options') && !current_user_can('englishline_manager')) {
            wp_send_json_error(array('message' => 'No tienes permiso para realizar esta acción.'));
        }

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
            if ($status === 'graded') {
                $result_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d",
                    $result_id
                ));

                if ($result_data && !empty($result_data->user_email)) {
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

    public function handle_result_actions()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'englishline-test-results') {
            return;
        }

        if (!isset($_GET['action'])) {
            return;
        }

        $action = sanitize_text_field($_GET['action']);
        $result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

        if ($action === 'delete' && $result_id > 0) {
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_result_' . $result_id)) {
                global $wpdb;
                $results_table = $wpdb->prefix . 'englishline_results';

                $deleted = $wpdb->delete(
                    $results_table,
                    ['id' => $result_id],
                    ['%d']
                );

                wp_redirect(admin_url('admin.php?page=englishline-test-results&status=' . ($deleted ? 'deleted' : 'error')));
                exit;
            }
        }
    }

    private function send_result_email($result_data, $score, $feedback)
    {
        global $wpdb;
        $templates_table = $wpdb->prefix . 'englishline_email_templates';

        $template = $wpdb->get_row("SELECT * FROM $templates_table WHERE name = 'results_template' LIMIT 1");

        if (!$template) {
            $subject = 'Resultados de tu prueba de inglés';
            $content = 'Hola,<br><br>Gracias por completar nuestra prueba de nivel de inglés.<br><br>Tu puntuación es: {score}/100<br><br>Comentarios: {feedback}<br><br>Saludos,<br>El equipo de EnglishLine';
        } else {
            $subject = $template->subject;
            $content = $template->content;
        }

        $subject = str_replace('{score}', $score, $subject);
        $content = str_replace('{score}', $score, $content);
        $content = str_replace('{feedback}', nl2br($feedback), $content);

        $headers = array('Content-Type: text/html; charset=UTF-8');

        $admin_email = get_option('englishline_notification_email', get_option('admin_email'));

        $headers[] = 'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>';

        $mail_sent = wp_mail($result_data->user_email, $subject, $content, $headers);

        return $mail_sent;
    }

    public function check_github_updates()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_check_updates')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'No tienes permiso para verificar actualizaciones.'));
        }

        $github_repo = 'kerackdiaz/EnglishLine-Placement';
        $current_version = $this->version;

        if (empty($current_version)) {
            wp_send_json_error(array('message' => 'La versión actual del plugin no está definida.'));
            return;
        }

        $github_token = ''; // Define tu token de acceso personal aquí
        $headers = array();
        if (!empty($github_token)) {
            $headers['Authorization'] = 'token ' . $github_token;
        }

        $response = wp_remote_get(
            "https://api.github.com/repos/{$github_repo}/releases/latest",
            array(
                'headers' => $headers,
                'timeout' => 15
            )
        );

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Error al conectar con GitHub: ' . $response->get_error_message()
            ));
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            wp_send_json_error(array(
                'message' => 'Error al obtener la última versión de GitHub. Código de respuesta: ' . $response_code
            ));
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $release_info = json_decode($body);

        if (!$release_info || !isset($release_info->tag_name)) {
            wp_send_json_error(array(
                'message' => 'No se pudo obtener información de la última versión. Respuesta inválida de GitHub.'
            ));
            return;
        }

        $latest_version = preg_replace('/^[vV]/', '', $release_info->tag_name);
        $update_available = version_compare($latest_version, $current_version, '>');

        wp_send_json_success(array(
            'current_version' => $current_version,
            'latest_version' => $latest_version,
            'update_available' => $update_available,
            'release_notes' => isset($release_info->body) ? $release_info->body : '',
            'download_url' => isset($release_info->zipball_url) ? $release_info->zipball_url : '',
        ));
    }

    public function update_from_github()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_update_plugin')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'No tienes permiso para actualizar el plugin.'));
        }

        $download_url = isset($_POST['download_url']) ? esc_url_raw($_POST['download_url']) : '';

        if (empty($download_url)) {
            wp_send_json_error(array('message' => 'URL de descarga no proporcionada.'));
            return;
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/misc.php');

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            WP_Filesystem();
        }

        if (!$wp_filesystem->is_writable(WP_PLUGIN_DIR)) {
            wp_send_json_error(array('message' => 'El directorio de plugins no es escribible. Verifica los permisos.'));
            return;
        }

        set_time_limit(300);
        @ini_set('memory_limit', '256M');

        $free_space = disk_free_space(WP_CONTENT_DIR);
        if ($free_space === false || $free_space < 50 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'Espacio en disco insuficiente para realizar la actualización.'));
            return;
        }

        ob_start();

        $plugin_slug = 'englishline-test';
        $plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
        $plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;

        try {
            $was_active = is_plugin_active($plugin_file);
            if ($was_active) {
                deactivate_plugins($plugin_file);
            }

            // Descargar el archivo
            $temp_file = download_url($download_url, 300);
            if (is_wp_error($temp_file)) {
                throw new Exception('Error al descargar el archivo: ' . $temp_file->get_error_message());
            }

            // Crear directorio temporal
            $temp_dir = WP_CONTENT_DIR . '/upgrade/englishline-temp-' . time();
            if (!$wp_filesystem->mkdir($temp_dir, 0755)) {
                throw new Exception('No se pudo crear el directorio temporal: ' . $temp_dir);
            }

            // Descomprimir el archivo
            $unzipped = unzip_file($temp_file, $temp_dir);
            $wp_filesystem->delete($temp_file);

            if (is_wp_error($unzipped)) {
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    if ($zip->open($temp_file) === true) {
                        $zip->extractTo($temp_dir);
                        $zip->close();
                    } else {
                        throw new Exception('Error al extraer el archivo (ZipArchive): No se pudo abrir el archivo ZIP.');
                    }
                } else {
                    throw new Exception('Error al extraer el archivo: ' . $unzipped->get_error_message());
                }
            }

            // Buscar el directorio extraído
            $extracted_dir = '';
            $items = $wp_filesystem->dirlist($temp_dir);
            if ($items) {
                foreach ($items as $item => $data) {
                    if ($data['type'] === 'd' && strpos($item, 'EnglishLine-Placement') !== false) {
                        $extracted_dir = $temp_dir . '/' . $item;
                        break;
                    }
                }
            }

            if (empty($extracted_dir) || !$wp_filesystem->exists($extracted_dir)) {
                throw new Exception('No se pudo encontrar el directorio extraído en: ' . $temp_dir);
            }

            // Limpiar el directorio del plugin existente
            if ($wp_filesystem->exists($plugin_dir)) {
                if (!$this->clear_directory($plugin_dir)) {
                    throw new Exception('No se pudo limpiar el directorio del plugin existente: ' . $plugin_dir);
                }
            }
            if (!$wp_filesystem->mkdir($plugin_dir, 0755)) {
                throw new Exception('No se pudo crear el directorio del plugin: ' . $plugin_dir);
            }

            // Copiar los archivos del directorio extraído directamente al directorio del plugin
            $files = $wp_filesystem->dirlist($extracted_dir);
            if ($files === false) {
                throw new Exception('No se pudo leer el contenido del directorio extraído: ' . $extracted_dir);
            }

            foreach ($files as $file => $fileinfo) {
                $src_file = $extracted_dir . '/' . $file;
                $dst_file = $plugin_dir . '/' . $file;

                if ($fileinfo['type'] === 'd') {
                    $this->copy_directory($src_file, $dst_file);
                } else {
                    $wp_filesystem->copy($src_file, $dst_file);
                }
            }

            // Limpiar el directorio temporal
            $this->remove_directory($temp_dir);

            // Verificar que el archivo principal del plugin exista
            if (!$wp_filesystem->exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
                throw new Exception('El archivo principal del plugin no se encontró después de la actualización: ' . $plugin_file);
            }

            // Reactivar el plugin si estaba activo
            if ($was_active) {
                $activate_result = activate_plugin($plugin_file);
                if (is_wp_error($activate_result)) {
                    throw new Exception('Plugin actualizado pero error al reactivar: ' . $activate_result->get_error_message());
                }
            }

            $debug_info = ob_get_clean();

            wp_send_json_success(array(
                'message' => 'El plugin se ha actualizado correctamente' . ($was_active ? ' y reactivado' : ''),
                'debug_info' => $debug_info
            ));
        } catch (Exception $e) {
            $debug_info = ob_get_clean();

            // Limpiar el directorio temporal en caso de error
            if (isset($temp_dir) && $wp_filesystem->exists($temp_dir)) {
                $this->remove_directory($temp_dir);
            }

            // Reactivar el plugin si estaba activo y aún existe
            if (isset($was_active) && $was_active && $wp_filesystem->exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
                activate_plugin($plugin_file);
            }

            wp_send_json_error(array(
                'message' => 'Error: ' . $e->getMessage(),
                'debug_info' => $debug_info
            ));
        }
    }

    public function handle_export_data()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'englishline_export_data')) {
            wp_die('Error de seguridad. Por favor, intenta nuevamente.', 'Error', array('response' => 400));
        }

        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.', 'Error de permisos', array('response' => 403));
        }

        global $wpdb;
        $data = array(
            'plugin_name' => 'EnglishLine Placement',
            'version' => $this->version,
            'export_date' => current_time('mysql'),
            'settings' => get_option('englishline_test_settings', array()),
        );

        $forms_table = $wpdb->prefix . 'englishline_forms';
        if ($wpdb->get_var("SHOW TABLES LIKE '$forms_table'") == $forms_table) {
            $data['forms'] = $wpdb->get_results("SELECT * FROM $forms_table", ARRAY_A);
        }

        $results_table = $wpdb->prefix . 'englishline_results';
        if ($wpdb->get_var("SHOW TABLES LIKE '$results_table'") == $results_table) {
            $data['results'] = $wpdb->get_results("SELECT * FROM $results_table", ARRAY_A);
        }

        $submissions_table = $wpdb->prefix . 'englishline_submissions';
        if ($wpdb->get_var("SHOW TABLES LIKE '$submissions_table'") == $submissions_table) {
            $data['submissions'] = $wpdb->get_results("SELECT * FROM $submissions_table", ARRAY_A);
        }

        $email_templates_table = $wpdb->prefix . 'englishline_email_templates';
        if ($wpdb->get_var("SHOW TABLES LIKE '$email_templates_table'") == $email_templates_table) {
            $data['email_templates'] = $wpdb->get_results("SELECT * FROM $email_templates_table", ARRAY_A);
        }

        $filename = 'englishline-placement-export-' . date('Y-m-d') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    public function export_settings()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_export_settings')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'No tienes permiso para exportar la configuración.'));
        }

        $settings = get_option('englishline_test_settings', array());

        $export_data = array(
            'plugin_name' => 'EnglishLine Placement',
            'version' => $this->version,
            'export_date' => current_time('mysql'),
            'settings' => $settings
        );

        wp_send_json_success(array(
            'settings_data' => json_encode($export_data),
            'filename' => 'englishline-test-settings-' . date('Y-m-d') . '.json'
        ));
    }

    public function import_settings()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_import_settings')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'No tienes permiso para importar datos.'));
        }

        if (!isset($_POST['settings_data']) || empty($_POST['settings_data'])) {
            wp_send_json_error(array('message' => 'No hay datos para importar.'));
            return;
        }

        $import_data = json_decode(stripslashes($_POST['settings_data']), true);

        if (!$import_data) {
            wp_send_json_error(array('message' => 'Los datos de importación no son válidos o tienen formato incorrecto.'));
            return;
        }

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

        $duplicate_mode = isset($_POST['duplicate_action']) ? sanitize_text_field($_POST['duplicate_action']) : 'skip';

        if (isset($import_data['settings']) && is_array($import_data['settings'])) {
            update_option('englishline_test_settings', $import_data['settings']);
            $stats['settings_imported'] = true;
        }

        if (isset($import_data['forms']) && is_array($import_data['forms'])) {
            $forms_table = $wpdb->prefix . 'englishline_forms';

            if ($wpdb->get_var("SHOW TABLES LIKE '$forms_table'") != $forms_table) {
                $stats['forms_error'] = 'La tabla de formularios no existe';
            } else {
                $existing_shortcodes = $wpdb->get_col("SELECT shortcode FROM $forms_table");
                $existing_titles = $wpdb->get_col("SELECT title FROM $forms_table");

                foreach ($import_data['forms'] as $form) {
                    $title = isset($form['title']) ? sanitize_text_field($form['title']) : '';
                    $description = isset($form['description']) ? sanitize_textarea_field($form['description']) : '';
                    $shortcode = isset($form['shortcode']) ? sanitize_text_field($form['shortcode']) : '';
                    $form_data = isset($form['form_data']) ? $form['form_data'] : '';
                    $form_style = isset($form['form_style']) ? $form['form_style'] : '';

                    $is_duplicate_shortcode = in_array($shortcode, $existing_shortcodes);
                    $is_duplicate_title = in_array($title, $existing_titles);

                    if ($is_duplicate_shortcode || $is_duplicate_title) {
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
                                $existing_shortcodes[] = $new_shortcode;
                                $existing_titles[] = $new_title;
                            }
                        }
                        else {
                            $stats['forms_skipped']++;
                        }
                    }
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
                            $existing_shortcodes[] = $shortcode;
                            $existing_titles[] = $title;
                        }
                    }
                }
            }
        }

        if (isset($import_data['email_templates']) && is_array($import_data['email_templates'])) {
            $templates_table = $wpdb->prefix . 'englishline_email_templates';

            if ($wpdb->get_var("SHOW TABLES LIKE '$templates_table'") != $templates_table) {
                $stats['templates_error'] = 'La tabla de plantillas no existe';
            } else {
                $existing_names = $wpdb->get_col("SELECT name FROM $templates_table");

                foreach ($import_data['email_templates'] as $template) {
                    $name = isset($template['name']) ? sanitize_text_field($template['name']) : '';
                    $subject = isset($template['subject']) ? sanitize_text_field($template['subject']) : '';
                    $content = isset($template['content']) ? $template['content'] : '';

                    if (in_array($name, $existing_names)) {
                        if ($duplicate_mode === 'update') {
                            $result = $wpdb->update(
                                $templates_table,
                                array('subject' => $subject, 'content' => $content),
                                array('name' => $name),
                                array('%s', '%s'),
                                array('%s')
                            );
                        } else if ($duplicate_mode === 'rename') {
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
                    } else {
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

        if (isset($import_data['results']) && is_array($import_data['results']) && $duplicate_mode === 'full_import') {
            $results_table = $wpdb->prefix . 'englishline_results';

            if ($wpdb->get_var("SHOW TABLES LIKE '$results_table'") == $results_table) {
                foreach ($import_data['results'] as $result) {
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

        if (isset($import_data['submissions']) && is_array($import_data['submissions']) && $duplicate_mode === 'full_import') {
            $submissions_table = $wpdb->prefix . 'englishline_submissions';

            if ($wpdb->get_var("SHOW TABLES LIKE '$submissions_table'") == $submissions_table) {
                foreach ($import_data['submissions'] as $submission) {
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

        wp_send_json_success(array(
            'message' => 'Importación completada correctamente.',
            'stats' => $stats,
            'imported_version' => isset($import_data['version']) ? $import_data['version'] : 'desconocida',
            'imported_date' => isset($import_data['export_date']) ? $import_data['export_date'] : 'desconocida'
        ));
    }
}