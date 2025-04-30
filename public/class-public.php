<?php
/**
 * La funcionalidad específica del área pública del plugin.
 */
class EnglishLine_Test_Public {

    private $plugin_name;
    private $version;

    /**
     * Inicializa la clase y establece sus propiedades.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Registrar acciones AJAX (único punto de entrada)
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
        
        // 1. Verificación de seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'englishline_test_public_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
            return;
        }
        
        // 2. Obtener y validar datos básicos
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $form_responses = isset($_POST['form_responses']) ? json_decode(stripslashes($_POST['form_responses']), true) : array();
        
        if ($form_id <= 0) {
            wp_send_json_error(array('message' => 'ID de formulario inválido.'));
            return;
        }
        
        // 3. Obtener datos de usuario
        $user_data = isset($form_responses['user_info']) ? $form_responses['user_info'] : array();
        $user_email = isset($user_data['email']) ? sanitize_email($user_data['email']) : '';
        $user_name = isset($user_data['first_name']) ? sanitize_text_field($user_data['first_name']) : '';
        
        if (empty($user_email)) {
            wp_send_json_error(array('message' => 'El correo electrónico es obligatorio.'));
            return;
        }
        
        // 4. Guardar en la base de datos
        global $wpdb;
        $results_table = $wpdb->prefix . 'englishline_results';
        
        // Verificar si existe la tabla
        if ($wpdb->get_var("SHOW TABLES LIKE '$results_table'") != $results_table) {
            wp_send_json_error(array('message' => 'Error en la base de datos. Por favor, contacta al administrador.'));
            return;
        }
        
        // Estructurar los datos correctamente
        $serialized_data = json_encode($form_responses);
        
        // Insertar registro con estado pendiente
        $result = $wpdb->insert(
            $results_table,
            array(
                'form_id' => $form_id,
                'user_id' => get_current_user_id(),
                'user_email' => $user_email,
                'form_data' => $serialized_data,
                'score' => 0,
                'level' => '',
                'feedback' => null,
                'individual_scores' => null,
                'status' => 'pending',
                'reviewer_id' => null,
                'reviewed_at' => null,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Error al guardar el resultado.'));
            return;
        }
        
        $result_id = $wpdb->insert_id;
        
        // 5. Obtener y preparar el formulario original para calificar
        $forms_table = $wpdb->prefix . 'englishline_forms';
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $forms_table WHERE id = %d",
            $form_id
        ));
        
        if (!$form) {
            wp_send_json_error(array('message' => 'Error al recuperar el formulario original.'));
            return;
        }
        
        // 6. Calificar automáticamente
        $score = 0;
        $level = '';
        $status = 'pending';
        $individual_scores = null;
        
        try {
            $graded_result = $this->auto_grade_test($result_id, $form_responses, $form);
            
            $score = $graded_result['totalPercentage'];
            $level = $graded_result['level'];
            $status = $graded_result['status'];
            $individual_scores = json_encode($graded_result['individual_scores'] ?? []);
            
            // Actualizar el registro con los resultados
            $wpdb->update(
                $results_table,
                array(
                    'score' => $score,
                    'level' => $level,
                    'status' => $status,
                    'individual_scores' => $individual_scores,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $result_id),
                array('%f', '%s', '%s', '%s', '%s'),
                array('%d')
            );
        } catch (Exception $e) {
            error_log('ENGLISHLINE - Error en calificación: ' . $e->getMessage());
            $status = 'pending';
        }
        
        // 7. Enviar notificaciones por correo
        $email_sent = false;
        try {
            // Enviar notificación al administrador
            $admin_notification = $this->send_admin_notification($form, $user_email, $result_id);
            
            // Enviar confirmación al usuario
            $user_confirmation = $this->send_user_confirmation($user_email);
            
            $email_sent = ($admin_notification && $user_confirmation);
        } catch (Exception $e) {
            error_log('ENGLISHLINE - Error al enviar correo: ' . $e->getMessage());
            $email_sent = false;
        }
        
        // 8. Enviar respuesta de éxito
        wp_send_json_success(array(
            'message' => '¡Gracias! Tu prueba ha sido enviada correctamente.',
            'result_id' => $result_id,
            'score' => $score,
            'level' => $level,
            'status' => $status,
            'email_sent' => $email_sent
        ));
    }

    /**
     * Califica automáticamente las respuestas del formulario
     * 
     * @param int $result_id ID del resultado en wp_englishline_results
     * @param array $form_responses Respuestas del usuario (decodificadas de JSON)
     * @param object $form Datos del formulario desde wp_englishline_forms
     * @return array Resultado con totalPercentage, level, status, individual_scores
     */
    private function auto_grade_test($result_id, $form_responses, $form) {
        global $wpdb;
        
        try {
            // 1. Obtener respuestas correctas del formulario
            $form_content = json_decode($form->form_data, true);
            if (!$form_content || !is_array($form_content)) {
                throw new Exception('Formato de respuestas correctas inválido.');
            }
            
            // Indexar respuestas correctas por ID
            $correct_answers = [];
            foreach ($form_content as $section) {
                foreach ($section['questions'] as $question) {
                    if (!isset($question['isGradable']) || !$question['isGradable']) {
                        continue;
                    }
                    $question_id = $question['id'] ?? '';
                    if (empty($question_id)) {
                        error_log('ENGLISHLINE - Pregunta sin ID en forms.form_data: ' . json_encode($question));
                        continue;
                    }
                    $correct_answers[$question_id] = [
                        'type' => $question['type'],
                        'correct_answer' => $this->get_correct_answer($question),
                        'case_sensitive' => $question['caseSensitive'] ?? false
                    ];
                }
            }
            
            // 2. Procesar respuestas del usuario
            $total_questions = 0;
            $correct_count = 0;
            $individual_scores = [];
            
            // Inicializar contadores por sección
            foreach ($form_responses['sections'] as $section) {
                $section_title = $section['title'];
                $individual_scores[$section_title] = [
                    'percentage' => 0,
                    'correct' => 0,
                    'total' => 0
                ];
            }
            
            foreach ($form_responses['sections'] as $section) {
                $section_title = $section['title'];
                foreach ($section['questions'] as $question) {
                    if (!$question['isGradable']) {
                        continue; // Ignorar preguntas no calificables
                    }
                    
                    $question_id = $question['id'];
                    if (empty($question_id)) {
                        error_log('ENGLISHLINE - Pregunta sin ID en results.form_data: ' . json_encode($question));
                        continue;
                    }
                    
                    $user_answer = $question['answer'];
                    $question_type = $question['type'];
                    
                    if (!isset($correct_answers[$question_id])) {
                        error_log('ENGLISHLINE - Pregunta no encontrada en correct_answers: ID ' . $question_id);
                        continue;
                    }
                    
                    $correct_answer = $correct_answers[$question_id]['correct_answer'];
                    $case_sensitive = $correct_answers[$question_id]['case_sensitive'];
                    $total_questions++;
                    $individual_scores[$section_title]['total']++;
                    
                    // 3. Comparar respuestas según el tipo
                    $is_correct = $this->compare_answers($question_type, $user_answer, $correct_answer, $case_sensitive);
                    
                    // Log para depuración
                    error_log('ENGLISHLINE - Pregunta ' . $question_id . ' (Sección: ' . $section_title . '): ' . ($is_correct ? 'Correcta' : 'Incorrecta'));
                    
                    if ($is_correct) {
                        $correct_count++;
                        $individual_scores[$section_title]['correct']++;
                    }
                }
                
                // Calcular porcentaje por sección
                $section_total = $individual_scores[$section_title]['total'];
                $section_correct = $individual_scores[$section_title]['correct'];
                $individual_scores[$section_title]['percentage'] = $section_total > 0 
                    ? round(($section_correct / $section_total) * 100, 2) 
                    : 0;
            }
            
            // 4. Calcular puntuación total
            $total_percentage = $total_questions > 0 
                ? ($correct_count / $total_questions) * 100 
                : 0;
            
            // 5. Asignar nivel
            $level = $this->assign_level($total_percentage);
            
            // 6. Devolver resultado
            return [
                'totalPercentage' => round($total_percentage, 2),
                'level' => $level,
                'status' => $total_percentage >= 60 ? 'approved' : 'failed',
                'individual_scores' => $individual_scores,
                'correct_count' => $correct_count,
                'total_questions' => $total_questions
            ];
            
        } catch (Exception $e) {
            error_log('ENGLISHLINE - Error en autocalificación: ' . $e->getMessage());
            return [
                'totalPercentage' => 0,
                'level' => '',
                'status' => 'pending',
                'individual_scores' => [],
                'correct_count' => 0,
                'total_questions' => 0
            ];
        }
    }

