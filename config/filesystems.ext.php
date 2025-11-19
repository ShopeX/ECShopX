<?php

return [
    'oss'=>[
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],

        'import-file' => [
            'driver' => 'oss',
            'root' => '',
            'access_key' => env('OSS_ACCESS_KEY'),
            'secret_key' => env('OSS_SECRET_KEY'),
            'endpoint'   => env('OSS_FILE_ENDPOINT'),
            'bucket'     => env('OSS_FILE_BUCKET'),
            'isCName'    => env('OSS_FILE_IS_CNAME', false),
        ],

        'import-image' => [
            'driver' => 'oss',
            'root' => '',
            'access_key' => env('OSS_ACCESS_KEY'),
            'secret_key' => env('OSS_SECRET_KEY'),
            'endpoint'   => env('OSS_IMAGE_ENDPOINT'),
            'bucket'     => env('OSS_IMAGE_BUCKET'),
            'isCName'    => env('OSS_IMAGE_IS_CNAME', false),
            'domain'    => env('OSS_IMAGE_DOMAIN'),
        ],

        'import-videos' => [
            'driver' => 'oss',
            'root' => '',
            'access_key' => env('OSS_ACCESS_KEY'),
            'secret_key' => env('OSS_SECRET_KEY'),
            'endpoint'   => env('OSS_VIDEO_ENDPOINT'),
            'bucket'     => env('OSS_VIDEO_BUCKET'),
            'isCName'    => env('OSS_VIDEO_IS_CNAME', false),
            'domain'    => env('OSS_VIDEO_DOMAIN'),
        ],
    ],
    'qiniu'=>[
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],

        'import-file' => [
            'driver' => 'qiniu',
            'access_key'=> env('QINIU_ACCESS_KEY', 'wTHrpmk-brD6l5rJzKci9xLK_SMhjFePhRogGm9a'),
            'secret_key' => env('QINIU_SECRET_KEY', 'yWRq4fiX256HUVHXRktRKw08QIKZgbH9P2p58YLM'),
            'bucket' => env('QINIU_FILE_NAME', 'espier-file'),
            'domain' => env('QINIU__FILE_DOMAIN', 'https://b-import-cdn.yuanyuanke.cn'),
            'region' => env('QINIU_FILE_REGION', 'z2'),
        ],

        'import-image' => [
            'driver' => 'qiniu',
            'access_key'=> env('QINIU_ACCESS_KEY', 'wTHrpmk-brD6l5rJzKci9xLK_SMhjFePhRogGm9a'),
            'secret_key' => env('QINIU_SECRET_KEY', 'yWRq4fiX256HUVHXRktRKw08QIKZgbH9P2p58YLM'),
            'bucket' => env('QINIU_IMAGE_NAME'),
            'domain' => env('QINIU_IMAGE_DOMAIN', 'https://b-img-cdn.yuanyuanke.cn'),
            'region' => env('QINIU_IMAGE_REGION', 'z2'),
        ],

        'import-videos' => [
            'driver' => 'qiniu',
            'access_key'=> env('QINIU_ACCESS_KEY', 'wTHrpmk-brD6l5rJzKci9xLK_SMhjFePhRogGm9a'),
            'secret_key' => env('QINIU_SECRET_KEY', 'yWRq4fiX256HUVHXRktRKw08QIKZgbH9P2p58YLM'),
            'bucket' => env('QINIU_VIDEO_NAME'),
            'domain' => env('QINIU_VIDEO_DOMAIN', 'https://b-video-cdn.yuanyuanke.cn'),
            'region' => env('QINIU_VIDEO_REGION', 'z2'),
        ],
    ],
    'aws'=>[
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],

        'import-file' => [
            'driver' => 'aws',
            'access_key' => env('AWS_ACCESS_KEY_ID', ''),
            'secret_key' => env('AWS_SECRET_ACCESS_KEY', ''),
            'arn' => env('AWS_ARN', ''),
            'endpoint'   => env('AWS_ENDPOINT'),
            'bucket' => env('AWS_BUCKET', ''),
            'region' => env('AWS_REGION', ''),
            'curl' => env('AWS_CURL', ''),
        ],

        'import-image' => [
            'driver' => 'aws',
            'access_key' => env('AWS_ACCESS_KEY_ID', ''),
            'secret_key' => env('AWS_SECRET_ACCESS_KEY', ''),
            'arn' => env('AWS_ARN', ''),
            'endpoint'   => env('AWS_ENDPOINT'),
            'bucket' => env('AWS_BUCKET', ''),
            'region' => env('AWS_REGION', ''),
            'curl' => env('AWS_CURL', ''),
        ],

        'import-videos' => [
            'driver' => 'aws',
            'access_key' => env('AWS_ACCESS_KEY_ID', ''),
            'secret_key' => env('AWS_SECRET_ACCESS_KEY', ''),
            'arn' => env('AWS_ARN', ''),
            'endpoint'   => env('AWS_ENDPOINT'),
            'bucket' => env('AWS_BUCKET', ''),
            'region' => env('AWS_REGION', ''),
            'curl' => env('AWS_CURL', ''),
        ],
    ],
    'local'=>[
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'import-file' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'private',
        ],

        'import-image' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'import-videos' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],
    ],

    'cosv5'=>[
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],

        'import-file' => [
            'driver' => 'cosv5',
            'region'          => env('FILE_COS_REGION', ''),
            'credentials'     => [
                'appId'     => env('COS_APPID', ''),
                'secretId'  => env('COS_SECRET_ID', ''),
                'secretKey' => env('COS_SECRET_KEY', ''),
                'token'     => null,
            ],
            'timeout'         => 60,
            'connect_timeout' => 60,
            'bucket'          => env('FILE_COS_BUCKET', ''),
            'cdn'             => '',
            'scheme'          => 'https',
            'read_from_cdn'   => false,
            'cdn_key'         => '',
            'encrypt'         => false,
        ],

        'import-image' => [
            'driver' => 'cosv5',
            'region'          => env('IMAGE_COS_REGION', ''),
            'credentials'     => [
                'appId'     => env('COS_APPID', ''),
                'secretId'  => env('COS_SECRET_ID', ''),
                'secretKey' => env('COS_SECRET_KEY', ''),
                'token'     => null,
            ],
            'timeout'         => 60,
            'connect_timeout' => 60,
            'bucket'          => env('IMAGE_COS_BUCKET', ''),
            'cdn'             => '',
            'scheme'          => 'https',
            'read_from_cdn'   => false,
            'cdn_key'         => '',
            'encrypt'         => false,
        ],

        'import-videos' => [
            'driver' => 'cosv5',
            'region'          => env('VIDEO_COS_REGION', ''),
            'credentials'     => [
                'appId'     => env('COS_APPID', ''),
                'secretId'  => env('COS_SECRET_ID', ''),
                'secretKey' => env('COS_SECRET_KEY', ''),
                'token'     => null,
            ],
            'timeout'         => 60,
            'connect_timeout' => 60,
            'bucket'          => env('VIDEO_COS_BUCKET', ''),
            'cdn'             => '',
            'scheme'          => 'https',
            'read_from_cdn'   => false,
            'cdn_key'         => '',
            'encrypt'         => false,
        ],
    ],
];
