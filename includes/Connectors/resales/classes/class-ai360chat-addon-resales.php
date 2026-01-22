<?php
/**
 * Núcleo del add-on Resales para AI360Chat.
 *
 * Se encarga de:
 * - Registrar los ajustes del conector (P1, P2, sandbox, idioma, filtro, etc.).
 * - Integrar Resales como sub-tab dentro de Inventario en Ajustes Chat.
 * - Integrar el proveedor de inventario Resales en el sistema AI360_Chat_InventoryService.
 *
 * El objetivo de esta V1 es que, una vez configurado, el chat pueda utilizar Resales
 * como provider de inventario, y que las Fichas puedan trabajar con las propiedades
 * recuperadas desde la API.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI360Chat_Addon_Resales {

    const OPTION_KEY = 'ai360chat_resales_settings';

    /**
     * Punto de entrada del add-on.
     */
    public static function init() {
        // Ajustes y administración.
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

        // Encolar scripts de admin.
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );

        // Filtro para recuperar la configuración normalizada.
        add_filter( 'ai360chat_resales_get_settings', array( __CLASS__, 'get_settings' ) );

        // Registrar el provider de inventario dentro del sistema AI360WA.
        add_filter( 'ai360_chat_inventory_providers', array( __CLASS__, 'register_inventory_provider' ) );
        
        // Note: The Resales sub-tab in Inventario has been removed (issue #148).
        // Resales configuration is now managed exclusively through Connections.
        // The hook 'ai360chat_render_inventory_subtab_resales' is no longer registered.

        // Register external connection with the connections manager.
        add_action( 'ai360chat_register_connections', array( __CLASS__, 'register_external_connection' ) );
    }

    /**
     * Register the Resales connection with the connections manager.
     *
     * @param AI360Chat_Connections_Manager $manager Connections manager instance.
     * @return void
     */
    public static function register_external_connection( $manager ) {
        require_once dirname( __FILE__ ) . '/class-ai360chat-resales-connection.php';
        $manager->register( new AI360Chat_Resales_Connection() );
    }
    
    /**
     * Valores por defecto de la configuración.
     */
    public static function get_default_settings() {
        return array(
            'enabled'              => false,
            'mode'                 => 'standalone', // 'standalone' = agency properties only, 'network' = MLS shared database
            'p1'                   => '',
            'p2'                   => '',
            'sandbox'              => false,
            'language'             => 2,     // ES por defecto
            'filters'              => array(), // Legacy: Array of filter IDs (deprecated, use individual filter_*_id fields)
            'filter_sale_id'       => 1,     // Filter ID for sale properties
            'filter_short_rent_id' => 2,     // Filter ID for short-term rentals
            'filter_long_rent_id'  => 3,     // Filter ID for long-term rentals
            'filter_featured_id'   => 4,     // Filter ID for featured properties
            'property_types'       => array(), // Property types filter
            'locations'            => array(), // Locations filter
            'price_min'            => '',     // Minimum price filter
            'price_max'            => '',     // Maximum price filter
            'bedrooms_min'         => '',     // Minimum bedrooms filter
            'bedrooms_max'         => '',     // Maximum bedrooms filter
            'own_properties'       => false,  // Only show own properties (not MLS)
            'lead_id'              => '',     // Lead ID in Resales-Online
            'lead_language'        => 2,
            'lead_default_msg'     => '',
        );
    }

    /**
     * Devuelve los ajustes actuales, mezclados con los valores por defecto.
     * 
     * Since Issue #148, settings are primarily managed through the Connections system.
     * This method first attempts to get settings from Connections, falling back to
     * legacy option storage for backward compatibility.
     */
    public static function get_settings() {
        // Try to get settings from the Connections system first (new way)
        if ( function_exists( 'ai360chat_connections_manager' ) && class_exists( 'AI360Chat_Connections_Manager' ) ) {
            try {
                $manager = ai360chat_connections_manager();
                if ( $manager ) {
                    $connection = $manager->get( 'resales' );
                    if ( $connection ) {
                        $settings = $connection->get_settings();
                        if ( is_array( $settings ) ) {
                            return $settings;
                        }
                    }
                }
            } catch ( Exception $e ) {
                // Log error and fall through to legacy fallback
                if ( class_exists( 'AI360Chat_LoggerV2_Backend' ) ) {
                    AI360Chat_LoggerV2_Backend::instance()->error(
                        'resales_connection_error',
                        'Error getting Resales settings from Connections',
                        array( 'error' => $e->getMessage() )
                    );
                }
            }
        }

        // Fallback to legacy option for backward compatibility during migration
        $settings = get_option( self::OPTION_KEY, array() );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
        return wp_parse_args( $settings, self::get_default_settings() );
    }

    /**
     * Registro de ajustes en la API de Settings de WordPress.
     * 
     * Uses the same settings group as the main plugin settings form
     * to enable global save functionality.
     */
    public static function register_settings() {
        register_setting(
            'ai360_chat_settings_group',
            self::OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
                'default'           => self::get_default_settings(),
            )
        );
    }

    /**
     * Sanitiza los datos que llegan desde el formulario de ajustes.
     *
     * @param array $input
     *
     * @return array
     */
    public static function sanitize_settings( $input ) {
        $defaults = self::get_default_settings();

        $output = array();
        $output['enabled']          = ! empty( $input['enabled'] );
        
        // Mode field: must be 'standalone' or 'network', default 'standalone'
        $output['mode']             = isset( $input['mode'] ) && in_array( $input['mode'], array( 'standalone', 'network' ), true ) 
                                        ? $input['mode'] 
                                        : $defaults['mode'];
        
        $output['p1']               = isset( $input['p1'] ) ? sanitize_text_field( $input['p1'] ) : '';
        $output['p2']               = isset( $input['p2'] ) ? sanitize_text_field( $input['p2'] ) : '';
        $output['sandbox']          = ! empty( $input['sandbox'] );
        
        // Language field: must be >= 1, default 2 (Spanish)
        $output['language']         = isset( $input['language'] ) ? max( 1, (int) $input['language'] ) : $defaults['language'];
        
        // Legacy filters array: sanitize each value (kept for backwards compatibility)
        $output['filters'] = array();
        if ( isset( $input['filters'] ) && is_array( $input['filters'] ) ) {
            foreach ( $input['filters'] as $filter ) {
                $filter_value = (int) $filter;
                if ( $filter_value >= 1 && $filter_value <= 4 ) {
                    $output['filters'][] = $filter_value;
                }
            }
        }

        // Individual filter IDs for each operation type (1-4 range, validated)
        $output['filter_sale_id']       = isset( $input['filter_sale_id'] ) 
            ? max( 1, min( 4, (int) $input['filter_sale_id'] ) ) 
            : $defaults['filter_sale_id'];
        
        $output['filter_short_rent_id'] = isset( $input['filter_short_rent_id'] ) 
            ? max( 1, min( 4, (int) $input['filter_short_rent_id'] ) ) 
            : $defaults['filter_short_rent_id'];
        
        $output['filter_long_rent_id']  = isset( $input['filter_long_rent_id'] ) 
            ? max( 1, min( 4, (int) $input['filter_long_rent_id'] ) ) 
            : $defaults['filter_long_rent_id'];
        
        $output['filter_featured_id']   = isset( $input['filter_featured_id'] ) 
            ? max( 1, min( 4, (int) $input['filter_featured_id'] ) ) 
            : $defaults['filter_featured_id'];

        // Lead language field: must be >= 1, default 2 (Spanish)
        $output['lead_language']    = isset( $input['lead_language'] ) ? max( 1, (int) $input['lead_language'] ) : $defaults['lead_language'];
        
        $output['lead_default_msg'] = isset( $input['lead_default_msg'] ) ? wp_kses_post( $input['lead_default_msg'] ) : '';

        // Property types (array/multiselect)
        $output['property_types'] = array();
        if ( isset( $input['property_types'] ) && is_array( $input['property_types'] ) ) {
            $output['property_types'] = array_map( 'sanitize_text_field', $input['property_types'] );
        }

        // Locations (array/multiselect)
        $output['locations'] = array();
        if ( isset( $input['locations'] ) && is_array( $input['locations'] ) ) {
            $output['locations'] = array_map( 'sanitize_text_field', $input['locations'] );
        }

        // Price range
        $output['price_min'] = isset( $input['price_min'] ) && $input['price_min'] !== '' ? absint( $input['price_min'] ) : '';
        $output['price_max'] = isset( $input['price_max'] ) && $input['price_max'] !== '' ? absint( $input['price_max'] ) : '';

        // Bedrooms range
        $output['bedrooms_min'] = isset( $input['bedrooms_min'] ) && $input['bedrooms_min'] !== '' ? absint( $input['bedrooms_min'] ) : '';
        $output['bedrooms_max'] = isset( $input['bedrooms_max'] ) && $input['bedrooms_max'] !== '' ? absint( $input['bedrooms_max'] ) : '';

        // Own properties checkbox
        $output['own_properties'] = ! empty( $input['own_properties'] );

        // Lead ID
        $output['lead_id'] = isset( $input['lead_id'] ) && $input['lead_id'] !== '' ? absint( $input['lead_id'] ) : '';

        /**
         * Permite a otros módulos ajustar la configuración antes de guardarla.
         */
        return apply_filters( 'ai360chat_resales_sanitized_settings', $output, $input );
    }

    /**
     * Encola los scripts de admin para la pestaña Resales.
     *
     * @param string $hook_suffix El sufijo del hook de la página actual.
     */
    public static function enqueue_admin_scripts( $hook_suffix ) {
        // Solo encolar en la página de ajustes del chat
        if ( false === strpos( $hook_suffix, 'ai360-chat' ) ) {
            return;
        }

        // Verificar que estamos en la pestaña inventario.
        // No filtramos por subtab porque el cambio de subtab se hace vía JavaScript
        // sin recargar la página, por lo que el script debe estar disponible
        // cuando el usuario navegue a la subtab de Resales.
        $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
        
        if ( 'inventario' !== $tab ) {
            return;
        }

        /*
         * Build the URL to resales-admin.js using AI360_CHAT_PLUGIN_URL.
         *
         * Legacy behavior used plugin_dir_url(dirname(__FILE__)) which incorrectly
         * resolved the path when the addon directory structure was nested.
         * Using AI360_CHAT_PLUGIN_URL ensures the correct base URL regardless of
         * how WordPress resolves plugin directory URLs.
         */
        $script_path = 'includes/addons/ai360chat-resales/assets/js/resales-admin.js';
        $script_url  = defined( 'AI360_CHAT_PLUGIN_URL' )
            ? AI360_CHAT_PLUGIN_URL . $script_path
            : plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/resales-admin.js'; // Fallback

        wp_enqueue_script(
            'ai360chat-resales-admin',
            $script_url,
            array(),
            defined( 'AI360_CHAT_VERSION' ) ? AI360_CHAT_VERSION : '1.0.0',
            true
        );

        wp_localize_script(
            'ai360chat-resales-admin',
            'ai360chatResalesAdmin',
            array(
                'restUrl' => esc_url_raw( rest_url( 'ai360-chat/v1' ) ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
                'i18n'    => array(
                    'testing'    => __( 'Probando...', 'ai360-chat' ),
                    'testButton' => __( 'Probar conexión con Resales', 'ai360-chat' ),
                    'error'      => __( 'Error de conexión', 'ai360-chat' ),
                ),
            )
        );
    }

    /**
     * Registra el proveedor de inventario Resales en el sistema AI360WA.
     *
     * @param AI360_Chat_InventoryProvider[] $providers
     *
     * @return AI360_Chat_InventoryProvider[]
     */
    public static function register_inventory_provider( $providers ) {
        // Solo seguimos si el sistema de inventario está disponible.
        if ( ! class_exists( 'AI360_Chat_InventoryService' ) || ! interface_exists( 'AI360_Chat_InventoryProvider' ) ) {
            return $providers;
        }

        // Evitar duplicados si ya existe un provider de este tipo.
        foreach ( $providers as $provider ) {
            if ( $provider instanceof AI360Chat_Resales_Inventory_Provider ) {
                return $providers;
            }
        }

        $provider = new AI360Chat_Resales_Inventory_Provider();

        if ( $provider->is_available() ) {
            $providers[] = $provider;
        }

        return $providers;
    }
    
    /**
     * Render the Resales inventory sub-tab content.
     *
     * @deprecated Since issue #148. Resales configuration has moved to Connections.
     *
     * This method is kept for backward compatibility but now redirects users
     * to the Connections page where Resales configuration is managed.
     */
    public static function render_inventory_subtab() {
        $connections_url = admin_url( 'admin.php?page=ai360chat-connections' );
        ?>
        <div class="ai360chat-inventory-section-box" style="background: #e7f3ff; border-left: 4px solid #2271b1;">
            <h3><?php esc_html_e( '🏠 Resales Connector', 'ai360-chat' ); ?></h3>
            <p class="description" style="font-size: 14px; margin-bottom: 15px;">
                <?php esc_html_e( 'La configuración de Resales se ha movido a la sección de Connections para evitar conflictos de configuración.', 'ai360-chat' ); ?>
            </p>
            <p>
                <a href="<?php echo esc_url( $connections_url ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Ir a Connections → Resales-Online', 'ai360-chat' ); ?>
                </a>
            </p>
            <p class="description" style="margin-top: 15px;">
                <?php esc_html_e( 'En Connections podrás configurar las credenciales P1/P2, activar/desactivar el conector, y probar la conexión.', 'ai360-chat' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Register the Resales tab in the main settings page.
     *
     * @deprecated Since Issue #148. Use AI360Chat_Resales_Connection via Connections.
     * @param array $tabs The existing tabs array.
     * @return array Modified tabs array (unchanged - Resales no longer has its own top-level tab).
     */
    public static function register_settings_tab( $tabs ) {
        // No longer adding a top-level Resales tab. Settings are now in Inventario > Resales subtab.
        return $tabs;
    }
    
    /**
     * Render the Resales settings tab content (legacy).
     *
     * @deprecated Since Issue #148. Use AI360Chat_Resales_Connection via Connections.
     */
    public static function render_settings_tab() {
        $connections_url = admin_url( 'admin.php?page=ai360chat-connections' );
        ?>
        <div class="notice notice-info">
            <p>
                <?php
                printf(
                    /* translators: %s: URL to Connections page */
                    esc_html__( 'La configuración de Resales se gestiona ahora desde Connections. %s', 'ai360-chat' ),
                    '<a href="' . esc_url( $connections_url ) . '">' . esc_html__( 'Ir a Connections', 'ai360-chat' ) . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Delete all addon data (for uninstall).
     *
     * Cleans up all settings and cached data associated with this addon.
     * Called when the addon is being uninstalled.
     *
     * @return bool True on success.
     */
    public static function delete_addon_data() {
        // Delete addon settings.
        delete_option( self::OPTION_KEY );

        // Clear any cached data.
        delete_transient( 'ai360chat_resales_properties' );
        delete_transient( 'ai360chat_resales_property_types' );
        delete_transient( 'ai360chat_resales_locations' );

        // Log the cleanup.
        if ( class_exists( 'AI360Chat_LoggerV2_Backend' ) ) {
            AI360Chat_LoggerV2_Backend::instance()->info( 'resales_addon_data_deleted', 'Resales addon data deleted' );
        }

        /**
         * Fires when the Resales addon data is deleted.
         */
        do_action( 'ai360chat_resales_addon_data_deleted' );

        return true;
    }
}
