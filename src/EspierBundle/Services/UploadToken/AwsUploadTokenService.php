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

use Aws\Sts\StsClient;
use Aws\S3\S3Client;
use Aws\S3\PostObjectV4;

class AwsUploadTokenService extends UploadTokenAbstract
{
    // 获取临时aksk
    private function getAkSk()
    {
    }

    public function getToken($companyId, $group = null, $fileName = null)
    {
        // 获取配置信息
        $disks_config = config('filesystems.disks.import-' . $this->fileType);

        $bucket = $disks_config['bucket'];  // 储存桶
        $region = $disks_config['region'];  // 地区
        $key = $this->getUploadName($companyId, $group, $fileName);

        // 命令获取临时ak，sk
        if (!empty($disks_config['curl'])) {
            // dump('命令获取临时ak，sk');
        }

        // 授权信息
        $sts = new StsClient(['version' => 'latest', 'region' => $region]);
        // 判断是否有role_arn配置
        if (empty($disks_config['arn'])) {
            // 用户临时凭证
            $sessionToken = $sts->getSessionToken();
        } else {
            $sessionToken = $sts->AssumeRole([
                'RoleArn' => $disks_config['arn'],
                'RoleSessionName' => 'Token',// 这个可以随意填写
            ]);
        }

        $client = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $sessionToken['Credentials']['AccessKeyId'],
                'secret' => $sessionToken['Credentials']['SecretAccessKey'],
                'token' => $sessionToken['Credentials']['SessionToken']
            ]
        ]);

        // 预签名 POST
        $formInputs = [ 'key' => $key ];
        $options = [['bucket' => $bucket], ['key' => $key]];
        $expires = '+2 hours';
        $postObject = new PostObjectV4($client, $bucket, $formInputs, $options, $expires);

        // 获取要在HTML表单上设置的属性，例如action、method、enctype
        $formAttributes = $postObject->getFormAttributes();

        //获取表单输入字段。这将包括在中设置为表单输入的任何内容
        //构造函数、提供的JSON策略、AWS访问密钥ID和
        //授权签名。
        $formInputs = $postObject->getFormInputs();
        // 数据处理
        $formInputs = $this->handleformInputs($formInputs);

        // 返回给前端的内容
        $result = $sessionToken['Credentials'];
        $result['Region'] = $disks_config['region'];
        $result['Bucket'] = $bucket;
        $result['endpoint'] = $disks_config['endpoint'];
        $result['formAttributes'] = $formAttributes;
        $result['formInputs'] = $formInputs;

        return $this->formart('aws', $result);
    }

    public function upload($companyId, $group = null, $fileName = null, string $fileContent = ""): array
    {
        // CONST: 1E236443
        $result = $this->getToken($companyId, $group, $fileName);

        $filename = $result["token"]["formInputs"]['key'] ?? "";

        $data = $this->adapter->write($filename, $fileContent, []);
        if ($data === false) {
            throw new \Exception("上传失败");
        }
        $result['token']['domain'] = $result['token']['endpoint'];
        $result['token']['key'] = $result["token"]["formInputs"]['key'];
        return $result;
    }

    // 数据处理   为方便前端使用  key 中间的 - 去掉
    private function handleformInputs($formInputs = [])
    {
        // CONST: 1E236443
        $formInputs_return = [];
        foreach ($formInputs as $k => $v) {
            $formInputs_return[str_replace('-', '', $k)] = $v;
        }
        return $formInputs_return;
    }
}
