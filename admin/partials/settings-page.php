<?php

/**
 * Proporciona una interfaz para configurar el plugin
 */

// Verificar que no se accede directamente a este archivo
if (!defined('ABSPATH')) {
    exit;
}

// Ejecutar inicialización
englishline_test_initialize_settings();

// Obtener opciones para su uso en el formulario
$options = get_option('englishline_test_settings', array());
?>

<div class="wrap englishline-admin-wrap">
    <div class="englishline-admin-header">
        <h1><?php esc_html_e('Configuración', 'englishline-test'); ?></h1>
    </div>

    <div class="englishline-admin-content">
        <div class="englishline-tabs-nav">
            <a href="#" class="tab-link active" data-tab="general"><?php esc_html_e('General', 'englishline-test'); ?></a>
            <a href="#" class="tab-link" data-tab="email"><?php esc_html_e('Correos electrónicos', 'englishline-test'); ?></a>
            <a href="#" class="tab-link" data-tab="display"><?php esc_html_e('Visualización', 'englishline-test'); ?></a>
            <a href="#" class="tab-link" data-tab="advanced"><?php esc_html_e('Avanzado', 'englishline-test'); ?></a>
        </div>

        <form method="post" action="options.php">
            <?php
            settings_fields('englishline_test_options');
            ?>

            <!-- Pestaña General -->
            <div id="tab-general" class="englishline-tab-content active">
                <h2><?php esc_html_e('Configuración General', 'englishline-test'); ?></h2>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_company_name"><?php esc_html_e('Nombre de la empresa', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="englishline_test_company_name"
                                name="englishline_test_settings[company_name]"
                                value="<?php echo esc_attr($options['company_name'] ?? ''); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Se utilizará en correos electrónicos y certificados', 'englishline-test'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_logo"><?php esc_html_e('Logo', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <?php
                            $logo_id = isset($options['logo_id']) ? absint($options['logo_id']) : 0;
                            $logo_url = '';
                            if ($logo_id > 0) {
                                $logo_url = wp_get_attachment_image_url($logo_id, 'medium');
                            }
                            ?>

                            <div class="logo-preview-wrapper" style="margin-bottom: 10px;">
                                <?php if ($logo_url) : ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('Logo de la empresa', 'englishline-test'); ?>" style="max-width: 200px; max-height: 100px;">
                                <?php endif; ?>
                            </div>

                            <input type="hidden" id="englishline_test_logo" name="englishline_test_settings[logo_id]" value="<?php echo esc_attr($logo_id); ?>">
                            <button type="button" class="button" id="upload-logo-button">
                                <?php echo $logo_id ? esc_html__('Cambiar logo', 'englishline-test') : esc_html__('Subir logo', 'englishline-test'); ?>
                            </button>

                            <?php if ($logo_id > 0) : ?>
                                <button type="button" class="button" id="remove-logo-button">
                                    <?php esc_html_e('Quitar logo', 'englishline-test'); ?>
                                </button>
                            <?php endif; ?>

                            <p class="description">
                                <?php esc_html_e('Selecciona o sube un logo para tu empresa. Tamaño recomendado: 200x100px.', 'englishline-test'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_admin_email"><?php esc_html_e('Correo electrónico del administrador', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="englishline_test_admin_email" name="englishline_test_settings[admin_email]"
                                value="<?php echo esc_attr($options['admin_email'] ?? get_option('admin_email')); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Dirección de correo para recibir notificaciones', 'englishline-test'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_terms_url"><?php esc_html_e('URL de Términos y Condiciones', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="englishline_test_terms_url" name="englishline_test_settings[terms_url]"
                                value="<?php echo esc_url($options['terms_url'] ?? ''); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('URL de la página de Términos y Condiciones', 'englishline-test'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_privacy_url"><?php esc_html_e('URL de Políticas de Tratamiento de Datos', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="englishline_test_privacy_url" name="englishline_test_settings[privacy_url]"
                                value="<?php echo esc_url($options['privacy_url'] ?? ''); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('URL de la página de Políticas de Tratamiento de Datos', 'englishline-test'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Pestaña de Correos Electrónicos -->
            <div id="tab-email" class="englishline-tab-content">
                <h2><?php esc_html_e('Configuración de Correos', 'englishline-test'); ?></h2>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_enable_admin_email"><?php esc_html_e('Notificaciones al administrador', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="englishline_test_enable_admin_email" name="englishline_test_settings[enable_admin_email]"
                                    value="1" <?php checked($options['enable_admin_email'] ?? '1', '1'); ?>>
                                <?php esc_html_e('Enviar correo al administrador cuando se complete un formulario', 'englishline-test'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_enable_user_email"><?php esc_html_e('Notificaciones al usuario', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="englishline_test_enable_user_email" name="englishline_test_settings[enable_user_email]"
                                    value="1" <?php checked(get_option('englishline_test_settings')['enable_user_email'] ?? '1', '1'); ?>>
                                <?php esc_html_e('Enviar confirmación al usuario cuando complete un formulario', 'englishline-test'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_enable_grade_email"><?php esc_html_e('Notificación de calificación', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="englishline_test_enable_grade_email" name="englishline_test_settings[enable_grade_email]"
                                    value="1" <?php checked(get_option('englishline_test_settings')['enable_grade_email'] ?? '1', '1'); ?>>
                                <?php esc_html_e('Enviar notificación al usuario cuando su prueba sea calificada', 'englishline-test'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label><?php esc_html_e('Plantillas de correo', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-email-templates')); ?>" class="button">
                                    <?php esc_html_e('Editar plantillas de correo', 'englishline-test'); ?>
                                </a>
                            </p>
                            <p class="description"><?php esc_html_e('Personaliza los correos electrónicos enviados a administradores y usuarios', 'englishline-test'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Pestaña de Visualización -->
            <div id="tab-display" class="englishline-tab-content">
                <h2><?php esc_html_e('Opciones de visualización', 'englishline-test'); ?></h2>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_primary_color"><?php esc_html_e('Color primario', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="englishline_test_primary_color" name="englishline_test_settings[primary_color]"
                                value="<?php echo esc_attr(get_option('englishline_test_settings')['primary_color'] ?? '#3498db'); ?>">
                            <p class="description"><?php esc_html_e('Color principal para botones y elementos destacados', 'englishline-test'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_secondary_color"><?php esc_html_e('Color secundario', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="englishline_test_secondary_color" name="englishline_test_settings[secondary_color]"
                                value="<?php echo esc_attr(get_option('englishline_test_settings')['secondary_color'] ?? '#2980b9'); ?>">
                            <p class="description"><?php esc_html_e('Color secundario para elementos interactivos', 'englishline-test'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_font_family"><?php esc_html_e('Tipografía', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <select id="englishline_test_font_family" name="englishline_test_settings[font_family]">
                                <?php
                                $current_font = get_option('englishline_test_settings')['font_family'] ?? 'inherit';
                                $fonts = array(
                                    'inherit' => __('Usar fuente del tema', 'englishline-test'),
                                    'Arial, sans-serif' => 'Arial',
                                    'Helvetica, Arial, sans-serif' => 'Helvetica',
                                    'Georgia, serif' => 'Georgia',
                                    'Tahoma, Geneva, sans-serif' => 'Tahoma',
                                    'Verdana, Geneva, sans-serif' => 'Verdana',
                                    '"Times New Roman", Times, serif' => 'Times New Roman',
                                    '"Trebuchet MS", Helvetica, sans-serif' => 'Trebuchet MS',
                                    '"Courier New", Courier, monospace' => 'Courier New'
                                );

                                foreach ($fonts as $value => $label) {
                                    echo '<option value="' . esc_attr($value) . '" ' . selected($current_font, $value, false) . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php esc_html_e('Familia tipográfica para los formularios', 'englishline-test'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_custom_css"><?php esc_html_e('CSS personalizado', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <textarea id="englishline_test_custom_css" name="englishline_test_settings[custom_css]" rows="8" class="large-text code"><?php echo esc_textarea(get_option('englishline_test_settings')['custom_css'] ?? ''); ?></textarea>
                            <p class="description"><?php esc_html_e('Añade estilos CSS personalizados para los formularios', 'englishline-test'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Pestaña Avanzado -->
            <div id="tab-advanced" class="englishline-tab-content">
                <h2><?php esc_html_e('Configuración Avanzada', 'englishline-test'); ?></h2>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_recaptcha"><?php esc_html_e('Protección reCAPTCHA', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="englishline_test_recaptcha" name="englishline_test_settings[enable_recaptcha]"
                                    value="1" <?php checked(get_option('englishline_test_settings')['enable_recaptcha'] ?? '', '1'); ?>>
                                <?php esc_html_e('Habilitar Google reCAPTCHA v3', 'englishline-test'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Protege tus formularios contra spam y bots', 'englishline-test'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top" class="recaptcha-settings" style="<?php echo (get_option('englishline_test_settings')['enable_recaptcha'] ?? '') !== '1' ? 'display: none;' : ''; ?>">
                        <th scope="row">
                            <label for="englishline_test_recaptcha_site_key"><?php esc_html_e('Clave del sitio reCAPTCHA', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="englishline_test_recaptcha_site_key" name="englishline_test_settings[recaptcha_site_key]"
                                value="<?php echo esc_attr(get_option('englishline_test_settings')['recaptcha_site_key'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr valign="top" class="recaptcha-settings" style="<?php echo (get_option('englishline_test_settings')['enable_recaptcha'] ?? '') !== '1' ? 'display: none;' : ''; ?>">
                        <th scope="row">
                            <label for="englishline_test_recaptcha_secret_key"><?php esc_html_e('Clave secreta reCAPTCHA', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="englishline_test_recaptcha_secret_key" name="englishline_test_settings[recaptcha_secret_key]"
                                value="<?php echo esc_attr(get_option('englishline_test_settings')['recaptcha_secret_key'] ?? ''); ?>" class="regular-text">
                            <p class="description">
                                <?php esc_html_e('Obtén tus claves en', 'englishline-test'); ?>
                                <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA</a>
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_delete_data"><?php esc_html_e('Datos al desinstalar', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="englishline_test_delete_data" name="englishline_test_settings[delete_data]"
                                    value="1" <?php checked(get_option('englishline_test_settings')['delete_data'] ?? '', '1'); ?>>
                                <?php esc_html_e('Eliminar todos los datos al desinstalar el plugin', 'englishline-test'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Esto eliminará todas las tablas y opciones creadas por el plugin cuando se desinstale. Incluye exámenes, resultados y certificados.', 'englishline-test'); ?></p>

                            <div id="data-delete-warning" class="notice notice-warning inline" style="margin-top: 10px; padding: 10px; <?php echo (get_option('englishline_test_settings')['delete_data'] ?? '') !== '1' ? 'display: none;' : ''; ?>">
                                <p><strong><?php esc_html_e('¡Advertencia!', 'englishline-test'); ?></strong></p>
                                <p><?php esc_html_e('Si desinstala el plugin con esta opción activada, todos los datos serán eliminados permanentemente, incluyendo:', 'englishline-test'); ?></p>
                                <ul style="list-style: disc; margin-left: 20px;">
                                    <li><?php esc_html_e('Formularios y pruebas creados', 'englishline-test'); ?></li>
                                    <li><?php esc_html_e('Resultados y calificaciones de participantes', 'englishline-test'); ?></li>
                                    <li><?php esc_html_e('Configuración personalizada', 'englishline-test'); ?></li>
                                </ul>
                                <p><?php esc_html_e('Considere exportar sus datos antes de desinstalar si necesita conservarlos.', 'englishline-test'); ?></p>
                            </div>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="englishline_test_export_data"><?php esc_html_e('Exportar/Importar', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <p>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=englishline_export_data'), 'englishline_export_data')); ?>" class="button">
                                    <?php esc_html_e('Exportar todos los datos', 'englishline-test'); ?>
                                </a>
                            </p>

                            <p>
                                <input type="file" id="englishline_import_file" name="englishline_import_file" accept=".json">
                                <button type="submit" name="englishline_import_data" class="button" value="1">
                                    <?php esc_html_e('Importar datos', 'englishline-test'); ?>
                                </button>
                            </p>
                            <p class="description"><?php esc_html_e('Exporta o importa formularios y configuraciones', 'englishline-test'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label><?php esc_html_e('Actualización desde GitHub', 'englishline-test'); ?></label>
                        </th>
                        <td>
                            <p>
                                <a href="#" id="check-github-updates" class="button">
                                    <?php esc_html_e('Comprobar actualizaciones', 'englishline-test'); ?>
                                </a>
                                <span id="github-update-status"></span>
                            </p>
                            <p class="description"><?php esc_html_e('Comprueba y actualiza el plugin desde el repositorio de GitHub', 'englishline-test'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(); ?>
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
                localStorage.setItem('englishline_settings_tab', tabId);
            }
        });

        // Restaurar última pestaña activa
        if (typeof(Storage) !== "undefined") {
            let lastTab = localStorage.getItem('englishline_settings_tab');
            if (lastTab) {
                $('.tab-link[data-tab="' + lastTab + '"]').trigger('click');
            }
        }

        // Mostrar/ocultar campos de reCAPTCHA
        $('#englishline_test_recaptcha').on('change', function() {
            if ($(this).is(':checked')) {
                $('.recaptcha-settings').show();
            } else {
                $('.recaptcha-settings').hide();
            }
        });

        // Selector de logo
        $('#upload-logo-button').on('click', function(e) {
            e.preventDefault();

            // Asegurarse de que la API de medios esté cargada
            if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
                let logoUploader = wp.media({
                    title: '<?php esc_html_e('Seleccionar o subir logo', 'englishline-test'); ?>',
                    library: {
                        type: 'image'
                    },
                    button: {
                        text: '<?php esc_html_e('Usar este logo', 'englishline-test'); ?>'
                    },
                    multiple: false
                });

                logoUploader.on('select', function() {
                    let attachment = logoUploader.state().get('selection').first().toJSON();
                    $('#englishline_test_logo').val(attachment.id);

                    let $previewWrapper = $('.logo-preview-wrapper');
                    $previewWrapper.html('<img src="' + attachment.url + '" alt="<?php esc_attr_e('Logo de la empresa', 'englishline-test'); ?>" style="max-width: 200px; max-height: 100px;">');

                    $('#upload-logo-button').text('<?php esc_html_e('Cambiar logo', 'englishline-test'); ?>');

                    if (!$('#remove-logo-button').length) {
                        $('#upload-logo-button').after('<button type="button" class="button" id="remove-logo-button"><?php esc_html_e('Quitar logo', 'englishline-test'); ?></button>');
                    }
                });

                logoUploader.open();
            } else {
                console.error('La API de WordPress Media no está disponible.');
            }
        });

        // Quitar logo
        $(document).on('click', '#remove-logo-button', function(e) {
            e.preventDefault();

            $('#englishline_test_logo').val('');
            $('.logo-preview-wrapper').empty();
            $('#upload-logo-button').text('<?php esc_html_e('Subir logo', 'englishline-test'); ?>');
            $(this).remove();
        });

        // Mostrar/ocultar advertencia de eliminación de datos
        $('#englishline_test_delete_data').on('change', function() {
            if ($(this).is(':checked')) {
                $('#data-delete-warning').slideDown();
            } else {
                $('#data-delete-warning').slideUp();
            }
        });

        // Comprobar actualizaciones de GitHub
        $('#check-github-updates').on('click', function(e) {
            e.preventDefault();

            let $statusElement = $('#github-update-status');
            $statusElement.html('<span style="color:#999;"><?php esc_html_e('Comprobando...', 'englishline-test'); ?></span>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'englishline_check_github_updates',
                    nonce: '<?php echo wp_create_nonce('englishline_check_updates'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.update_available) {
                            $statusElement.html(
                                '<span style="color:green;"><?php esc_html_e('Nueva versión disponible:', 'englishline-test'); ?> ' + response.data.new_version + '</span> ' +
                                '<a href="#" class="button button-small update-from-github" data-version="' + response.data.new_version + '"><?php esc_html_e('Actualizar ahora', 'englishline-test'); ?></a>'
                            );
                        } else {
                            $statusElement.html('<span style="color:green;"><?php esc_html_e('Tienes la última versión', 'englishline-test'); ?></span>');
                        }
                    } else {
                        $statusElement.html('<span style="color:red;">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $statusElement.html('<span style="color:red;"><?php esc_html_e('Error al comprobar actualizaciones', 'englishline-test'); ?></span>');
                }
            });
        });

        // Actualizar desde GitHub
        $(document).on('click', '.update-from-github', function(e) {
            e.preventDefault();

            if (!confirm('<?php esc_html_e('¿Estás seguro de que deseas actualizar el plugin? Se recomienda hacer una copia de seguridad antes de continuar.', 'englishline-test'); ?>')) {
                return;
            }

            let $statusElement = $('#github-update-status');
            let version = $(this).data('version');

            $statusElement.html('<span style="color:#999;"><?php esc_html_e('Actualizando...', 'englishline-test'); ?></span>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'englishline_update_from_github',
                    nonce: '<?php echo wp_create_nonce('englishline_update_plugin'); ?>',
                    version: version
                },
                success: function(response) {
                    if (response.success) {
                        $statusElement.html('<span style="color:green;">' + response.data.message + '</span>');

                        // Recargar página después de 2 segundos
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $statusElement.html('<span style="color:red;">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $statusElement.html('<span style="color:red;"><?php esc_html_e('Error al actualizar el plugin', 'englishline-test'); ?></span>');
                }
            });
        });
    });
</script>