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

namespace OrdersBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;

use Illuminate\Http\Request;
use OrdersBundle\Services\TradeRateReplyService;
use OrdersBundle\Services\TradeRateService;
use Dingo\Api\Exception\ResourceException;
use CompanysBundle\Ego\CompanysActivationEgo;

class TradeRate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/trade/rate",
     *     summary="获取订单评价列表",
     *     tags={"订单"},
     *     description="获取订单评价列表",
     *     operationId="getTradeRateList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="query",
     *         description="商品id",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="订单号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="rate_status",
     *         in="query",
     *         description="是否回复",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_begin",
     *         in="query",
     *         description="查询开始时间",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_end",
     *         in="query",
     *         description="查询结束时间",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="67", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="rate_id", type="string", example="68", description="评价id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="item_id", type="string", example="5041", description="商品ID"),
     *                           @SWG\Property(property="goods_id", type="string", example="5041", description="产品ID"),
     *                           @SWG\Property(property="order_id", type="string", example="3322700000210261", description="订单号"),
     *                           @SWG\Property(property="user_id", type="string", example="20261", description="用户id"),
     *                           @SWG\Property(property="rate_pic", type="string", example="", description="评价图片"),
     *                           @SWG\Property(property="rate_pic_num", type="integer", example="0", description="评价图片数量"),
     *                           @SWG\Property(property="content", type="string", example="wwww", description="评价内容"),
     *                           @SWG\Property(property="content_len", type="integer", example="4", description="评价内容长度"),
     *                           @SWG\Property(property="is_reply", type="string", example="", description="评价是否回复。0:否；1:是"),
     *                           @SWG\Property(property="disabled", type="string", example="", description="是否删除。0:否；1:是"),
     *                           @SWG\Property(property="anonymous", type="string", example="", description="是否匿名。0:否；1:是"),
     *                           @SWG\Property(property="star", type="integer", example="5", description="评价星级"),
     *                           @SWG\Property(property="created", type="integer", example="1612433364", description=""),
     *                           @SWG\Property(property="updated", type="integer", example="1612433364", description=""),
     *                           @SWG\Property(property="unionid", type="string", example="ofQlA0-Q4FymUt3nFtTZKm4UWkFY", description="微信unionid"),
     *                           @SWG\Property(property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *                           @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单, pointsmall:积分商城订单"),
     *                           @SWG\Property(property="username", type="string", example="宝", description=""),
     *                           @SWG\Property(property="item_name", type="string", example="内蒙古正宗羊肉", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getTradeRateList(Request $request)
    {
        // fe10e2f6 module
        $tradeRateService = new TradeRateService();

        $filter['company_id'] = app('auth')->user()->get('company_id');

        $company = (new CompanysActivationEgo())->check($filter['company_id']);
        if ($company['product_model'] == 'platform') {
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $orderBy = ['created' => 'DESC'];
        if ($request->input('time_start_begin')) {
            $filter['created|gte'] = $request->input('time_start_begin');
            $filter['created|lte'] = $request->input('time_start_end');
        }

        if ($request->input('order_id')) {
            $filter['order_id'] = $request->input('order_id');
        }

        if ($request->input('item_id')) {
            $filter['item_id'] = $request->input('item_id');
        }

        if ($request->has('rate_status') && $request->get('rate_status') != '') {
            $filter['is_reply'] = $request->input('rate_status');
        }

        // 订单类型
        if ($request->input('order_type')) {
            $filter['order_type'] = $request->input('order_type');
        }

        $data = $tradeRateService->lists($filter, $page, $pageSize, $orderBy);

        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/trade/rate",
     *     summary="管理员回复评价",
     *     tags={"订单"},
     *     description="管理员回复评价",
     *     operationId="replyTradeRate",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="reply_id", type="string", example="48", description="回复id"),
     *               @SWG\Property(property="rate_id", type="string", example="1", description="评价id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *               @SWG\Property(property="user_id", type="string", example="", description="用户id"),
     *               @SWG\Property(property="operator_id", type="string", example="1", description="操作员的id"),
     *               @SWG\Property(property="content", type="string", example="111223321111", description="评价内容"),
     *               @SWG\Property(property="content_len", type="integer", example="12", description="评价内容长度"),
     *               @SWG\Property(property="role", type="string", example="seller", description="回复角色.seller：卖家；buyer：买家"),
     *               @SWG\Property(property="created", type="integer", example="1612591908", description=""),
     *               @SWG\Property(property="unionid", type="string", example="", description="微信unionid"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function replyTradeRate(Request $request)
    {
        $params = $request->only('content', 'rate_id');
        $rules = [
            'content' => ['required', '回复必填'],
            'rate_id' => ['required', '评价ID异常'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['operator_id'] = app('auth')->user()->get('operator_id');

        $tradeRateService = new TradeRateReplyService();
        $result = $tradeRateService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/trade/{rate_id}/rate",
     *     summary="获取订单评价详情",
     *     tags={"订单"},
     *     description="获取订单评价详情",
     *     operationId="getTradeRateInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *          *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *              @SWG\Property(property="rateInfo", type="object", description="",
     *                   @SWG\Property(property="rate_id", type="string", example="1", description="评价id"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                   @SWG\Property(property="item_id", type="string", example="879", description="商品ID"),
     *                   @SWG\Property(property="goods_id", type="string", example="874", description="产品ID"),
     *                   @SWG\Property(property="order_id", type="string", example="2990860000110015", description="订单号"),
     *                   @SWG\Property(property="user_id", type="string", example="20015", description="用户id"),
     *                   @SWG\Property(property="rate_pic", type="array", description="",
     *                     @SWG\Items(
     *                       type="string", example="http://bbctest.aixue7.com/1/2020/03/14/43ffe98d364b6a6fc386a9d71fc4b77ae9e32d834a990b881b09ed92fa0dbdff.jpg", description=""
     *                     ),
     *                   ),
     *                   @SWG\Property(property="rate_pic_num", type="integer", example="1", description="评价图片数量"),
     *                   @SWG\Property(property="content", type="string", example="sdfsdfdfgdsfgsdfgdf", description="评价内容"),
     *                   @SWG\Property(property="content_len", type="integer", example="19", description="评价内容长度"),
     *                   @SWG\Property(property="is_reply", type="string", example="", description="评价是否回复。0:否；1:是"),
     *                   @SWG\Property(property="disabled", type="string", example="", description="是否删除。0:否；1:是"),
     *                   @SWG\Property(property="anonymous", type="string", example="", description="是否匿名。0:否；1:是"),
     *                   @SWG\Property(property="star", type="integer", example="4", description="评价星级"),
     *                   @SWG\Property(property="created", type="integer", example="1584115982", description=""),
     *                   @SWG\Property(property="updated", type="integer", example="1584115982", description=""),
     *                   @SWG\Property(property="unionid", type="string", example="", description="微信unionid"),
     *                   @SWG\Property(property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *                   @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单, pointsmall:积分商城订单"),
     *                   @SWG\Property(property="username", type="string", example="", description=""),
     *              ),
     *               @SWG\Property(property="itemInfo", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="item_id", type="string", example="879", description="商品ID"),
     *                           @SWG\Property(property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                           @SWG\Property(property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                           @SWG\Property(property="is_show_specimg", type="string", example="1", description="详情页是否显示规格图片"),
     *                           @SWG\Property(property="store", type="integer", example="71", description="库存"),
     *                           @SWG\Property(property="barcode", type="string", example="", description="商品条形码"),
     *                           @SWG\Property(property="sales", type="integer", example="34", description="销量"),
     *                           @SWG\Property(property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                           @SWG\Property(property="rebate", type="integer", example="1", description="推广商品 1已选择 0未选择 2申请加入 3拒绝"),
     *                           @SWG\Property(property="rebate_conf", type="object", description="",
     *                                          @SWG\Property(property="type", type="string", example="money", description="商品类型，0普通，1跨境商品，可扩展"),
     *                                          @SWG\Property(property="value", type="object", description="",
     *                                               @SWG\Property(property="first_level", type="string", example="100", description=""),
     *                                               @SWG\Property(property="second_level", type="string", example="50", description=""),
     *                                          ),
     *                                          @SWG\Property(property="ratio_type", type="string", example="order_money", description=""),
     *                                          @SWG\Property(property="rebate_task", type="array", description="",
     *                                             @SWG\Items(
     *                                                @SWG\Property(property="money", type="string", example="10", description=""),
     *                                                @SWG\Property(property="ratio", type="string", example="", description=""),
     *                                                @SWG\Property(property="filter", type="string", example="1", description=""),
     *                                             ),
     *                                           ),
     *                                           @SWG\Property(property="rebate_task_type", type="string", example="money", description=""),
     *                           ),
     *                           @SWG\Property(property="cost_price", type="integer", example="1230600", description="价格,单位为‘分’"),
     *                           @SWG\Property(property="is_point", type="string", example="1", description="是否积分兑换 true可以 false不可以"),
     *                           @SWG\Property(property="point", type="string", example="", description="积分个数"),
     *                           @SWG\Property(property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                           @SWG\Property(property="goods_id", type="string", example="874", description="产品ID"),
     *                           @SWG\Property(property="brand_id", type="integer", example="198", description="品牌id"),
     *                           @SWG\Property(property="item_name", type="string", example="兔兔10", description="商品名称"),
     *                           @SWG\Property(property="item_unit", type="string", example="", description="商品计量单位"),
     *                           @SWG\Property(property="item_bn", type="string", example="S5DA7CFAA04126", description="商品编号"),
     *                           @SWG\Property(property="brief", type="string", example="这是副标题", description="简洁的描述"),
     *                           @SWG\Property(property="price", type="integer", example="1", description="价格,单位为‘分’"),
     *                           @SWG\Property(property="market_price", type="integer", example="1008600", description="原价,单位为‘分’"),
     *                           @SWG\Property(property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                           @SWG\Property(property="goods_function", type="string", example="", description="商品功能"),
     *                           @SWG\Property(property="goods_series", type="string", example="", description="商品系列"),
     *                           @SWG\Property(property="volume", type="string", example="", description="商品体积"),
     *                           @SWG\Property(property="goods_color", type="string", example="", description="商品颜色"),
     *                           @SWG\Property(property="goods_brand", type="string", example="", description="商品品牌"),
     *                           @SWG\Property(property="item_address_province", type="string", example="", description="产地省"),
     *                           @SWG\Property(property="item_address_city", type="string", example="", description="产地市"),
     *                           @SWG\Property(property="regions_id", type="string", example="", description="产地地区id"),
     *                           @SWG\Property(property="brand_logo", type="string", example="", description="品牌图片"),
     *                           @SWG\Property(property="sort", type="integer", example="0", description="商品排序"),
     *                           @SWG\Property(property="templates_id", type="integer", example="1", description="运费模板id"),
     *                           @SWG\Property(property="is_default", type="string", example="", description="商品是否为默认商品"),
     *                           @SWG\Property(property="nospec", type="string", example="", description="商品是否为单规格"),
     *                           @SWG\Property(property="default_item_id", type="string", example="874", description="默认商品ID"),
     *                           @SWG\Property(property="pics", type="array", description="",
     *                             @SWG\Items(
     *                                type="string", example="http://bbctest.aixue7.com/1/2019/09/25/849ea5f8819debe4530f360160f42acfDqJlJi1k9Im78CUtgwxna38AXh5rwKuN", description=""
     *                             ),
     *                           ),
     *                           @SWG\Property(property="distributor_id", type="integer", example="0", description="店铺id,为0时表示该商品为商城商品，否则为店铺自有商品"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="enable_agreement", type="string", example="", description="开启购买协议"),
     *                           @SWG\Property(property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
     *                           @SWG\Property(property="item_category", type="string", example="33", description="商品主类目"),
     *                           @SWG\Property(property="rebate_type", type="string", example="default", description="分佣计算方式"),
     *                           @SWG\Property(property="weight", type="integer", example="2", description="商品重量"),
     *                           @SWG\Property(property="begin_date", type="integer", example="0", description="有效期开始时间"),
     *                           @SWG\Property(property="end_date", type="integer", example="0", description="有效期结束时间"),
     *                           @SWG\Property(property="fixed_term", type="integer", example="0", description="有效期的有效天数"),
     *                           @SWG\Property(property="tax_rate", type="integer", example="0", description="税率, 百分之～/100"),
     *                           @SWG\Property(property="created", type="integer", example="1571278762", description=""),
     *                           @SWG\Property(property="updated", type="integer", example="1595842793", description=""),
     *                           @SWG\Property(property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                           @SWG\Property(property="videos", type="string", example="http://bbctest.aixue7.com/video/1/2019/09/10/42058dbbdd79c4846ec82a593337ff2fNNQ20vSen8fApfdFf32GXg8SiTrnjPPA", description="视频"),
     *                           @SWG\Property(property="video_pic_url", type="string", example="", description="视频封面图"),
     *                           @SWG\Property(property="purchase_agreement", type="string", example="", description="购买协议"),
     *                           @SWG\Property(property="intro", type="string", example="测试", description="图文详情"),
     *                           @SWG\Property(property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                           @SWG\Property(property="audit_reason", type="string", example="", description="审核拒绝原因"),
     *                           @SWG\Property(property="is_gift", type="string", example="", description="是否为赠品"),
     *                           @SWG\Property(property="is_package", type="string", example="", description="是否为打包产品"),
     *                           @SWG\Property(property="profit_type", type="integer", example="0", description="分润类型, 默认为0配置分润,1主类目分润,2商品指定分润(比例),3商品指定分润(金额)"),
     *                           @SWG\Property(property="profit_fee", type="integer", example="0", description="分润金额,单位为分 冗余字段"),
     *                           @SWG\Property(property="is_profit", type="string", example="", description="是否支持分润"),
     *                           @SWG\Property(property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                           @SWG\Property(property="origincountry_id", type="string", example="0", description="产地国id"),
     *                           @SWG\Property(property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                           @SWG\Property(property="taxation_num", type="integer", example="0", description="计税单位份数"),
     *                           @SWG\Property(property="type", type="integer", example="0", description="商品类型，0普通，1跨境商品，可扩展"),
     *                           @SWG\Property(property="tdk_content", type="string", example="", description="tdk详情"),
     *                           @SWG\Property(property="itemId", type="string", example="879", description=""),
     *                           @SWG\Property(property="consumeType", type="string", example="every", description=""),
     *                           @SWG\Property(property="itemName", type="string", example="兔兔10", description=""),
     *                           @SWG\Property(property="itemBn", type="string", example="S5DA7CFAA04126", description=""),
     *                           @SWG\Property(property="companyId", type="string", example="1", description=""),
     *                           @SWG\Property(property="item_main_cat_id", type="string", example="33", description=""),
     *                           @SWG\Property(property="total_fee", type="integer", example="1", description=""),
     *                 ),
     *               ),
     *               @SWG\Property(property="userReply", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="reply_id", type="string", example="31", description="回复id"),
     *                           @SWG\Property(property="rate_id", type="string", example="1", description="评价id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20327", description="用户id"),
     *                           @SWG\Property(property="operator_id", type="string", example="", description="操作员的id"),
     *                           @SWG\Property(property="content", type="string", example="评论一下", description="评价内容"),
     *                           @SWG\Property(property="content_len", type="integer", example="12", description="评价内容长度"),
     *                           @SWG\Property(property="role", type="string", example="buyer", description="回复角色.seller：卖家；buyer：买家"),
     *                           @SWG\Property(property="created", type="integer", example="1604478261", description=""),
     *                           @SWG\Property(property="unionid", type="string", example="", description="微信unionid"),
     *                           @SWG\Property(property="username", type="string", example="A", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getTradeRateInfo($rate_id)
    {
        if (isset($rate_id) && $rate_id == '') {
            throw new ResourceException('参数缺失');
        }

        $tradeRateService = new TradeRateService();
        $data = $tradeRateService->getTradeRate($rate_id);

        return $this->response->array($data);
    }

    /**
     * @SWG\Delete(
     *     path="/trade/rate/{rate_id}",
     *     summary="删除订单评价",
     *     tags={"订单"},
     *     description="删除订单评价",
     *     operationId="tradeRateDelete",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="status", type="integer", example="1", description="状态"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function tradeRateDelete($rate_id)
    {
        $tradeRateService = new TradeRateService();
        $tradeRateService->update(['rate_id' => $rate_id], ['disabled' => 1]);
        return $this->response->array(['status' => 1]);
    }
}
