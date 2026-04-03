<?php
use Cake\Cache\Engine\FileEngine;
use Cake\Cache\Engine\RedisEngine;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;

$databaseUrl = getenv('DATABASE_URL') ?: 'sqlite:///database.db';

// Handle SQLite URLs specially (parse_url doesn't work with sqlite:///)
if (str_starts_with($databaseUrl, 'sqlite:///')) {
    // Absolute path: sqlite:///absolute/path
    $scheme = 'sqlite';
    $sqlitePath = substr($databaseUrl, 9); // Remove 'sqlite://' to keep the leading /
} elseif (str_starts_with($databaseUrl, 'sqlite://')) {
    // Relative path: sqlite://relative/path
    $scheme = 'sqlite';
    $sqlitePath = substr($databaseUrl, 9); // Remove 'sqlite://'
} else {
    // Other database types (mysql, postgres, postgresql)
    $parsedUrl = parse_url($databaseUrl);
    $scheme = $parsedUrl['scheme'] ?? 'sqlite';
}

$config = [
    'className' => Connection::class,
    'driver' => Sqlite::class,
    'database' => 'database.db',
    'encoding' => 'utf8',
    'timezone' => 'UTC',
    'cacheMetadata' => true,
    'quoteIdentifiers' => false,
    'log' => false,
];

switch ($scheme) {
    case 'sqlite':
        $config['driver'] = Sqlite::class;
        $config['database'] = $sqlitePath ?? 'database.db';
        break;
    case 'mysql':
        $config['driver'] = Mysql::class;
        $config['host'] = $parsedUrl['host'] ?? 'localhost';
        $config['port'] = $parsedUrl['port'] ?? 3306;
        $config['username'] = $parsedUrl['user'] ?? 'root';
        $config['password'] = $parsedUrl['pass'] ?? '';
        $config['database'] = ltrim($parsedUrl['path'] ?? '/app', '/');
        $config['encoding'] = 'utf8mb4';
        break;
    case 'postgres':
    case 'postgresql':
        $config['driver'] = Postgres::class;
        $config['host'] = $parsedUrl['host'] ?? 'localhost';
        $config['port'] = $parsedUrl['port'] ?? 5432;
        $config['username'] = $parsedUrl['user'] ?? 'postgres';
        $config['password'] = $parsedUrl['pass'] ?? '';
        $config['database'] = ltrim($parsedUrl['path'] ?? '/app', '/');
        $config['encoding'] = 'utf8';
        $config['schema'] = 'public';
        break;
}

// Redis configuration
$redisUrl = getenv('REDIS_URL') ?: '';
$cacheDriver = getenv('CACHE_DRIVER') ?: 'file';
$sessionDriver = getenv('SESSION_DRIVER') ?: 'php';

$redisHost = '127.0.0.1';
$redisPort = 6379;
$redisPassword = '';
if ($redisUrl) {
    $parsedRedis = parse_url($redisUrl);
    $redisHost = $parsedRedis['host'] ?? '127.0.0.1';
    $redisPort = $parsedRedis['port'] ?? 6379;
    $redisPassword = $parsedRedis['pass'] ?? '';
}

// Build cache configuration
$cacheConfig = [];

