<?php
/**
 * Capa de servicio para operaciones de alto nivel con la API de Resales.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI360Chat_Resales_Service {

    /**
     * Devuelve los ajustes del conector.
     *
     * @return array
     */
    protected static function get_settings() {
        /**
         * Usamos el filtro estándar del add-on para obtener la configuración normalizada.
         *
         * @see AI360Chat_Addon_Resales::get_settings()
         */
        $settings = apply_filters( 'ai360chat_resales_get_settings', array() );

        return is_array( $settings ) ? $settings : array();
    }

    /**
     * Construye los parámetros de autenticación y entorno comunes a cualquier llamada.
     *
     * @param array $settings
     *
     * @return array
     */
    protected static function build_auth_params( array $settings ) {
        return array(
            'p1'        => isset( $settings['p1'] ) ? $settings['p1'] : '',
            'p2'        => isset( $settings['p2'] ) ? $settings['p2'] : '',
            'P_sandbox' => ! empty( $settings['sandbox'] ) ? 'true' : 'false',
            'P_Lang'    => isset( $settings['language'] ) ? (int) $settings['language'] : 2,
            'p_output'  => 'JSON',
        );
    }

    /**
     * Validates and normalizes a filter_id value.
     *
     * The Resales API requires filter_id to be between 1 and 4.
     * If the provided value is outside this range, it defaults to the provided default.
     *
     * @param int $filter_id The filter ID to validate.
     * @param int $default   The default value if invalid (default: 1).
     *
     * @return int The validated filter_id (1-4).
     */
    protected static function validate_filter_id( $filter_id, $default = 1 ) {
        $filter_id = (int) $filter_id;
        if ( $filter_id < 1 || $filter_id > 4 ) {
            return (int) $default;
        }
        return $filter_id;
    }

    /**
     * Gets the appropriate filter ID based on operation type.
     *
     * Maps operation types to the corresponding filter ID from settings.
     * No fallback from rent to sale - returns the exact filter for the requested operation.
     *
     * @param array  $settings  The addon settings array.
     * @param string $operation The operation type ('sale', 'short_rent', 'long_rent', 'featured').
     *
     * @return int The filter_id (1-4) for the given operation.
     */
    protected static function get_filter_id_for_operation( array $settings, $operation = 'sale' ) {
        switch ( $operation ) {
            case 'short_rent':
            case 'holiday_rent':
            case 'rent_short':
                $filter_id = ! empty( $settings['filter_short_rent_id'] )
                    ? (int) $settings['filter_short_rent_id']
                    : 2;
                break;

            case 'long_rent':
            case 'rent_long':
            case 'rent': // Default rent is long-term
                $filter_id = ! empty( $settings['filter_long_rent_id'] )
                    ? (int) $settings['filter_long_rent_id']
                    : 3;
                break;

            case 'featured':
                $filter_id = ! empty( $settings['filter_featured_id'] )
                    ? (int) $settings['filter_featured_id']
                    : 4;
                break;

            case 'sale':
            default:
                $filter_id = ! empty( $settings['filter_sale_id'] )
                    ? (int) $settings['filter_sale_id']
                    : 1;
                break;
        }

        return self::validate_filter_id( $filter_id, 1 );
    }

    /**
     * Validates and normalizes the filter_id parameter (legacy method).
     *
     * The Resales API requires filter_id to be between 1 and 4.
     * If the provided value is outside this range, it defaults to 1.
     *
     * @deprecated Use get_filter_id_for_operation() instead.
     * @param array $settings The addon settings array.
     *
     * @return int The validated filter_id (1-4).
     */
    protected static function get_validated_filter_id( array $settings ) {
        // For legacy compatibility, default to sale filter
        return self::get_filter_id_for_operation( $settings, 'sale' );
    }

    /**
     * Ejecuta SearchProperties con un conjunto de filtros simplificado.
     *
     * @param array $args {
     *     @type int    $page        Página actual (1..n).
     *     @type int    $per_page    Propiedades por página.
     *     @type int    $min_price   Precio mínimo.
     *     @type int    $max_price   Precio máximo.
     *     @type int    $beds        Nº mínimo de dormitorios.
     *     @type int    $baths       Nº mínimo de baños.
     *     @type string $location    Localización (texto libre).
     *     @type string $type_id     Tipo/subtipo en formato "1-1".
     *     @type string $operation   Operation type: 'sale', 'short_rent', 'long_rent', 'featured'.
     * }
     *
     * @return array|WP_Error
     */
    public static function search( array $args ) {
        $settings = self::get_settings();

        if ( empty( $settings['enabled'] ) || empty( $settings['p1'] ) || empty( $settings['p2'] ) ) {
            return new WP_Error(
                'ai360chat_resales_not_configured',
                __( 'El conector de Resales no está configurado o no está habilitado.', 'ai360chat' )
            );
        }

        $params = self::build_auth_params( $settings );

        // Determine operation type and get the corresponding filter ID
        $operation = isset( $args['operation'] ) ? $args['operation'] : 'sale';
        $filter_id = self::get_filter_id_for_operation( $settings, $operation );
        $params['P_agency_filterid'] = $filter_id;

        // Debug logging for operation and filter ID selection
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( '[ai360chat-resales] Operation detected: ' . $operation . ' → P_agency_filterid: ' . $filter_id );
        }

        // Pagination
        $params['CurrentPage']       = isset( $args['page'] ) ? (int) $args['page'] : 1;
        $params['PropertiesPerPage'] = isset( $args['per_page'] ) ? (int) $args['per_page'] : 10;
        $params['p_ShowLastUpdateDate'] = 'true';
        $params['p_SortType']           = 3;

        if ( ! empty( $args['min_price'] ) ) {
            $params['P_Min_Price'] = (int) $args['min_price'];
        }

        if ( ! empty( $args['max_price'] ) ) {
            $params['P_Max_Price'] = (int) $args['max_price'];
        }

        if ( ! empty( $args['beds'] ) ) {
            $params['P_Beds'] = (int) $args['beds'];
        }

        if ( ! empty( $args['baths'] ) ) {
            $params['P_Baths'] = (int) $args['baths'];
        }

        if ( ! empty( $args['location'] ) ) {
            $params['P_Location'] = sanitize_text_field( $args['location'] );
        }

        if ( ! empty( $args['type_id'] ) ) {
            $params['P_TypeId'] = sanitize_text_field( $args['type_id'] );
        }

        // Debug logging for SearchProperties parameters
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            $debug_params = $params;
            if ( isset( $debug_params['p2'] ) ) {
                $debug_params['p2'] = '***MASKED***';
            }
            error_log( '[ai360chat-resales] SearchProperties params: ' . wp_json_encode( $debug_params ) );
        }

        $result = AI360Chat_Resales_API::get( '/SearchProperties', $params );

        /**
         * Permite ajustar o transformar la respuesta de SearchProperties.
         */
        return apply_filters( 'ai360chat_resales_search_result', $result, $params, $args, $settings );
    }

    /**
     * Obtiene el detalle de una propiedad por referencia.
     *
     * @param string $reference
     *
     * @return array|WP_Error
     */
    public static function get_property_details( $reference ) {
        $settings = self::get_settings();

        if ( empty( $settings['enabled'] ) || empty( $settings['p1'] ) || empty( $settings['p2'] ) ) {
            return new WP_Error(
                'ai360chat_resales_not_configured',
                __( 'El conector de Resales no está configurado o no está habilitado.', 'ai360chat' )
            );
        }

        $params = self::build_auth_params( $settings );

        $params['P_agency_filterid'] = self::get_validated_filter_id( $settings );
        $params['Reference']         = sanitize_text_field( $reference );

        $result = AI360Chat_Resales_API::get( '/PropertyDetails', $params );

        return apply_filters( 'ai360chat_resales_property_details_result', $result, $params, $reference, $settings );
    }

    /**
     * Registra un lead asociado a una propiedad.
     *
     * @param array $lead {
     *     @type string $first_name
     *     @type string $last_name
     *     @type string $email
     *     @type string $subject
     *     @type string $message
     *     @type string $reference
     * }
     *
     * @return array|WP_Error
     */
    public static function register_lead( array $lead ) {
        $settings = self::get_settings();

        if ( empty( $settings['enabled'] ) || empty( $settings['p1'] ) || empty( $settings['p2'] ) ) {
            return new WP_Error(
                'ai360chat_resales_not_configured',
                __( 'El conector de Resales no está configurado o no está habilitado.', 'ai360chat' )
            );
        }

        $params = self::build_auth_params( $settings );

        $params['P_agency_filterid'] = self::get_validated_filter_id( $settings );

        $params['M1']   = isset( $lead['first_name'] ) ? sanitize_text_field( $lead['first_name'] ) : '';
        $params['M2']   = isset( $lead['last_name'] ) ? sanitize_text_field( $lead['last_name'] ) : '';
        $params['M5']   = isset( $lead['email'] ) ? sanitize_email( $lead['email'] ) : '';
        $params['M6']   = isset( $lead['subject'] ) ? sanitize_text_field( $lead['subject'] ) : '';
        $params['M7']   = isset( $lead['message'] ) ? wp_kses_post( $lead['message'] ) : '';
        $params['RsId'] = isset( $lead['reference'] ) ? sanitize_text_field( $lead['reference'] ) : '';

        if ( empty( $params['M5'] ) || empty( $params['RsId'] ) ) {
            return new WP_Error(
                'ai360chat_resales_invalid_lead',
                __( 'Para registrar un lead es necesario incluir al menos email y referencia de propiedad.', 'ai360chat' )
            );
        }

        $result = AI360Chat_Resales_API::get( '/RegisterLead', $params );

        return apply_filters( 'ai360chat_resales_register_lead_result', $result, $params, $lead, $settings );
    }
}
