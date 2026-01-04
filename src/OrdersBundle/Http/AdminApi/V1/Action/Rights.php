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

namespace OrdersBundle\Http\AdminApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use OrdersBundle\Services\Rights\LogsService;

use Dingo\Api\Exception\ResourceException;

class Rights extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/right",
     *     summary="获取权益详情",
     *     tags={"订单"},
     *     description="获取权益详情",
     *     operationId="getRightsDetail",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="code", in="query", description="根据状态筛选", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="rights_id", type="string", example="311", description="权益ID"),
     *               @SWG\Property(property="user_id", type="string", example="20205", description="用户id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *               @SWG\Property(property="can_reservation", type="string", example="1", description="是否可预约"),
     *               @SWG\Property(property="rights_name", type="string", example="次卡-不限次", description="权益标题"),
     *               @SWG\Property(property="rights_subname", type="string", example="物料1", description="权益子标题"),
     *               @SWG\Property(property="total_num", type="string", example="0", description="服务商品原始总次数,0标示无限制"),
     *               @SWG\Property(property="total_consum_num", type="string", example="0", description="总消耗次数"),
     *               @SWG\Property(property="is_not_limit_num", type="integer", example="1", description="限制核销次数,1:不限制；2:限制"),
     *               @SWG\Property(property="status", type="string", example="0", description="权益状态; valid:有效的, expire:过期的; invalid:失效的"),
     *               @SWG\Property(property="start_time", type="integer", example="1596556800", description="权益开始时间"),
     *               @SWG\Property(property="end_time", type="integer", example="1597075199", description="权益结束时间"),
     *               @SWG\Property(property="created", type="integer", example="1596594460", description=""),
     *               @SWG\Property(property="updated", type="integer", example="1596594460", description=""),
     *               @SWG\Property(property="order_id", type="string", example="3139418000130205", description="订单号"),
     *               @SWG\Property(property="rights_from", type="string", example="购买获取", description="权益来源"),
     *               @SWG\Property(property="operator_desc", type="string", example="", description="操作员信息"),
     *               @SWG\Property(property="label_infos", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="label_id", type="string", example="17", description=""),
     *                           @SWG\Property(property="label_name", type="string", example="物料1", description=""),
     *                 ),
     *               ),
     *               @SWG\Property(property="is_valid", type="string", example="", description=""),
     *               @SWG\Property(property="total_surplus_num", type="integer", example="-1", description=""),
     *               @SWG\Property(property="start_date", type="string", example="2020-08-05", description=""),
     *               @SWG\Property(property="end_date", type="string", example="2020-08-10", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getRightsDetail(Request $request)
    {
        $rightsService = new RightsService(new TimesCardService());

        $authInfo = $this->auth->user();

        if ($request->input('code')) {
            $rightId = $rightsService->getRightsByCode($request->input('code'));
        } else {
            throw new ResourceException(trans('OrdersBundle/Order.verification_code_required'));
        }

        if (!$rightId) {
            throw new ResourceException(trans('OrdersBundle/Order.verification_code_error'));
        }

        $rightDetail = $rightsService->getRightsDetail($rightId);

        if ($rightDetail['start_time'] > time()) {
            throw new ResourceException(trans('OrdersBundle/Order.current_rights_not_active'));
        }

        if ($rightDetail['company_id'] != $authInfo['company_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.no_permission_to_verify_rights'));
        }

        if ($rightDetail['end_time'] && $rightDetail['end_time'] < time()) {
            throw new ResourceException(trans('OrdersBundle/Order.rights_verification_expired'));
        }

        if ($rightDetail['is_not_limit_num'] == 2 && ($rightDetail['total_num'] - $rightDetail['total_consum_num']) <= 0) {
            throw new ResourceException(trans('OrdersBundle/Order.rights_verification_used_up'));
        }

        $rightDetail['start_date'] = date('Y-m-d', $rightDetail['start_time']);

        if ($rightDetail['end_time']) {
            $rightDetail['end_date'] = date('Y-m-d', $rightDetail['end_time']);
        }


        return $this->response->array($rightDetail);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/rights/consume",
     *     summary="核销权益",
     *     tags={"订单"},
     *     description="核销权益",
     *     operationId="consumeRights",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="rights_id", in="query", description="需要核销的权益ID", type="string"),
     *     @SWG\Parameter( name="consum_num", in="query", description="核销的权益次数", type="string"),
     *     @SWG\Parameter( name="attendant", in="query", description="服务员姓名", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                  @SWG\Property(property="status", type="string"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function consumeRights(Request $request)
    {
        $rightsService = new RightsService(new TimesCardService());

        $authInfo = $this->auth->user();
        $shopId = $request->input('shop_id');
        if ($authInfo['shop_ids'] && $shopId && !in_array($shopId, $authInfo['shop_ids'])) {
            return $this->response->array(['status' => false]);
        }
        $rightId = $request->input('rights_id');
        $params = [
            'consum_num' => $request->input('consum_num'),
            'attendant' => $request->input('attendant'),
            'salesperson_mobile' => $authInfo['phoneNumber'],
            'shop_id' => $shopId,
            'company_id' => $authInfo['company_id'],
        ];

        $status = $rightsService->consumeRights($rightId, $params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/right/list",
     *     summary="获取指定会员权益列表",
     *     tags={"订单"},
     *     description="获取指定会员权益列表",
     *     operationId="getRightsList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="user_id", in="query", description="用户id", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="string", example="3", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="rights_id", type="string", example="303", description="权益ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20205", description="用户id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="can_reservation", type="string", example="1", description="是否可预约"),
     *                           @SWG\Property(property="rights_name", type="string", example="次卡-不限次", description="权益标题"),
     *                           @SWG\Property(property="rights_subname", type="string", example="物料1", description="权益子标题"),
     *                           @SWG\Property(property="total_num", type="string", example="0", description="服务商品原始总次数,0标示无限制"),
     *                           @SWG\Property(property="total_consum_num", type="string", example="0", description="总消耗次数"),
     *                           @SWG\Property(property="start_time", type="string", example="1595865600", description="权益开始时间"),
     *                           @SWG\Property(property="end_time", type="string", example="1596383999", description="权益结束时间"),
     *                           @SWG\Property(property="order_id", type="string", example="0", description="订单号"),
     *                           @SWG\Property(property="label_infos", type="array", description="",
     *                             @SWG\Items(
     *                                  @SWG\Property(property="label_id", type="string", example="17", description=""),
     *                                  @SWG\Property(property="label_name", type="string", example="物料1", description=""),
     *                             ),
     *                           ),
     *                           @SWG\Property(property="created", type="string", example="1595925481", description=""),
     *                           @SWG\Property(property="updated", type="string", example="1595925481", description=""),
     *                           @SWG\Property(property="rights_from", type="string", example="注册赠送", description="权益来源"),
     *                           @SWG\Property(property="mobile", type="string", example="15623503296", description="手机号"),
     *                           @SWG\Property(property="operator_desc", type="string", example="", description="操作员信息"),
     *                           @SWG\Property(property="status", type="string", example="0", description="权益状态; valid:有效的, expire:过期的; invalid:失效的"),
     *                           @SWG\Property(property="is_not_limit_num", type="string", example="1", description="限制核销次数,1:不限制；2:限制"),
     *                           @SWG\Property(property="is_valid", type="string", example="", description=""),
     *                           @SWG\Property(property="total_surplus_num", type="integer", example="-1", description=""),
     *                           @SWG\Property(property="start_date", type="string", example="2020-07-28", description=""),
     *                           @SWG\Property(property="end_date", type="string", example="2020-08-02", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getRightsList(Request $request)
    {
        $rightsService = new RightsService(new TimesCardService());

        $authInfo = $this->auth->user();

        $inputdata = $request->input();
        if (!isset($inputdata['user_id']) || !$inputdata['user_id']) {
            return $this->response->array(['list' => []]);
        }

        $page = isset($inputdata['page']) ? $inputdata['page'] : 1;
        $limit = isset($inputdata['page_size']) ? $inputdata['page_size'] : 100;
        $filter['valid'] = isset($inputdata['valid']) ? $inputdata['valid'] : 1 ;
        $filter['user_id'] = $inputdata['user_id'];
        $filter['company_id'] = $authInfo['company_id'];
        $filter['end_time'] = time();
        $rightList = $rightsService->getRightsList($filter, $page, $limit);
        foreach ($rightList['list'] as &$value) {
            $value['start_date'] = date('Y-m-d', $value['start_time']);
            $value['end_date'] = date('Y-m-d', $value['end_time']);
        }

        return $this->response->array($rightList);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/consumer/list",
     *     summary="获取会员核销记录列表",
     *     tags={"订单"},
     *     description="获取会员核销记录列表",
     *     operationId="getRightsConsumerList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="user_id", in="query", description="用户id", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Property(property="total_count", type="integer", description="总记录条数"),
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="rights_log_id", type="integer", description="权益日志ID"),
     *                     @SWG\Property(property="rights_id", type="integer", description="权益ID"),
     *                     @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                     @SWG\Property(property="user_id", type="integer", description="用户id"),
     *                     @SWG\Property(property="shop_id", type="integer", description="门店ID"),
     *                     @SWG\Property(property="rights_name", type="string", description="权益标题"),
     *                     @SWG\Property(property="rights_subname", type="string", description="权益子标题"),
     *                     @SWG\Property(property="consum_num", type="integer", description="消耗次数"),
     *                     @SWG\Property(property="attendant", type="string", description="服务员"),
     *                     @SWG\Property(property="salesperson_mobile", type="string", description="核销员手机号"),
     *                     @SWG\Property(property="end_time", type="string", description="权益结束时间"),
     *                     @SWG\Property(property="created", type="integer", description="创建时间"),
     *                     @SWG\Property(property="shop_name", type="string", description="门店名称"),
     *                     @SWG\Property(property="name", type="string", description="服务人员名称"),
     *                     @SWG\Property(property="user_name", type="string", description="会员姓名"),
     *                     @SWG\Property(property="user_sex", type="string", description="会员性别"),
     *                     @SWG\Property(property="user_mobile", type="string", description="会员手机号"),
     *                     @SWG\Property(property="created_date", type="datetime", description="创建时间"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getRightsConsumerList(Request $request)
    {
        $consumerLogService = new LogsService();
        $authInfo = $this->auth->user();

        $inputdata = $request->input();
        if (!isset($inputdata['user_id']) || !$inputdata['user_id']) {
            return $this->response->array(['list' => []]);
        }

        $filter['user_id'] = $inputdata['user_id'];

        $page = isset($inputdata['page']) ? $inputdata['page'] : 1;
        $limit = isset($inputdata['page_size']) ? $inputdata['page_size'] : 100;
        $cousumerList = $consumerLogService->getList($filter, $page, $limit);
        foreach ($cousumerList['list'] as &$value) {
            $value['created_date'] = date('Y-m-d H:i:s', $value['created']);
        }
        return $this->response->array($cousumerList);
    }
}
