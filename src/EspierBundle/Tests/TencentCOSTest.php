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

namespace EspierBundle\Tests;

use EspierBundle\Services\TestBaseService;

class TencentCOSTest extends TestBaseService
{
    public function testCRUD()
    {
        $disk = app('filesystem')->disk('cos');
        $fileName = 'test.txt';
        $contents = '20022303100810010';
        $res = $disk->put($fileName, $contents);
        $this->assertEquals(true, $res);

        $exists = $disk->has($fileName);
        $this->assertEquals(true, $exists);

        $url = $disk->getUrl($fileName);
        $ossContents = $disk->read($fileName);
        $this->assertEquals($contents, $ossContents);

        #$res = $disk->delete($fileName);
        #$this->assertEquals(true,$res);

        // $config = $disk->signatureConfig('2002/10012.22');

        // $token = json_decode($config,1 );
        // dd($token);
        // dd(base64_decode($token['callback']));
    }
}
