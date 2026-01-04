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
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;
use ReservationBundle\Services\ReservationManagementService as ReservationService;
use ReservationBundle\Services\ResourceLevelManagementService as ResourceLevelService;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use GoodsBundle\Services\ItemsService;

use MembersBundle\Services\UserService;
use MembersBundle\Services\ShopRelMemberService;
use OrdersBundle\Services\Rights\OperateLogService;
use OrdersBundle\Traits\GetUserIdByMobileTrait;

class Rights extends Controller
{
    use GetUserIdByMobileTrait;
    /**
     * @SWG\get(
     *     path="/rights/getdata",
     *     summary="获取用户权益",
     *     tags={"订单"},
     *     description="获取指定用户的权益",
     *     operationId="getRightsListData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="用户id ",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="rights_id",
     *         in="path",
     *         description="权益id",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="resource_level_id",
     *         in="path",
     *         description="资源位id",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取商品列表的初始偏移位置，从1开始计数",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="end_time",
     *         in="path",
     *         description="日期",
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="string", example="827", description="数据总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="rights_id", type="string", example="836", description="权益ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20402", description="用户id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="can_reservation", type="string", example="1", description="是否可预约"),
     *                           @SWG\Property(property="rights_name", type="string", example="次卡-不限次", description="权益标题"),
     *                           @SWG\Property(property="rights_subname", type="string", example="物料1", description="权益子标题"),
     *                           @SWG\Property(property="total_num", type="string", example="0", description="服务商品原始总次数,0标示无限制"),
     *                           @SWG\Property(property="total_consum_num", type="string", example="0", description="总消耗次数"),
     *                           @SWG\Property(property="start_time", type="string", example="1612108800", description="权益开始时间"),
     *                           @SWG\Property(property="end_time", type="string", example="1614441600", description="权益结束时间"),
     *                           @SWG\Property(property="order_id", type="string", example="0", description="订单号"),
     *                           @SWG\Property(property="label_infos", type="array", description="",
     *                             @SWG\Items(
     *                                 @SWG\Property(property="label_id", type="string", example="17", description=""),
     *                                 @SWG\Property(property="label_name", type="string", example="物料1", description=""),
     *                             ),
     *                           ),
     *                           @SWG\Property(property="created", type="string", example="1612186185", description=""),
     *                           @SWG\Property(property="updated", type="string", example="1612405684", description=""),
     *                           @SWG\Property(property="rights_from", type="string", example="注册赠送", description="权益来源"),
     *                           @SWG\Property(property="mobile", type="string", example="17364824590", description="手机号"),
     *                           @SWG\Property(property="operator_desc", type="string", example="", description="操作员信息"),
     *                           @SWG\Property(property="status", type="string", example="valid", description="权益状态; valid:有效的, expire:过期的; invalid:失效的"),
     *                           @SWG\Property(property="is_not_limit_num", type="string", example="1", description="限制核销次数,1:不限制；2:限制"),
     *                           @SWG\Property(property="is_valid", type="string", example="1", description=""),
     *                           @SWG\Property(property="total_surplus_num", type="integer", example="-1", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getRightsListData(Request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'user_id' => 'required',
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:100',
        ]);
        if ($validator->fails()) {
            throw new resourceexception('获取权益列表出错.', $validator->errors());
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $params['user_id'] = $inputData['user_id'];

        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];

        if (isset($inputData['rights_id']) && $inputData['rights_id']) {
            $params['rights_id'] = $inputData['rights_id'];
        }
        if (isset($inputData['end_time']) && $inputData['end_time']) {
            $params['end_time'] = strtotime($inputData['end_time']);
        }

        $reservationService = new ReservationService();
        $result = $reservationService->getCanReservationRightsList($params, $pageSize, $page);
        if ($result) {
            //获取指定资源位下的物料
            $ResourceLevelService = new ResourceLevelService();
            $filter = [
                'resource_level_id' => $inputData['resource_level_id'],
                'company_id' => $companyId
            ];
            $resourceLevel = $ResourceLevelService->getResourceLevel($filter);
            foreach ($result['list'] as $key => $value) {
                if (isset($value['label_infos'])) {
                    foreach ($value['label_infos'] as $val) {
                        if (!in_array($val['label_id'], $resourceLevel['materialIds'])) {
                            unset($result['list'][$key]);
                        }
                    }
                }
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/rights/list",
     *     summary="获取权益列表",
     *     tags={"订单"},
     *     description="获取权益列表",
     *     operationId="getRightsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店id", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="string", example="827", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="rights_id", type="string", example="836", description="权益ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20402", description="用户id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="can_reservation", type="string", example="1", description="是否可预约"),
     *                           @SWG\Property(property="rights_name", type="string", example="次卡-不限次", description="权益标题"),
     *                           @SWG\Property(property="rights_subname", type="string", example="物料1", description="权益子标题"),
     *                           @SWG\Property(property="total_num", type="string", example="0", description="服务商品原始总次数,0标示无限制"),
     *                           @SWG\Property(property="total_consum_num", type="string", example="0", description="总消耗次数"),
     *                           @SWG\Property(property="start_time", type="string", example="1612108800", description="权益开始时间"),
     *                           @SWG\Property(property="end_time", type="string", example="1614441600", description="权益结束时间"),
     *                           @SWG\Property(property="order_id", type="string", example="0", description="订单号"),
     *                           @SWG\Property(property="label_infos", type="array", description="",
     *                             @SWG\Items(
     *                                 @SWG\Property(property="label_id", type="string", example="17", description=""),
     *                                 @SWG\Property(property="label_name", type="string", example="物料1", description=""),
     *                             ),
     *                           ),
     *                           @SWG\Property(property="created", type="string", example="1612186185", description=""),
     *                           @SWG\Property(property="updated", type="string", example="1612405684", description=""),
     *                           @SWG\Property(property="rights_from", type="string", example="注册赠送", description="权益来源"),
     *                           @SWG\Property(property="mobile", type="string", example="17364824590", description="手机号"),
     *                           @SWG\Property(property="operator_desc", type="string", example="", description="操作员信息"),
     *                           @SWG\Property(property="status", type="string", example="valid", description="权益状态; valid:有效的, expire:过期的; invalid:失效的"),
     *                           @SWG\Property(property="is_not_limit_num", type="string", example="1", description="限制核销次数,1:不限制；2:限制"),
     *                           @SWG\Property(property="is_valid", type="string", example="1", description=""),
     *                           @SWG\Property(property="total_surplus_num", type="integer", example="-1", description=""),
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
        $filter = array();

        $filter['company_id'] = app('auth')->user()->get('company_id');

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);


        $params = $request->all('mobile', 'valid', 'date_begin', 'date_end', 'rights_from');

        if (intval($params['mobile'])) {
            $filter['mobile'] = intval($params['mobile']);
            $filter = $this->checkMobile($filter);
        }
        if ($userId = $request->input('user_id')) {
            $filter['user_id'] = $userId;
        }

        if (isset($params['valid'])) {
            $filter['valid'] = intval($params['valid']);
        }

        if ($params['date_begin']) {
            $filter['datetime'] = [$params['date_begin'], $params['date_end']];
        }

        if ($params['rights_from']) {
            $filter['rights_from'] = $params['rights_from'];
        }

        if ($shopId = $request->get('shop_id')) {
            $shopRelMemberService = new ShopRelMemberService();
            $data = [ 'list' => [], 'total_count' => 0];
            $sf = [
                'company_id' => $filter['company_id'],
                'shop_id' => $shopId,
            ];
            if ($filter['user_id'] ?? 0) {
                $sf['user_id'] = $filter['user_id'];
            }
            $userIds = $shopRelMemberService->getUserIdBy($sf);
            if (!$userIds) {
                return $this->response->array($data);
            }
            $filter['user_id'] = $userIds;
        }

        $orderBy = ['created' => 'DESC'];

        $data = $rightsService->getRightsList($filter, $page, $pageSize, $orderBy);
        if ($data['list']) {
            // 是否有权限查看加密数据
            $datapassBlock = $request->get('x-datapass-block');
            foreach ($data['list'] as $key => $value) {
                if ($datapassBlock) {
                    $data['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
                }
            }
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/rights",
     *     summary="新增权益",
     *     tags={"订单"},
     *     description="新增权益",
     *     operationId="createRights",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="新增权益的用户手机号", type="string"),
     *     @SWG\Parameter( name="itemids", in="query", description="商品id集合", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function createRights(Request $request)
    {
        $itemsService = new ItemsService();

        $companyId = app('auth')->user()->get('company_id');

        $mobile = $request->input('mobile');
        //通过手机号获取userId
        if ($mobile) {
            $userService = new UserService();
            $userId = $userService->getUserIdByMobile($mobile, $companyId);
        } else {
            return $this->response->error('请填写手机号', 412);
        }

        if (!$userId) {
            return $this->response->error('当前手机号不是会员', 412);
        }

        $itemIds = $request->input('itemids');
        if (!$itemIds) {
            return $this->response->error('请选择新增权益的商品', 412);
        }

        foreach ($itemIds as $itemId) {
            $itemsService->addRightsByItemId($itemId, $userId, $companyId, $mobile, '管理员手动添加');
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/transfer/rights",
     *     summary="转赠会员权益",
     *     tags={"订单"},
     *     description="转赠会员权益",
     *     operationId="createRights",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="rights_id", in="query", description="转赠权益的id", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="转赠权益的用户手机号", type="string"),
     *     @SWG\Parameter( name="transfer_mobile", in="query", description="获赠权限的用户手机号", type="string"),
     *     @SWG\Parameter( name="remark", in="query", description="备注", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function transferRights(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $rightsId = $request->input('rights_id');
        $mobile = $request->input('mobile');
        $transferMobile = $request->input('transfer_mobile');
        $remark = $request->input('remark');
        $rightsObj = new RightsService(new TimesCardService());
        // 通过rights_id获取userId
        $rightsDetail = $rightsObj->getRightsDetail($rightsId);
        $userId = $rightsDetail['user_id'];
        //通过userId获取手机号
        $userService = new UserService();
        if ($userId) {
            $filter = ['company_id' => $companyId, 'user_id' => $userId];
            $rightsData = $rightsObj->getRightsList($filter);
            if ($rightsData['list']) {
                $mobile = reset($rightsData['list'])['mobile'];
            }
        } else {
            return $this->response->error('请填写手机号', 412);
        }

        if (!$userId) {
            return $this->response->error('当前手机号不是会员', 412);
        }
        //通过手机号获取userId
        if ($transferMobile) {
            $transferUserId = $userService->getUserIdByMobile($transferMobile, $companyId);
        } else {
            return $this->response->error('请填写转让手机号', 412);
        }

        if (!$transferUserId) {
            return $this->response->error('当前转让手机号不是会员', 412);
        }
        $params = [
            'user_id' => $userId,
            'transfer_user_id' => $transferUserId,
            'mobile' => $mobile,
            'transfer_mobile' => $transferMobile,
            'company_id' => $companyId,
            'remark' => $remark,
        ];

        $rightsObj->transferRights($rightsId, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/transfer/rights/list",
     *     summary="转赠会员权益列表",
     *     tags={"订单"},
     *     description="转赠会员权益列表",
     *     operationId="list",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="rights_id", in="query", description="转赠权益的id", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="转赠权益的用户手机号", type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="会员id", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数据记录条数", type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="2", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="列表数据",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="2", description="权益延期操作日志"),
     *                           @SWG\Property(property="rights_id", type="string", example="253", description="权益ID"),
     *                           @SWG\Property(property="user_id", type="string", example="125", description="用户id"),
     *                           @SWG\Property(property="transfer_user_id", type="string", example="20148", description="转让用户id"),
     *                           @SWG\Property(property="mobile", type="string", example="18818266589", description="用户手机号"),
     *                           @SWG\Property(property="transfer_mobile", type="string", example="15618429140", description="转让用户手机号"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="remark", type="string", example="啊啊", description="操作备注"),
     *                           @SWG\Property(property="operator_id", type="string", example="", description="操作员Id"),
     *                           @SWG\Property(property="operator", type="string", example="", description="操作员"),
     *                           @SWG\Property(property="created", type="integer", example="1593427516", description=""),
     *                           @SWG\Property(property="rights_name", type="string", example="次卡-3次", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function transferRightsList(Request $request)
    {
        $filter = array();

        $filter['company_id'] = app('auth')->user()->get('company_id');

        if ($request->input('rights_id', null)) {
            $filter['rights_id'] = $request->input('rights_id');
        }
        if ($request->input('mobile', null)) {
            $filter['mobile'] = $request->input('mobile');
        }
        if ($request->input('user_id', null)) {
            $filter['user_id'] = $request->input('user_id');
        }

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);
        $rightsObj = new RightsService(new TimesCardService());
        $data = $rightsObj->transferRightsLog($filter, $page, $pageSize);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        if ($data['list']) {
            foreach ($data['list'] as $key => $value) {
                if ($datapassBlock) {
                    $data['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
                    $data['list'][$key]['transfer_mobile'] = data_masking('mobile', (string) $value['transfer_mobile']);
                }
            }
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/rights/delay",
     *     summary="权益延期",
     *     tags={"订单"},
     *     description="权益延期",
     *     operationId="delayRights",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="rights_id", in="query", description="权益id", type="string"),
     *     @SWG\Parameter( name="delay_date", in="query", description="延期至日期", type="string"),
     *     @SWG\Parameter( name="remark", in="query", description="延期至日期", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="id", type="string", example="10", description="权益延期操作日志"),
     *               @SWG\Property(property="rights_id", type="string", example="836", description="权益ID"),
     *               @SWG\Property(property="user_id", type="string", example="20402", description="用户id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *               @SWG\Property(property="remark", type="string", example="123", description="操作备注"),
     *               @SWG\Property(property="operator_id", type="string", example="1", description="操作员Id"),
     *               @SWG\Property(property="operator", type="string", example="", description="操作员"),
     *               @SWG\Property(property="original_date", type="integer", example="1612627199", description="延期之前日期"),
     *               @SWG\Property(property="delay_date", type="integer", example="1614441600", description="延期之后日期"),
     *               @SWG\Property(property="created", type="integer", example="1612405684", description="创建时间"),
     *               @SWG\Property(property="updated", type="integer", example="1612405684", description="更新时间"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function delayRights(Request $request)
    {
        if ($request->input('rights_id')) {
            $postdata['rights_id'] = $request->input('rights_id');
        } else {
            return $this->response->error('权益id必填', 412);
        }

        if ($request->input('delay_date')) {
            $postdata['delay_date'] = strtotime($request->input('delay_date'));
        } else {
            return $this->response->error('请填写延期日期', 412);
        }

        if ($request->input('remark')) {
            $postdata['remark'] = $request->input('remark');
        } else {
            return $this->response->error('请填写此次操作备注', 412);
        }

        $authUser = app('auth')->user()->get();
        $postdata['company_id'] = $authUser['company_id'];
        $postdata['operator_id'] = $authUser['operator_id'];

        $operateLogService = new OperateLogService();
        $result = $operateLogService->DelayRights($postdata);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/rights/info",
     *     summary="权益详情",
     *     tags={"订单"},
     *     description="权益详情",
     *     operationId="delayRights",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="rights_id", in="query", description="权益id", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *              @SWG\Property(property="logs", type="object", description="",
     *                   @SWG\Property(property="total_count", type="integer", example="1", description="总记录条数"),
     *                   @SWG\Property(property="list", type="array", description="数据列表",
     *                     @SWG\Items(
     *                        @SWG\Property(property="id", type="string", example="1", description="权益延期操作日志"),
     *                        @SWG\Property(property="rights_id", type="string", example="144", description="权益ID"),
     *                        @SWG\Property(property="user_id", type="string", example="20031", description="用户id"),
     *                        @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                        @SWG\Property(property="remark", type="string", example="test", description="操作备注"),
     *                        @SWG\Property(property="operator_id", type="string", example="1", description="操作员Id"),
     *                        @SWG\Property(property="operator", type="string", example="", description="操作员"),
     *                        @SWG\Property(property="original_date", type="integer", example="1593014399", description="延期之前日期"),
     *                        @SWG\Property(property="delay_date", type="integer", example="1649952000", description="延期之后日期"),
     *                        @SWG\Property(property="created", type="integer", example="1585808709", description="创建时间"),
     *                        @SWG\Property(property="updated", type="integer", example="1585808709", description="修改时间"),
     *                     ),
     *                   ),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */

    public function getRightsInfo(Request $request)
    {
        $rightsId = $request->input('rights_id');
        //$rightsService = new RightsService(new TimesCardService());
        //$result['rights'] = $rightsService->getRightsDetail($rightsId);

        $operateLogService = new OperateLogService();
        $result['logs'] = $operateLogService->getLogList($rightsId);
        return $this->response->array($result);
    }
}
