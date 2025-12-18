# Especificación de Conectores - ai360realestate

## Propósito del Documento

Este documento define la especificación completa del sistema de conectores para sincronización bidireccional con sistemas externos.

---

## Arquitectura de Conectores

### Principios de Diseño

1. **Bidireccionalidad**: Todos los conectores soportan lectura Y escritura
2. **Normalización**: Datos convertidos a formato interno único
3. **Prevención de bucles**: Hash por conector previene sincronización infinita
4. **Resolución de conflictos**: 4 estrategias configurables
5. **Extensibilidad**: Fácil agregar nuevos conectores

---

## Interface Base

### ConnectorInterface

```php
namespace AI360RealEstate\Connectors;

interface ConnectorInterface {
    /**
     * Conectar con el sistema externo
     * 
     * @param array $config Configuración del conector
     * @return bool true si conexión exitosa
     * @throws ConnectionException si falla la conexión
     */
    public function connect(array $config): bool;
    
    /**
     * Desconectar del sistema externo
     * 
     * @return void
     */
    public function disconnect(): void;
    
    /**
     * Verificar si está conectado
     * 
     * @return bool
     */
    public function isConnected(): bool;
    
    /**
     * Probar la conexión
     * 
     * @return array Estado de la conexión
     */
    public function testConnection(): array;
    
    /**
     * Sincronización completa
     * 
     * @param string $direction 'import', 'export', 'bidirectional'
     * @param array $options Opciones de sincronización
     * @return SyncResult
     */
    public function sync(string $direction, array $options = []): SyncResult;
    
    /**
     * Leer una propiedad específica del sistema externo
     * 
     * @param string $externalId ID en el sistema externo
     * @return Property|null
     */
    public function read(string $externalId): ?Property;
    
    /**
     * Leer múltiples propiedades
     * 
     * @param array $filters Filtros de búsqueda
     * @param int $limit Límite de resultados
     * @param int $offset Offset para paginación
     * @return array<Property>
     */
    public function readMany(array $filters = [], int $limit = 50, int $offset = 0): array;
    
    /**
     * Crear propiedad en sistema externo
     * 
     * @param Property $property Propiedad a crear
     * @return string ID externo de la propiedad creada
     * @throws CreateException si falla la creación
     */
    public function create(Property $property): string;
    
    /**
     * Actualizar propiedad en sistema externo
     * 
     * @param string $externalId ID externo
     * @param Property $property Datos actualizados
     * @return bool true si actualización exitosa
     * @throws UpdateException si falla la actualización
     */
    public function update(string $externalId, Property $property): bool;
    
    /**
     * Eliminar propiedad del sistema externo
     * 
     * @param string $externalId ID externo
     * @return bool true si eliminación exitosa
     * @throws DeleteException si falla la eliminación
     */
    public function delete(string $externalId): bool;
    
    /**
     * Obtener metadatos del conector
     * 
     * @return array
     */
    public function getMetadata(): array;
    
    /**
     * Validar configuración del conector
     * 
     * @param array $config
     * @return array Errores de validación (vacío si válido)
     */
    public function validateConfig(array $config): array;
}
```

---

## Clase Base Abstracta

### AbstractConnector

