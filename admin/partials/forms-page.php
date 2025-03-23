<?php
/**
 * Proporciona una interfaz para gestionar formularios
 */

// Verificar que no se accede directamente a este archivo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar si las tablas existen
global $wpdb;
$forms_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}englishline_forms'") === $wpdb->prefix . 'englishline_forms';

// Mostrar mensaje si las tablas no existen
if (!$forms_table_exists) {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Formularios de EnglishLine Test', 'englishline-test'); ?></h1>
        <div class="notice notice-error">
            <p><?php esc_html_e('Las tablas necesarias no existen. Por favor, desactiva y reactiva el plugin para crear las tablas.', 'englishline-test'); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Determinar la acción actual
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

// Manejar acciones y mostrar la vista correspondiente
switch ($action) {
    case 'new':
        // Llamar a la función para mostrar el editor
        display_form_editor(null, 'new');
        break;
        
    case 'edit':
        if ($form_id > 0) {
            // Obtener datos del formulario
            $form = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}englishline_forms WHERE id = %d",
                $form_id
            ));
            
            if ($form) {
                // Llamar a la función para mostrar el editor con los datos del formulario
                display_form_editor($form, 'edit');
            } else {
                // Redirigir a la lista si el formulario no existe
                wp_redirect(admin_url('admin.php?page=englishline-test-forms'));
                exit;
            }
        } else {
            // Redirigir a la lista si no hay ID de formulario
            wp_redirect(admin_url('admin.php?page=englishline-test-forms'));
            exit;
        }
        break;
        
    case 'delete':
        if ($form_id > 0 && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'englishline_delete_form_' . $form_id)) {
            // Lógica para eliminar el formulario
            $deleted = delete_englishline_form($form_id);
            
            // Redirigir con mensaje de éxito o error
            $status = $deleted ? 'deleted' : 'error';
            wp_redirect(admin_url("admin.php?page=englishline-test-forms&status={$status}&form_id={$form_id}"));
            exit;
        } else {
            // Redirigir a la lista si el nonce no es válido
            wp_redirect(admin_url('admin.php?page=englishline-test-forms'));
            exit;
        }
        break;
        
    case 'list':
    default:
        // Incluir el archivo de lista de formularios mejorado
        include_once plugin_dir_path(__FILE__) . 'forms-list.php';
        break;
}

/**
 * Elimina un formulario y sus datos asociados
 */
function delete_englishline_form($form_id) {
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
            return false;
        }
        
        // Confirmar cambios
        $wpdb->query('COMMIT');
        return true;
    } catch (Exception $e) {
        // Si hay alguna excepción, revertir cambios
        $wpdb->query('ROLLBACK');
        return false;
    }
}

/**
 * Muestra el editor de formularios
 *
 * @param object $form Datos del formulario a editar (opcional)
 * @param string $action Acción actual ('new' o 'edit')
 */
