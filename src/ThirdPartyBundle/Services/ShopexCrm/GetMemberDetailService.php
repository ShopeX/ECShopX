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

namespace ThirdPartyBundle\Services\ShopexCrm;

class GetMemberDetailService
{
    private $apiName = 'getMemberDetail';

    public function getMemberDetail($user_id)
    {
        $data['platform_id'] = 'shopex';
        $data['ext_member_id'] = $user_id;
        $data['source'] = 'custom_source1';
        $request = new Request();
        $result = $request->sendRequest($this->apiName, $data);
        if (!empty($result)) {
            $result = json_decode($result, true);
        }
        return $result;
    }
}
