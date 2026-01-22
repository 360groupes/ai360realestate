<?php
/**
 * REST API endpoint para probar la conexión con Resales.
 *
 * Proporciona un endpoint POST /wp-json/ai360-chat/v1/test-resales
 * que realiza una llamada ligera a la API de Resales para verificar
 * que las credenciales P1/P2 son correctas.
 *
 * @package AI360Chat
 * @subpackage Addons\Resales
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI360Chat_Resales_REST {

    /**
     * Error code to Spanish message mapping for Resales Web API V6.
     *
     * @var array
     */
    private static $error_code_messages = array(
        '001' => 'La IP del servidor no coincide con la API Key configurada en Resales-Online.',
        '102' => 'La credencial P2 (API Key) no es válida.',
        '103' => 'El parámetro P_Agency_FilterId (Filter Agency ID) no es válido.',
        '104' => 'El filtro configurado no es válido o no devuelve propiedades. Revisa la configuración de tus API Filters en Resales-Online.',
        '003' => 'Falta el parámetro de filtro (FilterId / P_Agency_FilterId) en la petición.',
    );

    /**
     * Inicializa el endpoint REST.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    /**
     * Registra las rutas REST.
     */
    public static function register_routes() {
        register_rest_route(
            'ai360-chat/v1',
            '/test-resales',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'test_connection' ),
                'permission_callback' => array( __CLASS__, 'permission_check' ),
            )
        );

        // Synchronization endpoint
        register_rest_route(
            'ai360-chat/v1',
            '/resales/sync',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'sync_inventory' ),
                'permission_callback' => array( __CLASS__, 'permission_check' ),
            )
        );

        // Status endpoint
        register_rest_route(
            'ai360-chat/v1',
            '/resales/status',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_status' ),
                'permission_callback' => array( __CLASS__, 'permission_check' ),
            )
        );

        // Property details endpoint (public - for chat use)
        // Note: Reference pattern allows alphanumeric, dots, underscores, and hyphens
        // to accommodate various reference formats (e.g., "R12345", "AGC-001", "PROP.2024.01")
        register_rest_route(
            'ai360-chat/v1',
            '/resales/property/(?P<reference>[a-zA-Z0-9._-]+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_property_details' ),
                'permission_callback' => '__return_true', // Public endpoint for chat
                'args'                => array(
                    'reference' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'description'       => __( 'Property reference code', 'ai360chat' ),
                    ),
                ),
            )
        );
    }

    /**
     * Verifica permisos para el endpoint.
     *
     * Requiere:
     * - Capacidad manage_options
     * - Nonce válido en header X-WP-Nonce
     *
     * @param WP_REST_Request $request
     *
     * @return bool|WP_Error
     */
    public static function permission_check( $request ) {
        // Verificar capacidad
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'No tienes permisos para realizar esta acción.', 'ai360chat' ),
                array( 'status' => 403 )
            );
        }

        // Verificar nonce
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Nonce inválido o ausente.', 'ai360chat' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Callback para probar la conexión con Resales.
     *
     * Realiza una llamada ligera a SearchProperties con P_PageSize=1
     * para verificar que las credenciales son válidas.
     *
     * El comportamiento varía según el modo configurado:
     * - Standalone: utiliza el filtro de la agencia (p_agency_filterid)
     * - Network: utiliza el filtro de red MLS (P_Agency_FilterId)
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public static function test_connection( $request ) {
        // Verificar que el add-on está disponible
        if ( ! class_exists( 'AI360Chat_Addon_Resales' ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'El add-on Resales no está disponible.', 'ai360chat' ),
                ),
                500
            );
        }

        // Get saved settings as defaults.
        $saved_settings = AI360Chat_Addon_Resales::get_settings();

        // Read parameters from JSON request body if available, otherwise use saved settings.
        $body_params = $request->get_json_params();
        $body_params = is_array( $body_params ) ? $body_params : array();

        $p1              = isset( $body_params['p1'] ) && '' !== $body_params['p1'] ? sanitize_text_field( $body_params['p1'] ) : $saved_settings['p1'];
        $p2              = isset( $body_params['p2'] ) && '' !== $body_params['p2'] ? sanitize_text_field( $body_params['p2'] ) : $saved_settings['p2'];
        
        // Get the mode setting (standalone or network)
        $mode = isset( $saved_settings['mode'] ) && in_array( $saved_settings['mode'], array( 'standalone', 'network' ), true )
                ? $saved_settings['mode']
                : 'standalone';
        
        // Handle filter ID - support both legacy filter_id and new filters array
        $default_filter  = 1;
        if ( isset( $saved_settings['filters'] ) && is_array( $saved_settings['filters'] ) && ! empty( $saved_settings['filters'] ) ) {
            $default_filter = (int) $saved_settings['filters'][0];
        } elseif ( isset( $saved_settings['filter_id'] ) ) {
            $default_filter = (int) $saved_settings['filter_id'];
        }
        $p_agency_filter = isset( $body_params['P_Agency_FilterId'] ) ? (int) $body_params['P_Agency_FilterId'] : $default_filter;
        
        // Clamp filter_id to valid range 1-4
        if ( $p_agency_filter < 1 || $p_agency_filter > 4 ) {
            $p_agency_filter = 1;
        }
        
        $p_lang          = isset( $body_params['P_Lang'] ) ? (int) $body_params['P_Lang'] : ( isset( $saved_settings['language'] ) ? (int) $saved_settings['language'] : 2 );

        // Verificar que hay credenciales configuradas
        if ( empty( $p1 ) || empty( $p2 ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'Credenciales P1 y P2 no configuradas. Por favor, guarda primero los ajustes con las credenciales.', 'ai360chat' ),
                ),
                200
            );
        }

        // Preparar parámetros para una llamada ligera
        // La diferencia entre modos está en el nombre del parámetro de filtro
        $params = array(
            'p1'         => $p1,
            'p2'         => $p2,
            'P_Lang'     => $p_lang,
            'P_PageSize' => 1, // Solo 1 resultado para test rápido
            'P_PageNo'   => 1,
        );
        
        // Set the appropriate filter parameter based on mode
        // Standalone: P_agency_filterid (agency-specific filter) - matches working test script
        // Network: P_Agency_FilterId (MLS shared database filter)
        if ( 'network' === $mode ) {
            $params['P_Agency_FilterId'] = $p_agency_filter;
        } else {
            $params['P_agency_filterid'] = $p_agency_filter;
        }

        // Realizar llamada a la API usando la clase API si está disponible
        if ( class_exists( 'AI360Chat_Resales_API' ) && method_exists( 'AI360Chat_Resales_API', 'get' ) ) {
            // Use the API class for the call
            $data = AI360Chat_Resales_API::get( 'SearchProperties', $params );

            if ( is_wp_error( $data ) ) {
                $error_data  = $data->get_error_data();
                $status_code = isset( $error_data['status_code'] ) ? (int) $error_data['status_code'] : null;
                $body        = isset( $error_data['body'] ) ? $error_data['body'] : '';

                // Handle HTTP 401 with enhanced error parsing.
                if ( 401 === $status_code ) {
                    return self::build_401_error_response( $body, $status_code );
                }

                // Generic error response for other errors.
                $hint    = isset( $error_data['hint'] ) ? $error_data['hint'] : null;
                $details = array();
                if ( $status_code ) {
                    $details['status_code'] = $status_code;
                }
                if ( $body ) {
                    $details['body'] = $body;
                }
                if ( $hint ) {
                    $details['hint'] = $hint;
                }

                return new WP_REST_Response(
                    array(
                        'success' => false,
                        'message' => $data->get_error_message(),
                        'details' => ! empty( $details ) ? $details : null,
                    ),
                    200
                );
            }

            // Check for API errors in the response
            if ( isset( $data['QueryInfo']['ErrorMessage'] ) && ! empty( $data['QueryInfo']['ErrorMessage'] ) ) {
                return new WP_REST_Response(
                    array(
                        'success' => false,
                        'message' => sprintf(
                            /* translators: %s = API error message */
                            __( 'Error de la API: %s', 'ai360chat' ),
                            $data['QueryInfo']['ErrorMessage']
                        ),
                    ),
                    200
                );
            }

            // Success - get property count (check both TotalPropertyCount and Total/total).
            $property_count = self::extract_total_count( $data );

            // Build mode-specific response message
            $mode_label = 'network' === $mode
                ? __( 'Network (Base de datos compartida)', 'ai360chat' )
                : __( 'Standalone (Solo propiedades de la agencia)', 'ai360chat' );

            // Handle zero results differently based on mode
            if ( 0 === $property_count ) {
                if ( 'network' === $mode ) {
                    $message = __( 'Conectividad OK, pero no se encontraron propiedades en modo Network. Verifica que tu cuenta tenga acceso a la red MLS y que el filtro esté correctamente configurado en Resales-Online.', 'ai360chat' );
                } else {
                    $message = __( 'Conectividad OK, pero no se encontraron propiedades propias. Esto es normal si la agencia no tiene listados activos en Resales-Online.', 'ai360chat' );
                }
            } else {
                $message = sprintf(
                    /* translators: 1: number of properties, 2: mode label */
                    __( 'Conectividad OK. %1$d propiedades disponibles en modo %2$s.', 'ai360chat' ),
                    $property_count,
                    $mode_label
                );
            }

            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => $message,
                    'details' => array(
                        'total' => $property_count,
                        'mode'  => $mode,
                    ),
                ),
                200
            );
        }

        // Fallback: Make the request directly
        $url = 'https://webapi.resales-online.com/V6/SearchProperties';

        $response = wp_remote_get(
            add_query_arg( $params, $url ),
            array(
                'timeout' => 15,
            )
        );

        // Manejar errores de conexión
        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => sprintf(
                        /* translators: %s = error message */
                        __( 'Error de conexión: %s', 'ai360chat' ),
                        $response->get_error_message()
                    ),
                ),
                200
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        // Verificar código HTTP
        if ( $code < 200 || $code >= 300 ) {
            // Handle HTTP 401 with enhanced error parsing.
            if ( 401 === $code ) {
                return self::build_401_error_response( $body, $code );
            }

            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => sprintf(
                        /* translators: %d = HTTP status code */
                        __( 'Error HTTP %d al conectar con Resales.', 'ai360chat' ),
                        $code
                    ),
                    'details' => array(
                        'status_code' => $code,
                        'body'        => $body,
                        'hint'        => __( 'Revisa la configuración de la API y contacta con soporte si el problema persiste.', 'ai360chat' ),
                    ),
                ),
                200
            );
        }

        // Intentar decodificar respuesta
        $data = json_decode( $body, true );

        if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'Respuesta inválida de la API de Resales.', 'ai360chat' ),
                ),
                200
            );
        }

        // Verificar si hay errores en la respuesta de Resales
        // La API de Resales devuelve errores en diferentes formatos
        if ( isset( $data['QueryInfo']['ErrorMessage'] ) && ! empty( $data['QueryInfo']['ErrorMessage'] ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => sprintf(
                        /* translators: %s = API error message */
                        __( 'Error de la API: %s', 'ai360chat' ),
                        $data['QueryInfo']['ErrorMessage']
                    ),
                ),
                200
            );
        }

        // Si llegamos aquí, la conexión fue exitosa
        $property_count = self::extract_total_count( $data );

        // Build mode-specific response message (fallback path)
        $mode_label = 'network' === $mode
            ? __( 'Network (Base de datos compartida)', 'ai360chat' )
            : __( 'Standalone (Solo propiedades de la agencia)', 'ai360chat' );

        // Handle zero results differently based on mode
        if ( 0 === $property_count ) {
            if ( 'network' === $mode ) {
                $message = __( 'Conectividad OK, pero no se encontraron propiedades en modo Network. Verifica que tu cuenta tenga acceso a la red MLS y que el filtro esté correctamente configurado en Resales-Online.', 'ai360chat' );
            } else {
                $message = __( 'Conectividad OK, pero no se encontraron propiedades propias. Esto es normal si la agencia no tiene listados activos en Resales-Online.', 'ai360chat' );
            }
        } else {
            $message = sprintf(
                /* translators: 1: number of properties, 2: mode label */
                __( 'Conectividad OK. %1$d propiedades disponibles en modo %2$s.', 'ai360chat' ),
                $property_count,
                $mode_label
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => $message,
                'details' => array(
                    'total' => $property_count,
                    'mode'  => $mode,
                ),
            ),
            200
        );
    }

    /**
     * Build an enhanced error response for HTTP 401 errors.
     *
     * Parses the Resales Web API V6 error payload (transaction.errordescription)
     * and maps error codes to actionable Spanish messages.
     *
     * @param string $body        The raw response body.
     * @param int    $status_code The HTTP status code (401).
     *
     * @return WP_REST_Response
     */
    private static function build_401_error_response( $body, $status_code ) {
        $error_codes   = array();
        $error_details = array();
        $decoded       = json_decode( $body, true );

        // Try to extract error codes from transaction.errordescription.
        if ( is_array( $decoded )
            && isset( $decoded['transaction'] )
            && is_array( $decoded['transaction'] )
            && isset( $decoded['transaction']['errordescription'] )
            && is_array( $decoded['transaction']['errordescription'] )
        ) {
            $error_codes = $decoded['transaction']['errordescription'];
        }

        // Build human-readable message from detected error codes.
        $messages = array();
        foreach ( $error_codes as $code => $description ) {
            $code_str = (string) $code;
            if ( isset( self::$error_code_messages[ $code_str ] ) ) {
                $messages[] = sprintf( '• [%s] %s', $code_str, self::$error_code_messages[ $code_str ] );
            } else {
                // Include unknown codes with their original description.
                $messages[] = sprintf( '• [%s] %s', $code_str, sanitize_text_field( $description ) );
            }
        }

        if ( ! empty( $messages ) ) {
            $human_message = __( 'Error de autenticación (HTTP 401). Problemas detectados:', 'ai360chat' ) . "\n" . implode( "\n", $messages );
        } else {
            $human_message = __( 'Error de autenticación (HTTP 401).', 'ai360chat' );
        }

        // Build the details object.
        $details = array(
            'status_code' => $status_code,
            'error_codes' => ! empty( $error_codes ) ? $error_codes : null,
            'body'        => $body,
            'hint'        => __( 'Verifica que P1/P2 sean correctos y que la IP de este servidor esté autorizada en tu cuenta de Resales-Online.', 'ai360chat' ),
        );

        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => $human_message,
                'details' => $details,
            ),
            200
        );
    }

    /**
     * Extract total property count from API response.
     *
     * Checks multiple possible locations for the count.
     * The Resales Web API V6 returns PropertyCount in QueryInfo.
     *
     * @param array $data The decoded API response.
     *
     * @return int The total property count.
     */
    private static function extract_total_count( $data ) {
        // Primary: Resales Web API V6 uses PropertyCount
        if ( isset( $data['QueryInfo']['PropertyCount'] ) ) {
            return (int) $data['QueryInfo']['PropertyCount'];
        }
        // Fallback: Some versions may use TotalPropertyCount
        if ( isset( $data['QueryInfo']['TotalPropertyCount'] ) ) {
            return (int) $data['QueryInfo']['TotalPropertyCount'];
        }
        // Count items in Property array if QueryInfo is not available
        if ( isset( $data['Property'] ) && is_array( $data['Property'] ) ) {
            return count( $data['Property'] );
        }
        if ( isset( $data['Total'] ) ) {
            return (int) $data['Total'];
        }
        if ( isset( $data['total'] ) ) {
            return (int) $data['total'];
        }

        return 0;
    }

    /**
     * Synchronize inventory from Resales.
     *
     * @param WP_REST_Request $request The REST request.
     *
     * @return WP_REST_Response
     */
    public static function sync_inventory( $request ) {
        // Check if Resales addon is available
        if ( ! class_exists( 'AI360Chat_Addon_Resales' ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'El add-on de Resales no está disponible.', 'ai360chat' ),
                ),
                200
            );
        }

        $settings = AI360Chat_Addon_Resales::get_settings();

        // Check if addon is enabled
        if ( empty( $settings['enabled'] ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'El conector de Resales no está habilitado.', 'ai360chat' ),
                ),
                200
            );
        }

        // Check if credentials are configured
        if ( empty( $settings['p1'] ) || empty( $settings['p2'] ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'El conector de Resales no está configurado. Configura las credenciales P1 y P2.', 'ai360chat' ),
                ),
                200
            );
        }

        // Get optional arguments from request
        $body_params = $request->get_json_params();
        $body_params = is_array( $body_params ) ? $body_params : array();

        $args = array(
            'page'     => isset( $body_params['page'] ) ? (int) $body_params['page'] : 1,
            'per_page' => isset( $body_params['per_page'] ) ? (int) $body_params['per_page'] : 100,
        );

        // Check if Resales service is available for sync
        if ( ! class_exists( 'AI360Chat_Resales_Service' ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'El servicio de Resales no está disponible.', 'ai360chat' ),
                ),
                200
            );
        }

        // Perform a search to fetch properties
        $result = AI360Chat_Resales_Service::search( $args );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                ),
                200
            );
        }

        // Store the last sync time
        update_option( 'ai360chat_resales_last_sync', current_time( 'mysql' ) );

        // Use the common extraction method to get items count
        $items_count = self::extract_total_count( $result );

        /**
         * Fires after Resales inventory synchronization completes.
         *
         * @param array $result The synchronization result.
         * @param array $args   The synchronization arguments.
         */
        do_action( 'ai360_chat_resales_sync_completed', $result, $args );

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => sprintf(
                    /* translators: %d = number of properties */
                    __( 'Sincronización completada. %d propiedades encontradas.', 'ai360chat' ),
                    $items_count
                ),
                'data'    => array(
                    'items_count' => $items_count,
                    'synced_at'   => current_time( 'mysql' ),
                ),
            ),
            200
        );
    }

    /**
     * Get Resales module status.
     *
     * @param WP_REST_Request $request The REST request.
     *
     * @return WP_REST_Response
     */
    public static function get_status( $request ) {
        // Check if addon is available
        if ( ! class_exists( 'AI360Chat_Addon_Resales' ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'El add-on de Resales no está disponible.', 'ai360chat' ),
                    'status'  => array(
                        'addon_active'  => false,
                        'module_active' => false,
                        'configured'    => false,
                        'enabled'       => false,
                        'last_sync'     => null,
                    ),
                ),
                200
            );
        }

        $settings = AI360Chat_Addon_Resales::get_settings();
        $last_sync = get_option( 'ai360chat_resales_last_sync', null );

        return new WP_REST_Response(
            array(
                'success' => true,
                'status'  => array(
                    'addon_active'   => true,
                    'module_active'  => true, // Addon is active, so module functionality is available
                    'configured'     => ! empty( $settings['p1'] ) && ! empty( $settings['p2'] ),
                    'enabled'        => ! empty( $settings['enabled'] ),
                    'mode'           => isset( $settings['mode'] ) ? $settings['mode'] : 'standalone',
                    'sandbox'        => ! empty( $settings['sandbox'] ),
                    'language'       => isset( $settings['language'] ) ? (int) $settings['language'] : 2,
                    'filters'        => isset( $settings['filters'] ) ? $settings['filters'] : array(),
                    'last_sync'      => $last_sync,
                ),
            ),
            200
        );
    }

    /**
     * Get property details by reference.
     *
     * Fetches detailed property information from Resales WebAPI V6
     * and returns it in a structured format suitable for chat display.
     *
     * @param WP_REST_Request $request The REST request with 'reference' param.
     *
     * @return WP_REST_Response
     */
    public static function get_property_details( $request ) {
        $reference = $request->get_param( 'reference' );

        // Check if addon is available
        if ( ! class_exists( 'AI360Chat_Addon_Resales' ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'El add-on de Resales no está disponible.', 'ai360chat' ),
                ),
                200
            );
        }

        $settings = AI360Chat_Addon_Resales::get_settings();

        // Check if addon is enabled
        if ( empty( $settings['enabled'] ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'El conector de Resales no está habilitado.', 'ai360chat' ),
                ),
                200
            );
        }

        // Check if credentials are configured
        if ( empty( $settings['p1'] ) || empty( $settings['p2'] ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'El conector de Resales no está configurado. Configura las credenciales P1 y P2.', 'ai360chat' ),
                ),
                200
            );
        }

        // Check if Resales service is available
        if ( ! class_exists( 'AI360Chat_Resales_Service' ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'El servicio de Resales no está disponible.', 'ai360chat' ),
                ),
                200
            );
        }

        // Fetch property details
        $result = AI360Chat_Resales_Service::get_property_details( $reference );

        if ( is_wp_error( $result ) ) {
            $error_data  = $result->get_error_data();
            $status_code = isset( $error_data['status_code'] ) ? (int) $error_data['status_code'] : null;
            $body        = isset( $error_data['body'] ) ? $error_data['body'] : '';

            // Handle HTTP 401 with enhanced error parsing
            if ( 401 === $status_code ) {
                return self::build_property_error_response( $body, $status_code, $reference );
            }

            // Generic error response
            $details = array();
            if ( $status_code ) {
                $details['status_code'] = $status_code;
            }
            if ( $body ) {
                $details['body'] = $body;
            }
            if ( isset( $error_data['hint'] ) ) {
                $details['hint'] = $error_data['hint'];
            }

            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                    'details' => ! empty( $details ) ? $details : null,
                ),
                200
            );
        }

        // Check for API errors in the response
        if ( isset( $result['QueryInfo']['ErrorMessage'] ) && ! empty( $result['QueryInfo']['ErrorMessage'] ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => sprintf(
                        /* translators: %s = API error message */
                        __( 'Error de la API: %s', 'ai360chat' ),
                        $result['QueryInfo']['ErrorMessage']
                    ),
                ),
                200
            );
        }

        // Extract property from result
        $property = null;
        if ( isset( $result['Property'] ) && is_array( $result['Property'] ) ) {
            $property = $result['Property'];
        } elseif ( isset( $result['Properties'] ) && is_array( $result['Properties'] ) && ! empty( $result['Properties'] ) ) {
            $property = reset( $result['Properties'] );
        }

        if ( empty( $property ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => sprintf(
                        /* translators: %s = property reference */
                        __( 'No se encontró la propiedad con referencia %s.', 'ai360chat' ),
                        $reference
                    ),
                ),
                200
            );
        }

        // Normalize property data to structured format
        $normalized = self::normalize_property_for_chat( $property );

        return new WP_REST_Response(
            array(
                'success'  => true,
                'property' => $normalized,
            ),
            200
        );
    }

    /**
     * Normalize a property from Resales API to a structured format for chat display.
     *
     * @param array $property Raw property data from Resales API.
     *
     * @return array Normalized property object.
     */
    private static function normalize_property_for_chat( array $property ) {
        // Reference
        $ref = '';
        if ( ! empty( $property['Reference'] ) ) {
            $ref = $property['Reference'];
        } elseif ( ! empty( $property['AgencyRef'] ) ) {
            $ref = $property['AgencyRef'];
        }

        // Title - combine property type with location
        $title_parts = array();
        if ( ! empty( $property['PropertyType']['Type'] ) ) {
            $title_parts[] = $property['PropertyType']['Type'];
        } elseif ( ! empty( $property['PropertyType']['NameType'] ) ) {
            $title_parts[] = $property['PropertyType']['NameType'];
        }
        if ( ! empty( $property['Location'] ) ) {
            $title_parts[] = $property['Location'];
        }
        $title = implode( ' en ', array_filter( $title_parts ) );
        if ( empty( $title ) && $ref ) {
            $title = $ref;
        }

        // Location - combine area, location, and province
        $location_parts = array();
        if ( ! empty( $property['Area'] ) ) {
            $location_parts[] = $property['Area'];
        }
        if ( ! empty( $property['Location'] ) ) {
            $location_parts[] = $property['Location'];
        }
        if ( ! empty( $property['Province'] ) ) {
            $location_parts[] = $property['Province'];
        }
        $location = implode( ', ', array_unique( array_filter( $location_parts ) ) );

        // Price
        $price = 0;
        if ( isset( $property['Price'] ) ) {
            $raw_price = is_array( $property['Price'] ) ? reset( $property['Price'] ) : $property['Price'];
            $price     = (float) preg_replace( '/[^0-9.]/', '', (string) $raw_price );
        }

        // Currency - default to EUR for Spanish properties
        $currency = 'EUR';
        if ( ! empty( $property['Currency'] ) ) {
            $currency = $property['Currency'];
        }

        // Bedrooms
        $bedrooms = 0;
        if ( isset( $property['Bedrooms'] ) ) {
            $bedrooms = (int) $property['Bedrooms'];
        }

        // Bathrooms
        $bathrooms = 0;
        if ( isset( $property['Bathrooms'] ) ) {
            $bathrooms = (int) $property['Bathrooms'];
        }

        // Area in m²
        $area_m2 = 0;
        if ( isset( $property['Built'] ) ) {
            $area_m2 = (int) $property['Built'];
        } elseif ( isset( $property['BuiltArea'] ) ) {
            $area_m2 = (int) $property['BuiltArea'];
        } elseif ( isset( $property['GardenPlot'] ) ) {
            $area_m2 = (int) $property['GardenPlot'];
        }

        // Property URL (if available)
        $url = '';
        if ( ! empty( $property['URL'] ) && filter_var( $property['URL'], FILTER_VALIDATE_URL ) ) {
            $url = $property['URL'];
        } elseif ( ! empty( $property['VirtualTourUrl'] ) && filter_var( $property['VirtualTourUrl'], FILTER_VALIDATE_URL ) ) {
            $url = $property['VirtualTourUrl'];
        }

        // Main image URL
        $image = '';
        if ( ! empty( $property['Pictures'] ) && is_array( $property['Pictures'] ) ) {
            $first_pic = reset( $property['Pictures'] );
            if ( is_array( $first_pic ) && ! empty( $first_pic['URL'] ) ) {
                $image = $first_pic['URL'];
            } elseif ( is_string( $first_pic ) ) {
                $image = $first_pic;
            }
        } elseif ( ! empty( $property['MainImage'] ) ) {
            $image = $property['MainImage'];
        }

        return array(
            'ref'       => $ref,
            'title'     => $title,
            'location'  => $location,
            'price'     => $price,
            'currency'  => $currency,
            'bedrooms'  => $bedrooms,
            'bathrooms' => $bathrooms,
            'area_m2'   => $area_m2,
            'url'       => $url,
            'image'     => $image,
        );
    }

    /**
     * Build an enhanced error response for property fetch errors.
     *
     * @param string $body        The raw response body.
     * @param int    $status_code The HTTP status code.
     * @param string $reference   The property reference that was requested.
     *
     * @return WP_REST_Response
     */
    private static function build_property_error_response( $body, $status_code, $reference ) {
        $error_codes   = array();
        $decoded       = json_decode( $body, true );

        // Try to extract error codes from transaction.errordescription
        if ( is_array( $decoded )
            && isset( $decoded['transaction'] )
            && is_array( $decoded['transaction'] )
            && isset( $decoded['transaction']['errordescription'] )
            && is_array( $decoded['transaction']['errordescription'] )
        ) {
            $error_codes = $decoded['transaction']['errordescription'];
        }

        // Build human-readable message from detected error codes
        $messages = array();
        foreach ( $error_codes as $code => $description ) {
            $code_str = (string) $code;
            if ( isset( self::$error_code_messages[ $code_str ] ) ) {
                $messages[] = sprintf( '• [%s] %s', $code_str, self::$error_code_messages[ $code_str ] );
            } else {
                $messages[] = sprintf( '• [%s] %s', $code_str, sanitize_text_field( $description ) );
            }
        }

        if ( ! empty( $messages ) ) {
            $human_message = sprintf(
                /* translators: %s = property reference */
                __( 'No se pudo obtener los detalles de la propiedad %s (HTTP 401). Problemas detectados:', 'ai360chat' ),
                $reference
            ) . "\n" . implode( "\n", $messages );
        } else {
            $human_message = sprintf(
                /* translators: %s = property reference */
                __( 'No se pudo obtener los detalles de la propiedad %s. Error de autenticación (HTTP 401).', 'ai360chat' ),
                $reference
            );
        }

        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => $human_message,
                'details' => array(
                    'status_code' => $status_code,
                    'error_codes' => ! empty( $error_codes ) ? $error_codes : null,
                    'body'        => $body,
                    'hint'        => __( 'Verifica que P1/P2 sean correctos y que la IP de este servidor esté autorizada en tu cuenta de Resales-Online.', 'ai360chat' ),
                ),
            ),
            200
        );
    }
}
