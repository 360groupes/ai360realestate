# Especificación de Entidades - ai360realestate

## Propósito del Documento

Este documento define todas las entidades del sistema ai360realestate, sus atributos, estados, relaciones y comportamientos.

---

## Entidades Principales

### 1. PROYECTO (Project)

**Descripción:** Entidad central que agrupa usuarios, propiedades y configuración. Permite aislamiento multi-tenant.

#### Atributos

| Atributo | Tipo | Requerido | Descripción |
|----------|------|-----------|-------------|
| `project_id` | int | Sí | Identificador único |
| `name` | string | Sí | Nombre del proyecto |
| `slug` | string | Sí | Slug único (URL-friendly) |
| `description` | string | No | Descripción del proyecto |
| `status` | enum | Sí | Estado del proyecto |
| `owner_id` | int | Sí | ID del usuario propietario |
| `settings` | object | No | Configuración específica del proyecto |
| `created_at` | datetime | Sí | Fecha de creación |
| `updated_at` | datetime | Sí | Fecha de última actualización |
| `deleted_at` | datetime | No | Fecha de eliminación (soft delete) |

#### Estados (status)

```php
enum ProjectStatus: string {
    case ACTIVE = 'active';       // Proyecto activo
    case ARCHIVED = 'archived';   // Proyecto archivado (solo lectura)
    case DELETED = 'deleted';     // Proyecto marcado para eliminar
}
```

**Transiciones válidas:**
```
active → archived → active (puede reactivarse)
active → deleted (no reversible sin admin)
archived → deleted
```

#### Settings (Configuración)

```json
{
    "ai": {
        "default_provider": "360group",
        "auto_optimize": false,
        "optimization_rules": {}
    },
    "sync": {
        "default_strategy": "last_modified",
        "auto_sync_enabled": false,
        "auto_sync_interval": 3600
    },
    "notifications": {
        "emails": ["admin@example.com"],
        "slack_webhook": null,
        "notify_on_sync": true,
        "notify_on_errors": true
    },
    "branding": {
        "logo": null,
        "color": "#0073aa"
    },
    "custom_fields": {},
    "metadata": {}
}
```

#### Relaciones

```
Project (1) → (N) ProjectUser → (1) User
Project (1) → (N) Property
Project (1) → (N) Connector
Project (1) → (N) AuditLog
```

#### Métodos Principales

```php
class Project {
    // Constructor
    public function __construct(array $data);
    
    // Getters
    public function getId(): int;
    public function getName(): string;
    public function getSlug(): string;
    public function getStatus(): ProjectStatus;
    public function getOwnerId(): int;
    public function getSettings(): array;
    
    // Setters
    public function setName(string $name): void;
    public function setDescription(?string $description): void;
    public function setStatus(ProjectStatus $status): void;
    public function updateSettings(array $settings): void;
    
    // Business logic
    public function isActive(): bool;
    public function isArchived(): bool;
    public function canBeModified(): bool;
    public function archive(): void;
    public function activate(): void;
    public function softDelete(): void;
    
    // Relaciones
    public function getUsers(): array;
    public function getProperties(): array;
    public function getConnectors(): array;
    public function hasUser(int $userId): bool;
    public function addUser(int $userId, string $role): void;
    public function removeUser(int $userId): void;
    
    // Serialización
    public function toArray(): array;
    public function toJson(): string;
}
```

#### Validaciones

- `name`: 3-200 caracteres, no vacío
- `slug`: único, lowercase, guiones, sin espacios
- `status`: debe ser un valor válido del enum
- `owner_id`: debe existir en wp_users
- `settings`: debe ser JSON válido

---

### 2. USUARIO (User / ProjectUser)

**Descripción:** Relación entre usuarios de WordPress y proyectos con roles específicos.

#### Atributos

| Atributo | Tipo | Requerido | Descripción |
|----------|------|-----------|-------------|
| `project_user_id` | int | Sí | ID de la relación |
| `project_id` | int | Sí | ID del proyecto |
| `user_id` | int | Sí | ID del usuario (wp_users) |
| `role` | enum | Sí | Rol en el proyecto |
| `permissions` | object | No | Permisos específicos |
| `assigned_at` | datetime | Sí | Fecha de asignación |
| `assigned_by` | int | No | Usuario que asignó el rol |

#### Roles

