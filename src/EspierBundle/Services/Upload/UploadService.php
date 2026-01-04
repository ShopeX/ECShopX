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
