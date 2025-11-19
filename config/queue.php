<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | The Laravel queue API supports a variety of back-ends via an unified
    | API, giving you convenient access to each back-end using the same
    | syntax for each one. Here you may set the default queue driver.
    |
    | Supported: "null", "sync", "database", "beanstalkd", "sqs", "redis"
    |
    */

    'default' => env('QUEUE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    */

    'connections' => [

        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_PORT', 5672),
            'vhost'    => env('RABBITMQ_VHOST', '/'),
            'login'    => env('RABBITMQ_LOGIN', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'queue' => env('RABBITMQ_QUEUE'),
            'exchange_declare' => env('RABBITMQ_EXCHANGE_DECLARE', true),
            'queue_declare_bind' => env('RABBITMQ_QUEUE_DECLARE_BIND', true),
            'queue_params' => [
                // 如果设置为true, 会进行队列检查, 但不会创建队列
                'passive'     => env('RABBITMQ_QUEUE_PASSIVE', false),
                // 持久化
                'durable'     => env('RABBITMQ_QUEUE_DURABLE', true),
                // 排他
                'exclusive'   => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                // 当队列为空时自动清空
                'auto_delete' => env('RABBITMQ_QUEUE_AUTODELETE', false),
            ],
            'exchange_params' => [
                'name' => env('RABBITMQ_EXCHANGE_NAME', null),
                'type' => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
                'passive' => env('RABBITMQ_EXCHANGE_PASSIVE', false),
                'durable' => env('RABBITMQ_EXCHANGE_DURABLE', true),
                'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
            ],

            // the number of seconds to sleep if there's an error communicating with rabbitmq
            // if set to false, it'll throw an exception rather than doing the sleep for X seconds
            'sleep_on_error' => env('RABBITMQ_ERROR_SLEEP', 5),
        ],

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 300,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 300,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => 'your-public-key',
            'secret' => 'your-secret-key',
            'queue' => 'your-queue-url',
            'region' => 'us-east-1',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'default',
            'retry_after' => 300,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => env('QUEUE_FAILED_TABLE', 'failed_jobs'),
    ],

];
