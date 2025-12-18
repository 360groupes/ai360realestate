# Portal Frontend - ai360realestate

## Propósito del Documento

Este documento define la especificación completa del portal frontend (Modo Agencia) de ai360realestate, que proporciona una interfaz moderna para clientes y usuarios finales con paridad funcional con el backend de WordPress.

---

## Visión General

### Concepto: Modo Agencia

El **Portal Frontend** es una interfaz web independiente diseñada para que las agencias inmobiliarias puedan ofrecer a sus clientes acceso directo a las funcionalidades del plugin sin necesidad de acceder al admin de WordPress.

### Características Principales

- ✅ **Interfaz moderna y limpia**: UI/UX optimizada para usuarios finales
- ✅ **Paridad funcional**: Todas las funciones disponibles en el admin
- ✅ **Responsive**: Funciona en desktop, tablet y móvil
- ✅ **Multiproyecto**: Cada usuario ve solo sus proyectos
- ✅ **Tiempo real**: Actualizaciones automáticas vía REST API
- ✅ **White-label**: Personalizable con branding de la agencia

---

## Arquitectura del Portal

### Stack Tecnológico

**Backend:**
- PHP (WordPress)
- REST API personalizada

**Frontend:**
- HTML5
- CSS3 (Grid, Flexbox, Variables CSS)
- JavaScript Vanilla o Alpine.js (ligero, ~15KB)
- Sin dependencias pesadas (React, Vue)

**Comunicación:**
- REST API (`/wp-json/ai360re/v1/`)
- JSON para intercambio de datos
- Authentication via WordPress Application Passwords

---

## URL y Routing

### URL Base

```
https://example.com/ai360-portal/
```

Configurable mediante opciones del plugin.

### Sistema de Routing

```php
namespace AI360RealEstate\Frontend;

class PortalRouter {
    private array $routes = [];
    
    public function __construct() {
        $this->registerRoutes();
    }
    
    private function registerRoutes(): void {
        $this->routes = [
            '' => 'DashboardController@index',
            'login' => 'AuthController@login',
            'logout' => 'AuthController@logout',
            'projects' => 'ProjectsController@index',
            'projects/create' => 'ProjectsController@create',
            'projects/{id}' => 'ProjectsController@show',
            'projects/{id}/edit' => 'ProjectsController@edit',
            'properties' => 'PropertiesController@index',
            'properties/create' => 'PropertiesController@create',
            'properties/{id}' => 'PropertiesController@show',
            'properties/{id}/edit' => 'PropertiesController@edit',
            'properties/{id}/optimize' => 'AIController@optimize',
            'connectors' => 'ConnectorsController@index',
            'connectors/{id}/sync' => 'SyncController@sync',
            'settings' => 'SettingsController@index',
        ];
    }
    
    public function dispatch(string $path): void {
        $route = $this->matchRoute($path);
        
        if ($route === null) {
            $this->render404();
            return;
        }
        
        [$controller, $method] = explode('@', $route['handler']);
        $controllerClass = "AI360RealEstate\\Frontend\\Controllers\\{$controller}";
        
        $instance = new $controllerClass();
        $instance->$method($route['params']);
    }
    
    private function matchRoute(string $path): ?array {
        foreach ($this->routes as $pattern => $handler) {
            $regex = $this->patternToRegex($pattern);
            
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches); // Remover match completo
                
                return [
                    'handler' => $handler,
                    'params' => $matches,
                ];
            }
        }
        
        return null;
    }
    
    private function patternToRegex(string $pattern): string {
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = preg_replace('/\{([a-z]+)\}/', '([^\/]+)', $pattern);
        return '/^' . $pattern . '$/';
    }
}
```

---

## Autenticación

### Sistema de Login

