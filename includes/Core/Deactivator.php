<?php
/**
 * Plugin Deactivator
 *
 * @package AI360RealEstate
 * @subpackage Core
 * @since 0.1.0
 */

namespace AI360RealEstate\Core;

// Seguridad: Bloquear acceso directo
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase para manejar la desactivación del plugin
 *
 * Esta clase se ejecuta cuando el plugin es desactivado.
 *
 * @since 0.1.0
 */
class Deactivator {

	/**
	 * Ejecutar acciones de desactivación del plugin
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function deactivate() {
		// Clear scheduled log cleanup
		$timestamp = wp_next_scheduled( 'ai360re_cleanup_logs' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'ai360re_cleanup_logs' );
		}

		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