```php
namespace AI360RealEstate\Connectors;

abstract class AbstractConnector implements ConnectorInterface {
    protected int $connectorId;
    protected int $projectId;
    protected array $config;
    protected array $syncSettings;
    protected bool $connected = false;
    protected Logger $logger;
    protected MapperInterface $mapper;
    
    public function __construct(int $connectorId, array $config, array $syncSettings) {
        $this->connectorId = $connectorId;
        $this->config = $config;
        $this->syncSettings = $syncSettings;
        $this->logger = new Logger("Connector-{$connectorId}");
        $this->mapper = $this->createMapper();
    }
    
    abstract protected function createMapper(): MapperInterface;
    abstract protected function doConnect(): bool;
    abstract protected function doDisconnect(): void;
    abstract protected function doRead(string $externalId): ?array;
    abstract protected function doReadMany(array $filters, int $limit, int $offset): array;
    abstract protected function doCreate(array $data): string;
    abstract protected function doUpdate(string $externalId, array $data): bool;
    abstract protected function doDelete(string $externalId): bool;
    
    public function connect(array $config): bool {
        try {
            $this->config = array_merge($this->config, $config);
            $this->connected = $this->doConnect();
            $this->logger->info("Connected successfully");
            return $this->connected;
        } catch (\Exception $e) {
            $this->logger->error("Connection failed: " . $e->getMessage());
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }
    
    public function disconnect(): void {
        if ($this->connected) {
            $this->doDisconnect();
            $this->connected = false;
            $this->logger->info("Disconnected");
        }
    }
    
    public function isConnected(): bool {
        return $this->connected;
    }
    
    public function read(string $externalId): ?Property {
        $this->ensureConnected();
        
        $rawData = $this->doRead($externalId);
        if ($rawData === null) {
            return null;
        }
        
        return $this->mapper->toProperty($rawData);
    }
    
    public function readMany(array $filters = [], int $limit = 50, int $offset = 0): array {
        $this->ensureConnected();
        
        $rawDataList = $this->doReadMany($filters, $limit, $offset);
        
        return array_map(
            fn($rawData) => $this->mapper->toProperty($rawData),
            $rawDataList
        );
    }
    
    public function create(Property $property): string {
        $this->ensureConnected();
        
        $rawData = $this->mapper->fromProperty($property);
        $externalId = $this->doCreate($rawData);
        
        $this->logger->info("Created property with external ID: {$externalId}");
        
        return $externalId;
    }
    
    public function update(string $externalId, Property $property): bool {
        $this->ensureConnected();
        
        $rawData = $this->mapper->fromProperty($property);
        $result = $this->doUpdate($externalId, $rawData);
        
        $this->logger->info("Updated property {$externalId}");
        
        return $result;
    }
    
    public function delete(string $externalId): bool {
        $this->ensureConnected();
        
        $result = $this->doDelete($externalId);
        
        $this->logger->info("Deleted property {$externalId}");
        
        return $result;
    }
    
    public function sync(string $direction, array $options = []): SyncResult {
        $engine = new SyncEngine($this, $this->logger);
        return $engine->execute($direction, $options);
    }
    
    protected function ensureConnected(): void {
        if (!$this->connected) {
            throw new NotConnectedException("Connector is not connected");
        }
    }
    
    public function testConnection(): array {
        try {
            $this->connect($this->config);
            $metadata = $this->getMetadata();
            $this->disconnect();
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'metadata' => $metadata
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => get_class($e)
            ];
        }
    }
}
```

---

## Sistema de Mapeo

### MapperInterface

```php
namespace AI360RealEstate\Connectors\Mappers;

interface MapperInterface {
    /**
     * Convertir datos externos a Property normalizada
     * 
     * @param array $externalData Datos del sistema externo
     * @return Property
     */
    public function toProperty(array $externalData): Property;
    
    /**
     * Convertir Property normalizada a formato externo
     * 
     * @param Property $property
     * @return array Datos en formato del sistema externo
     */
    public function fromProperty(Property $property): array;
    
    /**
     * Obtener mapeo de campos
     * 
     * @return array
     */
    public function getFieldMapping(): array;
}
```

---

## Sincronización

### SyncEngine

El motor de sincronización coordina las operaciones entre el sistema local y los conectores.