    /**
     * Extrae la respuesta correcta según el tipo de pregunta
     * 
     * @param array $question Datos de la pregunta
     * @return mixed Respuesta correcta
     */
    private function get_correct_answer($question) {
        switch ($question['type']) {
            case 'text':
            case 'textarea':
                return $question['correctAnswer'];
                
            case 'select':
            case 'radio':
                return (string) $question['correctOption'];
                
            case 'checkbox':
                return array_map('strval', $question['correctOptions']);
                
            case 'cloze':
                return $question['correctFills'];
                
            case 'ordering':
                return array_map('strval', $question['correctOrder']);
                
            case 'true-false':
                return $question['correctValue'] ? 'Verdadero' : 'Falso';
                
            default:
                return null;
        }
    }

    /**
     * Compara la respuesta del usuario con la correcta
     * 
     * @param string $type Tipo de pregunta
     * @param mixed $user_answer Respuesta del usuario
     * @param mixed $correct_answer Respuesta correcta
     * @param bool $case_sensitive Sensibilidad a mayúsculas
     * @return bool Si la respuesta es correcta
     */
    private function compare_answers($type, $user_answer, $correct_answer, $case_sensitive) {
        switch ($type) {
            case 'text':
            case 'textarea':
                if ($case_sensitive) {
                    return $user_answer === $correct_answer;
                }
                return strtolower($user_answer) === strtolower($correct_answer);
                
            case 'select':
            case 'radio':
                return is_array($user_answer) && $user_answer['value'] === $correct_answer;
                
            case 'checkbox':
                if (!is_array($user_answer) || !is_array($correct_answer)) {
                    return false;
                }
                $user_values = array_column($user_answer, 'value');
                sort($user_values);
                sort($correct_answer);
                return $user_values === $correct_answer;
                
            case 'cloze':
                if (!is_array($user_answer) || !is_array($correct_answer)) {
                    return false;
                }
                if ($case_sensitive) {
                    return $user_answer === $correct_answer;
                }
                return array_map('strtolower', $user_answer) === array_map('strtolower', $correct_answer);
                
            case 'ordering':
                if (!is_array($user_answer) || !is_array($correct_answer)) {
                    return false;
                }
                $user_values = array_column($user_answer, 'value');
                return $user_values === $correct_answer;
                
            case 'true-false':
                return $user_answer === $correct_answer;
                
            default:
                return false;
        }
    }

