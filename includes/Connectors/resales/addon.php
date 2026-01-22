<?php
/**
 * Loader del add-on Resales para AI360Chat.
 *
 * Este archivo es cargado por el núcleo de AI360Chat cuando detecta el directorio
 * includes/addons/ai360chat-resales.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$addon_dir = __DIR__;

// Cargar clases núcleo del add-on.
require_once $addon_dir . '/classes/class-ai360chat-addon-resales.php';
require_once $addon_dir . '/classes/class-ai360chat-resales-api.php';
require_once $addon_dir . '/classes/class-ai360chat-resales-service.php';
require_once $addon_dir . '/classes/class-ai360chat-resales-fichas.php';
require_once $addon_dir . '/classes/class-ai360chat-resales-inventory-provider.php';

// Cargar clase REST para el endpoint de test de conexión.
require_once $addon_dir . '/includes/class-ai360chat-resales-rest.php';

// Inicializar el add-on.
AI360Chat_Addon_Resales::init();

// Inicializar el endpoint REST.
AI360Chat_Resales_REST::init();