```php
namespace AI360RealEstate\Sync;

class SyncEngine {
    private ConnectorInterface $connector;
    private Logger $logger;
    private ChangeDetector $changeDetector;
    private ConflictResolver $conflictResolver;
    private LoopPrevention $loopPrevention;
    
    public function execute(string $direction, array $options): SyncResult {
        $result = new SyncResult();
        
        try {
            switch ($direction) {
                case 'import':
                    $this->executeImport($result, $options);
                    break;
                    
                case 'export':
                    $this->executeExport($result, $options);
                    break;
                    
                case 'bidirectional':
                    $this->executeBidirectional($result, $options);
                    break;
                    
                default:
                    throw new \InvalidArgumentException("Invalid direction: {$direction}");
            }
        } catch (\Exception $e) {
            $result->addError($e->getMessage());
            $this->logger->error("Sync failed: " . $e->getMessage());
        }
        
        return $result;
    }
    
    private function executeImport(SyncResult $result, array $options): void {
        // Importar propiedades desde sistema externo
        $filters = $options['filters'] ?? [];
        $limit = $options['limit'] ?? 50;
        $offset = $options['offset'] ?? 0;
        
        $externalProperties = $this->connector->readMany($filters, $limit, $offset);
        
        foreach ($externalProperties as $externalProperty) {
            try {
                $this->importProperty($externalProperty, $result);
            } catch (\Exception $e) {
                $result->addError("Failed to import property: " . $e->getMessage());
            }
        }
    }
    
    private function executeExport(SyncResult $result, array $options): void {
        // Exportar propiedades locales a sistema externo
        $localProperties = $this->getLocalProperties($options);
        
        foreach ($localProperties as $localProperty) {
            try {
                $this->exportProperty($localProperty, $result);
            } catch (\Exception $e) {
                $result->addError("Failed to export property: " . $e->getMessage());
            }
        }
    }
    
    private function executeBidirectional(SyncResult $result, array $options): void {
        // Sincronización bidireccional con detección de conflictos
        // 1. Importar cambios remotos
        // 2. Exportar cambios locales
        // 3. Resolver conflictos
    }
    
    private function importProperty(Property $externalProperty, SyncResult $result): void {
        // Buscar si ya existe localmente
        $localProperty = $this->findLocalProperty($externalProperty);
        
        if ($localProperty === null) {
            // Crear nueva
            $this->createLocalProperty($externalProperty);
            $result->incrementCreated();
        } else {
            // Verificar si hay cambios
            if ($this->changeDetector->hasChanged($externalProperty, $localProperty)) {
                // Verificar conflictos
                if ($this->hasConflict($externalProperty, $localProperty)) {
                    $resolved = $this->conflictResolver->resolve(
                        $externalProperty,
                        $localProperty,
                        $this->getSyncSettings()
                    );
                    
                    if ($resolved) {
                        $this->updateLocalProperty($localProperty, $externalProperty);
                        $result->incrementUpdated();
                    } else {
                        $result->incrementConflicts();
                    }
                } else {
                    $this->updateLocalProperty($localProperty, $externalProperty);
                    $result->incrementUpdated();
                }
            } else {
                $result->incrementSkipped();
            }
        }
    }
    
    private function exportProperty(Property $localProperty, SyncResult $result): void {
        // Similar a importProperty pero en dirección inversa
    }
}
```

---

## Detección de Cambios

### ChangeDetector

```php
namespace AI360RealEstate\Sync;

class ChangeDetector {
    /**
     * Verificar si una propiedad ha cambiado comparando hashes
     */
    public function hasChanged(Property $property, string $connectorType): bool {
        $currentHash = $this->calculateHash($property);
        $storedHash = $property->getConnectorHash($connectorType);
        
        return $currentHash !== $storedHash;
    }
    
    /**
     * Calcular hash de una propiedad
     */
    public function calculateHash(Property $property): string {
        $data = $property->toNormalized();
        
        // Excluir campos que no deben afectar el hash
        unset($data['updated_at']);
        unset($data['version_number']);
        unset($data['connector_hashes']);
        
        return hash('sha256', json_encode($data, JSON_SORT_KEYS));
    }
    
    /**
     * Obtener campos que cambiaron
     */
    public function getChangedFields(Property $before, Property $after): array {
        $beforeData = $before->toArray();
        $afterData = $after->toArray();
        
        $changes = [];
        foreach ($afterData as $key => $value) {
            if (!isset($beforeData[$key]) || $beforeData[$key] !== $value) {
                $changes[$key] = [
                    'before' => $beforeData[$key] ?? null,
                    'after' => $value
                ];
            }
        }
        
        return $changes;
    }
}
```

---

## Resolución de Conflictos

### ConflictResolver

```php
namespace AI360RealEstate\Sync;

enum ConflictStrategy: string {
    case LOCAL_WINS = 'local_wins';
    case REMOTE_WINS = 'remote_wins';
    case LAST_MODIFIED = 'last_modified';
    case MANUAL_REVIEW = 'manual_review';
}

class ConflictResolver {
    /**
     * Resolver conflicto entre versión local y remota
     */
    public function resolve(
        Property $remote,
        Property $local,
        array $syncSettings
    ): ?Property {
        $strategy = ConflictStrategy::from($syncSettings['conflict_strategy']);
        
        switch ($strategy) {
            case ConflictStrategy::LOCAL_WINS:
                return $local;
                
            case ConflictStrategy::REMOTE_WINS:
                return $remote;
                
            case ConflictStrategy::LAST_MODIFIED:
                return $this->resolveByLastModified($remote, $local);
                
            case ConflictStrategy::MANUAL_REVIEW:
                $this->flagForManualReview($remote, $local);
                return null;
        }
    }
    
    private function resolveByLastModified(Property $remote, Property $local): Property {
        $remoteTime = $remote->getUpdatedAt();
        $localTime = $local->getUpdatedAt();
        
        return $remoteTime > $localTime ? $remote : $local;
    }
    
    private function flagForManualReview(Property $remote, Property $local): void {
        // Crear registro para revisión manual
        ConflictLog::create([
            'property_id' => $local->getId(),
            'remote_data' => $remote->toArray(),
            'local_data' => $local->toArray(),
            'status' => 'pending_review'
        ]);
    }
}
```