```php
namespace AI360RealEstate\Frontend;

class Auth {
    /**
     * Verificar si usuario está autenticado
     */
    public static function check(): bool {
        return is_user_logged_in();
    }
    
    /**
     * Obtener usuario actual
     */
    public static function user(): ?\WP_User {
        return wp_get_current_user();
    }
    
    /**
     * Verificar acceso al portal
     */
    public static function canAccessPortal(): bool {
        if (!self::check()) {
            return false;
        }
        
        $user = self::user();
        
        // Verificar que tenga al menos un proyecto asignado
        $projects = ProjectUser::getProjectsByUser($user->ID);
        
        return count($projects) > 0;
    }
    
    /**
     * Redirigir a login si no autenticado
     */
    public static function requireAuth(): void {
        if (!self::check()) {
            wp_redirect(self::getLoginUrl());
            exit;
        }
        
        if (!self::canAccessPortal()) {
            wp_die(__('No tienes acceso al portal.', 'ai360realestate'));
        }
    }
    
    /**
     * Obtener URL de login
     */
    public static function getLoginUrl(): string {
        return home_url('/ai360-portal/login/');
    }
    
    /**
     * Logout
     */
    public static function logout(): void {
        wp_logout();
        wp_redirect(self::getLoginUrl());
        exit;
    }
}
```

---

## Layout Base

### Estructura HTML

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($title); ?> - ai360 Real Estate</title>
    
    <!-- Estilos del portal -->
    <link rel="stylesheet" href="<?php echo AI360RE_URL; ?>public/assets/css/portal.css">
    
    <!-- Alpine.js para interactividad (opcional) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="ai360re-portal">
    <!-- Header -->
    <header class="portal-header">
        <div class="container">
            <div class="header-brand">
                <img src="<?php echo esc_url($branding['logo']); ?>" alt="Logo">
                <h1>Portal Inmobiliario</h1>
            </div>
            
            <nav class="header-nav">
                <a href="/ai360-portal/">Dashboard</a>
                <a href="/ai360-portal/projects/">Proyectos</a>
                <a href="/ai360-portal/properties/">Propiedades</a>
                <a href="/ai360-portal/connectors/">Conectores</a>
            </nav>
            
            <div class="header-user">
                <span><?php echo esc_html($user->display_name); ?></span>
                <a href="/ai360-portal/logout/">Salir</a>
            </div>
        </div>
    </header>
    
    <!-- Contenido principal -->
    <main class="portal-main">
        <div class="container">
            <?php echo $content; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="portal-footer">
        <div class="container">
            <p>Powered by ai360 Real Estate</p>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="<?php echo AI360RE_URL; ?>public/assets/js/portal.js"></script>
</body>
</html>
```

---

## Páginas del Portal

### 1. Dashboard

**Ruta:** `/ai360-portal/`

**Funcionalidad:**
- Resumen de actividad reciente
- Estadísticas generales
- Acciones rápidas
- Notificaciones

**Template:**
```php
<!-- public/templates/dashboard.php -->

