<?php
/**
 * Muestra la vista detallada de un resultado
 */

// Verificar que no se accede directamente
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
global $form_structure;
global $answers;

// Habilitar debug si no está activado
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    define('WP_DEBUG', true);
    define('WP_DEBUG_LOG', true);
    define('WP_DEBUG_DISPLAY', false);
}

// Obtener el ID del resultado de la URL
$result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

// Verificar si el ID del resultado es válido
if (!$result_id) {
    wp_die(__('Resultado no válido.', 'englishline-test'));
}

// Obtener los datos del resultado de la base de datos
$result = $wpdb->get_row($wpdb->prepare(
    "SELECT r.*, f.title as form_title, f.form_data as form_structure, u.display_name as user_name, u.user_email as user_email_from_wp
     FROM {$wpdb->prefix}englishline_results r
     LEFT JOIN {$wpdb->prefix}englishline_forms f ON r.form_id = f.id
     LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
     WHERE r.id = %d",
    $result_id
));

// Si no se encuentra el resultado, mostrar un mensaje de error
if (!$result) {
    wp_die(__('Resultado no encontrado.', 'englishline-test'));
}

// Debug: Registrar datos crudos
error_log('ENGLISHLINE_DEBUG - Raw form_structure: ' . print_r($result->form_structure, true));
error_log('ENGLISHLINE_DEBUG - Raw form_data: ' . print_r($result->form_data, true));

// Procesar form_structure
$form_structure = [];
if (!empty($result->form_structure)) {
    $form_structure = json_decode($result->form_structure, true, 512, JSON_UNESCAPED_UNICODE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('ENGLISHLINE_DEBUG - Error decodificando form_structure: ' . json_last_error_msg());
        $form_structure = [];
    }
    if (is_array($form_structure)) {
        if (isset($form_structure[0]) && !isset($form_structure['sections'])) {
            $form_structure = ['sections' => $form_structure];
        }
        error_log('ENGLISHLINE_DEBUG - Processed form_structure: ' . print_r($form_structure, true));
    } else {
        error_log('ENGLISHLINE_DEBUG - form_structure no es un array: ' . print_r($form_structure, true));
        $form_structure = [];
    }
}

// Procesar form_data
$user_data = [];
$answers = [];
$form_data = json_decode($result->form_data, true, 512, JSON_UNESCAPED_UNICODE);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('ENGLISHLINE_DEBUG - Error decodificando form_data: ' . json_last_error_msg());
    $form_data = [];
}

if (is_array($form_data)) {
    if (isset($form_data['user_info'])) {
        $user_data = $form_data['user_info'];
    }

    if (isset($form_data['sections']) && is_array($form_data['sections'])) {
        foreach ($form_data['sections'] as $section_index => $section) {
            if (isset($section['questions']) && is_array($section['questions'])) {
                foreach ($section['questions'] as $question_index => $question) {
                    if (isset($question['id']) && !empty($question['id'])) {
                        $question_id = "question_{$section_index}_{$question_index}";
                        if (isset($question['answer'])) {
                            $answers[$question_id] = $question['answer'];
                        }
                    }
                }
            }
        }
    }
    error_log('ENGLISHLINE_DEBUG - Processed user_data: ' . print_r($user_data, true));
    error_log('ENGLISHLINE_DEBUG - Processed answers: ' . print_r($answers, true));
} else {
    error_log('ENGLISHLINE_DEBUG - form_data no es un array: ' . print_r($form_data, true));
}

// Procesar envío del formulario de calificación
$message = '';
$message_type = '';

