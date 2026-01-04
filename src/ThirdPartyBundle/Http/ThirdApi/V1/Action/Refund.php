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

namespace ThirdPartyBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;
use ThirdPartyBundle\Http\Controllers\Controller as Controller;

use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Traits\GetOrderServiceTrait;

use AftersalesBundle\Services\AftersalesService;

use ThirdPartyBundle\Services\SaasErpCentre\OrderAftersalesService;
use ThirdPartyBundle\Services\SaasErpCentre\OrderRefundService;
use ThirdPartyBundle\Services\SaasErpCentre\OrderService as SaasErpOrderService;
use ThirdPartyBundle\Services\SaasErpCentre\Request as SaasErpRequest;

class Refund extends Controller
{
    use GetOrderServiceTrait;

    /**
     * SaasErp更新退款状态
     * 未发货的取消（没有aftersales_bn）   已发货的仅退款(有aftersales_bn)
     * 1.ERP 接受申请 （商城不进行任何操作）
     * 2.ERP 拒绝申请、接受申请后拒绝 （同商城的不同意）
     * 3.ERP 接受申请后，同意 （同商城的同意）
     */
    public function updateOrderRefund(Request $request)
    {
        $params = $request->all();
        app('log')->debug('Refund_updateOrderRefund_params=>:'.var_export($params, 1));

        $rules = [
            'order_id' => ['required', '订单号缺少'],
            'status' => ['required', '退款状态缺少'],
        ];
        $params['refund_id'] = $params['refund_id'] ?? '';
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $aftersales_bn = '';
        if (isset($params['aftersales_bn']) && $params['aftersales_bn']) {
            $aftersales_bn = $params['aftersales_bn'];
        } elseif (isset($params['memo']) && strpos($params['memo'], '|') !== false) {
            //兼容老的逻辑
            list($aftersales_bn) = explode('|', $params['memo']);
            if (!$aftersales_bn || !is_numeric($aftersales_bn)) {
                $aftersales_bn = '';
            }
        }

        $orderRefundSerrvice = new OrderRefundService();
        // 售后仅退款获取售后单号
        if (!$aftersales_bn) {
            $aftersalesRefundInfo = $orderRefundSerrvice->getAftersalesRefundInfoByRefundBn($params['refund_id']);
            $aftersales_bn = $aftersalesRefundInfo['aftersales_bn'] ?? '';
        }

        $orderAftersalesService = new OrderAftersalesService();

        try {
            // 无售后单 直接全额退款  未发货，取消订单，直接退款
            if (!$aftersales_bn) {
                $aftersalesService = new AftersalesService();

                // 查询退款单
                $refundFilter = [
                    'order_id' => $params['order_id'],
                    'refund_bn' => $params['refund_id'],
                ];
                if ($this->companyId) {
                    $refundFilter['company_id'] = $this->companyId;
                }
                app('log')->debug("\n saaserp 无售后单退款 RefundAction ,".__FUNCTION__.",".__LINE__.",refundFilter=>:".var_export($refundFilter, 1));
                $refundInfo = $aftersalesService->aftersalesRefundRepository->getInfo($refundFilter);

                app('log')->debug("\n saaserp 无售后单退款 RefundAction ,".__FUNCTION__.",".__LINE__.",refundInfo=>:".var_export($refundInfo, 1));
                if (!$refundInfo) {
                    $this->api_response('fail', '退款单详情获取失败');
                }

                // 获取订单信息
                $orderService = $this->getOrderService('normal');
                $normalOrderService = new NormalOrderService();
                $tradeInfo = $normalOrderService->getInfo(['company_id' => $refundInfo['company_id'],'order_id' => $refundInfo['order_id']]);
                app('log')->debug("saaserp 无售后单 tradeInfo=>:".var_export($tradeInfo, 1));
                if (!$tradeInfo) {
                    $this->api_response('fail', '未找到订单');
                }

                // ERP操作 接受申请 时，商城不进行操作
                if ($params['status'] == 'succ') {
                    app('log')->debug("\n saaserp 无售后单退款 RefundAction ,".__FUNCTION__.",".__LINE__.",接受申请操作，无需更新");
                    $this->api_response('true', '操作成功', []);
                }
                //无售后单的情况下同意退款
                $status = [
                    "cancel" => 'refuseRefund',
                    "refund" => 'agreeRefund',
                ];
                $_status = $status[$params['status']] ?? false;
                if (!$_status) {
                    $this->api_response('fail', '无效的操作');
                }

                $result = $orderRefundSerrvice->toRefund($_status, $params, $refundInfo);
                if ($result && $params['status'] == 'refund') {
                    // 去更新 SaasErp 的 退款单
                    $orderRefundStruct = $orderRefundSerrvice->getOrderRefundInfo($params['refund_id'], $tradeInfo['company_id'], $tradeInfo['order_id'], 'normal', null, 'refund', 'SUCC');
                    app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 无售后单 确认退款后，更新退款单回打给erp orderRefundStruct\n".var_export($orderRefundStruct, 1));
                    $request = new SaasErpRequest($tradeInfo['company_id']);
                    $return = $request->call('store.trade.refund.add', $orderRefundStruct);
                    app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 无售后单 确认退款后，更新退款单回打给erp return\n".var_export($return, 1));

                    // 更新订单到ERP
                    app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 无售后单 确认退款后，更新退款单回打给erp");
                    $saasErpOrderService = new SaasErpOrderService();
                    $return = $saasErpOrderService->updateOrderStatus($tradeInfo['company_id'], $tradeInfo['order_id'], $tradeInfo['order_class']);

                    app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 无售后单 确认退款后，回打给erp return\n".var_export($return, 1));
                }
            } else {
                // 有售后单的情况  发货后的仅退款售后申请
                $aftersalesService = new AftersalesService();
                //检测售后单是否存在
                $filter = ['aftersales_bn' => $aftersales_bn];
                if ($this->companyId) {
                    $filter['company_id'] = $this->companyId;
                }
                $afterData = $aftersalesService->aftersalesRepository->get($filter);
                app('log')->debug("\n saaserp 有售后单退款".__FUNCTION__.",".__LINE__.",afterData=>:".var_export($afterData, 1));

                if (!$afterData) {
                    $this->api_response('fail', '售后单不存在');
                }
                // 获取订单信息
                $orderService = $this->getOrderService('normal');
                $normalOrderService = new NormalOrderService();
                $tradeInfo = $normalOrderService->getInfo(['company_id' => $afterData['company_id'],'order_id' => $afterData['order_id']]);
                app('log')->debug("saaserp 有售后单 tradeInfo=>:".var_export($tradeInfo, 1));
                if (!$tradeInfo) {
                    $this->api_response('fail', '未找到订单');
                }

                // ERP操作 接受申请 时，商城不进行操作
                if ($params['status'] == 'succ') {
                    app('log')->debug("\n saaserp 有售后单退款 RefundAction ,".__FUNCTION__.",".__LINE__.",接受申请操作，无需更新");
                    $this->api_response('true', '操作成功', []);
                }
                $status = [
                    "cancel" => '0',
                    "refund" => '1',
                ];
                $_status = $status[$params['status']] ?? false;
                if ($_status === false) {
                    $this->api_response('fail', '无效的操作');
                }
                if ($afterData['aftersales_type'] == 'REFUND_GOODS') {
                    $afterRefundData = [
                        'aftersales_bn' => $afterData['aftersales_bn'],
                        'company_id' => $afterData['company_id'],
                    ];
                    if ($_status == '0') {// 拒绝退款
                        $afterRefundData['refund_memo'] = 'OMS拒绝退款';
                        app('log')->debug("saaserp 有售后，退款退货，拒绝退款,".__FUNCTION__.",".__LINE__.",afterRefundData=>:".var_export($afterRefundData, 1));
                        $result = $aftersalesService->confirmRefund($afterRefundData);
                        app('log')->debug("saaserp 有售后，退款退货，拒绝退款,".__FUNCTION__.",".__LINE__.",result=>:".var_export($result, 1));
                        $this->api_response('true', '操作成功', $result);
                    } else {
                        $afterRefundData['check_refund'] = 1;
                        $afterRefundData['refund_fee'] = (float)$params['money'] * 100;
                        $afterRefundData['refund_memo'] = 'OMS确认退款';
                        app('log')->debug("saaserp 有售后，退款退货，确认退款,".__FUNCTION__.",".__LINE__.",afterRefundData=>:".var_export($afterRefundData, 1));
                        $result = $aftersalesService->confirmRefund($afterRefundData);
                        app('log')->debug("saaserp 有售后，退款退货，确认退款,".__FUNCTION__.",".__LINE__.",result=>:".var_export($result, 1));
                        if ($result) {
                            //退款状态回打OMS
                            // 去更新 SaasErp 的 退款单
                            $refund_data = [
                                'refund_id' => $params['refund_id'],
                                'company_id' => $afterData['company_id'],
                                'order_id' => $afterData['order_id'],
                                'aftersales_bn' => $aftersales_bn,
                            ];
                            $orderRefundStruct = $orderRefundSerrvice->refundSendSaasErp($refund_data);
                            app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后，退款退货，确认退款,更新退款单回打给erp orderRefundStruct\n".var_export($orderRefundStruct, 1));
                            $request = new SaasErpRequest($tradeInfo['company_id']);
                            $return = $request->call('store.trade.refund.add', $orderRefundStruct);
                            app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后，退款退货，确认退款,更新退款单回打给erp return\n".var_export($return, 1));


                            // 更新订单到ERP
                            // 更新订单到ERP
                            // app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后，退款退货，确认退款,更新订单回打给erp");
                            // $saasErpOrderService = new SaasErpOrderService();
                            // $return = $saasErpOrderService->updateOrderStatus($tradeInfo['company_id'], $tradeInfo['order_id'], $tradeInfo['order_class']);

                            // app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后，退款退货，确认退款,更新订单回打给erp return\n".var_export($return, 1));


                            // $saasErpOrderService = new SaasErpOrderService();
                            // $orderStruct = $saasErpOrderService->getOrderStruct($tradeInfo['company_id'], $tradeInfo['order_id'],$tradeInfo['order_class']);

                            // if (!$orderStruct )
                            // {
                            //     app('log')->debug("saaserp ".__FUNCTION__.",".__LINE__.", 售后 确认退款后，回打给erp 获取订单信息失败:companyId:".$tradeInfo['company_id'].",orderId:".$tradeInfo['order_id'].",sourceType:".$tradeInfo['order_class']."\n");
                            //     $this->api_response('fail', '退款操作失败');
                            // }
                            // app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 售后 确认退款后，订单信息回打给erp orderStruct\n".var_export($orderStruct,1));
                            // $request = new SaasErpRequest($tradeInfo['company_id']);
                            // $return = $request->call('store.trade.update', $orderStruct);
                            // app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 售后 确认退款后，回打给erp return\n".var_export($return,1));
                        }

                        $this->api_response('true', '操作成功', $result);
                    }
                } elseif ($afterData['aftersales_type'] == 'ONLY_REFUND') {
                    $reviewData = [
                        'aftersales_bn' => $afterData['aftersales_bn'],
                        'company_id' => $afterData['company_id'],
                        'is_approved' => $_status,
                        'refund_point' => 0,
                    ];
                    if ($_status == '0') {
                        $reviewData['refuse_reason'] = 'OMS拒绝退款';
                    } else {
                        // $reviewData['refund_fee'] = $params['money']>0 ? (float)$params['money']*100 : $refundInfo['refund_fee'];
                        $reviewData['refund_fee'] = (float)$params['money'] * 100;
                        $reviewData['refund_memo'] = 'OMS确认退款';
                    }
                    app('log')->debug("\n saaserp 有售后单退款 仅退款，".__FUNCTION__.",".__LINE__.",reviewData=>:".var_export($reviewData, 1));
                    $result = $aftersalesService->review($reviewData);
                    app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后单退款 仅退款，退款完成，result====>".var_export($result, 1));
                    if ($result && $_status == '1') {
                        // 去更新 SaasErp 的 退款单
                        $orderRefundStruct = $orderRefundSerrvice->getOrderRefundInfo($params['refund_id'], $tradeInfo['company_id'], $tradeInfo['order_id'], 'normal', $aftersales_bn, 'refund', 'SUCC');
                        app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后单退款 仅退款，退款完成，更新退款单回打给erp orderRefundStruct\n".var_export($orderRefundStruct, 1));
                        $request = new SaasErpRequest($tradeInfo['company_id']);
                        $return = $request->call('store.trade.refund.add', $orderRefundStruct);
                        app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后单退款 仅退款，退款完成，更新退款单回打给erp return\n".var_export($return, 1));


                        // 更新订单到ERP
                        app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后单退款 仅退款，退款完成，更新退款单回打给erp");
                        $saasErpOrderService = new SaasErpOrderService();
                        $return = $saasErpOrderService->updateOrderStatus($tradeInfo['company_id'], $tradeInfo['order_id'], $tradeInfo['order_class']);

                        app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后单退款 仅退款，退款完成，回打给erp return\n".var_export($return, 1));


                        // $saasErpOrderService = new SaasErpOrderService();
                        // $orderStruct = $saasErpOrderService->getOrderStruct($tradeInfo['company_id'], $tradeInfo['order_id'],$tradeInfo['order_class']);

                        // if (!$orderStruct )
                        // {
                        //     app('log')->debug("saaserp ".__FUNCTION__.",".__LINE__.", 有售后单退款 仅退款，退款完成，订单信息回打给erp 获取订单信息失败:companyId:".$tradeInfo['company_id'].",orderId:".$tradeInfo['order_id'].",sourceType:".$tradeInfo['order_class']."\n");
                        //     $this->api_response('fail', '退款操作失败');
                        // }
                        // app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后单退款 仅退款，退款完成，订单信息回打给erp orderStruct\n".var_export($orderStruct,1));
                        // $request = new SaasErpRequest($tradeInfo['company_id']);
                        // $return = $request->call('store.trade.update', $orderStruct);
                        // app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.", 有售后单退款 仅退款，退款完成，订单信息回打给er return\n".var_export($return,1));
                    }
                    $this->api_response('true', '操作成功');
                }
            }
        } catch (\Exception $e) {
            $errorMsg = "saaserp Refund_updateOrderRefund Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage()."\n";
            app('log')->debug($errorMsg);
            $this->api_response('fail', $e->getMessage());
        }

        $this->api_response('true', '操作成功', $result);
    }
}
