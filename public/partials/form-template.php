<?php

/**
 * Template para renderizar un formulario de prueba de inglés
 */

// Asegurarnos de que no se accede directamente a este archivo
if (!defined('ABSPATH')) {
    exit;
}

// Función para decodificar secuencias Unicode
function decode_unicode_sequences($text)
{
    if (!is_string($text)) {
        return $text;
    }

    // Patrón para detectar secuencias Unicode como 'u00f3'
    return preg_replace_callback('/u([0-9a-fA-F]{4})/', function ($matches) {
        return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
    }, $text);
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
}

// Modo de depuración solo para administradores
$debug_mode = current_user_can('manage_options');

// Decodificar los datos del formulario desde JSON
$form_data = null;
$raw_data = $form->form_data;

// Proceso de decodificación JSON optimizado
try {
    if (substr($raw_data, 0, 1) === '"' && substr($raw_data, -1) === '"') {
        $raw_data = substr($raw_data, 1, -1);
    }

    if (strpos($raw_data, '\\\\') !== false) {
        $raw_data = stripslashes($raw_data);
    }

    $form_data = json_decode($raw_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $form_data = json_decode(stripslashes($raw_data), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $form_data = json_decode(html_entity_decode($raw_data, ENT_QUOTES, 'UTF-8'), true);
        }
    }

    if (is_array($form_data) && isset($form_data[0]) && !isset($form_data['sections'])) {
        $form_data = array('sections' => $form_data);
    }


    if (is_object($form_data)) {
        $form_data = json_decode(json_encode($form_data), true);
    }

    if (is_array($form_data)) {
        process_text_encoding($form_data);
    }
} catch (Exception $e) {
    
}

// Si no hay datos válidos, mostrar mensaje para administradores o salir
if (!is_array($form_data) || !isset($form_data['sections'])) {
    if ($debug_mode) {
        echo '<div class="englishline-form-error" style="color:red;padding:15px;">';
        echo '<p>Error: No se pudieron decodificar los datos del formulario.</p>';
        echo '</div>';
    }
    return;
}

// Preparar los datos de formulario
$sections = $form_data['sections'];
$total_sections = count($sections);

if ($total_sections === 0) {
    return;
}

// Cargar recursos necesarios
wp_enqueue_style('dashicons');
wp_enqueue_script('jquery-ui-sortable');
wp_enqueue_script('jquery-ui-draggable');
wp_enqueue_script('jquery-ui-droppable');
?>