if ($cacheDriver === 'redis' && $redisUrl) {
    $cacheConfig['default'] = [
        'className' => RedisEngine::class,
        'host' => $redisHost,
        'port' => $redisPort,
        'password' => $redisPassword ?: null,
        'database' => 0,
        'prefix' => 'isp_status_',
        'duration' => '+1 hours',
    ];
    $cacheConfig['_cake_core_'] = [
        'className' => RedisEngine::class,
        'host' => $redisHost,
        'port' => $redisPort,
        'password' => $redisPassword ?: null,
        'database' => 1,
        'prefix' => 'isp_cake_core_',
        'duration' => '+1 years',
    ];
    $cacheConfig['_cake_model_'] = [
        'className' => RedisEngine::class,
        'host' => $redisHost,
        'port' => $redisPort,
        'password' => $redisPassword ?: null,
        'database' => 2,
        'prefix' => 'isp_cake_model_',
        'duration' => '+1 years',
    ];
    $cacheConfig['super_admin'] = [
        'className' => RedisEngine::class,
        'host' => $redisHost,
        'port' => $redisPort,
        'password' => $redisPassword ?: null,
        'database' => 4,
        'duration' => 300,
        'prefix' => 'sa_',
    ];
} else {
    $cacheConfig['default'] = [
        'className' => FileEngine::class,
        'path' => CACHE,
    ];
    $cacheConfig['_cake_core_'] = [
        'className' => FileEngine::class,
        'prefix' => 'myapp_cake_core_',
        'path' => CACHE . 'persistent' . DS,
        'serialize' => true,
        'duration' => '+1 years',
    ];
    $cacheConfig['_cake_model_'] = [
        'className' => FileEngine::class,
        'prefix' => 'myapp_cake_model_',
        'path' => CACHE . 'models' . DS,
        'serialize' => true,
        'duration' => '+1 years',
    ];
    $cacheConfig['super_admin'] = [
        'className' => FileEngine::class,
        'path' => CACHE . 'super_admin' . DS,
        'duration' => 300,
        'prefix' => 'sa_',
    ];
}

// Build session configuration (TASK-AUTH-007: secure cookie flags)
$sessionConfig = [
    'defaults' => 'php',
    'ini' => [
        'session.cookie_httponly' => true,
        'session.cookie_samesite' => 'Lax',
        'session.use_strict_mode' => true,
        // For production, also enable: 'session.cookie_secure' => true,
    ],
];

if ($sessionDriver === 'redis' && $redisUrl) {
    $sessionConfig = [
        'defaults' => 'php',
        'ini' => [
            'session.save_handler' => 'redis',
            'session.save_path' => $redisPassword
                ? "tcp://{$redisHost}:{$redisPort}?auth={$redisPassword}&database=3"
                : "tcp://{$redisHost}:{$redisPort}?database=3",
            'session.cookie_httponly' => true,
            'session.cookie_samesite' => 'Lax',
            'session.use_strict_mode' => true,
            // For production, also enable: 'session.cookie_secure' => true,
        ],
    ];
}

return [
    'debug' => filter_var(getenv('DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'Security' => [
        'salt' => env('SECURITY_SALT') ?: (function() {
            if (PHP_SAPI === 'cli' || env('APP_ENV') === 'test') {
                return 'test-salt-not-for-production-use-change-me';
            }
            trigger_error('SECURITY_SALT environment variable must be set', E_USER_ERROR);
            return '';
        })(),
    ],
    'Datasources' => [
        'default' => $config,
        'test' => [
            'className' => Connection::class,
            'driver' => Sqlite::class,
            'database' => ':memory:',
        ],
    ],
    'Cache' => $cacheConfig,
    'Session' => $sessionConfig,

    /*
     * Queue configuration for background job processing.
     * Uses Redis DB 5 for the queue broker and DB 6 for unique-job cache.
     */
    'Queue' => [
        'default' => [
            'url' => env(
                'REDIS_QUEUE_URL',
                $redisPassword
                    ? "redis://:{$redisPassword}@{$redisHost}:{$redisPort}/5"
                    : "redis://{$redisHost}:{$redisPort}/5"
            ),
            'queue' => 'default',
            'logger' => 'default',
            'receiveTimeout' => 5000,
            'uniqueCache' => [
                'className' => RedisEngine::class,
                'host' => $redisHost,
                'port' => $redisPort,
                'password' => $redisPassword ?: null,
                'database' => 6,
                'duration' => '+5 minutes',
                'prefix' => 'queue_unique_default_',
            ],
        ],
        'notifications' => [
            'url' => env(
                'REDIS_QUEUE_URL',
                $redisPassword
                    ? "redis://:{$redisPassword}@{$redisHost}:{$redisPort}/5"
                    : "redis://{$redisHost}:{$redisPort}/5"
            ),
            'queue' => 'notifications',
            'logger' => 'default',
            'receiveTimeout' => 5000,
            'uniqueCache' => [
                'className' => RedisEngine::class,
                'host' => $redisHost,
                'port' => $redisPort,
                'password' => $redisPassword ?: null,
                'database' => 6,
                'duration' => '+5 minutes',
                'prefix' => 'queue_unique_notif_',
            ],
        ],
    ],
];
