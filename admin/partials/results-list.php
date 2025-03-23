<?php
/**
 * Muestra una lista de resultados de formularios
 */

// Verificar que no se accede directamente
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$results_table = $wpdb->prefix . 'englishline_results';
$forms_table = $wpdb->prefix . 'englishline_forms';

// Manejar acciones
if (isset($_GET['action'])) {
    $action = sanitize_text_field($_GET['action']);
    $result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

    // Eliminar resultado
    if ($action === 'delete' && $result_id > 0) {
        // Verificar nonce
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_result_' . $result_id)) {
            // Eliminar el resultado
            $wpdb->delete(
                $results_table,
                ['id' => $result_id],
                ['%d']
            );

            add_settings_error(
                'englishline_result_messages',
                'result_deleted',
                __('El resultado ha sido eliminado correctamente.', 'englishline-test'),
                'success'
            );

            // Redirigir para evitar reenvío al actualizar
            wp_redirect(admin_url('admin.php?page=englishline-test-results&status=deleted'));
            exit;
        }
    }

    // Exportar resultados como CSV
    elseif ($action === 'export' && isset($_GET['form_id'])) {
        $form_id = intval($_GET['form_id']);

        if ($form_id > 0) {
            // Obtener datos del formulario
            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $form_id));

            if (!$form) {
                add_settings_error(
                    'englishline_result_messages',
                    'form_not_found',
                    __('El formulario solicitado no existe.', 'englishline-test'),
                    'error'
                );
            } else {
                // Obtener todos los resultados para este formulario
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT r.*, u.display_name as user_name, u.user_email as user_email_from_wp
                    FROM $results_table r
                    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                    WHERE r.form_id = %d
                    ORDER BY r.created_at DESC",
                    $form_id
                ));

                // Preparar datos para exportación
                $filename = sanitize_title($form->title) . '-resultados-' . date('Y-m-d') . '.csv';

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');

                $output = fopen('php://output', 'w');

                // Encabezados CSV
                fputcsv($output, [
                    'ID',
                    'Usuario',
                    'Email',
                    'Fecha',
                    'Estado',
                    'Puntuación',
                    'Respuestas'
                ]);

                foreach ($results as $result) {
                    // Decodificar los datos del formulario
                    $form_data = json_decode($result->form_data, true);
                    $user_data = [];

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

                    // Preparar respuestas para CSV
                    $responses = '';
                    if (is_array($form_data) && isset($form_data['form_data'])) {
                        $responses_array = [];
                        foreach ($form_data['form_data'] as $key => $answer) {
                            if (is_array($answer)) {
                                $responses_array[] = $key . ': ' . implode(', ', $answer);
                            } else {
                                $responses_array[] = $key . ': ' . $answer;
                            }
                        }
                        $responses = implode(' | ', $responses_array);
                    }

                    // Escribir fila en CSV
                    fputcsv($output, [
                        $result->id,
                        $result->user_name ?: (isset($user_data['first_name']) ? $user_data['first_name'] . ' ' . $user_data['last_name'] : 'Anónimo'),
                        $email,
                        $result->created_at,
                        $result->status,
                        $result->score !== null ? $result->score . '%' : 'N/A',
                        $responses
                    ]);
                }

                fclose($output);
                exit;
            }
        }
    }
}

