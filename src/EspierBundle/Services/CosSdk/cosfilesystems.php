<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key'    => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],

        'cosv5' => [
            'driver'         => 'cosv5',
            'region'         => env('COSV5_REGION', 'cn-east'),
            'credentials'    => [
                'appId'      => env('COSV5_APP_ID'),
                'secretId'   => env('COSV5_SECRET_ID'),
                'secretKey'  => env('COSV5_SECRET_KEY'),
                'token'      => env('COSV5_TOKEN'),
            ],
            'timeout'            => env('COSV5_TIMEOUT', 60),
            'connect_timeout'    => env('COSV5_CONNECT_TIMEOUT', 60),
            'bucket'             => env('COSV5_BUCKET'),
            'cdn'                => env('COSV5_CDN'),
            'scheme'             => env('COSV5_SCHEME', 'https'),
            'read_from_cdn'      => env('COSV5_READ_FROM_CDN', false),
            'cdn_key'            => env('COSV5_CDN_KEY'),
            'encrypt'            => env('COSV5_ENCRYPT', false),
        ],

    ],

];
