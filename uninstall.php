<?php

/**
 * Desinstalación del plugin EnglishLine Test
 *
 * Este archivo se ejecuta cuando el plugin es desinstalado.
 */

// Si no es WordPress, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verificar si el usuario ha elegido eliminar todos los datos
$settings = get_option('englishline_test_settings', []);
$delete_data = isset($settings['delete_data']) && $settings['delete_data'] === '1';

if ($delete_data) {
    global $wpdb;
    
    // Eliminar tablas personalizadas
    $tables = [
        $wpdb->prefix . 'englishline_forms',
        $wpdb->prefix . 'englishline_results',
        $wpdb->prefix . 'englishline_submissions',
        $wpdb->prefix . 'englishline_certificates',
        $wpdb->prefix . 'englishline_email_templates',
        $wpdb->prefix . 'englishline_user_attempts',   
        $wpdb->prefix . 'englishline_questions',       
        $wpdb->prefix . 'englishline_answers'          
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Eliminar opciones de configuración
    $options = [
        'englishline_test_settings',
        'englishline_test_db_version',
        'englishline_test_last_activated',
        'englishline_test_last_deactivated',
        'englishline_test_email_templates',
        'englishline_notification_email',
        'widget_englishline_test_form_widget',
        'englishline_test_initialized',
        'englishline_test_version',
        'englishline_test_activation_date',
        'englishline_test_last_error',
        'englishline_test_stats',                 
        'englishline_test_maintenance_mode',      
        'englishline_test_api_key',               
        'englishline_test_custom_templates',      
        'englishline_test_page_ids'               
    ];
    
    // Buscar todas las opciones que empiecen con englishline_test
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'englishline_test_%'");
    
    // Eliminar opciones específicas (por si acaso)
    foreach ($options as $option) {
        delete_option($option);
        delete_site_option($option); // Para multisitio
    }
    
    // Eliminar archivos subidos
    $upload_dir = wp_upload_dir();
    $englishline_dir = $upload_dir['basedir'] . '/englishline-test';
    if (is_dir($englishline_dir)) {
        delete_directory($englishline_dir);
    }
    
    // Eliminar posibles archivos de certificados
    $certificates_dir = $upload_dir['basedir'] . '/certificates';
    if (is_dir($certificates_dir)) {
        delete_directory($certificates_dir);
    }
    
    // Eliminar posibles archivos temporales
    $temp_dir = $upload_dir['basedir'] . '/englishline-temp';
    if (is_dir($temp_dir)) {
        delete_directory($temp_dir);
    }
    
    // Eliminar archivos de caché
    $cache_dir = WP_CONTENT_DIR . '/cache/englishline-test';
    if (is_dir($cache_dir)) {
        delete_directory($cache_dir);
    }
    
    // Eliminar roles y capacidades de usuario personalizadas
    $custom_roles = [
        'englishline_manager',
        'englishline_test_reviewer',
        'englishline_instructor',
        'englishline_student'
    ];
    
    foreach ($custom_roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            remove_role($role_name);
        }
    }
    
    // Limpiar capacidades de roles existentes
    $existing_roles = ['administrator', 'editor', 'author'];
    foreach ($existing_roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            // Eliminar capacidades específicas del plugin
            $capabilities = [
                'manage_englishline_test',
                'view_englishline_results',
                'edit_englishline_results',
                'export_englishline_results',
                'delete_englishline_results',
                'manage_englishline_settings',
                'review_englishline_results',
                'create_englishline_forms',
                'edit_englishline_forms',
                'delete_englishline_forms'
            ];
            
            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
    
    // Limpiar datos de transients
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_englishline_test_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_englishline_test_%'");
    
    // Eliminar metadatos de usuario relacionados con el plugin
    $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '%englishline_test%'");
    $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '%englishline\_%'");
    
    // Eliminar posts personalizados si existen
    $post_types = ['englishline_form', 'englishline_result', 'englishline_certificate'];
    foreach ($post_types as $post_type) {
        $items = get_posts([
            'post_type' => $post_type,
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
        ]);
        
        if ($items) {
            foreach ($items as $item) {
                wp_delete_post($item, true);
            }
        }
    }
    
    // Eliminar posts y metadatos de posts relacionados con el plugin
    $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%englishline_test%'");
    $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%englishline\_%'");
    
    // Eliminar todos los archivos multimedia asociados (como imágenes de logo)
    if (isset($settings['logo_id']) && !empty($settings['logo_id'])) {
        wp_delete_attachment($settings['logo_id'], true);
    }
    
    // Limpiar cualquier tarea programada
    wp_clear_scheduled_hook('englishline_test_cron');
    wp_clear_scheduled_hook('englishline_test_daily_maintenance');
    wp_clear_scheduled_hook('englishline_test_weekly_report');
    
    // Vaciar caché
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

/**
 * Eliminar directorio recursivamente
 * @param string $dir Ruta del directorio a eliminar
 * @return bool True si se eliminó correctamente
 */
function delete_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}