<div class="dashboard">
    <!-- Header de página -->
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Bienvenido, <?php echo esc_html($user->display_name); ?></p>
    </div>
    
    <!-- Métricas principales -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-value"><?php echo $stats['total_projects']; ?></div>
            <div class="metric-label">Proyectos</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo $stats['total_properties']; ?></div>
            <div class="metric-label">Propiedades</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo $stats['optimized_this_month']; ?></div>
            <div class="metric-label">Optimizadas este mes</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo $stats['synced_today']; ?></div>
            <div class="metric-label">Sincronizadas hoy</div>
        </div>
    </div>
    
    <!-- Actividad reciente -->
    <div class="recent-activity">
        <h2>Actividad Reciente</h2>
        <div class="activity-list">
            <?php foreach ($recent_activity as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="icon-<?php echo esc_attr($activity['type']); ?>"></i>
                    </div>
                    <div class="activity-content">
                        <p><?php echo esc_html($activity['description']); ?></p>
                        <span class="activity-time"><?php echo esc_html($activity['time_ago']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Acciones rápidas -->
    <div class="quick-actions">
        <h2>Acciones Rápidas</h2>
        <div class="actions-grid">
            <a href="/ai360-portal/properties/create/" class="action-button">
                <i class="icon-plus"></i>
                Nueva Propiedad
            </a>
            <a href="/ai360-portal/connectors/" class="action-button">
                <i class="icon-sync"></i>
                Sincronizar
            </a>
            <a href="/ai360-portal/properties/" class="action-button">
                <i class="icon-list"></i>
                Ver Propiedades
            </a>
        </div>
    </div>
</div>
```

---

### 2. Lista de Proyectos

**Ruta:** `/ai360-portal/projects/`

**Funcionalidad:**
- Listar proyectos del usuario
- Filtros y búsqueda
- Crear nuevo proyecto
- Acciones por proyecto

**Componente interactivo (Alpine.js):**
```html
<div x-data="projectsList()">
    <!-- Header con búsqueda y filtros -->
    <div class="list-header">
        <h1>Mis Proyectos</h1>
        <div class="list-actions">
            <input 
                type="search" 
                placeholder="Buscar proyectos..." 
                x-model="search"
                @input.debounce.300ms="fetchProjects()">
            
            <select x-model="statusFilter" @change="fetchProjects()">
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="archived">Archivados</option>
            </select>
            
            <a href="/ai360-portal/projects/create/" class="btn btn-primary">
                Nuevo Proyecto
            </a>
        </div>
    </div>
    
    <!-- Grid de proyectos -->
    <div class="projects-grid">
        <template x-for="project in projects" :key="project.id">
            <div class="project-card">
                <div class="project-header">
                    <h3 x-text="project.name"></h3>
                    <span class="project-status" :class="'status-' + project.status" x-text="project.status"></span>
                </div>
                
                <div class="project-stats">
                    <div class="stat">
                        <span class="stat-value" x-text="project.properties_count"></span>
                        <span class="stat-label">Propiedades</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value" x-text="project.users_count"></span>
                        <span class="stat-label">Usuarios</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value" x-text="project.connectors_count"></span>
                        <span class="stat-label">Conectores</span>
                    </div>
                </div>
                
                <div class="project-actions">
                    <a :href="'/ai360-portal/projects/' + project.id" class="btn btn-sm">Ver</a>
                    <a :href="'/ai360-portal/projects/' + project.id + '/edit'" class="btn btn-sm">Editar</a>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Paginación -->
    <div class="pagination" x-show="totalPages > 1">
        <button @click="previousPage()" :disabled="currentPage === 1">Anterior</button>
        <span x-text="'Página ' + currentPage + ' de ' + totalPages"></span>
        <button @click="nextPage()" :disabled="currentPage === totalPages">Siguiente</button>
    </div>
</div>

<script>
function projectsList() {
    return {
        projects: [],
        search: '',
        statusFilter: '',
        currentPage: 1,
        totalPages: 1,
        
        init() {
            this.fetchProjects();
        },
        
        async fetchProjects() {
            const params = new URLSearchParams({
                search: this.search,
                status: this.statusFilter,
                page: this.currentPage,
                per_page: 12
            });
            
            const response = await fetch(`/wp-json/ai360re/v1/projects?${params}`, {
                credentials: 'include'
            });
            
            const data = await response.json();
            this.projects = data.projects;
            this.totalPages = data.total_pages;
        },
        
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.fetchProjects();
            }
        },
        
        previousPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.fetchProjects();
            }
        }
    };
}
</script>
```

---

### 3. Lista de Propiedades

**Ruta:** `/ai360-portal/properties/`

**Funcionalidad:**
- Listar propiedades del proyecto
- Filtros avanzados (tipo, precio, ubicación, estado)
- Búsqueda
- Vista en grid o lista
- Acciones por propiedad

**Template con filtros:**
```html
<div x-data="propertiesList()">
    <!-- Filtros -->
    <div class="filters-panel">
        <div class="filter-group">
            <label>Proyecto</label>
            <select x-model="filters.project_id" @change="fetchProperties()">
                <option value="">Todos los proyectos</option>
                <template x-for="project in projects">
                    <option :value="project.id" x-text="project.name"></option>
                </template>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Estado</label>
            <select x-model="filters.status" @change="fetchProperties()">
                <option value="">Todos</option>
                <option value="draft">Borrador</option>
                <option value="imported">Importada</option>
                <option value="optimized">Optimizada</option>
                <option value="validated">Validada</option>
                <option value="ready">Lista</option>
                <option value="published">Publicada</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Precio</label>
            <div class="price-range">
                <input type="number" placeholder="Mín" x-model="filters.price_min">
                <input type="number" placeholder="Máx" x-model="filters.price_max">
            </div>
        </div>
        
        <div class="filter-group">
            <label>Tipo</label>
            <select x-model="filters.type" @change="fetchProperties()">
                <option value="">Todos</option>
                <option value="apartment">Apartamento</option>
                <option value="house">Casa</option>
                <option value="villa">Villa</option>
                <option value="commercial">Comercial</option>
            </select>
        </div>
        
        <button @click="resetFilters()" class="btn btn-secondary">Limpiar Filtros</button>
    </div>
    
    <!-- Lista de propiedades -->
    <div class="properties-list" :class="viewMode">
        <template x-for="property in properties" :key="property.id">
            <div class="property-card">
                <div class="property-image" :style="'background-image: url(' + property.main_image + ')'">
                    <span class="property-status" :class="'status-' + property.status" x-text="property.status"></span>
                </div>
                
                <div class="property-content">
                    <h3 x-text="property.title"></h3>
                    <p class="property-location" x-text="property.location"></p>
                    <p class="property-price" x-text="formatPrice(property.price, property.currency)"></p>
                    
                    <div class="property-specs">
                        <span x-show="property.bedrooms">
                            <i class="icon-bed"></i> <span x-text="property.bedrooms"></span>
                        </span>
                        <span x-show="property.bathrooms">
                            <i class="icon-bath"></i> <span x-text="property.bathrooms"></span>
                        </span>
                        <span x-show="property.surface">
                            <i class="icon-area"></i> <span x-text="property.surface + ' m²'"></span>
                        </span>
                    </div>
                </div>
                
                <div class="property-actions">
                    <a :href="'/ai360-portal/properties/' + property.id" class="btn btn-sm">Ver</a>
                    <a :href="'/ai360-portal/properties/' + property.id + '/edit'" class="btn btn-sm">Editar</a>
                    <button @click="optimizeProperty(property.id)" class="btn btn-sm btn-primary">
                        Optimizar con IA
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
```

---

### 4. Editor de Propiedad

**Ruta:** `/ai360-portal/properties/{id}/edit`

**Funcionalidad:**
- Formulario completo de edición
- Preview en tiempo real
- Guardado automático
- Validación
- Galería de imágenes
- Optimización con IA

**Componente de editor:**
```html
<div x-data="propertyEditor(<?php echo $property_id; ?>)">
    <form @submit.prevent="saveProperty()">
        <!-- Tabs de secciones -->
        <div class="tabs">
            <button type="button" @click="currentTab = 'basic'" :class="{'active': currentTab === 'basic'}">
                Básico
            </button>
            <button type="button" @click="currentTab = 'details'" :class="{'active': currentTab === 'details'}">
                Detalles
            </button>
            <button type="button" @click="currentTab = 'location'" :class="{'active': currentTab === 'location'}">
                Ubicación
            </button>
            <button type="button" @click="currentTab = 'media'" :class="{'active': currentTab === 'media'}">
                Imágenes
            </button>
            <button type="button" @click="currentTab = 'seo'" :class="{'active': currentTab === 'seo'}">
                SEO
            </button>
        </div>
        
        <!-- Tab: Básico -->
        <div x-show="currentTab === 'basic'" class="tab-content">
            <div class="form-group">
                <label>Título</label>
                <input type="text" x-model="property.title" required>
                <button type="button" @click="optimizeTitle()" class="btn-inline">
                    <i class="icon-ai"></i> Optimizar con IA
                </button>
            </div>
            
            <div class="form-group">
                <label>Descripción Corta</label>
                <textarea x-model="property.short_description" rows="3"></textarea>
                <button type="button" @click="generateShortDesc()" class="btn-inline">
                    <i class="icon-ai"></i> Generar con IA
                </button>
            </div>
            
            <div class="form-group">
                <label>Descripción Completa</label>
                <textarea x-model="property.long_description" rows="10"></textarea>
                <button type="button" @click="generateLongDesc()" class="btn-inline">
                    <i class="icon-ai"></i> Generar con IA
                </button>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Tipo</label>
                    <select x-model="property.type">
                        <option value="apartment">Apartamento</option>
                        <option value="house">Casa</option>
                        <option value="villa">Villa</option>
                        <option value="commercial">Comercial</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Precio</label>
                    <input type="number" x-model="property.price" step="0.01">
                </div>
                
                <div class="form-group">
                    <label>Moneda</label>
                    <select x-model="property.currency">
                        <option value="EUR">EUR</option>
                        <option value="USD">USD</option>
                        <option value="GBP">GBP</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Otros tabs... -->
        
        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="saving">
                <span x-show="!saving">Guardar</span>
                <span x-show="saving">Guardando...</span>
            </button>
            
            <button type="button" @click="optimizeAll()" class="btn btn-secondary">
                Optimizar Todo con IA
            </button>
            
            <a :href="'/ai360-portal/properties/' + property.id" class="btn">Cancelar</a>
        </div>
    </form>
    
    <!-- Preview en sidebar -->
    <aside class="preview-sidebar">
        <h3>Preview</h3>
        <div class="property-preview">
            <img :src="property.main_image" alt="Preview">
            <h4 x-text="property.title"></h4>
            <p x-text="property.short_description"></p>
            <p class="price" x-text="formatPrice(property.price, property.currency)"></p>
        </div>
    </aside>
