<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql_akses' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_AKSES_URL'),
            'host' => env('DB_AKSES_HOST', '127.0.0.1'),
            'port' => env('DB_AKSES_PORT', '3306'),
            'database' => env('DB_AKSES_DATABASE', 'forge'),
            'username' => env('DB_AKSES_USERNAME', 'forge'),
            'password' => env('DB_AKSES_PASSWORD', ''),
            'unix_socket' => env('DB_AKSES_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql_andon' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_ANDON_HOST', '127.0.0.1'),
            'port' => env('DB_ANDON_PORT', '3306'),
            'database' => env('DB_ANDON_DATABASE', 'forge'),
            'username' => env('DB_ANDON_USERNAME', 'forge'),
            'password' => env('DB_ANDON_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql_lpb' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_LPB_HOST', '127.0.0.1'),
            'port' => env('DB_LPB_PORT', '3306'),
            'database' => env('DB_LPB_DATABASE', 'forge'),
            'username' => env('DB_LPB_USERNAME', 'forge'),
            'password' => env('DB_LPB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql_pack1' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_PACK1_URL'),
            'host' => env('DB_PACK1_HOST', '127.0.0.1'),
            'port' => env('DB_PACK1_PORT', '3306'),
            'database' => env('DB_PACK1_DATABASE', 'forge'),
            'username' => env('DB_PACK1_USERNAME', 'forge'),
            'password' => env('DB_PACK1_PASSWORD', ''),
            'unix_socket' => env('DB_PACK1_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql_sop' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_SOP_HOST', '127.0.0.1'),
            'port' => env('DB_SOP_PORT', '3306'),
            'database' => env('DB_SOP_DATABASE', 'forge'),
            'username' => env('DB_SOP_USERNAME', 'forge'),
            'password' => env('DB_SOP_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql_polibag' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_POLIBAG_HOST', '127.0.0.1'),
            'port' => env('DB_POLIBAG_PORT', '3306'),
            'database' => env('DB_POLIBAG_DATABASE', 'forge'),
            'username' => env('DB_POLIBAG_USERNAME', 'forge'),
            'password' => env('DB_POLIBAG_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql_finance_mif' => [
            'driver' => 'mysql',
            'url' => env('DB_FINANCE_MIF_URL'),
            'host' => env('DB_FINANCE_MIF_HOST', '127.0.0.1'),
            'port' => env('DB_FINANCE_MIF_PORT', '3306'),
            'database' => env('DB_FINANCE_MIF_DATABASE', 'forge'),
            'username' => env('DB_FINANCE_MIF_USERNAME', 'forge'),
            'password' => env('DB_FINANCE_MIF_PASSWORD', ''),
            'unix_socket' => env('DB_FINANCE_MIF_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql_sample' => [
            'driver' => 'mysql',
            'url' => env('DB_SAMPLE_URL'),
            'host' => env('DB_SAMPLE_HOST', '127.0.0.1'),
            'port' => env('DB_SAMPLE_PORT', '3306'),
            'database' => env('DB_SAMPLE_DATABASE', 'forge'),
            'username' => env('DB_SAMPLE_USERNAME', 'forge'),
            'password' => env('DB_SAMPLE_PASSWORD', ''),
            'unix_socket' => env('DB_SAMPLE_SOCKET', ''),
            'charset' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql_gis' => [
            'driver' => 'mysql',
            'url' => env('DB_GIS_URL'),
            'host' => env('DB_GIS_HOST', '127.0.0.1'),
            'port' => env('DB_GIS_PORT', '3306'),
            'database' => env('DB_GIS_DATABASE', 'forge'),
            'username' => env('DB_GIS_USERNAME', 'forge'),
            'password' => env('DB_GIS_PASSWORD', ''),
            'unix_socket' => env('DB_GIS_SOCKET', ''),
            'charset' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
