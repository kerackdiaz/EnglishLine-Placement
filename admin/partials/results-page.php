<?php
/**
 * Proporciona una interfaz para gestionar resultados de pruebas
 */

// Verificar que no se accede directamente a este archivo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar si las tablas existen
global $wpdb;
$results_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}englishline_results'") === $wpdb->prefix . 'englishline_results';

// Procesamiento de acciones que requieren redirección ANTES de cualquier salida HTML
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

// Manejar acción de eliminación antes de cualquier salida HTML
if ($action === 'delete' && $result_id > 0 && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_result_' . $result_id)) {
    // Eliminar resultado
    $deleted = delete_englishline_result($result_id);
    
    // Redirigir con mensaje de éxito o error
    $status = $deleted ? 'deleted' : 'error';
    wp_safe_redirect(admin_url("admin.php?page=englishline-test-results&status={$status}&result_id={$result_id}"));
    exit;
}

// Mostrar mensaje si las tablas no existen
if (!$results_table_exists) {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Resultados de EnglishLine Test', 'englishline-test'); ?></h1>
        <div class="notice notice-error">
            <p><?php esc_html_e('Las tablas necesarias no existen. Por favor, desactiva y reactiva el plugin para crear las tablas.', 'englishline-test'); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Manejar acciones y mostrar la vista correspondiente
switch ($action) {
    case 'view':
        // Ver un resultado específico
        $result = null;
        if ($result_id > 0) {
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT r.*, f.title as form_title, u.display_name as user_name
                 FROM {$wpdb->prefix}englishline_results r
                 LEFT JOIN {$wpdb->prefix}englishline_forms f ON r.form_id = f.id
                 LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                 WHERE r.id = %d",
                $result_id
            ));
            
            if (!$result) {
                ?>
                <div class="wrap">
                    <h1><?php esc_html_e('Ver resultado', 'englishline-test'); ?></h1>
                    <div class="notice notice-error">
                        <p><?php esc_html_e('No se encontró el resultado especificado.', 'englishline-test'); ?></p>
                    </div>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results')); ?>" class="button">
                            <?php esc_html_e('Volver a resultados', 'englishline-test'); ?>
                        </a>
                    </p>
                </div>
                <?php
                return;
            }

            // Mostrar vista de resultado
            include plugin_dir_path(__FILE__) . 'result-view.php';
        } else {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Ver resultado', 'englishline-test'); ?></h1>
                <div class="notice notice-error">
                    <p><?php esc_html_e('ID de resultado no válido.', 'englishline-test'); ?></p>
                </div>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results')); ?>" class="button">
                        <?php esc_html_e('Volver a resultados', 'englishline-test'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
        break;
        
    case 'export':
        // Exportar resultados como CSV
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        if ($form_id > 0) {
            export_results_as_csv($form_id);
            exit;
        } else {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Exportar resultados', 'englishline-test'); ?></h1>
                <div class="notice notice-error">
                    <p><?php esc_html_e('ID de formulario no válido para exportar.', 'englishline-test'); ?></p>
                </div>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results')); ?>" class="button">
                        <?php esc_html_e('Volver a resultados', 'englishline-test'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
        break;
        
    case 'list':
    default:
        // Mostrar listado de resultados
        display_results_list();
        break;
}

/**
 * Elimina un resultado
 *
 * @param int $result_id ID del resultado a eliminar
 * @return bool True si se eliminó correctamente, false en caso contrario
 */
function delete_englishline_result($result_id) {
    global $wpdb;
    
    // Iniciar transacción
    $wpdb->query('START TRANSACTION');
    
    try {
        // Eliminar el resultado
        $results_table = $wpdb->prefix . 'englishline_results';
        $result = $wpdb->delete($results_table, array('id' => $result_id), array('%d'));
        
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
 * Muestra la lista de resultados
 */
function display_results_list() {
    include plugin_dir_path(__FILE__) . 'results-list.php';
}

/**
 * Exporta los resultados de un formulario como CSV
 *
 * @param int $form_id ID del formulario
 */
function export_results_as_csv($form_id) {
    global $wpdb;
    
    // Obtener información del formulario
    $form = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}englishline_forms WHERE id = %d",
        $form_id
    ));
    
    if (!$form) {
        wp_die(esc_html__('No se encontró el formulario especificado.', 'englishline-test'));
    }
    
    // Obtener resultados para este formulario
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT r.*, u.display_name as user_name, u.user_email as user_email_from_wp
         FROM {$wpdb->prefix}englishline_results r
         LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
         WHERE r.form_id = %d
         ORDER BY r.created_at DESC",
        $form_id
    ));
    
    if (empty($results)) {
        wp_die(esc_html__('No hay resultados para exportar para este formulario.', 'englishline-test'));
    }
    
    // Preparar nombre del archivo
    $filename = sanitize_title($form->title) . '-resultados-' . date('Y-m-d') . '.csv';
    
    // Configurar headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Abrir flujo de salida
    $output = fopen('php://output', 'w');
    
    // Escribir BOM UTF-8
    fputs($output, "\xEF\xBB\xBF");
    
    // Preparar encabezados CSV
    $headers = array(
        'ID', 
        'Usuario', 
        'Email', 
        'Fecha', 
        'Estado',
        'Puntuación',
        'Respuestas'
    );
    
    // Escribir encabezados
    fputcsv($output, $headers);
    
    // Preparar y escribir cada fila
    foreach ($results as $result) {
        // Decodificar los datos del formulario
        $form_data = json_decode($result->form_data, true);
        $user_data = array();
        
        if (is_array($form_data) && isset($form_data['user_data'])) {
            $user_data = $form_data['user_data'];
        }
        
        // Determinar el email del usuario
        $email = $result->user_email;
        if (empty($email) && !empty($result->user_email_from_wp)) {
            $email = $result->user_email_from_wp;
        } elseif (empty($email) && isset($user_data['email'])) {
            $email = $user_data['email'];
        }
        
        // Preparar nombre del usuario
        $user_name = $result->user_name;
        if (empty($user_name) && isset($user_data['first_name'])) {
            $user_name = $user_data['first_name'];
            if (isset($user_data['last_name'])) {
                $user_name .= ' ' . $user_data['last_name'];
            }
        }
        
        // Formatear respuestas para CSV
        $responses = '';
        if (is_array($form_data) && isset($form_data['form_data'])) {
            $responses_array = array();
            foreach ($form_data['form_data'] as $key => $answer) {
                if (is_array($answer)) {
                    $responses_array[] = $key . ': ' . implode(', ', $answer);
                } else {
                    $responses_array[] = $key . ': ' . $answer;
                }
            }
            $responses = implode(' | ', $responses_array);
        }
        
        // Preparar fila
        $row = array(
            $result->id,
            $user_name ?: 'Anónimo',
            $email ?: 'No disponible',
            $result->created_at,
            $result->status ?: 'Pendiente',
            $result->score !== null ? $result->score . '%' : 'N/A',
            $responses
        );
        
        // Escribir fila
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>


<style>
/* Estilos específicos para resultados */
.englishline-admin-wrap {
    max-width: 1600px;
    margin: 20px auto;
    background: #f8f9fa;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.englishline-admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.englishline-admin-header h1 {
    margin: 0;
    padding: 0;
    font-size: 24px;
    font-weight: 600;
}

.englishline-admin-actions {
    display: flex;
    gap: 10px;
}

.englishline-admin-content {
    background: #fff;
    padding: 20px;
    border-radius: 3px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

/* Estilos para detalles de resultado */
.result-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 15px;
}

.result-header {
    width: 100%;
    border-bottom: 1px solid #eee;
    margin-bottom: 20px;
    padding-bottom: 15px;
}

.result-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.result-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 10px;
}

.result-meta-item {
    display: flex;
    align-items: center;
}

.result-meta-label {
    font-weight: bold;
    margin-right: 5px;
}

.result-score {
    font-size: 36px;
    font-weight: bold;
    color: #2271b1;
    text-align: center;
    padding: 15px 0;
}

.result-score-text {
    display: block;
    font-size: 14px;
    color: #666;
}

.result-answers {
    width: 100%;
    margin-top: 20px;
}

.result-section {
    border: 1px solid #eee;
    margin-bottom: 20px;
    border-radius: 3px;
    overflow: hidden;
}

.result-section-header {
    background: #f9f9f9;
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    font-weight: bold;
}

.result-question {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.result-question:last-child {
    border-bottom: none;
}

.result-question-text {
    margin-bottom: 10px;
    font-weight: 500;
}

.result-answer {
    background: #f4f7fb;
    padding: 10px;
    border-radius: 3px;
}

.result-correct {
    border-left: 4px solid #46b450;
}

.result-incorrect {
    border-left: 4px solid #dc3232;
}

.result-expected {
    margin-top: 10px;
    color: #666;
    font-style: italic;
}

/* Estilos para botones de acciones */
.englishline-action-buttons {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

/* Estilos responsivos */
@media screen and (max-width: 782px) {
    .englishline-admin-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .result-meta {
        flex-direction: column;
        gap: 5px;
    }
}
</style>