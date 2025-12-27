<?php
/**
 * Audit Logger Class
 *
 * Handles audit trail logging for user actions.
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
 * Audit logger class.
 *
 * Records user actions for audit trail.
 *
 * @since 0.1.0
 */
class AuditLogger {

	/**
	 * Common actions.
	 */
	const ACTION_CREATE = 'create';
	const ACTION_UPDATE = 'update';
	const ACTION_DELETE = 'delete';
	const ACTION_VIEW   = 'view';
	const ACTION_LOGIN  = 'login';
	const ACTION_LOGOUT = 'logout';
	const ACTION_SYNC   = 'sync';
	const ACTION_AI_OPT = 'ai_optimize';
	const ACTION_EXPORT = 'export';
	const ACTION_IMPORT = 'import';

	/**
	 * Entity types.
	 */
	const ENTITY_PROJECT   = 'project';
	const ENTITY_PROPERTY  = 'property';
	const ENTITY_CONNECTOR = 'connector';
	const ENTITY_USER      = 'user';
	const ENTITY_SETTINGS  = 'settings';

	/**
	 * Log an audit entry.
	 *
	 * @since 0.1.0
	 * @param array $args {
	 *     Audit log arguments.
	 *
	 *     @type int    $user_id     User ID performing the action.
	 *     @type int    $project_id  Optional. Related project ID.
	 *     @type string $entity_type Entity type (project, property, etc).
	 *     @type int    $entity_id   Optional. Entity ID.
	 *     @type string $action      Action performed (create, update, delete, etc).
	 *     @type mixed  $old_value   Optional. Old value before change.
	 *     @type mixed  $new_value   Optional. New value after change.
	 * }
	 * @return int|false Audit log ID on success, false on failure.
	 */
	public static function log( array $args ) {
		global $wpdb;

		// Default values
		$defaults = array(
			'user_id'     => get_current_user_id(),
			'project_id'  => null,
			'entity_type' => '',
			'entity_id'   => null,
			'action'      => '',
			'old_value'   => null,
			'new_value'   => null,
			'ip_address'  => self::get_client_ip(),
			'user_agent'  => self::get_user_agent(),
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate required fields
		if ( empty( $args['entity_type'] ) || empty( $args['action'] ) ) {
			return false;
		}

		// Prepare data for insertion
		$data = array(
			'user_id'     => $args['user_id'],
			'project_id'  => $args['project_id'],
			'entity_type' => sanitize_text_field( $args['entity_type'] ),
			'entity_id'   => $args['entity_id'],
			'action'      => sanitize_text_field( $args['action'] ),
			'old_value'   => self::serialize_value( $args['old_value'] ),
			'new_value'   => self::serialize_value( $args['new_value'] ),
			'ip_address'  => $args['ip_address'],
			'user_agent'  => $args['user_agent'],
			'created_at'  => current_time( 'mysql', true ),
		);

		$format = array(
			'%d', // user_id
			'%d', // project_id
			'%s', // entity_type
			'%d', // entity_id
			'%s', // action
			'%s', // old_value
			'%s', // new_value
			'%s', // ip_address
			'%s', // user_agent
			'%s', // created_at
		);

		$table_name = Database::get_table_name( 'audit_log' );
		$result     = $wpdb->insert( $table_name, $data, $format );

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Log a create action.
	 *
	 * @since 0.1.0
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @param mixed  $new_value   New value.
	 * @param int    $project_id  Optional. Project ID.
	 * @return int|false Audit log ID on success, false on failure.
	 */
	public static function log_create( string $entity_type, int $entity_id, $new_value, int $project_id = null ) {
		return self::log(
			array(
				'entity_type' => $entity_type,
				'entity_id'   => $entity_id,
				'action'      => self::ACTION_CREATE,
				'new_value'   => $new_value,
				'project_id'  => $project_id,
			)
		);
	}

	/**
	 * Log an update action.
	 *
	 * @since 0.1.0
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @param mixed  $old_value   Old value.
	 * @param mixed  $new_value   New value.
	 * @param int    $project_id  Optional. Project ID.
	 * @return int|false Audit log ID on success, false on failure.
	 */
	public static function log_update( string $entity_type, int $entity_id, $old_value, $new_value, int $project_id = null ) {
		return self::log(
			array(
				'entity_type' => $entity_type,
				'entity_id'   => $entity_id,
				'action'      => self::ACTION_UPDATE,
				'old_value'   => $old_value,
				'new_value'   => $new_value,
				'project_id'  => $project_id,
			)
		);
	}

	/**
	 * Log a delete action.
	 *
	 * @since 0.1.0
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @param mixed  $old_value   Old value.
	 * @param int    $project_id  Optional. Project ID.
	 * @return int|false Audit log ID on success, false on failure.
	 */
	public static function log_delete( string $entity_type, int $entity_id, $old_value, int $project_id = null ) {
		return self::log(
			array(
				'entity_type' => $entity_type,
				'entity_id'   => $entity_id,
				'action'      => self::ACTION_DELETE,
				'old_value'   => $old_value,
				'project_id'  => $project_id,
			)
		);
	}

	/**
	 * Get audit log entries.
	 *
	 * @since 0.1.0
	 * @param array $args {
	 *     Query arguments.
	 *
	 *     @type int    $user_id     Optional. Filter by user ID.
	 *     @type int    $project_id  Optional. Filter by project ID.
	 *     @type string $entity_type Optional. Filter by entity type.
	 *     @type int    $entity_id   Optional. Filter by entity ID.
	 *     @type string $action      Optional. Filter by action.
	 *     @type int    $limit       Optional. Number of entries to return. Default 100.
	 *     @type int    $offset      Optional. Offset for pagination. Default 0.
	 *     @type string $order       Optional. Order by (created_at). Default 'DESC'.
	 * }
	 * @return array Array of audit log entries.
	 */
	public static function get_entries( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'user_id'     => null,
			'project_id'  => null,
			'entity_type' => '',
			'entity_id'   => null,
			'action'      => '',
			'limit'       => 100,
			'offset'      => 0,
			'order'       => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$table_name = Database::get_table_name( 'audit_log' );
		$where      = array( '1=1' );
		$values     = array();

		// Build WHERE clause
		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$values[] = $args['user_id'];
		}

		if ( ! empty( $args['project_id'] ) ) {
			$where[]  = 'project_id = %d';
			$values[] = $args['project_id'];
		}

		if ( ! empty( $args['entity_type'] ) ) {
			$where[]  = 'entity_type = %s';
			$values[] = $args['entity_type'];
		}

		if ( ! empty( $args['entity_id'] ) ) {
			$where[]  = 'entity_id = %d';
			$values[] = $args['entity_id'];
		}

		if ( ! empty( $args['action'] ) ) {
			$where[]  = 'action = %s';
			$values[] = $args['action'];
		}

		// Add limit and offset
		$values[] = absint( $args['limit'] );
		$values[] = absint( $args['offset'] );

		// Validate order
		$order = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';

		$where_clause = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at {$order} LIMIT %d OFFSET %d",
			$values
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( ! is_array( $results ) ) {
			return array();
		}

		// Unserialize values
		foreach ( $results as &$result ) {
			$result['old_value'] = self::unserialize_value( $result['old_value'] );
			$result['new_value'] = self::unserialize_value( $result['new_value'] );
		}

		return $results;
	}

	/**
	 * Count audit log entries.
	 *
	 * @since 0.1.0
	 * @param array $args Query arguments. Same as get_entries().
	 * @return int Number of entries.
	 */
	public static function count_entries( array $args = array() ): int {
		global $wpdb;

		$defaults = array(
			'user_id'     => null,
			'project_id'  => null,
			'entity_type' => '',
			'entity_id'   => null,
			'action'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$table_name = Database::get_table_name( 'audit_log' );
		$where      = array( '1=1' );
		$values     = array();

		// Build WHERE clause
		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$values[] = $args['user_id'];
		}

		if ( ! empty( $args['project_id'] ) ) {
			$where[]  = 'project_id = %d';
			$values[] = $args['project_id'];
		}

		if ( ! empty( $args['entity_type'] ) ) {
			$where[]  = 'entity_type = %s';
			$values[] = $args['entity_type'];
		}

		if ( ! empty( $args['entity_id'] ) ) {
			$where[]  = 'entity_id = %d';
			$values[] = $args['entity_id'];
		}

		if ( ! empty( $args['action'] ) ) {
			$where[]  = 'action = %s';
			$values[] = $args['action'];
		}

		$where_clause = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( empty( $values ) ) {
			$query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
			$count = $wpdb->get_var( $query );
		} else {
			$query = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}",
				$values
			);
			$count = $wpdb->get_var( $query );
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return absint( $count );
	}

