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

use EspierBundle\Services\CosSdk\CosSts;

class TencentCosTokenService extends UploadTokenAbstract
{
    public function getToken($companyId, $group = null, $fileName = null)
    {
        $pathinfo = pathinfo($fileName);
        $extension = $pathinfo['extension'];
        $fileName = md5(uniqid('source', true)).'.'.$extension;
        $key = '/'.$this->getUploadName($companyId, $group, $fileName);
        $disks_config = config('filesystems.disks.import-' . $this->fileType);

        $token = $this->adapter->getAuthorization('put', $key);
        $result['region'] = $disks_config['region'];
        $result['bucket'] = $disks_config['bucket'];
        $result['url'] = $key;
        //$result['key'] = $tempKeys;
        $result['token'] = $token;
        return $this->formart('cosv5', $result);
        //$key = $this->getUploadName($companyId, $group, $fileName);

    }

    public function upload($companyId, $group = null, $fileName = null, string $fileContent = ""): array
    {

        $uploadName = $this->getUploadName($companyId, $group, $fileName);
        $this->adapter->write($uploadName, $fileContent);
        $hosts = $this->adapter->getAdapter()->getHost();

        $data['token']['domain'] = $hosts;
        $data['token']['key'] = $uploadName;
//        $adapter = $this->adapter->getAdapter();
        //$link = $adapter->getTemporaryUrl($uploadName,date_create('2023-08-28 16:00:00'));
//        dd($link);
        return $data;

    }


}
