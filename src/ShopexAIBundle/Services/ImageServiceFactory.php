<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ShopexAIBundle\Services;

use Illuminate\Support\Facades\Log;

class ImageServiceFactory
{
    /**
     * 获取图片生成服务实例
     * @return AliyunImageService|JimengImageService
     */
    public static function getImageService()
    {
        $service = config('shopexai.image_service', 'wanxiang');
        
        Log::info('使用图片生成服务', ['service' => $service]);
        
        switch ($service) {
            case 'jimeng':
                return app(JimengImageService::class);
            case 'wanxiang':
            default:
                return app(AliyunImageService::class);
        }
    }
} 