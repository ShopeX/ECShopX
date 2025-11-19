<?php

return [
    /*
     | Your kafka brokers url.
     */
    'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),

    /*
     | Default security protocol
     */
    'securityProtocol' =>  env('KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),

    /*
     | Default sasl configuration 
     */
    'sasl' => [
        'mechanisms' => env('KAFKA_MECHANISMS', 'PLAIN'),
        'username' => env('KAFKA_USERNAME', null),
        'password' => env('KAFKA_PASSWORD', null)
    ],

    'consumer_timeout_ms' => env("KAFKA_CONSUMER_DEFAULT_TIMEOUT", 2000),

    /*
     | After the consumer receives its assignment from the coordinator,
     | it must determine the initial position for each assigned partition.
     | When the group is first created, before any messages have been consumed, the position is set according to a configurable
     | offset reset policy (auto.offset.reset). Typically, consumption starts either at the earliest offset or the latest offset.
     | You can choose between "latest", "earliest" or "none".
     */
    'offset_reset' => env('KAFKA_OFFSET_RESET', 'latest'),

    /*
     | If you set enable.auto.commit (which is the default), then the consumer will automatically commit offsets periodically at the
     | interval set by auto.commit.interval.ms.
     */
    'auto_commit' => env('KAFKA_AUTO_COMMIT', true),

    'sleep_on_error' => env('KAFKA_ERROR_SLEEP', 5),

    /*
     | Kafka consumers
     | topic => class@method
     */
    'consumers' => [
    ],
];