if (isset($_POST['englishline_save_review']) && check_admin_referer('englishline_review_result_' . $result_id)) {
    $score = isset($_POST['score']) ? intval($_POST['score']) : null;
    $comments = isset($_POST['comments']) ? sanitize_textarea_field($_POST['comments']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'reviewed';

    if ($score !== null && ($score < 0 || $score > 100)) {
        $score = null;
    }

    // Procesar estados de correcta/incorrecta por pregunta
    $individual_corrections = [];
    if (isset($_POST['is_correct']) && is_array($_POST['is_correct'])) {
        foreach ($_POST['is_correct'] as $question_id => $value) {
            $individual_corrections[$question_id] = (bool)$value;
        }

        // Calcular la calificación total si no se proporcionó manualmente
        if ($score === null) {
            $total_gradable = 0;
            $correct_count = 0;

            if (isset($form_structure['sections'])) {
                foreach ($form_structure['sections'] as $section_index => $section) {
                    if (isset($section['questions'])) {
                        foreach ($section['questions'] as $question_index => $question) {
                            if (isset($question['type']) && ($question['type'] === 'title' || $question['type'] === 'paragraph')) {
                                continue;
                            }
                            if (!isset($question['isGradable']) || !$question['isGradable']) {
                                continue;
                            }
                            $total_gradable++;
                            $question_id = "question_{$section_index}_{$question_index}";
                            if (isset($individual_corrections[$question_id]) && $individual_corrections[$question_id]) {
                                $correct_count++;
                            }
                        }
                    }
                }
            }

            $score = $total_gradable > 0 ? round(($correct_count / $total_gradable) * 100, 2) : 0;
        }
    }

    $updated = $wpdb->update(
        $wpdb->prefix . 'englishline_results',
        array(
            'score' => $score,
            'feedback' => $comments,
            'status' => $status,
            'individual_scores' => json_encode($individual_corrections),
            'reviewed_at' => current_time('mysql'),
            'reviewer_id' => get_current_user_id()
        ),
        array('id' => $result_id),
        array('%d', '%s', '%s', '%s', '%s', '%d'),
        array('%d')
    );

    if ($updated !== false) {
        $message = __('Calificación guardada exitosamente.', 'englishline-test');
        $message_type = 'success';

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, f.title as form_title, f.form_data as form_structure, u.display_name as user_name, u.user_email as user_email_from_wp
             FROM {$wpdb->prefix}englishline_results r
             LEFT JOIN {$wpdb->prefix}englishline_forms f ON r.form_id = f.id
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
             WHERE r.id = %d",
            $result_id
        ));
    } else {
        $message = __('Error al guardar la calificación.', 'englishline-test');
        $message_type = 'error';
    }
    if ($status === 'approved' || $status === 'failed') {
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/helpers/class-email-helper.php';
        $email_helper = new Email_Helper();
        $email_sent = $email_helper->send_grade_notification($result_id, $score, $comments);

        if ($email_sent) {
            $message .= ' ' . __('Notificación enviada por correo electrónico.', 'englishline-test');
        } else {
            $message .= ' ' . __('No se pudo enviar la notificación por correo electrónico.', 'englishline-test');
        }
    }
}

// Cargar las correcciones individuales si existen
$individual_corrections = [];
if (!empty($result->individual_scores)) {
    $individual_corrections = json_decode($result->individual_scores, true);
    if (!is_array($individual_corrections)) {
        $individual_corrections = [];
    }
}

// Función para obtener el tipo legible de pregunta
function get_question_type_label($type)
{
    $types = [
        'text' => 'Respuesta corta',
        'textarea' => 'Respuesta larga',
        'select' => 'Selección desplegable',
        'radio' => 'Selección única',
        'checkbox' => 'Selección múltiple',
        'image' => 'Imagen',
        'cloze' => 'Completar huecos',
        'ordering' => 'Ejercicio de ordenación',
        'drag-drop' => 'Arrastrar y soltar',
        'matching' => 'Emparejar',
        'true-false' => 'Verdadero/Falso',
        'paragraph' => 'Párrafo informativo'
    ];
    return isset($types[$type]) ? $types[$type] : ucfirst($type);
}

