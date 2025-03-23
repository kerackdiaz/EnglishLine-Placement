<?php
/**
 * Define la funcionalidad de internacionalización
 */
class EnglishLine_Test_i18n {

    /**
     * Carga el dominio de texto del plugin para traducción.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'englishline-test',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}