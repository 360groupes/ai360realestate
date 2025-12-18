# Roadmap Completo - ai360realestate

## Propósito del Documento

Este documento define el roadmap completo del proyecto ai360realestate, desde el PR-00 (documentación fundacional) hasta el proyecto completamente funcional en producción.

---

## Definición de "Proyecto Terminado"

El proyecto se considera **COMPLETO** cuando:

✅ **Funcionalidad Core**
- [x] Todos los CRUDs implementados (Proyectos, Propiedades, Conectores, Usuarios)
- [ ] Sistema de sincronización bidireccional funcional
- [ ] Integración con IA operativa
- [ ] Versionado de propiedades funcional
- [ ] Workflow de estados implementado

✅ **Conectores**
- [ ] Conector WooCommerce (lectura/escritura)
- [ ] Conector WordPress Posts (lectura/escritura)
- [ ] Conector Resales V6 (lectura/escritura)
- [ ] Al menos 3 conectores completamente operativos

✅ **Interfaces de Usuario**
- [ ] Backend Admin WordPress completo
- [ ] Frontend Portal (Modo Agencia) operativo
- [ ] Paridad funcional entre backend y frontend

✅ **Calidad y Seguridad**
- [ ] Tests unitarios con >80% cobertura
- [ ] Tests de integración para funcionalidades críticas
- [ ] Sin vulnerabilidades de seguridad conocidas
- [ ] Sin bugs críticos o bloqueantes

✅ **Documentación**
- [ ] Documentación técnica completa
- [ ] Documentación de usuario (manual)
- [ ] README.md profesional
- [ ] CHANGELOG.md actualizado

✅ **Deployment**
- [ ] Instalación limpia funcional
- [ ] Actualización de versiones funcional
- [ ] Desinstalación limpia funcional
- [ ] Compatible con WordPress.org standards

---

## Fases del Proyecto

### FASE 0: Fundamentos (PR-00)
**Objetivo:** Establecer la base documental y arquitectónica

**Estado:** 🔄 En Progreso

**PRs:**
- **PR-00**: Documentación técnica fundacional ✅ (este PR)

---

### FASE 1: Core y Base de Datos (PR-01 a PR-03)
**Objetivo:** Implementar el núcleo del plugin y el esquema de base de datos

**Duración estimada:** 2-3 semanas

#### PR-01: Plugin Base y Autoloading
**Alcance:**
- Crear archivo principal `ai360realestate.php`
- Configurar `composer.json` con PSR-4 autoloading
- Implementar clase Core principal
- Sistema de hooks y activación/desactivación
- Estructura de directorios completa

**Criterios de validación:**
- ✅ Plugin se activa sin errores
- ✅ Autoloading funciona correctamente
- ✅ No hay conflictos con otros plugins
- ✅ Hooks de activación ejecutan correctamente

**Archivos a crear:**
```
ai360realestate.php
uninstall.php
composer.json
includes/Core/Plugin.php
includes/Core/Activator.php
includes/Core/Deactivator.php
```

---

#### PR-02: Schema de Base de Datos
**Alcance:**
- Implementar creación de todas las tablas
- Sistema de migraciones
- Versionado de schema
- Scripts de cleanup para desinstalación

**Criterios de validación:**
- ✅ Todas las 8 tablas se crean correctamente
- ✅ Índices se aplican
- ✅ Migraciones funcionan
- ✅ Desinstalación limpia (elimina todo)

**Archivos a crear:**
```
includes/Core/Database.php
includes/Core/Migrations.php
includes/Core/Schema/*.php (una clase por tabla)
```

---

#### PR-03: Sistema de Logging y Auditoría
**Alcance:**
- Implementar Logger centralizado
- Sistema de Audit Log
- Handlers para diferentes tipos de logs
- Configuración de retención

**Criterios de validación:**
- ✅ Logs se escriben correctamente
- ✅ Audit trail captura acciones
- ✅ Limpieza automática funciona
- ✅ Performance no se ve afectado

**Archivos a crear:**
```
includes/Logging/Logger.php
includes/Logging/AuditLogger.php
includes/Logging/LogHandler.php
```

---

### FASE 2: Entidades Core (PR-04 a PR-07)
**Objetivo:** Implementar las entidades principales del sistema

**Duración estimada:** 3-4 semanas

#### PR-04: Entidad Proyecto
**Alcance:**
- Clase Project
- CRUD completo de proyectos
- Validaciones
- Tests unitarios

