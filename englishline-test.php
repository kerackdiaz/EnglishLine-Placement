<?php
/**
 * Plugin Name: EnglishLine Placement
 * Plugin URI: https://github.com/kerackdiaz/EnglishLine-Placement
 * Description: Un plugin para crear tests de inglés con formularios personalizables paso a paso.
 * Version: 2.2.7
 * Author: KerackDiaz
 * Author URI: https://github.com/kerackdiaz/
 * Text Domain: englishline-test
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Si este archivo es llamado directamente, aborta.
if (!defined('WPINC')) {
    die;
}

// Definir constantes
define('ENGLISHLINETEST_VERSION', '2.2.7');
define('ENGLISHLINETEST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ENGLISHLINETEST_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Código que se ejecuta durante la activación del plugin.
 */
function activate_englishline_test() {
    require_once ENGLISHLINETEST_PLUGIN_DIR . 'includes/class-activator.php';
    EnglishLine_Test_Activator::activate();
}

/**
 * Código que se ejecuta durante la desactivación del plugin.
 */
function deactivate_englishline_test() {
    require_once ENGLISHLINETEST_PLUGIN_DIR . 'includes/class-deactivator.php';
    EnglishLine_Test_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_englishline_test');
register_deactivation_hook(__FILE__, 'deactivate_englishline_test');

/**
 * Incluye la clase principal del plugin.
 */
require_once ENGLISHLINETEST_PLUGIN_DIR . 'includes/class-englishline-test.php';

/**
 * Comienza la ejecución del plugin.
 */
function run_englishline_test() {
    $plugin = new EnglishLine_Test();
    $plugin->run();
}

define( 'WP_GITHUB_FORCE_UPDATE', true );

add_action('plugins_loaded', 'run_englishline_test');