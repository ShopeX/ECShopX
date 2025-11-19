<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Tests;

use EspierBundle\Services\TestBaseService;

class AliyunOSSTest extends TestBaseService
{
    public function testCRUD()
    {
        $disk = app('filesystem')->disk('oss');
        $fileName = 'test.txt';
        $contents = '20022303100810010';
        $res = $disk->put($fileName, $contents);
        $this->assertEquals(true, $res);

        $exists = $disk->has($fileName);
        $this->assertEquals(true, $exists);

        $url = $disk->getUrl($fileName);
        $ossContents = $disk->read($fileName);
        $this->assertEquals($contents, $ossContents);

        $res = $disk->delete($fileName);
        $this->assertEquals(true, $res);

        $config = $disk->signatureConfig('2002/10012.22');

        $token = json_decode($config, 1);
        dd($token);
        dd(base64_decode($token['callback']));
    }
}