**Criterios de validación:**
- ✅ Crear proyecto funciona
- ✅ Leer proyecto funciona
- ✅ Actualizar proyecto funciona
- ✅ Eliminar proyecto funciona (soft delete)
- ✅ Tests pasan (>80% cobertura)

**Archivos a crear:**
```
includes/Entities/Project.php
includes/Entities/ProjectRepository.php
includes/Entities/ProjectValidator.php
tests/Unit/Entities/ProjectTest.php
```

---

#### PR-05: Gestión de Usuarios en Proyectos
**Alcance:**
- Clase ProjectUser (relación)
- Asignación de usuarios a proyectos
- Sistema de roles por proyecto
- Capabilities personalizadas
- Tests unitarios

**Criterios de validación:**
- ✅ Asignar usuario a proyecto funciona
- ✅ Roles se aplican correctamente
- ✅ Capabilities se verifican
- ✅ Usuario puede tener diferentes roles en diferentes proyectos

**Archivos a crear:**
```
includes/Entities/ProjectUser.php
includes/Auth/RoleManager.php
includes/Auth/CapabilityChecker.php
tests/Unit/Auth/RoleManagerTest.php
```

---

#### PR-06: Entidad Propiedad
**Alcance:**
- Clase Property
- CRUD completo de propiedades
- Formato normalizado
- Estados (status)
- Tests unitarios

**Criterios de validación:**
- ✅ CRUD completo funciona
- ✅ Validaciones funcionan
- ✅ Estados se aplican correctamente
- ✅ Tests pasan

**Archivos a crear:**
```
includes/Entities/Property.php
includes/Entities/PropertyRepository.php
includes/Entities/PropertyValidator.php
includes/Entities/PropertyStatus.php (enum)
tests/Unit/Entities/PropertyTest.php
```

---

#### PR-07: Versionado de Propiedades
**Alcance:**
- Sistema de versionado automático
- Comparación entre versiones (diff)
- Rollback a versiones anteriores
- Limpieza automática de versiones antiguas
- Tests unitarios

**Criterios de validación:**
- ✅ Nueva versión se crea en cada update significativo
- ✅ Diff muestra cambios correctamente
- ✅ Rollback restaura versión anterior
- ✅ Limpieza automática funciona

**Archivos a crear:**
```
includes/Entities/PropertyVersion.php
includes/Entities/VersionManager.php
includes/Entities/DiffGenerator.php
tests/Unit/Entities/VersionManagerTest.php
```

---

### FASE 3: Sistema de Conectores (PR-08 a PR-12)
**Objetivo:** Implementar el sistema de conectores bidireccionales

**Duración estimada:** 4-5 semanas

#### PR-08: Interface de Conectores
**Alcance:**
- Interface ConnectorInterface
- Clase base AbstractConnector
- Registro de conectores
- Factory pattern
- Tests

**Criterios de validación:**
- ✅ Interface define contrato claro
- ✅ Clase base proporciona funcionalidad común
- ✅ Registro funciona
- ✅ Factory crea instancias correctamente

**Archivos a crear:**
```
includes/Connectors/ConnectorInterface.php
includes/Connectors/AbstractConnector.php
includes/Connectors/ConnectorRegistry.php
includes/Connectors/ConnectorFactory.php
tests/Unit/Connectors/ConnectorTest.php
```

---

#### PR-09: Conector WooCommerce
**Alcance:**
- Implementar WooCommerceConnector
- Lectura de productos
- Escritura de productos
- Mapeo bidireccional
- Tests de integración

**Criterios de validación:**
- ✅ Lee productos de WooCommerce
- ✅ Crea productos en WooCommerce
- ✅ Actualiza productos en WooCommerce
- ✅ Elimina productos en WooCommerce
- ✅ Mapeo es correcto en ambas direcciones

**Archivos a crear:**
```
includes/Connectors/WooCommerceConnector.php
includes/Connectors/Mappers/WooCommerceMapper.php
tests/Integration/Connectors/WooCommerceConnectorTest.php
```

---

#### PR-10: Conector WordPress Posts
**Alcance:**
- Implementar WordPressConnector
- Lectura de posts
- Escritura de posts
- Custom Post Type para propiedades
- Tests de integración

**Criterios de validación:**
- ✅ Lee posts/CPT de WordPress
- ✅ Crea posts/CPT en WordPress
- ✅ Actualiza posts existentes
- ✅ Mapeo correcto

