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
