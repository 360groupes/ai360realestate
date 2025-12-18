# Decisiones Técnicas - ai360realestate

## Propósito del Documento

Este documento registra todas las decisiones técnicas y arquitectónicas tomadas para el proyecto ai360realestate. Cada decisión está justificada y documentada para referencia futura.

---

## Decisiones Fundamentales

### D-001: Plugin WordPress Independiente

**Decisión:** ai360realestate será un plugin de WordPress completamente independiente.

**Justificación:**
- ✅ Funciona sin dependencias de otros plugins del ecosistema 360group
- ✅ Instalación simple (un solo plugin)
- ✅ Mantenimiento independiente
- ✅ Distribución facilitada
- ✅ No requiere configuración externa

**Alternativas consideradas:**
- ❌ Plugin que requiere ai360agency-api → rechazada (dependencia externa)
- ❌ Theme en lugar de plugin → rechazada (menor portabilidad)
- ❌ Must-use plugin → rechazada (complejidad de instalación)

**Implicaciones:**
- Debe incluir todo el código necesario
- No puede asumir que otros plugins están instalados
- Debe ser autocontenido

---

### D-002: Reimplementación desde Cero

**Decisión:** TODO el código será escrito desde cero específicamente para este plugin.

**Justificación:**
- ✅ Evita conflictos de namespaces
- ✅ Control total sobre el código
- ✅ No hay dependencias ocultas
- ✅ Licenciamiento claro (GPL v2)
- ✅ Mantenimiento simplificado

**Alternativas consideradas:**
- ❌ Copiar código de ai360agency-api → rechazada (conflictos potenciales)
- ❌ Compartir librerías comunes → rechazada (dependencia)
- ❌ Usar código de ai360chat → rechazada (no se ajusta)

**Implicaciones:**
- Mayor tiempo de desarrollo inicial
- Código optimizado para el caso de uso específico
- Mayor flexibilidad para cambios futuros

---

### D-003: Namespace Único

