<?php
/**
 * Código que se ejecuta durante la desactivación del plugin
 */
class EnglishLine_Test_Deactivator {

    /**
     * Método que se ejecuta al desactivar el plugin
     */
    public static function deactivate() {
        // Al desactivar solo limpiamos programaciones y cachés
        wp_clear_scheduled_hook('englishline_test_cron');
        
        // Limpieza de caché
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Guardar fecha de desactivación (útil para estadísticas)
        update_option('englishline_test_last_deactivated', current_time('mysql'));
    }
}