<?php
/**
 * Database Management Class
 *
 * Handles database connection, table creation, and common queries.
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
 * Database management class.
 *
 * Handles database connection, table creation, and common queries.
 *
 * @since 0.1.0
 */
class Database {

	/**
	 * Table prefix for plugin tables
	 *
	 * @var string
	 */
	const TABLE_PREFIX = 'ai360re_';

	/**
	 * Current database schema version
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * Get full table name with WordPress prefix.
	 *
	 * @since 0.1.0
	 * @param string $table Table name without prefix.
	 * @return string Full table name.
	 */
	public static function get_table_name( string $table ): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_PREFIX . $table;
	}

	/**
	 * Get all plugin table names.
	 *
	 * @since 0.1.0
	 * @return array List of table names.
	 */
	public static function get_all_tables(): array {
		return array(
			self::get_table_name( 'projects' ),
			self::get_table_name( 'project_users' ),
			self::get_table_name( 'properties' ),
			self::get_table_name( 'property_versions' ),
			self::get_table_name( 'connectors' ),
			self::get_table_name( 'sync_log' ),
			self::get_table_name( 'ai_tasks' ),
			self::get_table_name( 'audit_log' ),
		);
	}

	/**
	 * Check if all tables exist.
	 *
	 * @since 0.1.0
	 * @return bool True if all tables exist.
	 */
	public static function tables_exist(): bool {
		global $wpdb;
		foreach ( self::get_all_tables() as $table ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
			);
			if ( $exists !== $table ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get current schema version from database.
	 *
	 * @since 0.1.0
	 * @return string Schema version or empty string if not set.
	 */
	public static function get_schema_version(): string {
		return get_option( 'ai360re_schema_version', '' );
	}

	/**
	 * Update schema version in database.
	 *
	 * @since 0.1.0
	 * @param string $version Version to set.
	 */
	public static function set_schema_version( string $version ): void {
		update_option( 'ai360re_schema_version', $version );
	}
}