// Función para renderizar la respuesta del usuario de forma legible
function render_user_answer($answer, $question_type, $question = null)
{
    if ($question === null) {
        $question = [];
    }

    if (is_array($answer)) {
        if ($question_type == 'checkbox') {
            echo '<ul>';
            foreach ($answer as $item) {
                $option_text = is_array($item) && isset($item['text']) ? $item['text'] : $item;
                $value = is_array($item) && isset($item['value']) ? $item['value'] : $item;
                if (isset($question['options']) && is_array($question['options'])) {
                    foreach ($question['options'] as $opt_idx => $opt) {
                        if ((string)$opt_idx === (string)$value && isset($opt['text'])) {
                            $option_text = $opt['text'];
                            break;
                        }
                    }
                }
                echo '<li>' . esc_html($option_text) . '</li>';
            }
            echo '</ul>';
        } elseif ($question_type == 'ordering') {
            echo '<ol>';
            foreach ($answer as $item) {
                $option_text = is_array($item) && isset($item['text']) ? $item['text'] : $item;
                $value = is_array($item) && isset($item['value']) ? $item['value'] : $item;
                if (empty($option_text) && isset($question['itemsText']) && is_string($question['itemsText'])) {
                    $items_array = array_map('trim', explode("\n", $question['itemsText']));
                    if (isset($items_array[$value])) {
                        $option_text = $items_array[$value];
                    }
                }
                echo '<li>' . esc_html($option_text) . '</li>';
            }
            echo '</ol>';
        } elseif ($question_type == 'cloze') {
            if (isset($question['clozeText']) && !empty($question['clozeText'])) {
                $cloze_text = $question['clozeText'];
                $blank_count = 0;
                $pattern = strpos($cloze_text, '[blank]') !== false ? '/\[blank\]/' : '/\[(.*?)\]/';
                $filled_text = preg_replace_callback($pattern, function ($matches) use ($answer, &$blank_count) {
                    $user_answer = isset($answer[$blank_count]) ? '<span class="user-answer">' . esc_html($answer[$blank_count]) . '</span>' : '______';
                    $blank_count++;
                    return $user_answer;
                }, $cloze_text);
                echo '<div class="cloze-text-filled">' . $filled_text . '</div>';
                echo '<div class="cloze-answers"><strong>' . __('Respuestas:', 'englishline-test') . '</strong><ol>';
                foreach ($answer as $blank_answer) {
                    echo '<li>' . esc_html($blank_answer) . '</li>';
                }
                echo '</ol></div>';
            } else {
                echo '<ul>';
                foreach ($answer as $idx => $blank) {
                    echo '<li>' . __('Hueco', 'englishline-test') . ' ' . ($idx + 1) . ': ' . esc_html($blank) . '</li>';
                }
                echo '</ul>';
            }
        } else {
            echo '<p>' . esc_html(implode(', ', $answer)) . '</p>';
        }
    } else {
        if (($question_type == 'select' || $question_type == 'radio') && isset($question['options'])) {
            $option_text = $answer;
            if (is_array($answer) && isset($answer['text'])) {
                $option_text = $answer['text'];
                $value = isset($answer['value']) ? $answer['value'] : $answer;
            } else {
                $value = $answer;
            }
            foreach ($question['options'] as $opt_idx => $opt) {
                if ((string)$opt_idx === (string)$value && isset($opt['text'])) {
                    $option_text = $opt['text'];
                    break;
                }
            }
            echo '<p>' . esc_html($option_text) . '</p>';
        } elseif ($question_type == 'true-false') {
            $tf_value = $answer === 'Verdadero' || $answer === true ? __('Verdadero', 'englishline-test') : __('Falso', 'englishline-test');
            echo '<p>' . esc_html($tf_value) . '</p>';
        } else {
            echo '<p>' . esc_html($answer !== null ? $answer : '') . '</p>';
        }
    }
}

function display_question_image($image_id)
{
    if ($image_id) {
        $image_url = wp_get_attachment_url($image_id);
        if ($image_url) {
            echo '<div class="result-question-image">';
            echo '<img src="' . esc_url($image_url) . '" alt="Imagen de pregunta">';
            echo '</div>';
        }
    }
}

function find_question_in_structure($section_index, $question_index)
{
    global $form_structure;
    if (isset($form_structure['sections'][$section_index]['questions'][$question_index])) {
        return $form_structure['sections'][$section_index]['questions'][$question_index];
    }
    return null;
}

$total_questions = 0;
$total_gradable_questions = 0;
if (isset($form_structure['sections'])) {
    foreach ($form_structure['sections'] as $section) {
        if (isset($section['questions'])) {
            $total_questions += count(array_filter($section['questions'], function ($q) {
                return !isset($q['type']) || ($q['type'] !== 'title' && $q['type'] !== 'paragraph');
            }));
            $total_gradable_questions += count(array_filter($section['questions'], function ($q) {
                return isset($q['isGradable']) && $q['isGradable'] && !isset($q['type']) || ($q['type'] !== 'title' && $q['type'] !== 'paragraph');
            }));
        }
    }
}

$value_per_question = $total_gradable_questions > 0 ? round(100 / $total_gradable_questions, 2) : 0;

$user_name = '';
if (!empty($user_data['first_name'])) {
    $user_name = $user_data['first_name'] . ' ' . ($user_data['last_name'] ?? '');
} elseif (!empty($user_data['name'])) {
    $user_name = $user_data['name'];
} elseif (!empty($result->user_name)) {
    $user_name = $result->user_name;
} else {
    $user_name = 'Usuario anónimo';
}