```php
enum ProjectRole: string {
    case ADMIN = 'admin';       // Control total del proyecto
    case MANAGER = 'manager';   // Gestión de usuarios y config
    case EDITOR = 'editor';     // Edición de propiedades
    case VIEWER = 'viewer';     // Solo lectura
}
```

#### Capacidades por Rol

| Capacidad | Admin | Manager | Editor | Viewer |
|-----------|-------|---------|--------|--------|
| `manage_project` | ✅ | ❌ | ❌ | ❌ |
| `delete_project` | ✅ | ❌ | ❌ | ❌ |
| `manage_users` | ✅ | ✅ | ❌ | ❌ |
| `manage_connectors` | ✅ | ✅ | ❌ | ❌ |
| `create_properties` | ✅ | ✅ | ✅ | ❌ |
| `edit_properties` | ✅ | ✅ | ✅ | ❌ |
| `delete_properties` | ✅ | ✅ | ✅ | ❌ |
| `view_properties` | ✅ | ✅ | ✅ | ✅ |
| `use_ai` | ✅ | ✅ | ✅ | ❌ |
| `sync_connectors` | ✅ | ✅ | ❌ | ❌ |
| `publish_properties` | ✅ | ✅ | ✅ | ❌ |
| `view_logs` | ✅ | ✅ | ❌ | ❌ |
| `export_data` | ✅ | ✅ | ✅ | ❌ |

#### Permissions (Personalización)

```json
{
    "can_delete_properties": false,
    "can_sync_connectors": true,
    "can_use_ai": true,
    "can_publish": true,
    "max_properties": null,
    "allowed_property_types": [],
    "custom_permissions": []
}
```

#### Métodos Principales

```php
class ProjectUser {
    public function __construct(array $data);
    
    public function getId(): int;
    public function getProjectId(): int;
    public function getUserId(): int;
    public function getRole(): ProjectRole;
    public function getPermissions(): array;
    
    public function setRole(ProjectRole $role): void;
    public function updatePermissions(array $permissions): void;
    
    // Verificación de permisos
    public function can(string $capability): bool;
    public function canManageProject(): bool;
    public function canEditProperties(): bool;
    public function canUseAI(): bool;
    public function canSyncConnectors(): bool;
    
    public function toArray(): array;
}
```

---

### 3. PROPIEDAD (Property)

**Descripción:** Entidad central de contenido. Representa una propiedad inmobiliaria en formato normalizado.

#### Atributos Principales

| Atributo | Tipo | Requerido | Descripción |
|----------|------|-----------|-------------|
| `property_id` | int | Sí | Identificador único |
| `project_id` | int | Sí | Proyecto al que pertenece |
| `external_id` | string | No | ID en sistema externo |
| `source` | string | No | Origen de la propiedad |
| `status` | enum | Sí | Estado en el workflow |
| `type` | string | No | Tipo de propiedad |

#### Atributos de Contenido

| Atributo | Tipo | Requerido | Descripción |
|----------|------|-----------|-------------|
| `title` | string | No | Título de la propiedad |
| `short_description` | string | No | Descripción corta (resumen) |
| `long_description` | text | No | Descripción completa |

#### Atributos Económicos

| Atributo | Tipo | Requerido | Descripción |
|----------|------|-----------|-------------|
| `price` | decimal | No | Precio |
| `currency` | string | No | Moneda (EUR, USD, etc.) |

#### Atributos de Ubicación

| Atributo | Tipo | Requerido | Descripción |
|----------|------|-----------|-------------|
| `location_address` | string | No | Dirección completa |
| `location_city` | string | No | Ciudad |
| `location_state` | string | No | Provincia/Estado |
| `location_country` | string | No | País |
| `location_postal_code` | string | No | Código postal |
| `location_latitude` | float | No | Latitud |
| `location_longitude` | float | No | Longitud |

#### Objetos JSON

| Atributo | Tipo | Descripción |
|----------|------|-------------|
| `specifications` | object | Especificaciones técnicas |
| `features` | object | Características y amenidades |
| `images` | array | Lista de imágenes |
| `documents` | array | Lista de documentos |
| `contact_info` | object | Información de contacto |
| `seo_data` | object | Datos de SEO |
| `metadata` | object | Metadatos adicionales |
| `connector_hashes` | object | Hash por conector (sync) |

#### Atributos de Control