**Decisión:** Todo el código PHP usará el namespace `AI360RealEstate\`

**Justificación:**
- ✅ Evita conflictos con otros plugins
- ✅ Claridad en el código
- ✅ Cumple con PSR-4
- ✅ Facilita autoloading con Composer

**Convención:**
```php
namespace AI360RealEstate;              // Root
namespace AI360RealEstate\Core;         // Core system
namespace AI360RealEstate\Entities;     // Entidades
namespace AI360RealEstate\Connectors;   // Conectores
namespace AI360RealEstate\AI;           // Providers IA
namespace AI360RealEstate\API;          // REST API
namespace AI360RealEstate\Admin;        // Backend admin
namespace AI360RealEstate\Auth;         // Autenticación
namespace AI360RealEstate\Sync;         // Sincronización
namespace AI360RealEstate\Logging;      // Logs y auditoría
```

**Alternativas consideradas:**
- ❌ `AI360\RealEstate\` → rechazada (conflicto con otros plugins AI360)
- ❌ `RealEstate\` → rechazada (muy genérico)
- ❌ `A360RE\` → rechazada (poco clara)

---

### D-004: WordPress Coding Standards

**Decisión:** El código seguirá los WordPress Coding Standards.

**Justificación:**
- ✅ Estándar de la comunidad WordPress
- ✅ Facilita revisión por otros desarrolladores
- ✅ Herramientas de linting disponibles (PHPCS)
- ✅ Consistencia con el ecosistema

**Estándares a seguir:**
- WordPress-Core
- WordPress-Docs
- WordPress-Extra

**Herramientas:**
```bash
composer require --dev wp-coding-standards/wpcs
phpcs --standard=WordPress src/
```

**Excepciones permitidas:**
- Namespaces (no son 100% estándar WP pero son necesarios)
- Composer autoloading

---

### D-005: Persistencia con Custom Tables

**Decisión:** Usaremos tablas personalizadas en lugar de wp_posts/wp_postmeta.

**Justificación:**
- ✅ Mayor rendimiento en queries complejas
- ✅ Estructura de datos clara y específica
- ✅ No contamina wp_posts con miles de propiedades
- ✅ Relaciones explícitas mediante foreign keys (lógicas)
- ✅ Indexes optimizados para nuestro caso de uso

**Alternativas consideradas:**
- ❌ Custom Post Types → rechazada (limitado para casos complejos)
- ❌ WordPress Options → rechazada (no escala)
- ❌ Base de datos externa → rechazada (complejidad innecesaria)

**Implicaciones:**
- Necesitamos crear/actualizar schema en activación
- Necesitamos limpieza en desinstalación
- No tenemos UI automática de WordPress (debemos crearla)

---

### D-006: Prefijo de Tablas

**Decisión:** Todas las tablas personalizadas usarán el prefijo `{wpdb_prefix}ai360re_`

**Ejemplo:**
```
wp_ai360re_projects
wp_ai360re_properties
wp_ai360re_connectors
```

**Justificación:**
- ✅ Evita conflictos con otras tablas
- ✅ Fácil identificación
- ✅ Respeta el prefijo de WordPress
- ✅ Facilita backup selectivo

**Alternativas consideradas:**
- ❌ `wp_realestate_` → rechazada (muy genérico, conflictos potenciales)
- ❌ `wp_360re_` → rechazada (menos claro)

---

### D-007: PSR-4 Autoloading con Composer

**Decisión:** Usaremos Composer para autoloading de clases PHP.

**Configuración (composer.json):**
```json
{
    "autoload": {
        "psr-4": {
            "AI360RealEstate\\": "includes/"
        }
    }
}
```

**Justificación:**
- ✅ Estándar moderno de PHP
- ✅ No necesitamos require/include manual
- ✅ Rendimiento optimizado
- ✅ Facilita testing

**Alternativas consideradas:**
- ❌ Require manual → rechazada (propenso a errores)
- ❌ Autoloader personalizado → rechazada (reinventar la rueda)

---

## Decisiones de Arquitectura

### D-010: Proyecto como Entidad Central

**Decisión:** El "Proyecto" será la entidad central que agrupa usuarios, propiedades y configuración.

**Modelo:**
```
Proyecto
  ├── ID único
  ├── Nombre
  ├── Usuarios asignados (con roles específicos)
  ├── Propiedades asociadas
  ├── Conectores configurados
  ├── Configuración de IA
  └── Metadata
