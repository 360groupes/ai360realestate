<?php
/**
 * Database Schema Definitions
 *
 * Contains all SQL statements for table creation.
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
 * Database schema definitions.
 *
 * Contains all SQL statements for table creation.
 *
 * @since 0.1.0
 */
class Schema {

	/**
	 * Get the charset collate for tables.
	 *
	 * @since 0.1.0
	 * @return string Charset collate string.
	 */
	public static function get_charset_collate(): string {
		global $wpdb;
		return $wpdb->get_charset_collate();
	}

	/**
	 * Get SQL for creating all tables.
	 *
	 * @since 0.1.0
	 * @return string SQL statements.
	 */
	public static function get_schema(): string {
		$charset_collate = self::get_charset_collate();
		$tables          = array();

		// Projects table
		$tables[] = self::get_projects_schema( $charset_collate );

		// Project users table
		$tables[] = self::get_project_users_schema( $charset_collate );

		// Properties table
		$tables[] = self::get_properties_schema( $charset_collate );

		// Property versions table
		$tables[] = self::get_property_versions_schema( $charset_collate );

		// Connectors table
		$tables[] = self::get_connectors_schema( $charset_collate );

		// Sync log table
		$tables[] = self::get_sync_log_schema( $charset_collate );

		// AI tasks table
		$tables[] = self::get_ai_tasks_schema( $charset_collate );

		// Audit log table
		$tables[] = self::get_audit_log_schema( $charset_collate );

		return implode( "\n\n", $tables );
	}

