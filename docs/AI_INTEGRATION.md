# Integración con IA - ai360realestate

## Propósito del Documento

Este documento define la especificación completa de integración con sistemas de Inteligencia Artificial para optimización automática de propiedades inmobiliarias.

---

## Arquitectura de IA

### Principios de Diseño

1. **Provider Agnostic**: Sistema desacoplado que soporta múltiples providers
2. **Asíncrono**: Procesamiento en segundo plano para no bloquear UI
3. **Versionado**: Cada optimización crea una nueva versión
4. **Reversible**: Usuario puede aprobar, rechazar o hacer rollback
5. **Trackeable**: Registro completo de costos, tokens y resultados

---

## Interface de Providers

### AIProviderInterface

```php
namespace AI360RealEstate\AI;

interface AIProviderInterface {
    /**
     * Reescribir título de propiedad
     * 
     * @param string $original Título original
     * @param array $context Contexto adicional (ubicación, tipo, etc.)
     * @return string Título optimizado
     */
    public function rewriteTitle(string $original, array $context): string;
    
    /**
     * Generar descripción corta
     * 
     * @param string $title Título de la propiedad
     * @param array $property Datos completos de la propiedad
     * @return string Descripción corta (2-3 líneas)
     */
    public function generateShortDescription(string $title, array $property): string;
    
    /**
     * Generar descripción larga
     * 
     * @param string $title Título de la propiedad
     * @param array $property Datos completos de la propiedad
     * @return string Descripción larga (varios párrafos)
     */
    public function generateLongDescription(string $title, array $property): string;
    
    /**
     * Optimizar SEO
     * 
     * @param array $property Datos completos de la propiedad
     * @return array Datos SEO optimizados
     */
    public function optimizeSEO(array $property): array;
    
    /**
     * Generar contenido por canal
     * 
     * @param array $property Datos de la propiedad
     * @param string $channel Canal de destino (idealista, fotocasa, etc.)
     * @return array Contenido adaptado al canal
     */
    public function generateByChannel(array $property, string $channel): array;
    
    /**
     * Traducir contenido
     * 
     * @param string $content Contenido a traducir
     * @param string $from Idioma origen (ISO 639-1)
     * @param string $to Idioma destino (ISO 639-1)
     * @return string Contenido traducido
     */
    public function translate(string $content, string $from, string $to): string;
    
    /**
     * Obtener metadatos del provider
     * 
     * @return array
     */
    public function getMetadata(): array;
    
    /**
     * Verificar disponibilidad del provider
     * 
     * @return bool
     */
    public function isAvailable(): bool;
}
```

---

## Clase Base Abstracta

### AbstractAIProvider

```php
namespace AI360RealEstate\AI;

abstract class AbstractAIProvider implements AIProviderInterface {
    protected string $providerName;
    protected array $config;
    protected Logger $logger;
    protected AICache $cache;
    protected TokenTracker $tokenTracker;
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->logger = new Logger("AI-{$this->providerName}");
        $this->cache = new AICache();
        $this->tokenTracker = new TokenTracker();
    }
    
    abstract protected function doRequest(string $prompt, array $options): AIResponse;
    
    protected function request(string $prompt, array $options = []): AIResponse {
        // Verificar cache
        $cacheKey = $this->getCacheKey($prompt, $options);
        if ($this->cache->has($cacheKey)) {
            $this->logger->debug("Cache hit for prompt");
            return $this->cache->get($cacheKey);
        }
        
        // Hacer request
        $this->logger->info("Making AI request");
        $response = $this->doRequest($prompt, $options);
        
        // Trackear tokens y costo
        $this->tokenTracker->track([
            'provider' => $this->providerName,
            'tokens' => $response->getTokensUsed(),
            'cost' => $response->getCost(),
            'timestamp' => time()
        ]);
        
        // Guardar en cache
        $this->cache->set($cacheKey, $response);
        
        return $response;
    }
    
    protected function buildPrompt(string $template, array $variables): string {
        $prompt = $template;
        foreach ($variables as $key => $value) {
            $prompt = str_replace("{{$key}}", $value, $prompt);
        }
        return $prompt;
    }
    
    protected function getCacheKey(string $prompt, array $options): string {
        return md5($prompt . json_encode($options));
    }
    
    public function isAvailable(): bool {
        try {
            // Hacer un request de prueba pequeño
            $response = $this->request("Test", ['max_tokens' => 10]);
            return $response->isSuccessful();
        } catch (\Exception $e) {
            $this->logger->error("Provider not available: " . $e->getMessage());
            return false;
        }
    }
}
```

