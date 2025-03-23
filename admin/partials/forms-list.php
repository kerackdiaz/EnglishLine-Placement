<?php
/**
 * Proporciona una vista de lista de todos los formularios
 */

if (!defined('ABSPATH')) {
    exit;
}

// Preparar la tabla de formularios
$forms = array(); 

global $wpdb;
$table_name = $wpdb->prefix . 'englishline_forms';


$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

if ($table_exists) {
    $forms = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC", ARRAY_A);
}

// Manejar mensajes de estado para operaciones CRUD
$status_message = '';
$message_type = '';

if (isset($_GET['status']) && isset($_GET['form_id'])) {
    $form_id = intval($_GET['form_id']);
    
    switch ($_GET['status']) {
        case 'duplicated':
            $status_message = sprintf(__('Formulario duplicado con éxito (ID: %d).', 'englishline-test'), $form_id);
            $message_type = 'success';
            break;
        case 'deleted':
            $status_message = __('Formulario eliminado con éxito.', 'englishline-test');
            $message_type = 'success';
            break;
        case 'error':
            $status_message = __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'englishline-test');
            $message_type = 'error';
            break;
    }
}
?>

<div class="wrap englishline-admin-wrap">
    <div class="englishline-admin-header">
        <h1 class="wp-heading-inline">
            <?php esc_html_e('Formularios', 'englishline-test'); ?>
        </h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-form-editor')); ?>" class="page-title-action">
            <?php esc_html_e('Añadir nuevo', 'englishline-test'); ?>
        </a>
    </div>

    <?php if ($status_message) : ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($status_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="englishline-admin-content">
        <?php if (empty($forms)) : ?>
            <div class="englishline-empty-state">
                <div class="englishline-empty-icon">
                    <span class="dashicons dashicons-format-aside"></span>
                </div>
                <h2><?php esc_html_e('No hay formularios todavía', 'englishline-test'); ?></h2>
                <p><?php esc_html_e('Comienza creando tu primer formulario de examen.', 'englishline-test'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-form-editor')); ?>" class="button button-primary button-hero">
                    <?php esc_html_e('Crear formulario', 'englishline-test'); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="englishline-filters">
                <div class="englishline-search-box">
                    <input type="text" id="englishline-form-search" placeholder="<?php esc_attr_e('Buscar formularios...', 'englishline-test'); ?>">
                </div>
                <div class="englishline-filter-options">
                    <select id="englishline-form-status-filter">
                        <option value=""><?php esc_html_e('Todos los estados', 'englishline-test'); ?></option>
                        <option value="published"><?php esc_html_e('Publicado', 'englishline-test'); ?></option>
                        <option value="draft"><?php esc_html_e('Borrador', 'englishline-test'); ?></option>
                        <option value="archived"><?php esc_html_e('Archivado', 'englishline-test'); ?></option>
                    </select>
                    <select id="englishline-form-date-filter">
                        <option value=""><?php esc_html_e('Cualquier fecha', 'englishline-test'); ?></option>
                        <option value="today"><?php esc_html_e('Hoy', 'englishline-test'); ?></option>
                        <option value="this_week"><?php esc_html_e('Esta semana', 'englishline-test'); ?></option>
                        <option value="this_month"><?php esc_html_e('Este mes', 'englishline-test'); ?></option>
                    </select>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped englishline-forms-table">
                <thead>
                    <tr>
                        <th class="column-title"><?php esc_html_e('Título', 'englishline-test'); ?></th>
                        <th class="column-shortcode"><?php esc_html_e('Shortcode', 'englishline-test'); ?></th>
                        <th class="column-submissions"><?php esc_html_e('Envíos', 'englishline-test'); ?></th>
                        <th class="column-status"><?php esc_html_e('Estado', 'englishline-test'); ?></th>
                        <th class="column-date"><?php esc_html_e('Fecha', 'englishline-test'); ?></th>
                        <th class="column-actions"><?php esc_html_e('Acciones', 'englishline-test'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form) : ?>
                        <?php 

                            $submissions_count = 0;
                            if ($table_exists) {
                                $submissions_table = $wpdb->prefix . 'englishline_submissions';
                                $submissions_count = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$submissions_table} WHERE form_id = %d",
                                    $form['id']
                                ));
                            }
                            
                            $date_created = date_i18n(get_option('date_format'), strtotime($form['created_at']));

                            $row_classes = 'englishline-form-row';
                            $row_classes .= ' status-' . esc_attr($form['status']);

                            $created_timestamp = strtotime($form['created_at']);
                            $today_start = strtotime('today');
                            $week_start = strtotime('monday this week');
                            $month_start = strtotime('first day of this month');
                            
                            if ($created_timestamp >= $today_start) {
                                $row_classes .= ' date-today';
                            }
                            if ($created_timestamp >= $week_start) {
                                $row_classes .= ' date-this-week';
                            }
                            if ($created_timestamp >= $month_start) {
                                $row_classes .= ' date-this-month';
                            }
                        ?>
                        <tr class="<?php echo esc_attr($row_classes); ?>" data-form-id="<?php echo esc_attr($form['id']); ?>">
                            <td class="column-title">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-form-editor&form_id=' . $form['id'])); ?>" class="row-title">
                                    <?php echo esc_html($form['title']); ?>
                                </a>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-form-editor&form_id=' . $form['id'])); ?>">
                                            <?php esc_html_e('Editar', 'englishline-test'); ?>
                                        </a> | 
                                    </span>
                                    <span class="duplicate">
                                        <a href="#" class="englishline-duplicate-form" data-form-id="<?php echo esc_attr($form['id']); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('englishline_duplicate_form_' . $form['id'])); ?>">
                                            <?php esc_html_e('Duplicar', 'englishline-test'); ?>
                                        </a> | 
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-form-preview&form_id=' . $form['id'])); ?>" target="_blank">
                                            <?php esc_html_e('Vista previa', 'englishline-test'); ?>
                                        </a> | 
                                    </span>
                                    <span class="trash">
                                        <a href="#" class="englishline-delete-form" data-form-id="<?php echo esc_attr($form['id']); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('englishline_delete_form_' . $form['id'])); ?>">
                                            <?php esc_html_e('Eliminar', 'englishline-test'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-shortcode">
                                <code>[englishline_test id="<?php echo esc_attr($form['id']); ?>"]</code>
                                <button type="button" class="button button-small copy-shortcode" data-shortcode='[englishline_form id="<?php echo esc_attr($form['id']); ?>"]'>
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </td>
                            <td class="column-submissions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results&form_id=' . $form['id'])); ?>">
                                    <?php echo esc_html($submissions_count); ?>
                                </a>
                            </td>
                            <td class="column-status">
                                <?php
                                $status_text = '';
                                $status_class = '';
                                
                                switch ($form['status']) {
                                    case 'published':
                                        $status_text = __('Publicado', 'englishline-test');
                                        $status_class = 'status-published';
                                        break;
                                    case 'draft':
                                        $status_text = __('Borrador', 'englishline-test');
                                        $status_class = 'status-draft';
                                        break;
                                    case 'archived':
                                        $status_text = __('Archivado', 'englishline-test');
                                        $status_class = 'status-archived';
                                        break;
                                }
                                ?>
                                <span class="englishline-status <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($status_text); ?>
                                </span>
                            </td>
                            <td class="column-date">
                                <?php echo esc_html($date_created); ?>
                            </td>
                            <td class="column-actions">
                                <div class="englishline-row-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-form-editor&form_id=' . $form['id'])); ?>" class="button button-small" aria-label="<?php esc_attr_e('Editar este formulario', 'englishline-test'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results&form_id=' . $form['id'])); ?>" class="button button-small" aria-label="<?php esc_attr_e('Ver resultados', 'englishline-test'); ?>">
                                        <span class="dashicons dashicons-chart-bar"></span>
                                    </a>
                                    <a href="#" class="button button-small englishline-form-toggle-status" data-form-id="<?php echo esc_attr($form['id']); ?>" data-current-status="<?php echo esc_attr($form['status']); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('englishline_toggle_form_status_' . $form['id'])); ?>" aria-label="<?php esc_attr_e('Cambiar estado', 'englishline-test'); ?>">
                                        <?php if ($form['status'] === 'published') : ?>
                                            <span class="dashicons dashicons-visibility"></span>
                                        <?php else : ?>
                                            <span class="dashicons dashicons-hidden"></span> 
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (count($forms) > 10) : ?>
                <div class="englishline-pagination">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo sprintf(_n('%s elemento', '%s elementos', count($forms), 'englishline-test'), number_format_i18n(count($forms))); ?></span>
                        <span class="pagination-links">
                            <a class="first-page button disabled" href="#"><span class="screen-reader-text"><?php esc_html_e('Primera página', 'englishline-test'); ?></span><span aria-hidden="true">«</span></a>
                            <a class="prev-page button disabled" href="#"><span class="screen-reader-text"><?php esc_html_e('Página anterior', 'englishline-test'); ?></span><span aria-hidden="true">‹</span></a>
                            <span class="paging-input">
                                <label for="current-page-selector" class="screen-reader-text"><?php esc_html_e('Página actual', 'englishline-test'); ?></label>
                                <input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging">
                                <span class="tablenav-paging-text"> <?php esc_html_e('de', 'englishline-test'); ?> <span class="total-pages">1</span></span>
                            </span>
                            <a class="next-page button" href="#"><span class="screen-reader-text"><?php esc_html_e('Página siguiente', 'englishline-test'); ?></span><span aria-hidden="true">›</span></a>
                            <a class="last-page button" href="#"><span class="screen-reader-text"><?php esc_html_e('Última página', 'englishline-test'); ?></span><span aria-hidden="true">»</span></a>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div id="englishline-delete-modal" class="englishline-modal">
        <div class="englishline-modal-content">
            <span class="englishline-close-modal">&times;</span>
            <div class="englishline-modal-header">
                <h3><?php esc_html_e('Confirmar eliminación', 'englishline-test'); ?></h3>
            </div>
            <div class="englishline-modal-body">
                <p><?php esc_html_e('¿Estás seguro de que deseas eliminar este formulario? Esta acción no se puede deshacer.', 'englishline-test'); ?></p>
                <p><?php esc_html_e('Todos los envíos asociados a este formulario también serán eliminados.', 'englishline-test'); ?></p>
            </div>
            <div class="englishline-modal-footer">
                <button class="button" id="englishline-cancel-delete"><?php esc_html_e('Cancelar', 'englishline-test'); ?></button>
                <button class="button button-danger" id="englishline-confirm-delete"><?php esc_html_e('Sí, eliminar', 'englishline-test'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {

        let deleteModal = $('#englishline-delete-modal');
        let currentFormId = null;
        let deleteNonce = '';

        function filterFormsTable() {
            let searchTerm = $('#englishline-form-search').val().toLowerCase();
            let statusFilter = $('#englishline-form-status-filter').val();
            let dateFilter = $('#englishline-form-date-filter').val();

            $('.englishline-form-row').each(function() {
                let $row = $(this);
                let title = $row.find('.column-title a.row-title').text().toLowerCase();
                let showBySearch = searchTerm === '' || title.indexOf(searchTerm) > -1;
                let showByStatus = statusFilter === '' || $row.hasClass('status-' + statusFilter);
                let showByDate = dateFilter === '' || $row.hasClass('date-' + dateFilter.replace('_', '-'));

                if (showBySearch && showByStatus && showByDate) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });

            updatePagination();
        }

        function updatePagination() {
            let visibleRows = $('.englishline-form-row:visible');
            let totalRows = $('.englishline-form-row').length;
            let totalPages = Math.ceil(visibleRows.length / 10);

            $('.total-pages').text(totalPages);

            if (totalPages > 1) {
                $('.englishline-pagination').show();
            } else {
                $('.englishline-pagination').hide();
            }
        }


        $('#englishline-form-search').on('keyup', filterFormsTable);
        $('#englishline-form-status-filter, #englishline-form-date-filter').on('change', filterFormsTable);


        $('.copy-shortcode').on('click', function() {
            let shortcode = $(this).data('shortcode');
            let tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(shortcode).select();
            document.execCommand('copy');
            tempInput.remove();
            

            let $button = $(this);
            $button.addClass('copied');
            setTimeout(function() {
                $button.removeClass('copied');
            }, 1000);
        });

        $('.englishline-delete-form').on('click', function(e) {
            e.preventDefault();
            currentFormId = $(this).data('form-id');
            deleteNonce = $(this).data('nonce');
            deleteModal.show();
        });


        $('.englishline-close-modal, #englishline-cancel-delete').on('click', function() {
            deleteModal.hide();
        });

        $('#englishline-confirm-delete').on('click', function() {
            if (currentFormId && deleteNonce) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'englishline_delete_form',
                        form_id: currentFormId,
                        nonce: deleteNonce
                    },
                    success: function(response) {
                        if (response.success) {

                            window.location.href = '<?php echo esc_url(admin_url('admin.php?page=englishline-test-forms&status=deleted')); ?>&form_id=' + currentFormId;
                        } else {
                            alert(response.data.message || '<?php esc_html_e('Ha ocurrido un error al eliminar el formulario.', 'englishline-test'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e('Ha ocurrido un error al comunicarse con el servidor.', 'englishline-test'); ?>');
                    },
                    complete: function() {
                        deleteModal.hide();
                    }
                });
            }
        });


        $('.englishline-duplicate-form').on('click', function(e) {
            e.preventDefault();
            let formId = $(this).data('form-id');
            let nonce = $(this).data('nonce');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'englishline_duplicate_form',
                    form_id: formId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {

                        window.location.href = '<?php echo esc_url(admin_url('admin.php?page=englishline-test-forms&status=duplicated')); ?>&form_id=' + response.data.new_form_id;
                    } else {
                        alert(response.data.message || '<?php esc_html_e('Ha ocurrido un error al duplicar el formulario.', 'englishline-test'); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e('Ha ocurrido un error al comunicarse con el servidor.', 'englishline-test'); ?>');
                }
            });
        });


        $('.englishline-form-toggle-status').on('click', function(e) {
            e.preventDefault();
            let $button = $(this);
            let formId = $button.data('form-id');
            let currentStatus = $button.data('current-status');
            let nonce = $button.data('nonce');
            let newStatus = currentStatus === 'published' ? 'draft' : 'published';
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'englishline_toggle_form_status',
                    form_id: formId,
                    new_status: newStatus,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {

                        let $row = $button.closest('tr');
                        $row.removeClass('status-' + currentStatus).addClass('status-' + newStatus);
                        $button.data('current-status', newStatus);
                        

                        let $icon = $button.find('.dashicons');
                        if (newStatus === 'published') {

                            $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                            $row.find('.column-status .englishline-status')
                                .removeClass('status-draft status-archived')
                                .addClass('status-published')
                                .text('<?php esc_html_e('Publicado', 'englishline-test'); ?>');
                        } else {
                            $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                            $row.find('.column-status .englishline-status')
                                .removeClass('status-published status-archived')
                                .addClass('status-draft')
                                .text('<?php esc_html_e('Borrador', 'englishline-test'); ?>');
                        }
                    } else {
                        alert(response.data.message || '<?php esc_html_e('Ha ocurrido un error al cambiar el estado del formulario.', 'englishline-test'); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e('Ha ocurrido un error al comunicarse con el servidor.', 'englishline-test'); ?>');
                }
            });
        });


        $(window).on('click', function(e) {
            if ($(e.target).is(deleteModal)) {
                deleteModal.hide();
            }
        });
    });
