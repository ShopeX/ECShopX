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

namespace AftersalesBundle\Http\Api\V1\Action;

use AftersalesBundle\Services\AftersalesRefundService;
use Dingo\Api\Exception\StoreResourceFailedException;
use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use AftersalesBundle\Services\AftersalesService;

use Dingo\Api\Exception\ResourceException;

use EspierBundle\Traits\GetExportServiceTraits;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Services\Orders\NormalOrderService;
use SalespersonBundle\Services\SalespersonService;
use SupplierBundle\Services\SupplierService;

class Aftersales extends Controller
{
    use GetExportServiceTraits;

    /**
     * @SWG\Definition(
     *     definition="Aftersales",
     *     type="object",
     *     @SWG\Property(property="aftersales_bn", type="string", example="202012025055505"),
     *     @SWG\Property(property="order_id", type="string", example="3258750000110027"),
     *     @SWG\Property(property="company_id", type="integer", example="1"),
     *     @SWG\Property(property="user_id", type="integer", example="111"),
     *     @SWG\Property(property="salesman_id", type="integer", example="1"),
     *     @SWG\Property(property="shop_id", type="integer", example="1"),
     *     @SWG\Property(property="distributor_id", type="integer", example="1"),
     *     @SWG\Property(property="aftersales_type", type="string", example="ONLY_REFUND"),
     *     @SWG\Property(property="aftersales_status", type="integer", example="4"),
     *     @SWG\Property(property="progress", type="integer", example="1"),
     *     @SWG\Property(property="refund_fee", type="integer", example="1"),
     *     @SWG\Property(property="refund_point", type="string", example="100"),
     *     @SWG\Property(property="description", type="string", example="ddd"),
     *     @SWG\Property(property="evidence_pic", type="array", @SWG\Items()),
     *     @SWG\Property(property="refuse_reason", type="string", example=""),
     *     @SWG\Property(property="memo", type="string", example=""),
     *     @SWG\Property(property="sendback_data", type="array", @SWG\Items()),
     *     @SWG\Property(property="sendconfirm_data", type="array", @SWG\Items()),
     *     @SWG\Property(property="create_time", type="string", example="1606916210"),
     *     @SWG\Property(property="update_time", type="string", example="1607936870"),
     *     @SWG\Property(property="third_data", type="string", example=""),
     *     @SWG\Property(property="aftersales_address", type="array", @SWG\Items()),
     *     @SWG\Property(property="buttons", type="array", description="展示按钮type: cancel-取消订单, check-处理售后",
     *        @SWG\Items(
     *            @SWG\Property(property="type", type="string", example="contact", description="按钮类型"),
     *            @SWG\Property(property="name", type="string", example="联系客户", description="显示名字"),
     *        ),
     *    ),
     *     @SWG\Property(property="detail", type="array", @SWG\Items(
     *         ref="#/definitions/AftersalesDetail"
     *     )),
     *     @SWG\Property(property="order_info", type="object",
     *     ),
     * )
     */

    /**
     * @SWG\Definition(
     *     definition="AftersalesDetail",
     *     type="object",
     *     @SWG\Property(property="aftersales_bn", type="string", example="202012025055505"),
     *     @SWG\Property(property="order_id", type="string", example="3258750000110027"),
     *     @SWG\Property(property="company_id", type="integer", example="1"),
     *     @SWG\Property(property="user_id", type="integer", example="111"),
     *     @SWG\Property(property="detail_id", type="integer", example="1"),
     *     @SWG\Property(property="sub_order_id", type="integer", example="1"),
     *     @SWG\Property(property="item_id", type="integer"),
     *     @SWG\Property(property="item_bn", type="string", example="S5FAD27C9C2C44"),
     *     @SWG\Property(property="item_name", type="integer", example="测试商品"),
     *     @SWG\Property(property="order_item_type", type="string", example="normal"),
     *     @SWG\Property(property="item_pic", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg"),
     *     @SWG\Property(property="num", type="integer", example="100"),
     *     @SWG\Property(property="refund_fee", type="string", example="0"),
     *     @SWG\Property(property="refund_point", type="string", example="1"),
     *     @SWG\Property(property="aftersales_type", type="string", example="ONLY_REFUND"),
     *     @SWG\Property(property="progress", type="integer", example="7"),
     *     @SWG\Property(property="aftersales_status", type="integer", example="7"),
     *     @SWG\Property(property="create_time", type="string", example="1606916210"),
     *     @SWG\Property(property="update_time", type="string", example="1607936870"),
     *     @SWG\Property(property="auto_refuse_time", type="integer", example="0"),
     * )
     */