```

**Justificación:**
- ✅ Permite multi-tenant dentro del mismo WordPress
- ✅ Aislamiento de datos entre proyectos
- ✅ Gestión de permisos por proyecto
- ✅ Facilita facturación/auditoría por proyecto

**Alternativas consideradas:**
- ❌ Sin proyectos, todo global → rechazada (no escala)
- ❌ Proyectos como sites WP multisite → rechazada (muy pesado)

---

### D-011: Sincronización Bidireccional

**Decisión:** Los conectores soportarán sincronización bidireccional (lectura y escritura).

**Operaciones:**
- READ: Importar propiedades desde fuente externa
- CREATE: Crear propiedad en fuente externa
- UPDATE: Actualizar propiedad en fuente externa
- DELETE: Eliminar propiedad en fuente externa
- SYNC: Sincronización completa

**Justificación:**
- ✅ Permite optimizar propiedades con IA y publicar
- ✅ Workflow completo: importar → optimizar → publicar
- ✅ Flexibilidad total

**Alternativas consideradas:**
- ❌ Solo lectura → rechazada (insuficiente)
- ❌ Solo escritura → rechazada (no permite importar)

**Implicaciones:**
- Necesitamos resolver conflictos
- Necesitamos prevenir bucles infinitos
- Mayor complejidad técnica

---

### D-012: Estrategias de Resolución de Conflictos

**Decisión:** Implementaremos 4 estrategias configurables de resolución de conflictos.

**Estrategias:**

1. **LOCAL_WINS** - La versión local siempre gana
   - Uso: Cuando el usuario está editando activamente
   
2. **REMOTE_WINS** - La versión remota siempre gana
   - Uso: Cuando la fuente externa es la autoridad

3. **LAST_MODIFIED** - Gana la que se modificó más recientemente
   - Uso: Colaboración entre sistemas

4. **MANUAL_REVIEW** - Marcar para revisión manual
   - Uso: Cuando los cambios son críticos

**Justificación:**
- ✅ Flexibilidad según el caso de uso
- ✅ Previene pérdida de datos
- ✅ Usuario tiene control

**Implementación:**
```php
enum ConflictStrategy: string {
    case LOCAL_WINS = 'local_wins';
    case REMOTE_WINS = 'remote_wins';
    case LAST_MODIFIED = 'last_modified';
    case MANUAL_REVIEW = 'manual_review';
}
```

---

### D-013: Sistema de Hash por Conector

**Decisión:** Cada conector mantendrá su propio hash de la última versión sincronizada.

**Modelo:**
```php
Property {
    id: string
    data: PropertyData
    connector_hashes: {
        'woocommerce': 'abc123...',
        'resales': 'def456...',
        'idealista': 'ghi789...'
    }
}
```

**Justificación:**
- ✅ Previene sincronizaciones innecesarias
- ✅ Detecta cambios por conector
- ✅ Permite sincronización selectiva
- ✅ Previene bucles infinitos

**Alternativas consideradas:**
- ❌ Hash global único → rechazada (no detecta origen del cambio)
- ❌ Timestamp solamente → rechazada (no confiable para cambios)

---

### D-014: Versionado de Propiedades

**Decisión:** Cada modificación significativa creará una nueva versión de la propiedad.

**Tabla:** `ai360re_property_versions`

**Campos:**
- version_id
- property_id
- version_number
- data (JSON completo)
- changed_by (user_id o 'ai' o 'sync')
- change_reason
- created_at

**Justificación:**
- ✅ Auditoría completa
- ✅ Permite rollback
- ✅ Comparación (diff) entre versiones
- ✅ Usuario ve qué cambió la IA

**Alternativas consideradas:**
- ❌ Solo última versión → rechazada (pierde historial)
- ❌ Versiones ilimitadas → rechazada (problemas de espacio)

**Decisión adicional:** Mantener últimas 10 versiones por propiedad por defecto (configurable).

---

### D-015: Estados de Propiedad

**Decisión:** Cada propiedad tiene un estado que refleja su posición en el workflow.

**Estados definidos:**
```php
enum PropertyStatus: string {
    case IMPORTED = 'imported';           // Recién importada
    case OPTIMIZED = 'optimized';         // IA aplicada
    case VALIDATED = 'validated';         // Usuario revisó
    case READY = 'ready';                 // Lista para publicar
    case PUBLISHED = 'published';         // Publicada en destinos
    case SYNCED = 'synced';              // Sincronizada
    case DRAFT = 'draft';                 // Borrador
    case ARCHIVED = 'archived';           // Archivada
}
```

**Transiciones válidas:**
```
imported → optimized → validated → ready → published → synced
                ↓          ↓         ↓
              draft      draft     draft
```

**Justificación:**
- ✅ Claridad en el proceso
- ✅ Previene errores (ej: no publicar sin validar)
- ✅ Facilita filtrado y reporting

---

## Decisiones de Seguridad

### D-020: Roles y Capabilities Personalizados

**Decisión:** Crearemos roles personalizados específicos para el plugin.

**Roles definidos:**
```php
'ai360re_admin'      // Administrador completo del plugin
'ai360re_manager'    // Gestor de proyecto
'ai360re_editor'     // Editor de propiedades
'ai360re_viewer'     // Solo lectura
```

**Capabilities principales:**
```php
'manage_ai360re_projects'
'edit_ai360re_projects'
'delete_ai360re_projects'
'view_ai360re_projects'

'manage_ai360re_properties'
'edit_ai360re_properties'
'publish_ai360re_properties'
'view_ai360re_properties'

