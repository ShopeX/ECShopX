<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
