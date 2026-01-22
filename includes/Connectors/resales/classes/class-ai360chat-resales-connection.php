<?php
/**
 * Resales External Connection for AI360Chat.
 *
 * Implements the external connection interface for Resales-Online integration.
 * Allows Resales to be managed through the central connections panel.
 *
 * @package AI360Chat
 * @subpackage Addons\Resales
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AI360Chat_Resales_Connection
 *
 * External connection implementation for Resales-Online.
 */
class AI360Chat_Resales_Connection extends AI360Chat_External_Connection_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'resales',
			__( 'Resales-Online', 'ai360-chat' ),
			__( 'Integración con Resales-Online Web API V6 para búsqueda de propiedades inmobiliarias.', 'ai360-chat' )
		);

		// Set default settings.
		$this->default_settings = array(
			'p1'                   => '',
			'p2'                   => '',
			'sandbox'              => false,
			'language'             => '2',
			'mode'                 => 'standalone',
			'filter_sale_id'       => 1,
			'filter_short_rent_id' => 2,
			'filter_long_rent_id'  => 3,
			'filter_featured_id'   => 4,
			'property_types'       => array(),
			'locations'            => array(),
			'price_min'            => '',
			'price_max'            => '',
			'bedrooms_min'         => '',
			'bedrooms_max'         => '',
			'own_properties'       => false,
			'lead_id'              => '',
			'lead_language'        => '2',
			'lead_default_msg'     => '',
		);
	}

	/**
	 * Get the settings schema.
	 *
	 * @return array Settings schema.
	 */
	public function get_settings_schema() {
		if ( null !== $this->settings_schema ) {
			return $this->settings_schema;
		}

		$this->settings_schema = array(
			array(
				'name'        => 'enabled',
				'type'        => 'checkbox',
				'label'       => __( 'Activar conexión', 'ai360-chat' ),
				'description' => __( 'Habilita la integración con Resales-Online.', 'ai360-chat' ),
				'default'     => false,
			),
			array(
				'name'        => 'p1',
				'type'        => 'text',
				'label'       => __( 'P1 (Agency ID)', 'ai360-chat' ),
				'description' => __( 'Identificador P1 proporcionado por Resales-Online.', 'ai360-chat' ),
				'required'    => true,
				'default'     => '',
			),
			array(
				'name'        => 'p2',
				'type'        => 'password',
				'label'       => __( 'P2 (API Key)', 'ai360-chat' ),
				'description' => __( 'Clave P2 proporcionada por Resales-Online.', 'ai360-chat' ),
				'sensitive'   => true,
				'required'    => true,
				'default'     => '',
			),
			array(
				'name'        => 'mode',
				'type'        => 'select',
				'label'       => __( 'Modo', 'ai360-chat' ),
				'description' => __( 'standalone = solo propiedades de la agencia, network = base de datos compartida MLS.', 'ai360-chat' ),
				'options'     => array(
					'standalone' => __( 'Standalone (Solo agencia)', 'ai360-chat' ),
					'network'    => __( 'Network (MLS compartido)', 'ai360-chat' ),
				),
				'default'     => 'standalone',
			),
			array(
				'name'        => 'sandbox',
				'type'        => 'checkbox',
				'label'       => __( 'Modo Sandbox', 'ai360-chat' ),
				'description' => __( 'Usar entorno de pruebas de Resales-Online.', 'ai360-chat' ),
				'default'     => false,
			),
			array(
				'name'        => 'language',
				'type'        => 'select',
				'label'       => __( 'Idioma por defecto', 'ai360-chat' ),
				'description' => __( 'Idioma para los resultados de propiedades.', 'ai360-chat' ),
				'options'     => array(
					'1' => __( 'English', 'ai360-chat' ),
					'2' => __( 'Español', 'ai360-chat' ),
					'3' => __( 'Deutsch', 'ai360-chat' ),
					'4' => __( 'Français', 'ai360-chat' ),
				),
				'default'     => '2',
			),
			array(
				'name'        => 'filter_sale_id',
				'type'        => 'number',
				'label'       => __( 'Filter ID - Venta', 'ai360-chat' ),
				'description' => __( 'ID del filtro para propiedades en venta (1-4).', 'ai360-chat' ),
				'default'     => 1,
			),
			array(
				'name'        => 'filter_short_rent_id',
				'type'        => 'number',
				'label'       => __( 'Filter ID - Alquiler corto', 'ai360-chat' ),
				'description' => __( 'ID del filtro para alquileres de corta duración (1-4).', 'ai360-chat' ),
				'default'     => 2,
			),
			array(
				'name'        => 'filter_long_rent_id',
				'type'        => 'number',
				'label'       => __( 'Filter ID - Alquiler largo', 'ai360-chat' ),
				'description' => __( 'ID del filtro para alquileres de larga duración (1-4).', 'ai360-chat' ),
				'default'     => 3,
			),
			array(
				'name'        => 'filter_featured_id',
				'type'        => 'number',
				'label'       => __( 'Filter ID - Destacados', 'ai360-chat' ),
				'description' => __( 'ID del filtro para propiedades destacadas (1-4).', 'ai360-chat' ),
				'default'     => 4,
			),
			array(
				'name'        => 'property_types',
				'type'        => 'array',
				'label'       => __( 'Tipos de propiedad', 'ai360-chat' ),
				'description' => __( 'Tipos de propiedades a incluir en las búsquedas (multiselección).', 'ai360-chat' ),
				'default'     => array(),
			),
			array(
				'name'        => 'locations',
				'type'        => 'array',
				'label'       => __( 'Ubicaciones', 'ai360-chat' ),
				'description' => __( 'Ubicaciones a incluir en las búsquedas (multiselección).', 'ai360-chat' ),
				'default'     => array(),
			),
			array(
				'name'        => 'price_min',
				'type'        => 'number',
				'label'       => __( 'Precio mínimo', 'ai360-chat' ),
				'description' => __( 'Precio mínimo para filtrar propiedades.', 'ai360-chat' ),
				'default'     => '',
			),
			array(
				'name'        => 'price_max',
				'type'        => 'number',
				'label'       => __( 'Precio máximo', 'ai360-chat' ),
				'description' => __( 'Precio máximo para filtrar propiedades.', 'ai360-chat' ),
				'default'     => '',
			),
			array(
				'name'        => 'bedrooms_min',
				'type'        => 'number',
				'label'       => __( 'Dormitorios mínimos', 'ai360-chat' ),
				'description' => __( 'Número mínimo de dormitorios.', 'ai360-chat' ),
				'default'     => '',
			),
			array(
				'name'        => 'bedrooms_max',
				'type'        => 'number',
				'label'       => __( 'Dormitorios máximos', 'ai360-chat' ),
				'description' => __( 'Número máximo de dormitorios.', 'ai360-chat' ),
				'default'     => '',
			),
			array(
				'name'        => 'own_properties',
				'type'        => 'checkbox',
				'label'       => __( 'Solo propiedades propias', 'ai360-chat' ),
				'description' => __( 'Mostrar únicamente las propiedades de la agencia (no MLS compartido).', 'ai360-chat' ),
				'default'     => false,
			),
			array(
				'name'        => 'lead_id',
				'type'        => 'number',
				'label'       => __( 'ID del lead', 'ai360-chat' ),
				'description' => __( 'ID del lead en Resales-Online para asociar consultas.', 'ai360-chat' ),
				'default'     => '',
			),
			array(
				'name'        => 'lead_language',
				'type'        => 'select',
				'label'       => __( 'Idioma del lead', 'ai360-chat' ),
				'description' => __( 'Idioma para el registro de leads en Resales-Online.', 'ai360-chat' ),
				'options'     => array(
					'1' => __( 'English', 'ai360-chat' ),
					'2' => __( 'Español', 'ai360-chat' ),
					'3' => __( 'Deutsch', 'ai360-chat' ),
					'4' => __( 'Français', 'ai360-chat' ),
				),
				'default'     => '2',
			),
			array(
				'name'        => 'lead_default_msg',
				'type'        => 'textarea',
				'label'       => __( 'Mensaje predeterminado para leads', 'ai360-chat' ),
				'description' => __( 'Mensaje que se enviará por defecto al registrar un lead en Resales-Online.', 'ai360-chat' ),
				'default'     => '',
			),
		);

		return $this->settings_schema;
	}

	/**
	 * Get current connection settings.
	 *
	 * Merges with addon settings for backward compatibility.
	 *
	 * @return array
	 */
	public function get_settings() {
		$saved = get_option( $this->option_key, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		// Merge with addon settings for backward compatibility.
		$addon_settings = get_option( AI360Chat_Addon_Resales::OPTION_KEY, array() );
		if ( is_array( $addon_settings ) ) {
			foreach ( $this->default_settings as $key => $default ) {
				if ( ! isset( $saved[ $key ] ) && isset( $addon_settings[ $key ] ) ) {
					$saved[ $key ] = $addon_settings[ $key ];
				}
			}
			// Handle enabled flag from addon.
			if ( ! isset( $saved['enabled'] ) && ! empty( $addon_settings['enabled'] ) ) {
				$saved['enabled'] = true;
			}
		}

		return wp_parse_args( $saved, $this->get_default_settings() );
	}

	/**
	 * Save connection settings.
	 *
	 * Also synchronizes with addon settings for backward compatibility.
	 *
	 * @param array $settings Settings to save.
	 * @return bool
	 */
	public function save_settings( $settings ) {
		$result = parent::save_settings( $settings );

		if ( $result ) {
			$sanitized = $this->get_settings();

			// Sync with addon settings for backward compatibility.
			$addon_settings = get_option( AI360Chat_Addon_Resales::OPTION_KEY, array() );
			if ( ! is_array( $addon_settings ) ) {
				$addon_settings = array();
			}

			$addon_settings['enabled']              = $sanitized['enabled'];
			$addon_settings['p1']                   = $sanitized['p1'];
			$addon_settings['p2']                   = $sanitized['p2'];
			$addon_settings['mode']                 = $sanitized['mode'];
			$addon_settings['sandbox']              = $sanitized['sandbox'];
			$addon_settings['language']             = $sanitized['language'];
			$addon_settings['filter_sale_id']       = $sanitized['filter_sale_id'];
			$addon_settings['filter_short_rent_id'] = $sanitized['filter_short_rent_id'];
			$addon_settings['filter_long_rent_id']  = $sanitized['filter_long_rent_id'];
			$addon_settings['filter_featured_id']   = $sanitized['filter_featured_id'];
			$addon_settings['property_types']       = $sanitized['property_types'];
			$addon_settings['locations']            = $sanitized['locations'];
			$addon_settings['price_min']            = $sanitized['price_min'];
			$addon_settings['price_max']            = $sanitized['price_max'];
			$addon_settings['bedrooms_min']         = $sanitized['bedrooms_min'];
			$addon_settings['bedrooms_max']         = $sanitized['bedrooms_max'];
			$addon_settings['own_properties']       = $sanitized['own_properties'];
			$addon_settings['lead_id']              = $sanitized['lead_id'];
			$addon_settings['lead_language']        = $sanitized['lead_language'];
			$addon_settings['lead_default_msg']     = $sanitized['lead_default_msg'];

			update_option( AI360Chat_Addon_Resales::OPTION_KEY, $addon_settings );
		}

		return $result;
	}

	/**
	 * Sanitize settings.
	 *
	 * Custom sanitization for Resales-specific fields.
	 *
	 * @param array $settings Raw settings.
	 * @return array Sanitized settings.
	 */
	protected function sanitize_settings( $settings ) {
		$sanitized = parent::sanitize_settings( $settings );

		// Validate filter IDs (must be 1-4).
		$filter_fields = array( 'filter_sale_id', 'filter_short_rent_id', 'filter_long_rent_id', 'filter_featured_id' );
		foreach ( $filter_fields as $field ) {
			if ( isset( $sanitized[ $field ] ) ) {
				$sanitized[ $field ] = max( 1, min( 4, absint( $sanitized[ $field ] ) ) );
			}
		}

		// Validate mode.
		if ( isset( $sanitized['mode'] ) && ! in_array( $sanitized['mode'], array( 'standalone', 'network' ), true ) ) {
			$sanitized['mode'] = 'standalone';
		}

		// Validate language (must be 1-4 as string).
		if ( isset( $sanitized['language'] ) ) {
			$lang = (string) $sanitized['language'];
			if ( ! in_array( $lang, array( '1', '2', '3', '4' ), true ) ) {
				$sanitized['language'] = '2'; // Default to Spanish.
			} else {
				$sanitized['language'] = $lang;
			}
		}

		// Validate lead_language (must be 1-4 as string).
		if ( isset( $sanitized['lead_language'] ) ) {
			$lang = (string) $sanitized['lead_language'];
			if ( ! in_array( $lang, array( '1', '2', '3', '4' ), true ) ) {
				$sanitized['lead_language'] = '2'; // Default to Spanish.
			} else {
				$sanitized['lead_language'] = $lang;
			}
		}

		// Validate price range (min should not be greater than max if both set).
		if ( ! empty( $sanitized['price_min'] ) && ! empty( $sanitized['price_max'] ) ) {
			if ( $sanitized['price_min'] > $sanitized['price_max'] ) {
				// Swap them if min > max.
				$temp                      = $sanitized['price_min'];
				$sanitized['price_min']    = $sanitized['price_max'];
				$sanitized['price_max']    = $temp;
			}
		}

		// Validate bedrooms range (min should not be greater than max if both set).
		if ( ! empty( $sanitized['bedrooms_min'] ) && ! empty( $sanitized['bedrooms_max'] ) ) {
			if ( $sanitized['bedrooms_min'] > $sanitized['bedrooms_max'] ) {
				// Swap them if min > max.
				$temp                       = $sanitized['bedrooms_min'];
				$sanitized['bedrooms_min']  = $sanitized['bedrooms_max'];
				$sanitized['bedrooms_max']  = $temp;
			}
		}

		return $sanitized;
	}

	/**
	 * Test the connection.
	 *
	 * @return array Result with 'success' and 'message'.
	 */
	public function test_connection() {
		$settings = $this->get_settings();

		// Check for required credentials.
		if ( empty( $settings['p1'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'P1 (Agency ID) no configurado.', 'ai360-chat' ),
			);
		}

		if ( empty( $settings['p2'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'P2 (API Key) no configurado.', 'ai360-chat' ),
			);
		}

		// Test connection using the Resales API class.
		if ( ! class_exists( 'AI360Chat_Resales_API' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Clase AI360Chat_Resales_API no disponible.', 'ai360-chat' ),
			);
		}

		// Get configuration values.
		$mode      = isset( $settings['mode'] ) ? $settings['mode'] : 'standalone';
		$language  = isset( $settings['language'] ) ? (int) $settings['language'] : 2;
		$filter_id = isset( $settings['filter_sale_id'] ) ? (int) $settings['filter_sale_id'] : 1;

		// Clamp filter_id to valid range 1-4.
		if ( $filter_id < 1 || $filter_id > 4 ) {
			$filter_id = 1;
		}

		// Build test API call parameters - matching the working REST endpoint.
		$params = array(
			'p1'         => $settings['p1'],
			'p2'         => $settings['p2'],
			'P_Lang'     => $language,
			'P_PageSize' => 1,  // Only 1 result for quick test.
			'P_PageNo'   => 1,
		);

		// Set the appropriate filter parameter based on mode.
		// IMPORTANT: Parameter names are case-sensitive in Resales API V6.
		// Standalone: P_agency_filterid (lowercase) - agency-specific filter.
		// Network: P_Agency_FilterId (mixed case) - MLS shared database filter.
		if ( 'network' === $mode ) {
			$params['P_Agency_FilterId'] = $filter_id;
		} else {
			$params['P_agency_filterid'] = $filter_id;
		}

		// Make test API call to SearchProperties endpoint.
		$result = AI360Chat_Resales_API::get( 'SearchProperties', $params );

		if ( is_wp_error( $result ) ) {
			$error_message = $result->get_error_message();
			$error_data    = $result->get_error_data();

			// Check for HTTP 401 authentication error.
			if ( isset( $error_data['status_code'] ) && 401 === (int) $error_data['status_code'] ) {
				return array(
					'success' => false,
					'message' => __( 'Error de autenticación (HTTP 401). Verifica que P1/P2 sean correctos y que la IP de este servidor esté autorizada en tu cuenta de Resales-Online.', 'ai360-chat' ),
				);
			}

			// Check for specific error types.
			if ( 'ai360chat_resales_auth_error' === $result->get_error_code() ) {
				$hint = isset( $error_data['hint'] ) ? ' ' . $error_data['hint'] : '';
				return array(
					'success' => false,
					'message' => $error_message . $hint,
				);
			}

			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Error de conexión: %s', 'ai360-chat' ),
					$error_message
				),
			);
		}

		// Check if we got a valid response structure.
		if ( ! is_array( $result ) ) {
			return array(
				'success' => false,
				'message' => __( 'Respuesta inválida de la API de Resales.', 'ai360-chat' ),
			);
		}

		// Check for API errors in the response.
		if ( isset( $result['QueryInfo']['ErrorMessage'] ) && ! empty( $result['QueryInfo']['ErrorMessage'] ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: API error message */
					__( 'Error de la API: %s', 'ai360-chat' ),
					$result['QueryInfo']['ErrorMessage']
				),
			);
		}

		// Extract property count from response.
		$property_count = 0;
		if ( isset( $result['QueryInfo']['PropertyCount'] ) ) {
			$property_count = absint( $result['QueryInfo']['PropertyCount'] );
		} elseif ( isset( $result['QueryInfo']['TotalPropertyCount'] ) ) {
			$property_count = absint( $result['QueryInfo']['TotalPropertyCount'] );
		} elseif ( isset( $result['Property'] ) && is_array( $result['Property'] ) ) {
			$property_count = count( $result['Property'] );
		}

		// Build mode-specific success message.
		$mode_label = 'network' === $mode
			? __( 'Network (Base de datos compartida)', 'ai360-chat' )
			: __( 'Standalone (Solo propiedades de la agencia)', 'ai360-chat' );

		if ( 0 === $property_count ) {
			if ( 'network' === $mode ) {
				$message = __( 'Conectividad OK, pero no se encontraron propiedades en modo Network. Verifica que tu cuenta tenga acceso a la red MLS y que el filtro esté correctamente configurado en Resales-Online.', 'ai360-chat' );
			} else {
				$message = __( 'Conectividad OK, pero no se encontraron propiedades propias. Esto es normal si la agencia no tiene listados activos en Resales-Online.', 'ai360-chat' );
			}
		} else {
			$message = sprintf(
				/* translators: 1: number of properties, 2: mode label */
				__( 'Conexión exitosa. %1$d propiedades disponibles en modo %2$s.', 'ai360-chat' ),
				$property_count,
				$mode_label
			);
		}

		return array(
			'success' => true,
			'message' => $message,
			'details' => array(
				'total' => $property_count,
				'mode'  => $mode,
			),
		);
	}

	/**
	 * Check if the connection is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		// Check connection settings.
		$settings = $this->get_settings();
		if ( ! empty( $settings['enabled'] ) ) {
			return true;
		}

		// Also check addon settings for backward compatibility.
		$addon_settings = get_option( AI360Chat_Addon_Resales::OPTION_KEY, array() );
		if ( is_array( $addon_settings ) && ! empty( $addon_settings['enabled'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Clean up connection data.
	 *
	 * @return bool
	 */
	public function cleanup() {
		// Clean up connection data.
		$result = parent::cleanup();

		// Clear connection-specific transients.
		delete_transient( 'ai360chat_resales_connection_status' );

		return $result;
	}
}
