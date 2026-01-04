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

use CommunityBundle\Services\CommunityActivityService;
use EspierBundle\Services\ExportFileService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Str;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use EspierBundle\Traits\GetExportServiceTraits;
use EspierBundle\Jobs\ExportFileJob;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\OrderItemsService;
use OrdersBundle\Services\Rights\LogsService;
use DistributionBundle\Services\DistributorSalesmanService;
use MembersBundle\Services\ShopRelMemberService;
use SalespersonBundle\Services\SalespersonService;
use OrdersBundle\Traits\GetUserIdByMobileTrait;

class ExportData extends Controller
{
    use GetExportServiceTraits;
    use GetOrderServiceTrait;
    use GetUserIdByMobileTrait;

    /**
     * @SWG\Get(
     *     path="/orders/exportdata",
     *     summary="导出订单列表",
     *     tags={"订单"},
     *     description="导出订单列表",
     *     operationId="exportOrderData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="服务人员手机号", type="string"),
     *     @SWG\Parameter( name="order_type", in="query", description="订单类型", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="时间筛选开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="时间筛选结束时间", type="string"),
     *     @SWG\Parameter( name="order_class", in="query", description="活动订单类型", type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", type="string"),
     *     @SWG\Parameter( name="source_id", in="query", description="订单来源id", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function exportOrderData(Request $request)
    {
        $operator_id = app('auth')->user()->get('operator_id');
        $order_type = trim($request->input('order_type'));
        $type = $request->input('type') ?? "normal_order";
        if ($type == 'supplier_order') {
            $order_type = $type;
        }
        $filter['order_type'] = $order_type;
        switch ($order_type) {
            case 'supplier_order':
                $filter['supplier_id'] = $operator_id;
                break;

            case 'service':
                $type = 'service_order';
                // $filter['order_type'] = 'service';
                $shopIds = app('auth')->user()->get('shop_ids');
                if ($shopIds) {
                    foreach ($shopIds as $value) {
                        $ids[] = $value['shop_id'];
                    }
                    $filter['shop_id'] = $ids;
                }
                if ($request->input('shop_id')) {
                    $filter['shop_id'] = $request->input('shop_id');
                }
                break;

            case 'normal':
                $type = $request->input('type') ?? "normal_order";

                if ($request->input('order_class_exclude')) {
                    $order_class_exclude = $request->input('order_class_exclude');
                    $filter['order_class|notin'] = explode(',', $order_class_exclude);
                }
                if ($request->input('order_class')) {
                    $filter['order_class'] = $request->input('order_class');
                }
                // $filter['order_type'] = 'normal';

                //staff员工进入默认导出该员工管理下店铺的所有订单数据
                $operator_type = app('auth')->user()->get('operator_type');
                if ($operator_type == 'staff') {
                    if (!is_null($request->input('distributor_id'))) {
                        $distributor_id = strval($request->input('distributor_id'));
                        $filter['distributor_id'] = $distributor_id;
                    } else {
                        $distributorIds = app('auth')->user()->get('distributor_ids');
                        if ($distributorIds) {
                            $distributorIds = array_column($distributorIds, 'distributor_id');
                            $filter['distributor_id|in'] = $distributorIds;
                        }
                    }
                }elseif ($operator_type == 'supplier') {
                    $filter['supplier_id'] = $operator_id;
                } else {
                    if (!is_null($request->input('distributor_id'))) {
                        $filter['distributor_id'] = $request->input('distributor_id');
                    }
                }
                break;
        }

        if ($request->input('time_start_begin')) {
            $timeStart = $request->input('time_start_begin');
            $timEnd = $request->input('time_start_end');
            if (false !== strpos($timeStart, '-')) {
                $timeStart = strtotime($timeStart.' 00:00:00');
                $timEnd = strtotime($timEnd.' 23:59:59');
            }
            $filter['create_time|gte'] = $timeStart;
            $filter['create_time|lte'] = $timEnd;
        }

        if ($request->input('delivery_time_begin')) {
            $deliveryTimeStart = $request->input('delivery_time_begin');
            $deliveryTimEnd = $request->input('delivery_time_end');
            if (false !== strpos($deliveryTimeStart, '-')) {
                $deliveryTimeStart = strtotime($deliveryTimeStart.' 00:00:00');
                $deliveryTimEnd = strtotime($deliveryTimEnd.' 23:59:59');
            }
            $filter['delivery_time|gte'] = $deliveryTimeStart;
            $filter['delivery_time|lte'] = $deliveryTimEnd;
        }

        if ($status = $request->input('order_status', '')) {
            switch ($status) {
                case 'ordercancel':   //已取消待退款
                    $filter['order_status'] = 'CANCEL_WAIT_PROCESS';
                    $filter['cancel_status'] = 'WAIT_PROCESS';
                    break;
                case 'refundprocess':    //已取消待退款
                    $filter['order_status'] = 'CANCEL';
                    $filter['cancel_status'] = 'NO_APPLY_CANCEL';
                    break;
                case 'refundsuccess':    //已取消已退款
                    $filter['order_status'] = 'CANCEL';
                    $filter['cancel_status'] = 'SUCCESS';
                    break;
                case 'notship':  //待发货
                    $filter['order_status'] = 'PAYED';
                    $filter['cancel_status|in'] = ['NO_APPLY_CANCEL', 'FAILS'];
                    $filter['receipt_type'] = 'logistics';
                    break;
                case 'cancelapply':  //待退款
                    $filter['order_status'] = 'PAYED';
                    $filter['cancel_status'] = 'WAIT_PROCESS';
                    break;
                case 'ziti':  //待自提
                    $filter['receipt_type'] = 'ziti';
                    $filter['order_status'] = 'PAYED';
                    $filter['ziti_status'] = 'PENDING';
                    break;
                case 'shipping':  //带收货
                    $filter['order_status'] = 'WAIT_BUYER_CONFIRM';
                    $filter['delivery_status'] = ['DONE', 'PARTAIL'];
                    $filter['receipt_type'] = 'logistics';
                    break;
                case 'finish':  //已完成
                    $filter['order_status'] = 'DONE';
                    break;
                case 'reviewpass':  //待审核
                    $filter['order_status'] = 'REVIEW_PASS';
                    break;
                case 'done_noinvoice':  //已完成未开票
                    $filter['order_status'] = 'DONE';
                    $filter['invoice|neq'] = null;
                    $filter['is_invoiced'] = 0;
                    break;
                case 'done_invoice':  //已完成已开票
                    $filter['order_status'] = 'DONE';
                    $filter['invoice|neq'] = null;
                    $filter['is_invoiced'] = 1;
                    break;
                default:
                    $filter['order_status'] = strtoupper($status);
                    break;
            }
        }

        if ($act_id = $request->input('act_id')) {
            $filter['act_id'] = $act_id;
        }

        if ($pay_type = $request->input('pay_type')) {
            $filter['pay_type'] = $pay_type;
        }

        if ($order_id = $request->input('order_id')) {
            if (strlen($order_id) < 16) {
                $filter['order_id|like'] = '%'.$order_id.'%';
            } else {
                $filter['order_id'] = $order_id;
            }
        }

        if ($mobile = $request->input('mobile')) {
            $filter['mobile'] = $mobile;
        }

        if ($request->input('receipt_type')) {
            $filter['receipt_type'] = $request->input('receipt_type');
        }

        if ($request->input('user_id')) {
            $filter['user_id'] = $request->input('user_id');
        }
        if ($request->input('source_id')) {
            $filter['source_id'] = $request->input('source_id');
        }
        if ($request->input('order_class')) {
            $filter['order_class'] = $request->input('order_class');
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        if ($request->input('salesman_mobile')) {
            $salespersonService = new SalespersonService();
            $salesmanInfo = $salespersonService->getInfo(['mobile' => trim($request->input('salesman_mobile')), 'company_id' => $filter['company_id']]);
            $filter['salesman_id'] = $salesmanInfo ? $salesmanInfo['salesperson_id'] : '-1';
        }
        // 按活动名称搜索
        $activity_name = $request->input('activity_name');
        $activity_status = $request->input('activity_status');
        if (!empty($activity_name) || !empty($activity_status)) {
            $activity_filter = [];
            if (!empty($activity_name)) {
                $activity_filter['activity_name|contains'] = $activity_name;
            }
            if (!empty($activity_status)) {
                $activity_filter['activity_status'] = $activity_status;
            }
            if (!empty($activity_filter)) {
                $activity_filter['company_id'] = $filter['company_id'];
                $activityService = new CommunityActivityService();
                $activity = $activityService->getLists($activity_filter, 'activity_id');
                $act_ids = array_merge([0], array_column($activity, 'activity_id'));
                if (!empty($act_ids)) {
                    $filter['act_id'] = $act_ids;
                }
            }
        }

        $orderService = $this->getOrderService($order_type);

        // dada 订单 order_status: dada_x 统计不出数量，需要提前特殊处理
        if (Str::startsWith($status, 'dada_')) {
            $filter = $orderService->getOrderIdByDadaStatus($filter);
            if ($filter['order_id'] === []) {
                throw new resourceexception('导出有误,暂无数据导出');
            }
            // Order:getOrderItem 不能直接用数组查询
            $filter['order_id|in'] = $filter['order_id'];
            unset($filter['order_id']);
        }

        if ($filter['order_type'] == 'normal') {
            $subdistrict_parent_id = $request->get('subdistrict_parent_id');
            $subdistrict_id = $request->get('subdistrict_id');
            if (!is_null($subdistrict_parent_id)) {
                 $filter['subdistrict_parent_id'] = $subdistrict_parent_id;
            }
            if (!is_null($subdistrict_id)) {
                 $filter['subdistrict_id'] = $subdistrict_id;
            }

            if ($type == 'normal_master_order') {
                $count = $orderService->countOrderNum($filter);
            } elseif ($type == 'normal_order') {
                $count = $orderService->getOrderItemCount($filter);
            }
        } elseif ($filter['order_type'] == 'service') {
            $count = $orderService->countOrderNum($filter);
        } elseif ($filter['order_type'] == 'supplier_order') {
            unset($filter['order_type']);
            $count = $orderService->countOrderNum($filter);
        }
        // 推广员身份名称、推广员手机号
        if ($request->input('promoter_identity', '')) {
            $filter['promoter_identity'] = $request->input('promoter_identity');
        }
        if ($request->input('promoter_mobile', '')) {
            $filter['promoter_mobile'] = $request->input('promoter_mobile');
        }
        //存储导出操作账号者

        // 是否有权限查看加密数据
        $filter['datapass_block'] = $request->get('x-datapass-block');
        return $this->exportData($count, $type, $filter, $operator_id);
    }

    /**
     * @SWG\Get(
     *     path="/orders/exportnormaldata",
     *     summary="导出实体订单列表",
     *     tags={"订单"},
     *     description="导出实体订单列表",
     *     operationId="exportnormaldata",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="服务人员手机号", type="string"),
     *     @SWG\Parameter( name="order_type", in="query", description="订单类型", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="时间筛选开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="时间筛选结束时间", type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺id", type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", type="string"),
     *     @SWG\Parameter( name="status", in="query", description="订单状态", type="integer"),
     *     @SWG\Parameter( name="exportStart", in="query", description="页码", type="integer"),
     *     @SWG\Parameter( name="exportLimit", in="query", description="记录条数", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function exportOrderNormalData(Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');

        if ($request->input('mobile', false)) {
            if (strlen($request->input('mobile')) == 11) {
                $filter['mobile'] = $request->input('mobile');
            } else {
                $filter['trade_id'] = $request->input('mobile');
            }
        }

        $filter['order_type'] = 'normal';

        $fileName = date('YmdHis') . $filter['company_id'];
        if ($request->input('shop_id')) {
            $filter['shop_id'] = $request->input('shop_id');
        }

        if ($request->input('time_start_begin')) {
            $filter['create_time|gte'] = $request->input('time_start_begin');
            $filter['create_time|lte'] = $request->input('time_start_end');
        }

        if ($request->input('order_id')) {
            $filter['order_id'] = $request->input('order_id');
        }
        if ($request->input('status')) {
            $filter['status'] = $request->input('status');
        }

        $orderService = $this->getOrderService('normal');
        $count = $orderService->countOrderNum($filter);
        if (!$count) {
            return response()->json(['filename' => '', 'url' => '']);
        }
        $pageStart = $request->input('exportStart');
        $limit = $request->input('exportLimit');

        $title = [
            'order_id' => '订单号',
            'title' => '订单标题',
            'create_date' => '下单时间',
            'mobile' => '手机号',
            'total_fee' => '订单价格',
            'order_status' => '订单状态',
            'delivery_status' => '发货状态',
            'item_name' => '商品名称',
            'item_total_fee' => '商品金额',
            'item_total_num' => '商品数量'
        ];

        $order_status = [
            'DONE' => '订单完成',
            'NOTPAY' => '未支付',
            'PAYED' => '已支付',
            'CANCEL' => '已取消',
            'WAIT_BUYER_CONFIRM' => '待用户收货',
        ];
        $delivery_status = [
            'DONE' => '已发货',
            'PENDING' => '待发货',
            'PARTAIL' => '部分发货'
        ];
        $orderList = [];
        $orderdata = $orderService->getOrderList($filter, $pageStart, $limit);
        foreach ($orderdata['list'] as $key => $value) {
            foreach ($value['items'] as $key1 => $value1) {
                //获取订单权益
                $orderList[] = [
                    'order_id' => "'" . $value['order_id'] . "'",
                    'title' => $value['title'],
                    'create_date' => date('Y-m-d H:i:s', $value['create_time']),
                    'mobile' => $value['mobile'],
                    'total_fee' => $value['total_fee'] / 100,
                    'order_status' => isset($order_status[$value['order_status']]) ? $order_status[$value['order_status']] : '未知状态',
                    'delivery_status' => isset($delivery_status[$value['delivery_status']]) ? $delivery_status[$value['delivery_status']] : '未知状态',
                    'item_name' => $value1['item_name'],
                    'item_total_fee' => $value1['total_fee'] / 100,
                    'item_total_num' => $value1['num'],
                ];
            }
        }
        $orderList = array_merge(array($title), $orderList);
        $exportService = new ExportFileService();
        $result = $exportService->export($orderList, $fileName, $limit, $limit);
        return response()->json($result);
    }

    /**
     * @SWG\Get(
     *     path="/rights/exportdata",
     *     summary="导出权益列表",
     *     tags={"订单"},
     *     description="导出权益列表",
     *     operationId="exportRightData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="rights_from", in="query", description="权益来源", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="服务人员手机号", type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="用户id", type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店id", type="string"),
     *     @SWG\Parameter( name="date_begin", in="query", description="时间筛选开始时间", type="string"),
     *     @SWG\Parameter( name="date_end", in="query", description="时间筛选结束时间", type="string"),
     *     @SWG\Parameter( name="valid", in="query", description="是否有效", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function exportRightData(Request $request)
    {
        $type = 'right';
        $filter['company_id'] = app('auth')->user()->get('company_id');

        $params = $request->all('mobile', 'user_id', 'valid', 'date_begin', 'date_end', 'rights_from');

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
            $filter['datetime'] = [$params['date_begin'],$params['date_end']];
        }

        if ($params['rights_from']) {
            $filter['rights_from'] = $params['rights_from'];
        }

        if ($request->input('order_id')) {
            $filter['order_id'] = $request->input('order_id');
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

        $rightsObj = new RightsService(new TimesCardService());
        $count = $rightsObj->countRights($filter);

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        // 是否有权限查看加密数据
        $filter['datapass_block'] = $request->get('x-datapass-block');
        return $this->exportData($count, $type, $filter, $operator_id);
    }

    /**
     * @SWG\Get(
     *     path="/trades/exportdata",
     *     summary="导出交易单列表",
     *     tags={"订单"},
     *     description="导出交易单列表",
     *     operationId="exportTradeData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="服务人员手机号", type="string"),
     *     @SWG\Parameter( name="name", in="query", description="服务人员名称", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="时间筛选开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="时间筛选结束时间", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function exportTradeData(Request $request)
    {
        $filter = array();
        $type = 'tradedata';

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        if ($request->input('status')) {
            $filter['trade_state'] = strtoupper($request->input('status'));
        }

        if ($request->input('orderId')) {
            $filter['order_id'] = $request->input('orderId');
        }

        if ($request->input('mobile', false)) {
            if (strlen($request->input('mobile')) == 11) {
                $filter['mobile'] = $request->input('mobile');
            } else {
                $filter['trade_id'] = $request->input('mobile');
            }
        }

        if ($request->input('date_begin')) {
            $filter['time_start_begin'] = $request->input('date_begin');
            $filter['time_start_end'] = $request->input('date_end');
        }

        if ($request->input('time_start_begin')) {
            $filter['time_start_begin'] = $request->input('time_start_begin');
            $filter['time_start_end'] = $request->input('time_start_end');
        }

        $shopIds = app('auth')->user()->get('shop_ids');
        if ($shopIds) {
            $filter['shop_id'] = array_column($shopIds, 'shop_id');
        }

        if ($request->input('shop_id', false)) {
            $filter['shop_id'] = $request->input('shop_id');
        }

        if ($request->get('distributor_id', 0)) {
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        $distributorListSet = app('auth')->user()->get('distributor_ids');
        if (!empty($distributorListSet)) {
            $distributorIdSet = array_column($distributorListSet, 'distributor_id');
            if (isset($filter['distributor_id']) && $filter['distributor_id']) {
                if (!in_array($filter['distributor_id'], $distributorIdSet)) {
                    unset($filter['distributor_id']);
                }
            } else {
                $filter['distributor_id'] = $distributorIdSet;
            }
        }

        if ($request->input('order_type') == 'service') {
            $filter['trade_source_type'] = ['service', 'groups', 'seckill'];
        } elseif ($request->input('order_type') == 'normal') {
            $filter['trade_source_type'] = ['normal', 'normal_groups', 'normal_seckill', 'normal_community'];
        } elseif ($request->input('order_type') == 'diposit') {
            $filter['trade_source_type'] = 'diposit';
        } elseif ($request->input('order_type') == 'order_pay') {
            $filter['trade_source_type'] = 'order_pay';
        }

        $tradeService = new TradeService();
        $count = $tradeService->getTradeCount($filter);

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        // 是否有权限查看加密数据
        $filter['datapass_block'] = $request->get('x-datapass-block');
        return $this->exportData($count, $type, $filter, $operator_id);
    }

    /**
     * @SWG\Get(
     *     path="/rights/logExport",
     *     summary="导出权益核销列表",
     *     tags={"订单"},
     *     description="导出权益核销列表",
     *     operationId="exportRightConsumeData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="服务人员手机号", type="string"),
     *     @SWG\Parameter( name="name", in="query", description="服务人员名称", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="时间筛选开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="时间筛选结束时间", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function exportRightConsumeData(Request $request)
    {
        $type = 'right_consume';
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $params = $request->all('mobile', 'name', 'shop_id', 'time_start_begin', 'time_start_end');
        if (intval($params['mobile'])) {
            $filter['salesperson_mobile'] = intval($params['mobile']);
        }
        if (intval($params['name'])) {
            $filter['name'] = intval($params['name']);
        }

        if (intval($params['shop_id'])) {
            $filter['shop_id'] = intval($params['shop_id']);
        }
        if ($params['time_start_begin']) {
            if (!is_numeric($params['time_start_begin'])) {
                throw new resourceexception('导出有误，日期时间参数有误');
            }
            $filter['time_start_begin'] = $params['time_start_begin'];
        }
        if ($params['time_start_end']) {
            if (!is_numeric($params['time_start_end'])) {
                throw new resourceexception('导出有误，日期时间参数有误');
            }
            $filter['time_start_end'] = $params['time_start_end'];
        }
        $rightsService = new LogsService();
        $count = $rightsService->getCount($filter);

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        // 是否有权限查看加密数据
        $filter['datapass_block'] = $request->get('x-datapass-block');
        return $this->exportData($count, $type, $filter, $operator_id);
    }

    /**
     * @SWG\Get(
     *     path="/invoice/exportdata",
     *     summary="导出发票列表",
     *     tags={"订单"},
     *     description="导出发票列表",
     *     operationId="exportdata",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_type", in="query", description="订单类型", type="string"),
     *     @SWG\Parameter( name="order_class_exclude", in="query", description="", type="string"),
     *     @SWG\Parameter( name="order_class", in="query", description="订单种类", type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="结束时间", type="string"),
     *     @SWG\Parameter( name="order_status", in="query", description="订单状态", type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="会员手机号", type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="会员id", type="string"),
     *     @SWG\Parameter( name="source_id", in="query", description="来源id", type="string"),
     *     @SWG\Parameter(name="salesman_mobile", in="query", description="售卖员手机号", type="string"),
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
    public function exportInvoiceData(Request $request)
    {
        $type = 'invoice';
        $order_type = $request->input('order_type');
        if ($order_type == 'normal') {
            if ($request->input('order_class_exclude')) {
                $order_class_exclude = $request->input('order_class_exclude');
                $filter['order_class|notin'] = explode(',', $order_class_exclude);
            }
            if ($request->input('order_class')) {
                $filter['order_class'] = $request->input('order_class');
            }
            $filter['order_type'] = 'normal';

            //staff员工进入默认导出该员工管理下店铺的所有订单数据
            $operator_type = app('auth')->user()->get('operator_type');
            if ($operator_type == 'staff') {
                if (!is_null($request->input('distributor_id'))) {
                    $filter['distributor_id'] = $request->input('distributor_id');
                } else {
                    $distributor_ids = app('auth')->user()->get('distributor_ids');
                    $distributorIds = isset($distributor_ids) ? $distributor_ids : [];
                    if ($distributorIds) {
                        $filter['distributor_id|in'] = array_column($distributorIds, 'distributor_id');
                    }
                }
            } else {
                if (!is_null($request->input('distributor_id'))) {
                    $filter['distributor_id'] = $request->input('distributor_id');
                }
            }
        } elseif ($order_type == 'supplier_order') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');
        }

        if ($request->input('time_start_begin')) {
            $filter['create_time|gte'] = $request->input('time_start_begin');
            $filter['create_time|lte'] = $request->input('time_start_end');
        }

        if ($request->input('order_date')) {
            $order_date = $request->input('order_date');
            $filter['create_time|gte'] = strtotime($order_date[0]);
            $filter['create_time|lte'] = strtotime($order_date[1]);
        }

        if ($status = $request->input('order_status')) {
            if (in_array($status, ['ordercancel','refundprocess', 'refundsuccess'])) {
                if ($status == 'refundprocess') {
                    $filter['order_status'] = 'CANCEL_WAIT_PROCESS';
                    $filter['cancel_status'] = 'WAIT_PROCESS';
                } elseif ($status == 'ordercancel') {
                    $filter['order_status'] = 'CANCEL';
                    $filter['cancel_status'] = 'NO_APPLY_CANCEL';
                } else {
                    $filter['order_status'] = 'CANCEL';
                    $filter['cancel_status'] = 'SUCCESS';
                }
            } elseif ($status == 'notship') {
                $filter['order_status'] = 'PAYED';
                $filter['ziti_status'] = 'NOTZITI';
            } elseif ($status == 'finish') {
                $filter['order_status'] = 'DONE';
            } elseif ($status == 'done_noinvoice') {
                $filter['order_status'] = 'DONE';
                $filter['invoice|neq'] = null;
                $filter['is_invoiced'] = 0;
            } elseif ($status == 'done_invoice') {
                $filter['order_status'] = 'DONE';
                $filter['invoice|neq'] = null;
                $filter['is_invoiced'] = 1;
            } else {
                $filter['order_status'] = strtoupper($request->input('order_status'));
            }
        }
        if ($request->input('pay_type')) {
            $filter['pay_type'] = $request->input('pay_type');
        }
        if ($request->input('order_id')) {
            $filter['order_id'] = $request->input('order_id');
        }
        if ($request->input('mobile')) {
            $filter['mobile'] = $request->input('mobile');
        }
        if ($request->input('user_id')) {
            $filter['user_id'] = $request->input('user_id');
        }
        if ($request->input('source_id')) {
            $filter['source_id'] = $request->input('source_id');
        }
        if ($request->input('order_class')) {
            $filter['order_class'] = $request->input('order_class');
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }

        if ($request->input('salesman_mobile')) {
            $distributorSalesmanService = new DistributorSalesmanService();
            $salesmanInfo = $distributorSalesmanService->getInfo(['mobile' => trim($request->input('salesman_mobile')), 'company_id' => $filter['company_id']]);
            $filter['salesman_id'] = $salesmanInfo ? $salesmanInfo['salesman_id'] : '-1';
        }
        // $filter['invoice|neq'] = null;
        // $filter['is_invoiced'] = 0;
        $filter['invoice_status'] = ['pending','success','inProgress','waste','failed'];

        $orderService = $this->getOrderService($order_type);
        $count = $orderService->countOrderNum($filter);

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        return $this->exportData($count, $type, $filter, $operator_id);
    }

    /**
     * @SWG\Get(
     *     path="/financial/salesreport",
     *     summary="导出财务销售报表",
     *     tags={"订单"},
     *     description="导出财务销售报表",
     *     operationId="exportSalesreportData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", type="string"),
     *     @SWG\Parameter( name="brand", in="query", description="品牌", type="string"),
     *     @SWG\Parameter( name="main_category", in="query", description="三级主类目", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="下单时间筛选开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="下单时间筛选结束时间", type="string"),
     *     @SWG\Parameter( name="delivery_time_start_begin", in="query", description="发货时间筛选开始时间", type="string"),
     *     @SWG\Parameter( name="delivery_time_start_end", in="query", description="发货时间筛选结束时间", type="string"),
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
    public function exportSalesreportData(Request $request)
    {
        $filter = array();
        $type = 'salesreport_financial';

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['delivery_status'] = 'DONE';
        $postdata = $request->all('order_id', 'brand', 'main_category', 'time_start_begin', 'time_start_end', 'delivery_time_start_begin', 'delivery_time_start_end');

        if (trim($postdata['order_id'])) {
            $filter['order_id'] = $postdata['order_id'];
        }

        if (trim($postdata['brand'])) {
            $filter['brand'] = $postdata['brand'];
        }

        if (trim($postdata['main_category'])) {
            $filter['main_category'] = $postdata['main_category'];
        }

        if ($postdata['time_start_begin']) {
            if ($postdata['time_start_end'] - $postdata['time_start_begin'] > 3600 * 24 * 31 * 3) {
                throw new resourceexception('导出有误，下单日期不能超过3个月');
            }
            $filter['create_time|gte'] = $postdata['time_start_begin'];
            $filter['create_time|lte'] = $postdata['time_start_end'];
        }

        if ($postdata['delivery_time_start_begin']) {
            if ($postdata['delivery_time_start_end'] - $postdata['delivery_time_start_begin'] > 3600 * 24 * 31 * 3) {
                throw new resourceexception('导出有误，发货日期不能超过3个月');
            }
            $filter['delivery_time|gte'] = $postdata['delivery_time_start_begin'];
            $filter['delivery_time|lte'] = $postdata['delivery_time_start_end'];
        }

        if (!$postdata['time_start_begin'] && !$postdata['delivery_time_start_begin']) {
            $filter['create_time|gte'] = strtotime(date('Y-m-d 00:00:00', strtotime("-30 day")));
            $filter['create_time|lte'] = strtotime(date('Y-m-d 23:59:59', time()));
        }

        $orderItemsService = new OrderItemsService();
        $count = $orderItemsService->salesreportCount($filter);

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        return $this->exportData($count, $type, $filter, $operator_id);
    }

    private function exportData($count, $type, $filter, $operator_id = 0)
    {
        if ($count <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }

        // if ($count > 15000) {
        //     throw new resourceexception("导出有误，当前导出数据为 $count 条，最高导出 15000 条数据");
        // }

        // if ($count > 500) {
//        (new ExportFileJob($type, $filter['company_id'], $filter, $operator_id))->handle();
        $gotoJob = (new ExportFileJob($type, $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        if (env('APP_ENV') == 'local_dev') {
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatchNow($gotoJob);
        } else {
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        $result['status'] = true;
        return response()->json($result);
        // } else {
        //     $exportService = $this->getService($type);
        //     $result = $exportService->exportData($filter);
        //     return response()->json($result);
        // }
    }
}