---

## Provider 360group.ai

### AI360Provider

```php
namespace AI360RealEstate\AI\Providers;

class AI360Provider extends AbstractAIProvider {
    protected string $providerName = '360group';
    private AI360Client $client;
    
    public function __construct(array $config) {
        parent::__construct($config);
        
        $this->client = new AI360Client(
            $config['api_url'] ?? 'https://api.360group.ai',
            $config['api_key']
        );
    }
    
    protected function doRequest(string $prompt, array $options): AIResponse {
        return $this->client->chat($prompt, $options);
    }
    
    public function rewriteTitle(string $original, array $context): string {
        $prompt = $this->buildPrompt($this->getPromptTemplate('rewrite_title'), [
            'original' => $original,
            'type' => $context['type'] ?? 'property',
            'city' => $context['city'] ?? '',
            'features' => $this->formatFeatures($context['features'] ?? [])
        ]);
        
        $response = $this->request($prompt, [
            'max_tokens' => 100,
            'temperature' => 0.7
        ]);
        
        return trim($response->getContent());
    }
    
    public function generateShortDescription(string $title, array $property): string {
        $prompt = $this->buildPrompt($this->getPromptTemplate('short_description'), [
            'title' => $title,
            'type' => $property['type'] ?? 'property',
            'price' => $property['price'] ?? '',
            'location' => $this->formatLocation($property),
            'specs' => $this->formatSpecifications($property['specifications'] ?? []),
            'features' => $this->formatFeatures($property['features'] ?? [])
        ]);
        
        $response = $this->request($prompt, [
            'max_tokens' => 150,
            'temperature' => 0.8
        ]);
        
        return trim($response->getContent());
    }
    
    public function generateLongDescription(string $title, array $property): string {
        $prompt = $this->buildPrompt($this->getPromptTemplate('long_description'), [
            'title' => $title,
            'type' => $property['type'] ?? 'property',
            'price' => $property['price'] ?? '',
            'location' => $this->formatLocation($property),
            'specs' => $this->formatSpecifications($property['specifications'] ?? []),
            'features' => $this->formatFeatures($property['features'] ?? []),
            'surroundings' => $this->formatSurroundings($property)
        ]);
        
        $response = $this->request($prompt, [
            'max_tokens' => 800,
            'temperature' => 0.8
        ]);
        
        return trim($response->getContent());
    }
    
    public function optimizeSEO(array $property): array {
        $prompt = $this->buildPrompt($this->getPromptTemplate('optimize_seo'), [
            'title' => $property['title'] ?? '',
            'description' => $property['short_description'] ?? '',
            'location' => $this->formatLocation($property),
            'type' => $property['type'] ?? 'property'
        ]);
        
        $response = $this->request($prompt, [
            'max_tokens' => 300,
            'temperature' => 0.5,
            'response_format' => 'json'
        ]);
        
        return json_decode($response->getContent(), true);
    }
    
    public function generateByChannel(array $property, string $channel): array {
        $channelSpecs = $this->getChannelSpecifications($channel);
        
        $prompt = $this->buildPrompt($this->getPromptTemplate('by_channel'), [
            'channel' => $channel,
            'max_title_length' => $channelSpecs['max_title_length'],
            'max_description_length' => $channelSpecs['max_description_length'],
            'tone' => $channelSpecs['tone'],
            'title' => $property['title'] ?? '',
            'description' => $property['short_description'] ?? '',
            'specs' => $this->formatSpecifications($property['specifications'] ?? [])
        ]);
        
        $response = $this->request($prompt, [
            'max_tokens' => 500,
            'temperature' => 0.7,
            'response_format' => 'json'
        ]);
        
        return json_decode($response->getContent(), true);
    }
    
    public function translate(string $content, string $from, string $to): string {
        $prompt = $this->buildPrompt($this->getPromptTemplate('translate'), [
            'content' => $content,
            'from' => $from,
            'to' => $to
        ]);
        
        $response = $this->request($prompt, [
            'max_tokens' => strlen($content) * 2, // Aproximación
            'temperature' => 0.3 // Más conservador para traducción
        ]);
        
        return trim($response->getContent());
    }
    
    private function getPromptTemplate(string $type): string {
        $templates = [
            'rewrite_title' => <<<PROMPT
Eres un experto en marketing inmobiliario. Reescribe el siguiente título de propiedad de forma atractiva y profesional.

Título original: {original}
Tipo de propiedad: {type}
Ciudad: {city}
Características destacadas: {features}

Reglas:
- Máximo 80 caracteres
- Incluir ubicación si es relevante
- Destacar características únicas
- Evitar emojis y mayúsculas innecesarias
- Ser preciso y descriptivo

Responde SOLO con el título reescrito, sin explicaciones adicionales.
PROMPT,
            
            'short_description' => <<<PROMPT
Eres un experto en copywriting inmobiliario. Genera una descripción corta y atractiva para esta propiedad.

Título: {title}
Tipo: {type}
Precio: {price}
Ubicación: {location}
Especificaciones: {specs}
Características: {features}

Reglas:
- 2-3 líneas máximo (aproximadamente 200 caracteres)
- Enfocarse en los puntos más atractivos
- Tono profesional pero cercano
- Llamado a la acción sutil

Responde SOLO con la descripción, sin explicaciones adicionales.
PROMPT,
            
            'long_description' => <<<PROMPT
Eres un experto en redacción inmobiliaria. Genera una descripción completa y detallada para esta propiedad.

Título: {title}
Tipo: {type}
Precio: {price}
Ubicación: {location}
Especificaciones: {specs}
Características: {features}
Entorno: {surroundings}

Estructura sugerida:
1. Párrafo introductorio atractivo
2. Descripción detallada de la propiedad
3. Características y comodidades
4. Ubicación y entorno
5. Cierre con invitación a visitar

Reglas:
- Varios párrafos (4-6)
- Tono profesional y descriptivo
- Incluir todos los detalles relevantes
- Evitar exageraciones
- No inventar características no mencionadas

Responde SOLO con la descripción, sin explicaciones adicionales.
PROMPT,
            
            'optimize_seo' => <<<PROMPT
Eres un experto en SEO para real estate. Optimiza los metadatos SEO para esta propiedad.

Título actual: {title}
Descripción actual: {description}
Ubicación: {location}
Tipo: {type}

Genera:
1. Meta Title optimizado (máx 60 caracteres)
2. Meta Description optimizada (máx 160 caracteres)
3. 5-7 Keywords relevantes
4. Slug optimizado

Responde en formato JSON:
{
    "meta_title": "...",
    "meta_description": "...",
    "meta_keywords": ["...", "..."],
    "slug": "..."
}
PROMPT,
            
            'by_channel' => <<<PROMPT
Eres un experto en marketing inmobiliario multi-canal. Adapta el contenido de esta propiedad para el portal {channel}.

Restricciones del canal:
- Título máximo: {max_title_length} caracteres
- Descripción máxima: {max_description_length} caracteres
- Tono: {tone}

Contenido original:
Título: {title}
Descripción: {description}
Especificaciones: {specs}

Genera contenido optimizado para {channel} que respete las restricciones y se adapte al estilo del portal.

Responde en formato JSON:
{
    "title": "...",
    "description": "...",
    "highlights": ["...", "...", "..."]
}
PROMPT,
            
            'translate' => <<<PROMPT
Traduce el siguiente texto de {from} a {to}. Mantén el tono y estilo del texto original.

Texto a traducir:
{content}

Reglas:
- Traducción precisa y natural
- Mantener el mismo tono (formal/informal)
- Adaptar expresiones idiomáticas si es necesario
- No agregar ni quitar información

Responde SOLO con la traducción, sin explicaciones adicionales.
PROMPT
        ];
        
        return $templates[$type] ?? '';
    }
    
    private function getChannelSpecifications(string $channel): array {
        $specs = [
            'idealista' => [
                'max_title_length' => 80,
                'max_description_length' => 10000,
                'tone' => 'profesional y directo'
            ],
            'fotocasa' => [
                'max_title_length' => 100,
                'max_description_length' => 5000,
                'tone' => 'cercano y descriptivo'
            ],
            'woocommerce' => [
                'max_title_length' => 200,
                'max_description_length' => -1, // sin límite
                'tone' => 'comercial y atractivo'
            ],
        ];
        
        return $specs[$channel] ?? $specs['woocommerce'];
    }
    
    private function formatLocation(array $property): string {
        $parts = array_filter([
            $property['location_address'] ?? '',
            $property['location_city'] ?? '',
            $property['location_state'] ?? '',
            $property['location_country'] ?? ''
        ]);
        
        return implode(', ', $parts);
    }
    
    private function formatSpecifications(array $specs): string {
        $formatted = [];
        
        if (isset($specs['bedrooms'])) {
            $formatted[] = $specs['bedrooms'] . ' habitaciones';
        }
        if (isset($specs['bathrooms'])) {
            $formatted[] = $specs['bathrooms'] . ' baños';
        }
        if (isset($specs['surface_total'])) {
            $formatted[] = $specs['surface_total'] . ' m²';
        }
        if (isset($specs['year_built'])) {
            $formatted[] = 'Año ' . $specs['year_built'];
        }
        
        return implode(', ', $formatted);
    }
    
    private function formatFeatures(array $features): string {
        $formatted = [];
        
        foreach ($features as $key => $value) {
            if ($value === true) {
                $formatted[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        
        return implode(', ', $formatted);
    }
    
    private function formatSurroundings(array $property): string {
        // Información sobre el entorno (colegios, transporte, servicios)
        // Por ahora, placeholder
        return 'Zona bien comunicada con todos los servicios';
    }
    
    public function getMetadata(): array {
        return [
            'provider' => '360group',
            'version' => '1.0',
            'supports' => [
                'rewrite_title' => true,
                'short_description' => true,
                'long_description' => true,
                'optimize_seo' => true,
                'by_channel' => true,
                'translate' => true,
            ],
            'models' => [
                'default' => 'gpt-4',
                'available' => ['gpt-3.5-turbo', 'gpt-4', 'claude-3']
            ]
        ];
    }
}
```

