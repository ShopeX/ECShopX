<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
