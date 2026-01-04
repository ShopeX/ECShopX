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

namespace EspierBundle\Tests\Services\Config;

use EspierBundle\Services\Config\ConfigRequestFieldsService;
use EspierBundle\Services\Config\ValidatorService;

class ValidatorTest extends \EspierBundle\Services\TestBaseService
{
    protected $companyId = 2;
    protected $moduleType = ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO;

    /**
     * 测试验证方法是否有误
     */
    public function testCheck()
    {
        try {
            $data = [
//                "code"          => "0918vrFa1PUuZA06RVFa1ODil428vrF5",
//                "user_type"     => "wechat",
//                "auth_type"     => "wxapp",
//                "encryptedData" => "DjN3k46zLsUn2emaqfbPfIGzCWr8X5zQog7tUYZe2ibgScGxsR1FZrw87mw+gHflD4xCHU9+5UTfQZZY4BOJhl/MaNIK1olXO0S/gqAUTbZfXB90hslG5lwSKd,+iZgqxc3LqXf+d4UvEOZl88Mue84hyjLVTM5uehRE6SV/xpCeruMQPlTS0sVS05RdkqLWOp74pXNR5lHedkV/qd/mEAA==",
//                "iv"            => "oJOT+ntIOrLc8EpMKvjROg==",
//                "appid"         => "wxbc41819b322cbd3f",
//                "company_id"    => 43,
//                "mobile"        => "17321265274",
//                "region_mobile" => "17321265274",
//                "username"      => "173212652741",
//                "address"       => "",
//                "sex"           => "未知",
//                "birthday"      => "",
//                "union_id"      => "oiThC1mTuoMdjy46Sz-EetD-wx84",
//                "open_id"       => "oBbMP0UFOyGsTJt_Nvqmk9uhYP8Q",
//                "b93392728d703b9790d7d64f7c63eb15" => -1
                "user_name" => "",
                "avatar" => "",
                "mobile" => "18516371552",
                "sex" => "0",
                "username" => "",
                "inviter_id" => "",
                "company_id" => "1",
            ];
            (new ValidatorService())->check($this->companyId, $this->moduleType, $data, false);
            $status = true;
        } catch (\Exception $exception) {
            $status = false;
        }
        $this->assertTrue($status);
    }
}
