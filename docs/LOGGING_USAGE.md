# Logging System Usage Examples

## Overview

The AI360 Real Estate plugin includes a comprehensive logging system with two main components:

1. **Logger**: PSR-3 compatible logging for system events and errors
2. **AuditLogger**: Specialized logging for user actions and audit trail

## Logger Usage

### Basic Usage

```php
use AI360RealEstate\Logging\Logger;

// Log at different levels
Logger::debug('Debug information');
Logger::info('Informational message');
Logger::notice('Normal but significant event');
Logger::warning('Warning message');
Logger::error('Error occurred');
Logger::critical('Critical condition');
Logger::alert('Action must be taken immediately');
Logger::emergency('System is unusable');
```

### Using Context

```php
// Log with context data
Logger::info('User logged in', array(
    'user_id' => 123,
    'ip_address' => '192.168.1.1',
    'browser' => 'Chrome'
));

// Context with placeholders
Logger::error('Failed to connect to {service} after {attempts} attempts', array(
    'service' => 'Resales API',
    'attempts' => 3
));
```

### Log Levels

The logger follows PSR-3 log levels (from highest to lowest priority):

1. `EMERGENCY` - System is unusable
2. `ALERT` - Action must be taken immediately
3. `CRITICAL` - Critical conditions
4. `ERROR` - Runtime errors
5. `WARNING` - Exceptional occurrences that are not errors
6. `NOTICE` - Normal but significant events
7. `INFO` - Interesting events
8. `DEBUG` - Detailed debug information

### Configuration

Set the minimum log level (logs below this level will be ignored):

```php
// Only log WARNING and above
update_option('ai360re_log_level', Logger::WARNING);
```

### Log Destinations

Configure where logs are written:

```php
use AI360RealEstate\Logging\LogHandler;

// Database only (default)
$handler = new LogHandler(LogHandler::DESTINATION_DATABASE);

// File only
$handler = new LogHandler(LogHandler::DESTINATION_FILE);

// Both database and file
$handler = new LogHandler(LogHandler::DESTINATION_BOTH);

// Initialize logger with custom handler
Logger::init($handler);
```

### Manual Cleanup

```php
// Clean up logs older than 30 days
$deleted = Logger::cleanup(30);
echo "Deleted {$deleted} old log files";
```

## AuditLogger Usage

### Basic Usage

```php
use AI360RealEstate\Logging\AuditLogger;

// Log a create action
AuditLogger::log_create(
    AuditLogger::ENTITY_PROPERTY,
    $property_id,
    $property_data,
    $project_id
);

// Log an update action
AuditLogger::log_update(
    AuditLogger::ENTITY_PROPERTY,
    $property_id,
    $old_data,
    $new_data,
    $project_id
);

// Log a delete action
AuditLogger::log_delete(
    AuditLogger::ENTITY_PROPERTY,
    $property_id,
    $old_data,
    $project_id
);
```

### Custom Actions

```php
// Log a custom action
AuditLogger::log(array(
    'entity_type' => AuditLogger::ENTITY_CONNECTOR,
    'entity_id'   => $connector_id,
    'action'      => AuditLogger::ACTION_SYNC,
    'project_id'  => $project_id,
    'new_value'   => array(
        'properties_synced' => 25,
        'duration' => 5.2
    )
));
```

### Entity Types

Available entity types:

- `ENTITY_PROJECT` - Projects
- `ENTITY_PROPERTY` - Properties
- `ENTITY_CONNECTOR` - Connectors
- `ENTITY_USER` - Users
- `ENTITY_SETTINGS` - Settings

### Action Types

Common action types:

- `ACTION_CREATE` - Create
- `ACTION_UPDATE` - Update
- `ACTION_DELETE` - Delete
- `ACTION_VIEW` - View
- `ACTION_LOGIN` - Login
- `ACTION_LOGOUT` - Logout
- `ACTION_SYNC` - Synchronization
- `ACTION_AI_OPT` - AI Optimization
- `ACTION_EXPORT` - Export
- `ACTION_IMPORT` - Import

### Retrieving Audit Logs

```php
// Get all audit entries for a property
$entries = AuditLogger::get_entries(array(
    'entity_type' => AuditLogger::ENTITY_PROPERTY,
    'entity_id'   => $property_id,
    'limit'       => 50
));

// Get all actions by a specific user
$entries = AuditLogger::get_entries(array(
    'user_id' => $user_id,
    'limit'   => 100
));

// Get all create actions in a project
$entries = AuditLogger::get_entries(array(
    'project_id' => $project_id,
    'action'     => AuditLogger::ACTION_CREATE,
    'limit'      => 50
));

// Count entries
$count = AuditLogger::count_entries(array(
    'entity_type' => AuditLogger::ENTITY_PROPERTY,
    'action'      => AuditLogger::ACTION_UPDATE
));
```

