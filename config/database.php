<?php

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
    'default' => env('DB_CONNECTION', 'default'),
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
    'connections' => config_ext('database')->get(env('DB_DRIVER','mysql')),

    'redis' => [
        'cluster' => false,
        'client' => env('REDIS_CLIENT', 'predis'),
        'default' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'host' => env('REDIS_HOST', env('REDIS_DEFAULT_HOST', '127.0.0.1')),
            'port' => env('REDIS_PORT', env('REDIS_DEFAULT_PORT', 6379)),
            'database' => env('REDIS_DATABASE', env('REDIS_DEFAULT_DATABASE', 0)),
            'password' => env('REDIS_PASSWORD', env('REDIS_DEFAULT_PASSWORD')),
        ],
        'cache' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DATABASE', 1), //默认为1，不要与其他db相同，否则清除缓存会删除整个db
            'password' => env('REDIS_PASSWORD'),
        ],
        'companys' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'host' => env('REDIS_HOST', env('REDIS_COMPANYS_HOST', '127.0.0.1')),
            'port' => env('REDIS_PORT', env('REDIS_COMPANYS_PORT', 6379)),
            'database' => env('REDIS_DATABASE', env('REDIS_COMPANYS_DATABASE', 0)),
            'password' => env('REDIS_PASSWORD', env('REDIS_COMPANYS_PASSWORD')),
        ],
        'espier' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'host' => env('REDIS_HOST', env('REDIS_ESPIER_HOST', '127.0.0.1')),
            'port' => env('REDIS_PORT', env('REDIS_ESPIER_PORT', 6379)),
            'database' => env('REDIS_DATABASE', env('REDIS_ESPIER_DATABASE', 0)),
            'password' => env('REDIS_PASSWORD', env('REDIS_ESPIER_PASSWORD')),
        ],
        'kaquan' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'host' => env('REDIS_HOST', env('REDIS_KAQUAN_HOST', '127.0.0.1')),
            'port' => env('REDIS_PORT', env('REDIS_KAQUAN_PORT', 6379)),
            'database' => env('REDIS_DATABASE', env('REDIS_KAQUAN_DATABASE', 0)),
            'password' => env('REDIS_PASSWORD', env('REDIS_KAQUAN_PASSWORD')),
        ],
        'members' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'host' => env('REDIS_HOST', env('REDIS_MEMBERS_HOST', '127.0.0.1')),
            'port' => env('REDIS_PORT', env('REDIS_MEMBERS_PORT', 6379)),
            'database' => env('REDIS_DATABASE', env('REDIS_MEMBERS_DATABASE', 0)),
            'password' => env('REDIS_PASSWORD', env('REDIS_MEMBERS_PASSWORD')),
        ],
        'wechat' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'host' => env('REDIS_HOST', env('REDIS_WECHAT_HOST', '127.0.0.1')),
            'port' => env('REDIS_PORT', env('REDIS_WECHAT_PORT', 6379)),
            'database' => env('REDIS_DATABASE', env('REDIS_WECHAT_DATABASE', 0)),
            'password' => env('REDIS_PASSWORD', env('REDIS_WECHAT_PASSWORD')),
        ],
        'datacube' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'host' => env('REDIS_HOST', env('REDIS_DATACUBE_HOST', '127.0.0.1')),
            'port' => env('REDIS_PORT', env('REDIS_DATACUBE_PORT', 6379)),
            'database' => env('REDIS_DATABASE', env('REDIS_DATACUBE_DATABASE', 0)),
            'password' => env('REDIS_PASSWORD', env('REDIS_DATACUBE_PASSWORD')),
        ],
        'deposit' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'host' => env('REDIS_HOST', env('REDIS_DEPOSIT_HOST', '127.0.0.1')),
            'port' => env('REDIS_PORT', env('REDIS_DEPOSIT_PORT', 6379)),
            'database' => env('REDIS_DATABASE', env('REDIS_DEPOSIT_DATABASE', 0)),
            'password' => env('REDIS_PASSWORD', env('REDIS_DEPOSIT_PASSWORD')),
        ],
        'prism' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'host' => env('REDIS_HOST', env('REDIS_PRISM_HOST', '127.0.0.1')),
            'port' => env('REDIS_PORT', env('REDIS_PRISM_PORT', 6379)),
            'database' => env('REDIS_DATABASE', env('REDIS_PRISM_DATABASE', 0)),
            'password' => env('REDIS_PASSWORD', env('REDIS_PRISM_PASSWORD')),
        ],
    ],
];
