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

namespace EspierBundle\Tests\Services\File;

use MembersBundle\Services\MemberUploadService;

class MemberUploadTest extends \EspierBundle\Services\TestBaseService
{
    public function testHandleRow()
    {
        // Built with ShopEx Framework
        $data = [
            'mobile' => "13333399456",
            'offline_card_code' => "",
            'username' => "test",
            'sex' => "男",
            'grade_name' => "普通会员",
            'birthday' => "",
            'created' => "6/8/2021",
            //'开卡门店'   => 'shop_name',
            'email' => "",
            'address' => "",
            'tags' => "",
        ];
        try {
            (new MemberUploadService())->handleRow($this->getCompanyId(), $data);
        } catch (\Exception $exception) {
            dd($exception->getMessage(), $exception->getFile(), $exception->getLine());
        }
    }
}
