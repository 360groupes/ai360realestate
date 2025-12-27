<?php
/**
 * Plugin Main Class
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
 * Clase principal del plugin usando patrón Singleton
 *
 * Esta clase maneja la inicialización y ejecución del plugin.
 *
 * @since 0.1.0
 */
class Plugin {

	/**
	 * Instancia única del plugin (Singleton)
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Obtener la instancia única del plugin
	 *
	 * @since 0.1.0
	 * @return Plugin Instancia del plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor privado (Singleton)
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		$this->check_requirements();
		$this->init_hooks();
	}

	/**
	 * Verificar requisitos mínimos del plugin
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function check_requirements() {
		global $wp_version;

		// Verificar versión de WordPress
		if ( version_compare( $wp_version, '6.0', '<' ) ) {
			add_action(
				'admin_notices',
				function() {
					?>
					<div class="notice notice-error">
						<p>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: Versión mínima requerida de WordPress */
									__( 'AI360 Real Estate requiere WordPress %s o superior.', 'ai360realestate' ),
									'6.0'
								)
							);
							?>
						</p>
					</div>
					<?php
				}
			);
			return;
		}

		// Verificar versión de PHP
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			add_action(
				'admin_notices',
				function() {
					?>
					<div class="notice notice-error">
						<p>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: Versión mínima requerida de PHP */
									__( 'AI360 Real Estate requiere PHP %s o superior.', 'ai360realestate' ),
									'7.4'
								)
							);
							?>
						</p>
					</div>
					<?php
				}
			);
			return;
		}
	}

	/**
	 * Inicializar hooks del plugin
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'ai360re_cleanup_logs', array( $this, 'cleanup_logs' ) );
	}

	/**
	 * Cargar archivos de traducción
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'ai360realestate',
			false,
			dirname( AI360RE_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Clean up old logs via scheduled cron job.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function cleanup_logs() {
		// Get retention days from options (default: 30 for file logs, 90 for audit logs)
		$file_log_retention  = (int) get_option( 'ai360re_file_log_retention', 30 );
		$audit_log_retention = (int) get_option( 'ai360re_audit_log_retention', 90 );

		// Clean up file logs
		\AI360RealEstate\Logging\Logger::cleanup( $file_log_retention );

		// Clean up audit logs
		\AI360RealEstate\Logging\AuditLogger::cleanup( $audit_log_retention );
	}

	/**
	 * Obtener la versión del plugin
	 *
	 * @since 0.1.0
	 * @return string Versión del plugin
	 */
	public function get_version() {
		return AI360RE_VERSION;
	}

	/**
	 * Prevenir clonación del objeto (Singleton)
	 *
	 * @since 0.1.0
	 * @throws \Exception Si se intenta clonar la instancia.
	 */
	private function __clone() {
		throw new \Exception( 'Cannot clone singleton' );
	}

	/**
	 * Prevenir deserialización del objeto (Singleton)
	 *
	 * @since 0.1.0
	 * @throws \Exception Si se intenta deserializar la instancia.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
