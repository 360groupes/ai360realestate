<?php
/**
 * Clase de apoyo para posibles integraciones adicionales en el backoffice.
 *
 * De momento la lógica principal de administración vive en AI360Chat_Addon_Resales,
 * pero esta clase queda preparada para extender funcionalidades (metaboxes en Fichas,
 * herramientas, etc.) sin romper compatibilidad hacia atrás.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI360Chat_Resales_Admin {

    public static function init() {
        // Punto de extensión para futuras integraciones en el admin.
    }
}
