<?php
/**
 * Plugin Activator
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
 * Clase para manejar la activación del plugin
 *
 * Esta clase se ejecuta cuando el plugin es activado.
 *
 * @since 0.1.0
 */
class Activator {

	/**
	 * Ejecutar acciones de activación del plugin
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function activate() {
		// Verificar requisitos mínimos
		self::check_requirements();

		// Guardar versión del plugin
		update_option( 'ai360re_version', AI360RE_VERSION );

		// Guardar timestamp de instalación
		if ( ! get_option( 'ai360re_installed_at' ) ) {
			update_option( 'ai360re_installed_at', time() );
		}

		// Crear/actualizar tablas de base de datos
		$migration_result = Migrations::run();

		// Log error si falla la migración
		if ( ! $migration_result ) {
			error_log( 'AI360 Real Estate: Error creating database tables during activation' );
		}

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Verificar requisitos mínimos del sistema
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private static function check_requirements() {
		global $wp_version;

		$errors = array();

		// Verificar versión de WordPress
		if ( version_compare( $wp_version, '6.0', '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: Versión mínima requerida de WordPress */
				__( 'AI360 Real Estate requiere WordPress %s o superior. Versión actual: %s', 'ai360realestate' ),
				'6.0',
				$wp_version
			);
		}

		// Verificar versión de PHP
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: Versión mínima requerida de PHP */
				__( 'AI360 Real Estate requiere PHP %s o superior. Versión actual: %s', 'ai360realestate' ),
				'7.4',
				PHP_VERSION
			);
		}

		// Si hay errores, desactivar y mostrar mensaje
		if ( ! empty( $errors ) ) {
			deactivate_plugins( AI360RE_PLUGIN_BASENAME );
			wp_die(
				'<h1>' . esc_html__( 'Error de Activación', 'ai360realestate' ) . '</h1>' .
				'<p>' . esc_html__( 'No se puede activar AI360 Real Estate debido a los siguientes errores:', 'ai360realestate' ) . '</p>' .
				'<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $errors ) ) . '</li></ul>',
				esc_html__( 'Error de Activación del Plugin', 'ai360realestate' ),
				array( 'back_link' => true )
			);
		}
	}
}
