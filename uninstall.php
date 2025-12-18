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

// Cargar autoloader de Composer
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// Si no existe el autoloader, cargar las clases manualmente
	require_once __DIR__ . '/includes/Core/Database.php';
	require_once __DIR__ . '/includes/Core/Migrations.php';
}

// Eliminar todas las opciones del plugin
delete_option( 'ai360re_version' );
delete_option( 'ai360re_installed_at' );
delete_option( 'ai360re_schema_version' );

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

// Eliminar tablas personalizadas usando el sistema de migraciones
if ( class_exists( 'AI360RealEstate\\Core\\Migrations' ) ) {
	\AI360RealEstate\Core\Migrations::uninstall();
}

// Limpiar cualquier cache
wp_cache_flush();