---

## Prevención de Bucles

### LoopPrevention

```php
namespace AI360RealEstate\Sync;

class LoopPrevention {
    private const MAX_SYNC_FREQUENCY = 60; // segundos
    
    /**
     * Verificar si es seguro sincronizar
     */
    public function canSync(int $connectorId, int $propertyId): bool {
        $lastSync = $this->getLastSyncTime($connectorId, $propertyId);
        
        if ($lastSync === null) {
            return true;
        }
        
        $elapsed = time() - $lastSync;
        return $elapsed >= self::MAX_SYNC_FREQUENCY;
    }
    
    /**
     * Registrar sincronización
     */
    public function recordSync(int $connectorId, int $propertyId): void {
        SyncLog::create([
            'connector_id' => $connectorId,
            'property_id' => $propertyId,
            'synced_at' => current_time('mysql'),
            'hash' => $this->getCurrentHash($propertyId)
        ]);
    }
    
    /**
     * Detectar bucle infinito
     */
    public function detectLoop(int $connectorId, int $propertyId): bool {
        $recentSyncs = $this->getRecentSyncs($connectorId, $propertyId, 5);
        
        if (count($recentSyncs) < 5) {
            return false;
        }
        
        // Si hay 5+ syncs en menos de 5 minutos, es un bucle
        $timespan = end($recentSyncs)->timestamp - $recentSyncs[0]->timestamp;
        return $timespan < 300; // 5 minutos
    }
}
```

---

## Conectores Específicos

### 1. WooCommerce Connector

```php
namespace AI360RealEstate\Connectors;

class WooCommerceConnector extends AbstractConnector {
    private \WC_REST_Products_Controller $productsController;
    
    protected function createMapper(): MapperInterface {
        return new WooCommerceMapper();
    }
    
    protected function doConnect(): bool {
        // Verificar que WooCommerce esté instalado y activo
        if (!class_exists('WooCommerce')) {
            throw new ConnectionException("WooCommerce is not installed");
        }
        
        return true;
    }
    
    protected function doRead(string $externalId): ?array {
        $product = wc_get_product($externalId);
        
        if (!$product) {
            return null;
        }
        
        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'images' => $this->getProductImages($product),
            'meta_data' => $product->get_meta_data(),
            // ... más campos
        ];
    }
    
    protected function doReadMany(array $filters, int $limit, int $offset): array {
        $args = [
            'status' => 'publish',
            'limit' => $limit,
            'offset' => $offset,
        ];
        
        // Aplicar filtros
        if (isset($filters['category'])) {
            $args['category'] = $filters['category'];
        }
        
        $products = wc_get_products($args);
        
        return array_map(function($product) {
            return $this->doRead($product->get_id());
        }, $products);
    }
    
    protected function doCreate(array $data): string {
        $product = new \WC_Product();
        
        $product->set_name($data['name']);
        $product->set_description($data['description']);
        $product->set_short_description($data['short_description']);
        $product->set_regular_price($data['price']);
        
        // Meta data personalizada
        foreach ($data['meta_data'] as $key => $value) {
            $product->update_meta_data($key, $value);
        }
        
        $product->save();
        
        // Agregar imágenes
        if (isset($data['images'])) {
            $this->setProductImages($product, $data['images']);
        }
        
        return (string) $product->get_id();
    }
    
    protected function doUpdate(string $externalId, array $data): bool {
        $product = wc_get_product($externalId);
        
        if (!$product) {
            return false;
        }
        
        $product->set_name($data['name']);
        $product->set_description($data['description']);
        $product->set_short_description($data['short_description']);
        $product->set_regular_price($data['price']);
        
        $product->save();
        
        return true;
    }
    
    protected function doDelete(string $externalId): bool {
        $product = wc_get_product($externalId);
        
        if (!$product) {
            return false;
        }
        
        return $product->delete(true); // Forzar eliminación permanente
    }
    
    public function getMetadata(): array {
        return [
            'type' => 'woocommerce',
            'version' => WC()->version,
            'supports' => [
                'create' => true,
                'read' => true,
                'update' => true,
                'delete' => true,
                'images' => true,
                'categories' => true,
                'meta_data' => true
            ]
        ];
    }
}
```

### 2. WordPress Posts Connector

