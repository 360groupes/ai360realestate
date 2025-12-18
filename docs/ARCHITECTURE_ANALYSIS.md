# Análisis de Arquitectura de Referencia

## Propósito del Documento

Este documento analiza los patrones arquitectónicos identificados en los repositorios de referencia del ecosistema 360group. El análisis es **puramente conceptual** y sirve como inspiración para diseñar la arquitectura de `ai360realestate`. **No se reutilizará código directamente** de estos repositorios.

## Repositorios Analizados

### 1. ai360agency-api - API de IA para WordPress

**Estructura Identificada:**
- Plugin WordPress que expone REST API
- Patrón Factory para providers de IA (OpenAI, Anthropic)
- Sistema de autenticación con API Keys
- Rate limiting para prevenir abuso
- Sistema de logs y auditoría
- Configuración centralizada mediante WordPress Options API

**Endpoints Principales:**
- `/chat` - Conversaciones con IA
- `/models` - Listado de modelos disponibles
- `/embeddings` - Generación de embeddings
- `/search` - Búsqueda semántica
- `/health` - Estado del servicio

**Patrones Arquitectónicos Observados:**

#### Provider Factory Pattern
```
Interface AIProvider
  - chat()
  - embeddings()
  - models()

OpenAIProvider implements AIProvider
AnthropicProvider implements AIProvider
```

Este patrón permite cambiar o agregar providers de IA sin modificar el código core.

#### Plugin Structure
```
plugin-root/
  includes/
    Core/
    Providers/
    API/
    Auth/
    Logging/
```

**Lecciones Aprendidas:**
- ✅ La separación de providers permite flexibilidad
- ✅ REST API bien estructurada facilita integraciones
- ✅ Autenticación con API Key es simple pero efectiva
- ✅ Logs centralizados son críticos para debugging
- ⚠️  La configuración via WordPress options puede ser limitada para configuraciones complejas

---

### 2. ai360chat - Sistema de Chat con Conectores Externos

**Estructura Identificada:**
- Arquitectura Core + Add-ons modular
- Sistema de External Connection Interface
- Inventory Provider Pattern
- Integración con Resales V6 API
- Formato normalizado de items independiente del origen
- REST API para gestión de conexiones y sincronización

**Componentes Principales:**

#### Core System
- Motor base del chat
- Sistema de gestión de conversaciones
- Integración con IA

#### External Connectors
- Interface común para todos los conectores
- Resales Connector (V6 API)
- Formato normalizado de propiedades

**Patrones Arquitectónicos Observados:**

#### Connector Interface Pattern
```
Interface ExternalConnector
  - connect()
  - sync()
  - fetchItems()
  - normalizeData()
  - disconnect()

ResalesConnector implements ExternalConnector
```

#### Normalized Data Format
```json
{
  "id": "external-id",
  "source": "resales",
  "type": "property",
  "data": {
    "title": "...",
    "description": "...",
    "price": 0,
    "location": {}
  },
  "metadata": {
    "synced_at": "timestamp",
    "hash": "..."
  }
}
```

**Lecciones Aprendidas:**
- ✅ El formato normalizado permite agregar múltiples fuentes sin cambios en el core
- ✅ La separación Core/Add-ons facilita mantenimiento
- ✅ El sistema de sync con hash previene sincronizaciones innecesarias
- ✅ La interface de conectores permite implementar múltiples fuentes (Idealista, Fotocasa, etc.)
- ⚠️  La sincronización unidireccional puede no ser suficiente para todos los casos

---

### 3. dashboard-wp - Dashboard con WooCommerce

**Estructura Identificada:**
- Integración profunda con WooCommerce REST API
- Integración con WordPress REST API nativa
- Sistema de templates independiente
- Gestión de productos como entidad central

**Componentes Principales:**
- WooCommerce API Client
- WordPress API Client
- Template System
- Product Management

**Patrones Arquitectónicos Observados:**

#### API Client Pattern
```
WooCommerceClient
  - products()
  - orders()
  - customers()

WordPressClient
  - posts()
  - pages()
  - media()
```