'manage_ai360re_connectors'
'sync_ai360re_connectors'

'use_ai360re_ai_features'
```

**Justificación:**
- ✅ Control granular de permisos
- ✅ Integración con WordPress User Management
- ✅ Multiusuario seguro

**Alternativas consideradas:**
- ❌ Usar roles nativos de WP → rechazada (no suficientemente específico)
- ❌ Sistema de permisos propio → rechazada (no integrado con WP)

---

### D-021: Sanitización y Validación

**Decisión:** TODO input del usuario será sanitizado y validado.

**Principios:**
```php
// Input
$input = sanitize_text_field($_POST['title']);

// Output
echo esc_html($title);

// SQL
$wpdb->prepare("SELECT * FROM table WHERE id = %d", $id);
```

**Justificación:**
- ✅ Previene XSS
- ✅ Previene SQL Injection
- ✅ Estándar de WordPress
- ✅ Seguridad por diseño

---

### D-022: API Keys para Conectores

**Decisión:** Las credenciales de conectores se almacenarán encriptadas.

**Método:**
```php
// Almacenamiento
$encrypted = openssl_encrypt($api_key, 'AES-256-CBC', $key, 0, $iv);

// Recuperación
$decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
```

**Justificación:**
- ✅ Credenciales no en texto plano en BD
- ✅ Cumple mejores prácticas de seguridad
- ✅ Protección en caso de leak de BD

**Alternativas consideradas:**
- ❌ Texto plano → rechazada (inseguro)
- ❌ Hash → rechazada (necesitamos recuperar el valor)

---

## Decisiones de Integraciones

### D-030: Provider de IA Principal: 360group.ai

**Decisión:** El provider principal de IA será el servicio 360group.ai

**Justificación:**
- ✅ Servicio interno del ecosistema
- ✅ Optimizado para real estate
- ✅ Control sobre funcionalidades
- ✅ Costos predecibles

**Arquitectura preparada para:**
- OpenAI (futuro)
- Anthropic (futuro)
- Otros providers

**Interface:**
```php
namespace AI360RealEstate\AI;

interface AIProviderInterface {
    public function rewriteTitle(string $original, array $context): string;
    public function generateShortDescription(string $title, array $property): string;
    public function generateLongDescription(string $title, array $property): string;
    public function optimizeSEO(array $property): array;
    public function generateByChannel(array $property, string $channel): array;
}
```

---

### D-031: Conectores Planificados

**Decisión:** Implementaremos conectores para las siguientes plataformas:

**Fase 1 (Core):**
- WooCommerce (lectura/escritura)
- WordPress Posts (lectura/escritura)

**Fase 2 (Externos):**
- Resales V6 API (lectura/escritura)
- Idealista (lectura)
- Fotocasa (lectura)

**Fase 3 (Adicionales):**
- API genérica REST
- CSV Import/Export
- Conectores personalizados

**Justificación:**
- ✅ WooCommerce es prioritario (e-commerce)
- ✅ Resales es fuente común de propiedades
- ✅ Portales inmobiliarios son importantes para distribución

---

### D-032: REST API Pública

**Decisión:** Expondremos REST API pública siguiendo estándares de WordPress.

**Endpoints principales:**
```
GET    /wp-json/ai360re/v1/projects
POST   /wp-json/ai360re/v1/projects
GET    /wp-json/ai360re/v1/projects/{id}
PUT    /wp-json/ai360re/v1/projects/{id}
DELETE /wp-json/ai360re/v1/projects/{id}

GET    /wp-json/ai360re/v1/properties
POST   /wp-json/ai360re/v1/properties
GET    /wp-json/ai360re/v1/properties/{id}
PUT    /wp-json/ai360re/v1/properties/{id}
DELETE /wp-json/ai360re/v1/properties/{id}

