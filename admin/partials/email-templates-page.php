<?php

if (!defined('ABSPATH')) {
    exit;
}

// Definir la función localmente si no existe
if (!function_exists('englishline_test_initialize_email_templates')) {
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
}

// También definir la función para guardar plantillas si no existe
if (!function_exists('englishline_test_save_email_templates')) {
    // Definición del hook para capturar el formulario
    add_action('admin_post_englishline_save_email_templates', 'englishline_test_save_email_templates');
    
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
}

// Inicializar plantillas si no existen
englishline_test_initialize_email_templates();

// Obtener las plantillas guardadas
$templates = get_option('englishline_test_email_templates', array());
?>

<div class="wrap englishline-admin-wrap">
    <div class="englishline-admin-header">
        <h1><?php esc_html_e('Plantillas de Correo Electrónico', 'englishline-test'); ?></h1>
    </div>

    <div class="englishline-admin-content">
        <div class="englishline-tabs-nav">
            <a href="#" class="tab-link active" data-tab="admin"><?php esc_html_e('Notificación al Administrador', 'englishline-test'); ?></a>
            <a href="#" class="tab-link" data-tab="user"><?php esc_html_e('Confirmación al Usuario', 'englishline-test'); ?></a>
            <a href="#" class="tab-link" data-tab="grade"><?php esc_html_e('Notificación de Calificación', 'englishline-test'); ?></a>
        </div>

        <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true') : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Plantillas de correo actualizadas con éxito.', 'englishline-test'); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="englishline_save_email_templates">
            <?php wp_nonce_field('englishline_email_templates', 'englishline_email_templates_nonce'); ?>

            <!-- Plantilla para Administrador -->
            <div id="tab-admin" class="englishline-tab-content active">
                <h2><?php esc_html_e('Notificación al Administrador', 'englishline-test'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Este correo se envía al administrador cuando un usuario completa un formulario.', 'englishline-test'); ?>
                </p>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="admin_subject"><?php esc_html_e('Asunto del correo', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="admin_subject" name="templates[admin][subject]" value="<?php echo esc_attr($templates['admin']['subject'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="admin_content"><?php esc_html_e('Contenido del correo', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <?php
                            $content = $templates['admin']['content'] ?? '';
                            wp_editor($content, 'admin_content', array(
                                'textarea_name' => 'templates[admin][content]',
                                'textarea_rows' => 10,
                                'media_buttons' => false
                            ));
                            ?>
                            <p class="description">
                                <?php esc_html_e('Variables disponibles:', 'englishline-test'); ?>
                                <code>{form_title}</code>, <code>{user_name}</code>, <code>{user_email}</code>, <code>{form_data}</code>, <code>{site_name}</code>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Plantilla para Usuario -->
            <div id="tab-user" class="englishline-tab-content">
                <h2><?php esc_html_e('Confirmación al Usuario', 'englishline-test'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Este correo se envía al usuario cuando completa un formulario.', 'englishline-test'); ?>
                </p>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="user_subject"><?php esc_html_e('Asunto del correo', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="user_subject" name="templates[user][subject]" value="<?php echo esc_attr($templates['user']['subject'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="user_content"><?php esc_html_e('Contenido del correo', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <?php
                            $content = $templates['user']['content'] ?? '';
                            wp_editor($content, 'user_content', array(
                                'textarea_name' => 'templates[user][content]',
                                'textarea_rows' => 10,
                                'media_buttons' => false
                            ));
                            ?>
                            <p class="description">
                                <?php esc_html_e('Variables disponibles:', 'englishline-test'); ?>
                                <code>{form_title}</code>, <code>{user_name}</code>, <code>{user_email}</code>, <code>{site_name}</code>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Plantilla para Calificación -->
            <div id="tab-grade" class="englishline-tab-content">
                <h2><?php esc_html_e('Notificación de Calificación', 'englishline-test'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Este correo se envía al usuario cuando su prueba ha sido calificada.', 'englishline-test'); ?>
                </p>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="grade_subject"><?php esc_html_e('Asunto del correo', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="grade_subject" name="templates[grade][subject]" value="<?php echo esc_attr($templates['grade']['subject'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="grade_content"><?php esc_html_e('Contenido del correo', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <?php
                            $content = $templates['grade']['content'] ?? '';
                            wp_editor($content, 'grade_content', array(
                                'textarea_name' => 'templates[grade][content]',
                                'textarea_rows' => 10,
                                'media_buttons' => false
                            ));
                            ?>
                            <p class="description">
                                <?php esc_html_e('Variables disponibles:', 'englishline-test'); ?>
                                <code>{form_title}</code>, <code>{user_name}</code>, <code>{user_email}</code>, <code>{score}</code>, <code>{feedback}</code>, <code>{site_name}</code>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(__('Guardar Plantillas', 'englishline-test')); ?>
        </form>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Gestión de pestañas
        $('.tab-link').on('click', function(e) {
            e.preventDefault();
            let tabId = $(this).data('tab');

            $('.tab-link').removeClass('active');
            $(this).addClass('active');

            $('.englishline-tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');

            // Guardar pestaña activa en localStorage
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('englishline_email_tab', tabId);
            }
        });

        // Restaurar última pestaña activa
        if (typeof(Storage) !== "undefined") {
            let lastTab = localStorage.getItem('englishline_email_tab');
            if (lastTab) {
                $('.tab-link[data-tab="' + lastTab + '"]').trigger('click');
            }
        }
    });
</script>