#### Template System
```
templates/
  product-list.php
  product-detail.php
  dashboard.php
```

**Lecciones Aprendidas:**
- ✅ La separación de clientes API facilita testing
- ✅ Templates independientes permiten personalización
- ✅ WooCommerce proporciona una base sólida para e-commerce
- ⚠️  La dependencia fuerte de WooCommerce puede limitar flexibilidad

---

## Análisis Comparativo de Arquitecturas

| Aspecto | ai360agency-api | ai360chat | dashboard-wp | ai360realestate |
|---------|-----------------|-----------|--------------|-----------------|
| **Patrón Principal** | Provider Factory | Connector Interface | API Client | Hybrid (todos) |
| **Extensibilidad** | Alta (Providers) | Alta (Connectors) | Media | Muy Alta |
| **Integración WordPress** | Plugin estándar | Plugin + Add-ons | Theme/Plugin | Plugin completo |
| **API Externa** | OpenAI/Anthropic | Resales V6 | WooCommerce | 360group.ai + Resales |
| **Persistencia** | WP Options | Custom Tables | WP Posts | Custom Tables |
| **Normalización** | No aplica | Alta | Media | Muy Alta |
| **Sincronización** | No aplica | Unidireccional | No aplica | Bidireccional |

---

## Patrones a Reimplementar en ai360realestate

### 1. Provider Factory Pattern (de ai360agency-api)

**Concepto a adoptar:**
Implementaremos nuestro propio patrón factory para providers de IA, pero con código completamente nuevo.

**Beneficios:**
- Permite agregar nuevos providers sin modificar código existente
- Facilita testing mediante mocking
- Cumple con Open/Closed Principle

**Implementación propuesta:**
```php
namespace AI360RealEstate\AI;

interface AIProviderInterface {
    public function rewriteTitle(string $original, array $context): string;
    public function generateDescription(string $title, array $property): string;
    public function optimizeSEO(array $property): array;
}

class AIProviderFactory {
    public static function create(string $provider): AIProviderInterface {
        // Implementación propia
    }
}
```

### 2. Connector Interface Pattern (de ai360chat)

**Concepto a adoptar:**
Sistema de conectores con interface común, pero implementado desde cero.

**Beneficios:**
- Múltiples fuentes de propiedades (WooCommerce, Resales, Idealista, etc.)
- Formato normalizado interno
- Sincronización bidireccional

**Implementación propuesta:**
```php
namespace AI360RealEstate\Connectors;

interface ConnectorInterface {
    public function connect(array $config): bool;
    public function sync(string $direction): SyncResult;
    public function read(string $id): ?Property;
    public function create(Property $property): string;
    public function update(string $id, Property $property): bool;
    public function delete(string $id): bool;
}
```

### 3. Normalized Data Format (de ai360chat)

**Concepto a adoptar:**
Formato interno único para propiedades, independiente del origen.

**Beneficios:**
- Independencia de fuentes externas
- Facilita transformaciones
- Simplifica lógica de negocio

**Implementación propuesta:**
```php
namespace AI360RealEstate\Entities;

class Property {
    private string $id;
    private string $source;
    private PropertyData $data;
    private PropertyMetadata $metadata;
    
    // Métodos propios
}
```

### 4. Custom Tables (experiencia propia)

**Concepto a adoptar:**
Tablas personalizadas en lugar de posts/meta de WordPress.

**Beneficios:**
- Mayor rendimiento
- Estructura de datos más clara
- Queries más eficientes
- No contamina wp_posts

**Implementación propuesta:**
- `{prefix}ai360re_projects`
- `{prefix}ai360re_properties`
- `{prefix}ai360re_connectors`
- etc.

---

## Decisiones de NO Reutilizar Código

### 1. NO copiaremos código de ai360agency-api

**Razón:**
- El código está específicamente diseñado para las necesidades de ese plugin
- Los namespaces serían conflictivos
- Queremos mantener independencia total

**Alternativa:**
- Implementaremos nuestro propio sistema de providers
- Usaremos el concepto del patrón, no el código

### 2. NO copiaremos código de ai360chat

