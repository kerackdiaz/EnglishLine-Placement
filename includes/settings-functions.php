<?php
/**
 * Funciones relacionadas con la configuración del plugin
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Función de sanitización para las opciones de configuración
 */
function englishline_test_sanitize_settings($input) {
    $sanitized = array();

    // Sanitizar cada campo según su tipo
    if (isset($input['company_name'])) {
        $sanitized['company_name'] = sanitize_text_field($input['company_name']);
    }

    if (isset($input['logo_id'])) {
        $sanitized['logo_id'] = absint($input['logo_id']);
    }

    if (isset($input['admin_email'])) {
        $sanitized['admin_email'] = sanitize_email($input['admin_email']);
    }
    
    // Añadir sanitización para terms_url y privacy_url
    if (isset($input['terms_url'])) {
        $sanitized['terms_url'] = esc_url_raw($input['terms_url']);
    }

    if (isset($input['privacy_url'])) {
        $sanitized['privacy_url'] = esc_url_raw($input['privacy_url']);
    }

    if (isset($input['enable_admin_email'])) {
        $sanitized['enable_admin_email'] = '1';
    } else {
        $sanitized['enable_admin_email'] = '0';
    }

    if (isset($input['enable_user_email'])) {
        $sanitized['enable_user_email'] = '1';
    } else {
        $sanitized['enable_user_email'] = '0';
    }

    if (isset($input['enable_grade_email'])) {
        $sanitized['enable_grade_email'] = '1';
    } else {
        $sanitized['enable_grade_email'] = '0';
    }

    if (isset($input['primary_color'])) {
        $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
    }

    if (isset($input['secondary_color'])) {
        $sanitized['secondary_color'] = sanitize_hex_color($input['secondary_color']);
    }

    if (isset($input['font_family'])) {
        $sanitized['font_family'] = sanitize_text_field($input['font_family']);
    }

    if (isset($input['custom_css'])) {
        $sanitized['custom_css'] = wp_kses_post($input['custom_css']);
    }

    if (isset($input['enable_recaptcha'])) {
        $sanitized['enable_recaptcha'] = '1';
    } else {
        $sanitized['enable_recaptcha'] = '0';
    }

    if (isset($input['recaptcha_site_key'])) {
        $sanitized['recaptcha_site_key'] = sanitize_text_field($input['recaptcha_site_key']);
    }

    if (isset($input['recaptcha_secret_key'])) {
        $sanitized['recaptcha_secret_key'] = sanitize_text_field($input['recaptcha_secret_key']);
    }

    if (isset($input['delete_data'])) {
        $sanitized['delete_data'] = '1';
    } else {
        $sanitized['delete_data'] = '0';
    }

    return $sanitized;
}

/**
 * Inicializa las opciones de configuración con valores predeterminados
 */
function englishline_test_initialize_settings() {
    $default_options = array(
        'company_name' => '',
        'logo_id' => 0,
        'admin_email' => get_option('admin_email'),
        'terms_url' => '', 
        'privacy_url' => '',
        'enable_admin_email' => '1',
        'enable_user_email' => '1',
        'enable_grade_email' => '1',
        'primary_color' => '#3498db',
        'secondary_color' => '#2980b9',
        'font_family' => 'inherit',
        'custom_css' => '',
        'enable_recaptcha' => '0',
        'recaptcha_site_key' => '',
        'recaptcha_secret_key' => '',
        'delete_data' => '0'
    );

    if (false === get_option('englishline_test_settings')) {
        add_option('englishline_test_settings', $default_options);
    }
}

/**
 * Registra las opciones de configuración con WordPress
 */
function englishline_test_register_settings() {

    register_setting(
        'englishline_test_options', 
        'englishline_test_settings', 
        'englishline_test_sanitize_settings'
    );
}


/**
 * Inicializa las plantillas de correo con valores predeterminados
 */
function englishline_test_initialize_email_templates() {
    $default_templates = array(
        'admin' => array(
            'subject' => __('Nuevo formulario enviado: {form_title}', 'englishline-test'),
            'content' => __("<p>Hola administrador,</p>\n<p>Un usuario ha completado el formulario \"{form_title}\".</p>\n<p><strong>Información del usuario:</strong></p>\n<p>Nombre: {user_name}<br>Email: {user_email}</p>\n<p><strong>Datos del formulario:</strong></p>\n{form_data}\n<p>Saludos,<br>{site_name}</p>", 'englishline-test')
        ),
        'user' => array(
            'subject' => __('Tu formulario ha sido enviado: {form_title}', 'englishline-test'),
            'content' => __("<p>Hola {user_name},</p>\n<p>Gracias por completar el formulario \"{form_title}\".</p>\n<p>Te notificaremos cuando tu prueba sea calificada.</p>\n<p>Saludos,<br>{site_name}</p>", 'englishline-test')
        ),
        'grade' => array(
            'subject' => __('Tu prueba ha sido calificada: {form_title}', 'englishline-test'),
            'content' => __("<p>Hola {user_name},</p>\n<p>Tu prueba \"{form_title}\" ha sido calificada.</p>\n<p><strong>Calificación:</strong> {score}%</p>\n{feedback}\n<p>Si tienes alguna pregunta, no dudes en contactarnos.</p>\n<p>Saludos,<br>{site_name}</p>", 'englishline-test')
        )
    );

    if (false === get_option('englishline_test_email_templates')) {
        add_option('englishline_test_email_templates', $default_templates);
    }
}

/**
 * Procesa el formulario para guardar las plantillas de correo
 */
function englishline_test_save_email_templates() {
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos suficientes para realizar esta acción.', 'englishline-test'));
    }

    if (!isset($_POST['englishline_email_templates_nonce']) || !wp_verify_nonce($_POST['englishline_email_templates_nonce'], 'englishline_email_templates')) {
        wp_die(__('Error de seguridad. Por favor, inténtalo de nuevo.', 'englishline-test'));
    }

    $templates = array();
    if (isset($_POST['templates']) && is_array($_POST['templates'])) {
        foreach ($_POST['templates'] as $type => $template) {
            $templates[$type] = array(
                'subject' => sanitize_text_field($template['subject']),
                'content' => wp_kses_post($template['content'])
            );
        }
    }

    update_option('englishline_test_email_templates', $templates);

    wp_redirect(add_query_arg('updated', 'true', admin_url('admin.php?page=englishline-test-email-templates')));
    exit;
}
add_action('admin_post_englishline_save_email_templates', 'englishline_test_save_email_templates');