</div>
```

---

### 5. Optimización con IA

**Ruta:** `/ai360-portal/properties/{id}/optimize`

**Funcionalidad:**
- Selección de tareas de IA
- Progreso en tiempo real
- Comparación antes/después
- Aprobar/Rechazar cambios

**Template:**
```html
<div x-data="aiOptimization(<?php echo $property_id; ?>)">
    <div class="optimization-wizard">
        <!-- Paso 1: Seleccionar tareas -->
        <div x-show="step === 1" class="wizard-step">
            <h2>Selecciona las optimizaciones</h2>
            
            <div class="tasks-selection">
                <label>
                    <input type="checkbox" x-model="selectedTasks" value="rewrite_title">
                    Reescribir título
                </label>
                <label>
                    <input type="checkbox" x-model="selectedTasks" value="generate_short_desc">
                    Generar descripción corta
                </label>
                <label>
                    <input type="checkbox" x-model="selectedTasks" value="generate_long_desc">
                    Generar descripción larga
                </label>
                <label>
                    <input type="checkbox" x-model="selectedTasks" value="optimize_seo">
                    Optimizar SEO
                </label>
            </div>
            
            <button @click="startOptimization()" class="btn btn-primary">
                Iniciar Optimización
            </button>
        </div>
        
        <!-- Paso 2: Procesando -->
        <div x-show="step === 2" class="wizard-step">
            <h2>Optimizando...</h2>
            
            <div class="progress-bar">
                <div class="progress-fill" :style="'width: ' + progress + '%'"></div>
            </div>
            
            <p x-text="currentTask"></p>
        </div>
        
        <!-- Paso 3: Revisar cambios -->
        <div x-show="step === 3" class="wizard-step">
            <h2>Revisa los cambios</h2>
            
            <div class="comparison">
                <div class="comparison-before">
                    <h3>Antes</h3>
                    <div class="content-box">
                        <h4>Título</h4>
                        <p x-text="before.title"></p>
                        
                        <h4>Descripción Corta</h4>
                        <p x-text="before.short_description"></p>
                        
                        <h4>Descripción Larga</h4>
                        <p x-text="before.long_description"></p>
                    </div>
                </div>
                
                <div class="comparison-after">
                    <h3>Después</h3>
                    <div class="content-box">
                        <h4>Título</h4>
                        <p x-text="after.title"></p>
                        
                        <h4>Descripción Corta</h4>
                        <p x-text="after.short_description"></p>
                        
                        <h4>Descripción Larga</h4>
                        <p x-text="after.long_description"></p>
                    </div>
                </div>
            </div>
            
            <div class="wizard-actions">
                <button @click="approveChanges()" class="btn btn-success">
                    Aprobar Cambios
                </button>
                <button @click="rejectChanges()" class="btn btn-danger">
                    Rechazar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