<div class="englishline-form-container" id="englishline-form-<?php echo esc_attr($form->id); ?>" data-form-id="<?php echo esc_attr($form->id); ?>">
    <div class="englishline-form-header">
        <h2 class="englishline-form-title"><?php echo esc_html($form->title); ?></h2>
        <?php if (!empty($form->description)): ?>
            <div class="englishline-form-description"><?php echo wp_kses_post($form->description); ?></div>
        <?php endif; ?>
    </div>

    <form class="englishline-test-form" method="post">
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form->id); ?>">
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('englishline_test_public_nonce')); ?>">

        <?php if ($total_sections > 1): ?>
            <div class="englishline-form-steps">
                <?php for ($i = 0; $i < $total_sections; $i++): ?>
                    <div class="englishline-form-step <?php echo ($i === 0) ? 'active' : ''; ?>" data-step="<?php echo $i; ?>">
                        <span class="step-number"><?php echo ($i + 1); ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <div class="englishline-form-content">
            <?php
            // Renderizar cada sección como un paso
            foreach ($sections as $section_index => $section):
                $section_class = ($section_index === 0) ? 'active' : '';
            ?>
                <div class="englishline-form-step-content <?php echo $section_class; ?>" data-step="<?php echo $section_index; ?>">
                    <?php if (!empty($section['title'])): ?>
                        <h3 class="englishline-section-title"><?php echo esc_html($section['title']); ?></h3>

                        <?php if (!empty($section['time_limit']) && $section['time_limit'] > 0): ?>
                            <div class="englishline-timer-container" data-section="<?php echo $section_index; ?>">
                                <span class="timer-label"><?php _e('Tiempo restante:', 'englishline-test'); ?></span>
                                <span class="englishline-timer"
                                    data-minutes="<?php echo intval($section['time_limit']); ?>"
                                    data-section="<?php echo $section_index; ?>">
                                    <?php echo sprintf('%02d:%02d', intval($section['time_limit']), 0); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($section['description'])): ?>
                        <div class="englishline-section-description"><?php echo wp_kses_post($section['description']); ?></div>
                    <?php endif; ?>

                    <?php
                    // Renderizar las preguntas de esta sección
                    if (!empty($section['questions']) && is_array($section['questions'])):
                        foreach ($section['questions'] as $question_index => $question):
                            if (!isset($question['type'])) continue;
                            $question_id = "question_{$section_index}_{$question_index}";
                    ?>
                            <div class="englishline-question" data-question-type="<?php echo esc_attr($question['type']); ?>">
                                <?php if (in_array($question['type'], ['title', 'text'])): ?>
                                    <div class="form-question-text size-<?php echo esc_attr($question['title_size'] ?? 'h2'); ?> align-<?php echo esc_attr($question['title_alignment'] ?? 'center'); ?>">
                                        <?php echo wp_kses_post($question['text'] ?? ''); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="form-question-prompt">
                                        <?php echo wp_kses_post($question['text'] ?? $question['prompt'] ?? ''); ?>
                                    </div>
                                <?php endif; ?>

                                <?php switch ($question['type']):
                                    case 'title':
                                        // Ya renderizado arriba
                                        break;

                                    case 'text':
                                    case 'short_answer': ?>
                                        <div class="form-question-answer">
                                            <input type="text" name="form_data[<?php echo $question_id; ?>]"
                                                placeholder="<?php echo esc_attr($question['placeholder'] ?? ''); ?>"
                                                <?php echo !empty($question['required']) ? 'required' : ''; ?>>
                                        </div>
                                    <?php break;

                                    case 'textarea':
                                    case 'long_answer': ?>
                                        <div class="form-question-answer">
                                            <textarea name="form_data[<?php echo $question_id; ?>]"
                                                rows="5"
                                                placeholder="<?php echo esc_attr($question['placeholder'] ?? ''); ?>"
                                                <?php echo !empty($question['required']) ? 'required' : ''; ?>></textarea>
                                        </div>
                                    <?php break;

                                    case 'select': ?>
                                        <div class="form-question-answer">
                                            <select name="form_data[<?php echo $question_id; ?>]"
                                                <?php echo !empty($question['required']) ? 'required' : ''; ?>>
                                                <option value=""><?php _e('- Seleccionar -', 'englishline-test'); ?></option>
                                                <?php
                                                if (!empty($question['options']) && is_array($question['options'])):
                                                    foreach ($question['options'] as $option_index => $option): ?>
                                                        <option value="<?php echo esc_attr($option_index); ?>">
                                                            <?php echo esc_html(is_array($option) ? ($option['text'] ?? '') : $option); ?>
                                                        </option>
                                                <?php endforeach;
                                                endif; ?>
                                            </select>
                                        </div>
                                    <?php break;

                                    case 'radio': ?>
                                        <div class="form-question-options">
                                            <?php
                                            if (!empty($question['options']) && is_array($question['options'])):
                                                foreach ($question['options'] as $option_index => $option): ?>
                                                    <div class="form-question-option">
                                                        <input type="radio"
                                                            id="option_<?php echo "{$question_id}_{$option_index}"; ?>"
                                                            name="form_data[<?php echo $question_id; ?>]"
                                                            value="<?php echo esc_attr($option_index); ?>"
                                                            <?php echo !empty($question['required']) ? 'required' : ''; ?>>
                                                        <label for="option_<?php echo "{$question_id}_{$option_index}"; ?>">
                                                            <?php echo esc_html(is_array($option) ? ($option['text'] ?? '') : $option); ?>
                                                        </label>
                                                    </div>
                                            <?php endforeach;
                                            endif; ?>
                                        </div>
                                    <?php break;

                                    case 'checkbox': ?>
                                        <div class="form-question-options">
                                            <?php
                                            if (!empty($question['options']) && is_array($question['options'])):
                                                foreach ($question['options'] as $option_index => $option): ?>
                                                    <div class="form-question-option">
                                                        <input type="checkbox"
                                                            id="checkbox_<?php echo "{$question_id}_{$option_index}"; ?>"
                                                            name="form_data[<?php echo $question_id; ?>][]"
                                                            value="<?php echo esc_attr($option_index); ?>">
                                                        <label for="checkbox_<?php echo "{$question_id}_{$option_index}"; ?>">
                                                            <?php echo esc_html(is_array($option) ? ($option['text'] ?? '') : $option); ?>
                                                        </label>
                                                    </div>
                                            <?php endforeach;
                                            endif; ?>
                                        </div>
                                    <?php break;

                                    case 'image': ?>
                                        <?php if (!empty($question['image_id'])):
                                            $image_url = wp_get_attachment_url($question['image_id']);
                                            if ($image_url): ?>
                                                <div class="question-image-container">
                                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($question['text'] ?? 'Imagen'); ?>">
                                                </div>
                                        <?php endif;
                                        endif; ?>
                                        <div class="form-question-answer">
                                            <textarea name="form_data[<?php echo $question_id; ?>]"
                                                rows="5"
                                                placeholder="<?php echo esc_attr($question['placeholder'] ?? __('Escribe tu respuesta aquí', 'englishline-test')); ?>"
                                                <?php echo !empty($question['required']) ? 'required' : ''; ?>></textarea>
                                        </div>
                                    <?php break;

                                    case 'true-false': ?>
                                        <div class="form-question-true-false">
                                            <div class="true-false-options">
                                                <span class="true-false-option">
                                                    <input type="radio"
                                                        id="true_<?php echo $question_id; ?>"
                                                        name="form_data[<?php echo $question_id; ?>]"
                                                        value="true"
                                                        <?php echo !empty($question['required']) ? 'required' : ''; ?>>
                                                    <label for="true_<?php echo $question_id; ?>"><?php _e('Verdadero', 'englishline-test'); ?></label>
                                                </span>
                                                <span class="true-false-option">
                                                    <input type="radio"
                                                        id="false_<?php echo $question_id; ?>"
                                                        name="form_data[<?php echo $question_id; ?>]"
                                                        value="false"
                                                        <?php echo !empty($question['required']) ? 'required' : ''; ?>>
                                                    <label for="false_<?php echo $question_id; ?>"><?php _e('Falso', 'englishline-test'); ?></label>
                                                </span>
                                            </div>
                                        </div>
                                    <?php break;
                                    case 'cloze': ?>
                                        <div class="form-question-cloze">
                                            <?php
                                            if (!empty($question['cloze_text'])) {
                                                $cloze_text = $question['cloze_text'];
                                                $pattern = '/\[(.*?)\]/';
                                                $count = 0;

                                                $cloze_html = preg_replace_callback($pattern, function ($matches) use (&$count, $question_id) {
                                                    $answer = !empty($matches[1]) ? $matches[1] : '';
                                                    $input = '<input type="text" name="form_data[' . $question_id . '][' . $count . ']" class="cloze-blank" data-answer="' . esc_attr($answer) . '">';

                                                    $count++;
                                                    return $input;
                                                }, $cloze_text);

                                                echo $cloze_html;
                                            }
                                            ?>
                                        </div>
                                    <?php break;

                                    case 'ordering': ?>
                                        <div class="form-question-ordering">
                                            <?php
                                            $items = [];
                                            if (!empty($question['items_text'])) {
                                                $items = array_filter(array_map('trim', explode("\n", $question['items_text'])));
                                            }

                                            $shuffled_items = $items;
                                            shuffle($shuffled_items);

                                            if (count($shuffled_items) > 0): ?>
                                                <ul class="ordering-list" data-question="<?php echo $question_id; ?>">
                                                    <?php foreach ($shuffled_items as $index => $item): ?>
                                                        <li class="ordering-item" data-original-index="<?php echo array_search($item, $items); ?>">
                                                            <span class="dashicons dashicons-menu"></span>
                                                            <span class="ordering-text"><?php echo esc_html($item); ?></span>
                                                            <input type="hidden" name="form_data[<?php echo $question_id; ?>][]" value="<?php echo array_search($item, $items); ?>">
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p><?php _e('No hay elementos para ordenar.', 'englishline-test'); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php break;

                                        echo '<p>' . sprintf(__('Tipo de pregunta no soportado: %s', 'englishline-test'), esc_html($question['type'])) . '</p>';
                                endswitch;

                                // Mostrar pista si existe
                                if (!empty($question['hint'])): ?>
                                    <div class="form-question-hint">
                                        <span class="dashicons dashicons-info-outline"></span>
                                        <?php echo wp_kses_post($question['hint']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                    <?php endforeach;
                    endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="englishline-form-step-content" data-step="<?php echo $total_sections; ?>" style="display:none;">
                <h3 class="englishline-section-title"><?php _e('Datos de contacto', 'englishline-test'); ?></h3>
                <p class="englishline-section-description"><?php _e('Por favor, introduce tus datos para recibir los resultados.', 'englishline-test'); ?></p>

                <div class="englishline-user-data">
                    <div class="form-row">
                        <div class="form-column">
                            <div class="englishline-question">
                                <div class="form-question-prompt">
                                    <?php _e('Nombre', 'englishline-test'); ?>
                                </div>
                                <div class="form-question-answer">
                                    <input type="text" name="user_data[first_name]" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-column">
                            <div class="englishline-question">
                                <div class="form-question-prompt">
                                    <?php _e('Apellido', 'englishline-test'); ?>
                                </div>
                                <div class="form-question-answer">
                                    <input type="text" name="user_data[last_name]" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-column">
                            <div class="englishline-question">
                                <div class="form-question-prompt">
                                    <?php _e('Correo electrónico', 'englishline-test'); ?>
                                </div>
                                <div class="form-question-answer">
                                    <input type="email" name="user_data[email]" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-column">
                            <div class="englishline-question">
                                <div class="form-question-prompt">
                                    <?php _e('Teléfono', 'englishline-test'); ?>
                                </div>
                                <div class="form-question-answer">
                                    <input type="tel" name="user_data[phone]">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row privacy-row">
                        <div class="englishline-question">
                            <div class="form-question-options">
                                <div class="form-question-option">
                                    <?php
                                    $settings = get_option('englishline_test_settings', array());
                                    $terms_url = !empty($settings['terms_url']) ? $settings['terms_url'] : '#';
                                    $privacy_url = !empty($settings['privacy_url']) ? $settings['privacy_url'] : get_privacy_policy_url();
                                    ?>
                                    <input type="checkbox"
                                        id="privacy_acceptance"
                                        name="user_data[privacy_acceptance]"
                                        value="1"
                                        required>
                                    <label for="privacy_acceptance">
                                        <?php echo sprintf(
                                            __('He leído y acepto los %1$s y las %2$s', 'englishline-test'),
                                            '<a href="' . esc_url($terms_url) . '" target="_blank">' . __('Términos y Condiciones', 'englishline-test') . '</a>',
                                            '<a href="' . esc_url($privacy_url) . '" target="_blank">' . __('Políticas de Tratamiento de Datos', 'englishline-test') . '</a>'
                                        ); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="englishline-form-nav">
            <?php if ($total_sections >= 1): ?>
                <button type="button" class="englishline-form-prev button" style="display:none;">
                    <span class="button-icon"><i class="dashicons dashicons-arrow-left-alt"></i></span>
                    <span class="button-text"><?php _e('Anterior', 'englishline-test'); ?></span>
                </button>
            <?php endif; ?>

            <button type="button" class="englishline-form-next button button-primary">
                <span class="button-text"><?php _e('Siguiente', 'englishline-test'); ?></span>
                <span class="button-icon"><i class="dashicons dashicons-arrow-right-alt"></i></span>
            </button>

            <button type="button" class="englishline-form-submit button button-primary" style="display:none;">
                <span class="button-text"><?php _e('Enviar', 'englishline-test'); ?></span>
                <span class="button-icon"><i class="dashicons dashicons-yes"></i></span>
            </button>
        </div>

        <div class="englishline-form-error" style="display:none;"></div>
        <div class="englishline-form-success" style="display:none;"></div>
    </form>

    <div class="englishline-loader" style="display:none;">
        <div class="englishline-spinner"></div>
    </div>
</div>