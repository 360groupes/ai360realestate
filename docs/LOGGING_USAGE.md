# Logging System Usage Examples

This document provides examples of how to use the logging and auditing system in AI360 Real Estate.

## Logger Usage

The Logger class provides centralized logging with multiple severity levels.

### Basic Usage

```php
use AI360RealEstate\Logging\Logger;

// Get logger instance
$logger = Logger::get_instance();

// Log at different levels
$logger->debug('Debug message for development');
$logger->info('Informational message');
$logger->warning('Warning: something might be wrong');
$logger->error('Error: something went wrong');

// Log with context
$logger->info('Property created', array(
    'property_id' => 123,
    'project_id' => 1,
    'user_id' => 5
));
```

### Log Levels

- **DEBUG**: Detailed information for debugging
- **INFO**: General informational messages
- **WARNING**: Warning messages for potential issues
- **ERROR**: Error messages for failures

### Configuration

Logging can be configured via WordPress options:

```php
// Enable/disable logging
update_option('ai360re_logging_enabled', true);

// Set minimum log level (debug, info, warning, error)
update_option('ai360re_log_level', 'info');

// Set retention period in days
update_option('ai360re_log_retention_days', 30);
```

### Retrieving Logs

```php
// Get recent logs
$logs = $logger->get_recent_logs(50);

// Get logs filtered by level
$errors = $logger->get_recent_logs(50, Logger::LEVEL_ERROR);

// Manually trigger cleanup
$deleted_count = $logger->cleanup_old_logs();
```

## AuditLogger Usage

The AuditLogger tracks all system actions for compliance and debugging.

### Basic Usage

```php
use AI360RealEstate\Logging\AuditLogger;

$audit = AuditLogger::get_instance();

// Log entity creation
$audit->log_create(
    'property',
    123,
    array(
        'title' => 'New Property',
        'price' => 250000
    ),
    1 // project_id
);

// Log entity update
$audit->log_update(
    'property',
    123,
    array('price' => 250000), // old value
    array('price' => 275000), // new value
    1
);

// Log entity deletion
$audit->log_delete(
    'property',
    123,
    array('title' => 'Property Name'),
    1
);
```

### Advanced Usage

```php
// Custom audit log
$audit->log(
    'connector',
    5,
    'sync_completed',
    array('last_sync' => '2025-12-27 10:00:00'),
    array('last_sync' => '2025-12-27 12:00:00'),
    1
);

// Retrieve audit logs for an entity
$logs = $audit->get_entity_logs('property', 123, 50);

// Retrieve audit logs for a user
$logs = $audit->get_user_logs(5, 50);

// Retrieve audit logs for a project
$logs = $audit->get_project_logs(1, 50);

// Clean up old audit logs (retention in days)
$deleted = $audit->cleanup_old_logs(90);
```

### Audit Log Configuration

```php
// Set audit log retention period
update_option('ai360re_audit_retention_days', 90);
```

## Automatic Cleanup

The system automatically schedules a daily cleanup task:

- File logs are cleaned based on `ai360re_log_retention_days` (default: 30 days)
- Audit logs are cleaned based on `ai360re_audit_retention_days` (default: 90 days)

The cleanup is scheduled automatically on plugin activation and runs daily via WordPress cron.

## Security

### Log Files

Log files are stored in `wp-content/uploads/ai360realestate-logs/` with:
- `.htaccess` file to prevent direct access
- `index.php` file to prevent directory listing

### Audit Logs

Audit logs are stored in the database table `{prefix}ai360re_audit_log` and include:
- User ID
- IP address
- User agent
- Timestamp
- Old and new values

## Best Practices

1. **Use appropriate log levels**: Don't log everything at ERROR level
2. **Add context**: Include relevant IDs and data in context arrays
3. **Don't log sensitive data**: Avoid logging passwords, API keys, etc.
4. **Monitor log sizes**: Adjust retention periods based on disk space
5. **Use audit logs for compliance**: Track all data modifications
6. **Review logs regularly**: Check for errors and warnings

## Example: Property CRUD Operations

```php
use AI360RealEstate\Logging\Logger;
use AI360RealEstate\Logging\AuditLogger;

$logger = Logger::get_instance();
$audit = AuditLogger::get_instance();

// Create property
function create_property($data, $project_id) {
    global $logger, $audit;
    
    try {
        // Create property logic here
        $property_id = 123; // Result of creation
        
        // Log the action
        $logger->info('Property created successfully', array(
            'property_id' => $property_id,
            'project_id' => $project_id
        ));
        
        // Audit trail
        $audit->log_create('property', $property_id, $data, $project_id);
        
        return $property_id;
    } catch (\Exception $e) {
        // Log error
        $logger->error('Failed to create property', array(
            'error' => $e->getMessage(),
            'project_id' => $project_id
        ));
        
        throw $e;
    }
}

// Update property
function update_property($property_id, $old_data, $new_data, $project_id) {
    global $logger, $audit;
    
    try {
        // Update property logic here
        
        $logger->info('Property updated', array(
            'property_id' => $property_id,
            'project_id' => $project_id
        ));
        
        $audit->log_update('property', $property_id, $old_data, $new_data, $project_id);
        
        return true;
    } catch (\Exception $e) {
        $logger->error('Failed to update property', array(
            'property_id' => $property_id,
            'error' => $e->getMessage()
        ));
        
        throw $e;
    }
}
```

## Performance Considerations

- Logging operations are designed to be fast and non-blocking
- File writes are buffered by the operating system
- Database inserts use prepared statements
- Cleanup runs asynchronously via WordPress cron
- Configure retention periods based on your needs and available resources
