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

namespace OrdersBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;

use OrdersBundle\Services\TradeRateReplyService;
use OrdersBundle\Services\TradeRateService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WxappTradeRate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/order/rate/list",
     *     summary="获取商品评价列表",
     *     tags={"订单"},
     *     description="获取商品评价列表",
     *     operationId="getRateList",
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
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="1", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="rate_id", type="string", example="67", description="评价id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="item_id", type="string", example="5437", description="商品ID"),
     *                           @SWG\Property(property="goods_id", type="string", example="5437", description="产品ID"),
     *                           @SWG\Property(property="order_id", type="string", example="3309653000180376", description="订单号"),
     *                           @SWG\Property(property="user_id", type="string", example="20376", description="用户id"),
     *                           @SWG\Property(property="rate_pic", type="string", example="https://bbctest.aixue7.com//image/1/2021/01/22/45bb1d4e1aeba4aa742a7a2a462215f4Xvh4BoOocgGx7392e26d595503f8e4de576792581228.png", description="评价图片"),
     *                           @SWG\Property(property="rate_pic_num", type="integer", example="1", description="评价图片数量"),
     *                           @SWG\Property(property="content", type="string", example="这里是评价内容内容内容内容内容", description="评价内容"),
     *                           @SWG\Property(property="content_len", type="integer", example="45", description="评价内容长度"),
     *                           @SWG\Property(property="is_reply", type="string", example="", description="评价是否回复。0:否；1:是"),
     *                           @SWG\Property(property="disabled", type="string", example="", description="是否删除。0:否；1:是"),
     *                           @SWG\Property(property="anonymous", type="string", example="", description="是否匿名。0:否；1:是"),
     *                           @SWG\Property(property="star", type="integer", example="5", description="评价星级"),
     *                           @SWG\Property(property="created", type="integer", example="1611304538", description=""),
     *                           @SWG\Property(property="updated", type="integer", example="1611304538", description=""),
     *                           @SWG\Property(property="unionid", type="string", example="oCzyo58WgO5d3xuF3PLp0lcaSGWY", description="微信unionid"),
     *                           @SWG\Property(property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *                           @SWG\Property(property="order_type", type="string", example="normal", description="订单类型"),
     *                           @SWG\Property(property="avatar", type="string", example="https://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJoC7iczcqvp72KScFPhsFFcRNFsOpibpiawiazhmCooJPmoNdOVqHefvib2ONlfUBBAo5WaRX2kibsU8Fg/132", description="会员头像"),
     *                           @SWG\Property(property="username", type="string", example="曹帅", description="会员昵称"),
     *                           @SWG\Property(property="praise_num", type="integer", example="0", description="点赞数"),
     *                          @SWG\Property(property="reply", type="object", description="回复记录",
     *                                           @SWG\Property(property="total_count", type="integer", example="0", description="记录数"),
     *                          ),
     *                 ),
     *               ),
     *            ),

     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function getRateList(Request $request)
    {
        $authInfo = $request->get('auth');
        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);
        $item_id = $request->get('item_id');
        $params = $request->input();
        $rules = [
            'item_id' => ['required|numeric|min:1', '商品ID异常'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new BadRequestHttpException($errorMessage);
        }

        $filter['item_id'] = $item_id;
        $filter['company_id'] = $authInfo['company_id'];
        $filter['disabled'] = 0;
        // 订单类型 normal:普通商品订单 pointsmall:积分商城订单
        if (isset($params['order_type']) && $params['order_type']) {
            $filter['order_type'] = $params['order_type'];
        }


        $tradeRateService = new TradeRateService();
        $result = $tradeRateService->rateList($filter, $page, $pageSize, [
            "created" => "DESC",
            "star" => "DESC",
            "rate_pic_num" => "DESC",
            "content_len" => "DESC",
        ]);

        return $this->response->array($result);
    }


    /**
     * @SWG\Post(
     *     path="/wxapp/order/rate/create",
     *     tags={"订单"},
     *     summary="用户评价",
     *     description="用户评价",
     *     operationId="addRate",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="order_id", in="query", description="订单编号", required=true, type="string"),
     *     @SWG\Parameter(name="anonymous", in="query", description="", required=false, type="string"),
     *     @SWG\Parameter(name="rates[0][item_id]", in="query", description="商品id", required=true, type="string"),
     *     @SWG\Parameter(name="rates[0][content]", in="query", description="评价内容", required=true, type="string"),
     *     @SWG\Parameter(name="rates[0][star]", in="query", description="评分星数", required=true, type="string"),
     *     @SWG\Parameter(name="rates[0][pics][0]", in="query", description="评价图片", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *             @SWG\Property(property="data", type="array", description="",
     *               @SWG\Items(
     *                   @SWG\Property(property="rate_id", type="string", example="67", description="评价id"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                   @SWG\Property(property="item_id", type="string", example="5437", description="商品ID"),
     *                   @SWG\Property(property="goods_id", type="string", example="5437", description="产品ID"),
     *                   @SWG\Property(property="order_id", type="string", example="3309653000180376", description="订单号"),
     *                   @SWG\Property(property="user_id", type="string", example="20376", description="用户id"),
     *                   @SWG\Property(property="rate_pic", type="string", example="https://bbctest.aixue7.com//image/1/2021/01/22/45bb1d4e1aeba4aa742a7a2a462215f4Xvh4BoOocgGx7392e26d595503f8e4de576792581228.png", description="评价图片"),
     *                   @SWG\Property(property="rate_pic_num", type="integer", example="1", description="评价图片数量"),
     *                   @SWG\Property(property="content", type="string", example="这里是评价内容内容内容内容内容", description="评价内容"),
     *                   @SWG\Property(property="content_len", type="integer", example="45", description="评价内容长度"),
     *                   @SWG\Property(property="is_reply", type="integer", example="0", description="评价是否回复。0:否；1:是"),
     *                   @SWG\Property(property="disabled", type="integer", example="0", description="是否删除。0:否；1:是"),
     *                   @SWG\Property(property="anonymous", type="string", example="0", description="是否匿名。0:否；1:是"),
     *                   @SWG\Property(property="star", type="string", example="5", description="评价星级"),
     *                   @SWG\Property(property="created", type="integer", example="1611304538", description=""),
     *                   @SWG\Property(property="updated", type="integer", example="1611304538", description=""),
     *                   @SWG\Property(property="unionid", type="string", example="oCzyo58WgO5d3xuF3PLp0lcaSGWY", description="微信unionid"),
     *                   @SWG\Property(property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *                   @SWG\Property(property="order_type", type="string", example="normal", description=""),
     *               ),
     *             ),

     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function addRate(Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');

        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['anonymous'] = $params['anonymous'] ?? 0;
        $params['unionid'] = $authInfo['unionid'];
        if (is_string($params['anonymous'])) {
            $params['anonymous'] = $params['anonymous'] === 'true' || $params['anonymous'] === '1';
        }

        $rules = [
            'rates.*.content' => ['required', '请输入商品评价内容'],
            'rates.*.star' => ['required|integer', '请给商品打星'],
            'rates.*.item_id' => ['required|numeric|min:1', '商品ID异常'],
            'order_id' => ['required', '订单号异常'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new BadRequestHttpException($errorMessage);
        }
        //return $params;
        $tradeRateService = new TradeRateService();
        $result = $tradeRateService->create($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/order/replyRate",
     *     summary="用户回复评价",
     *     tags={"订单"},
     *     description="用户回复评价",
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
     *               @SWG\Property(property="reply_id", type="string", example="49", description="回复id"),
     *               @SWG\Property(property="rate_id", type="string", example="36", description="评价id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *               @SWG\Property(property="user_id", type="string", example="20407", description="用户id"),
     *               @SWG\Property(property="operator_id", type="string", example="", description="操作员的id"),
     *               @SWG\Property(property="content", type="string", example="11111", description="评价内容"),
     *               @SWG\Property(property="content_len", type="integer", example="5", description="评价内容长度"),
     *               @SWG\Property(property="role", type="string", example="buyer", description="回复角色.seller：卖家；buyer：买家"),
     *               @SWG\Property(property="created", type="integer", example="1614667182", description=""),
     *               @SWG\Property(property="unionid", type="string", example="oCzyo58WgO5d3xuF3PLp0lcaSGWY", description="微信unionid"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function replyRate(Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');

        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['unionid'] = $authInfo['unionid'];

        $rules = [
            'rate_id' => ['required', '参数异常'],
            'content' => ['required', '评论不能为空！']
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new BadRequestHttpException($errorMessage);
        }

        $tradeRateService = new TradeRateReplyService();
        $result = $tradeRateService->create($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/rate/praise/{rate_id}",
     *     summary="评价点赞",
     *     tags={"订单"},
     *     description="评价点赞",
     *     operationId="ratePraise",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="rate_id",
     *         in="path",
     *         description="id",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="count", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function ratePraise($rate_id, Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params['user_id'] = $authInfo['user_id'] ? $authInfo['user_id'] : $params['user_id'];
        $params['company_id'] = $authInfo['company_id'] ? $authInfo['company_id'] : $params['company_id'];
        $params['rate_id'] = $rate_id;
        $tradeRateService = new TradeRateService();
        $result = $tradeRateService->ratePraise($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/rate/praise/num/{rate_id}",
     *     summary="评价点赞总数",
     *     tags={"订单"},
     *     description="评价点赞总数",
     *     operationId="ratePraiseNum",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="rate_id",
     *         in="path",
     *         description="id",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="count", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function ratePraiseNum($rate_id, Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'] ? $authInfo['company_id'] : $params['company_id'];
        $params['rate_id'] = $rate_id;
        $tradeRateService = new TradeRateService();
        $result = $tradeRateService->ratePraiseNum($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/rate/praise/check/{rate_id}",
     *     summary="评价点赞验证",
     *     tags={"订单"},
     *     description="评价点赞验证",
     *     operationId="ratePraiseCheck",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="rate_id",
     *         in="path",
     *         description="id",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function ratePraiseCheck($rate_id, Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params['user_id'] = $authInfo['user_id'] ? $authInfo['user_id'] : $params['user_id'];
        $params['company_id'] = $authInfo['company_id'] ? $authInfo['company_id'] : $params['company_id'];
        $params['rate_id'] = $rate_id;
        $tradeRateService = new TradeRateService();
        $result = $tradeRateService->ratePraiseCheck($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/replyRate/list",
     *     summary="获取评论回复列表",
     *     tags={"订单"},
     *     description="获取评论回复列表",
     *     operationId="getReplyRateList",
     *     @SWG\Parameter(
     *         name="rate_id",
     *         in="path",
     *         description="id",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取评论回复初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="1", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="reply_id", type="string", example="49", description="回复id"),
     *                           @SWG\Property(property="rate_id", type="string", example="36", description="评价id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20407", description="用户id"),
     *                           @SWG\Property(property="operator_id", type="string", example="", description="操作员的id"),
     *                           @SWG\Property(property="content", type="string", example="11111", description="评价内容"),
     *                           @SWG\Property(property="content_len", type="integer", example="5", description="评价内容长度"),
     *                           @SWG\Property(property="role", type="string", example="buyer", description="回复角色.seller：卖家；buyer：买家"),
     *                           @SWG\Property(property="created", type="integer", example="1614667182", description=""),
     *                           @SWG\Property(property="unionid", type="string", example="oCzyo58WgO5d3xuF3PLp0lcaSGWY", description="微信unionid"),
     *                           @SWG\Property(property="username", type="string", example="张三", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getReplyRateList(Request $request)
    {
        $authInfo = $request->get('auth');
        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);
        $rate_id = $request->get('rate_id');
        if (!$rate_id) {
            throw new BadRequestHttpException('参数异常');
        }

        $filter['rate_id'] = $rate_id;
        $filter['company_id'] = $authInfo['company_id'] ? $authInfo['company_id'] : $request->input('company_id');
        $tradeRateReplyService = new TradeRateReplyService();
        $result = $tradeRateReplyService->getList($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/ratePraise/status",
     *     summary="获取点赞状态",
     *     tags={"订单"},
     *     description="获取点赞状态",
     *     operationId="ratePraiseStatus",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="rate_ids",
     *         in="path",
     *         description="id",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function ratePraiseStatus(Request $request)
    {
        $auth = $request->get('auth');
        $rateIds = $request->input('rate_ids');

        if (!$rateIds) {
            throw new BadRequestHttpException('参数异常');
        }

        $praise_filter['company_id'] = $auth['company_id'];
        $praise_filter['user_id'] = $auth['user_id'];
        $tradeRateService = new TradeRateService();
        $rateIdsArr = json_decode($rateIds);
        $ratePraiseStatus = $tradeRateService->ratePraiseStatus($praise_filter, $rateIdsArr);
        return $this->response->array($ratePraiseStatus);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/rate/detail/{rate_id}",
     *     tags={"订单"},
     *     summary="获取评价详情",
     *     description="获取评价详情",
     *     operationId="getRateDetail",
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="rate_id", type="string", example="67", description="评价id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *               @SWG\Property(property="item_id", type="string", example="5437", description="商品ID"),
     *               @SWG\Property(property="goods_id", type="string", example="5437", description="产品ID"),
     *               @SWG\Property(property="order_id", type="string", example="3309653000180376", description="订单号"),
     *               @SWG\Property(property="user_id", type="string", example="20376", description="用户id"),
     *               @SWG\Property(property="rate_pic", type="string", example="https://bbctest.aixue7.com//image/1/2021/01/22/45bb1d4e1aeba4aa742a7a2a462215f4Xvh4BoOocgGx7392e26d595503f8e4de576792581228.png", description="评价图片"),
     *               @SWG\Property(property="rate_pic_num", type="integer", example="1", description="评价图片数量"),
     *               @SWG\Property(property="content", type="string", example="这里是评价内容内容内容内容内容", description="评价内容"),
     *               @SWG\Property(property="content_len", type="integer", example="45", description="评价内容长度"),
     *               @SWG\Property(property="is_reply", type="string", example="", description="评价是否回复。0:否；1:是"),
     *               @SWG\Property(property="disabled", type="string", example="", description="是否删除。0:否；1:是"),
     *               @SWG\Property(property="anonymous", type="string", example="", description="是否匿名。0:否；1:是"),
     *               @SWG\Property(property="star", type="integer", example="5", description="评价星级"),
     *               @SWG\Property(property="created", type="integer", example="1611304538", description="创建时间"),
     *               @SWG\Property(property="updated", type="integer", example="1611304538", description="修改时间"),
     *               @SWG\Property(property="unionid", type="string", example="oCzyo58WgO5d3xuF3PLp0lcaSGWY", description="微信unionid"),
     *               @SWG\Property(property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型"),
     *               @SWG\Property(property="avatar", type="string", example="https://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJoC7iczcqvp72KScFPhsFFcRNFsOpibpiawiazhmCooJPmoNdOVqHefvib2ONlfUBBAo5WaRX2kibsU8Fg/132", description=""),
     *               @SWG\Property(property="username", type="string", example="曹帅", description="用户昵称"),
     *               @SWG\Property(property="praise_num", type="integer", example="0", description="点赞数"),
     *               @SWG\Property(property="reply_count", type="integer", example="0", description="评论回复数"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function getRateDetail($rate_id, Request $request)
    {
        $params['rate_id'] = $rate_id;
        $rules = [
            'rate_id' => ['required', '评价id参数异常'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new BadRequestHttpException($errorMessage);
        }
        $authInfo = $request->get('auth');
        $tradeRateService = new TradeRateService();

        $result = $tradeRateService->rateDetail($rate_id, $authInfo['company_id']);
        return $this->response->array($result);
    }
}
