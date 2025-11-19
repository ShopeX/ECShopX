<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\UploadToken;

use EspierBundle\Interfaces\UploadTokenInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UploadTokenAbstract implements UploadTokenInterface
{
    /**
     * 分组：门店二维码
     */
    public const GROUP_DISTRIBUTOR_QR_CODE = "distributor_qr_code";

    /**
     * 分组：门店商品二维码
     */
    public const GROUP_DISTRIBUTOR_ITEM_QR_CODE = "distributor_item_qr_code";

    protected $adapter;
    protected $fileType;

    public function __construct(FilesystemAdapter $adapter = null, $fileType)
    {
        $this->adapter = $adapter;
        $this->fileType = $fileType;
    }

    public function formart($driver, $token)
    {
        return ['driver' => $driver, 'token' => $token];
    }

    public function getToken($companyId, $group = null, $fileName = null)
    {
    }

    public function getUploadName($companyId, $group = null, $fileName = null)
    {
        if (!$fileName) {
            $fileName = str_random(32);
        }

        $key = $this->fileType . '/' . $companyId . '/' . date('Y/m/d');
        $ossProjectName = config('filesystems.current_project_name');
        if ($ossProjectName) {
            $key = $ossProjectName . '/' . $key;
        }

        if ($group) {
            $key = $key . '/' . $group;
        }
        $key .= '/' . md5(date('YmdHis')) . $fileName;
        return $key;
    }

    public function checkFile($filed): bool
    {
        if ($filed->getError() == UPLOAD_ERR_FORM_SIZE) {
            return false;
        }

        // 检测文件大小，类型
        switch ($this->fileType) {
            case 'image':
                $filedSizeLimit = 2 * 1024 * 1024;
                $allowType = 'image';
                break;
            case 'videos':
                $filedSizeLimit = 50 * 1024 * 1024;
                $allowType = 'video';
                break;
            default:
                return false;
        }

        if ($filed->getSize() == 0) {
            throw new BadRequestHttpException('上传文件内容为空');
        }

        $mineType = $filed->getClientMimeType();

        $tmp = explode('/', $mineType);
        if (empty($tmp)) {
            throw new BadRequestHttpException('mineType错误');
        }
        $filedType = current($tmp);

        if ($filedType != $allowType) {
            throw new BadRequestHttpException(sprintf("上传文件只支持%s格式", $allowType));
        }

        if ($filed->getSize() > $filedSizeLimit) {
            throw new BadRequestHttpException('上传文件大小超出限制');
        }

        return true;
    }

    public function upload($companyId, $group = null, $fileName = null, string $fileContent = ""): array
    {
        return [];
    }
}