### Manual Cleanup

```php
// Clean up audit logs older than 90 days
$deleted = AuditLogger::cleanup(90);
echo "Deleted {$deleted} old audit log entries";
```

## Automatic Cleanup

The logging system automatically cleans up old logs via WordPress cron:

- **File logs**: Retained for 30 days by default
- **Audit logs**: Retained for 90 days by default

Configure retention periods:

```php
// Set file log retention to 60 days
update_option('ai360re_file_log_retention', 60);

// Set audit log retention to 180 days
update_option('ai360re_audit_log_retention', 180);
```

## Integration Examples

### In Entity Classes

```php
class Property {
    public function create($data) {
        // Create property...
        $property_id = $wpdb->insert_id;
        
        // Log creation
        Logger::info('Property created', array(
            'property_id' => $property_id,
            'project_id' => $data['project_id']
        ));
        
        // Audit log
        AuditLogger::log_create(
            AuditLogger::ENTITY_PROPERTY,
            $property_id,
            $data,
            $data['project_id']
        );
        
        return $property_id;
    }
}
```

### In Connector Classes

```php
class ResalesConnector {
    public function sync() {
        try {
            // Sync logic...
            Logger::info('Resales sync completed', array(
                'properties_synced' => $count,
                'duration' => $duration
            ));
            
            // Audit log
            AuditLogger::log(array(
                'entity_type' => AuditLogger::ENTITY_CONNECTOR,
                'entity_id'   => $this->connector_id,
                'action'      => AuditLogger::ACTION_SYNC,
                'new_value'   => array('count' => $count)
            ));
        } catch (Exception $e) {
            Logger::error('Resales sync failed: ' . $e->getMessage(), array(
                'connector_id' => $this->connector_id,
                'exception' => get_class($e)
            ));
        }
    }
}
```

### In AI Provider Classes

```php
class AI360Provider {
    public function optimize_property($property_id) {
        Logger::debug('Starting AI optimization', array(
            'property_id' => $property_id
        ));
        
        try {
            // AI optimization logic...
            $result = $this->api_call($property_id);
            
            Logger::info('AI optimization completed', array(
                'property_id' => $property_id,
                'tokens_used' => $result['tokens']
            ));
            
            // Audit log
            AuditLogger::log(array(
                'entity_type' => AuditLogger::ENTITY_PROPERTY,
                'entity_id'   => $property_id,
                'action'      => AuditLogger::ACTION_AI_OPT,
                'old_value'   => $old_data,
                'new_value'   => $result['data']
            ));
            
            return $result;
        } catch (Exception $e) {
            Logger::error('AI optimization failed', array(
                'property_id' => $property_id,
                'error' => $e->getMessage()
            ));
            throw $e;
        }
    }
}
```

## Security Considerations

### Sensitive Data

The logging system automatically redacts sensitive information in context arrays:

```php
// This context will have sensitive data redacted
Logger::info('User authenticated', array(
    'username' => 'john',
    'password' => 'secret123',  // Will be redacted
    'api_key' => 'abc123',      // Will be redacted
    'token' => 'xyz789'         // Will be redacted
));

// Logged as:
// [2025-12-27 10:00:00] [INFO] User authenticated {"username":"john","password":"***REDACTED***","api_key":"***REDACTED***","token":"***REDACTED***"}
```

Sensitive keys that are automatically redacted:
- password
- api_key
- secret
- token
- auth
- credentials

### IP Address and User Agent

The AuditLogger automatically captures IP address and user agent for all audit entries to help with security analysis and debugging.

## Best Practices

1. **Use appropriate log levels**: Don't log everything as ERROR
2. **Include context**: Always provide relevant context data
3. **Be concise**: Keep messages short and clear
4. **Don't log sensitive data**: Passwords, API keys, etc.
5. **Use audit logs for user actions**: Track all important user operations
6. **Regular monitoring**: Check logs regularly for errors and anomalies
7. **Set appropriate retention**: Balance between disk space and compliance needs

## Troubleshooting

### Logs not appearing

1. Check if the plugin is activated
2. Verify the log level setting: `get_option('ai360re_log_level')`
3. Check file permissions on the upload directory
4. Look for PHP errors in the WordPress debug log

### Audit logs growing too large

1. Reduce retention period: `update_option('ai360re_audit_log_retention', 30)`
2. Manually run cleanup: `AuditLogger::cleanup(30)`
3. Check for infinite loops in your code that might be creating excessive audit entries

### Cron not running

1. Verify WordPress cron is working: `wp_next_scheduled('ai360re_cleanup_logs')`
2. Check if cron is disabled in wp-config.php
3. Manually trigger cleanup if needed