$user_email = '';
if (!empty($result->user_email)) {
    $user_email = $result->user_email;
} elseif (!empty($result->user_email_from_wp)) {
    $user_email = $result->user_email_from_wp;
} elseif (!empty($user_data['email'])) {
    $user_email = $user_data['email'];
}

$time_spent = '';
if (!empty($result->start_time) && !empty($result->end_time)) {
    $start = new DateTime($result->start_time);
    $end = new DateTime($result->end_time);
    $diff = $start->diff($end);
    if ($diff->h > 0) {
        $time_spent = $diff->format('%h horas, %i minutos, %s segundos');
    } elseif ($diff->i > 0) {
        $time_spent = $diff->format('%i minutos, %s segundos');
    } else {
        $time_spent = $diff->format('%s segundos');
    }
}
?>
<div class="wrap englishline-admin-wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Detalle de Resultado', 'englishline-test'); ?>
        <a href="<?php echo admin_url('admin.php?page=englishline-test-results'); ?>" class="page-title-action"><?php _e('Volver a la lista', 'englishline-test'); ?></a>
    </h1>

    <?php if ($message): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <div class="englishline-result-container">
        <div class="result-header">
            <div class="result-meta">
                <div class="result-meta-item">
                    <span class="result-meta-label"><?php _e('ID del resultado:', 'englishline-test'); ?></span>
                    <span class="result-meta-value"><?php echo esc_html($result->id); ?></span>
                </div>
                <div class="result-meta-item">
                    <span class="result-meta-label"><?php _e('Formulario:', 'englishline-test'); ?></span>
                    <span class="result-meta-value"><?php echo esc_html($result->form_title); ?></span>
                </div>
                <div class="result-meta-item">
                    <span class="result-meta-label"><?php _e('Fecha de envío:', 'englishline-test'); ?></span>
                    <span class="result-meta-value"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($result->created_at))); ?></span>
                </div>
                <?php if (!empty($time_spent)): ?>
                    <div class="result-meta-item">
                        <span class="result-meta-label"><?php _e('Tiempo:', 'englishline-test'); ?></span>
                        <span class="result-meta-value"><?php echo esc_html($time_spent); ?></span>
                    </div>
                <?php endif; ?>
                <div class="result-meta-item">
                    <span class="result-meta-label"><?php _e('Estado:', 'englishline-test'); ?></span>
                    <span class="result-meta-value result-status result-status-<?php echo esc_attr($result->status); ?>">
                        <?php
                        switch ($result->status) {
                            case 'pending':
                                _e('Pendiente', 'englishline-test');
                                break;
                            case 'reviewed':
                                _e('Revisado', 'englishline-test');
                                break;
                            case 'approved':
                                _e('Aprobado', 'englishline-test');
                                break;
                            case 'failed':
                                _e('Reprobado', 'englishline-test');
                                break;
                            default:
                                echo esc_html($result->status);
                        }
                        ?>
                    </span>
                </div>
                <?php if ($result->score !== null): ?>
                    <div class="result-meta-item">
                        <span class="result-meta-label"><?php _e('Calificación:', 'englishline-test'); ?></span>
                        <span class="result-meta-value result-score">
                            <?php echo esc_html($result->score); ?>%
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="result-user-info">
                <h2><?php _e('Información del usuario', 'englishline-test'); ?></h2>
                <div class="result-user-data">
                    <div class="result-user-item">
                        <span class="result-user-label"><?php _e('Nombre:', 'englishline-test'); ?></span>
                        <span class="result-user-value"><?php echo esc_html($user_name); ?></span>
                    </div>
                    <div class="result-user-item">
                        <span class="result-user-label"><?php _e('Email:', 'englishline-test'); ?></span>
                        <span class="result-user-value"><?php echo esc_html($user_email); ?></span>
                    </div>
                    <?php if (!empty($user_data)): ?>
                        <?php foreach ($user_data as $key => $value): ?>
                            <?php if ($key !== 'name' && $key !== 'first_name' && $key !== 'last_name' && $key !== 'email' && $key !== 'privacy_acceptance'): ?>
                                <div class="result-user-item">
                                    <span class="result-user-label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?>:</span>
                                    <span class="result-user-value"><?php echo esc_html($value); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="result-content">
            <form method="post" action="">
                <?php wp_nonce_field('englishline_review_result_' . $result->id); ?>

                <div class="result-review-form">
                    <h2><?php _e('Calificación', 'englishline-test'); ?></h2>

                    <div class="result-review-row">
                        <div class="result-review-field">
                            <label for="score"><?php _e('Calificación global (0-100):', 'englishline-test'); ?></label>
                            <input type="number" id="score" name="score" min="0" max="100" value="<?php echo $result->score !== null ? esc_attr($result->score) : ''; ?>" readonly>
                            <p class="description"><?php _e('Calculada automáticamente según las respuestas marcadas como correctas.', 'englishline-test'); ?></p>
                        </div>

                        <div class="result-review-field">
                            <label for="status"><?php _e('Estado:', 'englishline-test'); ?></label>
                            <select id="status" name="status">
                                <option value="pending" <?php selected($result->status, 'pending'); ?>><?php _e('Pendiente', 'englishline-test'); ?></option>
                                <option value="reviewed" <?php selected($result->status, 'reviewed'); ?>><?php _e('Revisado', 'englishline-test'); ?></option>
                                <option value="approved" <?php selected($result->status, 'approved'); ?>><?php _e('Aprobado', 'englishline-test'); ?></option>
                                <option value="failed" <?php selected($result->status, 'failed'); ?>><?php _e('Reprobado', 'englishline-test'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="result-review-field full-width">
                        <label for="comments"><?php _e('Comentarios:', 'englishline-test'); ?></label>
                        <textarea id="comments" name="comments" rows="4"><?php echo esc_textarea($result->feedback ?? ''); ?></textarea>
                    </div>

                    <div class="result-answers">
                        <h3><?php _e('Respuestas y Evaluación', 'englishline-test'); ?></h3>

                        <?php if (isset($form_structure['sections'])): ?>
                            <?php foreach ($form_structure['sections'] as $section_index => $section): ?>
                                <div class="result-section">
                                    <div class="result-section-header">
                                        <div class="result-section-title">
                                            <?php echo esc_html($section['title'] ?? 'Sección ' . ($section_index + 1)); ?>
                                        </div>
                                        <div class="result-section-info">
                                            <?php echo sprintf(__('Valor por pregunta graduable: %.2f puntos', 'englishline-test'), $value_per_question); ?>
                                        </div>
                                    </div>

                                    <?php if (isset($section['questions'])): ?>
                                        <?php foreach ($section['questions'] as $question_index => $question): ?>
                                            <?php
                                            if (isset($question['type']) && ($question['type'] === 'title' || $question['type'] === 'paragraph')) {
                                                continue;
                                            }

                                            $question_id = "question_{$section_index}_{$question_index}";
                                            $user_answer = isset($answers[$question_id]) ? $answers[$question_id] : null;

                                            $has_correct_answer = false;
                                            $is_correct = false;

                                            if (
                                                isset($question['correct_answer']) ||
                                                isset($question['correctAnswer']) ||
                                                isset($question['correctOption']) ||
                                                isset($question['correctOptions']) ||
                                                isset($question['correctFills']) ||
                                                isset($question['correctOrder']) ||
                                                isset($question['correctValue'])
                                            ) {
                                                $has_correct_answer = true;

                                                // Calcular si la respuesta es correcta automáticamente
                                                if ($question['type'] === 'checkbox') {
                                                    $correct_answer = isset($question['correctOptions']) ? $question['correctOptions'] : [];
                                                    if (is_array($user_answer) && is_array($correct_answer)) {
                                                        $user_values = array_map(function ($item) {
                                                            return is_array($item) && isset($item['value']) ? $item['value'] : $item;
                                                        }, $user_answer);
                                                        $is_correct = count(array_diff($correct_answer, $user_values)) === 0 &&
                                                                      count(array_diff($user_values, $correct_answer)) === 0;
                                                    }
                                                } elseif ($question['type'] === 'ordering') {
                                                    $correct_answer = isset($question['correctOrder']) ? $question['correctOrder'] : [];
                                                    if (is_array($user_answer) && is_array($correct_answer)) {
                                                        $user_values = array_map(function ($item) {
                                                            return is_array($item) && isset($item['value']) ? $item['value'] : $item;
                                                        }, $user_answer);
                                                        $is_correct = $user_values === $correct_answer;
                                                    }
                                                } elseif ($question['type'] === 'cloze') {
                                                    $correct_answer = isset($question['correctFills']) ? $question['correctFills'] : [];
                                                    $case_sensitive = isset($question['caseSensitive']) && $question['caseSensitive'];
                                                    if (is_array($user_answer) && is_array($correct_answer)) {
                                                        $is_correct = true;
                                                        foreach ($correct_answer as $i => $correct) {
                                                            $user_value = isset($user_answer[$i]) ? $user_answer[$i] : '';
                                                            if ($case_sensitive) {
                                                                if ($user_value !== $correct) {
                                                                    $is_correct = false;
                                                                    break;
                                                                }
                                                            } else {
                                                                if (strtolower($user_value) !== strtolower($correct)) {
                                                                    $is_correct = false;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                } elseif ($question['type'] === 'true-false') {
                                                    $correct_answer = isset($question['correctValue']) ? $question['correctValue'] : false;
                                                    $is_correct = ($user_answer === 'Verdadero' && $correct_answer === true) ||
                                                                  ($user_answer === 'Falso' && $correct_answer === false);
                                                } elseif ($question['type'] === 'select' || $question['type'] === 'radio') {
                                                    $correct_answer = isset($question['correctOption']) ? $question['correctOption'] : null;
                                                    if ($correct_answer !== null) {
                                                        $user_value = is_array($user_answer) && isset($user_answer['value']) ? $user_answer['value'] : $user_answer;
                                                        $is_correct = (string)$user_value === (string)$correct_answer;
                                                    }
                                                } else {
                                                    $correct_answer = isset($question['correctAnswer']) ? $question['correctAnswer'] :
                                                                     (isset($question['correct_answer']) ? $question['correct_answer'] : null);
                                                    $case_sensitive = isset($question['caseSensitive']) && $question['caseSensitive'];
                                                    if ($correct_answer !== null) {
                                                        if ($case_sensitive) {
                                                            $is_correct = $user_answer === $correct_answer;
                                                        } else {
                                                            $is_correct = strtolower(trim($user_answer)) === strtolower(trim($correct_answer));
                                                        }
                                                    }
                                                }
                                            }

                                            // Si hay una corrección manual, usarla
                                            if (isset($individual_corrections[$question_id])) {
                                                $is_correct = $individual_corrections[$question_id];
                                            }

                                            $question_class = $has_correct_answer ?
                                                ($is_correct ? 'result-correct' : 'result-incorrect') : '';
                                            if (!isset($question['type'])) {
                                                $question['type'] = 'text';
                                            }
                                            ?>
                                            <div class="result-question <?php echo esc_attr($question_class); ?>">
                                                <div class="result-question-header">
                                                    <div class="result-question-number"><?php echo $question_index + 1; ?>.</div>
                                                    <div class="result-question-text">
                                                        <?php echo esc_html($question['text'] ?? ''); ?>
                                                    </div>
                                                    <div class="result-question-type">
                                                        <span class="badge badge-<?php echo $has_correct_answer ? 'primary' : 'info'; ?>">
                                                            <?php echo esc_html(get_question_type_label($question['type'])); ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="result-question-content">
                                                    <?php if (isset($question['type']) && $question['type'] === 'image' && !empty($question['imageId'])): ?>
                                                        <?php display_question_image($question['imageId']); ?>
                                                    <?php endif; ?>

                                                    <div class="result-answer">
                                                        <div class="result-answer-label"><?php _e('Respuesta del usuario:', 'englishline-test'); ?></div>
                                                        <?php if ($user_answer !== null): ?>
                                                            <?php render_user_answer($user_answer, $question['type'], $question); ?>
                                                        <?php else: ?>
                                                            <p class="empty-answer"><?php _e('Sin respuesta', 'englishline-test'); ?></p>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($has_correct_answer): ?>
                                                        <div class="result-correct-answer">
                                                            <div class="result-answer-label"><?php _e('Respuesta correcta:', 'englishline-test'); ?></div>
                                                            <?php
                                                            if ($question['type'] === 'checkbox' && isset($question['correctOptions'])) {
                                                                echo '<ul>';
                                                                foreach ($question['correctOptions'] as $value) {
                                                                    $option_text = $value;
                                                                    if (isset($question['options']) && is_array($question['options'])) {
                                                                        foreach ($question['options'] as $opt_idx => $opt) {
                                                                            if ((string)$opt_idx === (string)$value && isset($opt['text'])) {
                                                                                $option_text = $opt['text'];
                                                                                break;
                                                                            }
                                                                        }
                                                                    }
                                                                    echo '<li>' . esc_html($option_text) . '</li>';
                                                                }
                                                                echo '</ul>';
                                                            } elseif ($question['type'] === 'ordering' && isset($question['correctOrder'])) {
                                                                echo '<ol>';
                                                                foreach ($question['correctOrder'] as $value) {
                                                                    $option_text = $value;
                                                                    if (isset($question['itemsText']) && is_string($question['itemsText'])) {
                                                                        $items_array = array_map('trim', explode("\n", $question['itemsText']));
                                                                        if (isset($items_array[$value])) {
                                                                            $option_text = $items_array[$value];
                                                                        }
                                                                    }
                                                                    echo '<li>' . esc_html($option_text) . '</li>';
                                                                }
                                                                echo '</ol>';
                                                            } elseif ($question['type'] === 'cloze' && isset($question['correctFills'])) {
                                                                echo '<ul>';
                                                                foreach ($question['correctFills'] as $fill) {
                                                                    echo '<li>' . esc_html($fill) . '</li>';
                                                                }
                                                                echo '</ul>';
                                                            } elseif ($question['type'] === 'true-false' && isset($question['correctValue'])) {
                                                                $tf_value = $question['correctValue'] ? __('Verdadero', 'englishline-test') : __('Falso', 'englishline-test');
                                                                echo '<p>' . esc_html($tf_value) . '</p>';
                                                            } elseif (($question['type'] === 'select' || $question['type'] === 'radio') && isset($question['correctOption'])) {
                                                                $option_text = $question['correctOption'];
                                                                if (isset($question['options']) && is_array($question['options'])) {
                                                                    foreach ($question['options'] as $opt_idx => $opt) {
                                                                        if ((string)$opt_idx === (string)$question['correctOption'] && isset($opt['text'])) {
                                                                            $option_text = $opt['text'];
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                                echo '<p>' . esc_html($option_text) . '</p>';
                                                            } elseif (isset($question['correctAnswer']) || isset($question['correct_answer'])) {
                                                                $correct_answer = isset($question['correctAnswer']) ? $question['correctAnswer'] : $question['correct_answer'];
                                                                echo '<p>' . esc_html($correct_answer) . '</p>';
                                                            }
                                                            ?>
                                                        </div>

                                                        <?php if (isset($question['isGradable']) && $question['isGradable']): ?>
                                                            <div class="result-question-evaluation">
                                                                <label>
                                                                    <input type="checkbox"
                                                                           name="is_correct[<?php echo esc_attr($question_id); ?>]"
                                                                           value="1"
                                                                           class="is-correct-checkbox"
                                                                           <?php checked($is_correct); ?>>
                                                                    <?php _e('Marcar como correcta', 'englishline-test'); ?>
                                                                </label>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="result-error">
                                <p><?php _e('No se pudo cargar la estructura del formulario.', 'englishline-test'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="result-actions">
                        <button type="button" id="calculate-score" class="button"><?php _e('Calcular calificación automática', 'englishline-test'); ?></button>
                        <button type="submit" name="englishline_save_review" class="button button-primary"><?php _e('Guardar calificación', 'englishline-test'); ?></button>
                        <?php if ($result->status === 'approved' || $result->status === 'failed'): ?>
                            <button type="submit" name="englishline_send_email" class="button"><?php _e('Reenviar email de calificación', 'englishline-test'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        $('#calculate-score').on('click', function() {
            let totalGradable = <?php echo $total_gradable_questions; ?>;
            let correctCount = 0;

            $('.is-correct-checkbox').each(function() {
                if ($(this).is(':checked')) {
                    correctCount++;
                }
            });

            if (totalGradable > 0) {
                let score = Math.round((correctCount / totalGradable) * 100);
                $('#score').val(score);
                if (score >= 60) {
                    $('#status').val('approved');
                } else {
                    $('#status').val('failed');
                }
            } else {
                alert('No hay preguntas graduables para calcular la calificación.');
            }
        });

        $('.is-correct-checkbox').on('change', function() {
            let $question = $(this).closest('.result-question');
            if ($(this).is(':checked')) {
                $question.removeClass('result-incorrect').addClass('result-correct');
            } else {
                $question.removeClass('result-correct').addClass('result-incorrect');
            }
        });
    });
</script>