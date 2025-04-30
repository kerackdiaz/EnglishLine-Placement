<?php
if (!defined('ABSPATH')) {
    die('Acceso directo no permitido');
}

$form = null;
$form_fields = '[]';
$form_id = 0;
$form_title = '';
$form_description = '';
$form_status = 'draft';

if (isset($_GET['form_id']) && intval($_GET['form_id']) > 0) {
    $form_id = intval($_GET['form_id']);
    global $wpdb;

    $form = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}englishline_forms WHERE id = %d",
        $form_id
    ));

    if ($form) {
        $form_title = $form->title;
        $form_description = $form->description;
        $form_status = isset($form->status) ? $form->status : 'draft';

        if (isset($form->fields)) {
            $form_fields = $form->fields;
        } elseif (isset($form->form_data)) {
            $form_fields = $form->form_data;
        }
    } else {
        wp_redirect(admin_url('admin.php?page=englishline-test-forms'));
        exit;
    }
}

$mode = ($form_id > 0) ? 'edit' : 'new';
?>
<div class="wrap englishline-admin-wrap">
    <div class="englishline-admin-header">
        <h1>
            <?php echo ($mode === 'edit') ? esc_html__('Editar Formulario', 'englishline-test') : esc_html__('Nuevo Formulario', 'englishline-test'); ?>
        </h1>
    </div>
    <div class="englishline-admin-content">
        <form id="form-builder" data-form-id="<?php echo esc_attr($form_id); ?>">
            <div class="form-builder-container">
                <div class="form-builder-column">
                    <div class="form-details">
                        <div class="form-field">
                            <label for="form-title"><?php esc_html_e('Título del formulario', 'englishline-test'); ?></label>
                            <input type="text" id="form-title" class="widefat" value="<?php echo esc_attr($form_title); ?>" required>
                        </div>

                        <div class="form-field">
                            <label for="form-description"><?php esc_html_e('Descripción', 'englishline-test'); ?></label>
                            <textarea id="form-description" class="widefat" rows="3"><?php echo esc_textarea($form_description); ?></textarea>
                        </div>
                    </div>


                    <div class="form-builder-main">

                        <div class="form-components-panel">
                            <h2 class="form-components-title"><?php esc_html_e('Componentes disponibles', 'englishline-test'); ?></h2>

                            <div class="form-components-container">
                                <div class="component-group">
                                    <h3 class="component-group-title"><?php esc_html_e('Estructura', 'englishline-test'); ?></h3>

                                    <div class="form-component" data-type="section" data-gradable="false" data-structural="true">
                                        <span class="form-component-icon dashicons dashicons-category"></span>
                                        <span class="form-component-label"><?php esc_html_e('Nueva Sección', 'englishline-test'); ?></span>
                                    </div>

                                    <div class="form-component" data-type="title" data-gradable="false" data-structural="true">
                                        <span class="form-component-icon dashicons dashicons-heading"></span>
                                        <span class="form-component-label"><?php esc_html_e('Título', 'englishline-test'); ?></span>
                                    </div>
                                    
                                    <div class="form-component" data-type="paragraph" data-gradable="false" data-structural="true">
                                        <span class="form-component-icon dashicons dashicons-editor-paragraph"></span>
                                        <span class="form-component-label"><?php esc_html_e('Párrafo', 'englishline-test'); ?></span>
                                    </div>
                                </div>

                                <div class="component-group">
                                    <h3 class="component-group-title"><?php esc_html_e('Entradas básicas', 'englishline-test'); ?></h3>

                                    <div class="form-component" data-type="text" data-gradable="true" data-requires-value="true">
                                        <span class="form-component-icon dashicons dashicons-editor-textcolor"></span>
                                        <span class="form-component-label"><?php esc_html_e('Texto corto', 'englishline-test'); ?></span>
                                    </div>

                                    <div class="form-component" data-type="textarea" data-gradable="true" data-requires-value="true">
                                        <span class="form-component-icon dashicons dashicons-editor-paragraph"></span>
                                        <span class="form-component-label"><?php esc_html_e('Texto largo', 'englishline-test'); ?></span>
                                    </div>

                                    <div class="form-component" data-type="select" data-gradable="true" data-has-options="true">
                                        <span class="form-component-icon dashicons dashicons-arrow-down"></span>
                                        <span class="form-component-label"><?php esc_html_e('Desplegable', 'englishline-test'); ?></span>
                                    </div>

                                    <div class="form-component" data-type="radio" data-gradable="true" data-has-options="true">
                                        <span class="form-component-icon dashicons dashicons-yes-alt"></span>
                                        <span class="form-component-label"><?php esc_html_e('Opción única', 'englishline-test'); ?></span>
                                    </div>

                                    <div class="form-component" data-type="checkbox" data-gradable="true" data-has-options="true" data-multiple="true">
                                        <span class="form-component-icon dashicons dashicons-yes"></span>
                                        <span class="form-component-label"><?php esc_html_e('Opción múltiple', 'englishline-test'); ?></span>
                                    </div>
                                </div>

                                <div class="component-group">
                                    <h3 class="component-group-title"><?php esc_html_e('Componentes interactivos', 'englishline-test'); ?></h3>

                                    <div class="form-component" data-type="image" data-gradable="false" data-requires-media="true">
                                        <span class="form-component-icon dashicons dashicons-format-image"></span>
                                        <span class="form-component-label"><?php esc_html_e('Imagen con descripción', 'englishline-test'); ?></span>
                                    </div>

                                    <div class="form-component" data-type="cloze" data-gradable="true" data-special-format="true">
                                        <span class="form-component-icon dashicons dashicons-editor-insertmore"></span>
                                        <span class="form-component-label"><?php esc_html_e('Completar texto', 'englishline-test'); ?></span>
                                    </div>

                                    <div class="form-component" data-type="ordering" data-gradable="true" data-requires-items="true">
                                        <span class="form-component-icon dashicons dashicons-sort"></span>
                                        <span class="form-component-label"><?php esc_html_e('Ordenar elementos', 'englishline-test'); ?></span>
                                    </div>

                                    <div class="form-component" data-type="true-false" data-gradable="true" data-has-feedback="true">
                                        <span class="form-component-icon dashicons dashicons-yes-no"></span>
                                        <span class="form-component-label"><?php esc_html_e('Verdadero/Falso', 'englishline-test'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-sections-container" id="form-sections-container">
                            <div class="form-sections-empty">
                                <p><?php esc_html_e('Arrastra componentes aquí para construir tu formulario.', 'englishline-test'); ?></p>
                                <p><?php esc_html_e('Primero añade una sección y luego preguntas dentro de ella.', 'englishline-test'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="save-form-btn" class="button button-primary">
                            <?php echo $form_id > 0 ? esc_html__('Actualizar formulario', 'englishline-test') : esc_html__('Guardar formulario', 'englishline-test'); ?>
                        </button>

                        <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-forms')); ?>" class="button">
                            <?php esc_html_e('Cancelar', 'englishline-test'); ?>
                        </a>
                    </div>

                    <?php if ($form_id > 0): ?>
                        <div id="form-shortcode-wrapper" class="form-shortcode-display">
                            <label for="form-shortcode"><?php esc_html_e('Shortcode:', 'englishline-test'); ?></label>
                            <input type="text" id="form-shortcode" value="[englishline_test id=<?php echo esc_attr($form_id); ?>]" readonly onclick="this.select();">
                        </div>
                    <?php else: ?>
                        <div id="form-shortcode-wrapper" class="form-shortcode-display" style="display: none;">
                            <label for="form-shortcode"><?php esc_html_e('Shortcode:', 'englishline-test'); ?></label>
                            <input type="text" id="form-shortcode" value="" readonly onclick="this.select();">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-builder-column">
                    <div class="form-preview-container" id="form-preview">
                        <div class="form-preview-header">
                            <h2 class="preview-title">
                                <?php esc_html_e('Vista previa del formulario', 'englishline-test'); ?>
                            </h2>
                            <div class="preview-description">
                                <?php echo wp_kses_post($form_description); ?>
                            </div>
                        </div>

                        <div class="preview-sections">
                        </div>

                        <div class="preview-nav" style="display: none;">
                            <button class="preview-prev-btn" disabled><?php esc_html_e('Anterior', 'englishline-test'); ?></button>
                            <div class="preview-dots"></div>
                            <button class="preview-next-btn"><?php esc_html_e('Siguiente', 'englishline-test'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    var formDataFromPHP = <?php
        if ($mode === 'edit' && !empty($form_fields)) {
            // Hacer log para depuración
            error_log('EnglishLine - form_fields (primeros 100 caracteres): ' . substr($form_fields, 0, 100));
            
            // Decodificar directamente sin manipulaciones previas
            $decoded = json_decode($form_fields, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('EnglishLine - Error al decodificar JSON: ' . json_last_error_msg());
                echo '[]';
            } else {
                echo json_encode($decoded);
            }
        } else {
            echo '[]';
        }
    ?>;
    
    if (typeof formDataFromPHP !== 'object' || formDataFromPHP === null) {
        formDataFromPHP = [];
    }
</script>