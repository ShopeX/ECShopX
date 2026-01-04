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

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use PromotionsBundle\Services\PromotionGroupsActivityService;

class PromotionGroupsActivity extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/promotions/groups",
     *     summary="获取拼团活动列表",
     *     tags={"营销"},
     *     description="获取拼团活动列表",
     *     operationId="getPromotionGroupsActivityList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="keywords", in="query", description="活动名称", type="integer"),
     *     @SWG\Parameter( name="view", in="query", description="1未开始 2进行中 3已结束", type="integer" ),
     *     @SWG\Parameter( name="group_goods_type", in="query", description="商品类型 normal:普通商品 services:服务类", type="integer" ),
     *     @SWG\Parameter( name="page", in="query", description="页数,默认1", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,默认20", type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="groups_activity_id", type="string", example="125", description="活动ID"),
     *                          @SWG\Property( property="act_name", type="string", example="社区拼团测试", description="活动名称"),
     *                          @SWG\Property( property="goods_id", type="string", example="5436", description="商品id"),
     *                          @SWG\Property( property="group_goods_type", type="string", example="normal", description="团购活动商品类型"),
     *                          @SWG\Property( property="pics", type="string", example="https://bbctest.aixue7.com/image/1/2021/01/16/4952d45242f558ca400e02d2e1793818a60C2BuMpEwdoo62YljLiTaak4Q0NI0y", description="商品图片"),
     *                          @SWG\Property( property="act_price", type="string", example="1", description="活动价格"),
     *                          @SWG\Property( property="person_num", type="string", example="2", description="拼团人数"),
     *                          @SWG\Property( property="begin_time", type="string", example="1609430400", description="开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1614527999", description="结束时间"),
     *                          @SWG\Property( property="limit_buy_num", type="string", example="10", description="限买数量"),
     *                          @SWG\Property( property="limit_time", type="string", example="2", description="成团时效(单位时)"),
     *                          @SWG\Property( property="store", type="string", example="99", description="拼团库存"),
     *                          @SWG\Property( property="free_post", type="string", example="true", description="是否包邮"),
     *                          @SWG\Property( property="rig_up", type="string", example="true", description="是否展示开团列表"),
     *                          @SWG\Property( property="robot", type="string", example="true", description="成团机器人"),
     *                          @SWG\Property( property="share_desc", type="string", example="分享文字信息", description="分享描述"),
     *                          @SWG\Property( property="disabled", type="string", example="false", description="是否禁用。0:可用；1:禁用"),
     *                          @SWG\Property( property="created", type="string", example="1611124546", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1611131222", description=" | 修改时间"),
     *                          @SWG\Property( property="remaining_time", type="string", example="2961220", description="剩余时间"),
     *                          @SWG\Property( property="last_seconds", type="string", example="2961220", description="剩余秒数"),
     *                          @SWG\Property( property="show_status", type="string", example="noend", description="展示状态 nostart:未开始 noend:未结束"),
     *                          @SWG\Property( property="goods_name", type="string", example="商品名称", description="商品名称"),
     *                          @SWG\Property( property="activity_status", type="string", example="2", description="活动状态 1:未开始 2:进行中 3:已结束"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getPromotionGroupsActivityList(Request $request)
    {
        // ShopEx framework
        $authInfo = $request->get('auth');
        if (!in_array($request->input('view', 1), [1, 2])) {
            throw new ResourceException(trans('PromotionsBundle.get_group_activity_list_failed'));
        }
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $params['company_id'] = $authInfo['company_id'];
        if ($request->input('keywords', null)) {
            $params['act_name'] = $request->input('keywords');
        }
        if ($request->input('view', 0)) {
            $params['view'] = $request->input('view');
        }

        if ($request->input('group_goods_type')) {
            $params['group_goods_type'] = $request->input('group_goods_type');
        } else {
            $params['group_goods_type'] = 'services';
        }

        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $result = $promotionGroupsActivityService->getList($params, $page, $pageSize);
        return $this->response->array($result);
    }
}