---

## Cliente HTTP para 360group.ai

### AI360Client

```php
namespace AI360RealEstate\AI\Clients;

class AI360Client {
    private string $apiUrl;
    private string $apiKey;
    private int $timeout = 30;
    
    public function __construct(string $apiUrl, string $apiKey) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }
    
    public function chat(string $prompt, array $options = []): AIResponse {
        $endpoint = '/v1/chat';
        
        $body = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $options['max_tokens'] ?? 500,
            'temperature' => $options['temperature'] ?? 0.7,
            'model' => $options['model'] ?? 'gpt-4',
        ];
        
        if (isset($options['response_format'])) {
            $body['response_format'] = ['type' => $options['response_format']];
        }
        
        $response = $this->request('POST', $endpoint, $body);
        
        return new AIResponse(
            $response['choices'][0]['message']['content'],
            $response['usage']['total_tokens'],
            $this->calculateCost($response['usage'])
        );
    }
    
    private function request(string $method, string $endpoint, array $body = []): array {
        $url = $this->apiUrl . $endpoint;
        
        $args = [
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ];
        
        if (!empty($body)) {
            $args['body'] = json_encode($body);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new AIException($response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            throw new AIException("API request failed with status {$status_code}: {$body}");
        }
        
        return json_decode($body, true);
    }
    
    private function calculateCost(array $usage): float {
        // Precios aproximados por 1K tokens
        $costs = [
            'gpt-4' => ['input' => 0.03, 'output' => 0.06],
            'gpt-3.5-turbo' => ['input' => 0.001, 'output' => 0.002],
        ];
        
        $model = 'gpt-4'; // Default
        $pricing = $costs[$model];
        
        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;
        
        $cost = ($inputTokens / 1000 * $pricing['input']) + 
                ($outputTokens / 1000 * $pricing['output']);
        
        return round($cost, 6);
    }
}
```

