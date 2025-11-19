<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