POST   /wp-json/ai360re/v1/properties/{id}/optimize
POST   /wp-json/ai360re/v1/properties/{id}/publish
POST   /wp-json/ai360re/v1/connectors/{id}/sync
```

**Autenticación:**
- Application Passwords (WordPress 5.6+)
- OAuth (fase posterior)

**Justificación:**
- ✅ Permite integraciones externas
- ✅ Frontend desacoplado (portal)
- ✅ Estándar de WordPress

---

## Decisiones de Frontend

### D-040: Backend Admin WordPress (Fase 1)

**Decisión:** Inicialmente, la interfaz será el Admin de WordPress con páginas personalizadas.

**Páginas:**
```
Admin Menu:
  ai360 Real Estate
    ├── Dashboard
    ├── Proyectos
    ├── Propiedades
    ├── Conectores
    ├── IA Tools
    └── Configuración
```

**Justificación:**
- ✅ Rápido de implementar
- ✅ Familiar para usuarios WordPress
- ✅ Funcional desde inicio

**Tecnologías:**
- PHP templates
- WordPress Admin styles
- jQuery (estándar de WP)

---

### D-041: Portal Frontend (Fase 2+)

**Decisión:** Implementaremos un portal frontend independiente (Modo Agencia).

**Características:**
- URL: `{site}/ai360-portal/`
- UI moderna y responsive
- Paridad funcional con backend
- CRUD completo de todas las entidades
- Dashboard con métricas

**Justificación:**
- ✅ Mejor UX para usuarios finales
- ✅ Apropiado para clientes de agencia
- ✅ Separación clara admin/cliente

**Tecnologías propuestas:**
- PHP templates modernos
- CSS moderno (Grid, Flexbox)
- JavaScript vanilla o Alpine.js (ligero)
- REST API para datos

**Alternativas consideradas:**
- ❌ React/Vue SPA → rechazada (complejidad inicial alta)
- ❌ Solo backend → rechazada (UX limitada)

---

## Decisiones de Testing

### D-050: PHPUnit para Testing

**Decisión:** Usaremos PHPUnit con WordPress test suite.

**Estructura:**
```
tests/
  ├── bootstrap.php
  ├── Unit/           # Tests unitarios
  │   ├── Core/
  │   ├── Entities/
  │   └── Connectors/
  └── Integration/    # Tests de integración
      ├── API/
      └── Sync/
```

**Justificación:**
- ✅ Estándar para WordPress plugins
- ✅ Permite CI/CD
- ✅ Cobertura de código

---

## Decisiones de Documentación

### D-060: Documentación en /docs/

**Decisión:** Toda la documentación técnica estará en el directorio `/docs/`

**Estructura:**
```
docs/
  ├── ARCHITECTURE_ANALYSIS.md
  ├── TECHNICAL_DECISIONS.md (este archivo)
  ├── DATABASE_SCHEMA.md
  ├── ROADMAP.md
  ├── ENTITIES.md
  ├── CONNECTORS_SPEC.md
  ├── AI_INTEGRATION.md
  └── FRONTEND_PORTAL.md