---

## Sistema de Tareas de IA

### TaskQueue

```php
namespace AI360RealEstate\AI;

class TaskQueue {
    /**
     * Encolar tarea de IA
     */
    public function enqueue(AITask $task): int {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'ai360re_ai_tasks',
            [
                'property_id' => $task->getPropertyId(),
                'task_type' => $task->getType(),
                'provider' => $task->getProvider(),
                'status' => 'pending',
                'priority' => $task->getPriority(),
                'input_data' => json_encode($task->getInputData()),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Obtener siguiente tarea pendiente
     */
    public function getNext(): ?AITask {
        global $wpdb;
        
        $row = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}ai360re_ai_tasks 
             WHERE status = 'pending' 
             ORDER BY priority ASC, created_at ASC 
             LIMIT 1"
        );
        
        if (!$row) {
            return null;
        }
        
        return AITask::fromDatabase($row);
    }
    
    /**
     * Marcar tarea como procesando
     */
    public function markProcessing(int $taskId): void {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'ai360re_ai_tasks',
            [
                'status' => 'processing',
                'started_at' => current_time('mysql'),
            ],
            ['task_id' => $taskId],
            ['%s', '%s'],
            ['%d']
        );
    }
    
    /**
     * Marcar tarea como completada
     */
    public function markCompleted(int $taskId, AIResponse $response): void {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'ai360re_ai_tasks',
            [
                'status' => 'completed',
                'output_data' => json_encode($response->getData()),
                'tokens_used' => $response->getTokensUsed(),
                'cost' => $response->getCost(),
                'completed_at' => current_time('mysql'),
            ],
            ['task_id' => $taskId],
            ['%s', '%s', '%d', '%f', '%s'],
            ['%d']
        );
    }
    
    /**
     * Marcar tarea como fallida
     */
    public function markFailed(int $taskId, string $error): void {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'ai360re_ai_tasks',
            [
                'status' => 'failed',
                'error_message' => $error,
                'completed_at' => current_time('mysql'),
            ],
            ['task_id' => $taskId],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }
}
```