// Paginación
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Filtros
$form_id_filter = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$user_email_filter = isset($_GET['user_email']) ? sanitize_email($_GET['user_email']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

// Construir consulta SQL con filtros
$sql_count = "SELECT COUNT(*) FROM $results_table r WHERE 1=1";
$sql = "SELECT r.*, f.title as form_title, u.display_name as user_name
        FROM $results_table r
        LEFT JOIN $forms_table f ON r.form_id = f.id
        LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
        WHERE 1=1";

$sql_params = array();
$count_params = array();

if ($form_id_filter > 0) {
    $sql .= " AND r.form_id = %d";
    $sql_count .= " AND r.form_id = %d";
    $sql_params[] = $form_id_filter;
    $count_params[] = $form_id_filter;
}

if (!empty($user_email_filter)) {
    $sql .= " AND (r.user_email LIKE %s OR u.user_email LIKE %s)";
    $sql_count .= " AND (r.user_email LIKE %s OR u.user_email LIKE %s)";
    $sql_params[] = '%' . $wpdb->esc_like($user_email_filter) . '%';
    $sql_params[] = '%' . $wpdb->esc_like($user_email_filter) . '%';
    $count_params[] = '%' . $wpdb->esc_like($user_email_filter) . '%';
    $count_params[] = '%' . $wpdb->esc_like($user_email_filter) . '%';
}

if (!empty($status_filter)) {
    $sql .= " AND r.status = %s";
    $sql_count .= " AND r.status = %s";
    $sql_params[] = $status_filter;
    $count_params[] = $status_filter;
}

if (!empty($date_from)) {
    $sql .= " AND r.created_at >= %s";
    $sql_count .= " AND r.created_at >= %s";
    $sql_params[] = $date_from . ' 00:00:00';
    $count_params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $sql .= " AND r.created_at <= %s";
    $sql_count .= " AND r.created_at <= %s";
    $sql_params[] = $date_to . ' 23:59:59';
    $count_params[] = $date_to . ' 23:59:59';
}

// Ordenamiento
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// Validar campos de ordenamiento permitidos
$allowed_orderby_fields = ['id', 'form_id', 'user_email', 'created_at', 'score', 'status'];
if (!in_array($orderby, $allowed_orderby_fields)) {
    $orderby = 'created_at';
}

// Validar dirección de ordenamiento
if (!in_array(strtoupper($order), ['ASC', 'DESC'])) {
    $order = 'DESC';
}

$sql .= " ORDER BY r.$orderby " . $order;

// Agregar límite para paginación
$sql .= " LIMIT %d OFFSET %d";
$sql_params[] = $items_per_page;
$sql_params[] = $offset;

// Preparar las consultas
$prepared_count_sql = !empty($count_params) ? $wpdb->prepare($sql_count, $count_params) : $sql_count;
$total_items = $wpdb->get_var($prepared_count_sql);
$total_pages = ceil($total_items / $items_per_page);

$prepared_sql = $wpdb->prepare($sql, $sql_params);
$results = $wpdb->get_results($prepared_sql);

// Obtener formularios para el selector de filtro
$forms = $wpdb->get_results("SELECT id, title FROM $forms_table ORDER BY title ASC");

// Estados disponibles para el filtro
$statuses = [
    'pending' => __('Pendiente', 'englishline-test'),
    'completed' => __('Completado', 'englishline-test'),
    'reviewed' => __('Revisado', 'englishline-test'),
    'failed' => __('Fallido', 'englishline-test')
];

// Mensajes de estado
if (isset($_GET['status'])) {
    $status = sanitize_text_field($_GET['status']);

    if ($status === 'deleted') {
        add_settings_error(
            'englishline_result_messages',
            'result_deleted',
            __('El resultado ha sido eliminado correctamente.', 'englishline-test'),
            'success'
        );
    }
}

// Función para generar el header de columna ordenable
function get_sortable_column_header($column, $title) {
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

    $base_url = admin_url('admin.php?page=englishline-test-results');
    $query_args = [];

    // Mantener filtros actuales
    foreach (['form_id', 'user_email', 'status_filter', 'date_from', 'date_to', 'paged'] as $arg) {
        if (isset($_GET[$arg]) && !empty($_GET[$arg])) {
            $query_args[$arg] = $_GET[$arg];
        }
    }

    // Nueva dirección de ordenamiento
    $new_order = ($orderby === $column && $order === 'ASC') ? 'DESC' : 'ASC';
    $url = add_query_arg(array_merge($query_args, ['orderby' => $column, 'order' => $new_order]), $base_url);

    $sorted_class = '';
    $sort_indicator = '';

    if ($orderby === $column) {
        $sorted_class = ' sorted ' . strtolower($order);
        $sort_indicator = $order === 'ASC' ? ' ↑' : ' ↓';
    }

    return sprintf('<a href="%s" class="sortable%s">%s%s</a>',
        esc_url($url),
        esc_attr($sorted_class),
        esc_html($title),
        $sort_indicator
    );
}
?>

<div class="wrap englishline-admin-wrap">
    <div class="englishline-admin-header">
        <h1 class="wp-heading-inline"><?php esc_html_e('Resultados de Tests', 'englishline-test'); ?></h1>
        <hr class="wp-header-end">
    </div>

    <div class="englishline-admin-content">
        <?php settings_errors('englishline_result_messages'); ?>

        <div class="englishline-filters">
            <form method="get" action="" class="englishline-filters-form">
                <input type="hidden" name="page" value="englishline-test-results">

                <div class="englishline-search-box">
                    <input type="email" name="user_email" placeholder="<?php esc_attr_e('Buscar por email...', 'englishline-test'); ?>" value="<?php echo esc_attr($user_email_filter); ?>">
                    <button type="submit" class="button"><span class="dashicons dashicons-search"></span></button>
                </div>

                <div class="englishline-filter-options">
                    <select name="form_id">
                        <option value="0"><?php esc_html_e('Todos los formularios', 'englishline-test'); ?></option>
                        <?php foreach ($forms as $form) : ?>
                            <option value="<?php echo esc_attr($form->id); ?>" <?php selected($form_id_filter, $form->id); ?>>
                                <?php echo esc_html($form->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status_filter">
                        <option value=""><?php esc_html_e('Todos los estados', 'englishline-test'); ?></option>
                        <?php foreach ($statuses as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($status_filter, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="date-filters">
                        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="<?php esc_attr_e('Desde', 'englishline-test'); ?>">
                        <span class="date-separator">-</span>
                        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="<?php esc_attr_e('Hasta', 'englishline-test'); ?>">
                    </div>

                    <button type="submit" class="button filter-button"><?php esc_html_e('Filtrar', 'englishline-test'); ?></button>

                    <?php if ($form_id_filter || $user_email_filter || $date_from || $date_to || $status_filter) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results')); ?>" class="button reset-button">
                            <?php esc_html_e('Restablecer', 'englishline-test'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="englishline-stats-bar">
            <div class="stats-item">
                <span class="stats-value"><?php echo esc_html($total_items); ?></span>
                <span class="stats-label"><?php esc_html_e('Total de resultados', 'englishline-test'); ?></span>
            </div>

            <?php if ($form_id_filter > 0 && $total_items > 0) :
                $stats = $wpdb->get_row($wpdb->prepare(
                    "SELECT
                        AVG(score) as avg_score,
                        MAX(score) as max_score,
                        MIN(score) as min_score
                    FROM $results_table
                    WHERE form_id = %d AND score IS NOT NULL",
                    $form_id_filter
                ));
            ?>
                <div class="stats-item">
                    <span class="stats-value"><?php echo $stats->avg_score ? number_format($stats->avg_score, 1) . '%' : 'N/A'; ?></span>
                    <span class="stats-label"><?php esc_html_e('Puntuación promedio', 'englishline-test'); ?></span>
                </div>
                <div class="stats-item">
                    <span class="stats-value"><?php echo $stats->max_score ? number_format($stats->max_score, 1) . '%' : 'N/A'; ?></span>
                    <span class="stats-label"><?php esc_html_e('Puntuación máxima', 'englishline-test'); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($form_id_filter > 0) : ?>
                <div class="stats-item export-link">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results&action=export&form_id=' . $form_id_filter)); ?>" class="button">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Exportar como CSV', 'englishline-test'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($results) : ?>
            <table class="wp-list-table widefat fixed striped englishline-results-table">
                <thead>
                    <tr>
                        <th class="column-id"><?php echo get_sortable_column_header('id', __('ID', 'englishline-test')); ?></th>
                        <th class="column-form"><?php esc_html_e('Formulario', 'englishline-test'); ?></th>
                        <th class="column-user"><?php esc_html_e('Usuario / Email', 'englishline-test'); ?></th>
                        <th class="column-date"><?php echo get_sortable_column_header('created_at', __('Fecha', 'englishline-test')); ?></th>
                        <th class="column-score"><?php echo get_sortable_column_header('score', __('Puntuación', 'englishline-test')); ?></th>
                        <th class="column-status"><?php echo get_sortable_column_header('status', __('Estado', 'englishline-test')); ?></th>
                        <th class="column-actions"><?php esc_html_e('Acciones', 'englishline-test'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result) :
                        // Decodificar datos de usuario si están disponibles
                        $form_data = json_decode($result->form_data, true);
                        $user_data = [];

                        if (is_array($form_data) && isset($form_data['user_data'])) {
                            $user_data = $form_data['user_data'];
                        }

                        // Determinar información de usuario a mostrar
                        $user_display = $result->user_name;
                        if (!$user_display && isset($user_data['first_name'])) {
                            $user_display = $user_data['first_name'] . ' ' . $user_data['last_name'];
                        }

                        $email_display = $result->user_email;
                        if (empty($email_display) && isset($user_data['email'])) {
                            $email_display = $user_data['email'];
                        }
                    ?>
                        <tr>
                            <td class="column-id"><?php echo esc_html($result->id); ?></td>
                            <td class="column-form"><?php echo esc_html($result->form_title); ?></td>
                            <td class="column-user">
                                <strong><?php echo $user_display ? esc_html($user_display) : esc_html__('Usuario anónimo', 'englishline-test'); ?></strong>
                                <?php if ($email_display) : ?>
                                    <br><span class="user-email"><?php echo esc_html($email_display); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-date">
                                <?php
                                if (isset($result->created_at)) {
                                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($result->created_at)));
                                } elseif (isset($result->submitted_at)) {
                                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($result->submitted_at)));
                                }
                                ?>
                            </td>
                            <td class="column-score">
                                <?php
                                if ($result->score !== null) {
                                    echo '<span class="score-badge">' . esc_html($result->score) . '%</span>';
                                } else {
                                    echo '<span class="no-score">' . esc_html__('N/A', 'englishline-test') . '</span>';
                                }
                                ?>
                            </td>
                            <td class="column-status">
                                <?php
                                $status_class = 'status-' . $result->status;
                                $status_text = isset($statuses[$result->status]) ? $statuses[$result->status] : ucfirst($result->status);
                                echo '<span class="status-badge ' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span>';
                                ?>
                            </td>
                            <td class="column-actions">
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results&action=view&result_id=' . $result->id)); ?>" class="button button-small">
                                            <span class="dashicons dashicons-visibility"></span>
                                            <?php esc_html_e('Ver', 'englishline-test'); ?>
                                        </a>
                                    </span>

                                    <span class="delete">
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=englishline-test-results&action=delete&result_id=' . $result->id), 'delete_result_' . $result->id)); ?>"
                                           class="button button-small"
                                           onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que deseas eliminar este resultado? Esta acción no se puede deshacer.', 'englishline-test'); ?>')">
                                            <span class="dashicons dashicons-trash"></span>
                                            <?php esc_html_e('Eliminar', 'englishline-test'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ($total_pages > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(
                                _n('%s elemento', '%s elementos', $total_items, 'englishline-test'),
                                number_format_i18n($total_items)
                            ); ?>
                        </span>

                        <span class="pagination-links">
                            <?php
                            // Construir la URL base para la paginación manteniendo los filtros
                            $base_url = admin_url('admin.php?page=englishline-test-results');
                            $query_args = [];

                            foreach (['form_id', 'user_email', 'status_filter', 'date_from', 'date_to', 'orderby', 'order'] as $arg) {
                                if (isset($_GET[$arg]) && !empty($_GET[$arg])) {
                                    $query_args[$arg] = $_GET[$arg];
                                }
                            }

                            // Primera página
                            $first_url = add_query_arg(array_merge($query_args, ['paged' => 1]), $base_url);
                            if ($page > 1) {
                                printf('<a class="first-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a>',
                                    esc_url($first_url),
                                    esc_html__('Primera página', 'englishline-test'),
                                    '«'
                                );
                            } else {
                                printf('<span class="first-page disabled"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></span>',
                                    esc_html__('Primera página', 'englishline-test'),
                                    '«'
                                );
                            }

                            // Página anterior
                            $prev_page = max(1, $page - 1);
                            $prev_url = add_query_arg(array_merge($query_args, ['paged' => $prev_page]), $base_url);
                            if ($page > 1) {
                                printf('<a class="prev-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a>',
                                    esc_url($prev_url),
                                    esc_html__('Página anterior', 'englishline-test'),
                                    '‹'
                                );
                            } else {
                                printf('<span class="prev-page disabled"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></span>',
                                    esc_html__('Página anterior', 'englishline-test'),
                                    '‹'
                                );
                            }

                            // Selector de página actual
                            printf('<span class="paging-input"><label for="current-page-selector" class="screen-reader-text">%s</label>',
                                esc_html__('Página actual', 'englishline-test')
                            );
                            printf('<input class="current-page" id="current-page-selector" type="text" name="paged" value="%s" size="3" aria-describedby="table-paging">',
                                esc_attr($page)
                            );
                            printf('<span class="tablenav-paging-text"> %s <span class="total-pages">%s</span></span></span>',
                                esc_html__('de', 'englishline-test'),
                                number_format_i18n($total_pages)
                            );

                            // Página siguiente
                            $next_page = min($total_pages, $page + 1);
                            $next_url = add_query_arg(array_merge($query_args, ['paged' => $next_page]), $base_url);
                            if ($page < $total_pages) {
                                printf('<a class="next-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a>',
                                    esc_url($next_url),
                                    esc_html__('Página siguiente', 'englishline-test'),
                                    '›'
                                );
                            } else {
                                printf('<span class="next-page disabled"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></span>',
                                    esc_html__('Página siguiente', 'englishline-test'),
                                    '›'
                                );
                            }

                            // Última página
                            $last_url = add_query_arg(array_merge($query_args, ['paged' => $total_pages]), $base_url);
                            if ($page < $total_pages) {
                                printf('<a class="last-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a>',
                                    esc_url($last_url),
                                    esc_html__('Última página', 'englishline-test'),
                                    '»'
                                );
                            } else {
                                printf('<span class="last-page disabled"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></span>',
                                    esc_html__('Última página', 'englishline-test'),
                                    '»'
                                );
                            }
                            ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

        <?php else : ?>
            <div class="no-items">
                <p><?php esc_html_e('No se encontraron resultados para los filtros seleccionados.', 'englishline-test'); ?></p>
                <?php if ($form_id_filter || $user_email_filter || $date_from || $date_to || $status_filter) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results')); ?>" class="button">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Restablecer filtros', 'englishline-test'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>