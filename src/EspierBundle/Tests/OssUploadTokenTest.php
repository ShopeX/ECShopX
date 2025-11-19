<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Tests;

use EspierBundle\Services\TestBaseService;
use EspierBundle\Services\UploadTokenFactoryService;

class OssUploadTokenTest extends TestBaseService
{
    public function testFactory()
    {
        $data = UploadTokenFactoryService::create('file')->getToken(1, 'item');
        dd($data);
    }
}
