<?php
/**
 * Uninstall Script
 *
 * Este script se ejecuta cuando el plugin es completamente desinstalado.
 * NO se ejecuta en desactivación, solo en desinstalación.
 *
 * @package AI360RealEstate
 * @since 0.1.0
 */

// Verificar que viene de WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Eliminar todas las opciones del plugin
delete_option( 'ai360re_version' );
delete_option( 'ai360re_installed_at' );

// Eliminar transients
global $wpdb;
$wpdb->query( 
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_ai360re_' ) . '%'
	)
);
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_timeout_ai360re_' ) . '%'
	)
);

// Eliminar capabilities y roles personalizados (placeholder para futuras implementaciones)
// remove_role( 'ai360re_agent' );

// Eliminar tablas personalizadas (placeholder - nombres definidos en DATABASE_SCHEMA.md)
// Las tablas se crearán en PR-02, aquí solo dejamos los nombres:
$tables = array(
	$wpdb->prefix . 'ai360re_projects',
	$wpdb->prefix . 'ai360re_properties',
	$wpdb->prefix . 'ai360re_property_versions',
	$wpdb->prefix . 'ai360re_property_meta',
	$wpdb->prefix . 'ai360re_connector_configs',
	$wpdb->prefix . 'ai360re_sync_logs',
	$wpdb->prefix . 'ai360re_ai_logs',
	$wpdb->prefix . 'ai360re_audit_logs',
);

// Descomentar cuando las tablas existan (PR-02):
// foreach ( $tables as $table ) {
//     $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
// }

// Limpiar cualquier cache
wp_cache_flush();