    /**
     * @SWG\Definition(
     *     definition="OrderInfo",
     *     type="object",
     *     @SWG\Property(property="title", type="string", example="测试"),
     *     @SWG\Property(property="order_id", type="string", example="3258750000110027"),
     *     @SWG\Property(property="company_id", type="integer", example="1"),
     *     @SWG\Property(property="user_id", type="integer", example="111"),
     *     @SWG\Property(property="act_id", type="integer", example="1"),
     *     @SWG\Property(property="mobile", type="integer", example=""),
     *     @SWG\Property(property="freight_fee", type="integer"),
     *     @SWG\Property(property="freight_type", type="string", example="cash"),
     *     @SWG\Property(property="item_fee", type="integer", example="7"),
     *     @SWG\Property(property="item_point", type="integer", example="0"),
     *     @SWG\Property(property="cost_fee", type="integer", example="10000"),
     *     @SWG\Property(property="total_fee", type="integer", example="4"),
     *     @SWG\Property(property="step_paid_fee", type="integer", example="0"),
     *     @SWG\Property(property="total_rebate", type="integer", example="1"),
     *     @SWG\Property(property="receipt_type", type="string", example="logistics"),
     *     @SWG\Property(property="ziti_code", type="integer", example="7"),
     *     @SWG\Property(property="shop_id", type="integer", example="7"),
     *     @SWG\Property(property="ziti_status", type="string", example="NOTZITI"),
     *     @SWG\Property(property="order_status", type="string", example="DONE"),
     *     @SWG\Property(property="order_source", type="string", example="member"),
     *     @SWG\Property(property="order_type", type="string", example="normal"),
     *     @SWG\Property(property="order_class", type="string", example="normal"),
     *     @SWG\Property(property="auto_cancel_time", type="string", example="1606906883"),
     *     @SWG\Property(property="auto_cancel_seconds", type="string", example="100"),
     *     @SWG\Property(property="auto_finish_time", type="string", example=""),
     *     @SWG\Property(property="is_distribution", type="boolean", example="true"),
     *     @SWG\Property(property="source_id", type="string", example=""),
     *     @SWG\Property(property="monitor_id", type="integer", example=""),
     *     @SWG\Property(property="salesman_id", type="integer", example=""),
     *     @SWG\Property(property="delivery_corp", type="string", example=""),
     *     @SWG\Property(property="delivery_code", type="string", example=""),
     *     @SWG\Property(property="delivery_img", type="string", example=""),
     *     @SWG\Property(property="delivery_status", type="string", example="DONE"),
     *     @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL"),
     *     @SWG\Property(property="delivery_time", type="string", example="1606907764"),
     *     @SWG\Property(property="end_time", type="string", example="1606908751"),
     *     @SWG\Property(property="end_date", type="string", example=""),
     *     @SWG\Property(property="receiver_name", type="string", example=""),
     *     @SWG\Property(property="receiver_mobile", type="string", example=""),
     *     @SWG\Property(property="receiver_zip", type="string", example=""),
     *     @SWG\Property(property="receiver_state", type="string", example=""),
     *     @SWG\Property(property="receiver_district", type="string", example=""),
     *     @SWG\Property(property="receiver_address", type="string", example=""),
     *     @SWG\Property(property="member_discount", type="string", example=""),
     *     @SWG\Property(property="coupon_discount", type="string", example=""),
     *     @SWG\Property(property="discount_fee", type="string", example=""),
     *     @SWG\Property(property="create_time", type="string", example=""),
     *     @SWG\Property(property="update_time", type="string", example=""),
     *     @SWG\Property(property="fee_type", type="string", example=""),
     *     @SWG\Property(property="fee_rate", type="string", example=""),
     *     @SWG\Property(property="fee_symbol", type="string", example=""),
     *     @SWG\Property(property="cny_fee", type="string", example=""),
     *     @SWG\Property(property="point", type="string", example=""),
     *     @SWG\Property(property="third_params", type="string", example=""),
     *     @SWG\Property(property="invoice", type="string", example=""),
     *     @SWG\Property(property="send_point", type="string", example=""),
     *     @SWG\Property(property="is_rate", type="string", example=""),
     *     @SWG\Property(property="is_invoiced", type="string", example=""),
     *     @SWG\Property(property="invoice_number", type="string", example=""),
     *     @SWG\Property(property="audit_status", type="string", example=""),
     *     @SWG\Property(property="audit_msg", type="string", example=""),
     *     @SWG\Property(property="point_use", type="string", example=""),
     *     @SWG\Property(property="get_points", type="string", example=""),
     *     @SWG\Property(property="bonus_points", type="string", example=""),
     *     @SWG\Property(property="get_point_type", type="string", example=""),
     *     @SWG\Property(property="pack", type="string", example=""),
     *     @SWG\Property(property="identity_id", type="string", example=""),
     *     @SWG\Property(property="identity_name", type="string", example=""),
     *     @SWG\Property(property="total_tax", type="string", example=""),
     *     @SWG\Property(property="discount_info", type="object", @SWG\Items(
     *         @SWG\Property(property="id", type="integer", example=""),
     *         @SWG\Property(property="coupon_code", type="string", example=""),
     *         @SWG\Property(property="info", type="string", example=""),
     *         @SWG\Property(property="type", type="string", example=""),
     *         @SWG\Property(property="rule", type="string", example=""),
     *         @SWG\Property(property="discount_fee", type="string", example=""),
     *     ))
     * )
     */