</script>

<style>

.englishline-filters {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.englishline-search-box input {
    width: 250px;
    padding: 6px 10px;
}

.englishline-filter-options select {
    margin-left: 10px;
}

.englishline-forms-table {
    margin-top: 15px;
}

.englishline-forms-table .column-title {
    width: 30%;
}

.englishline-forms-table .column-shortcode {
    width: 25%;
}

.englishline-forms-table .column-submissions,
.englishline-forms-table .column-status,
.englishline-forms-table .column-date {
    width: 10%;
}

.englishline-forms-table .column-actions {
    width: 15%;
    text-align: right;
}

.englishline-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.status-published {
    background-color: #e7f7ed;
    color: #0a7227;
}

.status-draft {
    background-color: #f0f0f1;
    color: #50575e;
}

.status-archived {
    background-color: #fef8ee;
    color: #c76919;
}

.copy-shortcode {
    margin-left: 5px;
    vertical-align: baseline;
}

.copy-shortcode.copied {
    background-color: #e7f7ed;
}

.englishline-row-actions {
    display: flex;
    justify-content: flex-end;
}

.englishline-row-actions .button {
    margin-left: 5px;
}

/* Modal de eliminación */
.englishline-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.englishline-modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 4px;
    width: 500px;
    max-width: 90%;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.englishline-close-modal {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.englishline-modal-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.englishline-modal-footer {
    margin-top: 20px;
    text-align: right;
}

.button-danger {
    background-color: #d63638 !important;
    border-color: #d63638 !important;
    color: #fff !important;
}

.button-danger:hover {
    background-color: #b32d2e !important;
    border-color: #b32d2e !important;
}

/* Estado vacío */
.englishline-empty-state {
    text-align: center;
    padding: 60px 20px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 20px;
}

.englishline-empty-icon {
    margin-bottom: 20px;
}

.englishline-empty-icon .dashicons {
    font-size: 60px;
    width: 60px;
    height: 60px;
    color: #c3c4c7;
}

.englishline-empty-state h2 {
    font-size: 24px;
    margin-bottom: 15px;
}

.englishline-empty-state p {
    font-size: 16px;
    margin-bottom: 30px;
    color: #646970;
}
</style>