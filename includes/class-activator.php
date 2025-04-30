<?php
/**
 * Código que se ejecuta durante la activación del plugin
 */
class EnglishLine_Test_Activator {

    /**
     * Método que se ejecuta al activar el plugin
     */
    public static function activate() {
        // Crear el rol personalizado para el plugin
        self::create_custom_role();
        
        // Crear tablas necesarias en la base de datos
        self::create_database_tables();
    }
    
    /**
     * Crea un rol personalizado para gestionar el plugin
     */
    private static function create_custom_role() {
        add_role(
            'englishline_manager',
            __('EnglishLine Manager', 'englishline-test'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            )
        );
    }
    
    /**
     * Crea las tablas necesarias en la base de datos
     */
    private static function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla para almacenar los formularios
         $table_name_forms = $wpdb->prefix . 'englishline_forms';
        $sql_forms = "CREATE TABLE $table_name_forms (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text NULL,
            form_data longtext NOT NULL,
            shortcode varchar(100) NOT NULL,
            status varchar(50) DEFAULT 'published' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Tabla para almacenar resultados de los formularios
        $table_name_results = $wpdb->prefix . 'englishline_results';
        $sql_results = "CREATE TABLE $table_name_results (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            user_id bigint(20) NULL,
            user_email varchar(100) NULL,
            form_data longtext NOT NULL,
            score float NULL,
            level varchar(5) NULL,
            feedback text NULL,
            individual_scores longtext NULL,
            status varchar(50) DEFAULT 'pending' NOT NULL,
            reviewer_id bigint(20) NULL,
            reviewed_at datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY form_id (form_id)
        ) $charset_collate;";
        
        // Tabla para almacenar plantillas de correo electrónico
        $table_name_emails = $wpdb->prefix . 'englishline_email_templates';
        $sql_emails = "CREATE TABLE $table_name_emails (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            content longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_forms );
        dbDelta( $sql_results );
        dbDelta( $sql_emails );
    }
}