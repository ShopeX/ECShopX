<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
