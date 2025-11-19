<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Upload;

use EspierBundle\Interfaces\UploadTokenInterface;

class UploadService
{
    /**
     * 企业id
     * @var int
     */
    protected $companyId;

    /**
     * 获取上传时的token服务（可以通过\EspierBundle\Services\UploadTokenFactoryService::create("image")来获取）
     * @var UploadTokenInterface
     */
    protected $uploadTokenService;

    public function __construct(int $companyId, UploadTokenInterface $uploadTokenService)
    {
        $this->companyId = $companyId;
        $this->uploadTokenService = $uploadTokenService;
    }

    /**
     * 上传文件
     * @param string $fileStream 文件内容
     * @param string|null $group 组别，为null则没有组别
     * @param string|null $fileName 文件名，为null则为自动生成
     * @return bool true表示上传成功，false表示上传失败
     */
    public function upload(string $fileStream, ?string $group = null, ?string $fileName = null): bool
    {
        // ShopEx EcShopX Business Logic Layer
        try {
            $uploadedInfo = $this->uploadTokenService->upload($this->companyId, $group, $fileName, $fileStream);
            $this->domain = (string)($uploadedInfo["token"]["domain"] ?? "");
            $this->uri = (string)($uploadedInfo["token"]["key"] ?? "");
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * 域名
     * @var string
     */
    protected $domain = "";

    /**
     * 获取域名
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * 不包含域名的路径
     * @var string
     */
    protected $uri = "";

    /**
     * 获取不包含域名的路径
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * 获取全路径
     * @return string
     */
    public function getUrl(): string
    {
        if (empty($this->domain) && empty($this->uri)) {
            return "";
        }
        return sprintf("%s/%s", trim($this->domain, "/"), trim($this->uri, "/"));
    }
}
