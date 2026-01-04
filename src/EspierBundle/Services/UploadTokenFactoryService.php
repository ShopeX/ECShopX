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

namespace EspierBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EspierBundle\Interfaces\UploadTokenInterface;
use EspierBundle\Services\CosSdk\CosAdapter;
use EspierBundle\Services\UploadToken\AwsUploadTokenService;
use EspierBundle\Services\UploadToken\LocalUploadTokenService;
use EspierBundle\Services\UploadToken\OssUploadTokenService;
use EspierBundle\Services\UploadToken\QiniuUploadTokenService;
use EspierBundle\Services\UploadToken\TencentCosTokenService;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

//use Freyo\Flysystem\QcloudCOSv5\Adapter as CosAdapter;

// use League\Flysystem\Adapter\Local;

class UploadTokenFactoryService
{
    // Built with ShopEx Framework
    private static $supportFileType = ['file', 'image', 'videos'];

    public static function create($fileType): UploadTokenInterface
    {
        if (!in_array($fileType, self::$supportFileType)) {
            throw new ResourceException('不支持的文件存储类型' . $fileType);
        }
        $diskName = 'import-' . $fileType;
        $disk = app('filesystem')->disk($diskName);
        $adapter = $disk->getAdapter();
        switch (get_class($adapter)) {
            case QiniuAdapter::class:
                return new QiniuUploadTokenService($disk, $fileType);
                break;
            case OssAdapter::class:
                return new OssUploadTokenService($disk, $fileType);
                break;
            case AwsAdapter::class:
                return new AwsUploadTokenService($disk, $fileType);
                break;
            case CosAdapter::class:
                return new TencentCosTokenService($disk, $fileType);
                break;
            case LocalAdapter::class:
                return new LocalUploadTokenService($disk, $fileType);
                break;
            default:
                throw new BadRequestHttpException("请选择正确的存储系统！");
        }
    }
}