	/**
	 * Clean up old audit log entries.
	 *
	 * @since 0.1.0
	 * @param int $days Number of days to retain entries.
	 * @return int Number of entries deleted.
	 */
	public static function cleanup( int $days = 90 ): int {
		global $wpdb;

		$table_name = Database::get_table_name( 'audit_log' );
		$date       = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$query = $wpdb->prepare(
			"DELETE FROM {$table_name} WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$date
		);

		$result = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return absint( $result );
	}

	/**
	 * Serialize a value for storage.
	 *
	 * @since 0.1.0
	 * @param mixed $value Value to serialize.
	 * @return string|null Serialized value or null.
	 */
	private static function serialize_value( $value ): ?string {
		if ( null === $value ) {
			return null;
		}

		// If it's a string, store as is
		if ( is_string( $value ) ) {
			return $value;
		}

		// Otherwise, JSON encode
		return wp_json_encode( $value );
	}

	/**
	 * Unserialize a value from storage.
	 *
	 * @since 0.1.0
	 * @param string|null $value Serialized value.
	 * @return mixed Unserialized value.
	 */
	private static function unserialize_value( ?string $value ) {
		if ( null === $value ) {
			return null;
		}

		// Try to decode as JSON
		$decoded = json_decode( $value, true );

		// If it's valid JSON, return decoded value
		if ( null !== $decoded || 'null' === $value ) {
			return $decoded;
		}

		// Otherwise return as is
		return $value;
	}

	/**
	 * Get client IP address.
	 *
	 * Note: For security-critical audit logging, REMOTE_ADDR is prioritized
	 * as it cannot be easily spoofed. X-Forwarded-For is only used if
	 * behind a trusted proxy.
	 *
	 * @since 0.1.0
	 * @return string|null IP address or null.
	 */
	private static function get_client_ip(): ?string {
		$ip = null;

		// Prioritize REMOTE_ADDR as it's most reliable
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

			// If behind a proxy and X-Forwarded-For is present, append it for reference
			// but REMOTE_ADDR remains the primary IP for security
			if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$forwarded = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
				// Extract first IP if multiple are present
				$forwarded_ips = explode( ',', $forwarded );
				$first_ip      = trim( $forwarded_ips[0] );
				// Only append if different from REMOTE_ADDR
				if ( $first_ip !== $ip ) {
					$ip .= ' (via ' . $first_ip . ')';
				}
			}
		} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		}

		return $ip;
	}

	/**
	 * Get user agent string.
	 *
	 * @since 0.1.0
	 * @return string|null User agent or null.
	 */
	private static function get_user_agent(): ?string {
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

		return null;
	}
}