	/**
	 * Get projects table schema.
	 *
	 * @since 0.1.0
	 * @param string $charset_collate Charset collate string.
	 * @return string SQL for creating projects table.
	 */
	private static function get_projects_schema( string $charset_collate ): string {
		$table_name = Database::get_table_name( 'projects' );

		return "CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    settings LONGTEXT,
    created_by BIGINT(20) UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug),
    KEY status (status),
    KEY created_by (created_by)
) {$charset_collate};";
	}

	/**
	 * Get project users table schema.
	 *
	 * @since 0.1.0
	 * @param string $charset_collate Charset collate string.
	 * @return string SQL for creating project users table.
	 */
	private static function get_project_users_schema( string $charset_collate ): string {
		$table_name = Database::get_table_name( 'project_users' );

		return "CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'viewer',
    permissions LONGTEXT,
    added_by BIGINT(20) UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY project_user (project_id, user_id),
    KEY project_id (project_id),
    KEY user_id (user_id),
    KEY role (role)
) {$charset_collate};";
	}

	/**
	 * Get properties table schema.
	 *
	 * @since 0.1.0
	 * @param string $charset_collate Charset collate string.
	 * @return string SQL for creating properties table.
	 */
	private static function get_properties_schema( string $charset_collate ): string {
		$table_name = Database::get_table_name( 'properties' );

		return "CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT(20) UNSIGNED NOT NULL,
    external_id VARCHAR(255),
    reference VARCHAR(100),
    title VARCHAR(500),
    description_short TEXT,
    description_long LONGTEXT,
    property_type VARCHAR(100),
    operation_type ENUM('sale', 'rent', 'both') DEFAULT 'sale',
    status ENUM('imported', 'optimized', 'validated', 'ready', 'published', 'synced', 'draft', 'archived') DEFAULT 'draft',
    price DECIMAL(15,2),
    price_currency VARCHAR(3) DEFAULT 'EUR',
    location_address VARCHAR(500),
    location_city VARCHAR(255),
    location_province VARCHAR(255),
    location_country VARCHAR(100) DEFAULT 'ES',
    location_postal_code VARCHAR(20),
    location_lat DECIMAL(10,8),
    location_lng DECIMAL(11,8),
    bedrooms TINYINT UNSIGNED,
    bathrooms TINYINT UNSIGNED,
    area_built DECIMAL(10,2),
    area_plot DECIMAL(10,2),
    features LONGTEXT,
    images LONGTEXT,
    documents LONGTEXT,
    seo_title VARCHAR(255),
    seo_description TEXT,
    seo_keywords VARCHAR(500),
    connector_hashes LONGTEXT,
    metadata LONGTEXT,
    created_by BIGINT(20) UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY project_id (project_id),
    KEY external_id (external_id),
    KEY reference (reference),
    KEY status (status),
    KEY property_type (property_type),
    KEY operation_type (operation_type),
    KEY location_city (location_city),
    KEY price (price),
    FULLTEXT KEY search_index (title, description_short, location_address, location_city)
) {$charset_collate};";
	}

	/**
	 * Get property versions table schema.
	 *
	 * @since 0.1.0
	 * @param string $charset_collate Charset collate string.
	 * @return string SQL for creating property versions table.
	 */
	private static function get_property_versions_schema( string $charset_collate ): string {
		$table_name = Database::get_table_name( 'property_versions' );

		return "CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT(20) UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    data LONGTEXT NOT NULL,
    changed_by VARCHAR(100),
    change_reason VARCHAR(255),
    change_source ENUM('user', 'ai', 'sync', 'import') DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY property_id (property_id),
    KEY version_number (version_number),
    KEY change_source (change_source),
    KEY created_at (created_at)
) {$charset_collate};";
	}

	/**
	 * Get connectors table schema.
	 *
	 * @since 0.1.0
	 * @param string $charset_collate Charset collate string.
	 * @return string SQL for creating connectors table.
	 */
	private static function get_connectors_schema( string $charset_collate ): string {
		$table_name = Database::get_table_name( 'connectors' );

		return "CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT(20) UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'error') DEFAULT 'inactive',
    credentials LONGTEXT,
    settings LONGTEXT,
    sync_direction ENUM('pull', 'push', 'bidirectional') DEFAULT 'bidirectional',
    conflict_strategy ENUM('local_wins', 'remote_wins', 'last_modified', 'manual_review') DEFAULT 'last_modified',
    last_sync_at DATETIME,
    last_sync_status VARCHAR(50),
    last_sync_message TEXT,
    created_by BIGINT(20) UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY project_id (project_id),
    KEY type (type),
    KEY status (status)
) {$charset_collate};";
	}

	/**
	 * Get sync log table schema.
	 *
	 * @since 0.1.0
	 * @param string $charset_collate Charset collate string.
	 * @return string SQL for creating sync log table.
	 */
	private static function get_sync_log_schema( string $charset_collate ): string {
		$table_name = Database::get_table_name( 'sync_log' );

		return "CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    connector_id BIGINT(20) UNSIGNED NOT NULL,
    property_id BIGINT(20) UNSIGNED,
    operation ENUM('pull', 'push', 'create', 'update', 'delete') NOT NULL,
    status ENUM('pending', 'success', 'error', 'skipped', 'conflict') NOT NULL,
    direction ENUM('incoming', 'outgoing') NOT NULL,
    local_hash VARCHAR(64),
    remote_hash VARCHAR(64),
    error_message TEXT,
    details LONGTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY connector_id (connector_id),
    KEY property_id (property_id),
    KEY operation (operation),
    KEY status (status),
    KEY created_at (created_at)
) {$charset_collate};";
	}

	/**
	 * Get AI tasks table schema.
	 *
	 * @since 0.1.0
	 * @param string $charset_collate Charset collate string.
	 * @return string SQL for creating AI tasks table.
	 */
	private static function get_ai_tasks_schema( string $charset_collate ): string {
		$table_name = Database::get_table_name( 'ai_tasks' );

		return "CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT(20) UNSIGNED NOT NULL,
    task_type VARCHAR(50) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    priority TINYINT UNSIGNED DEFAULT 5,
    input_data LONGTEXT,
    output_data LONGTEXT,
    tokens_used INT UNSIGNED DEFAULT 0,
    cost DECIMAL(10,6) DEFAULT 0,
    provider VARCHAR(50),
    model VARCHAR(100),
    error_message TEXT,
    attempts TINYINT UNSIGNED DEFAULT 0,
    max_attempts TINYINT UNSIGNED DEFAULT 3,
    scheduled_at DATETIME,
    started_at DATETIME,
    completed_at DATETIME,
    created_by BIGINT(20) UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY property_id (property_id),
    KEY task_type (task_type),
    KEY status (status),
    KEY priority (priority),
    KEY scheduled_at (scheduled_at)
) {$charset_collate};";
	}

	/**
	 * Get audit log table schema.
	 *
	 * @since 0.1.0
	 * @param string $charset_collate Charset collate string.
	 * @return string SQL for creating audit log table.
	 */
	private static function get_audit_log_schema( string $charset_collate ): string {
		$table_name = Database::get_table_name( 'audit_log' );

		return "CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED,
    project_id BIGINT(20) UNSIGNED,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT(20) UNSIGNED,
    action VARCHAR(50) NOT NULL,
    old_value LONGTEXT,
    new_value LONGTEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY project_id (project_id),
    KEY entity_type (entity_type),
    KEY entity_id (entity_id),
    KEY action (action),
    KEY created_at (created_at)
) {$charset_collate};";
	}
}
