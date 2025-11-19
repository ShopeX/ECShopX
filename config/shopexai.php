<?php

return [
    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY', ''),
        'api_endpoint' => env('DEEPSEEK_API_ENDPOINT', 'https://api.deepseek.com/v1/chat/completions'),
    ],
    
    'aliyun' => [
        'access_key_id' => env('ALIYUN_ACCESS_KEY_ID', ''),
        'access_key_secret' => env('ALIYUN_ACCESS_KEY_SECRET', ''),
        'region_id' => env('ALIYUN_REGION_ID', 'cn-shanghai'),
        'endpoint' => env('ALIYUN_IMAGE_ENDPOINT', 'imagegenerate.cn-shanghai.aliyuncs.com'),
        'bailian_api_key' => env('ALIYUN_BAILIAN_API_KEY', ''),
        'bailian_endpoint' => env('ALIYUN_BAILIAN_ENDPOINT', 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis'),
        'default_image_url' => env('ALIYUN_DEFAULT_IMAGE_URL', ''),
        'max_poll_attempts' => env('ALIYUN_MAX_POLL_ATTEMPTS', 15),
        'poll_interval' => env('ALIYUN_POLL_INTERVAL', 5),
    ],
    
    // 缓存配置
    'cache_ttl' => env('SHOPEX_AI_CACHE_TTL', 60), // 内容生成缓存时间（秒）
    
    // 队列配置
    'use_queue' => env('SHOPEX_AI_USE_QUEUE', true), // 是否使用队列处理非流式生成
    'queue_name' => env('SHOPEX_AI_QUEUE_NAME', 'slow'), // 队列名称
    
    // 虚拟试衣配置
    'outfit' => [
        'use_queue' => env('SHOPEX_OUTFIT_USE_QUEUE', true), // 是否使用队列处理虚拟试衣生成
        'outfit_model' => env('SHOPEX_OUTFIT_MODEL', 'aitryon'), // 主模型
        'outfit_backup_model' => env('SHOPEX_OUTFIT_BACKUP_MODEL', 'aitryon-plus'), // 备用模型
        'cache_ttl' => env('SHOPEX_OUTFIT_CACHE_TTL', 3600), // 虚拟试衣结果缓存时间（秒）
    ],
    
    // 图片生成服务配置
    'image_service' => env('SHOPEX_AI_IMAGE_SERVICE', 'wanxiang'), // 可选值：wanxiang, jimeng
    
    // 通义万相配置
    'wanxiang' => [
        'api_key' => env('WANXIANG_API_KEY', ''),
        'api_secret' => env('WANXIANG_API_SECRET', ''),
        'base_url' => env('WANXIANG_BASE_URL', 'https://api.wanxiang.aliyun.com'),
    ],
    
    // 即梦配置
    'jimeng' => [
        'api_key' => env('JIMENG_API_KEY', ''),
        'base_url' => env('JIMENG_BASE_URL', 'https://ark.cn-beijing.volces.com'),
        'model' => env('JIMENG_MODEL', 'doubao-seedream-3-0-t2i-250415'),
        'guidance_scale' => env('JIMENG_GUIDANCE_SCALE', 2.5),
        'watermark' => env('JIMENG_WATERMARK', false),
        'seed' => env('JIMENG_SEED', 12),
    ],
    
    // 默认图片URL
    'default_image_url' => env('DEFAULT_IMAGE_URL', 'https://img.alicdn.com/imgextra/i4/O1CN01c26iB51CGdiWJA4L3_!!6000000000564-2-tps-818-404.png'),
]; 