```

**Justificación:**
- ✅ Documentación accesible
- ✅ Versionada con el código
- ✅ Markdown para fácil edición

---

### D-061: README.md Profesional

**Decisión:** El README.md debe ser profesional y completo.

**Secciones obligatorias:**
- Descripción del proyecto
- Características principales
- Requisitos del sistema
- Instalación
- Configuración básica
- Estructura de directorios
- Links a documentación técnica
- Licencia
- Créditos

---

## Decisiones de Deployment

### D-070: WordPress.org Compatible

**Decisión:** El plugin será compatible con los estándares de WordPress.org

**Requisitos:**
- ✅ GPL v2 o compatible
- ✅ No código ofuscado
- ✅ No dependencias externas no GPL
- ✅ WordPress Coding Standards
- ✅ Sanitización/Validación completa

**Justificación:**
- ✅ Posible distribución en wp.org
- ✅ Mayor confianza
- ✅ Mejores prácticas

---

### D-071: Versionado Semántico

**Decisión:** Usaremos Semantic Versioning (SemVer)

**Formato:** `MAJOR.MINOR.PATCH`

- MAJOR: Cambios incompatibles
- MINOR: Nueva funcionalidad compatible
- PATCH: Bug fixes

**Ejemplo:**
- 0.1.0 - Primera versión funcional
- 0.2.0 - Agregar conector Resales
- 0.2.1 - Bug fix en sync
- 1.0.0 - Versión producción completa

---

## Principios NO NEGOCIABLES

### P-001: Independencia Total

**Principio:** El plugin NO debe depender de otros plugins para funcionar.

**Implicaciones:**
- ❌ No requerir ai360agency-api
- ❌ No requerir ai360chat
- ✅ Incluir todo el código necesario

---

### P-002: Código Propio

**Principio:** TODO el código será escrito específicamente para este proyecto.

**Implicaciones:**
- ❌ No copiar código de otros proyectos
- ✅ Reimplementar patrones (no código)
- ✅ Usar solo librerías públicas GPL compatibles

---

### P-003: Proyecto COMPLETO

**Principio:** El proyecto no se considera terminado hasta que TODO funcione end-to-end.

**Definición de "Terminado":**
- ✅ Todas las funcionalidades implementadas
- ✅ Todos los tests pasan
- ✅ Documentación completa
- ✅ Frontend funcional
- ✅ Backend funcional
- ✅ Conectores funcionando
- ✅ IA integrada
- ✅ Sincronización bidireccional operativa
- ✅ Sin bugs críticos

---

### P-004: PRs Pequeños y Validados

**Principio:** No avanzar a la siguiente fase sin validar la anterior.

**Proceso:**
1. PR pequeño con funcionalidad específica
2. Tests de la funcionalidad
3. Revisión de código
4. Validación manual
5. Merge
6. SOLO ENTONCES → siguiente PR

---

### P-005: Seguridad por Diseño

**Principio:** La seguridad es prioritaria en cada línea de código.

**Checklist obligatorio:**
- ✅ Input sanitizado
- ✅ Output escaped
- ✅ SQL preparado
- ✅ Capabilities verificadas
- ✅ Nonces en formularios
- ✅ CSRF protegido

---

## Resumen de Decisiones Clave

| ID | Decisión | Justificación Principal |
|----|----------|------------------------|
| D-001 | Plugin independiente | Funciona sin dependencias |
| D-002 | Código desde cero | Control total, sin conflictos |
| D-003 | Namespace AI360RealEstate\ | Evita conflictos |
| D-004 | WordPress Coding Standards | Estándar de la comunidad |
| D-005 | Custom Tables | Rendimiento y flexibilidad |
| D-010 | Proyecto como entidad central | Multi-tenant, aislamiento |
| D-011 | Sync bidireccional | Workflow completo |
| D-012 | 4 estrategias de conflictos | Flexibilidad |
| D-013 | Hash por conector | Prevención de bucles |
| D-014 | Versionado de propiedades | Auditoría y rollback |
| D-015 | Estados definidos | Claridad en workflow |
| D-020 | Roles personalizados | Control granular |
| D-030 | Provider 360group.ai | Servicio optimizado |
| D-031 | Múltiples conectores | Flexibilidad de fuentes |
| D-040 | Admin WP inicial | Rápido de implementar |
| D-041 | Portal frontend posterior | Mejor UX |
| D-050 | PHPUnit testing | Estándar WordPress |

---

## Próximos Pasos

Este documento de decisiones técnicas sirve como guía para:
1. Implementación de código
2. Revisiones de código
3. Resolución de dudas técnicas
4. Onboarding de nuevos desarrolladores

Todas las decisiones aquí están sujetas a revisión si aparece nueva información, pero los cambios deben ser documentados con la misma rigurosidad.

---

**Documento creado para:** PR-00 - Análisis de Arquitectura y Documentación Técnica Fundacional  
**Última actualización:** 2025-12-18  
**Versión:** 1.0
