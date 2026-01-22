<?php
/**
 * Resales Settings Page - Redirect Notice
 *
 * This file renders a redirection notice informing users that all Resales
 * configuration is now managed through the Connections page (Issue #148).
 * It displays a helpful message with a link to the Connections page and
 * lists the features available there.
 *
 * @package AI360Chat
 * @subpackage Addons\Resales
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$connections_url = admin_url( 'admin.php?page=ai360chat-connections' );
?>
<div class="ai360chat-settings-redirect-notice" style="background: #e7f3ff; border-left: 4px solid #2271b1; padding: 20px; margin: 20px 0;">
    <h2><?php esc_html_e( '🏠 Configuración de Resales', 'ai360-chat' ); ?></h2>
    <p style="font-size: 14px; margin-bottom: 15px;">
        <?php esc_html_e( 'La configuración de Resales se ha centralizado en la sección de Connections para una mejor gestión y evitar conflictos.', 'ai360-chat' ); ?>
    </p>
    <p>
        <a href="<?php echo esc_url( $connections_url ); ?>" class="button button-primary button-hero">
            <?php esc_html_e( 'Ir a Connections → Resales-Online', 'ai360-chat' ); ?>
        </a>
    </p>
    <p class="description" style="margin-top: 15px;">
        <?php esc_html_e( 'En Connections podrás:', 'ai360-chat' ); ?>
    </p>
    <ul style="list-style: disc; margin-left: 20px;">
        <li><?php esc_html_e( 'Configurar credenciales P1/P2', 'ai360-chat' ); ?></li>
        <li><?php esc_html_e( 'Activar/desactivar el conector', 'ai360-chat' ); ?></li>
        <li><?php esc_html_e( 'Probar la conexión con la API', 'ai360-chat' ); ?></li>
        <li><?php esc_html_e( 'Configurar filtros de propiedades', 'ai360-chat' ); ?></li>
    </ul>
</div>
