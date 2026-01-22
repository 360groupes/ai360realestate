<?php
/**
 * Integración de Resales con el sistema de Fichas de AI360Chat.
 *
 * Esta clase define helpers para crear Fichas a partir de referencias de Resales
 * y metadatos asociados a dichas Fichas.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI360Chat_Resales_Fichas {

    const META_SOURCE          = '_ai360chat_resales_source';
    const META_REFERENCE       = '_ai360chat_resales_reference';
    const META_RAW_DATA        = '_ai360chat_resales_raw';
    const META_LAST_SYNC       = '_ai360chat_resales_last_sync';
    const SOURCE_KEY           = 'resales';
    const SOURCE_HUMAN_READABLE = 'Resales-Online';

    /**
     * Devuelve el array de metadatos que se almacenarán en una Ficha generada desde Resales.
     *
     * @param array $property
     *
     * @return array
     */
    public static function build_ficha_meta_from_property( array $property ) {
        $meta = array();

        $meta[ self::META_SOURCE ]    = self::SOURCE_KEY;
        $meta[ self::META_REFERENCE ] = isset( $property['Reference'] ) ? $property['Reference'] : '';
        $meta[ self::META_RAW_DATA ]  = wp_json_encode( $property );
        $meta[ self::META_LAST_SYNC ] = current_time( 'mysql' );

        return $meta;
    }

    /**
     * Crea (o actualiza) una Ficha a partir de los datos de una propiedad de Resales.
     *
     * IMPORTANTE: Esta función no registra CPTs ni asume una estructura concreta;
     * simplemente devuelve un array descriptivo que el sistema de Fichas puede usar
     * para crear su propia entrada.
     *
     * @param array $property
     *
     * @return array Array con claves estándar esperadas por el sistema de Fichas.
     */
    public static function build_ficha_payload_from_property( array $property ) {
        $reference = isset( $property['Reference'] ) ? $property['Reference'] : '';
        $title     = '';

        if ( ! empty( $property['PropertyType']['Type'] ) ) {
            $title .= $property['PropertyType']['Type'];
        }

        if ( ! empty( $property['Location'] ) ) {
            $title .= ( $title ? ' - ' : '' ) . $property['Location'];
        }

        if ( ! empty( $reference ) ) {
            $title .= ( $title ? ' · ' : '' ) . $reference;
        }

        $description = '';
        if ( ! empty( $property['Description']['es'] ) ) {
            $description = $property['Description']['es'];
        } elseif ( ! empty( $property['Description']['en'] ) ) {
            $description = $property['Description']['en'];
        }

        $image_url = '';
        if ( ! empty( $property['Pictures'] ) && is_array( $property['Pictures'] ) ) {
            $first = reset( $property['Pictures'] );
            if ( is_array( $first ) && ! empty( $first['URL'] ) ) {
                $image_url = $first['URL'];
            }
        }

        $price = '';
        if ( isset( $property['Price'] ) ) {
            $price = (string) $property['Price'];
        }

        return array(
            'title'       => $title ? $title : $reference,
            'content'     => $description,
            'image_url'   => $image_url,
            'price_label' => $price,
            'meta'        => self::build_ficha_meta_from_property( $property ),
        );
    }
}