**Razón:**
- Los conectores de chat tienen requerimientos diferentes
- La sincronización unidireccional no es suficiente para nuestro caso
- Necesitamos funcionalidad bidireccional

**Alternativa:**
- Diseñaremos nuestro propio sistema de conectores
- Implementaremos sincronización bidireccional desde cero

### 3. NO copiaremos código de dashboard-wp

**Razón:**
- Es un sistema de dashboard, no un plugin de gestión de propiedades
- La arquitectura es muy específica para su caso de uso

**Alternativa:**
- Crearemos nuestro propio frontend portal
- Implementaremos nuestra propia integración con WooCommerce

### 4. NO usaremos librerías de código común entre proyectos

**Razón:**
- Queremos evitar dependencias externas
- El plugin debe funcionar de forma completamente autónoma
- Facilita instalación y mantenimiento

**Alternativa:**
- Todo el código será desarrollado específicamente para ai360realestate
- Usaremos solo dependencias estándar de WordPress y Composer (si necesario)

---

## Patrones Arquitectónicos Propios de ai360realestate

### 1. Proyecto como Entidad Central

**Concepto nuevo:**
A diferencia de los repos de referencia, en ai360realestate el **Proyecto** es la entidad central que agrupa todo.

```
Proyecto (Project)
  ├── Usuarios asignados (roles)
  ├── Propiedades
  ├── Conectores configurados
  └── Configuración de IA
```

**Beneficio:**
Permite aislamiento multi-tenant dentro del mismo WordPress.

### 2. Sincronización Bidireccional Inteligente

**Concepto nuevo:**
Sistema de sincronización que previene bucles y conflictos mediante:
- Hash por conector
- Estrategias de resolución de conflictos
- Prevención de bucles infinitos

**Beneficio:**
Permite escribir en múltiples destinos sin perder cambios.

### 3. Versionado de Propiedades

**Concepto nuevo:**
Cada modificación de IA genera una nueva versión, permitiendo:
- Comparación (diff)
- Rollback
- Auditoría completa

**Beneficio:**
El usuario puede ver qué cambió y revertir si no le gusta.

### 4. Workflow de Estados

**Concepto nuevo:**
Cada propiedad pasa por estados definidos:
```
importada → optimizada → validada → lista_para_publicar → sincronizada
```

**Beneficio:**
Claridad en el proceso y prevención de errores.

---

## Arquitectura de Alto Nivel para ai360realestate

```
┌─────────────────────────────────────────────────────────────┐
│                     WordPress Core                          │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  ai360realestate Plugin                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐  │
│  │   Core       │  │  Entities    │  │      Auth       │  │
│  │   System     │  │   - Project  │  │   - Roles       │  │
│  │              │  │   - Property │  │   - Caps        │  │
│  └──────────────┘  │   - User     │  └─────────────────┘  │
│                    └──────────────┘                        │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │            Connectors (bidirectional)               │   │
│  ├─────────────────────────────────────────────────────┤   │
│  │  WooCommerce │ WordPress │ Resales │ Idealista      │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │               AI Provider System                    │   │
│  ├─────────────────────────────────────────────────────┤   │
│  │  360group.ai │ OpenAI (future) │ Anthropic (future) │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐  │
│  │   Sync       │  │   REST API   │  │    Frontend     │  │
│  │   Engine     │  │   Endpoints  │  │     Portal      │  │
│  │              │  │              │  │  (Modo Agencia) │  │
│  └──────────────┘  └──────────────┘  └─────────────────┘  │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐                        │
│  │   Logging    │  │    Admin     │                        │
│  │   & Audit    │  │   Backend    │                        │
│  └──────────────┘  └──────────────┘                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Custom Database Tables                         │
│  - projects                                                 │
│  - project_users                                            │
│  - properties                                               │
│  - property_versions                                        │
│  - connectors                                               │
│  - sync_log                                                 │
│  - ai_tasks                                                 │
│  - audit_log                                                │
└─────────────────────────────────────────────────────────────┘
```

---

## Comparativa de Decisiones Arquitectónicas

### Persistencia de Datos

