<?php
/**
 * Logger Class
 *
 * Centralized logging system for the plugin.
 *
 * @package AI360RealEstate
 * @subpackage Logging
 * @since 0.1.0
 */

namespace AI360RealEstate\Logging;

// Seguridad: Bloquear acceso directo
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger class.
 *
 * Provides centralized logging functionality with multiple log levels.
 *
 * @since 0.1.0
 */
class Logger {

	/**
	 * Log levels
	 */
	const LEVEL_DEBUG   = 'debug';
	const LEVEL_INFO    = 'info';
	const LEVEL_WARNING = 'warning';
	const LEVEL_ERROR   = 'error';

	/**
	 * Singleton instance
	 *
	 * @var Logger|null
	 */
	private static $instance = null;

	/**
	 * Log handler
	 *
	 * @var LogHandler|null
	 */
	private $handler = null;

	/**
	 * Get singleton instance
	 *
	 * @since 0.1.0
	 * @return Logger
	 */
	public static function get_instance(): Logger {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		try {
			$this->handler = new LogHandler();
		} catch ( \Exception $e ) {
			// Fallback: log to WordPress debug.log if handler fails
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[AI360RE] Failed to initialize LogHandler: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Log a debug message
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		$this->log( self::LEVEL_DEBUG, $message, $context );
	}

	/**
	 * Log an info message
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		$this->log( self::LEVEL_INFO, $message, $context );
	}

	/**
	 * Log a warning message
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->log( self::LEVEL_WARNING, $message, $context );
	}

	/**
	 * Log an error message
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$this->log( self::LEVEL_ERROR, $message, $context );
	}

	/**
	 * Log a message with specified level
	 *
	 * @since 0.1.0
	 * @param string $level Log level.
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function log( string $level, string $message, array $context = array() ): void {
		// Check if logging is enabled
		if ( ! $this->is_logging_enabled() ) {
			return;
		}

		// Check if this log level should be logged
		if ( ! $this->should_log_level( $level ) ) {
			return;
		}

		// Prepare log entry
		$entry = array(
			'timestamp' => current_time( 'mysql' ),
			'level'     => $level,
			'message'   => $message,
			'context'   => $context,
		);

		// Write log if handler is available
		if ( $this->handler ) {
			$this->handler->write( $entry );
		}

		// Also log to WordPress debug.log if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->write_to_debug_log( $level, $message, $context );
		}
	}

	/**
	 * Check if logging is enabled
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	private function is_logging_enabled(): bool {
		return (bool) get_option( 'ai360re_logging_enabled', true );
	}

	/**
	 * Check if a log level should be logged based on configuration
	 *
	 * @since 0.1.0
	 * @param string $level Log level to check.
	 * @return bool
	 */
	private function should_log_level( string $level ): bool {
		$min_level = get_option( 'ai360re_log_level', self::LEVEL_INFO );

		$levels = array(
			self::LEVEL_DEBUG   => 0,
			self::LEVEL_INFO    => 1,
			self::LEVEL_WARNING => 2,
			self::LEVEL_ERROR   => 3,
		);

		$current_level_priority = $levels[ $level ] ?? 1;
		$min_level_priority     = $levels[ $min_level ] ?? 1;

		return $current_level_priority >= $min_level_priority;
	}

	/**
	 * Write to WordPress debug.log
	 *
	 * @since 0.1.0
	 * @param string $level Log level.
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private function write_to_debug_log( string $level, string $message, array $context = array() ): void {
		$formatted_message = sprintf(
			'[AI360RE] [%s] %s',
			strtoupper( $level ),
			$message
		);

		if ( ! empty( $context ) ) {
			$formatted_message .= ' ' . wp_json_encode( $context );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $formatted_message );
	}

	/**
	 * Clean up old logs
	 *
	 * Deletes logs older than the retention period.
	 *
	 * @since 0.1.0
	 * @return int Number of logs deleted.
	 */
	public function cleanup_old_logs(): int {
		if ( ! $this->handler ) {
			return 0;
		}

		$retention_days = (int) get_option( 'ai360re_log_retention_days', 30 );

		return $this->handler->cleanup( $retention_days );
	}

	/**
	 * Get recent logs
	 *
	 * @since 0.1.0
	 * @param int    $limit Number of logs to retrieve.
	 * @param string $level Filter by log level (optional).
	 * @return array Array of log entries.
	 */
	public function get_recent_logs( int $limit = 100, string $level = '' ): array {
		if ( ! $this->handler ) {
			return array();
		}

		return $this->handler->get_recent( $limit, $level );
	}

	/**
	 * Prevent cloning
	 *
	 * @since 0.1.0
	 * @throws \Exception If clone is attempted.
	 */
	private function __clone() {
		throw new \Exception( 'Cannot clone singleton' );
	}

	/**
	 * Prevent unserialization
	 *
	 * @since 0.1.0
	 * @throws \Exception If unserialization is attempted.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
