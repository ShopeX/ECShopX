<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\UploadToken;

use Dingo\Api\Exception\ResourceException;

class QiniuUploadTokenService extends UploadTokenAbstract
{
    // 客户端上传对应表
    public $regionWithHost = [
        'z0' => 'https://upload.qiniup.com',
        'z1' => 'https://upload-z1.qiniup.com',
        'z2' => 'https://upload-z2.qiniup.com',
        'na0' => 'https://upload-na0.qiniup.com',
        'as0' => 'https://upload-as0.qiniup.com',
    ];

    public function getToken($companyId, $group = null, $fileName = null)
    {
        $bucketDomain = trim($this->adapter->url('/'), '/') . '/';
        $bucketRegion = $this->getRegion() ?? 'z0';
        $key = $this->getUploadName($companyId, $group, $fileName);
        $putPolicy = $this->getPutPolicy();
        $token = $this->adapter->getUploadToken($key, 86400, $putPolicy);
        $result = [
            'host' => $this->regionWithHost[$bucketRegion],
            'token' => $token,
            'domain' => $bucketDomain,
            'region' => $bucketRegion,
            'key' => $key
        ];
        return $this->formart('qiniu', $result);
    }

    private function getRegion()
    {
        $bucketConfig = config('filesystems.disks.import-' . $this->fileType);
        if (!$bucketConfig['region']) {
            throw new ResourceException($this->fileType . '配置文件错误');
        }
        return $bucketConfig['region'];
    }

    private function getPutPolicy()
    {
        if ($this->fileType == 'image') {
            return [
                'fsizeLimit' => 15 * 1024 * 1024,
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

    /**
     * 上传至七牛云
     * @param string $companyId 企业id
     * @param null $group
     * @param null $fileName
     * @param string $fileContent
     * @return array 返回格式可以参照getToken方法的返回参数
     * @throws \Exception
     */
    public function upload($companyId, $group = null, $fileName = null, string $fileContent = ""): array
    {
        // 获取七牛云有关的参数信息
        $result = $this->getToken($companyId, $group, $fileName);

        // 获取上传的文件路径加文件名（不包含域名）
        $filename = $result["token"]["key"] ?? "";
        // 上传至七牛云
        $data = $this->adapter->write($filename, $fileContent, []);
        if ($data === false) {
            throw new \Exception("上传失败");
        }
        return $result;
    }
}
