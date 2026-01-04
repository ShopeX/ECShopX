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

namespace WechatBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use WechatBundle\Services\WeappService;

class Qrcode extends Controller
{
    /**
     * @SWG\Get(
     *     path="/admin/wxapp/qrcode",
     *     summary="获取小程序二维码",
     *     tags={"微信平台"},
     *     description="获取小程序二维码",
     *     operationId="getQrcode",
     *     @SWG\Parameter( in="query", type="string", required=true, name="appid", description="AppId" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="temp_name"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="base64Image", type="string", example=""),
     *          ),
     *     )),
     * )
     */
    public function getQrcode(Request $request)
    {
        $wxappAppid = $request->input('appid');
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        if (!$wxappAppid) {
            $templateName = $request->input('temp_name', 'yykweishop');
            $weappService = new WeappService();
            $wxappAppid = $weappService->getWxappidByTemplateName($companyId, $templateName);
        }

        $weappService = new WeappService($wxappAppid, $companyId);

        $input = $request->input();
        unset($input['appid']);
        unset($input['company_id']);
        unset($input['temp_name']);

        if (isset($input['id']) && !isset($input['page'])) {
            $page = 'pages/goodsdetail';
        } else {
            $page = ($input['page'] ?? null) ? $input['page'] : 'pages/index';
        }

        unset($input['page']);

        //dtid:店铺id；smid:导购员id；uid:会员id(推广员id//dtid:店铺id；spid:导购员id；uid:会员id())
        foreach ($input as $key => $val) {
            if (!$val || $val === 'undefined') {
                unset($input[$key]);
            }
        }

        $scene = http_build_query($input);
        $qrcode = $weappService->createWxaCodeUnlimit($scene, $page);



        $base64 = 'data:image/jpg;base64,' . base64_encode($qrcode);
        return $this->response->array(['base64Image' => $base64]);
    }
}
