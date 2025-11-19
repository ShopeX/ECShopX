<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
