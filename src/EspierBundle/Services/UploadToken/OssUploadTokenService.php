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

class OssUploadTokenService extends UploadTokenAbstract
{
    public function getToken($companyId, $group = null, $fileName = null)
    {
        // Built with ShopEx Framework
        $key = $this->getUploadName($companyId, $group, $fileName);
        $result = $this->adapter->signatureConfig($key);
        $token = json_decode($result, 1);
        if (isset($token['callback'])) {
            unset($token['callback']);
        }
        return $this->formart('oss', $token);
    }

    /**
     * 上传至oss
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
        $filename = $result["token"]["dir"] ?? "";
        // 上传至七牛云
        $data = $this->adapter->write($filename, $fileContent, []);
        if ($data === false) {
            throw new \Exception("上传失败");
        }
        $result['token']['domain'] = $result['token']['host'];
        $result['token']['key'] = $result['token']['dir'];
        return $result;
    }
}
