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

namespace SystemLinkBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;
use SystemLinkBundle\Http\Controllers\Controller as Controller;

use OrdersBundle\Services\Orders\NormalOrderService;


use AftersalesBundle\Services\AftersalesService;

use SystemLinkBundle\Services\ShopexErp\OrderRefundService;

use SystemLinkBundle\Services\ShopexErp\Request as OmeRequest;

use OrdersBundle\Traits\GetOrderServiceTrait;

use AftersalesBundle\Services\AftersalesRefundService;

use SystemLinkBundle\Events\TradeRefundFinishEvent;
use SystemLinkBundle\Events\TradeUpdateEvent as OmsTradeUpdateEvent;

// use OrdersBundle\Services\Orders\AbstractNormalOrder;

class Refund extends Controller
{
    use GetOrderServiceTrait;

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/updateOrderRefund",
     *     summary="OMS新增退款单",
     *     tags={"omeapi"},
     *     description="OMS新增退款单",
     *     operationId="updateOrderRefund",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="store.trade.refund.add", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="tid", in="query", description="订单号", default="123456789", required=true, type="string"),
     *     @SWG\Parameter( name="refund_id", in="query", description="退款单ID", default="12345", required=true, type="string"),
     *     @SWG\Parameter( name="refund_fee", in="query", description="退款金额", default="100.10", required=true, type="string"),
     *     @SWG\Parameter( name="currency", in="query", description="退款货币类型", default="CNY", required=true, type="string"),
     *     @SWG\Parameter( name="aftersale_id", in="query", description="退货单ID", default="", required=false, type="string"),
     *     @SWG\Parameter( name="memo", in="query", description="备注", default="", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function updateOrderRefund(Request $request)
    {
        // Built with ShopEx Framework
        $params = $request->all();
        app('log')->debug('Refund_updateOrderRefund_params=>:'.var_export($params, 1));

        $rules = [
            'tid' => ['required', '订单号缺少'],
            'refund_id' => ['required', '退款单ID缺少'],
            'refund_fee' => ['required', '退款金额缺少'],
            'currency' => ['required', '退款货币类型缺少'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $aftersalesService = new AftersalesService();

        //退货退款
        if (isset($params['aftersale_id']) && trim($params['aftersale_id'])) {
            $filter = ['aftersales_bn' => $params['aftersale_id']];

            $afterRefundInfo = $aftersalesService->aftersalesRefundRepository->getInfo($filter);

            if (!$afterRefundInfo) {
                //后端发起退款 本地退款单不存在的情况 直接创建退款单

                $afterInfo = $aftersalesService->aftersalesRepository->get($filter);
                app('log')->debug('Refund_updateOrderRefund_afterInfo=>:'.var_export($afterInfo, 1));

                if (!$afterInfo) {
                    $this->api_response('fail', '售后单不存在');
                }

                $aftersalesRefundService = new AftersalesRefundService();

                $orderService = $this->getOrderService('normal');

                // 获取订单信息
                $orderData = $orderService->getOrderInfo($afterInfo['company_id'], $afterInfo['order_id']);

                if (!$orderData) {
                    $this->api_response('fail', '获取订单信息失败');
                }

                $afterDetailInfo = $aftersalesRefundService->aftersalesDetailRepository->get($filter);

                $full_refund = false; //是否全额退款

                //获取退款申请单
                $paramsRefund = [
                    'refund_bn' => $params['refund_id'],
                    'order_id' => $afterInfo['order_id'],
                    'company_id' => $afterInfo['company_id'],
                    'check_refund' => true,
                    //'refund_fee' => $orderData['tradeInfo']['payFee'],
                    'refund_fee' => (float)$params['refund_fee'] * 100,
                    'refund_type' => 1,
                    'aftersales_bn' => $params['aftersale_id'],
                    'refunds_memo' => isset($afterDetailInfo['reason']) ? $afterDetailInfo['reason'].'('.$afterDetailInfo['description'].')' : '',
                ];

                //售后申请仅退款产生的退款申请
                $orderItem = [];
                foreach ($orderData['orderInfo']['items'] as $ival) {
                    if ($afterInfo['item_id'] == $ival['item_id']) {
                        $orderItem = $ival;
                        //退款金额以oms为准
                        //$paramsRefund['refund_fee'] = $ival['total_fee'];
                    }
                }

                //todo 注意，这里参数不对，执行不了
                $afterRefundInfo = $aftersalesRefundService->createRefund($orderData['orderInfo'], $orderData['tradeInfo'], $paramsRefund, $full_refund, $orderItem);
                app('log')->debug('Refund_updateOrderRefund_orderData=>:'.var_export($orderData, 1));
                app('log')->debug('Refund_updateOrderRefund_paramsRefund=>:'.var_export($paramsRefund, 1));
                app('log')->debug('Refund_updateOrderRefund_orderItem=>:'.var_export($orderItem, 1));
                app('log')->debug('Refund_updateOrderRefund_afterRefundInfo=>:'.var_export($afterRefundInfo, 1));

                if (!$afterRefundInfo) {
                    $this->api_response('fail', '创建退货退款单失败');
                }
            }
        } else {
            //发货前退款
            $filter = ['refund_bn' => $params['refund_id'],'order_id' => $params['tid']];

            $afterRefundInfo = $aftersalesService->aftersalesRefundRepository->getInfo($filter);
            app('log')->debug('Refund_updateOrderRefund_filter=>:'.var_export($filter, 1));
            app('log')->debug('Refund_updateOrderRefund_afterRefundInfo=>:'.var_export($afterRefundInfo, 1));

            if (!$afterRefundInfo) {
                //$this->api_response('fail', '获取退款单失败');

                // 获取订单信息 todo 这里的代码需要优化
                $orderItem = [];
                $full_refund = false;//是否全额退款
                $companyId = 0;
                $orderService = $this->getOrderService('normal');
                $orderData = $orderService->getOrderInfo($companyId, $params['tid']);
                if (!$orderData or !$orderData['tradeInfo']) {
                    $this->api_response('fail', '获取订单信息失败:'.$params['tid']);
                }

                //从oms数据新增退款单
                $paramsRefund = [
                    'refund_bn' => $params['refund_id'],
                    'order_id' => $params['tid'],
                    'company_id' => $orderData['orderInfo']['company_id'],
                    'check_refund' => true,
                    'refund_fee' => (float)$params['refund_fee'] * 100,
                    'refund_type' => 1,//取消订单退款
                    'aftersales_bn' => 0,
                    'refunds_memo' => $params['memo'] ?? '',
                ];
                $orderItem = [
                    'point' => $orderData['orderInfo']['point'],
                    'total_fee' => $orderData['orderInfo']['total_fee'],
                ];

                //todo 注意，这里参数不对，执行不了
                $aftersalesRefundService = new AftersalesRefundService();
                $afterRefundInfo = $aftersalesRefundService->createRefund($orderData['orderInfo'], $orderData['tradeInfo'], $paramsRefund, $full_refund, $orderItem);
                app('log')->debug('Refund_updateOrderRefund_orderData=>:'.var_export($orderData, 1));
                app('log')->debug('Refund_updateOrderRefund_paramsRefund=>:'.var_export($paramsRefund, 1));
                app('log')->debug('Refund_updateOrderRefund_orderItem=>:'.var_export($orderItem, 1));
                app('log')->debug('Refund_updateOrderRefund_afterRefundInfo=>:'.var_export($afterRefundInfo, 1));

                if (!$afterRefundInfo) {
                    $this->api_response('fail', '创建退款单失败');
                }
            }
        }

        $normalOrderService = new NormalOrderService();

        app('log')->debug('Refund_updateOrderRefund_company_id=>:'.var_export($afterRefundInfo['company_id'], 1));
        app('log')->debug('Refund_updateOrderRefund_order_id=>:'.var_export($afterRefundInfo['order_id'], 1));
        $tradeInfo = $normalOrderService->get($afterRefundInfo['company_id'], $afterRefundInfo['order_id']);
        app('log')->debug('Refund_updateOrderRefund_tradeInfo=>:'.var_export($tradeInfo, 1));

        if (!$tradeInfo) {
            $this->api_response('fail', '未找到订单');
        }

        try {
            // 无售后单 直接全额退款
            $result = true;
            if (!$afterRefundInfo['aftersales_bn']) {
                //无售后单的情况下同意退款
                $orderRefundSerrvice = new OrderRefundService();
                $result = $orderRefundSerrvice->toRefund('agreeRefund', $params, $afterRefundInfo);
            } else {
                //检测售后单是否存在
                $afterData = $aftersalesService->aftersalesRepository->get(['aftersales_bn' => $afterRefundInfo['aftersales_bn']]);
                app('log')->debug('Refund_updateOrderRefund_afterData=>:'.var_export($tradeInfo, 1));

                if (!$afterData) {
                    $this->api_response('fail', '售后单不存在');
                }

                //走售后退款环节
                if ($params['refund_fee'] > 0) {
                    $refund_fee = (float)$params['refund_fee'] * 100;
                } else {
                    $refund_fee = $afterRefundInfo['refund_fee'] * 100;
                }
                $refundData = [
                    'aftersales_bn' => $afterRefundInfo['aftersales_bn'],
                    'company_id' => $afterRefundInfo['company_id'],
                    'check_refund' => 1,
                    'refund_memo' => 'OMS确认退款',
                    'refund_fee' => $refund_fee,
                    'refund_bn' => $params['refund_id'] ?? 0,
                ];
                app('log')->debug('Refund_updateOrderRefund_refundData=>:'.var_export($refundData, 1));
                $result = $aftersalesService->confirmRefund($refundData);
            }

            app('log')->debug('Refund_updateOrderRefund_return=>:'.var_export($result, 1));

            //退款状态回打OMS
            event(new TradeRefundFinishEvent($afterRefundInfo));

            //触发订单oms更新的事件
            $eventData = [
                'company_id' => $afterRefundInfo['company_id'],
                'order_id' => $afterRefundInfo['order_id'],
                'order_class' => $tradeInfo->getOrderClass(),
                'user_id' => $afterRefundInfo['user_id'],
            ];
            event(new OmsTradeUpdateEvent($eventData));

            //下面改成异步执行
            /*
            $orderRefundService = new OrderRefundService();
            $omeRefundData = $orderRefundService->refundSendOme($afterRefundInfo);
            $omeRequest = new OmeRequest($afterRefundInfo['company_id']);
            $method = 'ome.refund.add';
            $result = $omeRequest->call($method, $omeRefundData);
            app('log')->debug($method.'=>omeRefundData:'.var_export($omeRefundData,1)."\r\n=>result:". var_export($result,1));
            if (!$result) {
                $this->api_response('fail', '退款操作失败');
            }
            */
        } catch (\Exception $e) {
            $this->api_response('fail', $e->getMessage());
        }

        $this->api_response('true', '操作成功', $result);
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/closeOrderRefund",
     *     summary="OMS拒绝退款",
     *     tags={"omeapi"},
     *     description="OMS拒绝退款",
     *     operationId="closeOrderRefund",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="store.refund.refuse", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="tid", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="refund_id", in="query", description="退款单ID", required=true, type="string"),
     *     @SWG\Parameter( name="refuse_message", in="query", description="拒绝原因", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function closeOrderRefund(Request $request)
    {
        // Built with ShopEx Framework
        $params = $request->all();
        app('log')->debug('Refund_closeOrderRefund_params=>:'.var_export($params, 1));

        $rules = [
            'tid' => ['required', '订单号缺少'],
            'refund_id' => ['required', '退款单ID缺少'],
            'refuse_message' => ['required', '拒绝原因缺少'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $aftersalesService = new AftersalesService();

        $afterInfo = $aftersalesService->aftersalesRefundRepository->getInfo(['refund_bn' => $params['refund_id'],'order_id' => $params['tid']]);
        if (!$afterInfo) {
            $this->api_response('fail', '退款单不存在');
        }

        try {
            //售后退款
            if ($afterInfo['aftersales_bn']) {
                $refunData = [
                    'aftersales_bn' => $afterInfo['aftersales_bn'],
                    'is_approved' => 0,
                    'refuse_reason' => $params['refuse_message'],
                    'company_id' => $afterInfo['company_id'],
                ];

                $return = $aftersalesService->review($refunData);
            } else {
                //无售后单的情况下拒绝退款
                $orderRefundSerrvice = new OrderRefundService();
                $return = $orderRefundSerrvice->toRefund('refuseRefund', $params, $afterInfo);
            }

            //更新退款单的状态为拒绝
            $refundFilter = [
                 'refund_bn' => $params['refund_id'],
                 'order_id' => $params['tid'],
            ];
            $refundUpdate = [
                'refund_status' => 'REFUNDCLOSE',
                'update_time' => time(),
            ];
            $aftersalesService->updateAftersalesRefund($refundUpdate, $refundFilter);
        } catch (\Exception $e) {
            app('log')->debug('Refund_closeOrderRefund_error=>:'.var_export($e->getMessage(), 1));
            $this->api_response('fail', $e->getMessage());
        }

        app('log')->debug('Refund_closeOrderRefund_return=>:'.var_export($return, 1));
        $this->api_response('true', '操作成功');
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/updateRefundStatus",
     *     summary="OMS修改退款状态",
     *     tags={"omeapi"},
     *     description="OMS修改退款状态(暂时没用)",
     *     operationId="updateRefundStatus",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ome.user.up", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="refund_id", in="query", description="退款单ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function updateRefundStatus(Request $request)
    {
        // $params = $request->all();
        $this->api_response('true', '操作成功');
    }
}
