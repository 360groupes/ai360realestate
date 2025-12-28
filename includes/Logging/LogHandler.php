<?php
/**
 * Log Handler Class
 *
 * Handles writing and reading log files.
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
 * Log Handler class.
 *
 * Manages log file operations.
 *
 * @since 0.1.0
 */
class LogHandler {

	/**
	 * Log directory path
	 *
	 * @var string
	 */
	private $log_dir;

	/**
	 * Log file name
	 *
	 * @var string
	 */
	private $log_file = 'ai360realestate.log';

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$upload_dir    = wp_upload_dir();
		$this->log_dir = trailingslashit( $upload_dir['basedir'] ) . 'ai360realestate-logs';

		// Create log directory if it doesn't exist
		$this->ensure_log_directory();
	}

	/**
	 * Write a log entry
	 *
	 * @since 0.1.0
	 * @param array $entry Log entry data.
	 * @return bool True on success, false on failure.
	 */
	public function write( array $entry ): bool {
		$log_file = $this->get_log_file_path();

		// Format log entry
		$formatted = $this->format_entry( $entry );

		// Write to file
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $log_file, 'a' );
		if ( ! $handle ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		$result = fwrite( $handle, $formatted . PHP_EOL );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );

		return false !== $result;
	}

	/**
	 * Get recent log entries
	 *
	 * @since 0.1.0
	 * @param int    $limit Number of entries to retrieve.
	 * @param string $level Filter by log level (optional).
	 * @return array Array of log entries.
	 */
	public function get_recent( int $limit = 100, string $level = '' ): array {
		$log_file = $this->get_log_file_path();

		if ( ! file_exists( $log_file ) ) {
			return array();
		}

		// Read file lines
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $log_file );
		if ( false === $content ) {
			return array();
		}

		$lines = explode( PHP_EOL, $content );
		$lines = array_filter( $lines ); // Remove empty lines
		$lines = array_reverse( $lines ); // Most recent first

		$entries = array();
		foreach ( $lines as $line ) {
			if ( count( $entries ) >= $limit ) {
				break;
			}

			$entry = $this->parse_entry( $line );
			if ( ! $entry ) {
				continue;
			}

			// Filter by level if specified
			if ( $level && $entry['level'] !== $level ) {
				continue;
			}

			$entries[] = $entry;
		}

		return $entries;
	}

	/**
	 * Clean up old logs
	 *
	 * Note: This method loads the entire log file into memory.
	 * For very large log files (>100MB), consider implementing
	 * rotation instead of this cleanup approach.
	 *
	 * @since 0.1.0
	 * @param int $retention_days Number of days to retain logs.
	 * @return int Number of entries deleted.
	 */
	public function cleanup( int $retention_days = 30 ): int {
		$log_file = $this->get_log_file_path();

		if ( ! file_exists( $log_file ) ) {
			return 0;
		}

		// Read all entries
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $log_file );
		if ( false === $content ) {
			return 0;
		}

		$lines        = explode( PHP_EOL, $content );
		$cutoff_date  = strtotime( "-{$retention_days} days" );
		$kept_lines   = array();
		$deleted_count = 0;

		foreach ( $lines as $line ) {
			if ( empty( $line ) ) {
				continue;
			}

			$entry = $this->parse_entry( $line );
			if ( ! $entry ) {
				$kept_lines[] = $line;
				continue;
			}

			// Check if entry is older than retention period
			$entry_time = strtotime( $entry['timestamp'] );
			if ( $entry_time >= $cutoff_date ) {
				$kept_lines[] = $line;
			} else {
				$deleted_count++;
			}
		}

		// Write back kept entries
		if ( $deleted_count > 0 ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $log_file, implode( PHP_EOL, $kept_lines ) . PHP_EOL );
		}

		return $deleted_count;
	}

	/**
	 * Format a log entry for writing
	 *
	 * @since 0.1.0
	 * @param array $entry Log entry data.
	 * @return string Formatted log entry.
	 */
	private function format_entry( array $entry ): string {
		$parts = array(
			'timestamp' => $entry['timestamp'] ?? '',
			'level'     => strtoupper( $entry['level'] ?? 'INFO' ),
			'message'   => $entry['message'] ?? '',
		);

		$formatted = sprintf(
			'[%s] [%s] %s',
			$parts['timestamp'],
			$parts['level'],
			$parts['message']
		);

		// Add context if present
		if ( ! empty( $entry['context'] ) ) {
			$formatted .= ' ' . wp_json_encode( $entry['context'] );
		}

		return $formatted;
	}

	/**
	 * Parse a log entry line
	 *
	 * @since 0.1.0
	 * @param string $line Log line.
	 * @return array|null Parsed entry or null if parsing fails.
	 */
	private function parse_entry( string $line ): ?array {
		// Pattern: [timestamp] [LEVEL] message {context}
		$pattern = '/^\[([^\]]+)\]\s+\[([^\]]+)\]\s+(.+)$/';
		if ( ! preg_match( $pattern, $line, $matches ) ) {
			return null;
		}

		$entry = array(
			'timestamp' => $matches[1],
			'level'     => strtolower( $matches[2] ),
			'message'   => '',
			'context'   => array(),
		);

		// Separate message and context
		$content = $matches[3];
		$json_start = strrpos( $content, '{' );

		if ( false !== $json_start ) {
			$entry['message'] = trim( substr( $content, 0, $json_start ) );
			$json_str         = substr( $content, $json_start );
			$context          = json_decode( $json_str, true );
			if ( is_array( $context ) ) {
				$entry['context'] = $context;
			}
		} else {
			$entry['message'] = $content;
		}

		return $entry;
	}

	/**
	 * Ensure log directory exists
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function ensure_log_directory(): void {
		if ( ! file_exists( $this->log_dir ) ) {
			wp_mkdir_p( $this->log_dir );

			// Create .htaccess to protect log files (Apache 2.2 and 2.4 compatible)
			$htaccess_file = trailingslashit( $this->log_dir ) . '.htaccess';
			$htaccess_content = "# Protect AI360 Real Estate log files\n";
			$htaccess_content .= "# Apache 2.2\n";
			$htaccess_content .= "<IfModule !mod_authz_core.c>\n";
			$htaccess_content .= "Order deny,allow\n";
			$htaccess_content .= "Deny from all\n";
			$htaccess_content .= "</IfModule>\n\n";
			$htaccess_content .= "# Apache 2.4\n";
			$htaccess_content .= "<IfModule mod_authz_core.c>\n";
			$htaccess_content .= "Require all denied\n";
			$htaccess_content .= "</IfModule>\n";

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $htaccess_file, $htaccess_content );

			// Create index.php to prevent directory listing
			$index_file = trailingslashit( $this->log_dir ) . 'index.php';
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index_file, '<?php // Silence is golden' );
		}
	}

	/**
	 * Get log file path
	 *
	 * @since 0.1.0
	 * @return string Log file path.
	 */
	private function get_log_file_path(): string {
		return trailingslashit( $this->log_dir ) . $this->log_file;
	}

	/**
	 * Get log directory path
	 *
	 * @since 0.1.0
	 * @return string Log directory path.
	 */
	public function get_log_directory(): string {
		return $this->log_dir;
	}

	/**
	 * Clear all logs
	 *
	 * @since 0.1.0
	 * @return bool True on success, false on failure.
	 */
	public function clear_logs(): bool {
		$log_file = $this->get_log_file_path();

		if ( ! file_exists( $log_file ) ) {
			return true;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		return unlink( $log_file );
	}
}
