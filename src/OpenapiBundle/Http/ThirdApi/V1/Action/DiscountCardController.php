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

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;
use SystemLinkBundle\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\DiscountCardService;

class DiscountCardController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/ecx.discountcard.info",
     *     summary="获取卡券详情",
     *     tags={"优惠券"},
     *     description="获取卡券详情",
     *     operationId="getDiscountCardDetail",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.discountcard.info" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="card_id", description="卡券ID" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="errcode", type="string", example="0"),
     *          @SWG\Property( property="errmsg", type="string", example="success"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="card_id", type="string", example="599"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="card_type", type="string", example="discount"),
     *                  @SWG\Property( property="brand_name", type="string", example="null"),
     *                  @SWG\Property( property="logo_url", type="string", example="null"),
     *                  @SWG\Property( property="title", type="string", example="品牌折扣券测试"),
     *                  @SWG\Property( property="color", type="string", example="#000000"),
     *                  @SWG\Property( property="notice", type="string", example="null"),
     *                  @SWG\Property( property="description", type="string", example="测试"),
     *                  @SWG\Property( property="date_type", type="string", example="DATE_TYPE_FIX_TIME_RANGE"),
     *                  @SWG\Property( property="begin_date", type="string", example="1611763200"),
     *                  @SWG\Property( property="end_date", type="string", example="1611849600"),
     *                  @SWG\Property( property="fixed_term", type="string", example="null"),
     *                  @SWG\Property( property="service_phone", type="string", example="null"),
     *                  @SWG\Property( property="center_title", type="string", example="null"),
     *                  @SWG\Property( property="center_sub_title", type="string", example="null"),
     *                  @SWG\Property( property="center_url", type="string", example="null"),
     *                  @SWG\Property( property="custom_url_name", type="string", example="null"),
     *                  @SWG\Property( property="custom_url", type="string", example="null"),
     *                  @SWG\Property( property="custom_url_sub_title", type="string", example="null"),
     *                  @SWG\Property( property="promotion_url_name", type="string", example="null"),
     *                  @SWG\Property( property="promotion_url", type="string", example="null"),
     *                  @SWG\Property( property="promotion_url_sub_title", type="string", example="null"),
     *                  @SWG\Property( property="get_limit", type="string", example="1"),
     *                  @SWG\Property( property="use_limit", type="string", example="null"),
     *                  @SWG\Property( property="can_share", type="string", example="false"),
     *                  @SWG\Property( property="can_give_friend", type="string", example="false"),
     *                  @SWG\Property( property="abstract", type="string", example="null"),
     *                  @SWG\Property( property="icon_url_list", type="string", example="null"),
     *                  @SWG\Property( property="text_image_list", type="string", example="null"),
     *                  @SWG\Property( property="gift", type="string", example="null"),
     *                  @SWG\Property( property="default_detail", type="string", example="null"),
     *                  @SWG\Property( property="discount", type="string", example="1"),
     *                  @SWG\Property( property="least_cost", type="string", example="0"),
     *                  @SWG\Property( property="reduce_cost", type="string", example="0"),
     *                  @SWG\Property( property="deal_detail", type="string", example="null"),
     *                  @SWG\Property( property="accept_category", type="string", example="null"),
     *                  @SWG\Property( property="reject_category", type="string", example="null"),
     *                  @SWG\Property( property="object_use_for", type="string", example="null"),
     *                  @SWG\Property( property="can_use_with_other_discount", type="string", example="false"),
     *                  @SWG\Property( property="quantity", type="string", example="10"),
     *                  @SWG\Property( property="use_all_shops", type="string", example="true"),
     *                  @SWG\Property( property="rel_shops_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *                  @SWG\Property( property="created", type="string", example="1611823911"),
     *                  @SWG\Property( property="updated", type="string", example="1611823911"),
     *                  @SWG\Property( property="use_scenes", type="string", example="ONLINE"),
     *                  @SWG\Property( property="receive", type="string", example="true"),
     *                  @SWG\Property( property="self_consume_code", type="string", example="0"),
     *                  @SWG\Property( property="use_platform", type="string", example="mall"),
     *                  @SWG\Property( property="most_cost", type="string", example="99999900"),
     *                  @SWG\Property( property="use_bound", type="string", example="4"),
     *                  @SWG\Property( property="tag_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *                  @SWG\Property( property="brand_ids", type="object",
     *                          @SWG\Property( property="1", type="string", example="1382"),
     *                  ),
     *                  @SWG\Property( property="apply_scope", type="string", example="大屏"),
     *                  @SWG\Property( property="card_code", type="string", example=""),
     *                  @SWG\Property( property="card_rule_code", type="string", example=""),
     *                  @SWG\Property( property="distributor_info", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *                  @SWG\Property( property="use_all_distributor", type="string", example="true"),
     *                  @SWG\Property( property="use_all_items", type="string", example="brand"),
     *                  @SWG\Property( property="rel_category_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *                  @SWG\Property( property="item_category", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *                  @SWG\Property( property="rel_tag_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *                  @SWG\Property( property="tag_list", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *                  @SWG\Property( property="rel_brand_ids", type="object",
     *                          @SWG\Property( property="1", type="string", example="1382"),
     *                  ),
     *                  @SWG\Property( property="brand_list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="attribute_id", type="string", example="1382"),
     *                          @SWG\Property( property="company_id", type="string", example="1"),
     *                          @SWG\Property( property="shop_id", type="string", example="0"),
     *                          @SWG\Property( property="attribute_type", type="string", example="brand"),
     *                          @SWG\Property( property="attribute_name", type="string", example="大屏"),
     *                          @SWG\Property( property="attribute_memo", type="string", example="null"),
     *                          @SWG\Property( property="attribute_sort", type="string", example="1"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0"),
     *                          @SWG\Property( property="is_show", type="string", example="true"),
     *                          @SWG\Property( property="is_image", type="string", example="true"),
     *                          @SWG\Property( property="image_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkrdnwoLMY38PLNULch2rPgsGb4NCVCC4EGa8EFs2MPCSbzJolznV64F0L5VetQvyE2ZrCcIb1ZALEA/0?wx_fmt=png"),
     *                          @SWG\Property( property="created", type="string", example="1605183288"),
     *                          @SWG\Property( property="updated", type="string", example="1605183288"),
     *                          @SWG\Property( property="attribute_code", type="string", example="null"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="time_limit_type", type="array",
     *                      @SWG\Items( type="string", example="MONDAY"),
     *                  ),
     *                  @SWG\Property( property="begin_time", type="string", example="1611763200"),
     *                  @SWG\Property( property="days", type="string", example="30"),
     *                  @SWG\Property( property="end_time", type="string", example="1611849600"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getDiscountCardDetail(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'card_id' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取卡券的详细信息出错.', $validator->errors());
        }

        $discountCardService = new KaquanService(new DiscountCardService());

        $filter['company_id'] = $request->get('auth')['company_id'];

        $filter['card_id'] = $request->input('card_id');

        $result = $discountCardService->getKaquanDetail($filter);
        if (!$result) {
            throw new ResourceException('获取失败.');
        }

        if ($result['time_limit']) {
            $timeLimit = $result['time_limit'];
            unset($result['time_limit']);
            $begin = "";
            $end = "";
            foreach ($timeLimit as $k => $value) {
                $result['time_limit_type'][$k] = $value['type'];
                if (isset($value['begin_hour']) && isset($value['end_hour'])) {
                    if (!$begin && !$end) {
                        $begin = $value['begin_hour'].":".$value['begin_minute'];
                        $end = $value['end_hour'].":".$value['end_minute'];
                        $result['time_limit_date'][1] = ['begin_time' => $begin,'end_time' => $end];
                        continue;
                    }
                    if ($begin !== $value['begin_hour'].":".$value['begin_minute']) {
                        $begin2 = $value['begin_hour'].":".$value['begin_minute'];
                        $end2 = $value['end_hour'].":".$value['end_minute'];
                        $result['time_limit_date'][2] = ['begin_time' => $begin2,'end_time' => $end2];
                    }
                }
            }
            if (isset($result['time_limit_type'])) {
                $result['time_limit_type'] = array_values(array_unique($result['time_limit_type']));
            }
        }

        //$result['date_type'] = $result['date_type'];
        $result['begin_time'] = intval($result['begin_date']);
        $result['days'] = isset($result['fixed_term']) ? intval($result['fixed_term']) : 30;
        $result['end_time'] = isset($result['end_date']) ? intval($result['end_date']) : 0;

        if (isset($result['discount']) && $result['discount'] > 0) {
            $result['discount'] = (100 - $result['discount']) / 10;
        }
        if (isset($result['least_cost']) && $result['least_cost']) {
            $result['least_cost'] = $result['least_cost'] / 100;
        }
        if (isset($result['reduce_cost']) && $result['reduce_cost']) {
            $result['reduce_cost'] = $result['reduce_cost'] / 100;
        }
        $this->api_response('true', 'success', $result);
    }



    /**
     * @SWG\Get(
     *     path="/ecx.discountcard.list",
     *     summary="获取卡券列表",
     *     tags={"优惠券"},
     *     description="获取卡券列表",
     *     operationId="getDiscountCardList",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.discountcard.list" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page", description="当前页，默认1" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="pageSize", description="分页条数，默认20" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="title", description="卡券名称搜索" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="card_type", description="卡券类型搜索" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="card_ids", description="卡券id搜索" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_valid", description="true:仅获取未过期的；false:不限制" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="errcode", type="string", example="0"),
     *          @SWG\Property( property="errmsg", type="string", example="success"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="card_id", type="string", example="604"),
     *                          @SWG\Property( property="title", type="string", example="测试"),
     *                          @SWG\Property( property="color", type="string", example="#000000"),
     *                          @SWG\Property( property="card_type", type="string", example="cash"),
     *                          @SWG\Property( property="date_type", type="string", example="DATE_TYPE_FIX_TIME_RANGE"),
     *                          @SWG\Property( property="card_type_val", type="string", example="代金券"),
     *                          @SWG\Property( property="discount", type="string", example="0"),
     *                          @SWG\Property( property="least_cost", type="string", example="1000"),
     *                          @SWG\Property( property="reduce_cost", type="string", example="100"),
     *                          @SWG\Property( property="quantity", type="string", example="10"),
     *                          @SWG\Property( property="get_num", type="string", example="0"),
     *                          @SWG\Property( property="card_num", type="string", example="0"),
     *                          @SWG\Property( property="takeEffect", type="string", example=""),
     *                          @SWG\Property( property="begin_time", type="string", example="2021-03-18 00:00:00"),
     *                          @SWG\Property( property="end_time", type="string", example="2021-03-19 00:00:00"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total_count", type="string", example="189"),
     *                  @SWG\Property( property="opts", type="object",
     *                          @SWG\Property( property="card_type", type="object",
     *                                  @SWG\Property( property="discount", type="string", example="折扣券"),
     *                                  @SWG\Property( property="cash", type="string", example="代金券"),
     *                                  @SWG\Property( property="gift", type="string", example="兑换券"),
     *                          ),
     *                          @SWG\Property( property="channel", type="object",
     *                                  @SWG\Property( property="wechat", type="string", example="微信·"),
     *                                  @SWG\Property( property="alipay", type="string", example="支付宝"),
     *                                  @SWG\Property( property="all", type="string", example="全渠道"),
     *                          ),
     *                          @SWG\Property( property="card_source", type="object",
     *                                  @SWG\Property( property="local", type="string", example="本地"),
     *                                  @SWG\Property( property="topi", type="string", example="TOPI"),
     *                                  @SWG\Property( property="zhibo", type="string", example="zhibo"),
     *                                  @SWG\Property( property="merch", type="string", example="商家券"),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getDiscountCardList(Request $request)
    {
        $page = $request->input('page', 1);
        $count = $request->input('pageSize', 20);
        $offset = ($page - 1) * $count;
        $filter = [];

        //仅获取未过期的优惠券
        $isValide = $request->input('is_valid', null);
        if ($isValide === 'true' || $isValide == 'true') {
            $filter['end_date'] = time();
        }

        if ($request->input('status')) {
            $filter['kq_status'] = $request->input('status');
        }
        if ($request->input('card_type')) {
            $filter['card_type'] = $request->input('card_type');
        } else {
            $filter['card_type'] = ['cash', 'discount', 'gift'];
        }
        if ($request->input('title')) {
            $filter['title|like'] = $request->input('title');
        }
//        if ($request->input('card_source')) {
//            $filter['card_source'] = $request->input('card_source');
//        }
//        if ($request->input('channel')) {
//            $filter['channel'] = $request->input('channel');
//        }
        if ($request->input('card_ids')) {
            $card_ids = explode(',', $request->input('card_ids'));
            $filter['card_id'] = $card_ids;
        }
        $discountCardService = new KaquanService(new DiscountCardService());
        $filter['company_id'] = $request->get('auth')['company_id'];
        //dd($filter);
        $result = $discountCardService->getKaquanList($offset, $count, $filter);

        $card_type_opts = [
            'discount' => '折扣券',
            'cash' => '代金券',
            'gift' => '兑换券',
        ];
        $channel_opts = [
            'wechat' => '微信·',
            'alipay' => '支付宝',
            'all' => '全渠道',
        ];
        $card_source_opts = [
            'local' => '本地',
            'topi' => 'TOPI',
            'zhibo' => 'zhibo',
            'merch' => '商家券',
        ];
        if ($result['list']) {
            foreach ($result['list'] as &$list) {
                $takeEffect = '';
                $begin_time = '';
                $end_time = '';
                if ($list['date_type'] == "DATE_TYPE_FIX_TIME_RANGE") {
                    $begin_time = date('Y-m-d H:i:s', $list['begin_date']);
                    $end_time = date('Y-m-d H:i:s', $list['end_date']);
                } elseif ($list['date_type'] == "DATE_TYPE_FIX_TERM") {
                    $list['begin_date'] = ($list['begin_date'] - time()) / 3600 / 24;
                    $begin = $list['begin_date'] == 0 ? "当" : $list['begin_date'];
                    $takeEffect = "领取后".$begin."天生效,".$list['fixed_term']."天有效";
                }
                $card = [
                    'card_id' => $list['card_id'],// 卡券ID
                    'title' => $list['title'],// 卡券标题
                    'color' => $list['color'],// 卡券颜色
                    'card_type' => $list['card_type'],// 卡券分类
                    'date_type' => $list['date_type'],// 时间类型
//                    'card_source'       =>      $list['card_source'],// 优惠券渠道
//                    'channel'           =>      $list['channel'],// 卡券渠道
                    'card_type_val' => $card_type_opts[$list['card_type']] ?? '',// 渠道
//                    'channel_val'       =>      $channel_opts[$list['channel']] ?? '',// 渠道
//                    'card_source_val'   =>      $card_source_opts[$list['card_source']] ?? '',// 渠道
                    'discount' => $list['discount'],// 折扣券打折额度，八折券，值就是20.0
                    'least_cost' => $list['least_cost'],// 代金券起用金额
                    'reduce_cost' => $list['reduce_cost'],// 代金券减免金额 or 兑换券起用金额
                    'quantity' => (int)$list['quantity'],// 卡券库存数量
                    'get_num' => (int)$list['get_num'],// 卡券已领取数量
                    'card_num' => 0 // 卡券剩余库存
                ];
                // 计算卡券现有库存
                if ($card ['quantity'] > 0 && $card['get_num'] > 0) {
                    if ($card ['quantity'] > $card['get_num']) {
                        $card['card_num'] = $card ['quantity'] - $card['get_num'];
                    }
                }
                if ($takeEffect ?? '') {
                    $card['takeEffect'] = $takeEffect;
                    $card['begin_time'] = '';
                    $card['end_time'] = '';
                } elseif ($begin_time && $end_time) {
                    $card['takeEffect'] = '';
                    $card['begin_time'] = $begin_time;
                    $card['end_time'] = $end_time;
                } else {
                    continue;
                }
                $list = $card;
            }
        }
        $result['opts'] = [
            'card_type' => $card_type_opts,
            'channel' => $channel_opts,
            'card_source' => $card_source_opts,
        ];
        if (isset($result['pagers'])) {
            unset($result['pagers']);
        }
        $this->api_response('true', 'success', $result);
    }
}
