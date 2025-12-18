# ai360realestate

**Plugin WordPress para Gestión Inteligente de Propiedades Inmobiliarias**

![Version](https://img.shields.io/badge/version-0.1.0--dev-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/license-GPL%20v2-green)

---

## 📋 Descripción

**ai360realestate** es un plugin de WordPress profesional diseñado para agencias inmobiliarias que necesitan gestionar, optimizar y sincronizar propiedades inmobiliarias con múltiples plataformas, potenciado por Inteligencia Artificial.

### Características Principales

✅ **Gestión Multi-Proyecto**: Organiza propiedades en proyectos aislados con usuarios y permisos específicos  
✅ **Optimización con IA**: Genera y optimiza títulos, descripciones y contenido SEO automáticamente  
✅ **Sincronización Bidireccional**: Conecta con WooCommerce, WordPress Posts, Resales, Idealista, Fotocasa y más  
✅ **Versionado Completo**: Historial completo de cambios con posibilidad de rollback  
✅ **Portal Frontend**: Interfaz moderna para clientes (Modo Agencia)  
✅ **Resolución de Conflictos**: Sistema inteligente para manejar sincronizaciones complejas  
✅ **Workflow de Estados**: Proceso claro desde importación hasta publicación  
✅ **Auditoría Completa**: Registro detallado de todas las acciones

---

## 🏗️ Arquitectura

### Visión de Alto Nivel

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
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Custom Database Tables                         │
└─────────────────────────────────────────────────────────────┘
```

### Principios de Diseño

1. **Independencia Total**: No depende de otros plugins para funcionar
2. **Código Propio**: Todo implementado desde cero, sin código copiado
3. **Extensible**: Arquitectura modular preparada para crecer
4. **Seguro**: Seguridad por diseño en cada componente
5. **Performante**: Optimizado para manejar miles de propiedades

---

## 📦 Requisitos del Sistema

### Mínimos

- **WordPress**: 6.0 o superior
- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior / MariaDB 10.3 o superior
- **Memoria PHP**: 128 MB (recomendado 256 MB)
- **Espacio en disco**: 50 MB

### Recomendados

- **WordPress**: 6.4 o superior
- **PHP**: 8.1 o superior
- **MySQL**: 8.0 o superior
- **Memoria PHP**: 512 MB
- **HTTPS**: Certificado SSL válido
- **Cron**: Cron de WordPress habilitado

### Plugins Opcionales (Integraciones)

- **WooCommerce**: 7.0+ (para conector WooCommerce)
- **PHP Extensions**: curl, json, mbstring, openssl

---

## 🚀 Instalación

### Instalación Estándar

1. **Descargar** el plugin desde el repositorio
2. **Subir** a `/wp-content/plugins/ai360realestate/`
3. **Activar** desde el panel de WordPress
4. **Configurar** en `ai360 Real Estate > Configuración`

```bash
# Vía WP-CLI
wp plugin install ai360realestate.zip --activate
```

### Instalación para Desarrollo

```bash
# Clonar repositorio
git clone https://github.com/360groupes/ai360realestate.git
cd ai360realestate

# Instalar dependencias
composer install

# Crear enlace simbólico (opcional)
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/ai360realestate

# Activar plugin
wp plugin activate ai360realestate
```

---

## ⚙️ Configuración Básica

### 1. Configuración Inicial

Después de activar el plugin:

1. Navega a **ai360 Real Estate > Configuración**
2. Configura tu **API Key de 360group.ai** (para funciones de IA)
3. Crea tu primer **Proyecto**
4. Asigna **usuarios** al proyecto

### 2. Conectores

Para sincronizar con sistemas externos:

1. Ve a **ai360 Real Estate > Conectores**
2. Agrega un nuevo conector (WooCommerce, Resales, etc.)
3. Configura las credenciales
4. Prueba la conexión
5. Configura la sincronización (dirección, estrategia de conflictos)

### 3. Optimización con IA

Para usar funciones de IA:

1. Asegúrate de tener configurada la API Key
2. Ve a una propiedad
3. Click en **Optimizar con IA**
4. Selecciona las optimizaciones deseadas
5. Revisa y aprueba los cambios

---

## 📁 Estructura de Directorios

```
ai360realestate/
├── ai360realestate.php          # Archivo principal del plugin
├── uninstall.php                # Limpieza al desinstalar
├── composer.json                # Autoload PSR-4 y dependencias
├── README.md                    # Este archivo
├── CHANGELOG.md                 # Historial de cambios
│
├── docs/                        # 📚 Documentación técnica
│   ├── ARCHITECTURE_ANALYSIS.md    # Análisis de arquitectura
│   ├── TECHNICAL_DECISIONS.md      # Decisiones técnicas
│   ├── DATABASE_SCHEMA.md          # Esquema de base de datos
│   ├── ROADMAP.md                  # Roadmap completo
│   ├── ENTITIES.md                 # Especificación de entidades
│   ├── CONNECTORS_SPEC.md          # Especificación de conectores
│   ├── AI_INTEGRATION.md           # Integración con IA
│   └── FRONTEND_PORTAL.md          # Portal frontend
│
├── includes/                    # 💻 Código PHP principal
│   ├── Core/                    # Núcleo del plugin
│   │   ├── Plugin.php
│   │   ├── Activator.php
│   │   ├── Deactivator.php
│   │   ├── Database.php
│   │   └── Migrations.php
│   │
│   ├── Entities/                # Entidades del dominio
│   │   ├── Project.php
│   │   ├── ProjectUser.php
│   │   ├── Property.php
│   │   ├── PropertyVersion.php
│   │   └── Connector.php
│   │
│   ├── Auth/                    # Autenticación y autorización
│   │   ├── RoleManager.php
│   │   └── CapabilityChecker.php
│   │
│   ├── Connectors/              # Conectores bidireccionales
│   │   ├── ConnectorInterface.php
│   │   ├── AbstractConnector.php
│   │   ├── WooCommerceConnector.php
│   │   ├── WordPressConnector.php
│   │   ├── ResalesConnector.php
│   │   └── Mappers/
│   │
│   ├── AI/                      # Providers de IA
│   │   ├── AIProviderInterface.php
│   │   ├── AbstractAIProvider.php
│   │   ├── Providers/
│   │   │   └── AI360Provider.php
│   │   ├── TaskQueue.php
│   │   └── OptimizationWorkflow.php
│   │
│   ├── Sync/                    # Motor de sincronización
│   │   ├── SyncEngine.php
│   │   ├── ChangeDetector.php
│   │   ├── ConflictResolver.php
│   │   └── LoopPrevention.php
│   │
│   ├── API/                     # REST API endpoints
│   │   ├── Router.php
│   │   └── Controllers/
│   │
│   ├── Admin/                   # Backend WordPress Admin
│   │   ├── AdminMenu.php
│   │   ├── Pages/
│   │   └── Forms/
│   │
│   ├── Frontend/                # Portal Frontend
│   │   ├── PortalRouter.php
│   │   ├── Auth.php
│   │   └── Controllers/
│   │
│   └── Logging/                 # Logs y auditoría
│       ├── Logger.php
│       └── AuditLogger.php
│
├── public/                      # 🎨 Frontend (Portal Agencia)
│   ├── templates/               # Plantillas PHP
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── portal.php
│
├── assets/                      # 🎨 Assets del admin
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
│
├── languages/                   # 🌍 Traducciones
│   └── ai360realestate.pot
│
└── tests/                       # 🧪 Tests PHPUnit
    ├── bootstrap.php
    ├── Unit/
    └── Integration/
```

---

## 📖 Documentación

### Documentación Técnica

Toda la documentación técnica está en el directorio `/docs/`:

- **[Análisis de Arquitectura](docs/ARCHITECTURE_ANALYSIS.md)**: Patrones y decisiones arquitectónicas
- **[Decisiones Técnicas](docs/TECHNICAL_DECISIONS.md)**: Registro de todas las decisiones tomadas
- **[Esquema de Base de Datos](docs/DATABASE_SCHEMA.md)**: Estructura completa de tablas
- **[Roadmap](docs/ROADMAP.md)**: Plan completo del proyecto (PR-00 a PR-26+)
- **[Entidades](docs/ENTITIES.md)**: Especificación de todas las entidades
- **[Conectores](docs/CONNECTORS_SPEC.md)**: Sistema de conectores bidireccionales
- **[IA](docs/AI_INTEGRATION.md)**: Integración con providers de IA
- **[Portal Frontend](docs/FRONTEND_PORTAL.md)**: Especificación del portal

### API REST

Endpoints disponibles en `/wp-json/ai360re/v1/`:

```
GET    /projects              # Listar proyectos
POST   /projects              # Crear proyecto
GET    /projects/{id}         # Obtener proyecto
PUT    /projects/{id}         # Actualizar proyecto
DELETE /projects/{id}         # Eliminar proyecto

GET    /properties            # Listar propiedades
POST   /properties            # Crear propiedad
GET    /properties/{id}       # Obtener propiedad
PUT    /properties/{id}       # Actualizar propiedad
DELETE /properties/{id}       # Eliminar propiedad

POST   /properties/{id}/optimize    # Optimizar con IA
POST   /properties/{id}/publish     # Publicar propiedad
POST   /connectors/{id}/sync        # Sincronizar conector
```

Autenticación mediante **Application Passwords** de WordPress.

---

## 🗺️ Roadmap

### Estado Actual: **FASE 0** ✅

- [x] **PR-00**: Documentación técnica fundacional

### Próximas Fases

- **FASE 1** (PR-01 a PR-03): Core y Base de Datos
- **FASE 2** (PR-04 a PR-07): Entidades Core
- **FASE 3** (PR-08 a PR-12): Sistema de Conectores
- **FASE 4** (PR-13 a PR-16): Integración con IA
- **FASE 5** (PR-17 a PR-18): REST API
- **FASE 6** (PR-19 a PR-21): Backend Admin
- **FASE 7** (PR-22 a PR-24): Frontend Portal
- **FASE 8** (PR-25): Testing y QA
- **FASE 9** (PR-26+): Documentación y Lanzamiento

**Ver roadmap completo**: [docs/ROADMAP.md](docs/ROADMAP.md)

---

## 🤝 Contribución

Este es un proyecto en desarrollo activo. Las contribuciones son bienvenidas siguiendo estos principios:

### Principios NO NEGOCIABLES

1. ✅ **Independencia total**: No depender de otros plugins
2. ✅ **Código propio**: Todo escrito específicamente para este proyecto
3. ✅ **Proyecto completo**: No se considera terminado hasta que TODO funcione
4. ✅ **PRs pequeños**: Cambios incrementales y validados
5. ✅ **Seguridad primero**: Código seguro en cada línea

### Proceso de Contribución

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

### Standards

- **Coding**: WordPress Coding Standards
- **PHPDoc**: Documentación completa en el código
- **Tests**: Tests unitarios obligatorios para nueva funcionalidad
- **Security**: Sanitización y validación siempre

---

## 🧪 Testing

### Ejecutar Tests

```bash
# Tests unitarios
composer test

# Tests con cobertura
composer test:coverage

# Tests específicos
./vendor/bin/phpunit tests/Unit/Entities/PropertyTest.php
```

### Estructura de Tests

```
tests/
├── Unit/                    # Tests unitarios
│   ├── Core/
│   ├── Entities/
│   └── Connectors/
└── Integration/             # Tests de integración
    ├── API/
    └── Sync/
```

---

## 🔒 Seguridad

### Reportar Vulnerabilidades

Si encuentras una vulnerabilidad de seguridad, por favor **NO** abras un issue público. Envía un email a: **security@360group.es**

### Prácticas de Seguridad

- ✅ Sanitización de todos los inputs
- ✅ Escape de todos los outputs
- ✅ Prepared statements en SQL
- ✅ Nonces en todos los formularios
- ✅ Verificación de capabilities
- ✅ Credenciales encriptadas en BD

---

## 📝 Changelog

### [0.1.0-dev] - 2025-12-18

#### Added
- Documentación técnica fundacional completa
- Estructura de directorios definida
- Roadmap completo del proyecto
- Especificaciones de arquitectura

**Ver changelog completo**: [CHANGELOG.md](CHANGELOG.md)

---

## 📄 Licencia

Este proyecto está licenciado bajo **GPL v2 or later**.

```
ai360realestate - WordPress Plugin for Real Estate Management
Copyright (C) 2025 360group

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
```

---

## 👥 Créditos

### Desarrollado por

**360group** - [https://360group.es](https://360group.es)

### Referencias Conceptuales

Este proyecto se inspiró conceptualmente (sin copiar código) en:
- ai360agency-api - Patrones de Provider Factory
- ai360chat - Sistema de conectores externos
- dashboard-wp - Integración con WordPress/WooCommerce

### Tecnologías Utilizadas

- [WordPress](https://wordpress.org/) - CMS base
- [Composer](https://getcomposer.org/) - Autoloading PSR-4
- [PHPUnit](https://phpunit.de/) - Testing framework
- [Alpine.js](https://alpinejs.dev/) - Framework JS ligero (frontend)

---

## 📞 Soporte

### Documentación

- **Documentación técnica**: [/docs/](docs/)
- **FAQ**: Próximamente
- **Troubleshooting**: Próximamente

### Contacto

- **Website**: [https://360group.es](https://360group.es)
- **Email**: soporte@360group.es
- **GitHub Issues**: [Issues](https://github.com/360groupes/ai360realestate/issues)

---

## ⭐ Reconocimientos

Gracias a todos los que contribuyen a hacer este proyecto realidad.

---

**¿Te gusta el proyecto? ¡Dale una ⭐ en GitHub!**
