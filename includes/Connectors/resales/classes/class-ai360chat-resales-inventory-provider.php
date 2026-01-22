<?php
/**
 * Provider de inventario para Resales-Online.
 *
 * Implementa la interfaz AI360_Chat_InventoryProvider para que el sistema de inventario
 * de AI360Chat pueda utilizar Resales como una fuente más de propiedades, tanto
 * desde el chat como desde las pantallas de Fichas / Inventario.
 *
 * @package AI360Chat
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! interface_exists( 'AI360_Chat_InventoryProvider' ) ) {
    return;
}

class AI360Chat_Resales_Inventory_Provider implements AI360_Chat_InventoryProvider {

    /**
     * Minimum price value to consider as a valid real estate price (in local currency).
     */
    const MIN_VALID_PRICE = 100;

    /**
     * Maximum price value to consider as a valid real estate price (in local currency).
     * 50 million covers luxury properties.
     */
    const MAX_VALID_PRICE = 50000000;

    /**
     * Identificador interno del provider.
     *
     * @return string
     */
    public function get_name() {
        return 'resales';
    }

    /**
     * Indica si el provider está disponible.
     *
     * Para ello comprobamos:
     * - Que el sistema de inventario existe.
     * - Que el conector de Resales está habilitado y con credenciales válidas.
     *
     * @return bool
     */
    public function is_available() {
        if ( ! class_exists( 'AI360_Chat_InventoryService' ) ) {
            return false;
        }

        $settings = apply_filters( 'ai360chat_resales_get_settings', array() );

        if ( empty( $settings ) || ! is_array( $settings ) ) {
            return false;
        }

        if ( empty( $settings['enabled'] ) ) {
            return false;
        }

        if ( empty( $settings['p1'] ) || empty( $settings['p2'] ) ) {
            return false;
        }

        return true;
    }

    /**
     * Ejecuta una búsqueda en Resales a partir de los criterios genéricos del inventario.
     *
     * @param array $criteria Criterios de búsqueda estándar de AI360_Chat_InventoryService.
     *                        Supported criteria:
     *                        - operation: 'sale', 'short_rent', 'long_rent', 'featured' (determines which filter to use)
     *                        - min_price: Minimum price filter
     *                        - max_price: Maximum price filter
     *                        - min_beds: Minimum bedrooms
     *                        - min_baths: Minimum bathrooms
     *                        - location: Location text filter
     *                        - type_id: Property type ID
     *                        - query: Free text search (used as location fallback)
     * @param int   $limit    Límite máximo de resultados. Este valor debe provenir del ajuste global
     *                        de "máx. ítems por respuesta" configurado en el core de AI360Chat
     *                        (AI360_Chat_InventoryService). El provider utiliza este límite tanto
     *                        para el parámetro 'per_page' de la API como para un array_slice()
     *                        de seguridad posterior.
     *
     * @return array Lista de items normalizados.
     */
    public function search( array $criteria, $limit = 10 ) {
        // Parse query to extract structured criteria if only query is provided.
        if ( ! empty( $criteria['query'] ) ) {
            $parsed = $this->parse_query_criteria( $criteria['query'] );
            
            // Add parsed values to criteria only if not already explicitly set.
            // This ensures explicit criteria from the caller takes precedence over parsed values.
            foreach ( array( 'max_price', 'min_price', 'min_beds', 'location' ) as $key ) {
                if ( isset( $parsed[ $key ] ) && ! isset( $criteria[ $key ] ) ) {
                    $criteria[ $key ] = $parsed[ $key ];
                }
            }
        }

        // Adaptar criterios genéricos del inventario a los argumentos esperados por el servicio.
        $args = array(
            'page'        => 1,
            'per_page'    => $limit,
            'min_price'   => isset( $criteria['min_price'] ) ? (int) $criteria['min_price'] : 0,
            'max_price'   => isset( $criteria['max_price'] ) ? (int) $criteria['max_price'] : 0,
            'beds'        => isset( $criteria['min_beds'] ) ? (int) $criteria['min_beds'] : 0,
            'baths'       => isset( $criteria['min_baths'] ) ? (int) $criteria['min_baths'] : 0,
            'location'    => isset( $criteria['location'] ) ? $criteria['location'] : '',
            'type_id'     => isset( $criteria['type_id'] ) ? $criteria['type_id'] : '',
        );

        // Pass operation type to service for dynamic filter selection
        // This determines which filter ID (sale, short_rent, long_rent, featured) to use
        if ( isset( $criteria['operation'] ) && ! empty( $criteria['operation'] ) ) {
            $args['operation'] = $criteria['operation'];
        }

        // Si hay término de búsqueda libre, lo usamos como location si no se ha definido.
        if ( empty( $args['location'] ) && ! empty( $criteria['query'] ) ) {
            $args['location'] = $criteria['query'];
        }

        $result = AI360Chat_Resales_Service::search( $args );

        if ( is_wp_error( $result ) ) {
            return array();
        }

        $items = $this->map_result_to_items( $result );

        if ( $limit > 0 && count( $items ) > $limit ) {
            $items = array_slice( $items, 0, $limit );
        }

        return $items;
    }

    /**
     * Parse a free-text query to extract structured search criteria.
     *
     * Extracts:
     * - Price: "750€", "300k", "under 500000", "max 1000", "hasta 2000"
     * - Location: Common Spanish real estate locations (Málaga, Marbella, etc.)
     * - Bedrooms: "2 bedroom", "3 bed", "2 dormitorios", "3 habitaciones"
     *
     * @param string $query Free-text search query.
     * @return array Extracted criteria with keys: max_price, min_price, location, min_beds.
     */
    public function parse_query_criteria( $query ) {
        $criteria = array();
        $query_lower = mb_strtolower( trim( $query ), 'UTF-8' );

        // === PRICE EXTRACTION ===
        // Pattern 1: "750€", "1000€", "300,000€" (explicit euro symbol)
        // Regex captures both plain numbers and thousand-separated formats
        if ( preg_match( '/(\d+|\d{1,3}(?:[.,]\d{3})+)\s*€/u', $query_lower, $matches ) ) {
            $price = $this->parse_price_value( $matches[1] );
            if ( $price > 0 ) {
                $criteria['max_price'] = $price;
            }
        }
        // Pattern 2: "300k", "1.5M", "2m" (k=thousands, m=millions)
        elseif ( preg_match( '/(\d+(?:[.,]\d+)?)\s*([km])\b/i', $query_lower, $matches ) ) {
            $value = floatval( str_replace( ',', '.', $matches[1] ) );
            $multiplier = ( strtolower( $matches[2] ) === 'k' ) ? 1000 : 1000000;
            $criteria['max_price'] = (int) ( $value * $multiplier );
        }
        // Pattern 3: "under 500000", "max 1000", "hasta 2000", "menos de 800"
        // Captures both plain numbers and thousand-separated formats after the keyword
        elseif ( preg_match( '/(under|max|hasta|menos de|maximum|maximo|máximo)\s+(\d+|\d{1,3}(?:[.,]\d{3})+)/iu', $query_lower, $matches ) ) {
            $price = $this->parse_price_value( $matches[2] );
            if ( $price > 0 ) {
                $criteria['max_price'] = $price;
            }
        }
        // Pattern 4: "from 500", "desde 1000", "min 800", "minimum 600"
        // Captures both plain numbers and thousand-separated formats after the keyword
        elseif ( preg_match( '/(from|desde|min|minimum|minimo|mínimo)\s+(\d+|\d{1,3}(?:[.,]\d{3})+)/iu', $query_lower, $matches ) ) {
            $price = $this->parse_price_value( $matches[2] );
            if ( $price > 0 ) {
                $criteria['min_price'] = $price;
            }
        }
        // Pattern 5: Standalone number that looks like a price (4+ digits without context)
        elseif ( preg_match( '/\b(\d{4,})\b/', $query_lower, $matches ) ) {
            $price = (int) $matches[1];
            // Only treat as price if it's within reasonable real estate price range
            if ( $price >= self::MIN_VALID_PRICE && $price <= self::MAX_VALID_PRICE ) {
                $criteria['max_price'] = $price;
            }
        }

        // === LOCATION EXTRACTION ===
        // Common Spanish real estate locations (Costa del Sol and surrounding areas)
        $locations = array(
            // Málaga province
            'malaga'       => 'Malaga',
            'málaga'       => 'Malaga',
            'marbella'     => 'Marbella',
            'estepona'     => 'Estepona',
            'fuengirola'   => 'Fuengirola',
            'torremolinos' => 'Torremolinos',
            'benalmadena'  => 'Benalmadena',
            'benalmádena'  => 'Benalmadena',
            'mijas'        => 'Mijas',
            'nerja'        => 'Nerja',
            'calahonda'    => 'Calahonda',
            'san pedro'    => 'San Pedro de Alcantara',
            'nueva andalucia' => 'Nueva Andalucia',
            'puerto banus' => 'Puerto Banus',
            'banus'        => 'Puerto Banus',
            'banús'        => 'Puerto Banus',
            'cancelada'    => 'Cancelada',
            'atalaya'      => 'Atalaya',
            'el paraiso'   => 'El Paraiso',
            'el paraíso'   => 'El Paraiso',
            'benahavis'    => 'Benahavis',
            'benahavís'    => 'Benahavis',
            'rincon de la victoria' => 'Rincon de la Victoria',
            'rincón de la victoria' => 'Rincon de la Victoria',
            'velez-malaga' => 'Velez-Malaga',
            'vélez-málaga' => 'Velez-Malaga',
            'alhaurin'     => 'Alhaurin',
            'coin'         => 'Coin',
            'coín'         => 'Coin',
            'la cala'      => 'La Cala',
            'riviera'      => 'Riviera del Sol',
            'riviera del sol' => 'Riviera del Sol',
            'elviria'      => 'Elviria',
            'cabopino'     => 'Cabopino',
            'los monteros' => 'Los Monteros',
            'golden mile'  => 'Golden Mile',
            'sierra blanca' => 'Sierra Blanca',
            // Other popular areas
            'costa del sol' => 'Costa del Sol',
            'alicante'     => 'Alicante',
            'valencia'     => 'Valencia',
            'barcelona'    => 'Barcelona',
            'madrid'       => 'Madrid',
            'sevilla'      => 'Sevilla',
            'seville'      => 'Sevilla',
            'ibiza'        => 'Ibiza',
            'mallorca'     => 'Mallorca',
            'majorca'      => 'Mallorca',
            'tenerife'     => 'Tenerife',
            'gran canaria' => 'Gran Canaria',
            'lanzarote'    => 'Lanzarote',
            'fuerteventura' => 'Fuerteventura',
        );

        foreach ( $locations as $pattern => $normalized ) {
            if ( mb_strpos( $query_lower, $pattern ) !== false ) {
                $criteria['location'] = $normalized;
                break; // Take first match.
            }
        }

        // === BEDROOMS EXTRACTION ===
        // Pattern 1: "2 bedroom", "3 bed", "4 beds"
        if ( preg_match( '/(\d+)\s*(?:bedroom|bed|beds)\b/i', $query_lower, $matches ) ) {
            $criteria['min_beds'] = (int) $matches[1];
        }
        // Pattern 2: "2 dormitorios", "3 habitaciones", "4 dorms"
        elseif ( preg_match( '/(\d+)\s*(?:dormitorio|dormitorios|habitacion|habitaciones|habitación|dorms?)\b/iu', $query_lower, $matches ) ) {
            $criteria['min_beds'] = (int) $matches[1];
        }

        return $criteria;
    }

    /**
     * Parse a price value string to an integer.
     *
     * Handles formats like "750", "1,000", "300.000", etc.
     *
     * @param string $value Price value string.
     * @return int Parsed price value.
     */
    protected function parse_price_value( $value ) {
        $value = trim( $value );
        
        // For real estate prices, we expect whole numbers.
        // Thousand separators can be . or , depending on locale:
        // - European: 300.000 or 1.500.000
        // - US/UK: 300,000 or 1,500,000
        
        // Detect thousand-separated format: groups of 3 digits after separator
        if ( preg_match( '/^\d{1,3}(?:[.,]\d{3})+$/', $value ) ) {
            // Has thousand separators - remove all . and ,
            $value = str_replace( array( '.', ',' ), '', $value );
        } else {
            // Simple number - just remove any commas (US format thousands)
            // Note: We don't expect decimals for price values in this context
            $value = str_replace( ',', '', $value );
        }

        return (int) $value;
    }

    /**
     * Convierte el resultado de SearchProperties en una lista de items de inventario.
     *
     * The Resales Web API V6 returns properties in a 'Property' array (singular),
     * not 'Properties' (plural).
     *
     * @param array $result
     *
     * @return array
     */
    protected function map_result_to_items( array $result ) {
        $items      = array();
        $properties = array();

        // Primary: Resales Web API V6 uses 'Property' (singular) for the array
        if ( isset( $result['Property'] ) && is_array( $result['Property'] ) ) {
            $properties = $result['Property'];
        }
        // Fallback: Check for 'Properties' (plural) for compatibility
        elseif ( isset( $result['Properties'] ) && is_array( $result['Properties'] ) ) {
            $properties = $result['Properties'];
        }

        if ( empty( $properties ) ) {
            return $items;
        }

        foreach ( $properties as $property ) {
            if ( ! is_array( $property ) ) {
                continue;
            }

            $items[] = $this->normalize_property( $property );
        }

        return array_filter( $items );
    }

    /**
     * Normaliza una propiedad de Resales al formato estándar de inventario de AI360Chat.
     *
     * @param array $property
     *
     * @return array
     */
    protected function normalize_property( array $property ) {
        $reference = isset( $property['Reference'] ) ? $property['Reference'] : '';
        $agency_ref = isset( $property['AgencyRef'] ) ? $property['AgencyRef'] : '';

        $id = $reference ? $reference : ( $agency_ref ? $agency_ref : uniqid( 'resales_', true ) );

        $title_parts = array();

        if ( ! empty( $property['PropertyType']['Type'] ) ) {
            $title_parts[] = $property['PropertyType']['Type'];
        } elseif ( ! empty( $property['PropertyType']['NameType'] ) ) {
            $title_parts[] = $property['PropertyType']['NameType'];
        }

        if ( ! empty( $property['Location'] ) ) {
            $title_parts[] = $property['Location'];
        }

        if ( ! empty( $property['Province'] ) ) {
            $title_parts[] = $property['Province'];
        }

        $title = implode( ' - ', array_filter( $title_parts ) );

        if ( $reference ) {
            $title = $title ? $title . ' · ' . $reference : $reference;
        }

        // Descripción corta (si existe).
        $description = '';
        if ( ! empty( $property['Description']['es'] ) && is_string( $property['Description']['es'] ) ) {
            $description = $property['Description']['es'];
        } elseif ( ! empty( $property['Description']['en'] ) && is_string( $property['Description']['en'] ) ) {
            $description = $property['Description']['en'];
        }

        // Imagen destacada - delegado al método dedicado para mejor cobertura de formatos.
        $image_url = $this->extract_main_image_url( $property );

        // Precio numérico si es posible.
        $price = 0.0;
        if ( isset( $property['Price'] ) ) {
            $raw_price = is_array( $property['Price'] ) ? reset( $property['Price'] ) : $property['Price'];
            $price     = floatval( preg_replace( '/[^0-9\.]/', '', (string) $raw_price ) );
        }

        // Currency - default to EUR for Spanish properties
        $currency = 'EUR';
        if ( ! empty( $property['Currency'] ) ) {
            $currency = $property['Currency'];
        }

        // Localización combinada.
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

        // URL pública si la hubiera (no siempre viene en la API; se deja vacío si no).
        $detail_url = '';
        if ( ! empty( $property['URL'] ) && filter_var( $property['URL'], FILTER_VALIDATE_URL ) ) {
            $detail_url = $property['URL'];
        } elseif ( ! empty( $property['VirtualTourUrl'] ) && filter_var( $property['VirtualTourUrl'], FILTER_VALIDATE_URL ) ) {
            $detail_url = $property['VirtualTourUrl'];
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

        // Build a meaningful title if empty - use reference or a generic identifier
        // Note: We intentionally avoid using "Propiedad de Resales" to keep provider names hidden from users
        if ( empty( $title ) ) {
            if ( $reference ) {
                $title = $reference;
            } elseif ( $agency_ref ) {
                $title = $agency_ref;
            } else {
                $title = __( 'Propiedad inmobiliaria', 'ai360chat' );
            }
        }

        $item = array(
            'id'          => 'resales_' . $id,
            'ref'         => $reference,
            'source'      => 'resales',
            'title'       => $title,
            'description' => $description,
            'location'    => $location,
            'price'       => $price,
            'currency'    => $currency,
            'bedrooms'    => $bedrooms,
            'bathrooms'   => $bathrooms,
            'area_m2'     => $area_m2,
            'image_url'   => $image_url,
            'detail_url'  => $detail_url,
            'type'        => 'property',
            'raw'         => $property,
        );

        /**
         * Permite a otros módulos ajustar el item antes de devolverlo al sistema de inventario.
         */
        return apply_filters( 'ai360chat_resales_inventory_item', $item, $property );
    }

    /**
     * Extrae la URL de la imagen principal de una propiedad de Resales.
     *
     * Este método maneja múltiples formatos de respuesta de la API de Resales:
     *
     * 1. MainImage (string directa) - máxima prioridad
     * 2. MainPicture / MainPictureUrl (campos alternativos)
     * 3. Pictures.Picture[0].PictureURL (array indexado anidado)
     * 4. Pictures.Picture como objeto único (no array)
     * 5. Pictures.Picture como array asociativo
     * 6. Pictures como array plano con objetos que tienen URL o PictureURL
     * 7. Pictures como array plano de strings (URLs directas)
     *
     * @since 1.5.0
     *
     * @param array $property Datos de la propiedad de Resales.
     * @return string URL de la imagen principal, o cadena vacía si no se encuentra.
     */
    protected function extract_main_image_url( array $property ) {
        // Priority 1: MainImage field (direct URL string)
        if ( ! empty( $property['MainImage'] ) && is_string( $property['MainImage'] ) ) {
            return $property['MainImage'];
        }

        // Priority 2: Alternative main image field names
        if ( ! empty( $property['MainPicture'] ) && is_string( $property['MainPicture'] ) ) {
            return $property['MainPicture'];
        }

        if ( ! empty( $property['MainPictureUrl'] ) && is_string( $property['MainPictureUrl'] ) ) {
            return $property['MainPictureUrl'];
        }

        // Priority 3: Pictures.Picture nested structure
        if ( isset( $property['Pictures']['Picture'] ) ) {
            $picture_data = $property['Pictures']['Picture'];

            // Case 3a: Pictures.Picture is an indexed array [0 => [...], 1 => [...]]
            if ( isset( $picture_data[0] ) && is_array( $picture_data[0] ) ) {
                if ( ! empty( $picture_data[0]['PictureURL'] ) ) {
                    return $picture_data[0]['PictureURL'];
                }
                if ( ! empty( $picture_data[0]['URL'] ) ) {
                    return $picture_data[0]['URL'];
                }
            }
            // Case 3b: Pictures.Picture is a single object (associative array with PictureURL/URL)
            elseif ( is_array( $picture_data ) && ! isset( $picture_data[0] ) ) {
                if ( ! empty( $picture_data['PictureURL'] ) ) {
                    return $picture_data['PictureURL'];
                }
                if ( ! empty( $picture_data['URL'] ) ) {
                    return $picture_data['URL'];
                }
            }
            // Case 3c: Pictures.Picture is a string URL directly
            elseif ( is_string( $picture_data ) && ! empty( $picture_data ) ) {
                return $picture_data;
            }
        }

        // Priority 4: Pictures as flat array of objects or strings
        if ( ! empty( $property['Pictures'] ) && is_array( $property['Pictures'] ) ) {
            // Get the first element using reset() to handle both numeric and associative keys
            $first = reset( $property['Pictures'] );

            if ( is_array( $first ) ) {
                // Check for 'URL' key first
                if ( ! empty( $first['URL'] ) ) {
                    return $first['URL'];
                }
                // Fallback to 'PictureURL' key
                if ( ! empty( $first['PictureURL'] ) ) {
                    return $first['PictureURL'];
                }
            } elseif ( is_string( $first ) && ! empty( $first ) ) {
                // Pictures is an array of URL strings
                return $first;
            }
        }

        // No image found in any supported format
        return '';
    }
}
