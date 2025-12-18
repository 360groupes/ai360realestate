<?php
/**
 * Plugin Name:       AI360 Real Estate
 * Plugin URI:        https://360group.es/ai360realestate
 * Description:       Plugin WordPress para Gestión Inteligente de Propiedades Inmobiliarias con IA, sincronización bidireccional y portal de agencia.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            360group
 * Author URI:        https://360group.es
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai360realestate
 * Domain Path:       /languages
 *
 * @package AI360RealEstate
 */

// Seguridad: Bloquear acceso directo
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constantes del plugin
define( 'AI360RE_VERSION', '0.1.0' );
define( 'AI360RE_PLUGIN_FILE', __FILE__ );
define( 'AI360RE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI360RE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AI360RE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader de Composer
if ( file_exists( AI360RE_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once AI360RE_PLUGIN_DIR . 'vendor/autoload.php';
}

// Hooks de activación/desactivación
register_activation_hook( __FILE__, array( 'AI360RealEstate\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AI360RealEstate\\Core\\Deactivator', 'deactivate' ) );

// Inicializar el plugin
add_action(
	'plugins_loaded',
	function() {
		\AI360RealEstate\Core\Plugin::get_instance();
	},
	10
);