    /**
     * @SWG\Get(
     *     path="/aftersales/{aftersales_bn}",
     *     summary="获取售后单详情",
     *     tags={"售后"},
     *     description="获取售后单详情",
     *     operationId="getAftersalesDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="aftersales_bn",
     *         in="path",
     *         description="售后单号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object",
     *               ref="#/definitions/Aftersales"
     *            )
     *        )
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getAftersalesDetail($aftersales_bn)
    {
        // ShopEx EcShopX Service Component
        if (!$aftersales_bn) {
            throw new ResourceException('没有填写售后单号');
        }
        $companyId = app('auth')->user()->get('company_id');
        $aftersalesService = new AftersalesService();
        $filter = [
            'company_id' => $companyId,
            'aftersales_bn' => $aftersales_bn,
        ];
        $result = $aftersalesService->getAftersales($filter, true);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/aftersales",
     *     summary="获取售后单列表",
     *     tags={"售后"},
     *     description="getAftersalesList",
     *     operationId="getAftersalesList",
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
     *         name="aftersales_bn",
     *         in="query",
     *         description="售后单号",
     *         type="string",
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
     *         name="shop_id",
     *         in="query",
     *         description="店铺ID",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="分销商ID",
     *         type="string",
     *     ),
     *    @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="用户id",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="aftersales_type",
     *         in="query",
     *         description="售后类型",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="aftersales_status",
     *         in="query",
     *         description="售后状态",
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
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="total_count", type="integer"),
     *                     @SWG\Property(property="list", type="array", @SWG\Items(
     *                         ref="#/definitions/Aftersales"
     *                     )),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getAftersalesList(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }
        if ($request->input('time_start_begin')) {
            $filter['create_time|gte'] = $request->input('time_start_begin');
            $filter['create_time|lte'] = $request->input('time_start_end');
        }

        $filter['aftersales_status'] = $request->input('aftersales_status');

        if ($request->input('aftersales_type')) {
            $filter['aftersales_type'] = $request->input('aftersales_type');
        }
        if ($aftersales_bn = $request->input('aftersales_bn')) {
            if (strlen($aftersales_bn) < 15) {
                $filter['aftersales_bn|contains'] = $aftersales_bn;
            } else {
                $filter['aftersales_bn'] = $aftersales_bn;
            }
        }
        if ($request->input('item_id')) {
            $filter['item_id'] = $request->input('item_id');
        }
        if ($request->input('item_bn')) {
            $filter['item_bn|contains'] = $request->input('item_bn');
        }
        if ($order_id = $request->input('order_id')) {
            if (strlen($order_id) < 16) {
                $filter['order_id|contains'] = $order_id;
            } else {
                $filter['order_id'] = $order_id;
            }
        }
        if ($receiver_mobile = trim($request->input('receiver_mobile'))) {
            $this->__getReceiverMobileFilter($receiver_mobile, $filter);
        }
        if ($request->input('shop_id')) {
            $filter['shop_id'] = $request->input('shop_id');
        }
        if ($request->input('user_id')) {
            $filter['user_id'] = $request->input('user_id');
        }

        if ($mobile = $request->input('mobile')) {
            $filter['mobile'] = $mobile;
        }

        $distributor_id = $request->get('distributor_id');
        if (!is_null($distributor_id)) {
            $filter['distributor_id'] = $distributor_id;
        } elseif ($request->get('distributorIds', [])) {
            $filter['distributor_id'] = $request->get('distributorIds');
        }

        $distributorListSet = app('auth')->user()->get('distributor_ids');
        if (!empty($distributorListSet)) {
            $distributorIdSet = array_column($distributorListSet, 'distributor_id');
            if (!empty($filter['distributor_id'])) {
                if (is_array($filter['distributor_id'])) {
                    $filter['distributor_id'] = array_intersect($filter['distributor_id'], $distributorIdSet);
                } else {
                    if (!in_array($filter['distributor_id'], $distributorIdSet)) {
                        unset($filter['distributor_id']);
                    }
                }
            } else {
                $filter['distributor_id'] = $distributorIdSet;
            }
        }

        // 是否为处方单，关联订单查询
        if ($request->input('is_prescription_order')) {
            $filter['is_prescription_order'] = $request->input('is_prescription_order');
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $operatorType = app('auth')->user()->get('operator_type');
        $merchantId = app('auth')->user()->get('merchant_id');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');;
        }
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);
        $offset = ($page - 1) * $limit;
        $filter['need_order'] = true;

        $aftersalesService = new AftersalesService();
        $orderBy = ['create_time' => 'DESC'];
        if ($request->input('order_by') == 'asc') {
            $orderBy = ['create_time' => 'ASC'];
        }

        if (isset($filter['distributor_id']) && $filter['distributor_id']) {
            $filter['or'] = [
                 'distributor_id' => $filter['distributor_id'],
                 'return_distributor_id' => $filter['distributor_id'],
            ];
            unset($filter['distributor_id']);
        }
        $normalOrderService = new NormalOrderService();
        if ($orderClass = $request->get('order_class', '')) {
            $orderLists = $normalOrderService->normalOrdersRepository->getList(['company_id' => $companyId, 'order_class' => $orderClass], 0, -1, null, 'order_id');
            if (empty($orderLists)) {
                $filter['order_id'] = [-1];
            } else {
                $filter['order_id'] = array_column($orderLists, 'order_id');
            }
        }

        if ($request->input('item_name')) {
            $filter['item_name'] = $request->input('item_name');
        }

        $result = $aftersalesService->getAftersalesList($filter, $offset, $limit, $orderBy, true);
        if ($result['list']) {

            //获取订单信息
            $orderData = [];
            $orderIds = array_column($result['list'], 'order_id');
            // $normalOrderService = new NormalOrderService();
            $orderRs = $normalOrderService->normalOrdersRepository->getList(['company_id' => $companyId, 'order_id' => $orderIds], 0, 100, null, 'order_id, order_holder, o.receiver_mobile, o.salesman_id');
            if ($orderRs) {
                $orderData = array_column($orderRs, null, 'order_id');
            }

            //获取供应商信息
            $supplierData = [];
            $supplierIds = array_filter(array_column($result['list'], 'supplier_id'));
            if ($supplierIds) {
                $supplierService = new SupplierService();
                $rs = $supplierService->repository->getLists(['operator_id' => $supplierIds], 'operator_id, supplier_name');
                $supplierData = array_column($rs, null, 'operator_id');
            }

            // 售后退单数据
            $aftersalesBns = array_column($result['list'], 'aftersales_bn');
            $aftersalesRefundService = new AftersalesRefundService();
            $aftersalesBnTmp = $aftersalesRefundService->getRefundsList(['company_id' => $companyId, 'aftersales_bn' => $aftersalesBns], 0, -1);
            $aftersalesBnData =  array_column($aftersalesBnTmp['list'], null, 'aftersales_bn');

            // 导购数据
            $salespersonIds = array_column($orderRs, 'salesman_id');
            $salespersonIds = array_filter($salespersonIds); // 过滤掉 null、0、false、空字符串等
            $salespersonList = [];
            if (!empty($salespersonIds)) {
                $salespersonService = new SalespersonService();
                $filterSalespersion['company_id'] = $companyId;
                $filterSalespersion['salesperson_id'] = $salespersonIds;
                $filterSalespersion['salesperson_type'] = 'shopping_guide';
                $salespersonListTmp = $salespersonService->getLists($filterSalespersion,'salesperson_id,name,work_userid', 1, -1);
                $salespersonList =  array_column($salespersonListTmp, null, 'salesperson_id');
            }

            // 商品数据
            $itemListTmp = $aftersalesService->getItemsByAftersalesBnByNun($companyId, $aftersalesBns);
            $itemList =  array_column($itemListTmp, null, 'aftersales_bn');

            // 是否有权限查看加密数据
            $datapassBlock = $request->get('x-datapass-block');
            foreach ($result['list'] as $key => &$value) {
                $orderInfo = $orderData[$value['order_id']] ?? [];
                $value['refund_fee'] = bcdiv($value['refund_fee'], '100', 2);
                $value['receiver_mobile'] = $orderInfo['receiver_mobile'] ?? '';
                $value['order_holder'] = $orderInfo['order_holder'] ?? '';
                $value['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
                $value['supplier_name'] = isset($supplierData[$value['supplier_id']]) ? $supplierData[$value['supplier_id']]['supplier_name'] : '';
                if ($datapassBlock) {
                    $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                }

                $value['refunded_fee'] = $aftersalesBnData[$value['aftersales_bn']]['refunded_fee'] ?? 0;
                $value['refunded_point'] = $aftersalesBnData[$value['aftersales_bn']]['refunded_point'] ?? 0;
                $value['salesman_name'] = $salespersonList[$orderInfo['salesman_id']]['work_userid'] ?? '';
                $value['item_name'] = $itemList[$value['aftersales_bn']]['item_name'] ?? '';
                $value['item_bn'] = $itemList[$value['aftersales_bn']]['item_bn'] ?? '';
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/aftersales/review",
     *     summary="售后审核",
     *     tags={"售后"},
     *     description="售后审核",
     *     operationId="aftersalesReview",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="aftersales_bn",
     *         in="query",
     *         description="售后单号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_approved",
     *         in="query",
     *         description="处理结果",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refuse_reason",
     *         in="query",
     *         description="拒绝原因",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refund_fee",
     *         in="query",
     *         description="退款金额",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refund_point",
     *         in="query",
     *         description="退款积分",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="aftersales_address", type="array", @SWG\Items()),
     *                 @SWG\Property(property="aftersales_bn", type="string", example="202101219952564"),
     *                 @SWG\Property(property="aftersales_status", type="integer", example="0"),
     *                 @SWG\Property(property="aftersales_type", type="string", example="ONLY_REFUND"),
     *                 @SWG\Property(property="company_id", type="integer", example="1"),
     *                 @SWG\Property(property="create_time", type="string", example="1611201484"),
     *                 @SWG\Property(property="description", type="integer", example="321321"),
     *                 @SWG\Property(property="evidence_pic", type="array", @SWG\Items()),
     *                 @SWG\Property(property="memo", type="string", example=""),
     *                 @SWG\Property(property="order_id", type="integer", example="3308477000040347"),
     *                 @SWG\Property(property="progress", type="integer", example="0"),
     *                 @SWG\Property(property="reason", type="string", example="sdadasdas"),
     *                 @SWG\Property(property="refund_fee", type="integer", example="10000"),
     *                 @SWG\Property(property="refund_point", type="integer", example="0"),
     *                 @SWG\Property(property="refuse_reason", type="string", example=""),
     *                 @SWG\Property(property="salesman_id", type="integer", example="0"),
     *                 @SWG\Property(property="sendback_data", type="array", @SWG\Items()),
     *                 @SWG\Property(property="sendconfirm_data", type="array", @SWG\Items()),
     *                 @SWG\Property(property="shop_id", type="integer", example="0"),
     *                 @SWG\Property(property="third_data", type="string", example=""),
     *                 @SWG\Property(property="user_id", type="integer", example="20347"),
     *
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function aftersalesReview(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $params = $request->all();
        $params['company_id'] = $companyId;
        $validator = app('validator')->make($params, [
            'aftersales_bn' => 'required',
            'company_id' => 'required',
            'is_approved' => 'required',
            'refuse_reason' => 'required_if:is_approved,0',
//            'refuse_reason' => 'required_if:is_approved,0|max:300',
        ], [
            'aftersales_bn.*' => '售后单号必填',
            'company_id.*' => '企业id必填',
            'is_approved.*' => '处理结果必选',
            'refuse_reason.*' => '拒绝原因必填',
//            'refuse_reason.*' => '拒绝原因必填,且最大长度不超过300个字符',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }
        $params['refund_fee'] = intval($params['refund_fee'] ?? 0);
        $params['refund_point'] = $params['refund_point'] ?? 0;
        $params['freight'] = $params['freight'] ?? 0;
        $params['operator_type'] = 'admin';
        $params['operator_id'] = app('auth')->user()->get('operator_id');
        $aftersalesService = new AftersalesService();
        // 批量处理
        if (is_array($params['aftersales_bn'])) {
            $aftersales_bn_arr = $params['aftersales_bn'];
            $paramsBath = [];
            unset($params['aftersales_bn']);
            foreach($aftersales_bn_arr as $aftersalesBn){
                $paramsBath = $params;
                $paramsBath['aftersales_bn'] = $aftersalesBn;
                $result[] = $aftersalesService->review($paramsBath);
            }
        }else {
            $result = $aftersalesService->review($params);
        }

        return $this->response->array(['status' => true, 'result' => $result]);
    }

    /**
     * @SWG\Put(path="/aftersales/remark",
     *   tags={"售后"},
     *   summary="更新售后备注",
     *   description="更新售后备注",
     *   operationId="aftersalesRemark",
     *   @SWG\Parameter( name="aftersales_bn", in="query", description="售后单号", required=true, type="string"),
     *   @SWG\Parameter( name="remark", in="query", description="售后备注", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object",
     *               ref="#/definitions/Aftersales"
     *            )
     *        )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function updateRemark(Request $request)
    {
        $params = $request->all('aftersales_bn', 'remark');
        $rules = [
            'aftersales_bn' => ['required', '售后单号必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        if (mb_strlen($params['remark']) > 150) {
            throw new ResourceException('字数请不要超过150个！');
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'aftersales_bn' => $params['aftersales_bn'],
            'company_id' => $companyId,
        ];

        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->updateRemark($filter, $params['remark']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/aftersales/sendConfirm",
     *     summary="换货重新发货",
     *     tags={"售后"},
     *     description="换货重新发货",
     *     operationId="sendConfirm",
     *     @SWG\Parameter( name="aftersales_bn", in="query", description="售后单号", required=true, type="string"),
     *     @SWG\Parameter( name="corp_code", in="query", description="快递公司", required=true, type="string"),
     *     @SWG\Parameter( name="logi_no", in="query", description="快递单号", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function sendConfirm(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $params['company_id'] = $companyId;
        $validator = app('validator')->make($params, [
            'aftersales_bn' => 'required',
            'company_id' => 'required',
            'corp_code' => 'required|min:6|max:30',
            'logi_no' => 'required',
        ], [
            'aftersales_bn.*' => '售后单号必填',
            'company_id.*' => '企业ID必填',
            'corp_code.*' => '物流公司不能为空',
            'logi_no.*' => '物流单号不能为空,运单号不能小于6,运单号不能大于20',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }

        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->sendConfirm($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/aftersales/refundCheck",
     *     summary="退款确认",
     *     tags={"售后"},
     *     description="退款确认",
     *     operationId="refundCheck",
     *     @SWG\Parameter( name="aftersales_bn", in="query", description="售后单号", required=true, type="string"),
     *     @SWG\Parameter( name="check_refund", in="query", description="是否退款", required=true, type="boolean"),
     *     @SWG\Parameter( name="refund_memo", in="query", description="退款备注", required=true, type="string"),
     *     @SWG\Parameter( name="refund_fee", in="query", description="退款金额", required=true, type="string"),
     *     @SWG\Parameter( name="refund_point", in="query", description="退款积分", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Aftersales"
     *             )
     *         )
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function refundCheck(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $params = $request->all();
        $params['company_id'] = $companyId;
        $validator = app('validator')->make($params, [
            'aftersales_bn' => 'required',
            'company_id' => 'required',
            'check_refund' => 'required',
            // 'refund_fee' => 'required_if: check_refund,1|integer',
            'refunds_memo' => 'required_if:check_refund,0',
        ],[
            'aftersales_bn.*' => '售后单号必填',
            'company_id.*' => '企业ID必填',
            'check_refund.*' => '是否退款必选',
            // 'refund_fee.*' => '退款金额必填,以分为单位，必须为整数',
            'refunds_memo.*' => '拒绝原因必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }

        $aftersalesService = new AftersalesService();
        if (is_array($params['aftersales_bn'])) {
            $aftersales_bns = $params['aftersales_bn'];
            unset($params['aftersales_bn']);
            $paramsBath = [];
            foreach($aftersales_bns as $aftersales_bn) {
                $paramsBath = $params;
                $paramsBath['aftersales_bn'] = $aftersales_bn;
                $filter = [
                    'aftersales_bn' => $paramsBath['aftersales_bn'],
                    'company_id' => $paramsBath['company_id']
                ];
                $aftersales = $aftersalesService->aftersalesRepository->get($filter);
                if (!isset($paramsBath['refund_fee']) || empty($paramsBath['refund_fee'])) {
                    $paramsBath['refund_fee'] = $aftersales['refund_fee'];
                }
                if (!isset($paramsBath['refund_point']) || empty($paramsBath['refund_point'])) {
                    $paramsBath['refund_point'] = $aftersales['refund_point'];
                }
                $paramsBath['operator_type'] = 'admin';
                $paramsBath['operator_id'] = app('auth')->user()->get('operator_id');
                $result[] = $aftersalesService->confirmRefund($paramsBath);
            }   
        }else {
            $filter = [
                'aftersales_bn' => $params['aftersales_bn'],
                'company_id' => $params['company_id']
            ];
            $aftersales = $aftersalesService->aftersalesRepository->get($filter);
            if (!isset($params['refund_fee']) || empty($params['refund_fee'])) {
                $params['refund_fee'] = $aftersales['refund_fee'];
            }
            if (!isset($params['refund_point']) || empty($params['refund_point'])) {
                $params['refund_point'] = $aftersales['refund_point'];
            }
            $params['operator_type'] = 'admin';
            $params['operator_id'] = app('auth')->user()->get('operator_id');
            $result = $aftersalesService->confirmRefund($params);
        }

        return $this->response->array(['status' => true, 'result' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/aftersales/logExport",
     *     summary="导出售后列表",
     *     tags={"售后"},
     *     description="导出售后列表",
     *     operationId="logExport",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="time_start_begin",
     *         in="query",
     *         description="开始时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_end",
     *         in="query",
     *         description="结束时间",
     *         required=true,
     *         type="integer"
     *     ),
     *    @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="分销商ID",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="订单号",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="aftersales_bn",
     *         in="query",
     *         description="售后单号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="aftersales_status",
     *         in="query",
     *         description="售后状态",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="aftersales_type",
     *         in="query",
     *         description="售后类型",
     *         type="string"
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
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function logExport(Request $request)
    {
        $params = $request->all('distributor_id', 'time_start_begin', 'time_start_end', 'order_id', 'aftersales_bn', 'mobile', 'aftersales_status', 'aftersales_type', 'order_class');

        if ($params['distributor_id']) {
            $filter['distributor_id'] = $params['distributor_id'];
        }
        if ($params['time_start_begin'] && $params['time_start_end']) {
            $filter['create_time|gte'] = $params['time_start_begin'];
            $filter['create_time|lte'] = $params['time_start_end'];
        }

        if ($order_id = $request->input('order_id')) {
            if (strlen($order_id) < 16) {
                $filter['order_id|contains'] = $order_id;
            } else {
                $filter['order_id'] = $order_id;
            }
        }

        if ($receiver_mobile = trim($request->input('receiver_mobile'))) {
            $this->__getReceiverMobileFilter($receiver_mobile, $filter);
        }

        if ($mobile = $request->input('mobile')) {
            $filter['mobile'] = $mobile;
        }

        if ($aftersales_bn = $request->input('aftersales_bn')) {
            if (strlen($aftersales_bn) < 15) {
                $filter['aftersales_bn|contains'] = $aftersales_bn;
            } else {
                $filter['aftersales_bn'] = $aftersales_bn;
            }
        }

        $filter['aftersales_status'] = $params['aftersales_status'];

        if ($params['aftersales_type']) {
            $filter['aftersales_type'] = $params['aftersales_type'];
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');;
        }

        if ($params['order_class']) {
            $normalOrderService = new NormalOrderService();
            $orderLists = $normalOrderService->normalOrdersRepository->getList(['company_id' => $filter['company_id'], 'order_class' => $params['order_class']], 0, -1, null, 'order_id');
            if (empty($orderLists)) {
                $filter['order_id'] = [-1];
            } else {
                $filter['order_id'] = array_column($orderLists, 'order_id');
            }
        }

        $aftersalesService = new AftersalesService();
        $count = $aftersalesService->count($filter);

        if ($count <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }

        if ($count > 15000) {
            throw new resourceexception('导出有误，最高导出15000条数据');
        }

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');

//        if ($count > 500) {
        $gotoJob = (new ExportFileJob('aftersale_record_count', $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
//         } else {
//             $exportService = $this->getService('aftersale_record_count');
//             $result = $exportService->exportData($filter);
//             return response()->json($result);
//         }
    }

    /**
     * @SWG\Get(
     *     path="/aftersales/financialExport",
     *     summary="导出售后报表",
     *     tags={"售后"},
     *     description="导出售后报表",
     *     operationId="financialExport",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="start_time",
     *         in="query",
     *         description="开始时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="结束时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="订单号",
     *         required=true,
     *         type="integer"
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
     *                     @SWG\Property(property="status", type="boolean", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function financialExport(Request $request)
    {
        $params = $request->all('time_start_begin', 'time_start_end', 'order_id');
        // 已处理的售后单
        $filter = ['aftersales_status' => 2];
        if ($params['time_start_begin'] && $params['time_start_end']) {
            $filter['create_time|gte'] = $params['time_start_begin'];
            $filter['create_time|lte'] = $params['time_start_end'];
        }
        if ($params['order_id']) {
            $filter['order_id'] = $params['order_id'];
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');

        $aftersalesService = new AftersalesService();
        $count = $aftersalesService->count($filter);

        if ($count <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }

        if ($count > 15000) {
            throw new resourceexception('导出有误，最高导出15000条数据');
        }

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        $gotoJob = (new ExportFileJob('aftersale_financial', $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }


    /**
     * @SWG\Get(
     *     path="/aftersales/remind/detail",
     *     summary="售后提醒内容获取",
     *     tags={"售后"},
     *     description="售后提醒内容获取",
     *     operationId="getRemind",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
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
     *                     @SWG\Property(property="intro", type="string", description="内容详情" ),
     *                     @SWG\Property(property="is_open", type="boolean", description="是否开启", default=false ),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getRemind()
    {
        $company_id = app('auth')->user()->get('company_id');
        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->getRemind($company_id);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/aftersales/remind",
     *     summary="售后提醒内容设置",
     *     tags={"售后"},
     *     description="售后提醒内容设置",
     *     operationId="setRemind",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *    @SWG\Parameter(
     *         name="intro",
     *         in="query",
     *         description="内容详情",
     *         required=true,
     *         type="string",
     *     ),
     *    @SWG\Parameter(
     *         name="is_open",
     *         in="query",
     *         description="是否开启",
     *         required=true,
     *         type="boolean",
     *         default=false
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
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function setRemind(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'intro' => ['required_if:is_open,true|string', '售后内容必填'],
            'is_open' => ['in:true,false', '是否开启参数不正确'],
        ];

        $introTxt = strip_tags($params['intro']);
        if (mb_strlen($introTxt, 'UTF-8') > 200) {
            throw new ResourceException('售后内容最大长度不超过200个汉字');
        }

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $aftersalesService = new AftersalesService();
        $aftersalesService->setRemind($company_id, $params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/aftersales/apply",
     *     summary="创建售后单",
     *     tags={"售后"},
     *     description="创建售后单",
     *     operationId="apply",
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="detail", in="query", description="售后商品明细", required=true, type="string",required=true),
     *     @SWG\Parameter( name="aftersales_type", in="query", required=true, description="售后类型， ONLY_REFUND 仅退款 REFUND_GOODS 退货退款 EXCHANGING_GOODS 换货", required=true, type="string"),
     *     @SWG\Parameter( name="goods_returned", in="query", required=true, description="是否到店退货", required=true, type="boolean"),
     *     @SWG\Parameter( name="reason", in="query", description="申请售后原因", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="申请描述", required=true, type="string"),
     *     @SWG\Parameter( name="evidence_pic", in="query", description="凭证图片", required=true, type="string"),
     *     @SWG\Parameter( name="refund_fee", in="query", description="退款金额", required=true, type="number"),
     *     @SWG\Parameter( name="refund_point", in="query", description="退还积分", required=true, type="number"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#definitions/Aftersales"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function apply(Request $request)
    {
        $params = $request->all('order_id', 'detail', 'aftersales_type', 'goods_returned', 'reason', 'description', 'evidence_pic', 'refund_fee', 'refund_point','freight');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['operator_type'] = app('auth')->user()->get('operator_type');
        $params['operator_id'] = app('auth')->user()->get('operator_id');
        if (isset($params['detail']) && $params['detail'] && is_string($params['detail'])) {
            $params['detail'] = json_decode($params['detail'], true);
        }
        $validator = app('validator')->make($params, [
            'order_id' => 'required',
            'detail.*.id' => 'required',
            'detail.*.num' => 'required',
            'company_id' => 'required',
            'aftersales_type' => 'required',
            'goods_returned' => 'required_if:aftersales_type,REFUND_GOODS',
            'reason' => 'required',
            'refund_fee' => 'required',
            'refund_point' => 'required',
        ], [
            'order_id.*' => '订单号必填,必须为整数',
            'detail.*.id.*' => '售后明细商品ID必填',
            'detail.*.num.*' => '售后明细商品数量必填',
            'company_id.*' => '企业id必填',
            'aftersales_type.*' => '售后类型必选',
            'goods_returned.*' => '请选择是否到店退货',
            'reason.*' => '售后原因必选',
            'refund_fee.*' => '退款金额必填',
            'refund_point.*' => '退还积分必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($params['company_id'], $params['order_id']);
        if (!$order) {
            throw new \Exception("订单号为{$params['order_id']}的订单不存在");
        }
        if ($order['order_type'] != 'normal') {
            throw new ResourceException("实体类订单才能申请售后！");
        }
        $params['user_id'] = $order['user_id'];
        $params['goods_returned'] = $params['goods_returned'] == 'true';
        $params['distributor_id'] = $request->get('distributor_id');
        if ($params['goods_returned']) {
            $params['return_type'] = 'offline';
        }

        if (!$params['user_id'] && $params['aftersales_type'] == 'REFUND_GOODS' && !$params['goods_returned']) {
            throw new ResourceException('匿名订单只能申请仅退款或者到店退货');
        }

        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->shopApplyByNum($params);

        return $this->response->array(['status' => true, 'result' => $result]);
    }

        /**
     * @SWG\Post(
     *     path="/aftersales/sendback",
     *     summary="售后管理员填写寄回信息",
     *     tags={"售后"},
     *     description="售后管理员填写寄回信息",
     *     operationId="sendback",
     *     @SWG\Parameter( name="aftersales_bn", in="query", description="售后单号", required=true, type="string"),
     *     @SWG\Parameter( name="corp_code", in="query", description="快递公司", required=true, type="string"),
     *     @SWG\Parameter( name="logi_no", in="query", description="快递单号", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="data", type="object",ref="#definitions/Aftersales"),
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function sendback(Request $request)
    {
        $params = $request->all('aftersales_bn', 'corp_code', 'logi_no');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['operator_type'] = app('auth')->user()->get('operator_type');
        $params['operator_id'] = app('auth')->user()->get('operator_id');
        $validator = app('validator')->make($params, [
            'aftersales_bn' => 'required',
            'company_id' => 'required',
            'corp_code' => 'required',
            'logi_no' => 'required|min:6|max:30',
        ], [
            'aftersales_bn.*' => '售后单号必填',
            'company_id.*' => '企业ID必填',
            'corp_code.*' => '物流公司不能为空',
            'logi_no.*' => '物流单号不能为空,运单号不能小于6,运单号不能大于20',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }

        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->shopSendBack($params);

        return $this->response->array($result);
    }

    public function __getReceiverMobileFilter($receiver_mobile = '', &$filter = [])
    {
        if (!$receiver_mobile) {
            return true;
        }
        $normalOrderService = new NormalOrderService();
        $rs = $normalOrderService->normalOrdersRepository->getList(['receiver_mobile' => $receiver_mobile]);
        if ($rs) {
            $orderIds = array_column($rs, 'order_id');
            if (isset($filter['order_id'])) {
                $filter['order_id'] = array_merge($filter['order_id'], $orderIds);
                if (!$filter['order_id']) {
                    $filter['aftersales_bn'] = -1;
                }
            } else {
                $filter['order_id'] = $orderIds;
            }
        } else {
            $filter['aftersales_bn'] = -1;
        }
        return true;
    }
}