| Atributo | Tipo | Descripción |
|----------|------|-------------|
| `version_number` | int | Versión actual |
| `created_at` | datetime | Fecha de creación |
| `updated_at` | datetime | Última actualización |
| `published_at` | datetime | Fecha de publicación |
| `deleted_at` | datetime | Soft delete |

#### Estados (status)

```php
enum PropertyStatus: string {
    case DRAFT = 'draft';                     // Borrador
    case IMPORTED = 'imported';               // Recién importada
    case OPTIMIZED = 'optimized';             // Optimizada con IA
    case VALIDATED = 'validated';             // Validada por usuario
    case READY = 'ready';                     // Lista para publicar
    case PUBLISHED = 'published';             // Publicada
    case SYNCED = 'synced';                   // Sincronizada con destinos
    case ARCHIVED = 'archived';               // Archivada
}
```

**Workflow de Estados:**
```
draft → imported → optimized → validated → ready → published → synced
  ↓       ↓          ↓           ↓          ↓
draft   draft      draft       draft     draft
                                           ↓
                                       archived
```

**Reglas de Transición:**
- `draft`: estado inicial por defecto
- `imported`: cuando se importa desde conector
- `optimized`: después de aplicar IA
- `validated`: usuario revisa y aprueba
- `ready`: marcada como lista para publicar
- `published`: publicada en al menos un destino
- `synced`: sincronizada con todos los conectores activos
- `archived`: removida de circulación activa

#### Specifications (JSON)

```json
{
    "surface_total": 150,
    "surface_useful": 130,
    "surface_plot": 500,
    "bedrooms": 3,
    "bathrooms": 2,
    "toilets": 1,
    "floor": 2,
    "total_floors": 5,
    "year_built": 2015,
    "year_renovated": null,
    "condition": "excellent",
    "orientation": "south",
    "energy_rating": "A",
    "energy_consumption": 45.5,
    "emissions": 8.2,
    "rooms": 5,
    "storage_rooms": 1
}
```

#### Features (JSON)

```json
{
    "pool": true,
    "pool_type": "private",
    "garden": true,
    "terrace": true,
    "terrace_m2": 25,
    "balcony": false,
    "garage": true,
    "garage_spaces": 2,
    "elevator": true,
    "air_conditioning": true,
    "heating": true,
    "heating_type": "central",
    "furnished": false,
    "kitchen_equipped": true,
    "security": true,
    "security_24h": false,
    "concierge": true,
    "accessibility": false,
    "pets_allowed": true,
    "sea_view": true,
    "mountain_view": false,
    "storage_room": true
}
```

#### Images (JSON)

```json
[
    {
        "url": "https://example.com/image1.jpg",
        "title": "Exterior",
        "description": "Vista frontal",
        "order": 0,
        "is_main": true
    },
    {
        "url": "https://example.com/image2.jpg",
        "title": "Salón",
        "description": "Sala de estar amplia",
        "order": 1,
        "is_main": false
    }
]
```

#### SEO Data (JSON)

```json
{
    "meta_title": "Apartamento 3 hab en...",
    "meta_description": "Descubre este...",
    "meta_keywords": ["apartamento", "barcelona", "3 habitaciones"],
    "slug": "apartamento-3hab-barcelona-centro",
    "canonical_url": null,
    "og_title": null,
    "og_description": null,
    "og_image": null
}
```

#### Connector Hashes (JSON)

```json
{
    "woocommerce": "abc123def456789...",
    "resales": "789ghi012jkl345...",
    "idealista": null,
    "wordpress_posts": "mno678pqr901234..."
}
```

#### Métodos Principales

