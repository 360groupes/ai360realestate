<?php
/**
 * Audit Logger Class
 *
 * Handles audit trail logging for all system actions.
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
 * Audit Logger class.
 *
 * Logs all user actions for audit trail and compliance.
 *
 * @since 0.1.0
 */
class AuditLogger {

	/**
	 * Singleton instance
	 *
	 * @var AuditLogger|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @since 0.1.0
	 * @return AuditLogger
	 */
	public static function get_instance(): AuditLogger {
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
		// Private constructor for singleton
	}

	/**
	 * Log an audit entry
	 *
	 * @since 0.1.0
	 * @param string $entity_type Type of entity (e.g., 'property', 'project', 'connector').
	 * @param int    $entity_id ID of the entity.
	 * @param string $action Action performed (e.g., 'create', 'update', 'delete').
	 * @param array  $old_value Old value before change (optional).
	 * @param array  $new_value New value after change (optional).
	 * @param int    $project_id Project ID (optional).
	 * @return bool True on success, false on failure.
	 */
	public function log(
		string $entity_type,
		int $entity_id,
		string $action,
		array $old_value = array(),
		array $new_value = array(),
		int $project_id = 0
	): bool {
		global $wpdb;

		// Get current user
		$user_id = get_current_user_id();

		// Get IP address
		$ip_address = $this->get_client_ip();

		// Get user agent
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		// Prepare data
		$data = array(
			'user_id'     => $user_id ? $user_id : null,
			'project_id'  => $project_id ? $project_id : null,
			'entity_type' => sanitize_text_field( $entity_type ),
			'entity_id'   => $entity_id,
			'action'      => sanitize_text_field( $action ),
			'old_value'   => ! empty( $old_value ) ? wp_json_encode( $old_value ) : null,
			'new_value'   => ! empty( $new_value ) ? wp_json_encode( $new_value ) : null,
			'ip_address'  => $ip_address,
			'user_agent'  => $user_agent,
			'created_at'  => current_time( 'mysql' ),
		);

		// Insert into audit_log table
		$table_name = Database::get_table_name( 'audit_log' );
		$result     = $wpdb->insert( $table_name, $data );

		return false !== $result;
	}

	/**
	 * Log entity creation
	 *
	 * @since 0.1.0
	 * @param string $entity_type Type of entity.
	 * @param int    $entity_id ID of the entity.
	 * @param array  $data Entity data.
	 * @param int    $project_id Project ID (optional).
	 * @return bool True on success, false on failure.
	 */
	public function log_create( string $entity_type, int $entity_id, array $data, int $project_id = 0 ): bool {
		return $this->log( $entity_type, $entity_id, 'create', array(), $data, $project_id );
	}

	/**
	 * Log entity update
	 *
	 * @since 0.1.0
	 * @param string $entity_type Type of entity.
	 * @param int    $entity_id ID of the entity.
	 * @param array  $old_data Old entity data.
	 * @param array  $new_data New entity data.
	 * @param int    $project_id Project ID (optional).
	 * @return bool True on success, false on failure.
	 */
	public function log_update( string $entity_type, int $entity_id, array $old_data, array $new_data, int $project_id = 0 ): bool {
		return $this->log( $entity_type, $entity_id, 'update', $old_data, $new_data, $project_id );
	}

	/**
	 * Log entity deletion
	 *
	 * @since 0.1.0
	 * @param string $entity_type Type of entity.
	 * @param int    $entity_id ID of the entity.
	 * @param array  $data Entity data before deletion.
	 * @param int    $project_id Project ID (optional).
	 * @return bool True on success, false on failure.
	 */
	public function log_delete( string $entity_type, int $entity_id, array $data, int $project_id = 0 ): bool {
		return $this->log( $entity_type, $entity_id, 'delete', $data, array(), $project_id );
	}

	/**
	 * Get audit logs for an entity
	 *
	 * @since 0.1.0
	 * @param string $entity_type Type of entity.
	 * @param int    $entity_id ID of the entity.
	 * @param int    $limit Maximum number of logs to retrieve.
	 * @return array Array of audit log entries.
	 */
	public function get_entity_logs( string $entity_type, int $entity_id, int $limit = 50 ): array {
		global $wpdb;

		$table_name = Database::get_table_name( 'audit_log' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} 
				WHERE entity_type = %s AND entity_id = %d 
				ORDER BY created_at DESC 
				LIMIT %d",
				$entity_type,
				$entity_id,
				$limit
			),
			ARRAY_A
		);

		return $results ? $results : array();
	}

	/**
	 * Get audit logs for a user
	 *
	 * @since 0.1.0
	 * @param int $user_id User ID.
	 * @param int $limit Maximum number of logs to retrieve.
	 * @return array Array of audit log entries.
	 */
	public function get_user_logs( int $user_id, int $limit = 50 ): array {
		global $wpdb;

		$table_name = Database::get_table_name( 'audit_log' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} 
				WHERE user_id = %d 
				ORDER BY created_at DESC 
				LIMIT %d",
				$user_id,
				$limit
			),
			ARRAY_A
		);

		return $results ? $results : array();
	}

	/**
	 * Get audit logs for a project
	 *
	 * @since 0.1.0
	 * @param int $project_id Project ID.
	 * @param int $limit Maximum number of logs to retrieve.
	 * @return array Array of audit log entries.
	 */
	public function get_project_logs( int $project_id, int $limit = 50 ): array {
		global $wpdb;

		$table_name = Database::get_table_name( 'audit_log' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} 
				WHERE project_id = %d 
				ORDER BY created_at DESC 
				LIMIT %d",
				$project_id,
				$limit
			),
			ARRAY_A
		);

		return $results ? $results : array();
	}

	/**
	 * Clean up old audit logs
	 *
	 * @since 0.1.0
	 * @param int $retention_days Number of days to retain logs.
	 * @return int Number of logs deleted.
	 */
	public function cleanup_old_logs( int $retention_days = 90 ): int {
		global $wpdb;

		$table_name = Database::get_table_name( 'audit_log' );
		$date       = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE created_at < %s",
				$date
			)
		);

		return $deleted ? (int) $deleted : 0;
	}

	/**
	 * Get client IP address
	 *
	 * Note: Proxy headers can be spoofed. In production, configure
	 * trusted proxies via the ai360re_trusted_proxies filter.
	 *
	 * @since 0.1.0
	 * @return string Client IP address.
	 */
	private function get_client_ip(): string {
		// Get list of trusted proxy IPs (empty by default for security)
		$trusted_proxies = apply_filters( 'ai360re_trusted_proxies', array() );

		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		// If no trusted proxies configured, only use REMOTE_ADDR for security
		if ( empty( $trusted_proxies ) ) {
			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
			return '0.0.0.0';
		}

		// If trusted proxies are configured, check proxy headers
		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs (from proxies)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				// Validate IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
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
