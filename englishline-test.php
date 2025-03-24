<?php
/**
 * Plugin Name: EnglishLine Placement
 * Plugin URI: https://github.com/kerackdiaz/EnglishLine-Placement
 * Description: Un plugin para crear tests de inglés con formularios personalizables paso a paso.
 * Version: 1.5.4
 * Author: KerackDiaz
 * Author URI: https://github.com/kerackdiaz/
 * Text Domain: englishline-test
 * Domain Path: /languages
 */

// Si este archivo es llamado directamente, aborta.
if (!defined('WPINC')) {
    die;
}

// Definir constantes
define('ENGLISHLINE_TEST_VERSION', '1.5.4');
define('ENGLISHLINE_TEST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ENGLISHLINE_TEST_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Código que se ejecuta durante la activación del plugin.
 */
function activate_englishline_test() {
    require_once ENGLISHLINE_TEST_PLUGIN_DIR . 'includes/class-activator.php';
    EnglishLine_Test_Activator::activate();
}

/**
 * Código que se ejecuta durante la desactivación del plugin.
 */
function deactivate_englishline_test() {
    require_once ENGLISHLINE_TEST_PLUGIN_DIR . 'includes/class-deactivator.php';
    EnglishLine_Test_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_englishline_test');
register_deactivation_hook(__FILE__, 'deactivate_englishline_test');

/**
 * Incluye la clase principal del plugin.
 */
require_once ENGLISHLINE_TEST_PLUGIN_DIR . 'includes/class-englishline-test.php';

/**
 * Comienza la ejecución del plugin.
 */
function run_englishline_test() {
    $plugin = new EnglishLine_Test();
    $plugin->run();
}

run_englishline_test();