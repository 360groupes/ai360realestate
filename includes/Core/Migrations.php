<?php
/**
 * Database Migrations Handler
 *
 * Manages database schema updates and versioning.
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
 * Database migrations handler.
 *
 * Manages database schema updates and versioning.
 *
 * @since 0.1.0
 */
class Migrations {

	/**
	 * Run all pending migrations.
	 *
	 * @since 0.1.0
	 * @return bool True on success.
	 */
	public static function run(): bool {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$current_version = Database::get_schema_version();
		$target_version  = Database::SCHEMA_VERSION;

		// First installation
		if ( empty( $current_version ) ) {
			return self::install();
		}

		// Already up to date
		if ( version_compare( $current_version, $target_version, '>=' ) ) {
			return true;
		}

		// Run incremental migrations
		return self::migrate( $current_version, $target_version );
	}

	/**
	 * Initial installation - create all tables.
	 *
	 * @since 0.1.0
	 * @return bool True on success.
	 */
	public static function install(): bool {
		$sql = Schema::get_schema();
		dbDelta( $sql );

		Database::set_schema_version( Database::SCHEMA_VERSION );

		return Database::tables_exist();
	}

	/**
	 * Run migrations between versions.
	 *
	 * @since 0.1.0
	 * @param string $from Current version.
	 * @param string $to Target version.
	 * @return bool True on success.
	 */
	private static function migrate( string $from, string $to ): bool {
		// Future migrations will be added here
		// Example:
		// if ( version_compare( $from, '1.1.0', '<' ) ) {
		//     self::migrate_to_1_1_0();
		// }

		Database::set_schema_version( $to );
		return true;
	}

	/**
	 * Drop all plugin tables.
	 *
	 * @since 0.1.0
	 * @return bool True on success.
	 */
	public static function uninstall(): bool {
		global $wpdb;

		$tables = Database::get_all_tables();

		foreach ( $tables as $table ) {
			// Escape table name with backticks for safety
			$escaped_table = '`' . esc_sql( $table ) . '`';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$escaped_table}" );
		}

		delete_option( 'ai360re_schema_version' );

		return true;
	}
}