**Archivos a crear:**
```
includes/Connectors/WordPressConnector.php
includes/Connectors/Mappers/WordPressMapper.php
tests/Integration/Connectors/WordPressConnectorTest.php
```

---

#### PR-11: Motor de Sincronización
**Alcance:**
- SyncEngine para orquestar sincronizaciones
- Detección de cambios (hashes)
- Resolución de conflictos (4 estrategias)
- Prevención de bucles
- Cola de sincronización
- Tests

**Criterios de validación:**
- ✅ Detecta cambios correctamente
- ✅ Resuelve conflictos según estrategia
- ✅ No crea bucles infinitos
- ✅ Cola procesa correctamente
- ✅ Performance es aceptable

**Archivos a crear:**
```
includes/Sync/SyncEngine.php
includes/Sync/ChangeDetector.php
includes/Sync/ConflictResolver.php
includes/Sync/LoopPrevention.php
includes/Sync/SyncQueue.php
tests/Unit/Sync/SyncEngineTest.php
```

---

#### PR-12: Conector Resales V6
**Alcance:**
- Implementar ResalesConnector
- Cliente HTTP para Resales API
- Autenticación
- Mapeo de datos Resales ↔ Property
- Tests (mock de API)

**Criterios de validación:**
- ✅ Conecta con Resales API
- ✅ Autentica correctamente
- ✅ Lee propiedades de Resales
- ✅ Escribe propiedades a Resales
- ✅ Maneja errores de API

**Archivos a crear:**
```
includes/Connectors/ResalesConnector.php
includes/Connectors/Clients/ResalesClient.php
includes/Connectors/Mappers/ResalesMapper.php
tests/Integration/Connectors/ResalesConnectorTest.php
```

---

### FASE 4: Integración con IA (PR-13 a PR-16)
**Objetivo:** Implementar el sistema de providers de IA

**Duración estimada:** 3-4 semanas

#### PR-13: Interface de Providers de IA
**Alcance:**
- Interface AIProviderInterface
- Clase base AbstractAIProvider
- Factory pattern
- Registro de providers
- Tests

**Criterios de validación:**
- ✅ Interface define operaciones de IA
- ✅ Factory funciona
- ✅ Registro de providers operativo

**Archivos a crear:**
```
includes/AI/AIProviderInterface.php
includes/AI/AbstractAIProvider.php
includes/AI/AIProviderFactory.php
includes/AI/AIProviderRegistry.php
tests/Unit/AI/AIProviderTest.php
```

---

#### PR-14: Provider 360group.ai
**Alcance:**
- Implementar AI360Provider
- Cliente HTTP para 360group.ai
- Autenticación con API key
- Implementar todas las funciones de IA para real estate
- Cache de respuestas
- Tests (mock de API)

**Criterios de validación:**
- ✅ Conecta con 360group.ai
- ✅ Todas las funciones de IA funcionan:
  - Reescritura de título
  - Descripción corta
  - Descripción larga
  - Optimización SEO
  - Generación por canal
- ✅ Cache funciona
- ✅ Maneja errores

**Archivos a crear:**
```
includes/AI/Providers/AI360Provider.php
includes/AI/Clients/AI360Client.php
includes/AI/AICache.php
tests/Integration/AI/AI360ProviderTest.php
```

---

#### PR-15: Sistema de Tareas de IA
**Alcance:**
- Cola de tareas de IA
- Procesamiento asíncrono
- Sistema de prioridades
- Tracking de tokens y costos
- Reintentos en caso de fallo
- Tests

**Criterios de validación:**
- ✅ Encola tareas correctamente
- ✅ Procesa tareas en orden de prioridad
- ✅ Trackea tokens y costos
- ✅ Reintenta en caso de fallo
- ✅ No bloquea la UI

**Archivos a crear:**
```
includes/AI/TaskQueue.php
includes/AI/TaskProcessor.php
includes/AI/TaskScheduler.php
tests/Unit/AI/TaskQueueTest.php
```

---

#### PR-16: Workflow de Optimización con IA
**Alcance:**
- Integrar IA con versionado de propiedades
- Workflow: optimizar → comparar → aprobar/rechazar
- UI para revisión de cambios
- Tests de integración

**Criterios de validación:**
- ✅ Optimización crea nueva versión
- ✅ Usuario puede ver diff
- ✅ Usuario puede aprobar o rechazar
- ✅ Rollback funciona si rechaza

