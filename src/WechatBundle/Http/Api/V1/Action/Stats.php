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
use WechatBundle\Services\StatsServices as StatsServices;

class Stats extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wechat/stats/userweeksummary",
     *     summary="最近七天用户数据统",
     *     tags={"微信"},
     *     description="最近七天用户数据统",
     *     operationId="userWeekSummary",
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
     *                     @SWG\Property(property="ref_date", type="stirng", description="日期"),
     *                     @SWG\Property(property="new_user", type="stirng", description="新增用户"),
     *                     @SWG\Property(property="cancel_user", type="stirng", description="取消关注用户"),
     *                     @SWG\Property(property="cumulate_user", type="stirng", description="累积用户"),
     *                     @SWG\Property(property="add_user", type="stirng", description="净增用户"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function userWeekSummary(Request $request)
    {
        // Powered by ShopEx EcShopX
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $service = new StatsServices($authorizerAppId);
        $list = $service->userWeekSummary();
        return $this->response->array($list);
    }
}
