# Logging Configuration Guide

This project is configured with multiple logging channels to organize logs by functionality and purpose. All logs are stored in JSON format for easy parsing and analysis.

## Available Channels

### 1. **default** - General Application Logs
- **File**: `logs/{environment}.log`
- **Level**: DEBUG
- **Purpose**: General application logs, not filtered by event or http_client channels
- **Usage**: Generic application logging

### 2. **database** - Database Operations
- **File**: `logs/database.log`
- **Level**: DEBUG
- **Purpose**: Logs all database queries, connections, and ORM operations
- **Usage**: Monitor database performance and debug database issues

### 3. **api** - API Operations
- **File**: `logs/api.log`
- **Level**: INFO
- **Purpose**: Logs API requests, responses, and API-related errors
- **Usage**: Track API usage and debug API issues

### 4. **security** - Security Events
- **File**: `logs/security.log`
- **Level**: INFO
- **Purpose**: Authentication attempts, authorization decisions, and security events
- **Usage**: Monitor security-related events and audit trail

### 5. **currency** - Currency Operations
- **File**: `logs/currency.log`
- **Level**: INFO
- **Purpose**: Logs currency conversion, exchange rates, and currency-related operations
- **Usage**: Track currency operations specific to your application domain

### 6. **performance** - Performance Metrics
- **File**: `logs/performance.log`
- **Level**: WARNING
- **Purpose**: Logs slow operations, performance metrics, and bottlenecks
- **Usage**: Monitor and optimize application performance

### 7. **mail** - Email Operations
- **File**: `logs/mail.log`
- **Level**: INFO
- **Purpose**: Email sending operations, failures, and delivery status
- **Usage**: Track email operations and troubleshoot email issues

### 8. **deprecation** - Deprecation Notices
- **File**: `logs/deprecation.log`
- **Level**: INFO
- **Purpose**: Logs deprecated features and warnings
- **Usage**: Track deprecated code usage for cleanup

## How to Use Logging Channels

### Method 1: Constructor Injection with Named Parameters

```php
<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class MyService
{
    public function __construct(
        private LoggerInterface $defaultLogger,
        private LoggerInterface $databaseLogger,
        private LoggerInterface $apiLogger,
    ) {
    }

    public function doSomething(): void
    {
        $this->defaultLogger->info('Processing started');
        $this->databaseLogger->debug('Executing query');
        $this->apiLogger->info('API request received');
    }
}
```

### Method 2: Using LoggerFactory

```php
<?php

namespace App\Controller;

use App\Service\Logger\LoggerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MyController extends AbstractController
{
    public function __construct(private LoggerFactory $loggerFactory)
    {
    }

    public function index(): Response
    {
        $logger = $this->loggerFactory->getApiLogger();
        $logger->info('API endpoint called', [
            'path' => '/api/endpoint',
            'method' => 'GET',
        ]);

        return $this->json(['status' => 'ok']);
    }
}
```

### Method 3: Using the Container

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyCommand extends Command
{
    protected static $defaultName = 'app:my-command';

    public function __construct(
        private \Psr\Log\LoggerInterface $currencyLogger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->currencyLogger->info('Currency import started');
        
        try {
            // Process currencies
            $this->currencyLogger->info('Currency import completed', [
                'count' => 100,
            ]);
        } catch (\Exception $e) {
            $this->currencyLogger->error('Currency import failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return Command::SUCCESS;
    }
}
```

## Log Levels

- **DEBUG**: Detailed information, typically used for debugging
- **INFO**: Informational messages about normal operations
- **NOTICE**: Normal but significant events
- **WARNING**: Warning conditions, indicates potential issues
- **ERROR**: Error conditions that may need attention
- **CRITICAL**: Critical conditions, system may be unstable
- **ALERT**: Immediate action required
- **EMERGENCY**: System is unusable

## Environment-Specific Logging

### Development Environment (`when@dev`)
- Uses `fingers_crossed` handler to buffer errors
- Logs to `logs/dev.log`
- Console output for CLI commands
- Error level triggers: ERROR and above
- Buffer size: 50

### Production Environment (`when@prod`)
- Uses `fingers_crossed` handler with larger buffer
- Logs to `logs/prod.log` for normal operations
- Logs to `logs/prod_error.log` for errors
- Error level triggers: ERROR and above
- Excluded codes: 404 (normal 404s are not logged as errors)
- Buffer size: 100

### Test Environment (`when@test`)
- Uses `fingers_crossed` handler
- Logs to `logs/test.log`
- Error level triggers: ERROR and above
- Buffer size: 50

## JSON Format

All logs are formatted as JSON for easy parsing and analysis. Example log entry:

```json
{
  "message": "API request received",
  "context": {
    "path": "/api/endpoint",
    "method": "GET"
  },
  "level": "INFO",
  "level_name": "INFO",
  "channel": "api",
  "datetime": {
    "timezone_type": 3,
    "timezone": "UTC"
  },
  "extra": {}
}
```

## Viewing Logs

View logs in real-time:
```bash
# Watch main log
tail -f var/log/{environment}.log

# Watch specific channel
tail -f var/log/api.log
tail -f var/log/currency.log
tail -f var/log/security.log

# Parse and view JSON logs
tail -f var/log/api.log | jq .
```

## Best Practices

1. **Use appropriate channels**: Don't log everything to the default channel; use specific channels for domain-specific operations
2. **Include context**: Always include relevant context data in your logs
3. **Use appropriate log levels**: Don't log warnings as info or errors as debug
4. **Avoid logging sensitive data**: Never log passwords, API keys, or personal information
5. **Use structured logging**: Include context arrays for better analysis
6. **Monitor production logs**: Set up log aggregation and monitoring for production

Example of good logging:

```php
$this->apiLogger->info('User login attempt', [
    'user_id' => $user->getId(),
    'timestamp' => new \DateTime(),
    'ip_address' => $request->getClientIp(),
]);

$this->databaseLogger->debug('Database query executed', [
    'query' => $queryBuilder->getQuery()->getDQL(),
    'duration_ms' => $duration,
]);

$this->currencyLogger->error('Exchange rate update failed', [
    'currency_pair' => 'USD/EUR',
    'reason' => $exception->getMessage(),
]);
```

## Configuration Reference

All configurations are in `/config/packages/monolog.yaml`. To modify:

1. **Add a new channel**: Add channel name to the `channels` list and create a handler
2. **Change log level**: Modify the `level` property in the handler
3. **Change log file path**: Modify the `path` property in the handler
4. **Change formatter**: Modify the `formatter` property (currently using JSON)

Example of adding a new channel:

```yaml
monolog:
    channels:
        - my_custom_channel

    handlers:
        my_custom:
            type: stream
            path: "%kernel.logs_dir%/my_custom.log"
            level: info
            channels: [my_custom_channel]
            formatter: monolog.formatter.json
```

Then inject it in your service:

```php
class MyService
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.my_custom_channel')]
        private LoggerInterface $myLogger,
    ) {
    }
}
```

