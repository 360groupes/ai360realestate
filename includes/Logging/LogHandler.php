<?php
/**
 * Log Handler Class
 *
 * Handles log storage and management.
 *
 * @package AI360RealEstate
 * @subpackage Logging
 * @since 0.1.0
 */

namespace AI360RealEstate\Logging;

use AI360RealEstate\Core\Database;

// Seguridad: Bloquear acceso directo
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log handler class.
 *
 * Manages log storage and retrieval.
 *
 * @since 0.1.0
 */
class LogHandler {

	/**
	 * Storage destinations.
	 */
	const DESTINATION_DATABASE = 'database';
	const DESTINATION_FILE     = 'file';
	const DESTINATION_BOTH     = 'both';

	/**
	 * Storage destination.
	 *
	 * @var string
	 */
	private $destination;

	/**
	 * Log file path.
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param string $destination Optional. Storage destination. Default 'database'.
	 */
	public function __construct( string $destination = self::DESTINATION_DATABASE ) {
		$this->destination = $destination;
		$this->log_file    = $this->get_log_file_path();
	}

	/**
	 * Handle a log entry.
	 *
	 * @since 0.1.0
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return bool True on success, false on failure.
	 */
	public function handle( string $level, string $message, array $context = array() ): bool {
		$success = true;

		switch ( $this->destination ) {
			case self::DESTINATION_DATABASE:
				$success = $this->write_to_database( $level, $message, $context );
				break;

			case self::DESTINATION_FILE:
				$success = $this->write_to_file( $level, $message, $context );
				break;

			case self::DESTINATION_BOTH:
				$db_success   = $this->write_to_database( $level, $message, $context );
				$file_success = $this->write_to_file( $level, $message, $context );
				$success      = $db_success && $file_success;
				break;
		}

		return $success;
	}

	/**
	 * Write log entry to database.
	 *
	 * Note: This writes to WordPress error log, not a custom table,
	 * to keep the logging system simple and avoid creating custom log tables.
	 * The audit_log table is used specifically for audit trail of user actions.
	 *
	 * @since 0.1.0
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return bool True on success, false on failure.
	 */
	private function write_to_database( string $level, string $message, array $context = array() ): bool {
		// For system logs, we use error_log instead of a custom table
		// This keeps the system simple and leverages WordPress's built-in logging
		$formatted_message = $this->format_message( $level, $message, $context );
		error_log( '[AI360RE] ' . $formatted_message );

		return true;
	}

	/**
	 * Write log entry to file.
	 *
	 * @since 0.1.0
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return bool True on success, false on failure.
	 */
	private function write_to_file( string $level, string $message, array $context = array() ): bool {
		$formatted_message = $this->format_message( $level, $message, $context );

		// Add newline
		$formatted_message .= PHP_EOL;

		// Ensure log directory exists
		$log_dir = dirname( $this->log_file );
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}

		// Write to file
		$result = file_put_contents( $this->log_file, $formatted_message, FILE_APPEND | LOCK_EX );

		return false !== $result;
	}

	/**
	 * Format log message.
	 *
	 * @since 0.1.0
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return string Formatted message.
	 */
	private function format_message( string $level, string $message, array $context = array() ): string {
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$level     = strtoupper( $level );

		$formatted = "[{$timestamp}] [{$level}] {$message}";

		// Add context if present
		if ( ! empty( $context ) ) {
			// Remove sensitive data
			$context = $this->sanitize_context( $context );
			$formatted .= ' ' . wp_json_encode( $context );
		}

		return $formatted;
	}

	/**
	 * Sanitize context data to remove sensitive information.
	 *
	 * @since 0.1.0
	 * @param array $context Context data.
	 * @return array Sanitized context.
	 */
	private function sanitize_context( array $context ): array {
		$sensitive_keys = array(
			'password',
			'api_key',
			'secret',
			'token',
			'auth',
			'credentials',
		);

		foreach ( $context as $key => $value ) {
			$key_lower = strtolower( $key );

			foreach ( $sensitive_keys as $sensitive ) {
				if ( false !== strpos( $key_lower, $sensitive ) ) {
					$context[ $key ] = '***REDACTED***';
					break;
				}
			}

			// Recursively sanitize nested arrays
			if ( is_array( $value ) ) {
				$context[ $key ] = $this->sanitize_context( $value );
			}
		}

		return $context;
	}

	/**
	 * Get log file path.
	 *
	 * @since 0.1.0
	 * @return string Log file path.
	 */
	private function get_log_file_path(): string {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/ai360realestate-logs';

		return $log_dir . '/ai360re-' . gmdate( 'Y-m-d' ) . '.log';
	}

	/**
	 * Get all log files.
	 *
	 * @since 0.1.0
	 * @return array Array of log file paths.
	 */
	public function get_log_files(): array {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/ai360realestate-logs';

		if ( ! is_dir( $log_dir ) ) {
			return array();
		}

		$files = glob( $log_dir . '/ai360re-*.log' );

		return is_array( $files ) ? $files : array();
	}

	/**
	 * Clean up old log entries.
	 *
	 * @since 0.1.0
	 * @param int $days Number of days to retain logs.
	 * @return int Number of files deleted.
	 */
	public function cleanup( int $days = 30 ): int {
		$deleted   = 0;
		$log_files = $this->get_log_files();
		$threshold = time() - ( $days * DAY_IN_SECONDS );

		foreach ( $log_files as $file ) {
			$file_time = filemtime( $file );

			if ( $file_time && $file_time < $threshold ) {
				if ( unlink( $file ) ) {
					++$deleted;
				}
			}
		}

		return $deleted;
	}

	/**
	 * Read log entries from file.
	 *
	 * @since 0.1.0
	 * @param string $file     Log file path.
	 * @param int    $limit    Optional. Maximum number of entries to return. Default 100.
	 * @param string $level    Optional. Filter by log level.
	 * @return array Array of log entries.
	 */
	public function read_log( string $file, int $limit = 100, string $level = '' ): array {
		if ( ! file_exists( $file ) ) {
			return array();
		}

		$lines   = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		$entries = array();

		if ( ! is_array( $lines ) ) {
			return array();
		}

		// Reverse to get newest first
		$lines = array_reverse( $lines );

		foreach ( $lines as $line ) {
			// Parse log entry
			if ( preg_match( '/^\[([\d\-\s:]+)\]\s+\[(\w+)\]\s+(.+)$/', $line, $matches ) ) {
				$entry = array(
					'timestamp' => $matches[1],
					'level'     => strtolower( $matches[2] ),
					'message'   => $matches[3],
				);

				// Filter by level if specified
				if ( ! empty( $level ) && $entry['level'] !== strtolower( $level ) ) {
					continue;
				}

				$entries[] = $entry;

				// Limit reached
				if ( count( $entries ) >= $limit ) {
					break;
				}
			}
		}

		return $entries;
	}
}