### TaskProcessor

```php
namespace AI360RealEstate\AI;

class TaskProcessor {
    private TaskQueue $queue;
    private AIProviderFactory $providerFactory;
    private Logger $logger;
    
    public function __construct() {
        $this->queue = new TaskQueue();
        $this->providerFactory = new AIProviderFactory();
        $this->logger = new Logger('AI-TaskProcessor');
    }
    
    /**
     * Procesar una tarea
     */
    public function processNext(): bool {
        $task = $this->queue->getNext();
        
        if ($task === null) {
            return false;
        }
        
        $this->queue->markProcessing($task->getId());
        
        try {
            $provider = $this->providerFactory->create($task->getProvider());
            $result = $this->executeTask($task, $provider);
            
            $this->queue->markCompleted($task->getId(), $result);
            $this->applyResultToProperty($task, $result);
            
            $this->logger->info("Task {$task->getId()} completed successfully");
            
            return true;
        } catch (\Exception $e) {
            $this->queue->markFailed($task->getId(), $e->getMessage());
            $this->logger->error("Task {$task->getId()} failed: " . $e->getMessage());
            
            return false;
        }
    }
    
    private function executeTask(AITask $task, AIProviderInterface $provider): AIResponse {
        $inputData = $task->getInputData();
        
        switch ($task->getType()) {
            case 'rewrite_title':
                $content = $provider->rewriteTitle(
                    $inputData['original'],
                    $inputData['context']
                );
                break;
                
            case 'generate_short_desc':
                $content = $provider->generateShortDescription(
                    $inputData['title'],
                    $inputData['property']
                );
                break;
                
            case 'generate_long_desc':
                $content = $provider->generateLongDescription(
                    $inputData['title'],
                    $inputData['property']
                );
                break;
                
            case 'optimize_seo':
                $content = $provider->optimizeSEO($inputData['property']);
                break;
                
            case 'generate_by_channel':
                $content = $provider->generateByChannel(
                    $inputData['property'],
                    $inputData['channel']
                );
                break;
                
            default:
                throw new \InvalidArgumentException("Unknown task type: {$task->getType()}");
        }
        
        return new AIResponse($content, 0, 0);
    }
    
    private function applyResultToProperty(AITask $task, AIResponse $result): void {
        $property = Property::find($task->getPropertyId());
        
        // Crear nueva versión antes de modificar
        $versionManager = new VersionManager();
        $versionManager->createVersion($property, 'ai_optimization', $task->getType());
        
        // Aplicar cambios según tipo de tarea
        switch ($task->getType()) {
            case 'rewrite_title':
                $property->setTitle($result->getContent());
                break;
                
            case 'generate_short_desc':
                $property->setShortDescription($result->getContent());
                break;
                
            case 'generate_long_desc':
                $property->setLongDescription($result->getContent());
                break;
                
            case 'optimize_seo':
                $property->setSEOData($result->getContent());
                break;
        }
        
        // Actualizar estado
        $property->setStatus(PropertyStatus::OPTIMIZED);
        $property->save();
    }
}
```