function display_form_editor($form = null, $action = 'new') {
    // El código del editor de formularios que ya tenías
    ?>
    <div class="wrap englishline-admin-wrap">
        <div class="englishline-admin-header">
            <h1>
                <?php 
                if ($action === 'edit') {
                    echo esc_html__('Editar Formulario', 'englishline-test');
                } else {
                    echo esc_html__('Nuevo Formulario', 'englishline-test');
                }
                ?>
            </h1>
        </div>
        
        <div class="englishline-admin-content">
            <form id="form-builder" data-form-id="<?php echo isset($form) ? esc_attr($form->id) : '0'; ?>">
                <div class="form-builder-container">
                    <div class="form-builder-column">
                        <div class="form-details">
                            <div class="form-field">
                                <label for="form-title"><?php esc_html_e('Título del formulario', 'englishline-test'); ?></label>
                                <input type="text" id="form-title" class="widefat" value="<?php echo isset($form) ? esc_attr($form->title) : ''; ?>" required>
                            </div>
                            
                            <div class="form-field">
                                <label for="form-description"><?php esc_html_e('Descripción', 'englishline-test'); ?></label>
                                <textarea id="form-description" class="widefat" rows="3"><?php echo isset($form) ? esc_textarea($form->description) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-components-panel">
                            <h2 class="form-components-title"><?php esc_html_e('Componentes disponibles', 'englishline-test'); ?></h2>
                            <div class="form-components-container">
                                <div class="form-component" data-type="section">
                                    <span class="form-component-icon dashicons dashicons-category"></span>
                                    <span class="form-component-label"><?php esc_html_e('Nueva Sección', 'englishline-test'); ?></span>
                                </div>
                                
                                <div class="form-component" data-type="text">
                                    <span class="form-component-icon dashicons dashicons-editor-textcolor"></span>
                                    <span class="form-component-label"><?php esc_html_e('Texto corto', 'englishline-test'); ?></span>
                                </div>
                                
                                <div class="form-component" data-type="textarea">
                                    <span class="form-component-icon dashicons dashicons-editor-paragraph"></span>
                                    <span class="form-component-label"><?php esc_html_e('Texto largo', 'englishline-test'); ?></span>
                                </div>
                                
                                <div class="form-component" data-type="select">
                                    <span class="form-component-icon dashicons dashicons-arrow-down"></span>
                                    <span class="form-component-label"><?php esc_html_e('Desplegable', 'englishline-test'); ?></span>
                                </div>
                                
                                <div class="form-component" data-type="radio">
                                    <span class="form-component-icon dashicons dashicons-yes-alt"></span>
                                    <span class="form-component-label"><?php esc_html_e('Opción única', 'englishline-test'); ?></span>
                                </div>
                                
                                <div class="form-component" data-type="checkbox">
                                    <span class="form-component-icon dashicons dashicons-yes"></span>
                                    <span class="form-component-label"><?php esc_html_e('Opción múltiple', 'englishline-test'); ?></span>
                                </div>
                                
                                <div class="form-component" data-type="file">
                                    <span class="form-component-icon dashicons dashicons-paperclip"></span>
                                    <span class="form-component-label"><?php esc_html_e('Archivo', 'englishline-test'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-sections-container dropzone" id="form-sections-container">
                            <div class="form-sections-empty">
                                <p><?php esc_html_e('Arrastra componentes aquí para construir tu formulario.', 'englishline-test'); ?></p>
                                <p><?php esc_html_e('Primero añade una sección y luego preguntas dentro de ella.', 'englishline-test'); ?></p>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="save-form-btn" class="button button-primary">
                                <?php esc_html_e('Guardar formulario', 'englishline-test'); ?>
                            </button>
                            
                            <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-forms')); ?>" class="button">
                                <?php esc_html_e('Cancelar', 'englishline-test'); ?>
                            </a>
                        </div>
                        
                        <?php if (isset($form) && !empty($form->id)) : ?>
                            <div id="form-shortcode-wrapper" class="form-shortcode-display">
                                <label for="form-shortcode"><?php esc_html_e('Shortcode:', 'englishline-test'); ?></label>
                                <input type="text" id="form-shortcode" value="[englishline_form id=&quot;<?php echo esc_attr($form->id); ?>&quot;]" readonly onclick="this.select();">
                            </div>
                        <?php else : ?>
                            <div id="form-shortcode-wrapper" class="form-shortcode-display" style="display: none;">
                                <label for="form-shortcode"><?php esc_html_e('Shortcode:', 'englishline-test'); ?></label>
                                <input type="text" id="form-shortcode" value="" readonly onclick="this.select();">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-builder-column">
                        <div class="form-preview-container" id="form-preview">
                            <div class="form-preview-header">
                                <h2 class="preview-title"><?php echo isset($form) ? esc_html($form->title) : esc_html__('Vista previa del formulario', 'englishline-test'); ?></h2>
                                <div class="preview-description"><?php echo isset($form) ? esc_html($form->description) : ''; ?></div>
                            </div>
                            
                            <div class="preview-sections">
                                <?php if (!isset($form) || empty($form->form_data)) : ?>
                                    <p class="no-sections-message"><?php esc_html_e('No hay secciones añadidas aún. Utiliza el constructor de formularios para añadir secciones y preguntas.', 'englishline-test'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {

        <?php if (isset($form) && !empty($form->form_data)) : ?>
            let formData = <?php echo $form->form_data; ?>;
            if (typeof window.EnglishLineTest !== 'undefined' && 
                typeof window.EnglishLineTest.FormBuilder !== 'undefined') {
                window.EnglishLineTest.FormBuilder.loadFormData(formData);
            } else {
                console.error('El módulo FormBuilder no está disponible');
            }
        <?php endif; ?>
    });
    </script>
    <?php
}
?>