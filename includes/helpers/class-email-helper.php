<?php

if (!defined('ABSPATH')) {
    exit;
}

class Email_Helper {
    
    private function get_result($result_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}englishline_results WHERE id = %d",
            $result_id
        ));
    }
    
    private function get_form($form_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}englishline_forms WHERE id = %d",
            $form_id
        ));
    }
    
    public function send_grade_notification($result_id, $score, $feedback) {
        $result = $this->get_result($result_id);
        if (!$result) {
            return false;
        }
    
        $form_data = json_decode($result->form_data, true);
        $user_data = isset($form_data['user_info']) ? $form_data['user_info'] : [];
        
        $user_email = $result->user_email;
        if (empty($user_email) && isset($user_data['email'])) {
            $user_email = $user_data['email'];
        }
        
        if (empty($user_email)) {
            error_log('EnglishLine Test: No se pudo enviar email - dirección de correo no encontrada para el resultado ID ' . $result_id);
            return false;
        }
        
        $user_name = '';
        if (!empty($user_data['first_name'])) {
            $user_name = $user_data['first_name'] . ' ' . ($user_data['last_name'] ?? '');
        } elseif (!empty($user_data['name'])) {
            $user_name = $user_data['name'];
        } else {
            if (!empty($result->user_id)) {
                $wp_user = get_userdata($result->user_id);
                if ($wp_user) {
                    $user_name = $wp_user->display_name;
                }
            }
        }
        
        if (empty($user_name)) {
            $user_name = __('Usuario', 'englishline-test');
        }
    
        $templates = get_option('englishline_test_email_templates', array());
        $subject = isset($templates['grade']['subject']) ? $templates['grade']['subject'] : 
                  __('Tu prueba ha sido calificada: {form_title}', 'englishline-test');
        
        $content = isset($templates['grade']['content']) ? $templates['grade']['content'] :
                  __("<p>Hola {user_name},</p>\n<p>Tu prueba \"{form_title}\" ha sido calificada.</p>\n<p><strong>Calificación:</strong> {score}%</p>\n{feedback}\n<p>Saludos,<br>{site_name}</p>", 'englishline-test');
    
        $form = $this->get_form($result->form_id);
        $form_title = $form ? $form->title : __('Formulario', 'englishline-test');
        
        $feedback_html = '';
        if (!empty($feedback)) {
            $feedback_html = '<p><strong>' . __('Comentarios:', 'englishline-test') . '</strong></p>';
            $feedback_html .= '<p>' . nl2br(esc_html($feedback)) . '</p>';
        }
    
        $replacements = array(
            '{form_title}' => $form_title,
            '{user_name}' => $user_name,
            '{user_email}' => $user_email,
            '{score}' => $score,
            '{feedback}' => $feedback_html,
            '{site_name}' => get_bloginfo('name')
        );
    
        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
    
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $sent = wp_mail($user_email, $subject, $content, $headers);
        return $sent;
    }
    
    public function send_form_responses($form_id, $form_responses, $result_id) {
        $admin_email = get_option('englishline_test_email_recipient', get_option('admin_email'));
        
        $form = $this->get_form($form_id);
        $form_title = isset($form_responses['form_title']) ? $form_responses['form_title'] : ($form ? $form->title : 'Test de Inglés');
        
        $user_data = isset($form_responses['user_info']) ? $form_responses['user_info'] : [];
        $user_name = isset($user_data['first_name']) ? sanitize_text_field($user_data['first_name'] . ' ' . ($user_data['last_name'] ?? '')) : 'Usuario';
        $user_email = isset($user_data['email']) ? sanitize_email($user_data['email']) : '';
        $user_phone = isset($user_data['phone']) ? sanitize_text_field($user_data['phone']) : '';
        
        $subject = $form_title . ' - Nuevo resultado';
        
        $email_content = $this->build_detailed_responses_email($form_title, $form_responses, $form_id, $result_id);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        if (!empty($user_email) && is_email($user_email)) {
            $headers[] = 'Cc: ' . $user_email;
        }
        
        $sent = wp_mail($admin_email, $subject, $email_content, $headers);
        
        return $sent;
    }

    private function build_detailed_responses_email($form_title, $form_responses, $form_id, $result_id) {
        global $wpdb;
        
        $result = $this->get_result($result_id);
        if (!$result) {
            return '<p>Error: No se encontraron los resultados.</p>';
        }
        
        $form = $this->get_form($form_id);
        $form_content = $form ? json_decode($form->form_data, true) : [];
        
        $user_data = isset($form_responses['user_info']) ? $form_responses['user_info'] : [];
        $user_name = isset($user_data['first_name']) ? esc_html($user_data['first_name'] . ' ' . ($user_data['last_name'] ?? '')) : 'No proporcionado';
        $user_email = isset($user_data['email']) ? esc_html($user_data['email']) : 'No proporcionado';
        $user_phone = isset($user_data['phone']) ? esc_html($user_data['phone']) : 'No proporcionado';
        $submission_date = date_i18n('d/m/Y, H:i:s', strtotime($result->created_at));
        
        $individual_scores = json_decode($result->individual_scores, true) ?: [];
        $score = $result->score;
        $level = $result->level;
        $correct_count = $form_responses['correct_count'] ?? 0;
        $total_questions = $form_responses['total_questions'] ?? 0;
        
        // Mapear respuestas correctas
        $correct_answers = [];
        foreach ($form_content as $section) {
            foreach ($section['questions'] as $question) {
                $question_id = $question['id'] ?? '';
                if (empty($question_id)) {
                    error_log('ENGLISHLINE - Pregunta sin ID en forms.form_data: ' . json_encode($question));
                    continue;
                }
                $correct_answers[$question_id] = [
                    'type' => $question['type'],
                    'correct_answer' => $this->get_correct_answer($question),
                    'case_sensitive' => $question['caseSensitive'] ?? false,
                    'options' => $question['options'] ?? []
                ];
                // Debug: Log the correct answers mapping
                error_log('ENGLISHLINE - Correct answers for question ID ' . $question_id . ': ' . json_encode($correct_answers[$question_id]));
            }
        }
        
        $styles = '
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 800px;
                margin: 0 auto;
                background-color: #f9f9f9;
            }
            .container {
                background-color: #ffffff;
                border: 1px solid #e0e0e0;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }
            .header {
                background-color: #4a6fdc;
                color: white;
                padding: 20px;
                text-align: center;
            }
            .content {
                padding: 20px;
            }
            .section {
                margin-bottom: 30px;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                overflow: hidden;
            }
            .section-header {
                background-color: #f5f5f5;
                padding: 15px;
                border-bottom: 1px solid #e0e0e0;
            }
            .section-title {
                margin: 0;
                font-size: 20px;
                color: #333;
            }
            .section-description {
                margin-top: 5px;
                color: #666;
                font-style: italic;
            }
            .section-content {
                padding: 15px;
            }
            .question {
                margin-bottom: 20px;
                padding: 15px;
                background-color: #f9f9f9;
                border-left: 3px solid #4a6fdc;
            }
            .question-text {
                font-weight: bold;
                margin-bottom: 10px;
                color: #333;
            }
            .answer {
                padding-left: 15px;
                border-left: 2px solid #e0e0e0;
            }
            .student-info {
                background-color: #f5f5f5;
                padding: 20px;
                margin-bottom: 20px;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
            }
            .student-info h2 {
                margin-top: 0;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
                color: #333;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #666;
                padding: 20px;
                background-color: #f5f5f5;
                border-top: 1px solid #e0e0e0;
            }
            h1, h2, h3 {
                color: #333;
            }
            .writing-section {
                background-color: #fff8e1;
                padding: 15px;
                border-left: 3px solid #ffc107;
            }
            .results-section {
                background-color: #f0f8ff;
                padding: 20px;
                margin-bottom: 20px;
                border: 1px solid #d0e5ff;
                border-radius: 5px;
                border-left: 5px solid #4a6fdc;
            }
            .results-section h2 {
                margin-top: 0;
                color: #4a6fdc;
                border-bottom: 1px solid #d0e5ff;
                padding-bottom: 10px;
            }
            .score-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
            }
            .score-table th, .score-table td {
                padding: 8px;
                border: 1px solid #d0e5ff;
                text-align: center;
            }
            .score-table th {
                background-color: #e6f0ff;
                font-weight: bold;
            }
            .total-score {
                font-size: 18px;
                font-weight: bold;
                margin: 15px 0;
            }
            .correct {
                background-color: #e6ffea;
                color: #2e7d32;
                padding: 3px 6px;
                border-radius: 3px;
                font-weight: bold;
            }
            .incorrect {
                background-color: #ffebee;
                color: #c62828;
                padding: 3px 6px;
                border-radius: 3px;
                font-weight: bold;
            }
            .question-result.partial-correct {
                border-left: 4px solid #ff9800;
                background-color: #fff3e0;
            }
            .partial-correct {
                background-color: #fff3e0;
                color: #e65100;
                padding: 3px 6px;
                border-radius: 3px;
                font-weight: bold;
            }
            .level-badge {
                display: inline-block;
                padding: 5px 10px;
                background-color: #4a6fdc;
                color: white;
                border-radius: 5px;
                font-weight: bold;
                margin: 5px 0;
            }
            .correct-answer {
                background-color: #e6ffea;
                padding: 3px 6px;
                border-radius: 3px;
                margin-top: 5px;
                display: inline-block;
            }
            .question-result {
                margin-bottom: 15px;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .question-result.correct {
                border-left: 4px solid #2e7d32;
                background-color: #f1f8e9;
            }
            .question-result.incorrect {
                border-left: 4px solid #c62828;
                background-color: #fef5f5;
            }
            .compare-answers {
                display: flex;
                margin-top: 10px;
            }
            .compare-answers > div {
                flex: 1;
                padding: 10px;
            }
            .user-answer, .expected-answer {
                font-size: 14px;
            }
            .user-answer {
                border-right: 1px solid #ddd;
            }
        ';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . esc_html($form_title) . ' - Resultados</title>
            <style>' . $styles . '</style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Test completo</h1>
                    <p>Respuestas completas para revisión</p>
                </div>
                <div class="content">
                    <div class="results-section">
                        <h2>Resultados de la evaluación automática</h2>
                        <div class="total-score">
                            <p>Puntuación total: <strong>' . esc_html($score) . '%</strong> (' . 
                              esc_html($correct_count) . ' de ' . 
                              esc_html($total_questions) . ' preguntas correctas)</p>
                            <p>Nivel CEFR: <span class="level-badge">' . esc_html($level) . '</span> - ' . 
                              esc_html($this->get_cefr_description($level)) . '</p>
                        </div>
                        <h3>Resultados por secciones</h3>
                        <table class="score-table">
                            <thead>
                                <tr>
                                    <th>Sección</th>
                                    <th>Correctas</th>
                                    <th>Total</th>
                                    <th>Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>';
        
        foreach ($individual_scores as $section_title => $section_score) {
            if ($section_title === 'Datos de contacto') {
                continue;
            }        
            $html .= '
                <tr>
                    <td>' . esc_html($section_title) . '</td>
                    <td>' . esc_html($section_score['correct']) . '</td>
                    <td>' . esc_html($section_score['total']) . '</td>
                    <td>' . esc_html($section_score['percentage']) . '%</td>
                </tr>';
        }
        
        $html .= '
                            </tbody>
                        </table>
                    </div>
                    <div class="student-info">
                        <h2>Información del estudiante</h2>
                        <p><strong>Nombre:</strong> ' . $user_name . '</p>
                        <p><strong>Email:</strong> ' . $user_email . '</p>
                        <p><strong>Teléfono:</strong> ' . $user_phone . '</p>
                        <p><strong>Fecha de envío:</strong> ' . $submission_date . '</p>
                    </div>';
        
        foreach ($form_responses['sections'] as $index => $section) {
            if ($section['title'] === 'Datos de contacto') {
                continue;
            }
            $html .= '<div class="section">';
            $html .= '<div class="section-header">';
            $html .= '<h3 class="section-title">' . esc_html($section['title']) . '</h3>';
            
            if (!empty($section['description'])) {
                $html .= '<div class="section-description">' . esc_html($section['description']) . '</div>';
            }
            
            $html .= '</div>';
            $html .= '<div class="section-content">';
            
            if (isset($section['questions']) && is_array($section['questions'])) {
                $question_count = 0;
                
                foreach ($section['questions'] as $q_index => $question) {
                    if (in_array($question['type'], ['title', 'paragraph', 'image'])) {
                        if ($question['type'] === 'title') {
                            $html .= '<h4>' . esc_html($question['text']) . '</h4>';
                        } elseif ($question['type'] === 'paragraph') {
                            $html .= '<div class="info-text">' . nl2br(esc_html($question['text'])) . '</div>';
                        }
                        continue;
                    }
                    
                    $question_count++;
                    $is_gradable = isset($question['isGradable']) && $question['isGradable'];
                    $question_id = $question['id'] ?? '';
                    $question_type = $question['type'] ?? 'text';
                    
                    $is_correct = false;
                    $status_text = '';
                    $correct_answer_html = '';
                    
                    if ($is_gradable && isset($correct_answers[$question_id])) {
                        $correct_answer = $correct_answers[$question_id]['correct_answer'];
                        $case_sensitive = $correct_answers[$question_id]['case_sensitive'];
                        $result = $this->compare_answers($question_type, $question['answer'], $correct_answer, $case_sensitive);
                        if ($result === true) {
                            $is_correct = true;
                            $status_text = '✓ CORRECTA';
                        } else if ($result === 0.5) {
                            $is_correct = 'partial';
                            $status_text = '◑ PARCIALMENTE CORRECTA';
                        } else {
                            $is_correct = false;
                            $status_text = '✗ INCORRECTA';
                        }
                        // Pass user_answer for ordering questions to provide fallback options
                        $options_for_correct = $correct_answers[$question_id];
                        if ($question_type === 'ordering' && empty($options_for_correct['options']) && !empty($question['answer']) && is_array($question['answer'])) {
                            $options_for_correct['user_answer'] = $question['answer'];
                        }
                        if ($question_type === 'checkbox' || $question_type === 'ordering' || !$is_correct) {
                            error_log('ENGLISHLINE - Generating correct_answer_html for question ID ' . $question_id . ', type=' . $question_type);
                            error_log('ENGLISHLINE - User answer: ' . json_encode($question['answer']));
                            error_log('ENGLISHLINE - Correct answer: ' . json_encode($correct_answer));
                            error_log('ENGLISHLINE - Options: ' . json_encode($options_for_correct));
                            $correct_answer_html = '<div class="compare-answers">' .
                                                  '<div class="user-answer"><strong>Respuesta del estudiante:</strong><br>' .
                                                  $this->format_answer_for_email($question_type, $question['answer'], $options_for_correct) . '</div>' .
                                                  '<div class="expected-answer"><strong>Respuesta correcta:</strong><br>' .
                                                  $this->format_answer_for_email($question_type, $correct_answer, $options_for_correct) . '</div>' .
                                                  '</div>';
                        }
                    }
                    
                    $html .= '<div class="question-result ' . ($is_gradable && isset($correct_answers[$question_id]) ? 
                    ($is_correct === true ? 'correct' : ($is_correct === 'partial' ? 'partial-correct' : 'incorrect')) : '') . '">';
                    $html .= '<div class="question-text">Pregunta ' . $question_count . ': ' . esc_html($question['text']) . '</div>';
                
                    if ($is_gradable && isset($correct_answers[$question_id])) {
                        $status_class = $is_correct === true ? 'correct' : ($is_correct === 'partial' ? 'partial-correct' : 'incorrect');
                        $html .= '<div class="' . $status_class . '">' . $status_text . '</div>';
                    }
                    
                    $html .= '<div class="answer">' . 
                             ($question['answer'] !== null && $question['answer'] !== '' ? $this->format_answer_for_email($question_type, $question['answer'], $correct_answers[$question_id] ?? []) : '<p><em>No se proporcionó respuesta</em></p>') . 
                             '</div>';
                    
                    $html .= $correct_answer_html;
                    $html .= '</div>';
                }
                
                if ($question_count === 0) {
                    $html .= '<p>No hay preguntas en esta sección.</p>';
                }
            } else {
                $html .= '<p>No hay preguntas en esta sección.</p>';
            }
            
            $html .= '</div></div>';
        }
        
        $html .= '
                </div>
                <div class="footer">
                    <p>Este correo fue generado automáticamente por English Line Test.</p>
                    <p>' . get_bloginfo('name') . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    private function get_correct_answer($question) {
        switch ($question['type']) {
            case 'text':
            case 'textarea':
                return $question['correctAnswer'] ?? '';
                
            case 'select':
            case 'radio':
                return (string) ($question['correctOption'] ?? '');
                
            case 'checkbox':
                return array_map('strval', $question['correctOptions'] ?? []);
                
            case 'cloze':
                return $question['correctFills'] ?? [];
                
            case 'ordering':
                return array_map('strval', $question['correctOrder'] ?? []);
                
            case 'true-false':
                return $question['correctValue'] ? 'Verdadero' : 'Falso';
                
            default:
                return null;
        }
    }

    private function compare_answers($type, $user_answer, $correct_answer, $case_sensitive) {
        if ($user_answer === null || $user_answer === '' || (is_array($user_answer) && empty($user_answer))) {
            return false;
        }
        
        switch ($type) {
            case 'text':
            case 'textarea':
                if ($case_sensitive) {
                    return $user_answer === $correct_answer;
                }
                return strtolower($user_answer) === strtolower($correct_answer);
                
            case 'select':
            case 'radio':
                return is_array($user_answer) && isset($user_answer['value']) && $user_answer['value'] === $correct_answer;
                
            case 'checkbox':
                if (!is_array($user_answer) || !is_array($correct_answer)) {
                    return false;
                }
                $user_values = array_column($user_answer, 'value');
                $intersection = array_intersect($user_values, $correct_answer);
                $wrong_selections = array_diff($user_values, $correct_answer);
                
                if (count($intersection) == count($correct_answer) && count($wrong_selections) == 0) {
                    return true; 
                } else if (count($intersection) > 0 && count($wrong_selections) <= 1) {
                    return 0.5; 
                }
                return false;
                
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

    private function get_cefr_description($level) {
        switch ($level) {
            case 'C2':
                return 'Nivel de maestría - Puede comunicarse con fluidez y precisión.';
            case 'C1':
                return 'Nivel avanzado - Puede comunicarse con fluidez en situaciones complejas.';
            case 'B2':
                return 'Nivel intermedio alto - Puede comunicarse con confianza en situaciones familiares.';
            case 'B1':
                return 'Nivel intermedio - Puede comunicarse en situaciones cotidianas.';
            case 'A2':
                return 'Nivel elemental - Puede comunicarse en situaciones cotidianas simples.';
            case 'A1':
                return 'Nivel principiante - Puede usar expresiones básicas.';
            default:
                return 'Principiante - Conocimientos muy básicos.';
        }
    }

    private function format_answer_for_email($type, $answer, $options = []) {
        // Debug: Log input data at the start of the function
        error_log('ENGLISHLINE - format_answer_for_email called: type=' . $type . ', answer=' . json_encode($answer) . ', options=' . json_encode($options));
        
        if ($answer === null || $answer === '' || (is_array($answer) && empty($answer))) {
            return '<p><em>No se proporcionó respuesta</em></p>';
        }

        switch ($type) {
            case 'text':
                return '<p>' . esc_html($answer) . '</p>';
                
            case 'textarea':
                return '<div style="background-color:#f9f9f9; padding:10px; border-radius:4px;">' . nl2br(esc_html($answer)) . '</div>';
                
            case 'select':
            case 'radio':
                if (is_array($answer) && isset($answer['text'])) {
                    return '<p><span style="background-color:#e8f4ff; padding:2px 6px; border-radius:3px;">' . esc_html($answer['text']) . '</span></p>';
                } elseif (!empty($options['options']) && isset($answer['value'])) {
                    foreach ($options['options'] as $option) {
                        if (isset($option['value']) && $option['value'] === $answer['value']) {
                            return '<p><span style="background-color:#e8f4ff; padding:2px 6px; border-radius:3px;">' . esc_html($option['text']) . '</span></p>';
                        }
                    }
                }
                return '<p>' . esc_html($answer) . '</p>';
                
            case 'checkbox':
                if (is_array($answer) && !empty($answer)) {
                    $html = '<ul style="margin-top:5px;">';
                    foreach ($answer as $item) {
                        $text = is_array($item) && isset($item['text']) ? $item['text'] : $item;
                        $value = is_array($item) && isset($item['value']) ? $item['value'] : $item;
                        foreach ($options['options'] as $index => $option) {
                            // Match explicit value or use index as implicit value
                            if ((isset($option['value']) && $option['value'] === $value) || (!isset($option['value']) && (string)$index === (string)$value)) {
                                $text = $option['text'];
                                break;
                            }
                        }
                        $html .= '<li>' . esc_html($text) . '</li>';
                    }
                    $html .= '</ul>';
                    // Debug: Log the generated HTML for student answer
                    error_log('ENGLISHLINE - Checkbox student answer HTML: ' . $html);
                    return $html;
                } elseif (!empty($options['correct_answer']) && is_array($options['correct_answer'])) {
                    // Handle correct answer
                    $html = '<ul style="margin-top:5px;">';
                    foreach ($options['correct_answer'] as $value) {
                        $text = $value;
                        foreach ($options['options'] as $index => $option) {
                            // Match explicit value or use index as implicit value
                            if ((isset($option['value']) && $option['value'] === $value) || (!isset($option['value']) && (string)$index === (string)$value)) {
                                $text = $option['text'];
                                break;
                            }
                        }
                        $html .= '<li>' . esc_html($text) . '</li>';
                    }
                    $html .= '</ul>';
                    // Debug: Log the generated HTML for correct answer
                    error_log('ENGLISHLINE - Checkbox correct answer HTML: ' . $html);
                    return $html;
                }
                return '<p><em>No se seleccionó ninguna opción</em></p>';
                
            case 'cloze':
                if (is_array($answer)) {
                    $html = '<ol style="margin-top:5px;">';
                    foreach ($answer as $fill) {
                        $html .= '<li><span style="background-color:#f0f7e6; padding:2px 6px; border-radius:3px;">' . esc_html($fill) . '</span></li>';
                    }
                    $html .= '</ol>';
                    return $html;
                }
                return '<p>' . esc_html($answer) . '</p>';
                
            case 'ordering':
                if (is_array($answer) && !empty($answer)) {
                    // Handle student answer
                    if (isset($answer[0]) && is_array($answer[0]) && isset($answer[0]['value']) && isset($answer[0]['text'])) {
                        $html = '<ol style="margin-top:5px;">';
                        foreach ($answer as $item) {
                            $html .= '<li>' . esc_html($item['text']) . '</li>';
                        }
                        $html .= '</ol>';
                        // Debug: Log the generated HTML for student answer
                        error_log('ENGLISHLINE - Ordering student answer HTML: ' . $html);
                        return $html;
                    }
                    // Handle correct answer
                    if (!empty($options['correct_answer']) && is_array($options['correct_answer'])) {
                        $html = '<ol style="margin-top:5px;">';
                        // Use user_answer as fallback if options are empty
                        $text_map = [];
                        if (!empty($options['user_answer']) && is_array($options['user_answer'])) {
                            foreach ($options['user_answer'] as $item) {
                                if (isset($item['value']) && isset($item['text'])) {
                                    $text_map[$item['value']] = $item['text'];
                                }
                            }
                        }
                        foreach ($options['correct_answer'] as $value) {
                            $text = $value;
                            // Try options first
                            foreach ($options['options'] as $index => $option) {
                                if ((isset($option['value']) && $option['value'] === $value) || (!isset($option['value']) && (string)$index === (string)$value)) {
                                    $text = $option['text'];
                                    break;
                                }
                            }
                            // Fallback to user_answer text map
                            if ($text === $value && isset($text_map[$value])) {
                                $text = $text_map[$value];
                            }
                            $html .= '<li>' . esc_html($text) . '</li>';
                        }
                        $html .= '</ol>';
                        // Debug: Log the generated HTML for correct answer
                        error_log('ENGLISHLINE - Ordering correct answer HTML: ' . $html);
                        return $html;
                    }
                    // Fallback for indices
                    $html = '<ol style="margin-top:5px;">';
                    foreach ($answer as $value) {
                        $text = $value;
                        foreach ($options['options'] as $index => $option) {
                            if ((isset($option['value']) && $option['value'] === $value) || (!isset($option['value']) && (string)$index === (string)$value)) {
                                $text = $option['text'];
                                break;
                            }
                        }
                        $html .= '<li>' . esc_html($text) . '</li>';
                    }
                    $html .= '</ol>';
                    // Debug: Log the generated HTML for fallback
                    error_log('ENGLISHLINE - Ordering fallback answer HTML: ' . $html);
                    return $html;
                }
                return '<p>' . esc_html(is_array($answer) ? json_encode($answer) : $answer) . '</p>';

            case 'true-false':
                $style_color = $answer === 'Verdadero' ? '#4caf50' : '#f44336';
                return '<p><span style="color:' . $style_color . '; font-weight:bold;">' . esc_html($answer) . '</span></p>';
                
            default:
                if (is_array($answer)) {
                    return '<p>' . esc_html(json_encode($answer, JSON_PRETTY_PRINT)) . '</p>';
                }
                return '<p>' . esc_html($answer) . '</p>';
        }
    }

    private function index_to_text($indices, $options) {
        if (empty($indices) || !is_array($indices)) {
            return $indices;
        }
        
        error_log('Indices recibidos: ' . json_encode($indices));
        error_log('Opciones recibidas: ' . json_encode($options));
        
        if (isset($options['items']) && is_array($options['items'])) {
            $options = $options['items'];
        }
        
        if (isset($options[0]) && is_string($options[0])) {
            $texts = [];
            foreach ($indices as $index) {
                $idx = (int)$index;
                if (isset($options[$idx])) {
                    $texts[] = $options[$idx];
                } else {
                    $texts[] = "Opción #" . $idx;
                }
            }
            return $texts;
        }
        
        $result = [];
        foreach ($indices as $index) {
            $found = false;
            foreach ($options as $option) {
                if (is_array($option) && isset($option['text'])) {
                    if ((isset($option['value']) && $option['value'] == $index) || 
                        (isset($option['id']) && $option['id'] == $index)) {
                        $result[] = $option['text'];
                        $found = true;
                        break;
                    }
                }
            }
            
            if (!$found && isset($options[$index]) && is_string($options[$index])) {
                $result[] = $options[$index];
                $found = true;
            }
            
            if (!$found) {
                if (is_string($index) && !is_numeric($index)) {
                    $result[] = $index;
                } else {
                    $result[] = "Opción #" . $index;
                }
            }
        }
        
        return !empty($result) ? $result : $indices;
    }
}