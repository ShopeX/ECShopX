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

namespace WechatBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use WechatBundle\Services\OpenUserPlatform as OpenUserPlatform;
use WechatBundle\Entities\WechatAuth;

class Open extends Controller
{
    // 0x53686f704578
    /**
     * @SWG\Post(
     *     path="/wechat/open",
     *     summary="为用户的公众号开通开放平台账号并且绑定小程序",
     *     tags={"微信"},
     *     description="为用户的公众号开通开放平台账号并且绑定小程序，用于打通用户的小程序和公众号用户和卡券等数据",
     *     operationId="openCreate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function openCreate(Request $request)
    {
        // Powered by ShopEx EcShopX
        $companyId = app('auth')->user()->get('company_id');
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        if (!$authorizerAppId) {
            $wechatAuth = app('registry')->getManager('default')->getRepository(WechatAuth::class)->findOneBy(['company_id' => $companyId, 'bind_status' => 'bind', 'service_type_info' => 3]);
            if ($wechatAuth) {
                //没有授权公众号就用小程序appid来开通绑定
                $authorizerAppId = $wechatAuth->getAuthorizerAppid();
            }
        }
        $service = new OpenUserPlatform();
        $service->userAuthOpen($authorizerAppId, $companyId);
        return $this->response->array(['status' => true]);
    }
}