```php
class Property {
    public function __construct(array $data);
    
    // Getters básicos
    public function getId(): int;
    public function getProjectId(): int;
    public function getExternalId(): ?string;
    public function getSource(): ?string;
    public function getStatus(): PropertyStatus;
    public function getType(): ?string;
    
    // Getters de contenido
    public function getTitle(): ?string;
    public function getShortDescription(): ?string;
    public function getLongDescription(): ?string;
    
    // Getters económicos
    public function getPrice(): ?float;
    public function getCurrency(): string;
    public function getFormattedPrice(): string;
    
    // Getters de ubicación
    public function getAddress(): ?string;
    public function getCity(): ?string;
    public function getFullLocation(): string;
    public function getCoordinates(): ?array;
    
    // Getters de objetos JSON
    public function getSpecifications(): array;
    public function getFeatures(): array;
    public function getImages(): array;
    public function getMainImage(): ?array;
    public function getDocuments(): array;
    public function getContactInfo(): array;
    public function getSEOData(): array;
    public function getMetadata(): array;
    
    // Setters
    public function setTitle(string $title): void;
    public function setDescription(string $short, string $long): void;
    public function setPrice(float $price, string $currency = 'EUR'): void;
    public function setLocation(array $location): void;
    public function setSpecifications(array $specs): void;
    public function setFeatures(array $features): void;
    public function addImage(array $image): void;
    public function removeImage(int $index): void;
    public function setSEOData(array $seo): void;
    
    // Estados
    public function setStatus(PropertyStatus $status): void;
    public function isDraft(): bool;
    public function isImported(): bool;
    public function isOptimized(): bool;
    public function isValidated(): bool;
    public function isReady(): bool;
    public function isPublished(): bool;
    public function isSynced(): bool;
    public function canBePublished(): bool;
    
    // Sincronización
    public function getConnectorHash(string $connector): ?string;
    public function setConnectorHash(string $connector, string $hash): void;
    public function hasChangedFor(string $connector, string $currentHash): bool;
    
    // Versionado
    public function getVersionNumber(): int;
    public function incrementVersion(): void;
    
    // Serialización
    public function toArray(): array;
    public function toJson(): string;
    public function toNormalized(): array; // Formato normalizado para conectores
}
```

#### Validaciones

- `title`: máximo 500 caracteres
- `price`: debe ser >= 0 si se proporciona
- `currency`: debe ser código ISO válido
- `location_latitude`: rango válido -90 a 90
- `location_longitude`: rango válido -180 a 180
- `specifications`: JSON válido
- `features`: JSON válido
- `images`: array de objetos con estructura válida
- `seo_data`: JSON válido

---

### 4. CONECTOR (Connector)

**Descripción:** Configuración de conexión a sistemas externos para sincronización bidireccional.

#### Atributos

| Atributo | Tipo | Requerido | Descripción |
|----------|------|-----------|-------------|
| `connector_id` | int | Sí | ID único |
| `project_id` | int | Sí | Proyecto propietario |
| `name` | string | Sí | Nombre descriptivo |
| `type` | enum | Sí | Tipo de conector |
| `status` | enum | Sí | Estado del conector |
| `config` | object | Sí | Configuración general |
| `credentials` | object | No | Credenciales (encriptadas) |
| `sync_settings` | object | Sí | Config de sincronización |
| `last_sync_at` | datetime | No | Última sincronización |
| `last_sync_status` | string | No | Estado de última sync |
| `last_sync_message` | string | No | Mensaje de última sync |
| `created_at` | datetime | Sí | Fecha de creación |
| `updated_at` | datetime | Sí | Última actualización |

#### Tipos de Conectores

```php
enum ConnectorType: string {
    case WOOCOMMERCE = 'woocommerce';
    case WORDPRESS_POSTS = 'wordpress_posts';
    case RESALES = 'resales';
    case IDEALISTA = 'idealista';
    case FOTOCASA = 'fotocasa';
    case CUSTOM_API = 'custom_api';
    case CSV = 'csv';
}
```

#### Estados

```php
enum ConnectorStatus: string {
    case INACTIVE = 'inactive';   // No sincroniza
    case ACTIVE = 'active';       // Sincroniza activamente
    case ERROR = 'error';         // Error en última sync
    case PAUSED = 'paused';       // Pausado manualmente
}
```

#### Config (JSON)

```json
{
    "api_url": "https://api.example.com",
    "api_version": "v6",
    "timeout": 30,
    "retry_attempts": 3,
    "custom_mappings": {
        "price": "precioVenta",
        "title": "titulo"
    },
    "filters": {
        "property_type": ["apartment", "house"],
        "min_price": 100000,
        "location": "Barcelona"
    }
}
```

#### Sync Settings (JSON)

```json
{
    "direction": "bidirectional",
    "conflict_strategy": "last_modified",
    "auto_sync": false,
    "sync_interval": 3600,
    "sync_on_save": false,
    "create_enabled": true,
    "update_enabled": true,
    "delete_enabled": false,
    "batch_size": 50,
    "rate_limit": 100
}
```

#### Métodos Principales