```

---

## Estilos CSS

### Variables CSS

```css
:root {
    /* Colores primarios */
    --primary-color: #0073aa;
    --primary-hover: #005a87;
    --secondary-color: #23282d;
    
    /* Colores de estado */
    --success-color: #46b450;
    --warning-color: #ffb900;
    --error-color: #dc3232;
    --info-color: #00a0d2;
    
    /* Grises */
    --gray-50: #f9f9f9;
    --gray-100: #f0f0f0;
    --gray-200: #e0e0e0;
    --gray-300: #ccc;
    --gray-700: #555;
    --gray-900: #23282d;
    
    /* Tipografía */
    --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    --font-size-base: 16px;
    --line-height-base: 1.6;
    
    /* Espaciado */
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
    
    /* Border radius */
    --radius-sm: 4px;
    --radius-md: 8px;
    --radius-lg: 12px;
    
    /* Sombras */
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
    --shadow-lg: 0 10px 20px rgba(0,0,0,0.15);
}
```

### Componentes Base

```css
.portal-main {
    min-height: calc(100vh - 120px);
    padding: var(--spacing-xl) 0;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--spacing-lg);
}

.btn {
    display: inline-block;
    padding: var(--spacing-sm) var(--spacing-lg);
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: var(--radius-sm);
    border: none;
    cursor: pointer;
    font-size: var(--font-size-base);
    transition: background 0.2s;
}

