<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\UploadToken;

use Dingo\Api\Exception\ResourceException;

class LocalUploadTokenService extends UploadTokenAbstract
{
    public function getToken($companyId, $group = null, $fileName = null)
    {
        // $bucketDomain = trim($this->adapter->url('/'), '/') . '/';
        $config = config('filesystems.disks.import-' . $this->fileType);
        $bucketDomain = $config['url'];
        // $bucketRegion = $this->getRegion();
        $key = $this->getUploadName($companyId, $group, $fileName);
        // $putPolicy = $this->getPutPolicy();
        // $token = $this->adapter->getUploadToken($key, 3600, $putPolicy);
        $result = [
            // 'token' => $token,
            'domain' => $bucketDomain,
            // 'region' => '',
            'key' => $key
        ];
        return $this->formart('local', $result);
    }
    // private function getRegion()
    // {
    //     $bucketConfig = config('filesystems.disks.import-'.$this->fileType);
    //     if (!$bucketConfig['region']) {
    //         throw new ResourceException($this->fileType.'配置文件错误');
    //     }
    //     return $bucketConfig['region'];
    // }
    private function getPutPolicy()
    {
        if ($this->fileType == 'image') {
            return [
                'fsizeLimit' => 2 * 1024 * 1024,
                'mimeLimit' => 'image/jpeg;image/png;image/gif'
            ];
        }
        if ($this->fileType == 'videos') {
            return [
                'fsizeLimit' => 50 * 1024 * 1024,
                'mimeLimit' => 'video/mp4'
            ];
        }
        return [];
    }

    public function uploadeImage($companyId, $group, $filename, $newfilename = null)
    {
        $putPolicy = $this->getPutPolicy();
        $allowType = explode(';', $putPolicy['mimeLimit']);

        $clientMimeType = $filename->getClientMimeType();
        if (!in_array($clientMimeType, $allowType)) {
            throw new ResourceException('不支持的图片存储类型' . $clientMimeType);
        }
        $clientSize = $filename->getSize();
        if ($clientSize > $putPolicy['fsizeLimit']) {
            throw new ResourceException('图片大小超过' . $putPolicy['fsizeLimit']);
        }
        if (empty($newfilename)) {
            $clientName = $filename->getClientOriginalName();
            list(, $ext) = explode('/', $clientMimeType);
            if (substr($clientName, 0 - strlen('.'.$ext)) != '.'.$ext) {
                $clientName .= '.'.$ext;
            }
            $newfilename = $this->getUploadName($companyId, $group, $clientName);
        }

        $path = $this->adapter->putFileAs('/', $filename, $newfilename);
        return $path;
    }

    public function upload($companyId, $group = null, $fileName = null, string $fileContent = ""): array
    {
        // 获取本地有关的参数信息
        $result = $this->getToken($companyId, $group, $fileName);

        // 获取上传的文件路径加文件名（不包含域名）

        if (!empty($result["token"]["key"])) {
            $result["token"]["key"] = $result["token"]["key"] . '.png';
        }
        $filename = $result["token"]["key"] ?? "";
        // 上传至本地
        $data = $this->adapter->write($filename, $fileContent, []);
        if ($data === false) {
            throw new \Exception("上传失败");
        }
        $result['token']['domain'] = $result['token']['domain'] . '/';
        return $result;
    }
}