**Archivos a crear:**
```
includes/AI/OptimizationWorkflow.php
includes/Admin/Pages/AIReviewPage.php
tests/Integration/AI/OptimizationWorkflowTest.php
```

---

### FASE 5: REST API (PR-17 a PR-18)
**Objetivo:** Exponer API REST completa

**Duración estimada:** 2 semanas

#### PR-17: Endpoints Core
**Alcance:**
- Namespace `/wp-json/ai360re/v1/`
- Endpoints para Proyectos
- Endpoints para Propiedades
- Endpoints para Usuarios de Proyecto
- Autenticación
- Validación
- Tests

**Criterios de validación:**
- ✅ Todos los endpoints CRUD funcionan
- ✅ Autenticación funciona
- ✅ Permisos se verifican
- ✅ Validación funciona
- ✅ Respuestas son consistentes

**Archivos a crear:**
```
includes/API/Router.php
includes/API/Controllers/ProjectsController.php
includes/API/Controllers/PropertiesController.php
includes/API/Controllers/UsersController.php
includes/API/Middleware/AuthMiddleware.php
tests/Integration/API/ProjectsAPITest.php
```

---

#### PR-18: Endpoints de Operaciones
**Alcance:**
- Endpoint de sincronización
- Endpoint de optimización con IA
- Endpoint de publicación
- Endpoint de estadísticas
- Tests

**Criterios de validación:**
- ✅ Sincronización via API funciona
- ✅ Optimización via API funciona
- ✅ Publicación via API funciona
- ✅ Estadísticas son precisas

**Archivos a crear:**
```
includes/API/Controllers/SyncController.php
includes/API/Controllers/AIController.php
includes/API/Controllers/PublishController.php
includes/API/Controllers/StatsController.php
tests/Integration/API/OperationsAPITest.php
```

---

### FASE 6: Backend Admin (PR-19 a PR-21)
**Objetivo:** Implementar interfaz de administración en WordPress

**Duración estimada:** 3-4 semanas

#### PR-19: Menú y Dashboard
**Alcance:**
- Menú principal del plugin
- Dashboard con estadísticas
- Widgets de resumen
- Gráficos básicos
- Estilos admin

**Criterios de validación:**
- ✅ Menú aparece correctamente
- ✅ Dashboard muestra datos reales
- ✅ Widgets funcionan
- ✅ Responsive

**Archivos a crear:**
```
includes/Admin/AdminMenu.php
includes/Admin/Pages/DashboardPage.php
includes/Admin/Widgets/*.php
assets/css/admin.css
assets/js/admin.js
```

---

#### PR-20: Páginas CRUD
**Alcance:**
- Página de listado de proyectos
- Página de edición de proyecto
- Página de listado de propiedades
- Página de edición de propiedad
- Gestión de conectores
- Formularios con validación

**Criterios de validación:**
- ✅ Listados funcionan con paginación y filtros
- ✅ Formularios de edición funcionan
- ✅ Validación client-side y server-side
- ✅ CRUD completo desde UI

**Archivos a crear:**
```
includes/Admin/Pages/ProjectsListPage.php
includes/Admin/Pages/ProjectEditPage.php
includes/Admin/Pages/PropertiesListPage.php
includes/Admin/Pages/PropertyEditPage.php
includes/Admin/Pages/ConnectorsPage.php
includes/Admin/Forms/*.php
```

---

#### PR-21: Página de Configuración
**Alcance:**
- Configuración general del plugin
- Configuración de IA
- Configuración de sincronización
- Configuración de logs
- Exportar/Importar configuración

**Criterios de validación:**
- ✅ Todas las opciones se guardan
- ✅ Valores por defecto funcionan
- ✅ Exportar/Importar funciona

**Archivos a crear:**
```
includes/Admin/Pages/SettingsPage.php
includes/Admin/Settings/*.php
```

---

### FASE 7: Frontend Portal (PR-22 a PR-24)
**Objetivo:** Implementar portal frontend (Modo Agencia)

**Duración estimada:** 4-5 semanas

#### PR-22: Portal Base y Autenticación
**Alcance:**
- URL `/ai360-portal/`
- Sistema de routing
- Login/Logout
- Página de inicio
- Layout responsive

**Criterios de validación:**
- ✅ Portal accesible en URL configurada
- ✅ Login funciona
- ✅ Solo usuarios autorizados acceden
- ✅ Layout responsive

