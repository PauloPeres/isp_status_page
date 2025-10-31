<?php
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
    // Other database types (mysql, postgres)
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
        $config['driver'] = Postgres::class;
        $config['host'] = $parsedUrl['host'] ?? 'localhost';
        $config['port'] = $parsedUrl['port'] ?? 5432;
        $config['username'] = $parsedUrl['user'] ?? 'postgres';
        $config['password'] = $parsedUrl['pass'] ?? '';
        $config['database'] = ltrim($parsedUrl['path'] ?? '/app', '/');
        $config['schema'] = 'public';
        break;
}

return [
    'debug' => filter_var(getenv('DEBUG') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'Security' => ['salt' => getenv('SECURITY_SALT') ?: '__SALT__'],
    'Datasources' => [
        'default' => $config,
        'test' => [
            'className' => Connection::class,
            'driver' => Sqlite::class,
            'database' => ':memory:',
        ],
    ],
];