```php
namespace AI360RealEstate\Connectors;

class WordPressConnector extends AbstractConnector {
    private string $postType = 'ai360re_property'; // Custom Post Type
    
    protected function createMapper(): MapperInterface {
        return new WordPressMapper();
    }
    
    protected function doConnect(): bool {
        // Registrar Custom Post Type si no existe
        $this->registerCustomPostType();
        return true;
    }
    
    protected function doRead(string $externalId): ?array {
        $post = get_post($externalId);
        
        if (!$post || $post->post_type !== $this->postType) {
            return null;
        }
        
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'meta' => get_post_meta($post->ID),
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'full'),
            'images' => $this->getPostImages($post->ID),
        ];
    }
    
    protected function doCreate(array $data): string {
        $post_id = wp_insert_post([
            'post_title' => $data['title'],
            'post_content' => $data['content'],
            'post_excerpt' => $data['excerpt'],
            'post_status' => 'publish',
            'post_type' => $this->postType,
        ]);
        
        if (is_wp_error($post_id)) {
            throw new CreateException($post_id->get_error_message());
        }
        
        // Guardar meta data
        foreach ($data['meta'] as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        
        // Establecer featured image
        if (isset($data['featured_image'])) {
            $this->setFeaturedImage($post_id, $data['featured_image']);
        }
        
        return (string) $post_id;
    }
    
    private function registerCustomPostType(): void {
        if (post_type_exists($this->postType)) {
            return;
        }
        
        register_post_type($this->postType, [
            'labels' => [
                'name' => __('Properties', 'ai360realestate'),
                'singular_name' => __('Property', 'ai360realestate'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
            'show_in_rest' => true,
        ]);
    }
}
```

### 3. Resales V6 Connector

```php
namespace AI360RealEstate\Connectors;

class ResalesConnector extends AbstractConnector {
    private ResalesClient $client;
    
    protected function createMapper(): MapperInterface {
        return new ResalesMapper();
    }
    
    protected function doConnect(): bool {
        $this->client = new ResalesClient(
            $this->config['api_url'],
            $this->config['api_key']
        );
        
        return $this->client->authenticate();
    }
    
    protected function doRead(string $externalId): ?array {
        return $this->client->getProperty($externalId);
    }
    
    protected function doReadMany(array $filters, int $limit, int $offset): array {
        return $this->client->searchProperties($filters, $limit, $offset);
    }
    
    protected function doCreate(array $data): string {
        return $this->client->createProperty($data);
    }
    
    protected function doUpdate(string $externalId, array $data): bool {
        return $this->client->updateProperty($externalId, $data);
    }
    
    protected function doDelete(string $externalId): bool {
        return $this->client->deleteProperty($externalId);
    }
}
```

---

## Resultado de Sincronización

### SyncResult

```php
namespace AI360RealEstate\Sync;

class SyncResult {
    private int $created = 0;
    private int $updated = 0;
    private int $deleted = 0;
    private int $skipped = 0;
    private int $conflicts = 0;
    private array $errors = [];
    private float $startTime;
    private float $endTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
    }
    
    public function finish(): void {
        $this->endTime = microtime(true);
    }
    
    public function incrementCreated(): void {
        $this->created++;
    }
    
    public function incrementUpdated(): void {
        $this->updated++;
    }
    
    public function incrementDeleted(): void {
        $this->deleted++;
    }
    
    public function incrementSkipped(): void {
        $this->skipped++;
    }
    
    public function incrementConflicts(): void {
        $this->conflicts++;
    }
    
    public function addError(string $error): void {
        $this->errors[] = $error;
    }
    
    public function getDuration(): float {
        return $this->endTime - $this->startTime;
    }
    
    public function isSuccessful(): bool {
        return empty($this->errors) && $this->conflicts === 0;
    }
    
    public function toArray(): array {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'deleted' => $this->deleted,
            'skipped' => $this->skipped,
            'conflicts' => $this->conflicts,
            'errors' => $this->errors,
            'duration' => $this->getDuration(),
            'successful' => $this->isSuccessful(),
        ];
    }
}
```

---

## Conectores Planificados

### Fase 1 (Core)
- ✅ WooCommerce
- ✅ WordPress Posts

### Fase 2 (Externos)
- ✅ Resales V6
- ⏳ Idealista (solo lectura inicialmente)
- ⏳ Fotocasa (solo lectura inicialmente)

### Fase 3 (Adicionales)
- ⏳ API REST Genérica
- ⏳ CSV Import/Export
- ⏳ Conectores personalizados (extensión)

---

**Documento creado para:** PR-00 - Análisis de Arquitectura y Documentación Técnica Fundacional  
**Última actualización:** 2025-12-18  
**Versión:** 1.0