```php
class Connector {
    public function __construct(array $data);
    
    public function getId(): int;
    public function getProjectId(): int;
    public function getName(): string;
    public function getType(): ConnectorType;
    public function getStatus(): ConnectorStatus;
    public function getConfig(): array;
    public function getSyncSettings(): array;
    
    public function setStatus(ConnectorStatus $status): void;
    public function updateConfig(array $config): void;
    public function updateSyncSettings(array $settings): void;
    
    public function isActive(): bool;
    public function canSync(): bool;
    public function isBidirectional(): bool;
    public function supportsOperation(string $operation): bool;
    
    public function getLastSyncAt(): ?DateTime;
    public function getLastSyncStatus(): ?string;
    public function updateLastSync(string $status, string $message): void;
    
    public function toArray(): array;
}
```

---

### 5. VERSION (PropertyVersion)

**Descripción:** Snapshot de una propiedad en un momento determinado para auditoría y rollback.

#### Atributos

| Atributo | Tipo | Descripción |
|----------|------|-------------|
| `version_id` | int | ID de la versión |
| `property_id` | int | ID de la propiedad |
| `version_number` | int | Número de versión |
| `data` | object | Snapshot completo (JSON) |
| `changed_by` | string | Quién hizo el cambio |
| `change_source` | enum | Origen del cambio |
| `change_reason` | string | Razón del cambio |
| `diff_summary` | object | Resumen de diferencias |
| `created_at` | datetime | Fecha de creación |

#### Change Source

```php
enum ChangeSource: string {
    case USER_EDIT = 'user_edit';
    case AI_OPTIMIZATION = 'ai_optimization';
    case SYNC_IMPORT = 'sync_import';
    case SYNC_UPDATE = 'sync_update';
    case BULK_OPERATION = 'bulk_operation';
    case API_UPDATE = 'api_update';
}
```

#### Métodos Principales

```php
class PropertyVersion {
    public function getId(): int;
    public function getPropertyId(): int;
    public function getVersionNumber(): int;
    public function getData(): array;
    public function getChangedBy(): string;
    public function getChangeSource(): ChangeSource;
    public function getDiffSummary(): array;
    
    public function restore(): Property;
    public function compare(PropertyVersion $other): array;
    
    public function toArray(): array;
}
```

---

### 6. AI TASK (AITask)

**Descripción:** Tarea de optimización con IA en cola o completada.

#### Atributos

| Atributo | Tipo | Descripción |
|----------|------|-------------|
| `task_id` | int | ID de la tarea |
| `property_id` | int | Propiedad a optimizar |
| `task_type` | enum | Tipo de tarea |
| `provider` | string | Provider de IA |
| `status` | enum | Estado de la tarea |
| `priority` | int | Prioridad (1-10) |
| `input_data` | object | Datos de entrada |
| `output_data` | object | Resultado |
| `tokens_used` | int | Tokens consumidos |
| `cost` | decimal | Costo en USD |
| `error_message` | string | Mensaje de error |
| `started_at` | datetime | Inicio |
| `completed_at` | datetime | Fin |
| `created_at` | datetime | Creación |

#### Task Types

```php
enum AITaskType: string {
    case REWRITE_TITLE = 'rewrite_title';
    case GENERATE_SHORT_DESC = 'generate_short_desc';
    case GENERATE_LONG_DESC = 'generate_long_desc';
    case OPTIMIZE_SEO = 'optimize_seo';
    case GENERATE_BY_CHANNEL = 'generate_by_channel';
    case TRANSLATE = 'translate';
}
```

#### Estados

```php
enum AITaskStatus: string {
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
```

---

## Resumen de Relaciones

```
┌──────────────┐
│   Project    │
└──────┬───────┘
       │
       ├──────┐
       │      │
       ▼      ▼
┌──────────┐ ┌───────────┐
│ Property │ │Connector  │
└────┬─────┘ └───────────┘
     │
     ├──────┐
     │      │
     ▼      ▼
┌─────────┐┌────────┐
│ Version ││AITask  │
└─────────┘└────────┘

┌──────────────┐
│ ProjectUser  │ (relación User ↔ Project)
└──────────────┘
```

---

**Documento creado para:** PR-00 - Análisis de Arquitectura y Documentación Técnica Fundacional  
**Última actualización:** 2025-12-18  
**Versión:** 1.0