---

## Workflow de Optimización

### OptimizationWorkflow

```php
namespace AI360RealEstate\AI;

class OptimizationWorkflow {
    /**
     * Optimizar propiedad completa
     */
    public function optimizeProperty(int $propertyId, array $tasks = []): array {
        $property = Property::find($propertyId);
        
        if ($tasks === []) {
            $tasks = ['rewrite_title', 'generate_short_desc', 'generate_long_desc', 'optimize_seo'];
        }
        
        $taskIds = [];
        foreach ($tasks as $taskType) {
            $taskIds[] = $this->createTask($property, $taskType);
        }
        
        return $taskIds;
    }
    
    private function createTask(Property $property, string $taskType): int {
        $queue = new TaskQueue();
        
        $task = new AITask([
            'property_id' => $property->getId(),
            'task_type' => $taskType,
            'provider' => '360group',
            'priority' => 5,
            'input_data' => $this->prepareInputData($property, $taskType),
        ]);
        
        return $queue->enqueue($task);
    }
    
    private function prepareInputData(Property $property, string $taskType): array {
        $data = [
            'property' => $property->toArray(),
        ];
        
        switch ($taskType) {
            case 'rewrite_title':
                $data['original'] = $property->getTitle();
                $data['context'] = [
                    'type' => $property->getType(),
                    'city' => $property->getCity(),
                    'features' => $property->getFeatures(),
                ];
                break;
                
            case 'generate_short_desc':
            case 'generate_long_desc':
                $data['title'] = $property->getTitle();
                break;
        }
        
        return $data;
    }
    
    /**
     * Comparar versiones (antes y después de IA)
     */
    public function compareVersions(int $propertyId): array {
        $property = Property::find($propertyId);
        $versionManager = new VersionManager();
        
        $currentVersion = $property->getVersionNumber();
        $previousVersion = $currentVersion - 1;
        
        if ($previousVersion < 1) {
            return ['error' => 'No previous version to compare'];
        }
        
        $before = $versionManager->getVersion($propertyId, $previousVersion);
        $after = $property;
        
        return [
            'title' => [
                'before' => $before->getTitle(),
                'after' => $after->getTitle(),
                'changed' => $before->getTitle() !== $after->getTitle(),
            ],
            'short_description' => [
                'before' => $before->getShortDescription(),
                'after' => $after->getShortDescription(),
                'changed' => $before->getShortDescription() !== $after->getShortDescription(),
            ],
            'long_description' => [
                'before' => $before->getLongDescription(),
                'after' => $after->getLongDescription(),
                'changed' => $before->getLongDescription() !== $after->getLongDescription(),
            ],
            'seo_data' => [
                'before' => $before->getSEOData(),
                'after' => $after->getSEOData(),
                'changed' => $before->getSEOData() !== $after->getSEOData(),
            ],
        ];
    }
    
    /**
     * Aprobar cambios de IA
     */
    public function approve(int $propertyId): bool {
        $property = Property::find($propertyId);
        $property->setStatus(PropertyStatus::VALIDATED);
        return $property->save();
    }
    
    /**
     * Rechazar cambios de IA (rollback)
     */
    public function reject(int $propertyId): bool {
        $versionManager = new VersionManager();
        $currentVersion = Property::find($propertyId)->getVersionNumber();
        $previousVersion = $currentVersion - 1;
        
        return $versionManager->rollback($propertyId, $previousVersion);
    }
}
```

