# Integration System

This directory contains the integration system for connecting to external APIs and services.

## Architecture

The integration system follows the **Adapter Pattern** to provide a consistent interface for different external services:

```
IntegrationInterface (interface)
    ↓
AbstractIntegration (abstract base class)
    ↓
ConcreteAdapter (IxcAdapter, ZabbixAdapter, etc.)
```

## Core Components

### IntegrationInterface

Defines the contract that all integrations must follow:

- `connect()` - Establish connection
- `testConnection()` - Verify connectivity
- `getStatus()` - Get resource status
- `getMetrics()` - Get resource metrics
- `disconnect()` - Clean up resources
- `getName()` - Get integration name
- `getType()` - Get integration type
- `isConnected()` - Check connection status

### AbstractIntegration

Provides common functionality:

- Configuration management
- Connection state tracking
- Error handling and logging
- Debug logging support
- Helper methods for responses

## Creating a New Integration

### Step 1: Create Directory Structure

```bash
mkdir -p src/Integration/YourService
```

### Step 2: Implement the Adapter

```php
<?php
namespace App\Integration\YourService;

use App\Integration\AbstractIntegration;

class YourServiceAdapter extends AbstractIntegration
{
    protected string $name = 'Your Service';
    protected string $type = 'your_service';

    public function connect(): bool
    {
        try {
            $this->validateConfig(['api_url', 'api_key']);
            
            // Your connection logic here
            
            $this->connected = true;
            $this->logInfo('Connected to Your Service');
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Connection failed: ' . $e->getMessage());
            return false;
        }
    }

    public function testConnection(): array
    {
        if (!$this->connect()) {
            return $this->buildErrorResponse('Connection failed');
        }

        // Test the connection
        // ...

        return $this->buildSuccessResponse('Connection successful');
    }

    public function getStatus(string $resourceId): array
    {
        if (!$this->isConnected() && !$this->connect()) {
            return ['status' => 'unknown', 'online' => false];
        }

        // Get status logic
        // ...

        return [
            'status' => 'up',
            'online' => true,
            'message' => 'Resource is online',
        ];
    }

    public function getMetrics(string $resourceId, array $params = []): array
    {
        if (!$this->isConnected() && !$this->connect()) {
            return ['metrics' => []];
        }

        // Get metrics logic
        // ...

        return [
            'metrics' => [],
            'timestamp' => date('Y-m-d H:i:s'),
            'resource_id' => $resourceId,
        ];
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->logInfo('Disconnected from Your Service');
    }
}
```

### Step 3: Configuration

Configuration is passed to the constructor:

```php
$adapter = new YourServiceAdapter([
    'api_url' => 'https://api.yourservice.com',
    'api_key' => 'your-api-key',
    'timeout' => 30,
    'debug' => true,
]);
```

### Step 4: Usage

```php
// Connect
if ($adapter->connect()) {
    // Test connection
    $test = $adapter->testConnection();
    
    if ($test['success']) {
        // Get status
        $status = $adapter->getStatus('resource-123');
        
        // Get metrics
        $metrics = $adapter->getMetrics('resource-123', [
            'period' => '1h',
            'metrics' => ['cpu', 'memory'],
        ]);
    }
    
    // Disconnect
    $adapter->disconnect();
}

// Check for errors
if ($error = $adapter->getLastError()) {
    echo "Error: $error\n";
}
```

## Available Integrations

### Planned Integrations

- **IXC Soft** (`src/Integration/Ixc/`) - TASK-301
  - Equipment monitoring
  - Service status
  - Customer information

- **Zabbix** (`src/Integration/Zabbix/`)
  - Host monitoring
  - Item metrics
  - Trigger status

- **REST API** (`src/Integration/RestApi/`)
  - Generic REST API monitoring
  - Custom endpoints
  - Flexible authentication

## Logging

All integrations automatically log their activities:

```php
// Logs are automatically created with context
$adapter->connect();
// Logs: "Connected to Your Service" with integration context

// Debug logging (only when debug=true)
$this->logDebug('API request', ['endpoint' => '/status']);

// Error logging
$this->logError('API request failed', ['code' => 500]);
```

Logs include:
- Integration type
- Integration name
- Timestamp
- Context data

## Error Handling

The abstract class provides consistent error handling:

```php
// Set error
$this->setLastError('Connection timeout');

// Get error
$error = $adapter->getLastError();

// Build error response
return $this->buildErrorResponse('Invalid resource ID', [
    'resource_id' => $resourceId,
    'valid_ids' => ['123', '456'],
]);
```

## Testing

Example test structure:

```php
class YourServiceAdapterTest extends TestCase
{
    public function testConnect()
    {
        $adapter = new YourServiceAdapter([
            'api_url' => 'https://test.api.com',
            'api_key' => 'test-key',
        ]);

        $result = $adapter->connect();
        $this->assertTrue($result);
        $this->assertTrue($adapter->isConnected());
    }

    public function testGetStatus()
    {
        // Mock the API response
        // Test the adapter
    }
}
```

## Best Practices

1. **Always validate configuration** - Use `validateConfig()` in `connect()`
2. **Handle errors gracefully** - Use try-catch and return error responses
3. **Log important operations** - Use the logging methods provided
4. **Clean up resources** - Implement `disconnect()` properly
5. **Use type hints** - Follow strict typing for better code quality
6. **Document return types** - Use PHPDoc array shapes for complex returns
7. **Test thoroughly** - Write unit tests with mocked API responses

## See Also

- `docs/API_INTEGRATIONS.md` - Detailed integration specifications
- `docs/TASKS.md` - Integration implementation tasks (TASK-300+)