| Solución | Ventajas | Desventajas | Decisión |
|----------|----------|-------------|----------|
| WordPress Posts/Meta | - Integración nativa<br>- UI automática | - Rendimiento limitado<br>- Estructura rígida | ❌ No usar |
| WP Options | - Simple<br>- Bueno para config | - No escalable<br>- Sin relaciones | ❌ No usar para datos principales |
| Custom Tables | - Alto rendimiento<br>- Estructura flexible<br>- Queries eficientes | - Requiere más código<br>- Mantenimiento manual | ✅ **Usar** |

**Decisión final:** Custom Tables para todas las entidades principales.

### Sistema de Autenticación

| Solución | Ventajas | Desventajas | Decisión |
|----------|----------|-------------|----------|
| WordPress Roles nativos | - Ya existe<br>- Familiar | - No suficiente para multi-proyecto | ⚠️  Usar como base |
| Roles personalizados | - Control granular<br>- Por proyecto | - Más complejo | ✅ **Combinar con nativos** |
| API Keys externas | - Independiente<br>- Flexible | - Desconectado de WP | ❌ No necesario |

**Decisión final:** Roles personalizados basados en capabilities de WordPress, con gestión por proyecto.

### Sincronización

| Solución | Ventajas | Desventajas | Decisión |
|----------|----------|-------------|----------|
| Unidireccional (solo lectura) | - Simple<br>- Sin conflictos | - No permite publicar | ❌ Insuficiente |
| Unidireccional (solo escritura) | - Simple<br>- No importa cambios externos | - Pierde actualizaciones | ❌ Insuficiente |
| Bidireccional | - Completo<br>- Flexible | - Complejo<br>- Riesgo de conflictos | ✅ **Usar con estrategias** |

**Decisión final:** Sincronización bidireccional con resolución inteligente de conflictos.

### Frontend

| Solución | Ventajas | Desventajas | Decisión |
|----------|----------|-------------|----------|
| Solo Admin WordPress | - Rápido de hacer<br>- Familiar | - UX limitada<br>- No para clientes | ⚠️  Fase inicial |
| Portal separado | - UX moderna<br>- Personalizable | - Más desarrollo | ✅ **Agregar en fases posteriores** |
| SPA (React/Vue) | - Muy moderna<br>- Interactiva | - Complejidad alta | ❌ Overkill inicial |

**Decisión final:** Comenzar con Admin WordPress, evolucionar a Portal personalizado (Modo Agencia).

---

## Conclusiones

### Patrones que Adoptaremos (concepto)
1. ✅ Provider Factory Pattern para IA
2. ✅ Connector Interface Pattern para fuentes externas
3. ✅ Normalized Data Format para propiedades
4. ✅ Custom Tables para persistencia
5. ✅ REST API para integraciones

### Patrones que NO Adoptaremos
1. ❌ Código directo de repositorios de referencia
2. ❌ Dependencias entre plugins del ecosistema 360group
3. ❌ WordPress Posts/Meta para entidades principales
4. ❌ Sincronización unidireccional

### Innovaciones Propias
1. ✅ Proyecto como entidad central (multi-tenant)
2. ✅ Sincronización bidireccional inteligente
3. ✅ Versionado completo de propiedades
4. ✅ Workflow de estados bien definido
5. ✅ Portal Frontend con paridad de funciones

---

## Próximos Pasos

Este análisis arquitectónico sirve como base para:
1. ✅ Documentar decisiones técnicas (TECHNICAL_DECISIONS.md)
2. ✅ Diseñar esquema de base de datos (DATABASE_SCHEMA.md)
3. ✅ Planificar roadmap completo (ROADMAP.md)
4. ✅ Especificar entidades (ENTITIES.md)
5. ✅ Diseñar conectores (CONNECTORS_SPEC.md)
6. ✅ Definir integración IA (AI_INTEGRATION.md)
7. ✅ Especificar frontend (FRONTEND_PORTAL.md)

---

**Documento creado para:** PR-00 - Análisis de Arquitectura y Documentación Técnica Fundacional  
**Última actualización:** 2025-12-18  
**Versión:** 1.0