---

## Cache de IA

### AICache

```php
namespace AI360RealEstate\AI;

class AICache {
    private string $cacheGroup = 'ai360re_ai';
    private int $ttl = 7 * DAY_IN_SECONDS; // 7 días
    
    public function has(string $key): bool {
        return wp_cache_get($key, $this->cacheGroup) !== false;
    }
    
    public function get(string $key): ?AIResponse {
        $cached = wp_cache_get($key, $this->cacheGroup);
        
        if ($cached === false) {
            return null;
        }
        
        return unserialize($cached);
    }
    
    public function set(string $key, AIResponse $response): void {
        wp_cache_set($key, serialize($response), $this->cacheGroup, $this->ttl);
    }
    
    public function delete(string $key): void {
        wp_cache_delete($key, $this->cacheGroup);
    }
    
    public function flush(): void {
        wp_cache_flush();
    }
}
```

---

## Tracking de Tokens y Costos

### TokenTracker

```php
namespace AI360RealEstate\AI;

class TokenTracker {
    public function track(array $data): void {
        global $wpdb;
        
        // Guardar en tabla de auditoría o log
        $wpdb->insert(
            $wpdb->prefix . 'ai360re_audit_log',
            [
                'action' => 'ai_usage',
                'description' => json_encode($data),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s']
        );
    }
    
    public function getUsageStats(string $period = 'month'): array {
        global $wpdb;
        
        // Calcular stats
        $results = $wpdb->get_results(
            "SELECT 
                SUM(JSON_EXTRACT(description, '$.tokens')) as total_tokens,
                SUM(JSON_EXTRACT(description, '$.cost')) as total_cost,
                COUNT(*) as total_requests
             FROM {$wpdb->prefix}ai360re_audit_log
             WHERE action = 'ai_usage'
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 {$period})"
        );
        
        return [
            'total_tokens' => (int) $results[0]->total_tokens,
            'total_cost' => (float) $results[0]->total_cost,
            'total_requests' => (int) $results[0]->total_requests,
        ];
    }
}
```

---

## Preparación para Futuros Providers

### OpenAI Provider (Placeholder)

```php
namespace AI360RealEstate\AI\Providers;

class OpenAIProvider extends AbstractAIProvider {
    protected string $providerName = 'openai';
    
    // Implementación futura
    // Similar a AI360Provider pero conectando directamente con OpenAI API
}
```

### Anthropic Provider (Placeholder)

```php
namespace AI360RealEstate\AI\Providers;

class AnthropicProvider extends AbstractAIProvider {
    protected string $providerName = 'anthropic';
    
    // Implementación futura
    // Similar a AI360Provider pero conectando con Claude API
}
```

---

**Documento creado para:** PR-00 - Análisis de Arquitectura y Documentación Técnica Fundacional  
**Última actualización:** 2025-12-18  
**Versión:** 1.0
