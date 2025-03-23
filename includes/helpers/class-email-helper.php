<?php

/**
 * Proporciona una interfaz para configurar el plugin
 */

// Verificar que no se accede directamente a este archivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar el envío de correos electrónicos
 */
class Email_Helper {
    
    /**
     * Obtiene un resultado por su ID
     *
     * @param int $result_id ID del resultado
     * @return object|null Objeto de resultado o null si no existe
     */
    private function get_result($result_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}englishline_results WHERE id = %d",
            $result_id
        ));
    }
    
    /**
     * Obtiene un formulario por su ID
     *
     * @param int $form_id ID del formulario
     * @return object|null Objeto de formulario o null si no existe
     */
    private function get_form($form_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}englishline_forms WHERE id = %d",
            $form_id
        ));
    }
    
        // Modifica el método send_grade_notification
    
    /**
     * Envía una notificación al usuario cuando su prueba es calificada
     */
    public function send_grade_notification($result_id, $score, $feedback) {
        $result = $this->get_result($result_id);
        if (!$result) {
            return false;
        }
    
        // Obtener datos del usuario del campo form_data
        $form_data = json_decode($result->form_data, true);
        $user_data = isset($form_data['user_data']) ? $form_data['user_data'] : [];
        
        // Obtener el email del usuario (priorizar el campo user_email)
        $user_email = $result->user_email;
        if (empty($user_email) && isset($user_data['email'])) {
            $user_email = $user_data['email'];
        }
        
        // Si no hay email, no podemos enviar
        if (empty($user_email)) {
            error_log('EnglishLine Test: No se pudo enviar email - dirección de correo no encontrada para el resultado ID ' . $result_id);
            return false;
        }
        
        // Determinar nombre del usuario
        $user_name = '';
        if (!empty($user_data['first_name'])) {
            $user_name = $user_data['first_name'] . ' ' . ($user_data['last_name'] ?? '');
        } elseif (!empty($user_data['name'])) {
            $user_name = $user_data['name'];
        } else {
            // Intentar obtener nombre de WP si hay user_id
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
    
        // Obtener la plantilla de correo
        $templates = get_option('englishline_test_email_templates', array());
        $subject = isset($templates['grade']['subject']) ? $templates['grade']['subject'] : 
                  __('Tu prueba ha sido calificada: {form_title}', 'englishline-test');
        
        $content = isset($templates['grade']['content']) ? $templates['grade']['content'] :
                  __("<p>Hola {user_name},</p>\n<p>Tu prueba \"{form_title}\" ha sido calificada.</p>\n<p><strong>Calificación:</strong> {score}%</p>\n{feedback}\n<p>Saludos,<br>{site_name}</p>", 'englishline-test');
    
        // Reemplazar variables en el asunto y contenido
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
    
        // Enviar correo
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $sent = wp_mail($user_email, $subject, $content, $headers);
        if ($sent) {
            error_log("EnglishLine Test: Email enviado exitosamente a $user_email");
        } else {
            error_log("EnglishLine Test: Error al enviar email a $user_email");
        }
        
        return $sent;
    }
    
}