    /**
     * Asigna un nivel basado en el porcentaje
     * 
     * @param float $percentage Porcentaje obtenido
     * @return string Nivel CEFR
     */
    private function assign_level($percentage) {
        if ($percentage >= 90) return 'C2';
        if ($percentage >= 75) return 'C1';
        if ($percentage >= 60) return 'B2';
        if ($percentage >= 45) return 'B1';
        if ($percentage >= 30) return 'A2';
        if ($percentage >= 15) return 'A1';
        return 'Beginner';
    }

        
    /**
     * Envía notificación al administrador con los resultados del formulario
     *
     * @param object $form Datos del formulario desde wp_englishline_forms
     * @param string $user_email Correo del usuario
     * @param int $result_id ID del resultado en wp_englishline_results
     * @return bool Si el correo se envió correctamente
     */
    public function send_admin_notification($form, $user_email, $result_id) {
        try {
            // Cargar Email_Helper
            $helper_path = plugin_dir_path(dirname(__FILE__)) . 'includes/helpers/class-email-helper.php';
            if (!file_exists($helper_path)) {
                throw new Exception('Archivo Email_Helper no encontrado.');
            }
            require_once $helper_path;
            
            if (!class_exists('Email_Helper')) {
                throw new Exception('Clase Email_Helper no definida.');
            }
            
            // Obtener datos del resultado
            global $wpdb;
            $results_table = $wpdb->prefix . 'englishline_results';
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $results_table WHERE id = %d",
                $result_id
            ));
            
            if (!$result) {
                throw new Exception('Resultado no encontrado.');
            }
            
            $form_responses = json_decode($result->form_data, true);
            $form_responses['form_title'] = $form->title;
            $form_responses['correct_count'] = 0;
            $form_responses['total_questions'] = 0;
            
            // Calcular correctas/totales
            $individual_scores = json_decode($result->individual_scores, true) ?: [];
            foreach ($individual_scores as $section_score) {
                $form_responses['correct_count'] += $section_score['correct'];
                $form_responses['total_questions'] += $section_score['total'];
            }
            
            // Enviar correo usando Email_Helper
            $email_helper = new Email_Helper();
            $sent = $email_helper->send_form_responses($form->id, $form_responses, $result_id);
            
            if (!$sent) {
                throw new Exception('Error al enviar el correo al administrador.');
            }
            
            return true;
        } catch (Exception $e) {
            error_log('ENGLISHLINE - Error en send_admin_notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía un correo de confirmación al usuario
     */
    private function send_user_confirmation($user_email) {
        // Obtener la plantilla de correo
        global $wpdb;
        $templates_table = $wpdb->prefix . 'englishline_email_templates';
        
        $template = null;
        $table_check = $wpdb->get_var("SHOW TABLES LIKE '$templates_table'");
        
        if ($table_check) {
            // Obtener la plantilla de confirmación 
            $template = $wpdb->get_row("SELECT * FROM $templates_table 
                                       WHERE name = 'confirmation_template' LIMIT 1");
        }
        
        if ($template) {
            $subject = $template->subject;
            $content = $template->content;
        } else {
            // Usar una plantilla predeterminada si no existe
            $subject = 'Confirmación de recepción de tu prueba de inglés';
            $content = 'Hola,<br><br>Gracias por completar nuestra prueba de nivel de inglés. 
                      Hemos recibido tu envío correctamente.<br><br>Nuestro equipo revisará 
                      tus respuestas y te enviaremos los resultados en breve.<br><br>
                      Saludos,<br>El equipo de EnglishLine';
        }
        
        // Configurar encabezados para correo HTML
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Obtener dirección de correo para envío
        $admin_email = get_option('englishline_notification_email', get_option('admin_email'));
        
        // Añadir remitente
        $headers[] = 'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>';
        
        // Enviar correo
        return wp_mail($user_email, $subject, $content, $headers);
    }
}