**Archivos a crear:**
```
public/portal.php
public/templates/layout.php
public/templates/login.php
public/assets/css/portal.css
public/assets/js/portal.js
includes/Frontend/PortalRouter.php
includes/Frontend/Auth.php
```

---

#### PR-23: Páginas del Portal
**Alcance:**
- Dashboard
- Mis Proyectos
- Mis Propiedades
- Optimizar con IA
- Sincronización
- Paridad con backend admin

**Criterios de validación:**
- ✅ Todas las funciones disponibles en portal
- ✅ UI moderna y fácil de usar
- ✅ Funcionalidad igual que admin

**Archivos a crear:**
```
public/templates/dashboard.php
public/templates/projects/*.php
public/templates/properties/*.php
public/templates/ai/*.php
public/templates/sync/*.php
```

---

#### PR-24: Componentes Interactivos
**Alcance:**
- Componentes JavaScript reutilizables
- Formularios dinámicos
- Modales
- Notificaciones
- Drag & drop para imágenes

**Criterios de validación:**
- ✅ Componentes son reutilizables
- ✅ UX es fluida
- ✅ No hay bugs visuales

**Archivos a crear:**
```
public/assets/js/components/*.js
public/assets/css/components/*.css
```

---

### FASE 8: Testing y QA (PR-25)
**Objetivo:** Asegurar calidad del código

**Duración estimada:** 2 semanas

#### PR-25: Cobertura de Tests Completa
**Alcance:**
- Tests unitarios para toda la lógica de negocio
- Tests de integración para flujos completos
- Tests end-to-end para funcionalidad crítica
- Alcanzar >80% de cobertura de código

**Criterios de validación:**
- ✅ Cobertura >80%
- ✅ Todos los tests pasan
- ✅ No hay tests flakey
- ✅ CI/CD configurado

**Archivos a crear:**
```
tests/Unit/**/*.php
tests/Integration/**/*.php
tests/E2E/**/*.php
phpunit.xml
.github/workflows/tests.yml
```

---

### FASE 9: Documentación y Lanzamiento (PR-26+)
**Objetivo:** Documentación de usuario y preparación para producción

**Duración estimada:** 1-2 semanas

#### PR-26: Documentación de Usuario
**Alcance:**
- Manual de usuario
- Guía de inicio rápido
- FAQ
- Troubleshooting
- Videos tutoriales (opcional)

**Criterios de validación:**
- ✅ Manual completo
- ✅ Guías fáciles de seguir
- ✅ FAQ responde preguntas comunes

**Archivos a crear:**
```
docs/user/USER_MANUAL.md
docs/user/QUICK_START.md
docs/user/FAQ.md
docs/user/TROUBLESHOOTING.md
```

---

#### PR-27: Preparación para Producción
**Alcance:**
- CHANGELOG.md completo
- README.md finalizado
- Verificación WordPress.org standards
- Optimización de rendimiento
- Minificación de assets
- Version 1.0.0

**Criterios de validación:**
- ✅ CHANGELOG actualizado
- ✅ README profesional
- ✅ Cumple standards de wp.org
- ✅ Performance optimizado
- ✅ Sin warnings/notices

---

## Criterios de Validación por Fase

### FASE 0 ✅
- [x] Documentación técnica completa
- [x] Arquitectura definida
- [x] Roadmap creado

### FASE 1
- [ ] Plugin se activa sin errores
- [ ] Base de datos se crea correctamente
- [ ] Desinstalación limpia funciona
- [ ] Logging operativo

### FASE 2
- [ ] Todas las entidades tienen CRUD completo
- [ ] Versionado funciona
- [ ] Tests unitarios pasan
- [ ] Validaciones funcionan

### FASE 3
- [ ] Al menos 3 conectores funcionando
- [ ] Sincronización bidireccional operativa
- [ ] Resolución de conflictos funciona
- [ ] No hay bucles infinitos

### FASE 4
- [ ] IA integrada y funcional
- [ ] Todas las funciones de IA operativas
- [ ] Versionado con IA funciona
- [ ] Usuario puede aprobar/rechazar cambios

### FASE 5
- [ ] REST API completa
- [ ] Todos los endpoints funcionan
- [ ] Autenticación operativa
- [ ] Documentación de API

### FASE 6
- [ ] Backend admin completo
- [ ] Todos los CRUDs accesibles via UI
- [ ] Dashboard funcional
- [ ] Configuración operativa

### FASE 7
- [ ] Portal frontend operativo
- [ ] Paridad con backend
- [ ] UX moderna
- [ ] Responsive

