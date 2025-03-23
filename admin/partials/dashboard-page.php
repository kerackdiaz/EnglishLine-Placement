<?php
/**
 * Proporciona una vista de panel de control para el plugin
 */
?>

<div class="wrap englishline-admin-wrap">
    <div class="englishline-admin-header">
        <h1><?php esc_html_e('EnglishLine Test - Panel de Control', 'englishline-test'); ?></h1>
    </div>
    
    <div class="englishline-admin-content">
        <h2><?php esc_html_e('Bienvenido al sistema de formularios para pruebas de inglés', 'englishline-test'); ?></h2>
        <p><?php esc_html_e('Desde este panel de control puedes gestionar tus formularios de pruebas de inglés, revisar resultados y configurar el plugin.', 'englishline-test'); ?></p>
        
        <div class="englishline-dashboard-stats">
            <?php
            global $wpdb;
            
            // Obtener estadísticas
            $forms_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}englishline_forms");
            $results_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}englishline_results");
            $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}englishline_results WHERE status = 'pending'");
            ?>
            
            <div class="dashboard-stats-grid">
                <div class="stats-card">
                    <h3><?php esc_html_e('Formularios', 'englishline-test'); ?></h3>
                    <div class="stats-number"><?php echo esc_html($forms_count); ?></div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-forms')); ?>" class="stats-link">
                        <?php esc_html_e('Ver todos', 'englishline-test'); ?>
                    </a>
                </div>
                
                <div class="stats-card">
                    <h3><?php esc_html_e('Resultados', 'englishline-test'); ?></h3>
                    <div class="stats-number"><?php echo esc_html($results_count); ?></div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results')); ?>" class="stats-link">
                        <?php esc_html_e('Ver todos', 'englishline-test'); ?>
                    </a>
                </div>
                
                <div class="stats-card">
                    <h3><?php esc_html_e('Pendientes de calificar', 'englishline-test'); ?></h3>
                    <div class="stats-number"><?php echo esc_html($pending_count); ?></div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results&status=pending')); ?>" class="stats-link">
                        <?php esc_html_e('Calificar ahora', 'englishline-test'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="englishline-dashboard-actions">
            <h3><?php esc_html_e('Acciones rápidas', 'englishline-test'); ?></h3>
            
            <div class="dashboard-actions-grid">
                <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-forms&action=new')); ?>" class="dashboard-action-card">
                    <span class="action-icon dashicons dashicons-plus-alt2"></span>
                    <div class="action-content">
                        <h4><?php esc_html_e('Crear nuevo formulario', 'englishline-test'); ?></h4>
                        <p><?php esc_html_e('Diseña un nuevo test de inglés con nuestro editor intuitivo', 'englishline-test'); ?></p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-forms')); ?>" class="dashboard-action-card">
                    <span class="action-icon dashicons dashicons-list-view"></span>
                    <div class="action-content">
                        <h4><?php esc_html_e('Gestionar formularios', 'englishline-test'); ?></h4>
                        <p><?php esc_html_e('Ver, editar y administrar todos tus formularios existentes', 'englishline-test'); ?></p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results')); ?>" class="dashboard-action-card">
                    <span class="action-icon dashicons dashicons-chart-bar"></span>
                    <div class="action-content">
                        <h4><?php esc_html_e('Ver todos los resultados', 'englishline-test'); ?></h4>
                        <p><?php esc_html_e('Consulta las respuestas y calificaciones de todos los tests', 'englishline-test'); ?></p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=englishline-test-results&status=pending')); ?>" class="dashboard-action-card action-highlight">
                    <span class="action-icon dashicons dashicons-yes-alt"></span>
                    <div class="action-content">
                        <h4><?php esc_html_e('Calificar tests pendientes', 'englishline-test'); ?></h4>
                        <p><?php esc_html_e('Revisa y califica pruebas que requieren evaluación manual', 'englishline-test'); ?></p>
                    </div>
                    <?php if ($pending_count > 0): ?>
                        <div class="action-badge"><?php echo esc_html($pending_count); ?></div>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        
        <div class="englishline-dashboard-help">
            <h3><?php esc_html_e('Ayuda y recursos', 'englishline-test'); ?></h3>
            
            <div class="dashboard-help-grid">
                <div class="help-card">
                    <span class="help-icon dashicons dashicons-book"></span>
                    <h4><?php esc_html_e('Documentación', 'englishline-test'); ?></h4>
                    <p><?php esc_html_e('Aprende a sacar el máximo partido al plugin con nuestra guía detallada', 'englishline-test'); ?></p>
                    <a href="#" class="help-link"><?php esc_html_e('Ver documentación', 'englishline-test'); ?></a>
                </div>
                
                <div class="help-card">
                    <span class="help-icon dashicons dashicons-editor-help"></span>
                    <h4><?php esc_html_e('Soporte técnico', 'englishline-test'); ?></h4>
                    <p><?php esc_html_e('¿Tienes problemas o preguntas? Nuestro equipo está listo para ayudarte', 'englishline-test'); ?></p>
                    <a href="#" class="help-link"><?php esc_html_e('Contactar soporte', 'englishline-test'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos para el panel de control renovado */
    .englishline-admin-wrap {
        max-width: 100%;
        margin-top: 20px;
    }
    
    .englishline-admin-header {
        margin-bottom: 25px;
    }
    
    /* Tarjetas de estadísticas */
    .dashboard-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stats-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .stats-card h3 {
        margin-top: 0;
        color: #555;
        font-size: 16px;
    }
    
    .stats-number {
        font-size: 36px;
        font-weight: 700;
        color: #1d2327;
        margin: 15px 0;
    }
    
    .stats-link {
        display: inline-block;
        padding: 6px 12px;
        background: #f0f0f1;
        border-radius: 4px;
        text-decoration: none;
        color: #2271b1;
        font-weight: 500;
        transition: background 0.2s ease;
    }
    
    .stats-link:hover {
        background: #e0e0e1;
        color: #135e96;
    }
    
    /* Tarjetas de acciones */
    .englishline-dashboard-actions {
        margin-bottom: 40px;
    }
    
    .englishline-dashboard-actions h3,
    .englishline-dashboard-help h3 {
        font-size: 18px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    
    .dashboard-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .dashboard-action-card {
        display: flex;
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        text-decoration: none;
        color: #333;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        position: relative;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .dashboard-action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        border-color: #2271b1;
    }
    
    .action-icon {
        font-size: 28px;
        margin-right: 15px;
        color: #2271b1;
        align-self: center;
    }
    
    .action-content {
        flex: 1;
    }
    
    .action-content h4 {
        margin-top: 0;
        margin-bottom: 8px;
        color: #1d2327;
    }
    
    .action-content p {
        margin: 0;
        color: #646970;
        font-size: 13px;
    }
    
    .action-highlight {
        background: #f0f6fc;
        border-color: #2271b1;
    }
    
    .action-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #d63638;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }
    
    /* Ayuda y recursos */
    .dashboard-help-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .help-card {
        background: #fff;
        border-radius: 8px;
        padding: 25px;
        text-align: center;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .help-icon {
        font-size: 32px;
        color: #2271b1;
        margin-bottom: 15px;
    }
    
    .help-card h4 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #1d2327;
    }
    
    .help-card p {
        margin-bottom: 15px;
        color: #646970;
    }
    
    .help-link {
        display: inline-block;
        color: #2271b1;
        text-decoration: none;
        font-weight: 500;
        border-bottom: 1px solid transparent;
    }
    
    .help-link:hover {
        border-bottom-color: #2271b1;
    }
    
    /* Responsividad */
    @media (max-width: 782px) {
        .dashboard-stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>