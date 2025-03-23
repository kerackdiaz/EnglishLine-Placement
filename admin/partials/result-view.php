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

// Función para decodificar secuencias Unicode
function decode_unicode_sequences($text)
{
    if (!is_string($text)) {
        return $text;
    }

    // Reemplazar secuencias \uXXXX (4 dígitos hexadecimales)
    $text = preg_replace_callback('/u([0-9a-fA-F]{4})/', function ($matches) {
        return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
    }, $text);

    // También manejar secuencias como \u00f3 sin barras inversas
    $text = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
        return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
    }, $text);

    return $text;
}

// Función recursiva para procesar todo el array de datos
function process_text_encoding(&$data)
{
    if (is_array($data)) {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                process_text_encoding($value);
            } elseif (is_string($value)) {
                $value = decode_unicode_sequences($value);
            }
        }
    } elseif (is_string($data)) {
        $data = decode_unicode_sequences($data);
    }
    return $data;
}

// Procesar envío del formulario de calificación si existe
$message = '';
$message_type = '';

if (isset($_POST['englishline_save_review']) && check_admin_referer('englishline_review_result_' . $result_id)) {
    $score = isset($_POST['score']) ? intval($_POST['score']) : null;
    $comments = isset($_POST['comments']) ? sanitize_textarea_field($_POST['comments']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'reviewed';

    // Validar score
    if ($score !== null && ($score < 0 || $score > 100)) {
        $score = null;
    }

    // Recopilar calificaciones individuales si se enviaron
    $individual_scores = [];
    if (isset($_POST['question_score']) && is_array($_POST['question_score'])) {
        foreach ($_POST['question_score'] as $question_id => $q_score) {
            // Solo incluir si el checkbox correspondiente está marcado
            if (isset($_POST['include_in_score'][$question_id])) {
                $q_score = intval($q_score);
                if ($q_score >= 0 && $q_score <= 100) {
                    $individual_scores[$question_id] = $q_score;
                }
            }
        }
    
        // Calcular score automáticamente si no se especificó uno
        if ($score === null && !empty($individual_scores)) {
            $total_questions = count($individual_scores);
            $sum_scores = array_sum($individual_scores);
            $score = $total_questions > 0 ? round($sum_scores / $total_questions, 2) : null;
        }
    }

    // Actualizar en la base de datos
    $updated = $wpdb->update(
        $wpdb->prefix . 'englishline_results',
        array(
            'score' => $score,
            'feedback' => $comments,
            'status' => $status,
            'individual_scores' => json_encode($individual_scores),
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

        // Recargar datos actualizados
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
        // Cargar el helper de email
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/helpers/class-email-helper.php';
        $email_helper = new Email_Helper();

        // Intentar enviar el correo
        $email_sent = $email_helper->send_grade_notification($result_id, $score, $comments);

        // Actualizar mensaje con información del correo
        if ($email_sent) {
            $message .= ' ' . __('Notificación enviada por correo electrónico.', 'englishline-test');
        } else {
            $message .= ' ' . __('No se pudo enviar la notificación por correo electrónico.', 'englishline-test');
        }
    }
}

// Decodificar los datos del formulario
$form_data = json_decode($result->form_data, true);
$form_data = process_text_encoding($form_data);

$user_data = [];
$answers = [];

if (is_array($form_data)) {
    if (isset($form_data['user_data'])) {
        $user_data = $form_data['user_data'];
    }

    if (isset($form_data['form_data'])) {
        $answers = $form_data['form_data'];
    }
}

// Obtener estructura del formulario para mostrar las preguntas
$form_structure = [];
if (!empty($result->form_structure)) {
    $raw_structure = $result->form_structure;

    // Limpiar el JSON antes de decodificar
    if (substr($raw_structure, 0, 1) === '"' && substr($raw_structure, -1) === '"') {
        $raw_structure = substr($raw_structure, 1, -1);
    }

    if (strpos($raw_structure, '\\\\') !== false) {
        $raw_structure = stripslashes($raw_structure);
    }


    // Decodificar
    $form_structure = json_decode($raw_structure, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $form_structure = json_decode(stripslashes($raw_structure), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
        }
    }

    if (is_array($form_structure)) {

        $form_structure = process_text_encoding($form_structure);

        // Si es un array simple, envolverlo en 'sections'
        if (isset($form_structure[0]) && !isset($form_structure['sections'])) {
            $form_structure = ['sections' => $form_structure];
        }

       
    } 
}


// Cargar las calificaciones individuales si existen
$individual_scores = [];
if (!empty($result->individual_scores)) {
    $individual_scores = json_decode($result->individual_scores, true);
    if (!is_array($individual_scores)) {
        $individual_scores = [];
    }
}

// Función para obtener el tipo legible de pregunta
function get_question_type_label($type)
{
    $types = [
        'text' => 'Respuesta corta',
        'textarea' => 'Respuesta larga',
        'select' => 'Desplegable',
        'radio' => 'Selección única',
        'checkbox' => 'Selección múltiple',
        'image' => 'Imagen',
        'cloze' => 'Texto con huecos',
        'ordering' => 'Ordenación',
        'drag-drop' => 'Arrastrar y soltar',
        'matching' => 'Emparejar',
        'true-false' => 'Verdadero/Falso'
    ];

    return isset($types[$type]) ? $types[$type] : $type;
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
                // Buscar el texto de la opción según el índice
                $option_text = $item;
                if (isset($question['options']) && is_array($question['options'])) {
                    foreach ($question['options'] as $opt_idx => $opt) {
                        if ($opt_idx == $item && isset($opt['text'])) {
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
                // Inicialmente asumimos que el valor es el índice
                $option_text = $item;

                // Para preguntas de ordenación
                if (isset($question['items_text'])) {
                    $items_array = explode("\n", $question['items_text']);
                    $items_array = array_map('trim', $items_array);
                    if (isset($items_array[$item])) {
                        $option_text = $items_array[$item];
                    }
                }
                else if (isset($question['options']) && is_array($question['options'])) {
                    if (isset($question['options'][$item]['text'])) {
                        $option_text = $question['options'][$item]['text'];
                    } else {
                        foreach ($question['options'] as $opt_idx => $opt) {
                            if ((string)$opt_idx === (string)$item && isset($opt['text'])) {
                                $option_text = $opt['text'];
                                break;
                            }
                        }
                    }
                }

                echo '<li>' . esc_html($option_text) . '</li>';
            }
            echo '</ol>';
        } elseif ($question_type == 'cloze') {
            if (isset($question['cloze_text']) && !empty($question['cloze_text'])) {
                $cloze_text = $question['cloze_text'];
                $blank_count = 0;

                $filled_text = preg_replace_callback('/\[blank\]/', function ($matches) use ($answer, &$blank_count) {
                    $user_answer = isset($answer[$blank_count]) ? '<span class="user-answer">' . esc_html($answer[$blank_count]) . '</span>' : '______';
                    $blank_count++;
                    return $user_answer;
                }, $cloze_text);

                echo '<div class="cloze-text-filled">' . $filled_text . '</div>';
                echo '<div class="cloze-answers">';
                echo '<strong>' . __('Respuestas:', 'englishline-test') . '</strong>';
                echo '<ol>';
                foreach ($answer as $blank_answer) {
                    echo '<li>' . esc_html($blank_answer) . '</li>';
                }
                echo '</ol>';
                echo '</div>';
            } else {
                // Fallback si no tenemos el texto completo
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
            foreach ($question['options'] as $opt_idx => $opt) {
                if ((string)$opt_idx === (string)$answer && isset($opt['text'])) {
                    $option_text = $opt['text'];
                    break;
                }
            }
            echo '<p>' . esc_html($option_text) . '</p>';
        } else {
            echo '<p>' . esc_html($answer) . '</p>';
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
if (isset($form_structure['sections'])) {
    foreach ($form_structure['sections'] as $section) {
        if (isset($section['questions'])) {
            $total_questions += count($section['questions']);
        }
    }
}

$value_per_question = $total_questions > 0 ? round(100 / $total_questions, 2) : 0;

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

// Determinar el email del usuario
$user_email = '';
if (!empty($result->user_email)) {
    $user_email = $result->user_email;
} elseif (!empty($result->user_email_from_wp)) {
    $user_email = $result->user_email_from_wp;
} elseif (!empty($user_data['email'])) {
    $user_email = $user_data['email'];
}

// Calcular el tiempo transcurrido
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
                            <input type="number" id="score" name="score" min="0" max="100" value="<?php echo $result->score !== null ? esc_attr($result->score) : ''; ?>" placeholder="Calificación automática">
                            <p class="description"><?php _e('Deja en blanco para calcular automáticamente en base a las calificaciones individuales.', 'englishline-test'); ?></p>
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
                        <h3><?php _e('Respuestas y Calificación Individual', 'englishline-test'); ?></h3>

                        <?php if (isset($form_structure['sections'])): ?>
                            <?php foreach ($form_structure['sections'] as $section_index => $section): ?>
                                <div class="result-section">
                                    <div class="result-section-header">
                                        <div class="result-section-title">
                                            <?php echo esc_html($section['title']); ?>
                                        </div>
                                        <div class="result-section-info">
                                            <?php echo sprintf(__('Valor por pregunta: %.2f puntos', 'englishline-test'), $value_per_question); ?>
                                        </div>
                                    </div>

                                    <?php if (isset($section['questions'])): ?>
                                        <?php foreach ($section['questions'] as $question_index => $question): ?>
                                            <?php
                                            if (isset($question['type']) && $question['type'] === 'title') {
                                                continue;
                                            }

                                            $question_id = "question_{$section_index}_{$question_index}";
                                            $user_answer = isset($answers[$question_id]) ? $answers[$question_id] : null;
                                            $question_score = isset($individual_scores[$question_id]) ? $individual_scores[$question_id] : '';

                                            $has_correct_answer = false;
                                            $is_correct = false;

                                            if (
                                                isset($question['correct_answer']) ||
                                                (isset($question['options']) && is_array($question['options']) &&
                                                    array_filter($question['options'], function ($opt) {
                                                        return isset($opt['correct']) && $opt['correct'];
                                                    }))
                                            ) {
                                                $has_correct_answer = true;

                                                if (isset($question['correct_answer'])) {
                                                    $is_correct = ($user_answer == $question['correct_answer']);
                                                } else {
                                                    $correct_options = array_filter($question['options'], function ($opt) {
                                                        return isset($opt['correct']) && $opt['correct'];
                                                    });

                                                    if (!empty($correct_options)) {
                                                        $correct_values = array_map(function ($opt) {
                                                            return $opt['value'];
                                                        }, $correct_options);

                                                        if (is_array($user_answer)) {
                                                            $is_correct = count(array_diff($correct_values, $user_answer)) === 0;
                                                        } else {
                                                            $is_correct = in_array($user_answer, $correct_values);
                                                        }
                                                    }
                                                }
                                            }

                                            $question_class = $has_correct_answer ?
                                                ($is_correct ? 'result-correct' : 'result-incorrect') : '';
                                            ?>

                                            <div class="result-question <?php echo esc_attr($question_class); ?>">
                                                <div class="result-question-header">
                                                    <div class="result-question-number"><?php echo $question_index + 1; ?>.</div>
                                                    <div class="result-question-text">
                                                        <?php echo esc_html($question['text']); ?>
                                                    </div>
                                                    <div class="result-question-type">
                                                        <span class="badge badge-<?php echo $has_correct_answer ? 'primary' : 'info'; ?>">
                                                            <?php echo esc_html(get_question_type_label($question['type'])); ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="result-question-content">
                                                    <?php if ($question['type'] === 'image' && !empty($question['image_id'])): ?>
                                                        <?php display_question_image($question['image_id']); ?>
                                                    <?php endif; ?>

                                                    <div class="result-answer">
                                                        <div class="result-answer-label"><?php _e('Respuesta del usuario:', 'englishline-test'); ?></div>
                                                        <?php if ($user_answer !== null): ?>
                                                            <?php
                                                            if ($question['type'] === 'true-false') {
                                                                $tf_value = $user_answer ? __('Verdadero', 'englishline-test') : __('Falso', 'englishline-test');
                                                                echo '<p>' . esc_html($tf_value) . '</p>';
                                                            } else {
                                                                render_user_answer($user_answer, $question['type'], $question);
                                                            }
                                                            ?>
                                                        <?php else: ?>
                                                            <p class="empty-answer"><?php _e('Sin respuesta', 'englishline-test'); ?></p>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php
                                                    if ($has_correct_answer && $question['type'] !== 'true-false'):
                                                    ?>
                                                        <div class="result-correct-answer">
                                                            <div class="result-answer-label"><?php _e('Respuesta correcta:', 'englishline-test'); ?></div>
                                                            <?php
                                                            if (isset($question['correct_answer'])) {
                                                                echo '<p>' . esc_html($question['correct_answer']) . '</p>';
                                                            } elseif (isset($question['options'])) {
                                                                $correct_options = array_filter($question['options'], function ($opt) {
                                                                    return isset($opt['correct']) && $opt['correct'];
                                                                });

                                                                if (!empty($correct_options)) {
                                                                    echo '<ul>';
                                                                    foreach ($correct_options as $option) {
                                                                        echo '<li>' . esc_html($option['text']) . '</li>';
                                                                    }
                                                                    echo '</ul>';
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="result-question-score">
                                                        <label for="question-score-<?php echo esc_attr($question_id); ?>"><?php _e('Calificación:', 'englishline-test'); ?></label>
                                                        <div class="score-controls">
                                                            <input type="number" id="question-score-<?php echo esc_attr($question_id); ?>"
                                                                name="question_score[<?php echo esc_attr($question_id); ?>]"
                                                                min="0" max="100"
                                                                value="<?php echo esc_attr($question_score); ?>"
                                                                class="question-score-input"
                                                                placeholder="0-100"
                                                                <?php echo (isset($question['type']) && $question['type'] === 'textarea') ? 'disabled' : ''; ?>>

                                                            <label class="include-in-score">
                                                                <input type="checkbox"
                                                                    name="include_in_score[<?php echo esc_attr($question_id); ?>]"
                                                                    class="include-score-checkbox"
                                                                    <?php echo (isset($question['type']) && $question['type'] === 'textarea') ? '' : 'checked'; ?>>
                                                                <?php _e('Incluir en calificación', 'englishline-test'); ?>
                                                            </label>
                                                        </div>
                                                    </div>
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
        $('.include-score-checkbox').on('change', function() {
            let scoreInput = $(this).closest('.score-controls').find('.question-score-input');
            if($(this).is(':checked')) {
                scoreInput.prop('disabled', false);
            } else {
                scoreInput.prop('disabled', true);
                scoreInput.val(''); 
            }
        });
        
        $('.include-score-checkbox').trigger('change');
        
        // Calcular calificación automática
        $('#calculate-score').on('click', function() {
            let totalQuestions = 0;
            let totalScore = 0;

            $('.question-score-input').each(function() {
                let checkbox = $(this).closest('.score-controls').find('.include-score-checkbox');
                if(checkbox.is(':checked')) {
                    let score = parseInt($(this).val());
                    if (!isNaN(score)) {
                        totalScore += score;
                        totalQuestions++;
                    }
                }
            });

            if (totalQuestions > 0) {
                let averageScore = Math.round(totalScore / totalQuestions);
                $('#score').val(averageScore);

                if (averageScore >= 60) {
                    $('#status').val('approved');
                } else {
                    $('#status').val('failed');
                }
            } else {
                alert('Por favor, califica al menos una pregunta primero o selecciona qué preguntas incluir en la calificación.');
            }
        });


        $('form').on('submit', function(e) {
            let valid = true;
            let errorMessage = '';

            $('.question-score-input').each(function() {
                if(!$(this).prop('disabled')) {
                    let value = $(this).val();
                    if (value !== '') {
                        let score = parseInt(value);
                        if (isNaN(score) || score < 0 || score > 100) {
                            valid = false;
                            errorMessage = 'Las calificaciones deben ser números entre 0 y 100.';
                            $(this).addClass('error');
                            return false;
                        }
                    }
                }
            });

            if (!valid) {
                alert(errorMessage);
                e.preventDefault();
            }
        });

        $('.question-score-input').on('input', function() {
            $(this).removeClass('error');
        });
    });
</script>