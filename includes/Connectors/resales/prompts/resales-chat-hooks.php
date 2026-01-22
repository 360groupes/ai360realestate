<?php
/**
 * Hooks para integrar Resales con el flujo de chat de AI360Chat.
 *
 * Esta implementación V1 asume que el núcleo dispara un filtro
 * 'ai360chat_inventory_query' cuando el modelo necesita obtener
 * items de inventario para enriquecer una respuesta.
 *
 * Si tu núcleo usa otros filtros/acciones, puedes adaptar esta lógica.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ejemplo: inyectar resultados de Resales como items de inventario para el chat.
 *
 * @param array $items   Items de inventario ya calculados.
 * @param array $context Contexto de la consulta (puede incluir 'query', 'location', 'min_price', etc.).
 *
 * @return array
 */
function ai360chat_resales_hook_inventory_query( $items, $context ) {
    if ( ! is_array( $items ) ) {
        $items = array();
    }

    // Solo intentamos buscar si el provider está disponible.
    $provider = new AI360Chat_Resales_Inventory_Provider();

    if ( ! $provider->is_available() ) {
        return $items;
    }

    $criteria = array(
        'query'     => isset( $context['query'] ) ? $context['query'] : '',
        'location'  => isset( $context['location'] ) ? $context['location'] : '',
        'min_price' => isset( $context['min_price'] ) ? $context['min_price'] : 0,
        'max_price' => isset( $context['max_price'] ) ? $context['max_price'] : 0,
        'min_beds'  => isset( $context['min_beds'] ) ? $context['min_beds'] : 0,
        'min_baths' => isset( $context['min_baths'] ) ? $context['min_baths'] : 0,
        'type_id'   => isset( $context['type_id'] ) ? $context['type_id'] : '',
    );

    $resales_items = $provider->search( $criteria, 10 );

    if ( ! empty( $resales_items ) && is_array( $resales_items ) ) {
        // Fusionamos los items de Resales con los existentes, manteniendo los previos.
        $items = array_merge( $items, $resales_items );
    }

    return $items;
}
add_filter( 'ai360chat_inventory_query', 'ai360chat_resales_hook_inventory_query', 20, 2 );
