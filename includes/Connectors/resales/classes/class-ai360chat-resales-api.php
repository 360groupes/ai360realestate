<?php
/**
 * Cliente sencillo para la Web API V6 de Resales-Online.
 *
 * Encapsula las llamadas a:
 * - SearchProperties
 * - PropertyDetails
 * - RegisterLead
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI360Chat_Resales_API {

    /**
     * Construye la URL base de la API.
     *
     * @return string
     */
    protected static function get_base_url() {
        return 'https://webapi.resales-online.com/V6';
    }

    /**
     * Devuelve el endpoint completo a partir del nombre.
     *
     * @param string $endpoint
     *
     * @return string
     */
    protected static function get_endpoint( $endpoint ) {
        $base = rtrim( self::get_base_url(), '/' );
        $endpoint = ltrim( $endpoint, '/' );

        return $base . '/' . $endpoint;
    }

    /**
     * Ejecuta una llamada GET sencilla contra la API.
     *
     * @param string $endpoint
     * @param array  $params
     *
     * @return array|WP_Error
     */
    public static function get( $endpoint, array $params ) {
        $url = self::get_endpoint( $endpoint );

        $response = wp_remote_get(
            add_query_arg( $params, $url ),
            array(
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code < 200 || $code >= 300 ) {
            // Specific error message for HTTP 401 (Unauthorized)
            if ( 401 === $code ) {
                return new WP_Error(
                    'ai360chat_resales_auth_error',
                    __( 'Error de autenticación (HTTP 401).', 'ai360chat' ),
                    array(
                        'status_code' => $code,
                        'body'        => $body,
                        'hint'        => __( 'Verifica que P1/P2 sean correctos y que la IP de este servidor esté autorizada en tu cuenta de Resales-Online.', 'ai360chat' ),
                    )
                );
            }

            // Generic error for other non-2xx status codes
            return new WP_Error(
                'ai360chat_resales_http_error',
                sprintf(
                    /* translators: %d = HTTP status code */
                    __( 'Error HTTP %d al llamar a la API de Resales.', 'ai360chat' ),
                    $code
                ),
                array(
                    'status_code' => $code,
                    'body'        => $body,
                )
            );
        }

        $data = json_decode( $body, true );

        if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
            return new WP_Error(
                'ai360chat_resales_json_error',
                __( 'No se pudo decodificar la respuesta JSON de la API de Resales.', 'ai360chat' ),
                array(
                    'body'  => $body,
                    'error' => json_last_error_msg(),
                )
            );
        }

        return $data;
    }
}
