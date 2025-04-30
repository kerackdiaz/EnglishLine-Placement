<?php
if (!defined('ABSPATH')) {
    exit;
}

function decode_unicode_sequences($text) {
    if (!is_string($text)) {
        return $text;
    }
    return preg_replace_callback('/u([0-9a-fA-F]{4})/', function ($matches) {
        return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
    }, $text);
}

function process_text_encoding(&$data) {
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

$debug_mode = current_user_can('manage_options');
$form_data = null;
$raw_data = $form->form_data;

try {
    // Decodificar directamente, los datos ya deben estar en formato JSON válido
    $form_data = json_decode($raw_data, true);
    
    // Si hay un error, intentar con stripslashes por si acaso
    if (json_last_error() !== JSON_ERROR_NONE) {
        $form_data = json_decode(stripslashes($raw_data), true);
    }
    
    // Normalizar la estructura de datos
    if (is_array($form_data) && isset($form_data[0]) && !isset($form_data['sections'])) {
        $form_data = array('sections' => $form_data);
    }
} catch (Exception $e) {
    if ($debug_mode) {
        echo '<div class="englishline-form-error" style="color:red;padding:15px;">';
        echo '<p>Error de excepción: ' . $e->getMessage() . '</p>';
        echo '</div>';
    }
}

if (!is_array($form_data) || !isset($form_data['sections'])) {
    if ($debug_mode) {
        echo '<div class="englishline-form-error" style="color:red;padding:15px;">';
        echo '<p>Error: No se pudieron decodificar los datos del formulario.</p>';
        echo '<p>Datos brutos: <pre>' . esc_html(substr($raw_data, 0, 100)) . '...</pre></p>';
        echo '</div>';
    }
    return;
}

$sections = $form_data['sections'];
$total_sections = count($sections);

if ($total_sections === 0) {
    return;
}

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
            foreach ($sections as $section_index => $section):
                $section_class = ($section_index === 0) ? 'active' : '';
                
                $time_limit = null;
                if (!empty($section['timeLimit'])) {
                    $time_limit = intval($section['timeLimit']);
                } elseif (!empty($section['time_limit'])) {
                    $time_limit = intval($section['time_limit']);
                }
            ?>
                <div class="englishline-form-step-content <?php echo $section_class; ?>" data-step="<?php echo $section_index; ?>">
                    <?php if (!empty($section['title'])): ?>
                        <h3 class="englishline-section-title"><?php echo esc_html($section['title']); ?></h3>

                        <?php if (!empty($time_limit) && $time_limit > 0): ?>
                            <div class="englishline-timer-container" data-section="<?php echo $section_index; ?>">
                                <span class="timer-label"><?php _e('Tiempo restante:', 'englishline-test'); ?></span>
                                <span class="englishline-timer"
                                    data-minutes="<?php echo $time_limit; ?>"
                                    data-section="<?php echo $section_index; ?>">
                                    <?php echo sprintf('%02d:%02d', $time_limit, 0); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($section['description'])): ?>
                        <div class="englishline-section-description"><?php echo wp_kses_post($section['description']); ?></div>
                    <?php endif; ?>

                    <?php
                    if (!empty($section['questions']) && is_array($section['questions'])):
                        foreach ($section['questions'] as $question_index => $question):
                            if (!isset($question['type'])) continue;
                            $question_id = isset($question['id']) && !empty($question['id']) 
                                ? esc_attr($question['id']) 
                                : "question_{$section_index}_{$question_index}";
                            
                            $is_gradable = true;
                            if (isset($question['isGradable'])) {
                                $is_gradable = $question['isGradable'] !== false;
                            }
                            
                            if ($question['type'] === 'true-false') {
                                $is_gradable = true;
                            }
                    ?>
                            <div class="englishline-question" 
                                data-question-type="<?php echo esc_attr($question['type']); ?>"
                                data-is-gradable="<?php echo $is_gradable ? 'true' : 'false'; ?>"
                                data-question-id="<?php echo $question_id; ?>">
                                <?php if (in_array($question['type'], ['title', 'paragraph'])): 
                                    $alignment_class = '';
                                    $size_class = '';
                                    
                                    if ($question['type'] === 'title') {
                                        if (!empty($question['titleAlignment'])) {
                                            $alignment_class = 'align-' . esc_attr($question['titleAlignment']);
                                        } elseif (!empty($question['title_alignment'])) {
                                            $alignment_class = 'align-' . esc_attr($question['title_alignment']);
                                        }
                                        
                                        if (!empty($question['titleSize'])) {
                                            $size_class = 'size-' . esc_attr($question['titleSize']);
                                        } elseif (!empty($question['title_size'])) {
                                            $size_class = 'size-' . esc_attr($question['title_size']);
                                        }
                                    }
                                    elseif ($question['type'] === 'paragraph') {
                                        if (!empty($question['paragraphAlignment'])) {
                                            $alignment_class = 'align-' . esc_attr($question['paragraphAlignment']);
                                        } elseif (!empty($question['paragraph_alignment'])) {
                                            $alignment_class = 'align-' . esc_attr($question['paragraph_alignment']);
                                        }

                                        if (!empty($question['paragraphSize'])) {
                                            $size_class = 'size-' . esc_attr($question['paragraphSize']);
                                        } elseif (!empty($question['paragraph_size'])) {
                                            $size_class = 'size-' . esc_attr($question['paragraph_size']);
                                        } else {
                                            $size_class = 'size-p';
                                        }
                                    }
                                ?>
                                    <div class="form-question-text <?php echo $size_class; ?> <?php echo $alignment_class; ?>">
                                        <?php echo wp_kses_post($question['text'] ?? ''); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="form-question-prompt">
                                        <?php echo wp_kses_post($question['text'] ?? $question['prompt'] ?? ''); ?>
                                        <?php if (!empty($question['required'])): ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php switch ($question['type']):
                                    case 'title':
                                        break;

                                    case 'paragraph':
                                        $paragraph_content = '';
                                        if (isset($question['paragraphContent'])) {
                                            $paragraph_content = $question['paragraphContent'];
                                        } elseif (isset($question['content'])) {
                                            $paragraph_content = $question['content'];
                                        } elseif (isset($question['paragraph_content'])) {
                                            $paragraph_content = $question['paragraph_content'];
                                        }
                                        
                                        if (!empty($paragraph_content)): ?>
                                            <div class="form-paragraph-content">
                                                <?php echo wp_kses_post($paragraph_content); ?>
                                            </div>
                                        <?php endif;
                                        break;

                                    case 'text':
                                    case 'short_answer': ?>
                                        <div class="form-question-answer">
                                            <input type="text" name="form_data[<?php echo $question_id; ?>]"
                                                placeholder="<?php echo esc_attr($question['placeholder'] ?? ''); ?>"
                                                <?php echo !empty($question['required']) ? 'required' : ''; ?>
                                                <?php echo !empty($question['maxChars']) ? 'maxlength="'.esc_attr($question['maxChars']).'"' : ''; ?>
                                                <?php echo !empty($question['max_chars']) ? 'maxlength="'.esc_attr($question['max_chars']).'"' : ''; ?>>
                                        </div>
                                    <?php break;

                                    case 'textarea':
                                    case 'long_answer': ?>
                                        <div class="form-question-answer">
                                            <textarea name="form_data[<?php echo $question_id; ?>]"
                                                rows="5"
                                                placeholder="<?php echo esc_attr($question['placeholder'] ?? ''); ?>"
                                                <?php echo !empty($question['required']) ? 'required' : ''; ?>
                                                <?php echo !empty($question['maxChars']) ? 'maxlength="'.esc_attr($question['maxChars']).'"' : ''; ?>
                                                <?php echo !empty($question['max_chars']) ? 'maxlength="'.esc_attr($question['max_chars']).'"' : ''; ?>></textarea>
                                        </div>
                                    <?php break;

                                    case 'select': ?>
                                        <div class="form-question-answer">
                                            <select name="form_data[<?php echo $question_id; ?>]"
                                                <?php echo !empty($question['required']) ? 'required' : ''; ?>>
                                                <option value=""><?php _e('- Seleccionar -', 'englishline-test'); ?></option>
                                                <?php
                                                if (!empty($question['options']) && is_array($question['options'])):
                                                    foreach ($question['options'] as $option_index => $option): 
                                                        $option_value = $option_index;
                                                        $option_text = is_array($option) ? ($option['text'] ?? '') : $option;
                                                    ?>
                                                        <option value="<?php echo esc_attr($option_value); ?>">
                                                            <?php echo esc_html($option_text); ?>
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
                                                foreach ($question['options'] as $option_index => $option): 
                                                    $option_text = is_array($option) ? ($option['text'] ?? '') : $option;
                                                ?>
                                                    <div class="form-question-option">
                                                        <input type="radio"
                                                            id="option_<?php echo "{$question_id}_{$option_index}"; ?>"
                                                            name="form_data[<?php echo $question_id; ?>]"
                                                            value="<?php echo esc_attr($option_index); ?>"
                                                            <?php echo !empty($question['required']) ? 'required' : ''; ?>>
                                                        <label for="option_<?php echo "{$question_id}_{$option_index}"; ?>">
                                                            <?php echo esc_html($option_text); ?>
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
                                                foreach ($question['options'] as $option_index => $option): 
                                                    $option_text = is_array($option) ? ($option['text'] ?? '') : $option;
                                                ?>
                                                    <div class="form-question-option">
                                                        <input type="checkbox"
                                                            id="checkbox_<?php echo "{$question_id}_{$option_index}"; ?>"
                                                            name="form_data[<?php echo $question_id; ?>][]"
                                                            value="<?php echo esc_attr($option_index); ?>">
                                                        <label for="checkbox_<?php echo "{$question_id}_{$option_index}"; ?>">
                                                            <?php echo esc_html($option_text); ?>
                                                        </label>
                                                    </div>
                                            <?php endforeach;
                                            endif; ?>
                                        </div>
                                    <?php break;

                                    case 'image': ?>
                                        <?php 
                                        $image_id = 0;
                                        if (isset($question['imageId']) && !empty($question['imageId'])) {
                                            $image_id = $question['imageId'];
                                        } elseif (isset($question['image_id']) && !empty($question['image_id'])) {
                                            $image_id = $question['image_id'];
                                        }

                                        $image_url = '';
                                        if (isset($question['imageUrl']) && !empty($question['imageUrl'])) {
                                            $image_url = $question['imageUrl'];
                                        } elseif (isset($question['image_url']) && !empty($question['image_url'])) {
                                            $image_url = $question['image_url'];
                                        } else if ($image_id) {
                                            $image_url = wp_get_attachment_url($image_id);
                                        }
                                        
                                        if (!empty($image_url)): ?>
                                            <div class="question-image-container">
                                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($question['text'] ?? 'Imagen'); ?>">
                                            </div>
                                        <?php elseif ($image_id): ?>
                                            <div class="question-image-error">
                                                <?php _e('No se pudo cargar la imagen (ID: ', 'englishline-test'); echo $image_id; ?>)
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="form-question-answer">
                                            <?php
                                            $placeholder = '';
                                            if (!empty($question['imageQuestionText'])) {
                                                $placeholder = $question['imageQuestionText'];
                                            } elseif (!empty($question['image_question_text'])) {
                                                $placeholder = $question['image_question_text'];
                                            } elseif (!empty($question['placeholder'])) {
                                                $placeholder = $question['placeholder'];
                                            } else {
                                                $placeholder = __('Escribe tu respuesta aquí', 'englishline-test');
                                            }
                                            
                                            $max_chars = '';
                                            if (!empty($question['imageMaxChars'])) {
                                                $max_chars = $question['imageMaxChars'];
                                            } elseif (!empty($question['image_max_chars'])) {
                                                $max_chars = $question['image_max_chars'];
                                            } elseif (!empty($question['maxChars'])) {
                                                $max_chars = $question['maxChars'];
                                            } elseif (!empty($question['max_chars'])) {
                                                $max_chars = $question['max_chars'];
                                            }
                                            ?>
                                            <textarea name="form_data[<?php echo $question_id; ?>]"
                                                rows="5"
                                                placeholder="<?php echo esc_attr($placeholder); ?>"
                                                <?php echo !empty($question['required']) ? 'required' : ''; ?>
                                                <?php echo !empty($max_chars) ? 'maxlength="'.esc_attr($max_chars).'"' : ''; ?>></textarea>
                                        </div>
                                    <?php break;

                                    case 'true-false': ?>
                                        <div class="form-question-true-false">
                                            <?php
                                            $statement = '';
                                            if (!empty($question['statement'])) {
                                                $statement = $question['statement'];
                                            } else {
                                                $statement = $question['text'];
                                            }
                                            ?>
                                            <div class="true-false-statement"><?php echo wp_kses_post($statement); ?></div>
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
                                            $cloze_text = '';
                                            if (!empty($question['clozeText'])) {
                                                $cloze_text = $question['clozeText'];
                                            } elseif (!empty($question['cloze_text'])) {
                                                $cloze_text = $question['cloze_text'];
                                            }
                                            
                                            if (!empty($cloze_text)) {
                                                $pattern = '/\[(.*?)\]/';
                                                $count = 0;

                                                $cloze_html = preg_replace_callback($pattern, function ($matches) use (&$count, $question_id) {
                                                    $answer = !empty($matches[1]) ? $matches[1] : '';
                                                    $input = '<input type="text" name="form_data[' . $question_id . '][' . $count . ']" class="cloze-blank" placeholder="...">';

                                                    $count++;
                                                    return $input;
                                                }, $cloze_text);

                                                echo $cloze_html;
                                            } else {
                                                _e('Error: No se encontró texto para esta pregunta', 'englishline-test');
                                            }
                                            ?>
                                        </div>
                                    <?php break;

                                    case 'ordering': ?>
                                        <div class="form-question-ordering">
                                            <?php
                                            $items = [];
                                            
                                            if (!empty($question['itemsText'])) {
                                                $items = array_filter(array_map('trim', explode("\n", $question['itemsText'])));
                                            } elseif (!empty($question['items_text'])) {
                                                $items = array_filter(array_map('trim', explode("\n", $question['items_text'])));
                                            } elseif (!empty($question['items']) && is_array($question['items'])) {
                                                $items = $question['items'];
                                            } elseif (!empty($question['orderItems']) && is_array($question['orderItems'])) {
                                                $items = $question['orderItems'];
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
                                                <p><?php _e('No hay elementos para ordenar. La pregunta está vacía o mal configurada.', 'englishline-test'); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php break;

                                    default:
                                        echo '<p>' . sprintf(__('Tipo de pregunta no soportado: %s', 'englishline-test'), esc_html($question['type'])) . '</p>';
                                        break; 
                                endswitch;

                                if (!empty($question['hint'])): ?>
                                    <div class="form-question-hint">
                                        <span class="dashicons dashicons-info-outline"></span>
                                        <?php echo wp_kses_post($question['hint']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($question['explanation'])): ?>
                                    <div class="form-question-explanation-container">
                                        <a href="#" class="question-explanation-toggle"><?php _e('Ver explicación', 'englishline-test'); ?></a>
                                        <div class="question-explanation-content" style="display: none;">
                                            <?php echo wp_kses_post($question['explanation']); ?>
                                        </div>
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
                            <div class="englishline-question" data-question-type="text" data-is-gradable="false">
                                <div class="form-question-prompt">
                                    <?php _e('Nombre', 'englishline-test'); ?>
                                </div>
                                <div class="form-question-answer">
                                    <input type="text" name="user_data[first_name]" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-column">
                            <div class="englishline-question" data-question-type="text" data-is-gradable="false">
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
                            <div class="englishline-question" data-question-type="email" data-is-gradable="false">
                                <div class="form-question-prompt">
                                    <?php _e('Correo electrónico', 'englishline-test'); ?>
                                </div>
                                <div class="form-question-answer">
                                    <input type="email" name="user_data[email]" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-column">
                            <div class="englishline-question" data-question-type="tel" data-is-gradable="false">
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
                        <div class="englishline-question" data-question-type="checkbox" data-is-gradable="false">
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