### FASE 8
- [ ] Cobertura >80%
- [ ] Todos los tests pasan
- [ ] CI/CD configurado
- [ ] Sin bugs conocidos

### FASE 9
- [ ] Documentación de usuario completa
- [ ] Listo para producción
- [ ] Version 1.0.0 released

---

## Estimación de Tiempo Total

| Fase | Duración | PRs |
|------|----------|-----|
| FASE 0 | 1 semana | 1 |
| FASE 1 | 2-3 semanas | 3 |
| FASE 2 | 3-4 semanas | 4 |
| FASE 3 | 4-5 semanas | 5 |
| FASE 4 | 3-4 semanas | 4 |
| FASE 5 | 2 semanas | 2 |
| FASE 6 | 3-4 semanas | 3 |
| FASE 7 | 4-5 semanas | 3 |
| FASE 8 | 2 semanas | 1 |
| FASE 9 | 1-2 semanas | 2+ |
| **TOTAL** | **25-35 semanas** | **28+ PRs** |

**Nota:** Con 1 desarrollador full-time, aproximadamente 6-9 meses.

---

## Estrategia de Desarrollo

### Principios
1. **PRs pequeños**: Cada PR debe ser revisable en <1 hora
2. **Validación rigurosa**: No avanzar sin validar PR anterior
3. **Tests obligatorios**: Cada PR con funcionalidad debe incluir tests
4. **Documentación continua**: Actualizar docs con cada cambio
5. **Código limpio**: Seguir WordPress Coding Standards siempre

### Workflow por PR
1. Crear rama desde main
2. Implementar funcionalidad
3. Escribir tests
4. Ejecutar tests
5. Validar manualmente
6. Crear PR
7. Revisión de código
8. Merge a main
9. Deploy a staging
10. Validación en staging
11. **SOLO ENTONCES** → siguiente PR

---

## Hitos Importantes

### 🎯 Hito 1: MVP Funcional (Fin de FASE 3)
**Funcionalidad:**
- CRUD de proyectos y propiedades
- 3 conectores operativos
- Sincronización básica

**Validación:**
- Usuario puede importar propiedades
- Usuario puede editarlas
- Usuario puede sincronizar con WooCommerce

---

### 🎯 Hito 2: IA Integrada (Fin de FASE 4)
**Funcionalidad:**
- Optimización con IA funcional
- Workflow de revisión
- Versionado completo

**Validación:**
- Usuario puede optimizar propiedad con IA
- Usuario puede revisar y aprobar/rechazar
- Usuario puede hacer rollback

---

### 🎯 Hito 3: Backend Completo (Fin de FASE 6)
**Funcionalidad:**
- Admin de WordPress completo
- Todas las funciones accesibles
- Configuración operativa

**Validación:**
- Usuario puede hacer TODO desde admin WP

---

### 🎯 Hito 4: Proyecto Completo (Fin de FASE 9)
**Funcionalidad:**
- TODO implementado
- Frontend y Backend
- Documentación completa
- Listo para producción

**Validación:**
- Cumple TODOS los criterios de "Proyecto Terminado"

---

## Próximos Pasos Inmediatos

Después de aprobar PR-00:

1. **PR-01**: Crear estructura base del plugin
2. **PR-02**: Implementar schema de base de datos
3. **PR-03**: Sistema de logging

**Meta:** Tener la base sólida en 2-3 semanas.

---

## Riesgos y Mitigaciones

### Riesgo 1: Complejidad de Sincronización Bidireccional
**Mitigación:**
- Testing exhaustivo
- Implementar prevención de bucles desde el inicio
- Validar con datos reales en staging

### Riesgo 2: Dependencia de APIs Externas
**Mitigación:**
- Mocking en tests
- Manejo robusto de errores
- Timeouts y reintentos
- Fallbacks cuando sea posible

### Riesgo 3: Performance con Muchas Propiedades
**Mitigación:**
- Índices optimizados en BD
- Paginación en queries
- Cache donde sea apropiado
- Procesamiento asíncrono

### Riesgo 4: Cambios en APIs de Terceros
**Mitigación:**
- Versionado de APIs
- Abstracción mediante interfaces
- Monitoreo de deprecations

---

**Documento creado para:** PR-00 - Análisis de Arquitectura y Documentación Técnica Fundacional  
**Última actualización:** 2025-12-18  
**Versión:** 1.0
