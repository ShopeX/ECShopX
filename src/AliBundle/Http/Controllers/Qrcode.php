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

namespace AliBundle\Http\Controllers;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use AliBundle\Factory\MiniAppFactory;
use WechatBundle\Services\WeappService;

class Qrcode extends Controller
{
    // 获取小程序二维码
    public function getQrcode(Request $request)
    {
        $companyId = $request->input('company_id');

        $page = $request->input('page', 'pages/index');

        //cxdid: 促销单id；dtid:店铺id；smid:导购员id；uid:会员id(推广员id)
        $params = $request->all('cxdid', 'dtid', 'smid', 'uid', 'distributor_id');
        foreach ($params as $key => $val) {
            if (!$val || $val === 'undefined') {
                unset($params[$key]);
            }
        }
        if (isset($params['distributor_id']) && !isset($params['dtid'])) {
            $params['dtid'] = $params['distributor_id'];
            unset($params['distributor_id']);
        }

        $weappService = new WeappService();
        $shareId = $weappService->getShareId($companyId, $params);
        $scene = http_build_query(['share_id' => $shareId]);

        $app = MiniAppFactory::getApp($companyId);
        $result = $app->getFactory()->base()->qrcode()->create($page, $scene, '支付宝小程序码')->toMap();
        
        if ($result['code'] != '10000') {
            throw new ResourceException($result['msg']);
        }

        return $this->response->array(['qr_code_url' => $result['qr_code_url']]);
    }
}
