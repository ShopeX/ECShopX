<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\ShopexCrm;

class GetMemberListService
{
    // 456353686f7058
    private $apiName = 'getMemberList';

    public function GetMemberList($mobile)
    {
        // 456353686f7058
        $data['mobiles'] = $mobile;
        $data['source'] = 'custom_source1';
        $request = new Request();
        $result = $request->sendRequest($this->apiName, $data);
        if (!empty($result)) {
            $result = json_decode($result, true);
        }
        return $result;
    }
}