.btn:hover {
    background: var(--primary-hover);
}

.btn-secondary {
    background: var(--gray-300);
    color: var(--gray-900);
}

/* Cards */
.card {
    background: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    padding: var(--spacing-lg);
}

/* Grid */
.grid {
    display: grid;
    gap: var(--spacing-lg);
}

.grid-2 {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.grid-3 {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

/* Forms */
.form-group {
    margin-bottom: var(--spacing-lg);
}

.form-group label {
    display: block;
    margin-bottom: var(--spacing-sm);
    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: var(--spacing-sm);
    border: 1px solid var(--gray-300);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-base);
}
```

---

## JavaScript / Alpine.js

### API Client

```javascript
// public/assets/js/api-client.js

class AI360APIClient {
    constructor(baseUrl = '/wp-json/ai360re/v1') {
        this.baseUrl = baseUrl;
        this.nonce = window.ai360re?.nonce || '';
    }
    
    async request(endpoint, options = {}) {
        const url = this.baseUrl + endpoint;
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce,
            },
            credentials: 'include',
        };
        
        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            throw new Error(`API request failed: ${response.statusText}`);
        }
        
        return await response.json();
    }
    
    // Projects
    async getProjects(params = {}) {
        const query = new URLSearchParams(params).toString();
        return await this.request(`/projects?${query}`);
    }
    
    async getProject(id) {
        return await this.request(`/projects/${id}`);
    }
    
    async createProject(data) {
        return await this.request(`/projects`, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }
    
    async updateProject(id, data) {
        return await this.request(`/projects/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }
    
    // Properties
    async getProperties(params = {}) {
        const query = new URLSearchParams(params).toString();
        return await this.request(`/properties?${query}`);
    }
    
    async getProperty(id) {
        return await this.request(`/properties/${id}`);
    }
    
    async updateProperty(id, data) {
        return await this.request(`/properties/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }
    
    // AI Operations
    async optimizeProperty(id, tasks) {
        return await this.request(`/properties/${id}/optimize`, {
            method: 'POST',
            body: JSON.stringify({ tasks }),
        });
    }
    
    async compareVersions(id) {
        return await this.request(`/properties/${id}/versions/compare`);
    }
    
    async approveOptimization(id) {
        return await this.request(`/properties/${id}/approve`, {
            method: 'POST',
        });
    }
    
    async rejectOptimization(id) {
        return await this.request(`/properties/${id}/reject`, {
            method: 'POST',
        });
    }
}

// Instancia global
window.ai360API = new AI360APIClient();
```

---

## Responsive Design

### Breakpoints

```css
/* Mobile first approach */

/* Tablets */
@media (min-width: 768px) {
    .container {
        padding: 0 var(--spacing-xl);
    }
}

/* Desktop */
@media (min-width: 1024px) {
    .grid-2 {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .grid-3 {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Large screens */
@media (min-width: 1440px) {
    .container {
        max-width: 1400px;
    }
}
```

---

## Accesibilidad

### Requisitos WCAG 2.1 AA

- ✅ Contraste mínimo 4.5:1
- ✅ Navegación por teclado completa
- ✅ ARIA labels en componentes interactivos
- ✅ Focus visible en todos los elementos
- ✅ Textos alternativos en imágenes
- ✅ Formularios con labels asociados

---

## Próximas Mejoras

### Fase Futura
- PWA (Progressive Web App)
- Notificaciones push
- Modo offline
- Exportación de reportes PDF
- Integración con CRM
- Chat en tiempo real

---

**Documento creado para:** PR-00 - Análisis de Arquitectura y Documentación Técnica Fundacional  
**Última actualización:** 2025-12-18  
**Versión:** 1.0
