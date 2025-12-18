# Esquema de Base de Datos - ai360realestate

## Propósito del Documento

Este documento define el esquema completo de la base de datos para el plugin ai360realestate. Todas las tablas usan el prefijo `{wpdb->prefix}ai360re_`.

---

## Convenciones Generales

### Nomenclatura
- **Prefijo:** `{wpdb->prefix}ai360re_` (ejemplo: `wp_ai360re_projects`)
- **Nombres:** snake_case en minúsculas
- **IDs:** `{tabla}_id` (ejemplo: `project_id`)
- **Timestamps:** `created_at`, `updated_at`, `deleted_at`

### Tipos de Datos
- **IDs:** `BIGINT(20) UNSIGNED`
- **Timestamps:** `DATETIME` (formato MySQL)
- **Booleans:** `TINYINT(1)`
- **JSON:** `LONGTEXT` (validado en PHP)
- **Enum:** `VARCHAR` con validación en PHP

### Charset
- `utf8mb4` para soporte completo de Unicode
- `utf8mb4_unicode_ci` collation

---

## Tabla: ai360re_projects

**Propósito:** Almacena proyectos (entidad central del sistema)

```sql
CREATE TABLE {prefix}ai360re_projects (
    project_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    description TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    owner_id BIGINT(20) UNSIGNED NOT NULL,
    settings LONGTEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (project_id),
    UNIQUE KEY slug (slug),
    KEY status (status),
    KEY owner_id (owner_id),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campos

| Campo | Tipo | Descripción | Notas |
|-------|------|-------------|-------|
| `project_id` | BIGINT(20) UNSIGNED | ID único del proyecto | PK, AUTO_INCREMENT |
| `name` | VARCHAR(200) | Nombre del proyecto | Requerido, no único |
| `slug` | VARCHAR(200) | Slug único del proyecto | Requerido, UNIQUE |
| `description` | TEXT | Descripción del proyecto | Opcional |
| `status` | VARCHAR(50) | Estado del proyecto | active, archived, deleted |
| `owner_id` | BIGINT(20) UNSIGNED | ID del usuario propietario | FK a wp_users |
| `settings` | LONGTEXT | Configuración en JSON | JSON |
| `created_at` | DATETIME | Fecha de creación | Requerido |
| `updated_at` | DATETIME | Fecha de última actualización | Requerido |
| `deleted_at` | DATETIME | Fecha de eliminación (soft delete) | NULL si no eliminado |

### Valores de `status`
- `active` - Proyecto activo
- `archived` - Proyecto archivado (solo lectura)
- `deleted` - Proyecto marcado para eliminar (soft delete)

### Ejemplo de `settings` (JSON)
```json
{
    "default_ai_provider": "360group",
    "default_sync_strategy": "last_modified",
    "auto_sync_enabled": true,
    "auto_sync_interval": 3600,
    "notification_emails": ["admin@example.com"],
    "custom_fields": {},
    "metadata": {}
}
```

---

## Tabla: ai360re_project_users

**Propósito:** Relación muchos-a-muchos entre proyectos y usuarios con roles

```sql
CREATE TABLE {prefix}ai360re_project_users (
    project_user_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    role VARCHAR(50) NOT NULL,
    permissions LONGTEXT,
    assigned_at DATETIME NOT NULL,
    assigned_by BIGINT(20) UNSIGNED,
    PRIMARY KEY (project_user_id),
    UNIQUE KEY project_user (project_id, user_id),
    KEY project_id (project_id),
    KEY user_id (user_id),
    KEY role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campos

| Campo | Tipo | Descripción | Notas |
|-------|------|-------------|-------|
| `project_user_id` | BIGINT(20) UNSIGNED | ID único de la relación | PK, AUTO_INCREMENT |
| `project_id` | BIGINT(20) UNSIGNED | ID del proyecto | FK a ai360re_projects |
| `user_id` | BIGINT(20) UNSIGNED | ID del usuario | FK a wp_users |
| `role` | VARCHAR(50) | Rol del usuario en el proyecto | admin, manager, editor, viewer |
| `permissions` | LONGTEXT | Permisos específicos en JSON | Opcional, JSON |
| `assigned_at` | DATETIME | Fecha de asignación | Requerido |
| `assigned_by` | BIGINT(20) UNSIGNED | Usuario que asignó el rol | FK a wp_users, NULL si sistema |

### Valores de `role`
- `admin` - Administrador del proyecto (control total)
- `manager` - Gestor (puede editar configuración y usuarios)
- `editor` - Editor (puede editar propiedades)
- `viewer` - Visualizador (solo lectura)

### Ejemplo de `permissions` (JSON)
```json
{
    "can_delete_properties": false,
    "can_sync_connectors": true,
    "can_use_ai": true,
    "can_publish": true,
    "custom_permissions": []
}
```

---

## Tabla: ai360re_properties

**Propósito:** Almacena propiedades inmobiliarias (modelo normalizado)

```sql
CREATE TABLE {prefix}ai360re_properties (
    property_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT(20) UNSIGNED NOT NULL,
    external_id VARCHAR(255),
    source VARCHAR(100),
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    type VARCHAR(100),
    title VARCHAR(500),
    short_description TEXT,
    long_description LONGTEXT,
    price DECIMAL(15,2),
    currency VARCHAR(10) DEFAULT 'EUR',
    location_address VARCHAR(500),
    location_city VARCHAR(200),
    location_state VARCHAR(200),
    location_country VARCHAR(100),
    location_postal_code VARCHAR(20),
    location_latitude DECIMAL(10,8),
    location_longitude DECIMAL(11,8),
    specifications LONGTEXT,
    features LONGTEXT,
    images LONGTEXT,
    documents LONGTEXT,
    contact_info LONGTEXT,
    seo_data LONGTEXT,
    metadata LONGTEXT,
    connector_hashes LONGTEXT,
    version_number INT(11) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    published_at DATETIME,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (property_id),
    KEY project_id (project_id),
    KEY external_id (external_id),
    KEY source (source),
    KEY status (status),
    KEY type (type),
    KEY location_city (location_city),
    KEY price (price),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campos Principales

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `property_id` | BIGINT(20) UNSIGNED | ID único de la propiedad |
| `project_id` | BIGINT(20) UNSIGNED | ID del proyecto propietario |
| `external_id` | VARCHAR(255) | ID en sistema externo |
| `source` | VARCHAR(100) | Origen (woocommerce, resales, manual) |
| `status` | VARCHAR(50) | Estado en workflow |
| `type` | VARCHAR(100) | Tipo de propiedad |

### Campos de Contenido

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `title` | VARCHAR(500) | Título de la propiedad |
| `short_description` | TEXT | Descripción corta |
| `long_description` | LONGTEXT | Descripción completa |

### Campos Económicos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `price` | DECIMAL(15,2) | Precio |
| `currency` | VARCHAR(10) | Moneda (EUR, USD, etc.) |

### Campos de Ubicación

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `location_address` | VARCHAR(500) | Dirección completa |
| `location_city` | VARCHAR(200) | Ciudad |
| `location_state` | VARCHAR(200) | Estado/Provincia |
| `location_country` | VARCHAR(100) | País |
| `location_postal_code` | VARCHAR(20) | Código postal |
| `location_latitude` | DECIMAL(10,8) | Latitud |
| `location_longitude` | DECIMAL(11,8) | Longitud |

### Campos JSON

| Campo | Tipo | Descripción | Estructura |
|-------|------|-------------|-----------|
| `specifications` | LONGTEXT | Especificaciones técnicas | JSON (m², habitaciones, baños, etc.) |
| `features` | LONGTEXT | Características | JSON (piscina, garaje, etc.) |
| `images` | LONGTEXT | Imágenes | JSON array de URLs |
| `documents` | LONGTEXT | Documentos | JSON array de URLs |
| `contact_info` | LONGTEXT | Información de contacto | JSON |
| `seo_data` | LONGTEXT | Datos SEO | JSON |
| `metadata` | LONGTEXT | Metadatos adicionales | JSON |
| `connector_hashes` | LONGTEXT | Hashes por conector | JSON |

### Campos de Control

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `version_number` | INT(11) | Número de versión actual |
| `created_at` | DATETIME | Fecha de creación |
| `updated_at` | DATETIME | Fecha de última actualización |
| `published_at` | DATETIME | Fecha de publicación |
| `deleted_at` | DATETIME | Fecha de eliminación (soft delete) |

### Valores de `status`
- `draft` - Borrador
- `imported` - Recién importada
- `optimized` - Optimizada con IA
- `validated` - Validada por usuario
- `ready` - Lista para publicar
- `published` - Publicada
- `synced` - Sincronizada con destinos
- `archived` - Archivada

### Ejemplo de `specifications` (JSON)
```json
{
    "surface_total": 150,
    "surface_useful": 130,
    "surface_plot": 500,
    "bedrooms": 3,
    "bathrooms": 2,
    "floor": 2,
    "year_built": 2015,
    "condition": "excellent",
    "orientation": "south",
    "energy_rating": "A"
}
```

### Ejemplo de `features` (JSON)
```json
{
    "pool": true,
    "garden": true,
    "terrace": true,
    "garage": true,
    "garage_spaces": 2,
    "elevator": true,
    "air_conditioning": true,
    "heating": true,
    "furnished": false,
    "security": true,
    "storage_room": true
}
```

### Ejemplo de `connector_hashes` (JSON)
```json
{
    "woocommerce": "abc123def456...",
    "resales": "789ghi012jkl...",
    "idealista": null
}
```

---

## Tabla: ai360re_property_versions

**Propósito:** Versionado completo de propiedades para auditoría y rollback

```sql
CREATE TABLE {prefix}ai360re_property_versions (
    version_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT(20) UNSIGNED NOT NULL,
    version_number INT(11) NOT NULL,
    data LONGTEXT NOT NULL,
    changed_by VARCHAR(100),
    change_source VARCHAR(100),
    change_reason TEXT,
    diff_summary LONGTEXT,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (version_id),
    KEY property_id (property_id),
    KEY version_number (version_number),
    KEY created_at (created_at),
    KEY changed_by (changed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `version_id` | BIGINT(20) UNSIGNED | ID único de la versión |
| `property_id` | BIGINT(20) UNSIGNED | ID de la propiedad |
| `version_number` | INT(11) | Número de versión |
| `data` | LONGTEXT | Snapshot completo de la propiedad en JSON |
| `changed_by` | VARCHAR(100) | Quién hizo el cambio (user_id, 'ai', 'sync') |
| `change_source` | VARCHAR(100) | Origen del cambio |
| `change_reason` | TEXT | Razón del cambio |
| `diff_summary` | LONGTEXT | Resumen de diferencias en JSON |
| `created_at` | DATETIME | Fecha de creación de la versión |

### Valores de `change_source`
- `user_edit` - Edición manual por usuario
- `ai_optimization` - Optimización por IA
- `sync_import` - Importación desde conector
- `sync_update` - Actualización desde conector
- `bulk_operation` - Operación en lote
- `api_update` - Actualización via REST API

---

## Tabla: ai360re_connectors

**Propósito:** Configuración de conectores a sistemas externos

```sql
CREATE TABLE {prefix}ai360re_connectors (
    connector_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT(20) UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    type VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'inactive',
    config LONGTEXT NOT NULL,
    credentials LONGTEXT,
    sync_settings LONGTEXT,
    last_sync_at DATETIME,
    last_sync_status VARCHAR(50),
    last_sync_message TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (connector_id),
    KEY project_id (project_id),
    KEY type (type),
    KEY status (status),
    KEY last_sync_at (last_sync_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `connector_id` | BIGINT(20) UNSIGNED | ID único del conector |
| `project_id` | BIGINT(20) UNSIGNED | ID del proyecto |
| `name` | VARCHAR(200) | Nombre descriptivo del conector |
| `type` | VARCHAR(100) | Tipo de conector |
| `status` | VARCHAR(50) | Estado del conector |
| `config` | LONGTEXT | Configuración general en JSON |
| `credentials` | LONGTEXT | Credenciales (encriptadas) en JSON |
| `sync_settings` | LONGTEXT | Configuración de sincronización en JSON |
| `last_sync_at` | DATETIME | Última sincronización |
| `last_sync_status` | VARCHAR(50) | Estado de última sync |
| `last_sync_message` | TEXT | Mensaje de última sync |
| `created_at` | DATETIME | Fecha de creación |
| `updated_at` | DATETIME | Fecha de actualización |

### Valores de `type`
- `woocommerce` - WooCommerce Products
- `wordpress_posts` - WordPress Posts
- `resales` - Resales V6 API
- `idealista` - Idealista API
- `fotocasa` - Fotocasa API
- `custom_api` - API REST personalizada
- `csv` - Import/Export CSV

### Valores de `status`
- `inactive` - Inactivo (no sincroniza)
- `active` - Activo (sincroniza)
- `error` - Error en última sincronización
- `paused` - Pausado manualmente

### Ejemplo de `config` (JSON)
```json
{
    "api_url": "https://api.example.com",
    "version": "v6",
    "timeout": 30,
    "retry_attempts": 3,
    "custom_mappings": {}
}
```

### Ejemplo de `sync_settings` (JSON)
```json
{
    "direction": "bidirectional",
    "conflict_strategy": "last_modified",
    "auto_sync": true,
    "sync_interval": 3600,
    "sync_on_save": false,
    "filters": {
        "property_type": ["apartment", "house"],
        "min_price": 100000
    }
}
```

---

## Tabla: ai360re_sync_log

**Propósito:** Log detallado de operaciones de sincronización

```sql
CREATE TABLE {prefix}ai360re_sync_log (
    log_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    connector_id BIGINT(20) UNSIGNED NOT NULL,
    property_id BIGINT(20) UNSIGNED,
    operation VARCHAR(50) NOT NULL,
    direction VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    message TEXT,
    data_before LONGTEXT,
    data_after LONGTEXT,
    error_details LONGTEXT,
    duration_ms INT(11),
    created_at DATETIME NOT NULL,
    PRIMARY KEY (log_id),
    KEY connector_id (connector_id),
    KEY property_id (property_id),
    KEY operation (operation),
    KEY status (status),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `log_id` | BIGINT(20) UNSIGNED | ID único del log |
| `connector_id` | BIGINT(20) UNSIGNED | ID del conector |
| `property_id` | BIGINT(20) UNSIGNED | ID de la propiedad (si aplica) |
| `operation` | VARCHAR(50) | Tipo de operación |
| `direction` | VARCHAR(50) | Dirección de la sync |
| `status` | VARCHAR(50) | Estado de la operación |
| `message` | TEXT | Mensaje descriptivo |
| `data_before` | LONGTEXT | Datos antes (JSON) |
| `data_after` | LONGTEXT | Datos después (JSON) |
| `error_details` | LONGTEXT | Detalles de error (JSON) |
| `duration_ms` | INT(11) | Duración en milisegundos |
| `created_at` | DATETIME | Fecha de la operación |

### Valores de `operation`
- `sync_full` - Sincronización completa
- `sync_partial` - Sincronización parcial
- `import` - Importación
- `export` - Exportación
- `create` - Crear en remoto
- `update` - Actualizar en remoto
- `delete` - Eliminar en remoto
- `conflict_resolved` - Conflicto resuelto

### Valores de `direction`
- `local_to_remote` - Del plugin al sistema externo
- `remote_to_local` - Del sistema externo al plugin
- `bidirectional` - Ambas direcciones

### Valores de `status`
- `success` - Exitosa
- `failed` - Fallida
- `partial` - Parcialmente exitosa
- `skipped` - Omitida
- `conflict` - Conflicto detectado

---

## Tabla: ai360re_ai_tasks

**Propósito:** Cola y registro de tareas de IA

```sql
CREATE TABLE {prefix}ai360re_ai_tasks (
    task_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT(20) UNSIGNED NOT NULL,
    task_type VARCHAR(100) NOT NULL,
    provider VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    priority INT(11) NOT NULL DEFAULT 5,
    input_data LONGTEXT NOT NULL,
    output_data LONGTEXT,
    tokens_used INT(11),
    cost DECIMAL(10,6),
    error_message TEXT,
    started_at DATETIME,
    completed_at DATETIME,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (task_id),
    KEY property_id (property_id),
    KEY task_type (task_type),
    KEY provider (provider),
    KEY status (status),
    KEY priority (priority),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `task_id` | BIGINT(20) UNSIGNED | ID único de la tarea |
| `property_id` | BIGINT(20) UNSIGNED | ID de la propiedad |
| `task_type` | VARCHAR(100) | Tipo de tarea de IA |
| `provider` | VARCHAR(100) | Provider de IA usado |
| `status` | VARCHAR(50) | Estado de la tarea |
| `priority` | INT(11) | Prioridad (1-10, menor = mayor prioridad) |
| `input_data` | LONGTEXT | Datos de entrada en JSON |
| `output_data` | LONGTEXT | Resultado en JSON |
| `tokens_used` | INT(11) | Tokens consumidos |
| `cost` | DECIMAL(10,6) | Costo en USD |
| `error_message` | TEXT | Mensaje de error |
| `started_at` | DATETIME | Inicio de procesamiento |
| `completed_at` | DATETIME | Fin de procesamiento |
| `created_at` | DATETIME | Fecha de creación |

### Valores de `task_type`
- `rewrite_title` - Reescribir título
- `generate_short_desc` - Generar descripción corta
- `generate_long_desc` - Generar descripción larga
- `optimize_seo` - Optimizar SEO
- `generate_by_channel` - Generar por canal
- `translate` - Traducir
- `analyze_sentiment` - Análisis de sentimiento

### Valores de `status`
- `pending` - Pendiente
- `processing` - En proceso
- `completed` - Completada
- `failed` - Fallida
- `cancelled` - Cancelada

---

## Tabla: ai360re_audit_log

**Propósito:** Auditoría completa de acciones en el sistema

```sql
CREATE TABLE {prefix}ai360re_audit_log (
    audit_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED,
    project_id BIGINT(20) UNSIGNED,
    entity_type VARCHAR(100),
    entity_id BIGINT(20) UNSIGNED,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    data_before LONGTEXT,
    data_after LONGTEXT,
    ip_address VARCHAR(50),
    user_agent VARCHAR(500),
    created_at DATETIME NOT NULL,
    PRIMARY KEY (audit_id),
    KEY user_id (user_id),
    KEY project_id (project_id),
    KEY entity_type (entity_type),
    KEY entity_id (entity_id),
    KEY action (action),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `audit_id` | BIGINT(20) UNSIGNED | ID único del audit |
| `user_id` | BIGINT(20) UNSIGNED | Usuario que realizó la acción |
| `project_id` | BIGINT(20) UNSIGNED | Proyecto afectado |
| `entity_type` | VARCHAR(100) | Tipo de entidad |
| `entity_id` | BIGINT(20) UNSIGNED | ID de la entidad |
| `action` | VARCHAR(100) | Acción realizada |
| `description` | TEXT | Descripción de la acción |
| `data_before` | LONGTEXT | Estado anterior (JSON) |
| `data_after` | LONGTEXT | Estado posterior (JSON) |
| `ip_address` | VARCHAR(50) | IP del usuario |
| `user_agent` | VARCHAR(500) | User agent |
| `created_at` | DATETIME | Fecha de la acción |

### Valores de `entity_type`
- `project` - Proyecto
- `property` - Propiedad
- `connector` - Conector
- `user_assignment` - Asignación de usuario
- `ai_task` - Tarea de IA
- `setting` - Configuración

### Valores de `action`
- `create` - Crear
- `read` - Leer
- `update` - Actualizar
- `delete` - Eliminar
- `restore` - Restaurar
- `sync` - Sincronizar
- `optimize` - Optimizar con IA
- `publish` - Publicar
- `archive` - Archivar

---

## Índices y Rendimiento

### Índices Principales
Cada tabla tiene:
- Primary Key en el ID principal
- Índices en foreign keys
- Índices en campos de búsqueda frecuente
- Índices en campos de ordenamiento

### Consideraciones de Rendimiento
- `ENGINE=InnoDB` para soporte de transacciones y foreign keys lógicas
- `utf8mb4` para soporte completo de Unicode (emojis, etc.)
- Campos JSON almacenados como LONGTEXT (MySQL 5.7+ soporta tipo JSON nativo)
- Soft deletes con `deleted_at` para recuperación

---

## Relaciones entre Tablas

```
wp_users (WordPress)
    ├──> ai360re_projects (owner_id)
    └──> ai360re_project_users (user_id)

ai360re_projects
    ├──> ai360re_project_users (project_id)
    ├──> ai360re_properties (project_id)
    ├──> ai360re_connectors (project_id)
    └──> ai360re_audit_log (project_id)

ai360re_properties
    ├──> ai360re_property_versions (property_id)
    ├──> ai360re_ai_tasks (property_id)
    ├──> ai360re_sync_log (property_id)
    └──> ai360re_audit_log (entity_id)

ai360re_connectors
    ├──> ai360re_sync_log (connector_id)
    └──> ai360re_audit_log (entity_id)
```

---

## Scripts de Creación

### Activación del Plugin
Al activar el plugin, se ejecutará un script que:
1. Verifica la existencia de cada tabla
2. Crea las tablas faltantes
3. Actualiza tablas existentes si hay cambios en el schema
4. Crea índices
5. Guarda la versión del schema en opciones de WordPress

### Desinstalación del Plugin
En `uninstall.php`:
1. Eliminar todas las tablas `ai360re_*`
2. Eliminar todas las opciones de WordPress relacionadas
3. Limpiar capabilities personalizadas
4. Limpiar roles personalizados

---

## Migraciones

### Versionado de Schema
- Cada cambio de schema incrementa la versión
- La versión se almacena en `wp_options` con key `ai360re_db_version`
- Al activar, se compara la versión actual con la almacenada
- Si difieren, se ejecutan las migraciones necesarias

### Ejemplo de Migración
```php
function ai360re_migrate_to_v2() {
    global $wpdb;
    $table = $wpdb->prefix . 'ai360re_properties';
    
    // Agregar columna nueva
    $wpdb->query("ALTER TABLE {$table} ADD COLUMN new_field VARCHAR(255)");
    
    // Actualizar versión
    update_option('ai360re_db_version', '2.0');
}
```

---

## Backups y Mantenimiento

### Recomendaciones
- Backup completo antes de actualizaciones
- Backup selectivo de tablas `ai360re_*`
- Limpieza periódica de logs antiguos
- Optimización de tablas mensualmente

### Retención de Datos
- `ai360re_sync_log`: 30 días por defecto
- `ai360re_audit_log`: 90 días por defecto
- `ai360re_property_versions`: últimas 10 versiones por defecto
- `ai360re_ai_tasks`: completadas > 7 días eliminables

---

**Documento creado para:** PR-00 - Análisis de Arquitectura y Documentación Técnica Fundacional  
**Última actualización:** 2025-12-18  
**Versión:** 1.0
