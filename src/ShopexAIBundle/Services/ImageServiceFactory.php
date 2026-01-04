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