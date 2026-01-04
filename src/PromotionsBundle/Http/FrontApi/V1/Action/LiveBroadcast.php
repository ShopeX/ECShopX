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

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Response;
use Illuminate\Http\Request;
use PromotionsBundle\Services\PromotionActivity;

/**
 * 微信直播相关
 */
class LiveBroadcast extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/live/list",
     *     summary="获取直播列表",
     *     tags={"直播"},
     *     description="获取直播列表。接口文档的返回结果为直播列表",
     *     operationId="getLiveList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码 默认为1", required=true, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="一页信息数 默认为10", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="name", type="string", example="直播房间名", description="直播间名称"),
     *                          @SWG\Property( property="cover_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/donnRWvvtUsNpYibMd9Cib92zWHXYzuKmZbHTsiczoHE9l3ADicvD1kOOboicWGUsicpLx2bdL8diaKbq3TJ0qibNWwsKw/0", description="直播间图片"),
     *                          @SWG\Property( property="start_time", type="string", example="1604473876", description="直播间开始时间，列表按照start_time降序排列"),
     *                          @SWG\Property( property="end_time", type="string", example="1604474821", description="直播计划结束时间"),
     *                          @SWG\Property( property="anchor_name", type="string", example="里斯", description="主播名"),
     *                          @SWG\Property( property="roomid", type="string", example="1", description="直播间ID"),
     *                          @SWG\Property( property="goods", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="cover_img", type="string", example="http://http:\/\/mmbiz.qpic.cn\/mmbiz_jpg\/Rl1RuuhdstSfZa8EEljedAYcbtX3Ejpdl2et1tPAQ37bdicnxoVialDLCKKDcPBy8Iic0kCiaiaalXg3EbpNKoicrweQ\/0?wx_fmt=jpeg", description="商品封面图链接"),
     *                                  @SWG\Property( property="url", type="string", example="pages/index/index.html", description="商品小程序路径"),
     *                                  @SWG\Property( property="price", type="string", example="1889", description="商品价格（分）"),
     *                                  @SWG\Property( property="name", type="string", example="茶杯", description="商品名称"),
     *                                  @SWG\Property( property="price2", type="string", example="0", description="商品价格，使用方式看price_type"),
     *                                  @SWG\Property( property="price_type", type="string", example="1", description="价格类型，1：一口价（只需要传入price，price2不传） 2：价格区间（price字段为左边界，price2字段为右边界，price和price2必传） 3：显示折扣价（price字段为原价，price2字段为现价， price和price2必传）"),
     *                                  @SWG\Property( property="goods_id", type="string", example="256", description="商品id"),
     *                                  @SWG\Property( property="third_party_appid", type="string", example="wx3d0fae56402d8a81", description="第三方商品appid ,当前小程序商品则为空"),
     *                              ),
     *                          ),
     *                          @SWG\Property( property="live_status", type="string", example="103", description="直播间状态。101：直播中，102：未开始，103已结束，104禁播，105：暂停，106：异常，107：已过期"),
     *                          @SWG\Property( property="share_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/donnRWvvtUsNpYibMd9Cib92zWHXYzuKmZXRxRJnKv95Iot35qfaHecIXUZwicdua6MBPZ9ZFfATMkV87sxbkd5XQ/0", description="直播间分享图链接"),
     *                          @SWG\Property( property="live_type", type="string", example="0", description="直播类型，1 推流 0 手机直播"),
     *                          @SWG\Property( property="close_like", type="string", example="0", description="是否关闭点赞 【0：开启，1：关闭】（若关闭，观众端将隐藏点赞按钮，直播开始后不允许开启）"),
     *                          @SWG\Property( property="close_goods", type="string", example="0", description="是否关闭货架 【0：开启，1：关闭】（若关闭，观众端将隐藏商品货架，直播开始后不允许开启）"),
     *                          @SWG\Property( property="close_comment", type="string", example="0", description="是否关闭评论 【0：开启，1：关闭】（若关闭，观众端将隐藏评论入口，直播开始后不允许开启）"),
     *                          @SWG\Property( property="close_kf", type="string", example="1", description="是否关闭客服 【0：开启，1：关闭】 默认关闭客服（直播开始后允许开启）"),
     *                          @SWG\Property( property="close_replay", type="string", example="1", description="是否关闭回放 【0：开启，1：关闭】默认关闭回放（直播开始后允许开启）"),
     *                          @SWG\Property( property="is_feeds_public", type="string", example="1", description="是否开启官方收录，1 开启，0 关闭"),
     *                          @SWG\Property( property="live_time_text", type="string", example="12小时30分", description="视频时长文字"),
     *                          @SWG\Property( property="creater_openid", type="string", example="oO54hQFeIbZV1f9wyY6T0cob05Uc", description="创建者openid"),
     *                          @SWG\Property( property="feeds_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/donnRWvvtUsNpYibMd9Cib92zWHXYzuKmZqTicia13icyfJXORib0CBKV5vibuaCvtyXN09xibR2ElwBaQbQJL9qtJCLDQ/0", description="官方收录封面"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total_count", type="string", example="1", description="拉取房间总数"),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构" )
     * )
     */
    public function getLiveList(Request $request): Response
    {
        $baseParam = $this->_checkBaseParam($request);

        $list = (new PromotionActivity())->getliveRoomsList($baseParam['appid'], $baseParam['page'], $baseParam['page_size'], '', '');

        return $this->response->array($list);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/replay/list",
     *     summary="获取特定直播间的视频回放列表",
     *     tags={"直播"},
     *     description="获取已结束直播间的回放源视频。",
     *     operationId="getReplayList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码 默认为1", required=true, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="一页信息数 默认为10", required=true, type="integer"),
     *     @SWG\Parameter( name="room_id", in="query", description="直播间id 必填", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="expire_time", type="string", example="", description="回放视频url过期时间"),
     *                          @SWG\Property( property="create_time", type="string", example="", description="回放视频创建时间"),
     *                          @SWG\Property( property="media_url", type="string", example="", description="回放视频链接"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total_count", type="string", example="1", description="回放视频片段个数"),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构" ) )
     * )
     */
    public function getReplayList(Request $request): Response
    {
        $baseParam = $this->_checkBaseParam($request);
        $roomId = $request->get('room_id');

        if (!$roomId) {
            throw new ResourceException(trans('PromotionsBundle.live_room_id_required'));
        }

        $list = (new PromotionActivity())->getliveRoomsList($baseParam['appid'], $baseParam['page'], $baseParam['page_size'], $roomId);

        return $this->response->array($list);
    }

    /**
     * 检测基础参数
     *
     * @param Request $request
     * @return array
     */
    private function _checkBaseParam(Request $request): array
    {
        $authInfo = $request->get('auth');
        $authorizerAppId = $authInfo['wxapp_appid'];
        if (!$authorizerAppId) {
            throw new ResourceException(trans('PromotionsBundle.wechat_miniprogram_required'));
        }

        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'page_size' => 'required|integer|min:1|max:20',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('PromotionsBundle.required_params_missing'), $validator->errors());
        }

        $page = $inputData['page'] ? (int)$inputData['page'] : 1;
        $pageSize = $inputData['page_size'] ? (int)$inputData['page_size'] : 10;

        return [
            'appid' => $authorizerAppId,
            'page' => $page,
            'page_size' => $pageSize,
            'input_data' => $inputData
        ];
    }
}
