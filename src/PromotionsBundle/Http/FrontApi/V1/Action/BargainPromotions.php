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

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\BargainPromotionsService;

class BargainPromotions extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/bargains",
     *     summary="获取砍价活动列表",
     *     tags={"营销"},
     *     description="获取活动中的砍价活动列表",
     *     operationId="getBargainList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,默认1",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,默认50",
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="bargain_id", type="string", example="43", description="砍价活动ID"),
     *                          @SWG\Property( property="title", type="string", example="超绝茄子砍价", description="活动名称"),
     *                          @SWG\Property( property="ad_pic", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrdtmicAt4yB9TY93OBlRubpPk6CQhVOYZPzuZEcQEBvibqxwjFqszl2uPYLzYty6oIGxv6AyG5X99Ew/0?wx_fmt=jpeg", description="活动广告图"),
     *                          @SWG\Property( property="item_name", type="string", example="微信助力砍价专用", description="商品名称"),
     *                          @SWG\Property( property="item_pics", type="string", example="http://mmbiz.qpic.cn/mmbiz_gif/Hw4SsicubkrdgG6icibvyUTIsSsRw7k1QPx5PHqljSnfCPY3MGV4Q7YyTHdKwvMmDibV7dy33vRuKNAm8uxehysSibg/0?wx_fmt=gif", description="商品图片"),
     *                          @SWG\Property( property="item_intro", type="string", example="null", description="商品详情"),
     *                          @SWG\Property( property="mkt_price", type="string", example="9900", description="市场价格,单位为‘分’"),
     *                          @SWG\Property( property="price", type="string", example="5000", description="销售金额,单位为‘分’"),
     *                          @SWG\Property( property="limit_num", type="string", example="100", description="商品限购数量"),
     *                          @SWG\Property( property="order_num", type="string", example="0", description="已购买数量"),
     *                          @SWG\Property( property="bargain_rules", type="string", example="规则描述", description="规则描述"),
     *                          @SWG\Property( property="bargain_range", type="object",
     *                                  @SWG\Property( property="min", type="string", example="0", description=""),
     *                                  @SWG\Property( property="max", type="string", example="0", description=""),
     *                          ),
     *                          @SWG\Property( property="people_range", type="object",
     *                                  @SWG\Property( property="min", type="string", example="1", description="助力最小人数"),
     *                                  @SWG\Property( property="max", type="string", example="5", description="助力最大人数"),
     *                          ),
     *                          @SWG\Property( property="min_price", type="string", example="100", description="每个人最少能砍的价钱,单位为‘分’"),
     *                          @SWG\Property( property="begin_time", type="string", example="1610640000", description="活动开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1611936000", description="活动结束时间"),
     *                          @SWG\Property( property="share_msg", type="string", example="123", description="分享内容"),
     *                          @SWG\Property( property="help_pics", type="array",
     *                              @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkreDZrghuGAXq3g3licgEueUpChb9WiafJbicGvByicJsMVK8SFx6XEXcoyIq8EweCfffc05R5TmRscXWg/0?wx_fmt=png", description="翻拍图片url"),
     *                          ),
     *                          @SWG\Property( property="created", type="string", example="1610698728", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1610698728", description="修改时间"),
     *                          @SWG\Property( property="is_expired", type="string", example="false", description="是否无效"),
     *                          @SWG\Property( property="item_id", type="string", example="5427", description="商品id"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getBargainList(Request $request)
    {
        $validator = app('validator')->make($request->input(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:500',
        ]);

        $authInfo = $request->get('auth');
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 50);

        $bargainService = new BargainPromotionsService();
        $filter = [
            'company_id' => $authInfo['company_id'],
            'end_time|gt' => time(),
            'begin_time|lte' => time(),
        ];
        $offset = ($page - 1) * $limit;
        $result = $bargainService->getBargainList($filter, $offset, $limit);
        return $this->response->array($result);
    }
}
