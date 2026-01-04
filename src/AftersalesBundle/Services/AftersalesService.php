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

namespace AftersalesBundle\Services;

use AftersalesBundle\Repositories\AftersalesRepository;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Services\DistributorService;
use MembersBundle\Services\MemberService;
use PaymentBundle\Services\Payments\AdaPaymentService;
use PaymentBundle\Services\Payments\BsPayService;
use Dingo\Api\Exception\ResourceException;

use AftersalesBundle\Entities\Aftersales;
use AftersalesBundle\Entities\AftersalesDetail;
use AftersalesBundle\Entities\AftersalesRefund;

use MembersBundle\Entities\MembersDeleteRecord;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Repositories\NormalOrdersItemsRepository;
use OrdersBundle\Repositories\NormalOrdersRepository;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Services\OrderProfitService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Traits\OrderSettingTrait;
use PopularizeBundle\Services\BrokerageService;

use DistributionBundle\Services\DistributorAftersalesAddressService;

use SalespersonBundle\Services\SalespersonService;
use SupplierBundle\Services\SupplierService;
use SystemLinkBundle\Services\JushuitanSettingService;
use SystemLinkBundle\Events\TradeAftersalesEvent;
use SystemLinkBundle\Events\TradeRefundEvent;
use SystemLinkBundle\Events\TradeAftersalesCancelEvent;
use SystemLinkBundle\Events\TradeAftersalesLogiEvent;
use SystemLinkBundle\Events\Jushuitan\TradeAftersalesEvent as JushuitanTradeAftersalesEvent;
use SystemLinkBundle\Events\WdtErp\TradeAfterSaleEvent as WdtErpTradeAfterSaleEvent;

use ThirdPartyBundle\Events\TradeAftersalesEvent as SaasErpAftersalesEvent;
use ThirdPartyBundle\Events\TradeAftersalesRefuseEvent;
use ThirdPartyBundle\Events\TradeRefundEvent as SaasErpRefundEvent;
use ThirdPartyBundle\Events\TradeAftersalesCancelEvent as SaasErpAftersalesCancelEvent;
use ThirdPartyBundle\Events\TradeAftersalesLogiEvent as SaasErpAftersalesLogiEvent;
use ThirdPartyBundle\Events\TradeAftersalesUpdateEvent as SaasErpAftersalesUpdateEvent;
use AftersalesBundle\Jobs\AftersalesSuccessSendMsg;
use OrdersBundle\Events\OrderProcessLogEvent;
use AftersalesBundle\Jobs\OrderRefundCompleteJob;
use WorkWechatBundle\Jobs\sendAfterSaleCancelNoticeJob;
use WorkWechatBundle\Jobs\sendAfterSaleWaitConfirmNoticeJob;
use WorkWechatBundle\Jobs\sendAfterSaleWaitDealNoticeJob;
use CompanysBundle\Services\OperatorsService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Entities\Items;
use CompanysBundle\Entities\Operators;

class AftersalesService
{
    use GetOrderServiceTrait;
    use OrderSettingTrait;

    /** @var AftersalesRepository $aftersalesRepository */
    public $aftersalesRepository;
    public $aftersalesDetailRepository;
    /** @var \AftersalesBundle\Repositories\AftersalesRefundRepository */
    public $aftersalesRefundRepository;
    public $orderService;
    public $membersDeleteRecordRepository;
    /** @var NormalOrdersItemsRepository $orderItemsRepository */
    public $orderItemsRepository;

    public function __construct()
    {
        $this->aftersalesRepository = app('registry')->getManager('default')->getRepository(Aftersales::class);
        $this->aftersalesDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class);
        $this->aftersalesRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
        $this->membersDeleteRecordRepository = app('registry')->getManager('default')->getRepository(MembersDeleteRecord::class);
        $this->orderItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
    }


    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->aftersalesRepository->$method(...$parameters);
    }

    private function getOrderData($companyId, $orderId)
    {
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $orderId);
        if (!$order) {
            throw new ResourceException('此订单不存在！');
        }
        $this->orderService = $this->getOrderServiceByOrderInfo($order);
    }

    /**
     * 获取售后单号
     *
     * $userId
     */
    private function __genAftersalesBn()
    {
        $sign = date("Ymd");
        $randval = substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 7);
        return $sign . $randval;
    }

    /**
     * 消费者提交售后申请
     *
     * @param array $data 创建售后申请提交的参数
     */
    public function create($data)
    {
        unset($data['aftersales_bn']);
        // 检查是否可以申请售后
        $this->__checkApply($data);
        $filter = [
            'company_id' => $data['company_id'],
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
        ];
        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->getSimpleOrderInfo($filter);

        $tradeService = new TradeService();
        $trade = $tradeService->getInfo(['company_id' => $data['company_id'], 'order_id' => $orderInfo['order_id'], 'trade_state' => 'SUCCESS']);

        if (!isset($data['contact']) || !$data['contact']) {
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo(['company_id' => $data['company_id'], 'user_id' => $data['user_id']]);
            $data['contact'] = $memberInfo['username'];
        }

        //获取当前申请售后商品的供应商信息
        $_filter = [
            'company_id' => $data['company_id'],
            'order_id' => $data['order_id'],
            'id' => array_column($data['detail'], 'id'),
        ];
        $orderItems = $normalOrderService->normalOrdersItemsRepository->getList($_filter);
        if (!$orderItems['list']) throw new ResourceException('查询订单明细错误');

        //把售后商品按供应商区分
        $supplierInfo = array_column($orderItems['list'], 'supplier_id', 'id');
        $aftersaleItems = [];
        foreach ($data['detail'] as $v) {
            $v['supplier_id'] = $supplierInfo[$v['id']] ?? 0;
            $aftersaleItems[$v['supplier_id']][] = $v;
        }

        foreach($aftersaleItems as $supplierId => $v) {
            $data['detail'] = $v;//单个供应商对应的售后商品数组
            $aftersales = $this->__createAftersales($orderInfo, $trade, $data);
        }
        return $aftersales;
    }

    private function __createAftersales($orderInfo, $trade, $data)
    {
        $aftersales_bn = $this->__genAftersalesBn();
        $freight = floatval($data['freight'] ?? 0); // 自填运费
        $original_freight = $freight; // 保存原始运费值，用于判断是否退运费
        $aftersales_data = [
            'aftersales_bn' => $aftersales_bn,
            'shop_id' => $orderInfo['shop_id'],
            'order_id' => $data['order_id'],
            // 'original_order_id' => $orderInfo['original_order_id'],
            'company_id' => $data['company_id'],
            'supplier_id' => $data['supplier_id'] ?? 0,
            'user_id' => $data['user_id'],
            'distributor_id' => $orderInfo['distributor_id'],
            'aftersales_type' => $data['aftersales_type'],
            'aftersales_status' => 0,
            'progress' => 0,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? '',
            'evidence_pic' => $data['evidence_pic'] ?? [],
            'salesman_id' => $data['salesman_id'] ?? 0,
            'contact' => $data['contact'] ?? '',
            'mobile' => $data['mobile'] ?? ($orderInfo['mobile'] ?? ''),
            'merchant_id' => $orderInfo['merchant_id'] ?? 0,
            'self_delivery_operator_id' => $orderInfo['self_delivery_operator_id'] ?? 0,
            'is_partial_cancel' => $data['is_partial_cancel'] ?? 0,
            'return_type' => $data['return_type'] ?? 'logistics',
            'freight' => $freight,
        ];
        if (isset($data['return_type']) && $data['return_type'] == 'offline') {
            $distributorService = new DistributorService();
            $distributorId = $orderInfo['distributor_id'];
            $selfDistributorId = 0;
            if (!$distributorId) {
                $distributor = $distributorService->getInfoSimple(['company_id' => $data['company_id'], 'distributor_self' => 1]);
                if (!$distributor) {
                    throw new ResourceException('该订单不支持到店退货');
                }
                $distributorId = $distributor['distributor_id'];
                $selfDistributorId = $distributorId;
            }

            $distributor = $distributorService->getInfoSimple(['company_id' => $data['company_id'], 'distributor_id' => $distributorId]);
            if (!$distributor['offline_aftersales']) {
                throw new ResourceException('该订单不支持到店退货');
            }

            $adFilter = [
                'company_id' => $data['company_id'],
                'return_type' => 'offline',
                'address_id' => $data['aftersales_address_id'],
            ];
            $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
            $address = $distributorAftersalesAddressService->getInfo($adFilter);
            if (!$address) {
                throw new ResourceException('请选择正确的退货门店');
            }

            $dFilter = [
                'company_id' => $data['company_id'],
                'distributor_id' => $address['distributor_id'],
                'is_valid' => 'true',
            ];
            if ($address['distributor_id'] == $distributorId) {
                $dFilter['offline_aftersales_self'] = 1;
            } else {
                if (!in_array($address['distributor_id'], $distributor['offline_aftersales_distributor_id'])) {
                    throw new ResourceException('请选择正确的退货门店');
                }
                $dFilter['offline_aftersales_other'] = 1;
            }
            $returnDistributor = $distributorService->getInfoSimple($dFilter);
            if (!$returnDistributor) {
                throw new ResourceException('请选择正确的退货门店');
            }
            $aftersales_data['return_distributor_id'] = $returnDistributor['distributor_id'] == $selfDistributorId ? 0 : $returnDistributor['distributor_id'];
            $aftersales_data['aftersales_address'] = [
                'aftersales_address_id' => $address['address_id'],
                'aftersales_contact' => $address['contact'],
                'aftersales_mobile' => $address['mobile'],
                'aftersales_address' => $address['province'] . $address['city'] . $address['area'] . $address['address'],
                'aftersales_name' => $address['name'],
                'aftersales_hours' => $address['hours'],
            ];
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 创建售后单
            $normalOrderService = new NormalOrderService();
            $total_refund_fee = 0;
            $total_refund_point = 0;
            $total_return_point = 0;
            $apply_num = 0;
            foreach ($data['detail'] as $v) {
                $suborder_filter = [
                    'company_id' => $data['company_id'],
                    'user_id' => $data['user_id'],
                    'order_id' => $data['order_id'],
                    'id' => $v['id'],
                ];
                $subOrderInfo = $normalOrderService->getSimpleSubOrderInfo($suborder_filter);
                $applied_num = $this->getAppliedNum($data['company_id'], $data['order_id'], $v['id']); // 已申请数量
                $applied_refund_fee = $this->getAppliedTotalRefundFee($data['company_id'], $data['order_id'], $v['id']); // 已申请退款总金额
                $applied_refund_point = $this->getAppliedRefundPoint($data['company_id'], $data['order_id'], $v['id']); // 已申请退款总积分
                if ($v['num'] == $subOrderInfo['num']) { // 子订单 全部 退货
                    $refund_fee = $subOrderInfo['total_fee'];
                    $refund_point = $subOrderInfo['point'];
                } else { // 子订单 部分 退货
                    $left_num = $subOrderInfo['num'] - $applied_num - $v['num'];
                    if ($left_num == 0) { // 申请的是本明细剩余的所有数量
                        $refund_fee = $subOrderInfo['total_fee'] - $applied_refund_fee;
                        $refund_point = $subOrderInfo['point'] - $applied_refund_point;
                    } elseif ($left_num > 0) { // 还有没申请的商品的时候  通过除法来计算退款金额，向下取整
                        $refund_fee = floor(bcmul(bcdiv($subOrderInfo['total_fee'], $subOrderInfo['num'], 2), $v['num']));
                        $refund_point = floor(bcmul(bcdiv($subOrderInfo['point'], $subOrderInfo['num'], 2), $v['num']));
                    } else {
                        throw new ResourceException('申请售后单数据异常');
                    }
                }

                $total_return_point += $this->getReturnPoint($subOrderInfo, $v['num'], $applied_num);
                $total_refund_fee += $refund_fee;
                $total_refund_point += $refund_point;
                $aftersales_detail_data = [
                    'company_id' => $data['company_id'],
                    'user_id' => $data['user_id'],
                    'distributor_id' => $orderInfo['distributor_id'],
                    'aftersales_bn' => $aftersales_bn,
                    'order_id' => $data['order_id'],
                    'sub_order_id' => $v['id'],
                    'goods_id' => $subOrderInfo['goods_id'],
                    'item_id' => $subOrderInfo['item_id'],
                    'item_bn' => $subOrderInfo['item_bn'],
                    'item_pic' => $subOrderInfo['pic'],
                    'refund_fee' => $refund_fee,
                    'refund_point' => $refund_point,
                    'item_name' => $subOrderInfo['item_name'],
                    'order_item_type' => $subOrderInfo['order_item_type'],
                    'num' => $v['num'],
                    'aftersales_type' => $data['aftersales_type'],
                    'progress' => 0,
                    'aftersales_status' => 0,
                ];
                if ($subOrderInfo['item_spec_desc']) {
                    $aftersales_detail_data['item_name'] = $subOrderInfo['item_name'] . '(' . $subOrderInfo['item_spec_desc'] . ')';
                }
                // 创建售后明细
                $aftersales_detail = $this->aftersalesDetailRepository->create($aftersales_detail_data);
                $apply_num += $v['num'];
                $orderProfitService = new OrderProfitService();
                $orderProfitService->orderItemsProfitRepository->updateBy(['order_id' => $data['order_id'], 'company_id' => $data['company_id'], 'item_id' => $aftersales_detail_data['item_id']], ['order_profit_status' => 0]);

                //一次只能申请一个商品，把子订单的供应商ID保存在售后主表
                $aftersales_data['supplier_id'] = $subOrderInfo['supplier_id'] ?? 0;
                $aftersales_data['item_bn'] = $subOrderInfo['item_bn'];
            }

            if (isset($data['refund_fee']) && $data['refund_fee']) {
                if ($data['refund_fee'] > $total_refund_fee) {
                    throw new ResourceException('退款金额不能超过可退金额'.$data['refund_fee'].' > '.$total_refund_fee);
                }
                $aftersales_data['refund_fee'] = $data['refund_fee'];
            } else {
                $aftersales_data['refund_fee'] = $total_refund_fee;
            }

            if (isset($data['refund_point']) && $data['refund_point']) {
                if ($data['refund_point'] > $total_refund_point) {
                    throw new ResourceException('退还积分不能超过可退积分');
                }
                $aftersales_data['refund_point'] = $data['refund_point'];
            } else {
                $aftersales_data['refund_point'] = $total_refund_point;
            }

            // 加入自填运费 (子订单总金额是不包含运费的)
            // freight 根据 freight_type 存储现金（分）或积分值，与订单表逻辑一致
            $aftersales_data['freight'] = $freight;
            // 设置运费类型
            $aftersales_data['freight_type'] = $orderInfo['freight_type'] ?? 'cash';

            // 创建售后主单据
            app('log')->info('aftersales::__createAftersales::orderInfo===>'.json_encode($orderInfo,JSON_UNESCAPED_UNICODE));
            app('log')->info('aftersales::__createAftersales::aftersales_data===>'.json_encode($aftersales_data,JSON_UNESCAPED_UNICODE));
            $aftersales = $this->aftersalesRepository->create($aftersales_data);
            if (!$aftersales_data['is_partial_cancel']) {
                $left_aftersales_num = $orderInfo['left_aftersales_num'] - $apply_num;
                if ($left_aftersales_num < 0) {
                    throw new ResourceException('超出可申请数量');
                }
                $normalOrderService->normalOrdersRepository->update(['company_id' => $data['company_id'], 'order_id' => $data['order_id']], ['left_aftersales_num' => $left_aftersales_num]);
            }

            // 创建售后退款单
            $aftersalesRefundService = new AftersalesRefundService();
            $refundData = [
                'company_id' => $aftersales_data['company_id'],
                'supplier_id' => $aftersales_data['supplier_id'],
                'user_id' => $aftersales_data['user_id'],
                'aftersales_bn' => $aftersales_data['aftersales_bn'],
                'order_id' => $aftersales_data['order_id'],
                'trade_id' => $trade['trade_id'], // 已支付交易单号
                'shop_id' => $aftersales_data['shop_id'] ?? 0,
                'distributor_id' => $aftersales_data['distributor_id'] ?? 0,
                'refund_type' => 0, // 0 售后申请退款
                'refund_channel' => $trade['pay_type'] == 'offline_pay' ? 'offline' : 'original', // 默认取消订单原路返回,pay_type=offline_pay为线下退款
                'refund_fee' => $total_refund_fee,
                'refund_point' => $total_refund_point,
                'return_freight' => $original_freight ? 1 : 0, // 0 不退运费（使用原始运费值判断）
                'pay_type' => $orderInfo['pay_type'],
                'currency' => ($trade['pay_type'] == 'point') ? '' : $trade['fee_type'],
                'cur_fee_type' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_type'],
                'cur_fee_rate' => $trade['cur_fee_rate'],
                'cur_fee_symbol' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_symbol'],
                'cur_pay_fee' => ($trade['pay_type'] == 'point') ? ($total_refund_point * $trade['cur_fee_rate']) : ($total_refund_fee * $trade['cur_fee_rate']), // trade表没有单独积分字段，所以这样写
                'return_point' => $total_return_point,
                'merchant_id' => $orderInfo['merchant_id'] ?? 0,
                'freight' => $freight, // 根据freight_type存储现金（分）或积分值
                'freight_type' => $aftersales_data['freight_type'] ?? $orderInfo['freight_type'] ?? 'cash',
            ];
            $refund = $aftersalesRefundService->createAftersalesRefund($refundData);

            // if ($orderInfo['order_status'] != 'DONE') {
            // $normalOrderService->confirmReceipt($filter);
            // }
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $data['company_id'],
                'supplier_id' => $data['supplier_id'] ?? 0,
                'operator_type' => $data['operator_type'] ?? 'user',
                'operator_id' => ($data['operator_type'] ?? 'user') == 'user' ? $data['user_id'] : ($data['operator_id'] ?? 0),
                'remarks' => '订单售后',
                'detail' => '售后单号：' . $aftersales_bn . ' 申请售后，申请原因：' . $data['reason'],
                'params' => $data,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        $date = date('Ymd');
        $redisKey = 'OrderPayStatistics:normal:' . $data['company_id'] . ':' . $date;
        app('redis')->hincrby($redisKey, 'orderAftersales', 1);
        if (isset($orderInfo['distributor_id'])) {
            app('redis')->hincrby($redisKey, $orderInfo['distributor_id'] . '_orderAftersales', 1);
        }
        if (!empty($orderInfo['merchant_id'])) {
            app('redis')->hincrby($redisKey, $orderInfo['merchant_id'] . '_merchant_orderAftersales', 1);
        }

        //联通OME售后申请埋点
        if ($data['aftersales_type'] == 'REFUND_GOODS' || $data['aftersales_type'] == 'EXCHANGING_GOODS') {
            event(new TradeAftersalesEvent($aftersales)); // 退款退货 或换货
            event(new SaasErpAftersalesEvent($aftersales)); // SaasErp 售后申请 退款退货
        } else {
            event(new TradeRefundEvent($refund)); // 售后仅退款
            event(new SaasErpRefundEvent($aftersales));// SaasErp 售后申请 仅退款
        }
        //售后申请推聚水潭
        event(new JushuitanTradeAftersalesEvent($aftersales));
        //售后申请推旺店通
        event(new WdtErpTradeAfterSaleEvent($aftersales));
        $gotoJob = (new sendAfterSaleWaitDealNoticeJob($aftersales_data['company_id'], $aftersales_data['aftersales_bn']))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        return $aftersales;
    }

    // 售后拆单单个
    public function createByNum($data)
    {
        unset($data['aftersales_bn']);
        // 检查是否可以申请售后
        $this->__checkApply($data);
        $filter = [
            'company_id' => $data['company_id'],
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
        ];
        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->getSimpleOrderInfo($filter);

        $tradeService = new TradeService();
        $trade = $tradeService->getInfo(['company_id' => $data['company_id'], 'order_id' => $orderInfo['order_id'], 'trade_state' => 'SUCCESS']);

        if (!isset($data['contact']) || !$data['contact']) {
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo(['company_id' => $data['company_id'], 'user_id' => $data['user_id']]);
            $data['contact'] = $memberInfo['username'];
        }

        //获取当前申请售后商品的供应商信息
        $_filter = [
            'company_id' => $data['company_id'],
            'order_id' => $data['order_id'],
            'id' => array_column($data['detail'], 'id'),
        ];
        $orderItems = $normalOrderService->normalOrdersItemsRepository->getList($_filter);
        if (!$orderItems['list']) throw new ResourceException('查询订单明细错误');

        // //把售后商品按供应商区分
        // $supplierInfo = array_column($orderItems['list'], 'supplier_id', 'id');
        // $aftersaleItems = [];
        // foreach ($data['detail'] as $v) {
        //     $v['supplier_id'] = $supplierInfo[$v['id']] ?? 0;
        //     $aftersaleItems[$v['supplier_id']][] = $v;
        // }

        // foreach($aftersaleItems as $supplierId => $v) {
        //     $data['detail'] = $v;//单个供应商对应的售后商品数组
        //     $aftersales = $this->__createAftersales($orderInfo, $trade, $data);
        // }

        foreach ($data['detail'] as $v) {
            $detailTmp = $v;
            for ($i=0; $i < $v['num']; $i++) { 
                // 如果输入了退款子订单总金额，则平均分配
                if (isset($detailTmp['total_fee']) && $detailTmp['total_fee'] > 0) {
                    $detailTmp['total_fee'] = floor(bcdiv(bcmul($detailTmp['total_fee'],100), $detailTmp['num'], 2));
                }
                if (isset($detailTmp['total_point']) && $detailTmp['total_point'] > 0) {
                    $detailTmp['total_point'] = floor(bcdiv(bcmul($detailTmp['total_point'],100), $detailTmp['num'], 2));   
                }
                $detail = $detailTmp;
                $detail['num'] = 1;
                $data['detail'] = [$detail];
                $this->__createAftersalesByNum($orderInfo, $trade, $data);
            }
        }

        return true;
    }

    // 售后拆单数据按num单个拆分
    private function __createAftersalesByNum($orderInfo, $trade, $data)
    {
         // 是否开启退货退款退运费
        $is_refund_freight = $this->getOrdersSetting($data['company_id'], 'is_refund_freight');
        $aftersales_bn = $this->__genAftersalesBn();
        $aftersales_data = [
            'aftersales_bn' => $aftersales_bn,
            'shop_id' => $orderInfo['shop_id'],
            'order_id' => $data['order_id'],
            // 'original_order_id' => $orderInfo['original_order_id'],
            'company_id' => $data['company_id'],
            'supplier_id' => $data['supplier_id'] ?? 0,
            'user_id' => $data['user_id'],
            'distributor_id' => $orderInfo['distributor_id'],
            'aftersales_type' => $data['aftersales_type'],
            'aftersales_status' => 0,
            'progress' => 0,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? '',
            'evidence_pic' => $data['evidence_pic'] ?? [],
            'salesman_id' => $data['salesman_id'] ?? 0,
            'contact' => $data['contact'] ?? '',
            'mobile' => $data['mobile'] ?? ($orderInfo['mobile'] ?? ''),
            'merchant_id' => $orderInfo['merchant_id'] ?? 0,
            'self_delivery_operator_id' => $orderInfo['self_delivery_operator_id'] ?? 0,
            'is_partial_cancel' => $data['is_partial_cancel'] ?? 0,
            'return_type' => $data['return_type'] ?? 'logistics',
            'freight' => 0,
        ];
        if (isset($data['return_type']) && $data['return_type'] == 'offline') {
            $distributorService = new DistributorService();
            $distributorId = $orderInfo['distributor_id'];
            $selfDistributorId = 0;
            if (!$distributorId) {
                $distributor = $distributorService->getInfoSimple(['company_id' => $data['company_id'], 'distributor_self' => 1]);
                if (!$distributor) {
                    throw new ResourceException('该订单不支持到店退货');
                }
                $distributorId = $distributor['distributor_id'];
                $selfDistributorId = $distributorId;
            }

            $distributor = $distributorService->getInfoSimple(['company_id' => $data['company_id'], 'distributor_id' => $distributorId]);
            if (!$distributor['offline_aftersales']) {
                throw new ResourceException('该订单不支持到店退货');
            }

            $adFilter = [
                'company_id' => $data['company_id'],
                'return_type' => 'offline',
                'address_id' => $data['aftersales_address_id'],
            ];
            $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
            $address = $distributorAftersalesAddressService->getInfo($adFilter);
            if (!$address) {
                throw new ResourceException('请选择正确的退货门店');
            }

            $dFilter = [
                'company_id' => $data['company_id'],
                'distributor_id' => $address['distributor_id'],
                'is_valid' => 'true',
            ];
            if ($address['distributor_id'] == $distributorId) {
                $dFilter['offline_aftersales_self'] = 1;
            } else {
                if (!in_array($address['distributor_id'], $distributor['offline_aftersales_distributor_id'])) {
                    throw new ResourceException('请选择正确的退货门店');
                }
                $dFilter['offline_aftersales_other'] = 1;
            }
            $returnDistributor = $distributorService->getInfoSimple($dFilter);
            if (!$returnDistributor) {
                throw new ResourceException('请选择正确的退货门店');
            }
            $aftersales_data['return_distributor_id'] = $returnDistributor['distributor_id'] == $selfDistributorId ? 0 : $returnDistributor['distributor_id'];
            $aftersales_data['aftersales_address'] = [
                'aftersales_address_id' => $address['address_id'],
                'aftersales_contact' => $address['contact'],
                'aftersales_mobile' => $address['mobile'],
                'aftersales_address' => $address['province'] . $address['city'] . $address['area'] . $address['address'],
                'aftersales_name' => $address['name'],
                'aftersales_hours' => $address['hours'],
            ];
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 创建售后单
            $normalOrderService = new NormalOrderService();
            $total_refund_fee = 0;
            $total_refund_point = 0;
            $total_return_point = 0;
            $apply_num = 0;
            $sub_item_fee = 0; // 本次申请子订单单个金额
            $sub_item_point = 0; // 本次申请子订单单个积分
            $is_refund_freight_flag = false; //单个，如果以后多个则为数组hswzudiyur，ad// 子订单是否已退款运费
            foreach ($data['detail'] as $v) {
                $suborder_filter = [
                    'company_id' => $data['company_id'],
                    'user_id' => $data['user_id'],
                    'order_id' => $data['order_id'],
                    'id' => $v['id'],
                ];
                $subOrderInfo = $normalOrderService->getSimpleSubOrderInfo($suborder_filter);
                // 如果输入了总金额，以输入的为准
                if (isset($v['total_fee']) && $v['total_fee'] > 0) {
                    $sub_item_fee = $v['total_fee'];
                }else{
                    $sub_item_fee = floor(bcmul(bcdiv($subOrderInfo['total_fee'], $subOrderInfo['num'], 2), $v['num']));
                }
                 if (isset($v['total_point']) && $v['total_point'] > 0) {
                    $sub_item_point = $v['total_point'];
                }else{
                    $sub_item_point = floor(bcmul(bcdiv($subOrderInfo['point'], $subOrderInfo['num'], 2), $v['num']));
                }
                $applied_num = $this->getAppliedNum($data['company_id'], $data['order_id'], $v['id']); // 已申请数量
                $applied_refund_fee = $this->getAppliedTotalRefundFee($data['company_id'], $data['order_id'], $v['id']); // 已申请退款总金额
                $applied_refund_point = $this->getAppliedRefundPoint($data['company_id'], $data['order_id'], $v['id']); // 已申请退款总积分
                if ($v['num'] == $subOrderInfo['num']) { // 子订单 全部 退货
                    $refund_fee = $subOrderInfo['total_fee'];
                    $refund_point = $subOrderInfo['point'];
                } else { // 子订单 部分 退货
                    $left_num = $subOrderInfo['num'] - $applied_num - $v['num'];
                    if ($left_num == 0) { // 申请的是本明细剩余的所有数量
                        $refund_fee = $subOrderInfo['total_fee'] - $applied_refund_fee;
                        $sub_item_fee = $refund_fee;
                        $refund_point = $subOrderInfo['point'] - $applied_refund_point;
                        $sub_item_point = $refund_point;
                    } elseif ($left_num > 0) { // 还有没申请的商品的时候  通过除法来计算退款金额，向下取整
                        $refund_fee = floor(bcmul(bcdiv($subOrderInfo['total_fee'], $subOrderInfo['num'], 2), $v['num']));
                        $refund_point = floor(bcmul(bcdiv($subOrderInfo['point'], $subOrderInfo['num'], 2), $v['num']));
                    } else {
                        throw new ResourceException('申请售后单数据异常');
                    }
                }

                $total_return_point += $this->getReturnPoint($subOrderInfo, $v['num'], $applied_num);
                $total_refund_fee += $refund_fee;
                $total_refund_point += $refund_point;
                $aftersales_detail_data = [
                    'company_id' => $data['company_id'],
                    'user_id' => $data['user_id'],
                    'distributor_id' => $orderInfo['distributor_id'],
                    'aftersales_bn' => $aftersales_bn,
                    'order_id' => $data['order_id'],
                    'sub_order_id' => $v['id'],
                    'goods_id' => $subOrderInfo['goods_id'],
                    'item_id' => $subOrderInfo['item_id'],
                    'item_bn' => $subOrderInfo['item_bn'],
                    'item_pic' => $subOrderInfo['pic'],
                    'refund_fee' => $sub_item_fee,
                    'refund_point' => $sub_item_point,
                    'item_name' => $subOrderInfo['item_name'],
                    'order_item_type' => $subOrderInfo['order_item_type'],
                    'num' => $v['num'],
                    'aftersales_type' => $data['aftersales_type'],
                    'progress' => 0,
                    'aftersales_status' => 0,
                ];
                if ($subOrderInfo['item_spec_desc']) {
                    $aftersales_detail_data['item_name'] = $subOrderInfo['item_name'] . '(' . $subOrderInfo['item_spec_desc'] . ')';
                }
                // 创建售后明细
                $aftersales_detail = $this->aftersalesDetailRepository->create($aftersales_detail_data);
                $apply_num += $v['num'];
                $orderProfitService = new OrderProfitService();
                $orderProfitService->orderItemsProfitRepository->updateBy(['order_id' => $data['order_id'], 'company_id' => $data['company_id'], 'item_id' => $aftersales_detail_data['item_id']], ['order_profit_status' => 0]);

                //一次只能申请一个商品，把子订单的供应商ID保存在售后主表
                $aftersales_data['supplier_id'] = $subOrderInfo['supplier_id'] ?? 0;
                $aftersales_data['item_bn'] = $subOrderInfo['item_bn'];

                $is_refund_freight_flag = $this->isRefundFinishByNum($data['order_id'], $data['company_id'], $v['id'], $v['num']);
            }

             if ($sub_item_fee > $total_refund_fee) {
                throw new ResourceException('售后申请金额不能超过剩余金额! '.$sub_item_fee.' > '.$total_refund_fee);
            }
            if ( $sub_item_point > $total_refund_point) {
                throw new ResourceException('售后申请积分不能超过剩余积分! '.$sub_item_point.' > '.$total_refund_point);
            }
            $aftersales_data['refund_fee'] = $sub_item_fee;
            $aftersales_data['refund_point'] = $sub_item_point;
            // 判断是否是最后一个售后数据了
            $freight = 0;
            $original_freight = 0; // 保存原始运费值，用于判断是否退运费
            if ($is_refund_freight && $is_refund_freight_flag) {
                // 根据运费类型处理运费（与订单表逻辑一致：freight_fee根据freight_type存储现金或积分值）
                if ($orderInfo['freight_type'] == 'cash') {
                    // 现金运费（分）
                    $max_freight = $orderInfo['freight_fee'];
                    $refunded_freight = $this->getAppliedTotalRefundFreight($data['company_id'], $data['order_id']);
                    $remain_freight = $max_freight - $refunded_freight;
                    $max_freight = min($max_freight, $remain_freight);
                    $freight = isset($data['freight']) && $data['freight'] > 0 && $data['freight'] <= $max_freight ? $data['freight'] : $max_freight;
                    $original_freight = $freight; // 保存原始运费值
                } else if ($orderInfo['freight_type'] == 'point') {
                    // 积分运费（积分值，订单表的freight_fee存储的是积分值）
                    $max_freight = $orderInfo['freight_fee'];
                    $refunded_freight = $this->getAppliedTotalRefundFreightPoint($data['company_id'], $data['order_id']);
                    $remain_freight = $max_freight - $refunded_freight;
                    $max_freight = min($max_freight, $remain_freight);
                    $freight = isset($data['freight']) && $data['freight'] > 0 && $data['freight'] <= $max_freight ? $data['freight'] : $max_freight;
                    $original_freight = $freight; // 保存原始运费值
                }
                // 统一使用 freight 字段存储（根据 freight_type 决定是现金还是积分值）
                $aftersales_data['freight'] = $freight;
                // 设置运费类型
                $aftersales_data['freight_type'] = $orderInfo['freight_type'];
            } else {
                // 未申请运费时，设置默认值
                $aftersales_data['freight_type'] = $orderInfo['freight_type'] ?? 'cash';
                $aftersales_data['freight'] = 0;
            }
            // 创建售后主单据
            $aftersales = $this->aftersalesRepository->create($aftersales_data);
            if (!$aftersales_data['is_partial_cancel']) {
                $left_aftersales_num = $orderInfo['left_aftersales_num'] - $apply_num;
                if ($left_aftersales_num < 0) {
                    throw new ResourceException('超出可申请数量');
                }
                $normalOrderService->normalOrdersRepository->update(['company_id' => $data['company_id'], 'order_id' => $data['order_id']], ['left_aftersales_num' => $left_aftersales_num]);
            }
            // 创建售后退款单
            $aftersalesRefundService = new AftersalesRefundService();
            $refundData = [
                'company_id' => $aftersales_data['company_id'],
                'supplier_id' => $aftersales_data['supplier_id'],
                'user_id' => $aftersales_data['user_id'],
                'aftersales_bn' => $aftersales_data['aftersales_bn'],
                'order_id' => $aftersales_data['order_id'],
                'trade_id' => $trade['trade_id'], // 已支付交易单号
                'shop_id' => $aftersales_data['shop_id'] ?? 0,
                'distributor_id' => $aftersales_data['distributor_id'] ?? 0,
                'refund_type' => 0, // 0 售后申请退款
                'refund_channel' => $trade['pay_type'] == 'offline_pay' ? 'offline' : 'original', // 默认取消订单原路返回,pay_type=offline_pay为线下退款
                'refund_fee' => $sub_item_fee,
                'refund_point' => $aftersales_data['refund_point'], // refund_point 不包含运费积分，运费积分单独存储在 freight 中
                'return_freight' => $original_freight ? 1 : 0, // 使用原始运费值判断是否退运费
                'pay_type' => $orderInfo['pay_type'],
                'currency' => ($trade['pay_type'] == 'point') ? '' : $trade['fee_type'],
                'cur_fee_type' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_type'],
                'cur_fee_rate' => $trade['cur_fee_rate'],
                'cur_fee_symbol' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_symbol'],
                'cur_pay_fee' => ($trade['pay_type'] == 'point') ? ($aftersales_data['refund_point'] * $trade['cur_fee_rate']) : ($sub_item_fee * $trade['cur_fee_rate']), // trade表没有单独积分字段，所以这样写
                'return_point' => $total_return_point,
                'merchant_id' => $orderInfo['merchant_id'] ?? 0,
                'freight' => $freight, // 根据freight_type存储现金（分）或积分值
                'freight_type' => $aftersales_data['freight_type'] ?? $orderInfo['freight_type'] ?? 'cash',
            ];
            $refund = $aftersalesRefundService->createAftersalesRefund($refundData);
            
            // if ($orderInfo['order_status'] != 'DONE') {
            // $normalOrderService->confirmReceipt($filter);
            // }
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $data['company_id'],
                'supplier_id' => $data['supplier_id'] ?? 0,
                'operator_type' => $data['operator_type'] ?? 'user',
                'operator_id' => ($data['operator_type'] ?? 'user') == 'user' ? $data['user_id'] : ($data['operator_id'] ?? 0),
                'remarks' => '订单售后',
                'detail' => '售后单号：' . $aftersales_bn . ' 申请售后，申请原因：' . $data['reason'],
                'params' => $data,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        $date = date('Ymd');
        $redisKey = 'OrderPayStatistics:normal:' . $data['company_id'] . ':' . $date;
        app('redis')->hincrby($redisKey, 'orderAftersales', 1);
        if (isset($orderInfo['distributor_id'])) {
            app('redis')->hincrby($redisKey, $orderInfo['distributor_id'] . '_orderAftersales', 1);
        }
        if (!empty($orderInfo['merchant_id'])) {
            app('redis')->hincrby($redisKey, $orderInfo['merchant_id'] . '_merchant_orderAftersales', 1);
        }

        //联通OME售后申请埋点
        if ($data['aftersales_type'] == 'REFUND_GOODS' || $data['aftersales_type'] == 'EXCHANGING_GOODS') {
            event(new TradeAftersalesEvent($aftersales)); // 退款退货 或换货
            event(new SaasErpAftersalesEvent($aftersales)); // SaasErp 售后申请 退款退货
        } else {
            event(new TradeRefundEvent($refund)); // 售后仅退款
            event(new SaasErpRefundEvent($aftersales));// SaasErp 售后申请 仅退款
        }
        //售后申请推聚水潭
        event(new JushuitanTradeAftersalesEvent($aftersales));
        //售后申请推旺店通
        event(new WdtErpTradeAfterSaleEvent($aftersales));
        $gotoJob = (new sendAfterSaleWaitDealNoticeJob($aftersales_data['company_id'], $aftersales_data['aftersales_bn']))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        
        return $aftersales;
    }

    /**
     * 消费者提交售后申请
     *
     * @param array $data 创建售后申请提交的参数
     */
    public function salespersonCreateAftersales($data, $orderItem = [])
    {
        if (!$orderItem) {
            throw new ResourceException("系统无此订单，无法申请售后");
        }
        //只能自提订单
        if ($orderItem['receipt_type'] != 'ziti') {
            throw new ResourceException('只能申请自提订单');
        }
        if ($orderItem['delivery_status'] != 'DONE') {
            throw new ResourceException('请先核销订单');
        }
        //校验是否可以发起售后
        if (!$data['distributor_id'] || $orderItem['distributor_id'] != $data['distributor_id']) {
            throw new ResourceException('订单不可在此店铺申请售后');
        }

        $aftersales = $this->create($data);

        //更改为已回寄状态
        $get_aftersales_filter = [
            'company_id' => $data['company_id'],
            'user_id' => $data['user_id'],
            'aftersales_bn' => $aftersales['aftersales_bn'],
        ];
        $sendBackData = [
            'corp_code' => '',
            'logi_no' => '',
            'receiver_address' => '',
            'receiver_mobile' => '',
        ];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 更新售后主表
            $update_aftersales_filter = [
                'company_id' => $data['company_id'],
                'user_id' => $data['user_id'],
                'aftersales_bn' => $aftersales['aftersales_bn'],
            ];
            $aftersales_data = [
                'aftersales_status' => 1,
                'progress' => 2, // 已处理
                'sendback_data' => $sendBackData,
            ];
            $this->aftersalesRepository->update($update_aftersales_filter, $aftersales_data);
            // 更新售后明细表
            $update_aftersales_detail_filter = [
                'company_id' => $data['company_id'],
                'user_id' => $data['user_id'],
                'aftersales_bn' => $aftersales['aftersales_bn'],
            ];
            $aftersales_detail_data = [
                'aftersales_status' => 1,
                'progress' => 2, // 已处理
            ];
            $this->aftersalesDetailRepository->updateBy($update_aftersales_detail_filter, $aftersales_detail_data);
            $orderProcessLog = [
                'order_id' => $aftersales['order_id'],
                'company_id' => $data['company_id'],
                'operator_type' => 'user',
                'operator_id' => $data['user_id'],
                'remarks' => '订单售后',
                'detail' => '售后单号：' . $aftersales['aftersales_bn'] . '，售后单寄回商品',
                'params' => $data,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        $aftersales = $this->getAftersales($get_aftersales_filter);

        return $aftersales;
    }

    /**
     * 审核售后操作
     *
     * @param array $data 处理售后申请参数
     */
    public function review($data)
    {
        $filter = [
            'aftersales_bn' => $data['aftersales_bn'],
            'company_id' => $data['company_id']
        ];
        // if ($data['distributor_id'] ?? 0) {
        //     $filter['distributor_id'] = $data['distributor_id'];
        // }
        $aftersales = $this->aftersalesRepository->get($filter);
        if (!$aftersales) {
            throw new ResourceException('售后单数据异常');
        }
        if (!in_array($aftersales['aftersales_status'], ['0'])) {
            throw new ResourceException("售后{$data['aftersales_bn']}已处理，无需审核");
        }

        $orderService = $this->getOrderService('normal');
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //审核拒绝
            if (!$data['is_approved']) {
                // 处理售后 退款单状态
                $refundUpdate = [
                    'refund_status' => 'REFUSE', // 审核成功待退款
                ];
                $this->aftersalesRefundRepository->updateOneBy($filter, $refundUpdate);

                // 还原取消数量
                if ($aftersales['is_partial_cancel']) {
                    $orderService->partailCancelRestore($aftersales['order_id'], false);
                } else {
                    // 记录可申请售后的商品数量
                    $aftersalesDetailList = $this->aftersalesDetailRepository->getList($filter);
                    $can_aftersales_num = array_sum(array_column($aftersalesDetailList['list'], 'num'));
                    $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
                    $order = $normalOrdersRepository->get($aftersales['company_id'], $aftersales['order_id']);
                    $left_aftersales_num = $order->getLeftAftersalesNum() + $can_aftersales_num;
                    $normalOrdersRepository->update(['company_id' => $aftersales['company_id'], 'order_id' => $aftersales['order_id']], ['left_aftersales_num' => $left_aftersales_num]);
                }

                $update = [
                    'progress' => 3, // 拒绝
                    'aftersales_status' => 3, // 拒绝
                    'refuse_reason' => $data['refuse_reason'],
                ];
                $orderProcessLog = [
                    'order_id' => $aftersales['order_id'],
                    'company_id' => $aftersales['company_id'],
                    'operator_type' => $data['operator_type'] ?? 'system',
                    'operator_id' => $data['operator_id'] ?? 0,
                    'remarks' => '订单售后',
                    'detail' => '售后单号：' . $data['aftersales_bn'] . ' 售后单驳回，驳回原因：' . $data['refuse_reason'],
                    'params' => $data,
                ];
                event(new OrderProcessLogEvent($orderProcessLog));
                //拒绝退款推旺店通
                event(new WdtErpTradeAfterSaleEvent($aftersales));
            } else {
                if (isset($data['refund_fee']) && $data['refund_fee'] <= 0) {
                    $data['refund_fee'] = intval($aftersales['refund_fee']);
                }
                if (isset($data['refund_point']) && $data['refund_point'] <= 0 ) {
                    $data['refund_point'] = intval($aftersales['refund_point']);
                }
                if (isset($data['freight'])  && $data['freight'] <= 0  ) {
                    $data['freight'] = intval($aftersales['freight']);
                }
                
                if ($aftersales['aftersales_type'] == 'ONLY_REFUND') { // 仅退款,直接退款
                    if ($data['refund_fee'] < 0 or $data['freight'] < 0) {
                        throw new ResourceException('请填写退款金额并且大于等于0！');
                    }
                    if ($data['refund_fee'] > $aftersales['refund_fee']) {
                        throw new ResourceException('退款金额不能大于应退金额！');
                    }
                    if ($data['refund_point'] > $aftersales['refund_point']) {
                        throw new ResourceException('退款积分不能大于应退积分！');
                    }
                    if ($data['freight'] > $aftersales['freight']) {
                        throw new ResourceException('退款运费不能大于应退运费！');
                    }

                    // 处理售后退款单状态
                    $refundUpdate = [
                        'refund_status' => 'AUDIT_SUCCESS', // 审核成功待退款
                        'refund_fee' => $data['refund_fee'], // 审核售后的时候可能改退款金额
                        'freight' => $data['freight'],
                        'return_freight' => $data['freight'] ? 1 : 0, //是否退运费
                        'refund_point' => $data['refund_point'], // 审核售后的时候可能改退款金额
                    ];
                    $this->aftersalesRefundRepository->updateOneBy($filter, $refundUpdate);

                    $update = [
                        'progress' => 9, // 退款处理中
                        'aftersales_status' => 1, // 处理中
                    ];
                    $orderProcessLog = [
                        'order_id' => $aftersales['order_id'],
                        'company_id' => $aftersales['company_id'],
                        'operator_type' => $data['operator_type'] ?? 'system',
                        'operator_id' => $data['operator_id'] ?? 0,
                        'remarks' => '订单售后',
                        'detail' => '售后单号：' . $data['aftersales_bn'] . '，同意退款',
                        'params' => $data,
                    ];
                    event(new OrderProcessLogEvent($orderProcessLog));

                    //分销退佣金
                    $aftersalesDetailList = $this->aftersalesDetailRepository->getList($filter);
                    $brokerageService = new BrokerageService();
                    foreach ($aftersalesDetailList['list'] as $aftersalesDetail) {
                        $brokerageService->brokerageByAftersalse($data['company_id'], $aftersales['order_id'], $aftersalesDetail['item_id'], $aftersalesDetail['num']);
                    }

                    //部分取消增加库存
                    if ($aftersales['is_partial_cancel']) {
                        $orderService->partailCancelRestore($aftersales['order_id'], true);
                    }

                    // 如果审核通过,判断是不是所有商品都售后了
                    $couponjob = (new OrderRefundCompleteJob($aftersales['company_id'], $aftersales['order_id']))->onQueue('slow');
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($couponjob);
                    //退款成功推聚水潭
                    event(new JushuitanTradeAftersalesEvent($aftersales));
                    //退款成功推旺店通
                    event(new WdtErpTradeAfterSaleEvent($aftersales));

                    //退款成功推发票冲红 T
                    app('log')->info('[aftersales refund success] 退款成功推发票冲红 aftersales:'.json_encode($aftersales));
                    dispatch(new \OrdersBundle\Jobs\InvoiceRedJob($aftersales))->onQueue('invoice');


                } else { // 退款退货/换货
                    $update = [
                        'progress' => 1, // 等待消费者商品回寄
                        'aftersales_status' => 1, // 处理中
                    ];
                    if ($aftersales['return_type'] == 'offline') {
                        $update['progress'] = 2;
                    }
                    $autoRefuseTime = intval($this->getOrdersSetting($data['company_id'], 'auto_refuse_time'));
                    //获取售后时效时间
                    if ($autoRefuseTime > 0) {
                        $update['auto_refuse_time'] = strtotime("+$autoRefuseTime day", time());
                    } else {
                        $update['auto_refuse_time'] = time();
                    }

                    //售后地址
                    if (isset($data['aftersales_address_id']) && !empty($data['aftersales_address_id'])) {
                        $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
                        $aftersalesAddress = $distributorAftersalesAddressService->getDistributorAfterSalesAddressDetail(['company_id' => $aftersales['company_id'], 'address_id' => $data['aftersales_address_id']]);
                        $update['aftersales_address'] = [
                            'aftersales_address_id' => $data['aftersales_address_id'],
                            'aftersales_contact' => $aftersalesAddress['contact'],
                            'aftersales_mobile' => $aftersalesAddress['mobile'],
                            'aftersales_address' => $aftersalesAddress['province'] . $aftersalesAddress['city'] . $aftersalesAddress['area'] . $aftersalesAddress['address']
                        ];
                    }
                    $gotoJob = (new AftersalesSuccessSendMsg($aftersales['company_id'], $aftersales['order_id'], $aftersales['aftersales_bn']))->onQueue('slow');
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
                    // 退货退款模式下不处理对应退款单状态
                    $orderProcessLog = [
                        'order_id' => $aftersales['order_id'],
                        'company_id' => $aftersales['company_id'],
                        'operator_type' => $data['operator_type'] ?? 'system',
                        'operator_id' => $data['operator_id'] ?? 0,
                        'remarks' => '订单售后',
                        'detail' => '售后单号：' . $data['aftersales_bn'] . '，售后单审核通过，等待商品回寄',
                        'params' => $data,
                    ];
                    event(new OrderProcessLogEvent($orderProcessLog));
                }
            }

            // 更新售后主表
            $result = $this->aftersalesRepository->update($filter, $update);
            // 更新售后明细表
            $this->aftersalesDetailRepository->updateBy($filter, $update);
            //联通OME售后申请埋点
            if (!$data['is_approved']) {
                event(new TradeAftersalesRefuseEvent($result));
                event(new SaasErpAftersalesCancelEvent($result));
            } else {
                event(new SaasErpAftersalesUpdateEvent($result));
            }

            $conn->commit();

        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        // 拒绝售后申请推聚水潭
        if (!$data['is_approved']) {
            event(new JushuitanTradeAftersalesEvent($result));
        }

        $templateData = [
            'aftersales_type' => $aftersales['aftersales_type'],
            'aftersales_bn' => $aftersales['aftersales_bn'],
            'user_id' => $aftersales['user_id'],
            'item_name' => $aftersales['item_name'] ?? '',
            'company_id' => $aftersales['company_id'],
            'refuse_reason' => $data['refuse_reason'] ?? '',
            'order_id' => $aftersales['order_id'],
            'refund_amount' => $aftersales['refund_fee'],
        ];
        $this->sendWxaTemplateMsg($update['aftersales_status'], $templateData);

        return $aftersales;
    }

    public function sendWxaTemplateMsg($status, $aftersales)
    {
        if ($aftersales['aftersales_type'] == 'ONLY_REFUND') {
            $aftersalesType = '仅退款';
        } elseif ($aftersales['aftersales_type'] == 'REFUND_GOODS') {
            $aftersalesType = '退货退款';
        } else {
            $aftersalesType = '换货';
        }

        $wxaTemplateMsgData = [
            'order_id' => $aftersales['order_id'],
            'refund_fee' => bcdiv($aftersales['refund_amount'], 100, 2),
            'aftersales_bn' => $aftersales['aftersales_bn'],
            'aftersales_type' => $aftersalesType,
            'item_name' => $aftersales['item_name'],
            'remarks' => '订单售后详情查看具体信息',
        ];

        if (strval($status) == '1' && $aftersales['aftersales_type'] == 'REFUND_GOODS') {
            $status = 'WAIT_BUYER_RETURN_GOODS';
        }

        if (strval($status) == '1' && $aftersales['aftersales_type'] == 'ONLY_REFUND') {
            $status = 'REFUND_SUCCESS';
        }

        if (strval($status) == '2') {
            $status = 'REFUND_SUCCESS';
        }

        if (strval($status) == '3' && $aftersales['aftersales_type'] == 'REFUND_GOODS') {
            $status = 'SELLER_REFUSE_BUYER';
        }

        if (strval($status) == '3' && $aftersales['aftersales_type'] == 'ONLY_REFUND') {
            $status = 'REFUND_CLOSED';
        }

        switch ($status) {
            case 'SELLER_REFUSE_BUYER': // 商家拒绝售后
                $wxaTemplateMsgData['aftersales_status'] = '售后申请被驳回';
                $wxaTemplateMsgData['remarks'] = $aftersales['refuse_reason'];
                break;
            case 'WAIT_BUYER_RETURN_GOODS': //  同意申请，进行退货
                $wxaTemplateMsgData['aftersales_status'] = '售后申请已同意';
                $wxaTemplateMsgData['remarks'] = '商家已同意售后申请，请进行退货回寄处理';
                break;
            case 'REFUND_CLOSED': //  拒绝退款
                $wxaTemplateMsgData['aftersales_status'] = '退款申请被驳回';
                $wxaTemplateMsgData['remarks'] = $aftersales['refuse_reason'];
                break;
            case 'REFUND_SUCCESS': //  同意退款
                $wxaTemplateMsgData['aftersales_status'] = '退款申请已同意';
                $wxaTemplateMsgData['remarks'] = '商家已同意退款';
                break;
            default:
                return true;
                break;
        }

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($aftersales['company_id'], $aftersales['order_id']);
        if (!$order || !$order['wxa_appid']) {
            return true;
        }

        $openid = app('wxaTemplateMsg')->getOpenIdBy($aftersales['user_id'], $order['wxa_appid']);
        if (!$openid) {
            return true;
        }

        $sendData['scenes_name'] = 'aftersalesRefuse';
        $sendData['company_id'] = $aftersales['company_id'];
        $sendData['appid'] = $order['wxa_appid'];
        $sendData['openid'] = $openid;
        $sendData['data'] = $wxaTemplateMsgData;
        app('wxaTemplateMsg')->send($sendData);
    }

    /**
     * 确认退款
     *
     * @param array $data 确认退款
     */
    public function confirmRefund($param)
    {
        $filter = [
            'aftersales_bn' => $param['aftersales_bn'],
            'company_id' => $param['company_id']
        ];
        // if ($param['distributor_id'] ?? 0) {
        //     $filter['distributor_id'] = $param['distributor_id'];
        // }
        $aftersales = $this->aftersalesRepository->get($filter);
        if (!$aftersales) {
            throw new ResourceException("需要退款的售后单不存在");
        }
        if (in_array($aftersales['aftersales_status'], [2, 3])) {
            throw new ResourceException("售后单已处理");
        }
        if (!in_array($aftersales['aftersales_status'], [1])) {
            throw new ResourceException("售后{$param['aftersales_bn']}不是审核中，无需审核");
        }
        if (isset($param['refund_fee']) && $param['refund_fee'] > $aftersales['refund_fee']) {
            throw new ResourceException("实退金额必须小于等于应退金额");
        }
        if (isset($param['refund_point']) && $param['refund_point'] > $aftersales['refund_point']) {
            throw new ResourceException("实退积分必须小于等于应退积分");
        }
        $refund = $this->aftersalesRefundRepository->getInfo($filter);
        // 检查售后单是否存在退款单
        if (!$refund) {
            throw new ResourceException("售后单不存在退款单");
        }
        // 检查退款单是否是已经退款成功
        if ($refund['refund_status'] == 'SUCCESS') {
            throw new ResourceException("退款单已退款成功，无需重复操作");
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 拒绝退款
            if (!$param['check_refund']) {
                // 处理售后 退款单状态
                $refundUpdate = [
                    'refund_status' => 'REFUSE', // 审核成功待退款
                ];
                // 售后表数据
                $update = [
                    'aftersales_status' => 3,
                    'progress' => 3,
                    'refuse_reason' => $param['refunds_memo'] ?? '',
                ];
                $orderProcessLog = [
                    'order_id' => $aftersales['order_id'],
                    'company_id' => $param['company_id'],
                    'operator_type' => 'user',
                    'operator_id' => $param['user_id'] ?? 0,
                    'remarks' => '订单售后',
                    'detail' => '售后单号：' . $param['aftersales_bn'] . ' 拒绝退款，拒绝退款原因：' . $param['refunds_memo'],
                    'params' => $param,
                ];
                event(new OrderProcessLogEvent($orderProcessLog));
            } else {
                // 添加聚水潭确认收货的验证
                if ($aftersales['aftersales_type'] == 'REFUND_GOODS') {
                    // 检查是否开启了聚水潭
                    $jushuitanSettingService = new JushuitanSettingService();
                    $jushuitanSetting = $jushuitanSettingService->getJushuitanSetting($param['company_id']);
                    
                    if (isset($jushuitanSetting['is_open']) && $jushuitanSetting['is_open'] == true) {
                        // 开启了聚水潭，需要检查确认收货状态
                        if ($aftersales['progress'] != 8) {
                            throw new ResourceException("卖家暂未收货，请先到聚水潭进行处理");
                        }
                    }
                    // 未开启聚水潭，则按原有逻辑处理，不强制要求确认收货
                }

                // 处理售后 退款单状态
                $refundUpdate = [
                    'refund_status' => 'AUDIT_SUCCESS', // 审核成功待退款
                    // 'refund_fee' => $param['refund_fee'], // 审核售后的时候可能改退款金额
                    // 'refund_channel' => 'original', // $param['is_refund'] ? 'original' : 'offline',
                    'refund_channel' => $refund['pay_type'] == 'offline_pay' ? 'offline' : 'original', // 默认取消订单原路返回,pay_type=offline_pay为线下退款
                ];
                // 售后表数据
                $update = [
                    'aftersales_status' => 2,
                    'progress' => 4,
                ];
                $orderProcessLog = [
                    'order_id' => $aftersales['order_id'],
                    'company_id' => $aftersales['company_id'],
                    'operator_type' => 'user',
                    'operator_id' => isset($aftersales['user_id']) ? $aftersales['user_id'] : ($param['operator_id'] ?? 0),
                    'remarks' => '订单售后',
                    'detail' => '售后单号：' . $param['aftersales_bn'] . '，售后单同意退款',
                    'params' => $param,
                ];
                event(new OrderProcessLogEvent($orderProcessLog));

                //分销退佣金
                $aftersalesDetailList = $this->aftersalesDetailRepository->getList($filter);
                $brokerageService = new BrokerageService();
                foreach ($aftersalesDetailList['list'] as $aftersalesDetail) {
                    $brokerageService->brokerageByAftersalse($param['company_id'], $aftersales['order_id'], $aftersalesDetail['item_id'], $aftersalesDetail['num']);
                }
            }
            if (isset($param['refunds_memo']) && $param['refunds_memo']) {
                $refundUpdate['refunds_memo'] = $param['refunds_memo'];
            }

            // 更新售后退款单状态
            $this->aftersalesRefundRepository->updateOneBy($filter, $refundUpdate);
            // 更新售后主表状态
            $result = $this->aftersalesRepository->update($filter, $update);
            // 更新售后明细状态
            $this->aftersalesDetailRepository->updateBy($filter, $update);
            $jobParams = [
                'company_id' => $result['company_id'],
                'user_id' => $result['user_id'],
                'aftersales_bn' => $result['aftersales_bn'],
                'order_id' => $result['order_id'],
                'refunded_fee' => $param['refund_fee'],
            ];

            if (!$param['check_refund']) {
                // 记录可申请售后的商品数量
                $aftersalesDetailList = $this->aftersalesDetailRepository->getList($filter);
                $can_aftersales_num = array_sum(array_column($aftersalesDetailList['list'], 'num'));
                $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
                $order = $normalOrdersRepository->get($aftersales['company_id'], $aftersales['order_id']);
                $left_aftersales_num = $order->getLeftAftersalesNum() + $can_aftersales_num;
                $normalOrdersRepository->update(['company_id' => $aftersales['company_id'], 'order_id' => $aftersales['order_id']], ['left_aftersales_num' => $left_aftersales_num]);
            }

            //社区订单积分处理 @todo
            // if ($update['aftersales_status'] == 2 && $orderData['orderInfo']['shop_id']) {
            //     app('log')->debug('AftersalesService,积分处理'.__LINE__);
            //     $pointService = new PointService();
            //     $pointService->reducePoints($param['company_id'], $orderData['orderInfo']['shop_id'], $orderData, $orderItem, $aftersales['num']);
            // }

            $templateData = [
                'aftersales_type' => $aftersales['aftersales_type'],
                'aftersales_bn' => $aftersales['aftersales_bn'],
                'user_id' => $aftersales['user_id'],
                'item_name' => $aftersales['item_name'] ?? '',
                'company_id' => $aftersales['company_id'],
                'refuse_reason' => $param['refunds_memo'] ?? '',
                'order_id' => $aftersales['order_id'],
                'refund_amount' => $aftersales['refund_fee'],
            ];
            $this->sendWxaTemplateMsg($update['aftersales_status'], $templateData);

            // 如果审核通过,判断是不是所有商品都售后了
            if ($param['check_refund']) {
                $couponjob = (new OrderRefundCompleteJob($result['company_id'], $result['order_id']))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($couponjob);
            }

            //联通OME售后申请埋点
            if (!$param['check_refund']) {
                event(new SaasErpAftersalesCancelEvent($result));
            } else {
                event(new SaasErpAftersalesUpdateEvent($result));
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        // 退款驳回推聚水潭
        if (!$param['check_refund']) {
            event(new JushuitanTradeAftersalesEvent($result));
        }

        if ($update['aftersales_status'] == 2) {
            //退款成功推聚水潭
            event(new JushuitanTradeAftersalesEvent($result));
            //状态变更推旺店通
            event(new WdtErpTradeAfterSaleEvent($result));
            //退款成功推发票冲红
            dispatch(new \OrdersBundle\Jobs\InvoiceRedJob($result))->onQueue('invoice');
        }

        return $result;
    }

    /**
     * 售后消费者寄回商品
     *
     * @param array $data 确认退款
     */
    public function sendBack($param, $is_oms = false)
    {
        $get_aftersales_filter = [
            'company_id' => $param['company_id'],
            'user_id' => $param['user_id'],
            'aftersales_bn' => $param['aftersales_bn'],
        ];
        $aftersales = $this->getAftersales($get_aftersales_filter);
        if ($aftersales['aftersales_type'] == 'ONLY_REFUND') {
            throw new ResourceException("不需要回寄货品");
        }
        if ($aftersales['progress'] != 1) {// 1 商家接受申请，等待消费者回寄
            throw new ResourceException("您已提交回寄信息，请勿重复提交");
        }
        $sendBackData = [
            'corp_code' => $param['corp_code'],
            'logi_no' => $param['logi_no'],
            'receiver_address' => isset($param['receiver_address']) ? $param['receiver_address'] : '',
            'receiver_mobile' => isset($param['receiver_mobile']) ? $param['receiver_mobile'] : '',
        ];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {

            // 更新售后主表
            $update_aftersales_filter = [
                'company_id' => $param['company_id'],
                'user_id' => $param['user_id'],
                'aftersales_bn' => $param['aftersales_bn'],
            ];
            $aftersales_data = [
                'progress' => 2, // 已处理
                'sendback_data' => $sendBackData,
            ];
            $this->aftersalesRepository->update($update_aftersales_filter, $aftersales_data);
            // 更新售后明细表
            $update_aftersales_detail_filter = [
                'company_id' => $param['company_id'],
                'user_id' => $param['user_id'],
                'aftersales_bn' => $param['aftersales_bn'],
            ];
            $aftersales_detail_data = [
                'progress' => 2, // 已处理
            ];
            $this->aftersalesDetailRepository->updateBy($update_aftersales_detail_filter, $aftersales_detail_data);
            $orderProcessLog = [
                'order_id' => $aftersales['order_id'],
                'company_id' => $param['company_id'],
                'operator_type' => 'user',
                'operator_id' => $param['user_id'],
                'remarks' => '订单售后',
                'detail' => '售后单号：' . $param['aftersales_bn'] . '，售后单寄回商品',
                'params' => $param,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        $aftersales = $this->getAftersales($get_aftersales_filter);
        // 联通OME 填写退货物流埋点
        if (!$is_oms) {
            event(new TradeAftersalesLogiEvent($aftersales));
        }
        //联通 SaasErp 填写退货物流埋点
        app('log')->debug("saaserp " . __FUNCTION__ . "," . __LINE__ . ",消费者填写退货物流 埋点");
        event(new SaasErpAftersalesLogiEvent($aftersales));

        $gotoJob = (new sendAfterSaleWaitConfirmNoticeJob($param['company_id'], $param['aftersales_bn']))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        //填写退货物流推聚水潭
        event(new JushuitanTradeAftersalesEvent($aftersales));
        //填写退货物流推旺店通
        event(new WdtErpTradeAfterSaleEvent($aftersales));

        return $aftersales;
    }

    /**
     * 售后后台录入寄回商品物流
     *
     * @param array $data 确认退款
     */
    public function shopSendBack($param, $is_oms = false)
    {
        $get_aftersales_filter = [
            'company_id' => $param['company_id'],
            'aftersales_bn' => $param['aftersales_bn'],
        ];
        $aftersales = $this->getAftersales($get_aftersales_filter);
        if ($aftersales['aftersales_type'] == 'ONLY_REFUND') {
            throw new ResourceException("不需要回寄货品");
        }
        if ($aftersales['progress'] != 1) {// 1 商家接受申请，等待消费者回寄
            throw new ResourceException("您已提交回寄信息，请勿重复提交");
        }
        $sendBackData = [
            'corp_code' => $param['corp_code'],
            'logi_no' => $param['logi_no'],
            'receiver_address' => isset($param['receiver_address']) ? $param['receiver_address'] : '',
            'receiver_mobile' => isset($param['receiver_mobile']) ? $param['receiver_mobile'] : '',
        ];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {

            // 更新售后主表
            $update_aftersales_filter = [
                'company_id' => $param['company_id'],
                'aftersales_bn' => $param['aftersales_bn'],
            ];
            $aftersales_data = [
                'progress' => 2, // 已处理
                'sendback_data' => $sendBackData,
            ];
            $this->aftersalesRepository->update($update_aftersales_filter, $aftersales_data);
            // 更新售后明细表
            $update_aftersales_detail_filter = [
                'company_id' => $param['company_id'],
                'aftersales_bn' => $param['aftersales_bn'],
            ];
            $aftersales_detail_data = [
                'progress' => 2, // 已处理
            ];
            $this->aftersalesDetailRepository->updateBy($update_aftersales_detail_filter, $aftersales_detail_data);
            $orderProcessLog = [
                'order_id' => $aftersales['order_id'],
                'company_id' => $param['company_id'],
                'operator_type' => $param['operator_type'],
                'operator_id' => $param['operator_id'],
                'remarks' => '订单售后',
                'detail' => '售后单号：' . $param['aftersales_bn'] . '，售后单寄回商品',
                'params' => $param,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        $aftersales = $this->getAftersales($get_aftersales_filter);
        // 联通OME 填写退货物流埋点
        if (!$is_oms) {
            event(new TradeAftersalesLogiEvent($aftersales));
        }
        //联通 SaasErp 填写退货物流埋点
        app('log')->debug("saaserp " . __FUNCTION__ . "," . __LINE__ . ",管理员填写退货物流 埋点");
        event(new SaasErpAftersalesLogiEvent($aftersales));

        //填写退货物流推聚水潭
        event(new JushuitanTradeAftersalesEvent($aftersales));
        //填写退货物流推旺店通
        event(new WdtErpTradeAfterSaleEvent($aftersales));
        $gotoJob = (new sendAfterSaleWaitConfirmNoticeJob($param['company_id'], $param['aftersales_bn']))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        return $aftersales;
    }

    /**
     * 商家收到消费者回寄的退货商派，确认操作
     *
     * @param array $param 确认收到退货
     */
    public function sendBackConfirm($param)
    {
        $filter = [
            'aftersales_bn' => $param['aftersales_bn'],
            'company_id' => $param['company_id']
        ];
        $aftersales = $this->aftersalesRepository->get($filter);
        if (!$aftersales) {
            throw new ResourceException("售后单不存在");
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $updateData = [
                'progress' => 8, // 商家确认收到消费者回寄的退货商品
            ];

            // 更新售后主表状态
            $result = $this->aftersalesRepository->update($filter, $updateData);
            // 更新售后明细状态
            $this->aftersalesDetailRepository->updateBy($filter, $updateData);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $result;
    }

    /**
     * 消费者申请换货，商家确认收到回寄商品，进行重新进行发货
     *
     * @param array $data 确认退款
     */
    public function sendConfirm($param)
    {
        $get_aftersales_filter = [
            'company_id' => $param['company_id'],
            'aftersales_bn' => $param['aftersales_bn'],
        ];
        $aftersales = $this->getAftersales($get_aftersales_filter);
        if ($aftersales['aftersales_type'] != 'EXCHANGING_GOODS') {
            throw new ResourceException("不需要重新发货");
        }
        $sendConfirmData = [
            'corp_code' => $param['corp_code'],
            'logi_no' => $param['logi_no'],
        ];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = [
                'company_id' => $param['company_id'],
                'aftersales_bn' => $param['aftersales_bn'],
            ];

            // 更新售后主表
            $update_aftersales_filter = [
                'company_id' => $param['company_id'],
                'aftersales_bn' => $param['aftersales_bn'],
            ];
            $aftersales_data = [
                'aftersales_status' => 2, // 已处理
                'progress' => 4, // 已处理
                'sendconfirm_data' => $sendConfirmData,
            ];
            $this->aftersalesRepository->update($update_aftersales_filter, $aftersales_data);
            // 更新售后明细表
            $update_aftersales_detail_filter = [
                'company_id' => $param['company_id'],
                'aftersales_bn' => $param['aftersales_bn'],
            ];
            $aftersales_detail_data = [
                'aftersales_status' => 2, // 已处理
                'progress' => 4, // 已处理
            ];
            $this->aftersalesDetailRepository->updateBy($update_aftersales_detail_filter, $aftersales_detail_data);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        $get_aftersales_filter = [
            'company_id' => $param['company_id'],
            'aftersales_bn' => $param['aftersales_bn'],
        ];
        $aftersales = $this->getAftersales($get_aftersales_filter);

        return $aftersales;
    }

    /**
     * 子订单售后状态更新
     *
     * @param array $filter
     */
    public function updateItemAftersaleStatus($filter, $update)
    {
        $normalOrderService = new NormalOrderService();
        $res = $normalOrderService->ItemAftersalesStatusUpdate($filter, $update);
        //如果全部子订单都申请售后并且退款完成，订单状态改为已取消
        $filter = ['order_id' => $filter['order_id'], 'company_id' => $filter['company_id']];
        $orders = $normalOrderService->getOrderList($filter, 1, 1);
        $itemAftersales = array_unique(array_column($orders['list'][0]['items'], 'aftersales_status'));
        if (count($itemAftersales) == 1 && $itemAftersales[0] == 'REFUND_SUCCESS') {
            $updateInfo = ['order_status' => 'CANCEL'];
            $normalOrderService->update($filter, $updateInfo);

            $brokerageService = new BrokerageService();
            $brokerageService->updatePlanCloseTime($filter['company_id'], $filter['order_id']);

            $orderProfitService = new OrderProfitService();
            $orderProfitService->updateOneBy(['order_id' => $filter['order_id'], 'company_id' => $filter['company_id']], ['order_profit_status' => 0]);
            $orderProfitService->orderItemsProfitRepository->updateBy(['order_id' => $filter['order_id'], 'company_id' => $filter['company_id']], ['order_profit_status' => 0]);
        }

        return $res;
    }

    /**
     * 更新退款单的状态
     */
    public function updateAftersalesRefund($updateData = [], $filter = [])
    {
        $res = $this->aftersalesRefundRepository->updateOneBy($filter, $updateData);
        return $res;
    }

    /**
     * 处理item_name过滤条件，转换为aftersales_bn过滤
     * 
     * @param array $filter
     * @return array
     */
    private function processItemNameFilter($filter)
    {
        if (empty($filter['item_name']) || empty($filter['company_id'])) {
            if (isset($filter['item_name'])) {
                unset($filter['item_name']);
            }
            return $filter;
        }

        $itemName = $filter['item_name'];
        unset($filter['item_name']);

        // 根据item_name查询商品
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $itemResult = $itemsRepository->list(
            ['item_name|contains' => $itemName, 'company_id' => $filter['company_id']],
            [],
            -1,
            1,
            'item_id'
        );
        
        if (empty($itemResult['list'])) {
            // 如果没有找到商品，设置一个不存在的aftersales_bn，确保查询结果为空
            $filter['aftersales_bn'] = [-1];
            return $filter;
        }

        $itemIds = array_column($itemResult['list'], 'item_id');
        
        // 通过item_id从售后详情表查出aftersales_bn
        $aftersalesDetailResult = $this->aftersalesDetailRepository->getList(
            ['item_id' => $itemIds, 'company_id' => $filter['company_id']],
            0,
            -1
        );
        
        if (empty($aftersalesDetailResult['list'])) {
            // 如果没有找到售后详情，设置一个不存在的aftersales_bn，确保查询结果为空
            $filter['aftersales_bn'] = [-1];
            return $filter;
        }

        $aftersalesBns = array_unique(array_column($aftersalesDetailResult['list'], 'aftersales_bn'));
        
        // 如果filter中已有aftersales_bn，则取交集
        if (isset($filter['aftersales_bn'])) {
            $existingBns = is_array($filter['aftersales_bn']) ? $filter['aftersales_bn'] : [$filter['aftersales_bn']];
            $aftersalesBns = array_intersect($existingBns, $aftersalesBns);
            if (empty($aftersalesBns)) {
                $aftersalesBns = [-1];
            }
        }
        
        $filter['aftersales_bn'] = $aftersalesBns;
        
        return $filter;
    }

    /**
     * 获取售后单列表
     */
    public function getAftersalesList($filter, $offset = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'], $is_app = false)
    {
        // 处理item_name过滤条件
        $filter = $this->processItemNameFilter($filter);
        
        if (isset($filter['is_prescription_order']) || isset($filter['order_class'])) {
            if(isset($filter['is_prescription_order'])){
                if ($filter['is_prescription_order'] === '0') { // 不含处方药订单
                    $filter['o.prescription_status'] = 0;
                } else if ($filter['is_prescription_order'] === '1') {
                    $filter['o.prescription_status|gt'] = 0;
                }
                unset($filter['is_prescription_order']);
            }
            if(isset($filter['order_class'])){
                $filter['o.order_class'] = $filter['order_class'];
                unset($filter['order_class']);
            }

            $res = $this->aftersalesRepository->listsJoinOrder($filter, 'a.*', ($offset/$limit) + 1, $limit, $orderBy);
        } else {
            $res = $this->aftersalesRepository->getList($filter, $offset, $limit, $orderBy);
        }
        $membersDelete = $this->membersDeleteRecordRepository->getLists(['company_id' => $filter['company_id']], 'user_id');
        if (!empty($membersDelete)) {
            $deleteUsers = array_column($membersDelete, 'user_id');
        }
        //获取配送员信息
        $selfDeliveryOperatorIds = array_filter(array_unique(array_column($res['list'], 'self_delivery_operator_id')), function ($selfDeliveryOperatorId) {
            return  $selfDeliveryOperatorId > 0;
        });
        $selfDeliveryOperator = [];
        if ($selfDeliveryOperatorIds) {
            $operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
            $selfDeliveryOperator = $operatorsRepository->lists(['operator_id' => $selfDeliveryOperatorIds]);
            $selfDeliveryOperator = array_column($selfDeliveryOperator['list'], null, 'operator_id');
        }

        if ($res['list']) {
            $distributorIdList = array_column($res['list'], 'distributor_id');
            $distributorService = new DistributorService();
            $indexDistributor = $distributorService->getDistributorListById($filter['company_id'], $distributorIdList);
            $subOrderIds = [];

            // 售后默认地址
            $aftersalesAddressMap = [];
            $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
            $filterAddress = [
                'company_id' => $filter['company_id'],
                'distributor_id' => $distributorIdList,
                'is_default' => 1,
            ];
            $distributorAftersalesAddressList = $distributorAftersalesAddressService->getLists($filterAddress);
            if (!empty($distributorAftersalesAddressList)) {
                foreach($distributorAftersalesAddressList as $distributorAftersalesAddressInfo ) {
                    $aftersalesAddressMap[$distributorAftersalesAddressInfo['distributor_id']] = [
                        'aftersales_address_id' => $distributorAftersalesAddressInfo['address_id'] ?? '',
                        'aftersales_address' => $distributorAftersalesAddressInfo['address'] ?? '',
                        'aftersales_contact' => $distributorAftersalesAddressInfo['contact'] ?? '',
                        'aftersales_mobile' => $distributorAftersalesAddressInfo['mobile'] ?? '',
                        'is_default' => 1,
                    ];
                }
            }
        
            foreach ($res['list'] as &$v) {

                $v['self_delivery_operator_mobile'] = '';
                $v['self_delivery_operator_name'] = '';
                if($v['self_delivery_operator_id'] > 0 && isset($selfDeliveryOperator[$v['self_delivery_operator_id']])){
                    $v['self_delivery_operator_mobile'] = $selfDeliveryOperator[$v['self_delivery_operator_id']]['mobile'];
                    $v['self_delivery_operator_name'] = $selfDeliveryOperator[$v['self_delivery_operator_id']]['username'];
                }

                // 默认退货地址  
                if (empty($v['aftersales_address'])) {
                    $v['aftersales_address'] = $aftersalesAddressMap[$v['distributor_id']] ?? [];
                }
                $detail_filter = [
                    'aftersales_bn' => $v['aftersales_bn'],
                    'company_id' => $v['company_id'],
                    'user_id' => $v['user_id'],
                ];
                $detail = $this->aftersalesDetailRepository->getList($detail_filter);
                if ($is_app) {
                    $this->attachDetail($detail, $v['company_id'], $v['order_id']);
                    $v['app_info'] = $this->getAppInfo($v);
                }

                foreach ($detail['list'] as $d) {
                    $subOrderIds[] = $d['sub_order_id'];
                }

                $v['detail'] = $detail['list'];
                $v['user_delete'] = false;
                if (!empty($deleteUsers)) {
                    if (in_array($v['user_id'], $deleteUsers)) {
                        $v['user_delete'] = true;
                    }
                }
                $v['distributor_info'] = $indexDistributor[$v['distributor_id']] ?? ['name' => '平台自营'];
            }
            unset($v);

            // 申请售后的子订单信息
            if ($subOrderIds) {
                $orderItems = $this->orderItemsRepository->getList(['id' => $subOrderIds], 0, -1)['list'];
                $orderItems = array_column($orderItems, null, 'id');
            }
            if (!empty($orderItems)) {
                foreach ($res['list'] as &$v) {
                    if ($v['detail']) {
                        foreach ($v['detail'] as &$d) {
                            if (!empty($orderItems[$d['sub_order_id']])) {
                                $d['is_prescription'] = $orderItems[$d['sub_order_id']]['is_prescription'];
                            }
                        }
                    }
                }
            }
        }

        return $res;
    }

    private function attachDetail(&$detail, $company_id, $order_id)
    {
        /** @var NormalOrdersItemsRepository $normalOrdersItemsRepository */
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        // 获取order信息
        $orderitem_list = $normalOrdersItemsRepository->get($company_id, $order_id);

        //获取供应商信息
        $supplierData = [];
        $supplierIds = array_filter(array_column($orderitem_list, 'supplier_id'));
        if ($supplierIds) {
            $supplierService = new SupplierService();
            $rs = $supplierService->repository->getLists(['operator_id' => $supplierIds], 'operator_id, supplier_name');
            $supplierData = array_column($rs, null, 'operator_id');
        }

        $items = [];
        foreach ($orderitem_list as $orderitem) {
            $orderitem['supplier_name'] = $supplierData[$orderitem['supplier_id']] ?? '';
            $items[$orderitem['item_id']] = $orderitem;
        }
        // 拼装item信息
        foreach ($detail['list'] as &$d) {
            if (isset($items[$d['item_id']])) {
                $d['orderItem'] = $items[$d['item_id']];
                $d['supplier_name'] = $items[$d['item_id']]['supplier_name'] ?? '';
            } else {
                $d['orderItem'] = null;
                $d['supplier_name'] = '';
            }
        }
    }

    public function getButtons(...$types)
    {
        $aftersale_buttons = [
            'mark' =>
                ['type' => 'mark', 'name' => '备注'],
            'contact' =>
                ['type' => 'contact', 'name' => '联系客户'],
            'check' =>
                ['type' => 'check', 'name' => '处理售后'],
            'confirm' =>
                ['type' => 'confirm', 'name' => '确认收货'],
        ];
        $buttons = [];
        foreach ($types as $type) {
            $buttons[] = $aftersale_buttons[$type];
        }
        return $buttons;
    }

    /**
     * 前端又不要了
     * 前端又要了
     */
    public function getAppInfo(&$aftersale, $is_detail = false)
    {
        /** @var NormalOrdersRepository $normalOrdersRepository */
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $order_info = $normalOrdersRepository->getInfo([
            'company_id' => $aftersale['company_id'],
            'order_id' => $aftersale['order_id'],
        ]);
        $create_date = date("Y-m-d H:i:s", $aftersale['create_time']);
        $buttons = [];
        if (!$is_detail) {
            $buttons[] = 'mark';
        }
        $status_msg = '';
        $progress_msg = '';
        switch ($aftersale['aftersales_status']) {
            case 0:
                $status_msg = '待处理';
                break;
            case 1:
                $status_msg = '处理中';
                break;
            case 2:
                $status_msg = '已处理';
                break;
            case 3:
                $status_msg = '已驳回';
                break;
            case 4:
                $status_msg = '已关闭';
                break;
        }
        switch ($aftersale['progress']) {
            case 0:
                $progress_msg = '等待商家处理';
                if (!$is_detail) {
                    if ($aftersale['aftersales_type'] == 'ONLY_REFUND') {
                        $progress_msg .= '-仅退款';
                    } else {
                        $progress_msg .= '-退货退款';
                    }
                }
                break;
            case 1:
                $progress_msg = '商家接受申请，等待消费者回寄';
                break;
            case 2:
                if ($aftersale['return_type'] == 'offline') {
                    $progress_msg = '消费者已到店退货';
                } else {
                    $progress_msg = '消费者回寄，等待商家收货确认';
                }
                break;
            case 8:
                $progress_msg = '商家确认收货，等待审核退款';
                break;
            case 3:
                $progress_msg = '售后已驳回';
                break;
            case 4:
                $progress_msg = '售后已处理';
                break;
            case 7:
                $progress_msg = '消费者已撤销';
                break;
            case 9:
                $progress_msg = '退款处理中';
                break;
            case 5:
                $progress_msg = '退款已驳回';
                break;
            case 6:
                $progress_msg = '已完成，关闭';
                break;
        }
        switch ($aftersale['progress']) {
            case 0:
                $buttons = array_merge($buttons, ['check', 'contact']);
                break;
            case 2:
                $buttons = array_merge($buttons, ['confirm', 'contact']);
                break;
            default:
                $buttons = array_merge($buttons, ['contact']);
                break;
        }
        $buttons = $this->getButtons(...$buttons);
        return compact('buttons', 'progress_msg', 'status_msg', 'order_info', 'create_date');
    }

    /**
     * 获取导出售后单列表
     */
    public function exportAftersalesList($filter, $offset = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'])
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('aftersales_detail', 'ad')
            ->leftJoin('ad', 'aftersales', 'a', 'ad.aftersales_bn = a.aftersales_bn');

        $row = 'a.aftersales_bn,a.order_id,ad.item_bn,ad.item_name,ad.num,a.aftersales_type,a.aftersales_status,a.create_time,ad.refund_fee,a.progress,a.description,a.reason,a.refuse_reason,a.memo,ad.distributor_id,ad.company_id';

        $criteria = $this->getFilter($filter, $criteria);

        if ($limit > 0) {
            $criteria->setFirstResult(($offset - 1) * $limit)->setMaxResults($limit);
        }

        foreach ($orderBy as $key => $value) {
            $criteria->addOrderBy($key, $value);
        }
        $lists = $criteria->select($row)->execute()->fetchAll();

        // 附加店铺名称
        if (!empty($lists)) {
            $distributorIdSet = array_column($lists, 'distributor_id');
            $currentData = current($lists);
            (new DistributorService())->getListAddDistributorFields($currentData['company_id'], $distributorIdSet, $lists);
        }

        $result['list'] = $lists;
        return $result;
    }

    /**
     * 根据params获取售后详情
     */
    public function getAftersales($params, $is_app = false)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'aftersales_bn' => $params['aftersales_bn']
        ];
        if (isset($params['user_id']) && $params['user_id']) {
            $filter['user_id'] = $params['user_id'];
        }
        $aftersales = $this->aftersalesRepository->get($filter);
        if (!$aftersales) {
            throw new ResourceException('没有售后信息');
        }
        // 售后默认地址
        if (empty($aftersales['aftersales_address'])) {
            $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
            $filterAddress = [
                'company_id' => $params['company_id'],
                'distributor_id' => $aftersales['distributor_id'],
                'is_default' => 1,
            ];
            $distributorAftersalesAddressInfo = $distributorAftersalesAddressService->getInfo($filterAddress);
            $aftersales['aftersales_address'] = [
                'aftersales_address_id' => $distributorAftersalesAddressInfo['address_id'] ?? '',
                'aftersales_address' => $distributorAftersalesAddressInfo['address'] ?? '',
                'aftersales_contact' => $distributorAftersalesAddressInfo['contact'] ?? '',
                'aftersales_mobile' => $distributorAftersalesAddressInfo['mobile'] ?? '',
                'is_default' => 1,
            ];
        }
    
        $aftersales['salesman'] = [];
        //获取导购员信息
        if ($aftersales['salesman_id']) {
            $salespersonService = new SalespersonService();
            $aftersales['salesman'] = $salespersonService->getInfoById($aftersales['salesman_id']);
        }

        $aftersales_detail = $this->aftersalesDetailRepository->getList($filter);
        $this->attachDetail($aftersales_detail, $aftersales['company_id'], $aftersales['order_id']);
        $aftersales['detail'] = $aftersales_detail['list'];
        if ($is_app) {
            $aftersales['app_info'] = $this->getAppInfo($aftersales, true);
        }

        //获取退款单
        $refund_filter = [
            'company_id' => $aftersales['company_id'],
            'aftersales_bn' => $aftersales['aftersales_bn'],
            // 'refund_status' => 'AUDIT_SUCCESS',  // 审核成功待退款
        ];
        $refund_info = $this->aftersalesRefundRepository->getInfo($refund_filter);
        $aftersales['refund_info'] = $refund_info;

        // 兼容拆单单个数量，加入实退金额和实退积分
        if (count($aftersales['detail']) == 1) {
            foreach ($aftersales['detail'] as $key => $item) {
                $aftersales['detail'][$key]['refund_info']['refunded_fee'] = $refund_info['refunded_fee'];
                $aftersales['detail'][$key]['refund_info']['refund_point'] = $refund_info['refunded_point'];
            }
        }

        $normalOrderService = new NormalOrderService();
        $order_filter = [
            'company_id' => $aftersales['company_id'],
            'order_id' => $aftersales['order_id'],
        ];
        $aftersales['order_info'] = $normalOrderService->getInfo($order_filter);

        if ($aftersales['supplier_id']) {
            $supplier_filter = [
                'company_id' => $aftersales['company_id'],
                'operator_id' => $aftersales['supplier_id'],
                'operator_type' => 'supplier',
            ];
            $operatorsService = new OperatorsService();
            $aftersales['supplier_info'] = $operatorsService->getInfo($supplier_filter);
        }

        if ($aftersales['distributor_id']) {
            $distributor_filter = [
                'company_id' => $aftersales['company_id'],
                'distributor_id' => $aftersales['distributor_id'],
            ];
            $distributorService = new DistributorService();
            $aftersales['distributor_info'] = $distributorService->getInfoSimple($distributor_filter);
        }
        $service = new JushuitanSettingService();
        $setting = $service->getJushuitanSetting($aftersales['company_id']);
        $aftersales['is_jushuitan'] = $setting['is_open'] ?? false;
        return $aftersales;
    }

    /**
     * 获取当前售后信息
     */
    public function getAftersalesDetail($company_id, $aftersales_bn)
    {
        $filter = [
            'company_id' => $company_id,
            'aftersales_bn' => $aftersales_bn,
        ];
        /*
        if (isset($params['user_id']) && $params['user_id']) {
            $filter['user_id'] = $params['user_id'];
        }
        */
        $aftersales = $this->aftersalesRepository->get($filter);
        if (!$aftersales) {
            throw new ResourceException('没有售后信息');
        }
        $aftersales_detail = $this->aftersalesDetailRepository->getList($filter);
        $aftersales['detail'] = $aftersales_detail['list'];
        $normalOrderService = new NormalOrderService();
        $order_filter = [
            'company_id' => $aftersales['company_id'],
            'order_id' => $aftersales['order_id'],
        ];
        $aftersales['order_info'] = $normalOrderService->getInfo($order_filter);

        return $aftersales;
    }

    /**
     * 获取售后详情（包含订单、支付单、退款单）
     */
    public function getAftersalesInfo($company_id, $aftersales_bn)
    {
        $res = [];
        $filter = [
            'company_id' => $company_id,
            'aftersales_bn' => $aftersales_bn,
        ];
        $filter = array_filter($filter);
        $aftersales = $this->aftersalesRepository->get($filter);
        if ($aftersales) {
            if (($aftersales['distributor_id'] ?? 0) && $aftersales['aftersales_type'] == 'REFUND_GOODS' && $aftersales['aftersales_status'] == 1) {
                $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
                $adfilter = [
                    'company_id' => $aftersales['company_id'],
                    'distributor_id' => $aftersales['distributor_id'],
                    'return_type' => $aftersales['return_type'],
                    'is_default' => true,
                ];
                $address = $distributorAftersalesAddressService->getOneAftersaleAddressBy($adfilter);
                $aftersales['aftersales_address'] = $address;
            }
            $detail = $this->aftersalesDetailRepository->get($filter);
            $aftersalesInfo = array_merge($aftersales, $detail);

            $normalOrderService = new NormalOrderService();
            $orderData = $normalOrderService->getOrderInfo($company_id, $aftersales['order_id']);
            foreach ($orderData['orderInfo']['items'] as $key => $item) {
                if ($item['item_id'] != $aftersales['item_id']) {
                    unset($orderData['orderInfo']['items'][$key]);
                }
            }
            $orderData['orderInfo']['items'] = array_merge($orderData['orderInfo']['items']);
            $res = $orderData;
            $res['aftersales'] = $aftersalesInfo;
        }

        return $res;
    }

    /**
     * 售后关闭
     *
     * @param company_id
     * @param aftersales_bn
     * @return void
     * @author
     **/
    public function closeAftersales($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'aftersales_bn' => $params['aftersales_bn'],
            'user_id' => $params['user_id'],
        ];
        $aftersales = $this->getAftersales($filter);
        if (!$aftersales) {
            throw new ResourceException("售后单数据异常");
        }
        if ($aftersales['aftersales_status'] == '4') {
            throw new ResourceException("售后已撤销， 不需要重复操作！");
        }
        if ($aftersales['aftersales_status'] == '3') {
            throw new ResourceException("售后已驳回， 不需要撤销！");
        }
        if (in_array($aftersales['aftersales_status'], ['5', '1', '2'])) {
            throw new ResourceException("售后单已被受理,不能撤销,请联系商家处理！");
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 更新售后信息
            $filter = [
                'company_id' => $params['company_id'],
                'aftersales_bn' => $params['aftersales_bn'],
            ];
            $update = [
                'progress' => '7', // 已撤销。已关闭
                'aftersales_status' => '4', // 已撤销。已关闭（取消售后）
            ];
            $result = $this->aftersalesRepository->update($filter, $update);
            $this->aftersalesDetailRepository->updateBy($filter, $update);
            $refundUpdate = [
                'refund_status' => 'CANCEL', // 撤销退款
            ];
            $this->aftersalesRefundRepository->updateOneBy($filter, $refundUpdate);

            // 还原可申请售后的商品数量
            $canAftersalesNum = array_sum(array_column($aftersales['detail'], 'num'));
            $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $order = $normalOrdersRepository->get($params['company_id'], $aftersales['order_id']);
            $leftAftersalesNum = $order->getLeftAftersalesNum() + $canAftersalesNum;
            $normalOrdersRepository->update(['company_id' => $params['company_id'], 'order_id' => $aftersales['order_id']], ['left_aftersales_num' => $leftAftersalesNum]);

            $orderProcessLog = [
                'order_id' => $aftersales['order_id'],
                'company_id' => $params['company_id'],
                'operator_type' => 'user',
                'operator_id' => $params['user_id'],
                'remarks' => '订单售后',
                'detail' => '售后单号：' . $params['aftersales_bn'] . '，售后单关闭',
                'params' => $params,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        //联通OME售后申请埋点
        event(new TradeAftersalesCancelEvent($result));
        //联通 SaasErp 取消售后申请埋点
        app('log')->debug("saaserp " . __FUNCTION__ . "," . __LINE__ . ",取消售后申请，消费者主动关闭或者到期自动关闭  埋点");
        event(new SaasErpAftersalesCancelEvent($result));
        //撤销售后推聚水潭
        event(new JushuitanTradeAftersalesEvent($result));
        $gotoJob = (new sendAfterSaleCancelNoticeJob($params['company_id'], $aftersales['aftersales_bn']))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        return $result;
    }

    /**
     * 商家驳回的售后到期自动关闭
     */
    public function scheduleAutoDoneAftersales()
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $companys = $criteria->select('company_id')->from('companys')->execute()->fetchAll();
        if (!$companys) {
            return true;
        }
        foreach ($companys as $companyId) {
            $pageSize = 100;
            $time = time();
            $closeTime = 3;//$this->getOrdersSetting($companyId, 'aftersale_close_time');
            $filter = [
                'update_time|lte' => (time() - $closeTime * (24 * 60 * 60)),
                'aftersales_status' => "3",
                'company_id' => $companyId,
            ];
            $totalCount = $this->aftersalesRepository->count($filter);
            if ($totalCount) {
                $totalPage = ceil($totalCount / 100);
                for ($i = 1; $i <= $totalPage; $i++) {
                    $offset = 0;
                    $data = $this->aftersalesRepository->getList($filter, $offset, 100, ["create_time" => "ASC"]);
                    foreach ($data['list'] as $row) {
                        $params = [
                            'company_id' => $row['company_id'],
                            'aftersales_bn' => $row['aftersales_bn'],
                        ];
                        try {
                            $this->closeAftersales($params);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * 自动驳回
     */
    public function scheduleAutoRefuse()
    {
        //每分钟执行一次，当前只处理一分钟内的售后单
        //获取售后信息
        $pageSize = 20;
        $time = time() + 60;
        $filter = [
            'progress' => 1,
            'aftersales_type' => 'REFUND_GOODS',
            'auto_refuse_time|gt' => '0',
            'auto_refuse_time|lt' => $time
        ];

        $count = $this->aftersalesDetailRepository->count($filter);
        $totalPage = ceil($count / $pageSize);

        for ($i = 0; $i < $totalPage; $i++) {
            $list = $this->aftersalesDetailRepository->getList($filter, $i * $pageSize, $pageSize);
            foreach ($list['list'] as $v) {
                $aftersalesFilter = [
                    'aftersales_bn' => $v['aftersales_bn'],
                    'company_id' => $v['company_id']
                ];
                $aftersales = $this->aftersalesRepository->get($aftersalesFilter);
                if (!$aftersales) {
                    continue;
                }

                $update = [
                    'progress' => '3',
                    'aftersales_status' => '3',
                    'refuse_reason' => '未收到商品自动驳回',
                ];
                $itemUpdate['aftersales_status'] = 'SELLER_REFUSE_BUYER';

                $conn = app('registry')->getConnection('default');
                $conn->beginTransaction();
                try {
                    // 更新售后信息
                    $params = [
                        'company_id' => $v['company_id'],
                        'aftersales_bn' => $v['aftersales_bn'],
                    ];
                    $this->aftersalesRepository->update($params, $update);
                    $this->aftersalesRefundRepository->updateOneBy($params, ['refund_status' => 'REFUSE']);
                    $params['detail_id'] = $v['detail_id'];
                    $this->aftersalesDetailRepository->update($params, $update);

                    // 记录可申请售后的商品数量
                    $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
                    $order = $normalOrdersRepository->get($v['company_id'], $aftersales['order_id']);
                    $left_aftersales_num = $order->getLeftAftersalesNum() + $v['num'];
                    $normalOrdersRepository->update(['company_id' => $v['company_id'], 'order_id' => $aftersales['order_id']], ['left_aftersales_num' => $left_aftersales_num]);

                    //更新子订单售后信息
                    // $itemFilter = [
                    //     'company_id' => $v['company_id'],
                    //     'order_id' => $aftersales['order_id'],
                    //     'item_id' => $aftersales['item_id'],
                    // ];
                    // $this->updateItemAftersaleStatus($itemFilter, $itemUpdate);

                    $templateData = [
                        'aftersales_type' => $aftersales['aftersales_type'],
                        'aftersales_bn' => $aftersales['aftersales_bn'],
                        'user_id' => $aftersales['user_id'],
                        'item_name' => $aftersales['item_name'] ?? '',
                        'company_id' => $aftersales['company_id'],
                        'refuse_reason' => $aftersales['refuse_reason'] ?? '',
                        'order_id' => $aftersales['order_id'],
                        'refund_amount' => $aftersales['refund_fee'],
                    ];
                    $this->sendWxaTemplateMsg($itemUpdate['aftersales_status'], $templateData);

                    $orderProcessLog = [
                        'order_id' => $aftersales['order_id'],
                        'company_id' => $aftersales['company_id'],
                        'operator_type' => 'system',
                        'remarks' => '订单售后',
                        'detail' => '售后单号：' . $aftersales['aftersales_bn'] . ' 自动驳回，驳回原因：' . $update['refuse_reason'],
                    ];
                    event(new OrderProcessLogEvent($orderProcessLog));
                    $conn->commit();
                } catch (\Exception $e) {
                    $conn->rollback();
                    continue;
                }
            }
        }
        //app('log')->debug('自动驳回'. var_export($count, 1));
        return true;
    }

    /**
     * 获取退款单列表
     */
    public function getRefundsList($filter, $offset = 0, $limit = 10, $orderBy = ['create_time' => 'DESC'])
    {
        $res = $this->aftersalesRefundRepository->getList($filter, $offset, $limit, $orderBy);
        if ($res['list']) {
            $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
            $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            foreach ($res['list'] as $k => &$v) {
                if ($v['aftersales_bn']) {
                    $detail_filter = [
                        'aftersales_bn' => $v['aftersales_bn'],
                        'company_id' => $v['company_id'],
                        'user_id' => $v['user_id'],
                    ];
                    $aftersales = $this->aftersalesRepository->get($detail_filter);
                    $v = array_merge($v, $aftersales);
                    $detail = $this->aftersalesDetailRepository->getList($detail_filter);
                    $v['detail'] = $detail['list'];
                } else {
                    $detail_filter = [
                        'order_id' => $v['order_id'],
                        'company_id' => $v['company_id'],
                        'user_id' => $v['user_id'],
                    ];
                    $detail = $normalOrdersItemsRepository->getList($detail_filter);
                    $v['orderInfo'] = $normalOrdersRepository->getInfo($detail_filter);
                    $v['detail'] = $detail['list'];
                }
            }
        }
        return $res;
    }

    /**
     * 检查售后申请的订单是否合法
     *
     * @param array $data 申请的参数
     */
    public function __checkApply($data, $tradeInfo = [])
    {
        $order_filter = [
            'company_id' => $data['company_id'],
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
        ];

        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->getSimpleOrderInfo($order_filter);
        if (!$orderInfo) {
            throw new ResourceException("系统无此订单，无法申请售后");
        }
        if (in_array($orderInfo['order_status'], ['NOTPAY', 'CANCEL'])) {
            throw new ResourceException('该订单不能申请售后');
        }
        if ($orderInfo['order_status'] == 'DONE' && time() > $orderInfo['order_auto_close_aftersales_time']) {
            throw new ResourceException('该订单已超过售后申请时效');
        }

        if (!is_array($data['detail']) || !$data['detail']) {
            throw new ResourceException('请提交审核售后的商品');
        }
        // if (isset($data['shopId']) && !empty($data['shopId'])) {
        //     if ($orderInfo['ziti_status'] != 'DONE') {
        //         throw new ResourceException('该商品不能申请退换货');
        //     }
        // }
        // if (!in_array($orderInfo['order_status'], ['WAIT_BUYER_CONFIRM', 'DONE'])) {
        //     throw new ResourceException("订单未发货无法申请售后");
        // }
        if (!$data['aftersales_type']) {
            throw new ResourceException('售后类型必选');
        }
        if (empty($data['reason'])) {
            throw new ResourceException('售后理由必选');
        }
        // 如果有售后申请单号，则代表是修改操作。暂时不支持修改售后单，所以这个判断还用不上
        if (isset($data['aftersales_bn']) && $data['aftersales_bn']) {
            $aftersales_filter = [
                'company_id' => $data['company_id'],
                'user_id' => $data['user_id'],
                'order_id' => $data['order_id'],
                'aftersales_bn' => $data['aftersales_bn'],
            ];
            $aftersales = $this->aftersalesRepository->get($aftersales_filter);
            if (!$aftersales) {
                throw new ResourceException('此售后申请单不存在，请确认后再操作');
            }
            if ($aftersales['aftersales_status'] == 2) {
                throw new ResourceException('您的售后单已处理，不支持再修改');
            }
            if ($aftersales['aftersales_status'] == 3) {
                throw new ResourceException('您的售后单已驳回，不支持再修改');
            }
            if ($aftersales['aftersales_status'] == 4) {
                throw new ResourceException('您的售后单已撤销，不支持再修改');
            }
        }
        // 检查自填运费是否已经超过总运费
        // if (isset($data['freight']) && !empty($data['freight'])) {
        //     $total_freight = $this->getAppliedTotalRefundFreight($data['company_id'], $data['order_id']);
        //     app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':total_freight:' . json_encode($total_freight));
        //     app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':data[freight]:' . json_encode($data['freight']));
        //     if ($data['freight'] > $total_freight) {
        //         throw new ResourceException('申请退费运费已经超出支付总运费');
        //     }
        // }

        // 检查自填运费是否已经超过总运费
        if (isset($data['freight']) && !empty($data['freight']) && $data['freight'] > 0) {
            // 判断是否是最后一次申请（整单都申请售后）
            // 先判断数量是否满足最后一次申请的条件（不考虑运费）
            // 调整：当一次提交多个SKU时，需要基于本次提交后的整体状态来判断，而不是逐个SKU判断
            $normalOrderService = new NormalOrderService();
            
            // 1. 获取订单的所有订单项
            $orderItemsFilter = [
                'company_id' => $data['company_id'],
                'order_id' => $data['order_id'],
            ];
            $orderItems = $normalOrderService->normalOrdersItemsRepository->getList($orderItemsFilter, 0, -1);
            if (empty($orderItems['list'])) {
                throw new ResourceException('订单商品不存在');
            }
            
            // 2. 构建本次申请的订单项映射（key: 订单项id, value: 本次申请数量）
            $applyDetailMap = [];
            foreach ($data['detail'] as $v) {
                if (isset($applyDetailMap[$v['id']])) {
                    $applyDetailMap[$v['id']] += $v['num'];
                } else {
                    $applyDetailMap[$v['id']] = $v['num'];
                }
            }
            
            // 3. 遍历所有订单项，判断每个订单项是否满足条件
            $isLastAftersales = true;
            foreach ($orderItems['list'] as $orderItem) {
                // 跳过礼品订单项（礼品不需要申请售后）
                if (isset($orderItem['order_item_type']) && $orderItem['order_item_type'] == 'gift') {
                    continue;
                }
                
                $orderItemId = $orderItem['id'];
                $orderItemNum = $orderItem['num'];
                
                // 获取已申请数量
                $appliedNum = $this->getAppliedNum($data['company_id'], $data['order_id'], $orderItemId);
                
                // 判断是否在本次申请中
                if (isset($applyDetailMap[$orderItemId])) {
                    // 如果在本次申请中：已申请数量 + 本次申请数量 >= 订单项数量
                    $totalAppliedNum = $appliedNum + $applyDetailMap[$orderItemId];
                    if ($totalAppliedNum < $orderItemNum) {
                        $isLastAftersales = false;
                        break;
                    }
                } else {
                    // 如果不在本次申请中：已申请数量 >= 订单项数量
                    if ($appliedNum < $orderItemNum) {
                        $isLastAftersales = false;
                        break;
                    }
                }
            }
            
            if (!$isLastAftersales) {
                throw new ResourceException('部分退不支持退运费');
            }
            
            // 如果是最后一次申请，再校验运费是否超出
            // 根据运费类型分别校验
            if ($orderInfo['freight_type'] == 'cash') {
                // 现金运费校验
                if (!$orderInfo['freight_fee'] || $orderInfo['freight_fee'] <= 0) {
                    throw new ResourceException('订单未付运费');
                }
                $refunded_freight = $this->getAppliedTotalRefundFreight($data['company_id'], $data['order_id']);
                $remain_freight = $orderInfo['freight_fee'] - $refunded_freight;
                if ($data['freight'] > $remain_freight) {
                    throw new ResourceException('申请退款运费不能超出支付运费');
                }
            } else if ($orderInfo['freight_type'] == 'point') {
                // 积分运费校验（订单表的freight_fee存储的是积分值）
                if (!$orderInfo['freight_fee'] || $orderInfo['freight_fee'] <= 0) {
                    throw new ResourceException('订单未付积分运费');
                }
                $refunded_freight_point = $this->getAppliedTotalRefundFreightPoint($data['company_id'], $data['order_id']);
                $remain_freight_point = $orderInfo['freight_fee'] - $refunded_freight_point;
                if ($data['freight'] > $remain_freight_point) {
                    throw new ResourceException('申请退款积分运费不能超出支付积分运费');
                }
            }
        }

        $normalOrderService = new NormalOrderService();
        // $data['detail'] 申请的售后商品明细
        foreach ($data['detail'] as $v) {
            $suborder_filter = [
                'company_id' => $data['company_id'],
                'user_id' => $data['user_id'],
                'order_id' => $data['order_id'],
                'id' => $v['id'],
            ];
            $subOrderInfo = $normalOrderService->getSimpleSubOrderInfo($suborder_filter);
            if (!$subOrderInfo) {
                throw new ResourceException('申请售后商品的订单不存在');
            }
            //校验商品数量
            if ($v['num'] <= 0) {
                throw new ResourceException($subOrderInfo['item_name'] . ' 售后的商品数量必须大于0');
            }
            //自提订单判断是否核销
            if ($orderInfo['receipt_type'] == 'ziti') {
                if ($orderInfo['delivery_status'] != 'DONE') {
                    throw new ResourceException('请先核销订单');
                }
            } else {
                //修复导购发货后，订单无法申请售后的问题
                if ($subOrderInfo['delivery_status'] == 'DONE' && !$subOrderInfo['delivery_item_num']) {
                    $subOrderInfo['delivery_item_num'] = $subOrderInfo['num'];
                }

                //根据订单状态判断申请售后的类型是否可以进行申请【退货退款和换货需要判断】
                if (in_array($data['aftersales_type'], ['REFUND_GOODS', 'EXCHANGING_GOODS'])) {
                    // if (in_array($subOrderInfo['delivery_status'], ['PENDING', 'PARTAIL'])) {
                    if ($subOrderInfo['delivery_item_num'] <= 0) {
                        throw new ResourceException($subOrderInfo['item_name'] . ' 未发货，不能申请退换货');
                    }
                }
            }

            if (!($data['is_partial_cancel'] ?? false)) {
                // 判断单个子订单历史申请数量总和
                $applied_num = $this->getAppliedNum($data['company_id'], $data['order_id'], $v['id']); // 已申请数量
                //如果是自提订单发货数量等于子订单商品数量
                $subOrderInfo['delivery_item_num'] = $orderInfo['receipt_type'] == 'ziti' ? $subOrderInfo['num'] : $subOrderInfo['delivery_item_num'];
                $left_num = $subOrderInfo['delivery_item_num'] + $subOrderInfo['cancel_item_num'] - $applied_num; // 剩余申请数量
                if ($v['num'] > $left_num) {
                    throw new ResourceException($subOrderInfo['item_name'] . ' 剩余可申请售后的数量为' . $left_num . ',申请售后数量为' . $v['num']. ",已申请售后数量为:".$applied_num);
                }
            }


        }

        return true;
    }

    // 获取订单已经申请的退款运费金额（现金运费）
    public function getAppliedTotalRefundFreight($company_id, $order_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(freight)')
            ->from('aftersales')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->eq('freight_type', $qb->expr()->literal('cash'))) // 只查询现金运费
            ->andWhere($qb->expr()->in('aftersales_status', [0, 5, 1, 2])); // 0未处理，1处理中，2已处理，5申请中
        $sum = $qb->execute()->fetchColumn();

        return $sum ?? 0;
    }

    // 获取订单已经申请的退款积分运费（根据freight_type查询freight字段）
    public function getAppliedTotalRefundFreightPoint($company_id, $order_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(freight)')
            ->from('aftersales')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->eq('freight_type', $qb->expr()->literal('point'))) // 只查询积分运费
            ->andWhere($qb->expr()->in('aftersales_status', [0, 5, 1, 2])); // 0未处理，1处理中，2已处理，5申请中
        $sum = $qb->execute()->fetchColumn();

        return $sum ?? 0;
    }

    // 获取子订单已申请的退款金额
    public function getAppliedTotalRefundFee($company_id, $order_id, $sub_order_id = 0)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(refund_fee)')
            ->from('aftersales_detail')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->in('aftersales_status', [0, 5, 1, 2])); // 0未处理，1处理中，2已处理，5申请中
        if ($sub_order_id) {
            $qb = $qb->andWhere($qb->expr()->eq('sub_order_id', $sub_order_id));
        }            
        $sum = $qb->execute()->fetchColumn();
        return $sum ?? 0;
    }

    // 获取子订单已申请的退款积分(积分组合支付的时候)
    public function getAppliedRefundPoint($company_id, $order_id, $sub_order_id = 0)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(refund_point)')
            ->from('aftersales_detail')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->in('aftersales_status', [0, 5, 1, 2])); // 0未处理，1处理中，2已处理，5申请中
        if ($sub_order_id) {
            $qb = $qb->andWhere($qb->expr()->eq('sub_order_id', $sub_order_id));
        }    
        $sum = $qb->execute()->fetchColumn();
        return $sum ?? 0;
    }

    // 获取子订单已申请的应退还积分数量
    public function getAppliedReTurnPoint($company_id, $order_id, $sub_order_id, $order_num, $get_points)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('detail_id,num')
            ->from('aftersales_detail')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->eq('sub_order_id', $sub_order_id))
            ->andWhere($qb->expr()->in('aftersales_status', [0, 5, 1, 2])); // 0未处理，1处理中，2已处理，5申请中
        $list = $qb->execute()->fetchAll();
        $total_return_point = 0;
        foreach ($list as $row) {
            $proportion = bcdiv($row['num'], $order_num, 5);
            $total_return_point += round(bcmul($proportion, $get_points, 5));
        }
        return $get_points - $total_return_point;
    }

    // 获取子订单已申请的商品数量，获取到的是当前不能申请的数量
    public function getAppliedNum($company_id, $order_id, $sub_order_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(num)')
            ->from('aftersales_detail')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->eq('sub_order_id', $sub_order_id))
            ->andWhere($qb->expr()->in('aftersales_status', [0, 5, 1, 2])); // 0未处理，1处理中，2已处理，5申请中
        $sum = $qb->execute()->fetchColumn();
        return $sum ?? 0;
    }

    // 计算已售后的退款数量
    public function getAppliedNumByNum($company_id, $order_id, $sub_order_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(num)')
            ->from('aftersales_detail')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->eq('sub_order_id', $sub_order_id))
            ->andWhere($qb->expr()->in('aftersales_status', [0, 5, 1, 2])); // 0未处理，1处理中，2已处理，5申请中
        $sum = $qb->execute()->fetchColumn();
        return $sum ?? 0;
    }

    // 计算售后单上是否挂了运费
    public function getAppliedFreightByNum($company_id, $order_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('freight')
            ->from('aftersales')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->in('aftersales_status', [0, 5, 1, 2])); // 0未处理，1处理中，2已处理，5申请中
        $sum = $qb->execute()->fetchColumn();
        return $sum ?? 0;
    }

    // 获取子订单历史已申请的次数，不管申请是被驳回还是消费者自己关闭
    public function getAppliedCount($company_id, $order_id, $sub_order_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from('aftersales_detail')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->eq('sub_order_id', $sub_order_id));
        $count = $qb->execute()->fetchColumn();
        return $count ?? 0;
    }

    // 获取订单已申请的售后退款金额
    public function getOrderAppliedTotalRefundFee($company_id, $order_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(ad.refund_fee)')
            ->from('aftersales_detail', 'ad')
            ->leftJoin('ad', 'aftersales', 'a', 'ad.aftersales_bn = a.aftersales_bn')
            ->where($qb->expr()->eq('ad.company_id', $company_id))
            ->andWhere($qb->expr()->eq('ad.order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->in('ad.aftersales_status', [0, 5, 1, 2])); // 0未处理，1处理中，2已处理，5申请中
        $sum = $qb->execute()->fetchColumn();
        return $sum ?? 0;
    }

    public function getAfterSalesNumDetailList($userId, $companyId)
    {
        $filter = [
            'user_id'           => $userId,
            'company_id'        => $companyId,
            'aftersales_status' => [0, 1]
        ];
        $afterSalesList = $this->aftersalesRepository->getList($filter);

        if (empty($afterSalesList['list'])) {
            return [
                'aftersales'            => 0,
                'aftersales_pending'    => 0,
                'aftersales_processing' => 0,
            ];
        }

        $pending = 0;
        $processing = 0;
        foreach ($afterSalesList['list'] as $value) {
            if ($value['aftersales_status'] == 0) {
                $pending ++;
            } elseif ($value['aftersales_status'] == 1) {
                $processing ++;
            }
        }

        return [
            'aftersales'            => $pending + $processing,
            'aftersales_pending'    => $pending,
            'aftersales_processing' => $processing,
        ];
    }

    public function countAftersalesNum($filter)
    {
        $count = $this->aftersalesRepository->count($filter);
        return intval($count);
    }

    public function getRefundAmount($filter, $aftersales_item_num, $up = 0)
    {
        $normalOrderService = new NormalOrderService();
        $orderData = $normalOrderService->getOrderInfo($filter['company_id'], $filter['order_id']);
        foreach ($orderData['orderInfo']['items'] as $key => $item) {
            if ($item['item_id'] != $filter['item_id']) {
                unset($orderData['orderInfo']['items'][$key]);
            }
        }
        if (empty($orderData['orderInfo']['items'])) {
            throw new ResourceException('商品不存在');
        }

        $items = array_values($orderData['orderInfo']['items'])[0];

        if ($aftersales_item_num > $items['num']) {
            throw new ResourceException('超过购买数量');
        }

        //可申请次数
        $applyNum = $this->getCanApplyNum($filter, $up);
        if ($aftersales_item_num > $applyNum) {
            throw new ResourceException('超过申请数量');
        }

        //处理非现金支付订单金额为零。导致oms退款失败的问题
        if (!$items['total_fee']) {
            $items['total_fee'] = $items['item_fee'];//todo 如果订单有折扣，这里的金额不对
        }

        if ($aftersales_item_num == $items['num']) {
            return $items['total_fee'];
        }

        $unit_price = $items['total_fee'] / $items['num']; //单价

        if ($items['total_fee'] % $items['num'] == 0) {
            return $unit_price * $aftersales_item_num;
        }
        // 除不尽
        $unit_price = floor($unit_price);
        $aftersales_price = $unit_price * $aftersales_item_num;

        //申请数量 = 可以申请的最大数量，即 全额退款
        if ($aftersales_item_num == $applyNum) {
            //获取总售后金额
            $refund_amount = $this->aftersalesRepository->sum(['company_id' => $filter['company_id'], 'order_id' => $filter['order_id'], 'item_id' => $filter['item_id']], 'refund_amount');
            return $items['total_fee'] - $refund_amount;
        }

        return $aftersales_price;
    }

    /**
     * 获取可以申请次数
     */
    public function getCanApplyNum($filter, $up = 0)
    {
        //3 拒绝
        //获取申请数量
        $where = [
            'company_id' => $filter['company_id'],
            'order_id' => $filter['order_id'],
            'item_id' => $filter['item_id'],
        ];
        if ($up == 1) {
            //编辑
            $where['aftersales_bn|neq'] = $filter['aftersales_bn'];
        }
        $where['aftersales_status|neq'] = 3;
        $applyNum = $this->aftersalesRepository->sum($where, 'num');

        $normalOrderService = new NormalOrderService();
        $orderItem = $normalOrderService->getOrderItemInfo($filter['company_id'], $filter['order_id'], $filter['item_id']);

        return $orderItem['num'] - $applyNum;
    }

    /**
     * 判断是否为全部售后
     * @param $filter
     * @return bool
     */
    public function getAllSales($filter)
    {
        $num = $this->aftersalesRepository->sum(['order_id' => $filter['order_id'], 'item_id' => $filter['item_id'], 'aftersales_status' => 2], 'num');

        $normalOrderService = new NormalOrderService();
        $orderItem = $normalOrderService->getOrderItemInfo($filter['company_id'], $filter['order_id'], $filter['item_id']);

        if ($num >= $orderItem['num']) {
            return true;
        }
        return false;
    }

    private function getFilter($filter, $criteria)
    {
        $order = ['distributor_id', 'create_time', 'aftersales_bn', 'aftersales_type', 'aftersales_status', 'company_id', 'order_id'];

        if ($filter) {
            foreach ($filter as $key => $filterValue) {
                // if ($filterValue) {
                if (isset($filterValue)) {
                    if (is_array($filterValue)) {
                        array_walk($filterValue, function (&$value) use ($criteria) {
                            $value = $criteria->expr()->literal($value);
                        });
                    } else {
                        $filterValue = $criteria->expr()->literal($filterValue);
                    }
                    $list = explode('|', $key);
                    if (count($list) > 1) {
                        list($v, $k) = $list;
                        $v = in_array($v, $order) ? 'a.' . $v : $v;
                        $criteria->andWhere($criteria->expr()->andX(
                            $criteria->expr()->$k($v, $filterValue)
                        ));
                        continue;
                    } elseif (is_array($filterValue)) {
                        $key = in_array($key, $order) ? 'a.' . $key : $key;
                        $criteria->andWhere($criteria->expr()->andX(
                            $criteria->expr()->in($key, $filterValue)
                        ));
                        continue;
                    } else {
                        $key = in_array($key, $order) ? 'a.' . $key : $key;
                        $criteria->andWhere($criteria->expr()->andX(
                            $criteria->expr()->eq($key, $filterValue)
                        ));
                    }
                }
            }
        }
        return $criteria;
    }

    /**
     * 自动关闭售后
     */
    public function scheduleAutoCloseOrderItemAftersales()
    {
        $filter['order_status'] = 'DONE';
        $filter['order_class'] = 'normal';
        $filter['auto_close_aftersales_time|lte'] = time();
        $filter['aftersales_status'] = 'null';

        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $orderService = $this->getOrderService('normal');

        $pageSize = 20;
        $totalCount = $orderService->getOrderItemCount($filter);
        app('log')->info('scheduleAutoCloseOrderItemAftersales totalCount:'.$totalCount);
        $totalPage = ceil($totalCount / $pageSize);
        if ($totalCount) {
            $bspayOrderIds = [];
            for ($i = 1; $i <= $totalPage; $i++) {
                $aftersalesItem = $orderService->getOrderItemList($filter, $i, $pageSize, ['auto_close_aftersales_time' => 'asc']);
                foreach ($aftersalesItem['list'] as $val) {
                    $params = [
                        'id' => $val['id'],
                    ];
                    $normalOrdersItemsRepository->update($params, ['aftersales_status' => 'CLOSED']);
                    if ($val['pay_type'] == 'adapay') {
                        $adaPaymentService = new AdaPaymentService();
                        $adaPaymentService->scheduleAutoPaymentConfirmation($val['company_id'], $val['order_id']);
                    } elseif ($val['pay_type'] == 'bspay') {
                        $bspayOrderIds[$val['order_id']] = [
                            'company_id' => $val['company_id'],
                            'order_id' => $val['order_id'],
                        ];
                    }
                }
                app('log')->info('scheduleAutoCloseOrderItemAftersales bspayOrderIds:'.var_export($bspayOrderIds, true));
                if (!empty($bspayOrderIds)) {
                    $bspayService = new BsPayService();
                    foreach ($bspayOrderIds as $val) {
                        // 斗拱，支付确认
                        $bspayService->scheduleAutoPaymentConfirmation($val['company_id'], $val['order_id']);
                    }
                }
            }

        }
    }

    /**
     * 获取导出财务售后单列表
     */
    public function exportFinancialAftersalesList($filter, $offset = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'])
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->from('aftersales', 'a')
            ->leftJoin('a', 'aftersales_detail', 'ad', 'a.aftersales_bn = ad.aftersales_bn')
            ->leftJoin('a', 'aftersales_refund', 'ar', 'a.aftersales_bn = ar.aftersales_bn');

        $row = 'a.aftersales_bn,a.order_id,ad.item_name,ad.num,a.create_time,a.refund_fee,a.description,a.reason,ar.refund_success_time';

        $criteria = $this->getFilter($filter, $criteria);

        if ($limit > 0) {
            $criteria->setFirstResult(($offset - 1) * $limit)->setMaxResults($limit);
        }

        foreach ($orderBy as $key => $value) {
            $criteria->addOrderBy($key, $value);
        }
        $lists = $criteria->select($row)->execute()->fetchAll();

        $result['list'] = $lists;
        return $result;
    }


    /**
     * 【售后】售后申请提醒信息 获取
     * @param $company_id
     * @return array
     */
    public function getRemind($company_id)
    {
        $key = 'aftersalesRemind:' . $company_id;
        $data = app('redis')->connection('companys')->get($key);
        $default = [
            'intro' => '',
            'is_open' => false,
        ];
        if (!$data) {
            return $default;
        }
        $data = json_decode($data, 1);
        $_data = array_merge($default, $data);
        $_data['is_open'] = $_data['is_open'] === 'true' ? true : false;
        return $_data;
    }

    /**
     * 【售后】售后申请提醒信息 设置
     * @param $company_id
     * @param $data array intro:详情内容 is_open:是否开启
     */
    public function setRemind($company_id, $data)
    {
        $key = 'aftersalesRemind:' . $company_id;
        $params = json_encode($data);
        app('redis')->connection('companys')->set($key, $params);
    }

    public function bindUserAftersales($companyId, $orderId, $userId)
    {
        $filter = ['order_id' => $orderId, 'company_id' => $companyId];
        $data = ['user_id' => $userId];
        if ($this->aftersalesRepository->get($filter)) {
            $this->aftersalesRepository->updateBy($filter, $data);
        }

        if ($this->aftersalesDetailRepository->get($filter)) {
            $this->aftersalesDetailRepository->updateBy($filter, $data);
        }

        if ($this->aftersalesRefundRepository->getInfo($filter)) {
            $this->aftersalesRefundRepository->updateBy($filter, $data);
        }

        return true;
    }

    /**
     * 【售后】计算需要扣减的订单所得积分
     * @param $subOrderInfo
     * @param $num
     */

    public function getReturnPoint($subOrderInfo, $num, $appliedNum)
    {
        if ($subOrderInfo ?? 0) {
            if ($subOrderInfo['num'] - $appliedNum - $num == 0) {
                $snum = $this->getAppliedReTurnPoint($subOrderInfo['company_id'], $subOrderInfo['order_id'], $subOrderInfo['id'], $subOrderInfo['num'], $subOrderInfo['get_points']);
                return bcsub($subOrderInfo['get_points'], $snum);
            } else {
                $proportion = bcdiv($num, $subOrderInfo['num'], 5);
                return round(bcmul($proportion, $subOrderInfo['get_points'], 5));
            }
        }
        return 0;
    }

    public function updateRemark($filter, $remark)
    {
        return $this->aftersalesRepository->updateBy($filter, ['distributor_remark' => $remark]);
    }

    /**
     * 获取已完成售后单的商品数量
     * @param int $companyId 企业id
     * @param array $distributorIds 店铺id
     * @return array
     */
    public function getDoneAftersalesTotalSalesCountByDistributorIds(int $companyId, array $distributorIds): array
    {
        return $this->aftersalesRepository->getTotalSalesCountByDistributorIds([
            "company_id" => $companyId,
            "distributor_id" => $distributorIds,
            "aftersales_status" => 2
        ], [
            "aftersales_status" => 2
        ], [
            "order_status" => "DONE",
            "order_auto_close_aftersales_time|lt" => time()
        ]);
    }

    public function shopApply($data) {
        // 检查是否可以申请售后
        $this->__checkApply($data);
        $filter = [
            'company_id' => $data['company_id'],
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
        ];
        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->getSimpleOrderInfo($filter);
        $aftersales_bn = $this->__genAftersalesBn();

        $memberService = new MemberService();
        $memberInfo = $memberService->getMemberInfo(['company_id' => $data['company_id'], 'user_id' => $data['user_id']]);
        $data['contact'] = $memberInfo['username'] ?? '';
        $freight = $data['freight'] ?? 0;

        $aftersales_data = [
            'aftersales_bn' => $aftersales_bn,
            'shop_id' => $orderInfo['shop_id'],
            'order_id' => $data['order_id'],
            'company_id' => $data['company_id'],
            'user_id' => $data['user_id'],
            'distributor_id' => $orderInfo['distributor_id'],
            'aftersales_type' => $data['aftersales_type'],
            'aftersales_status' => 0,
            'progress' => 0,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? '',
            'evidence_pic' => $data['evidence_pic'] ?? [],
            'salesman_id' => $data['salesman_id'] ?? 0,
            'contact' => $data['contact'] ?? '',
            'mobile' => $orderInfo['mobile'] ?? '',
            'merchant_id' => $orderInfo['merchant_id'] ?? 0,
            'return_type' => $data['return_type'] ?? 'logistics',
            'return_distributor_id' => $data['distributor_id'] ?? 0,
            'freight' => $data['freight'] ?? 0,
        ];
        $refund_status = 'READY';
        if ($data['aftersales_type'] == 'ONLY_REFUND' || $data['goods_returned']) {
            $aftersales_data['progress'] = 4;
            $aftersales_data['aftersales_status'] = 2;
            $refund_status = 'AUDIT_SUCCESS';
        } else {
            // $aftersales_data['progress'] = 1;
            // $aftersales_data['aftersales_status'] = 1;
            //获取售后时效时间
            // $autoRefuseTime = intval($this->getOrdersSetting($data['company_id'], 'auto_refuse_time'));
            // if ($autoRefuseTime > 0) {
            //     $aftersales_data['auto_refuse_time'] = strtotime("+$autoRefuseTime day", time());
            // } else {
            //     $aftersales_data['auto_refuse_time'] = time();
            // }
        }

        $orderProfitService = new OrderProfitService();
        $brokerageService = new BrokerageService();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 创建售后单
            $total_refund_fee = 0;
            $total_refund_point = 0;
            $total_return_point = 0;
            $apply_num = 0;
            foreach ($data['detail'] as $v) {
                $suborder_filter = [
                    'company_id' => $data['company_id'],
                    'user_id' => $data['user_id'],
                    'order_id' => $data['order_id'],
                    'id' => $v['id'],
                ];
                $subOrderInfo = $normalOrderService->getSimpleSubOrderInfo($suborder_filter);
                $applied_num = $this->getAppliedNum($data['company_id'], $data['order_id'], $v['id']); // 已申请数量
                $applied_refund_fee = $this->getAppliedTotalRefundFee($data['company_id'], $data['order_id'], $v['id']); // 已申请退款总金额
                $applied_refund_point = $this->getAppliedRefundPoint($data['company_id'], $data['order_id'], $v['id']); // 已申请退款总积分
                if ($v['num'] == $subOrderInfo['num']) { // 子订单 全部 退货
                    $refund_fee = $subOrderInfo['total_fee'];
                    $refund_point = $subOrderInfo['point'];
                } else { // 子订单 部分 退货
                    $left_num = $subOrderInfo['num'] - $applied_num - $v['num'];
                    if ($left_num == 0) { // 申请的是本明细剩余的所有数量
                        $refund_fee = $subOrderInfo['total_fee'] - $applied_refund_fee;
                        $refund_point = $subOrderInfo['point'] - $applied_refund_point;
                    } elseif ($left_num > 0) { // 还有没申请的商品的时候  通过除法来计算退款金额，向下取整
                        $refund_fee = floor(bcmul(bcdiv($subOrderInfo['total_fee'], $subOrderInfo['num'], 2), $v['num']));
                        $refund_point = floor(bcmul(bcdiv($subOrderInfo['point'], $subOrderInfo['num'], 2), $v['num']));
                    } else {
                        throw new ResourceException('申请售后单数据异常');
                    }
                }

                $total_return_point += $this->getReturnPoint($subOrderInfo, $v['num'], $applied_num);
                $total_refund_fee += $refund_fee;
                $total_refund_point += $refund_point;
                $aftersales_detail_data = [
                    'company_id' => $data['company_id'],
                    'user_id' => $data['user_id'],
                    'distributor_id' => $orderInfo['distributor_id'],
                    'aftersales_bn' => $aftersales_bn,
                    'order_id' => $data['order_id'],
                    'sub_order_id' => $v['id'],
                    'goods_id' => $subOrderInfo['goods_id'],
                    'item_id' => $subOrderInfo['item_id'],
                    'item_bn' => $subOrderInfo['item_bn'],
                    'item_pic' => $subOrderInfo['pic'],
                    'refund_fee' => $refund_fee,
                    'refund_point' => $refund_point,
                    'item_name' => $subOrderInfo['item_name'],
                    'order_item_type' => $subOrderInfo['order_item_type'],
                    'num' => $v['num'],
                    'aftersales_type' => $data['aftersales_type'],
                    'progress' => $aftersales_data['progress'],
                    'aftersales_status' => $aftersales_data['aftersales_status'],
                ];
                if ($subOrderInfo['item_spec_desc']) {
                    $aftersales_detail_data['item_name'] = $subOrderInfo['item_name'] . '(' . $subOrderInfo['item_spec_desc'] . ')';
                }
                // 创建售后明细
                $aftersales_detail = $this->aftersalesDetailRepository->create($aftersales_detail_data);
                $apply_num += $v['num'];
                //使分润失效
                $orderProfitService->orderItemsProfitRepository->updateBy(['order_id' => $data['order_id'], 'company_id' => $data['company_id'], 'item_id' => $aftersales_detail_data['item_id']], ['order_profit_status' => 0]);
                if ($data['aftersales_type'] == 'ONLY_REFUND' || $data['goods_returned']) {
                    //分销退佣金
                    $brokerageService->brokerageByAftersalse($data['company_id'], $data['order_id'], $aftersales_detail_data['item_id'], $aftersales_detail_data['num']);
                }

                //一次只能申请一个商品，把子订单的供应商ID保存在售后主表
                $aftersales_data['supplier_id'] = $subOrderInfo['supplier_id'];
                $aftersales_data['item_bn'] = $subOrderInfo['item_bn'];
            }
            if ($data['refund_fee'] > $total_refund_fee) {
                throw new ResourceException('退款金额不能超过可退金额! '.$data['refund_fee'].' > '.$total_refund_fee);
            }

            if ($data['refund_point'] > $total_refund_point) {
                throw new ResourceException('退还积分不能超过可退积分');
            }

            $aftersales_data['refund_fee'] = $data['refund_fee'];
            $aftersales_data['refund_point'] = $data['refund_point'];

            // 创建售后主单据
            $aftersales = $this->aftersalesRepository->create($aftersales_data);
            $left_aftersales_num = $orderInfo['left_aftersales_num'] - $apply_num;
            $normalOrderService->normalOrdersRepository->update(['company_id' => $data['company_id'], 'order_id' => $data['order_id']], ['left_aftersales_num' => $left_aftersales_num]);

            $tradeService = new TradeService();
            $trade = $tradeService->getInfo(['company_id' => $data['company_id'], 'order_id' => $data['order_id'], 'trade_state' => 'SUCCESS']);
            // 创建售后退款单
            $aftersalesRefundService = new AftersalesRefundService();
            $refundData = [
                'company_id' => $aftersales_data['company_id'],
                'user_id' => $aftersales_data['user_id'],
                'aftersales_bn' => $aftersales_data['aftersales_bn'],
                'order_id' => $aftersales_data['order_id'],
                'trade_id' => $trade['trade_id'], // 已支付交易单号
                'shop_id' => $aftersales_data['shop_id'] ?? 0,
                'distributor_id' => $aftersales_data['distributor_id'] ?? 0,
                'refund_type' => 0, // 0 售后申请退款
                'refund_channel' => $trade['pay_type'] == 'offline_pay' ? 'offline' : 'original', // 默认取消订单原路返回,pay_type=offline_pay为线下退款
                'refund_fee' => $data['refund_fee'],
                'refund_point' => $data['refund_point'],
                'return_freight' => $freight ? 1 : 0, // 0 不退运费
                'pay_type' => $orderInfo['pay_type'],
                'currency' => $trade['fee_type'],
                'cur_fee_type' => $trade['cur_fee_type'],
                'cur_fee_rate' => $trade['cur_fee_rate'],
                'cur_fee_symbol' => $trade['cur_fee_symbol'],
                'cur_pay_fee' => $data['refund_fee'] * $trade['cur_fee_rate'], // trade表没有单独积分字段，所以这样写
                'return_point' => $total_return_point,
                'merchant_id' => $orderInfo['merchant_id'] ?? 0,
                'refund_status' => $refund_status,
                'freight' => $freight,
                
            ];
            $refund = $aftersalesRefundService->createAftersalesRefund($refundData);

            if ($data['aftersales_type'] == 'ONLY_REFUND' || $data['goods_returned']) {
                $couponjob = (new OrderRefundCompleteJob($data['company_id'], $data['order_id']))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($couponjob);
            }

            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $data['company_id'],
                'operator_type' => $data['operator_type'],
                'operator_id' => $data['operator_id'],
                'remarks' => '订单售后',
                'detail' => '售后单号：' . $aftersales_bn . ' 后台申请售后，申请原因：' . $data['reason'],
                'params' => $data,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        // 统计数据更新
        $date = date('Ymd');
        $redisKey = 'OrderPayStatistics:normal:' . $data['company_id'] . ':' . $date;
        app('redis')->hincrby($redisKey, 'orderAftersales', 1);
        if (isset($orderInfo['distributor_id'])) {
            app('redis')->hincrby($redisKey, $orderInfo['distributor_id'] . '_orderAftersales', 1);
        }
        if (!empty($orderInfo['merchant_id'])) {
            app('redis')->hincrby($redisKey, $orderInfo['merchant_id'] . '_merchant_orderAftersales', 1);
        }

        //联通OME售后申请埋点
        if ($data['aftersales_type'] == 'REFUND_GOODS' || $data['aftersales_type'] == 'EXCHANGING_GOODS') {
            event(new SaasErpAftersalesEvent($aftersales)); // SaasErp 售后申请 退款退货
        } else {
            event(new SaasErpRefundEvent($refund));// SaasErp 售后申请 仅退款
            //退款成功推发票冲红
            app('log')->info('[aftersales refund success] 退款成功推发票冲红 aftersales:'.json_encode($aftersales));
            dispatch(new \OrdersBundle\Jobs\InvoiceRedJob($aftersales))->onQueue('invoice');
        }

        return $aftersales;
    }

    // 售后申请按num拆单
    public function shopApplyByNum($data)
    {
        // 检查是否可以申请售后
        $this->__checkApply($data);
        $filter = [
            'company_id' => $data['company_id'],
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
        ];
        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->getSimpleOrderInfo($filter);
        
        $tradeService = new TradeService();
        $trade = $tradeService->getInfo(['company_id' => $data['company_id'], 'order_id' => $orderInfo['order_id'], 'trade_state' => 'SUCCESS']);

        foreach ($data['detail'] as $v) {
            $detailTmp = $v;
            for ($i=0; $i < $v['num']; $i++) { 
                 // 如果输入了退款子订单总金额，则平均分配
                if (isset($detailTmp['total_fee']) && $detailTmp['total_fee'] > 0) {
                    $detailTmp['total_fee'] = floor(bcdiv(bcmul($detailTmp['total_fee'],100), $detailTmp['num'], 2));
                }
                if (isset($detailTmp['total_point']) && $detailTmp['total_point'] > 0) {
                    $detailTmp['total_point'] = floor(bcdiv(bcmul($detailTmp['total_point'],100), $detailTmp['num'], 2));   
                }
                $detail = $detailTmp;
                $detail['num'] = 1;
                $data['detail'] = [$detail];
                $this->shopApplyByNumHandle($orderInfo, $trade, $data);
            }
        }

        return true;
    }

    private function shopApplyByNumHandle($orderInfo, $trade, $data) 
    {
        // 是否开启退货退款退运费
        $is_refund_freight = $this->getOrdersSetting($data['company_id'], 'is_refund_freight');
        $normalOrderService = new NormalOrderService();
        $aftersales_bn = $this->__genAftersalesBn();
        $aftersales_data = [
            'aftersales_bn' => $aftersales_bn,
            'shop_id' => $orderInfo['shop_id'],
            'order_id' => $data['order_id'],
            'company_id' => $data['company_id'],
            'user_id' => $data['user_id'],
            'distributor_id' => $orderInfo['distributor_id'],
            'aftersales_type' => $data['aftersales_type'],
            'aftersales_status' => 0,
            'progress' => 0,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? '',
            'evidence_pic' => $data['evidence_pic'] ?? [],
            'salesman_id' => $data['salesman_id'] ?? 0,
            'contact' => $data['contact'] ?? '',
            'mobile' => $orderInfo['mobile'] ?? '',
            'merchant_id' => $orderInfo['merchant_id'] ?? 0,
            'return_type' => $data['return_type'] ?? 'logistics',
            'return_distributor_id' => $data['distributor_id'] ?? 0,
            'freight' => 0,
        ];
        $refund_status = 'READY';
        if ($data['aftersales_type'] == 'ONLY_REFUND' || $data['goods_returned']) {
            // $aftersales_data['progress'] = 4;
            // $aftersales_data['aftersales_status'] = 2;
            // $refund_status = 'AUDIT_SUCCESS';
        } else {
            // $aftersales_data['progress'] = 1;
            // $aftersales_data['aftersales_status'] = 1;
            //获取售后时效时间
            // $autoRefuseTime = intval($this->getOrdersSetting($data['company_id'], 'auto_refuse_time'));
            // if ($autoRefuseTime > 0) {
            //     $aftersales_data['auto_refuse_time'] = strtotime("+$autoRefuseTime day", time());
            // } else {
            //     $aftersales_data['auto_refuse_time'] = time();
            // }
        }

        $orderProfitService = new OrderProfitService();
        $brokerageService = new BrokerageService();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 创建售后单
            $total_refund_fee = 0;
            $total_refund_point = 0;
            $total_return_point = 0;
            $apply_num = 0;
            $sub_item_fee = 0; // 本次申请子订单单个金额
            $sub_item_point = 0; // 本次申请子订单单个积分
            $is_refund_freight_flag = false; //单个，如果以后多个则为数组
            foreach ($data['detail'] as $v) {
                $suborder_filter = [
                    'company_id' => $data['company_id'],
                    'user_id' => $data['user_id'],
                    'order_id' => $data['order_id'],
                    'id' => $v['id'],
                ];
                $subOrderInfo = $normalOrderService->getSimpleSubOrderInfo($suborder_filter);
                // 如果输入了总金额，以输入的为准
                if (isset($v['total_fee']) && $v['total_fee'] > 0) {
                    $sub_item_fee = $v['total_fee'];
                }else{
                    $sub_item_fee = floor(bcmul(bcdiv($subOrderInfo['total_fee'], $subOrderInfo['num'], 2), $v['num']));
                }
                 if (isset($v['total_point']) && $v['total_point'] > 0) {
                    $sub_item_point = $v['total_point'];
                }else{
                    $sub_item_point = floor(bcmul(bcdiv($subOrderInfo['point'], $subOrderInfo['num'], 2), $v['num']));
                }
                $applied_num = $this->getAppliedNum($data['company_id'], $data['order_id'], $v['id']); // 已申请数量
                $applied_refund_fee = $this->getAppliedTotalRefundFee($data['company_id'], $data['order_id'], $v['id']); // 已申请退款总金额
                $applied_refund_point = $this->getAppliedRefundPoint($data['company_id'], $data['order_id'], $v['id']); // 已申请退款总积分
                if ($v['num'] == $subOrderInfo['num']) { // 子订单 全部 退货
                    $refund_fee = $subOrderInfo['total_fee'];
                    $refund_point = $subOrderInfo['point'];
                } else { // 子订单 部分 退货
                    $left_num = $subOrderInfo['num'] - $applied_num - $v['num'];
                    if ($left_num == 0) { // 申请的是本明细剩余的所有数量
                        $refund_fee = $subOrderInfo['total_fee'] - $applied_refund_fee;
                        $sub_item_fee = $refund_fee;
                        $refund_point = $subOrderInfo['point'] - $applied_refund_point;
                        $sub_item_point = $refund_point;
                    } elseif ($left_num > 0) { // 还有没申请的商品的时候  通过除法来计算退款金额，向下取整
                        $refund_fee = floor(bcmul(bcdiv($subOrderInfo['total_fee'], $subOrderInfo['num'], 2), $v['num']));
                        $refund_point = floor(bcmul(bcdiv($subOrderInfo['point'], $subOrderInfo['num'], 2), $v['num']));
                    } else {
                        throw new ResourceException('申请售后单数据异常');
                    }
                }
                $total_return_point += $this->getReturnPoint($subOrderInfo, $v['num'], $applied_num);
                $total_refund_fee += $refund_fee;
                $total_refund_point += $refund_point;
                $aftersales_detail_data = [
                    'company_id' => $data['company_id'],
                    'user_id' => $data['user_id'],
                    'distributor_id' => $orderInfo['distributor_id'],
                    'aftersales_bn' => $aftersales_bn,
                    'order_id' => $data['order_id'],
                    'sub_order_id' => $v['id'],
                    'goods_id' => $subOrderInfo['goods_id'],
                    'item_id' => $subOrderInfo['item_id'],
                    'item_bn' => $subOrderInfo['item_bn'],
                    'item_pic' => $subOrderInfo['pic'],
                    'refund_fee' => $sub_item_fee,
                    'refund_point' => $sub_item_point,
                    'item_name' => $subOrderInfo['item_name'],
                    'order_item_type' => $subOrderInfo['order_item_type'],
                    'num' => $v['num'],
                    'aftersales_type' => $data['aftersales_type'],
                    'progress' => $aftersales_data['progress'],
                    'aftersales_status' => $aftersales_data['aftersales_status'],
                ];
                if ($subOrderInfo['item_spec_desc']) {
                    $aftersales_detail_data['item_name'] = $subOrderInfo['item_name'] . '(' . $subOrderInfo['item_spec_desc'] . ')';
                }
                // 创建售后明细
                $aftersales_detail = $this->aftersalesDetailRepository->create($aftersales_detail_data);
                $apply_num += $v['num'];
                //使分润失效
                $orderProfitService->orderItemsProfitRepository->updateBy(['order_id' => $data['order_id'], 'company_id' => $data['company_id'], 'item_id' => $aftersales_detail_data['item_id']], ['order_profit_status' => 0]);
                if ($data['aftersales_type'] == 'ONLY_REFUND' || $data['goods_returned']) {
                    //分销退佣金
                    $brokerageService->brokerageByAftersalse($data['company_id'], $data['order_id'], $aftersales_detail_data['item_id'], $aftersales_detail_data['num']);
                }
                //一次只能申请一个商品，把子订单的供应商ID保存在售后主表
                $aftersales_data['supplier_id'] = $subOrderInfo['supplier_id'];
                $aftersales_data['item_bn'] = $subOrderInfo['item_bn'];

                $is_refund_freight_flag = $this->isRefundFinishByNum($data['order_id'], $data['company_id'],$v['id'], $v['num']);
            }
            if ($sub_item_fee > $total_refund_fee) {
                throw new ResourceException('售后申请金额不能超过剩余金额! '.$sub_item_fee.' > '.$total_refund_fee);
            }
            if ( $sub_item_point > $total_refund_point) {
                throw new ResourceException('售后申请积分不能超过剩余积分! '.$sub_item_point.' > '.$total_refund_point);
            }
            $aftersales_data['refund_fee'] = $sub_item_fee;
            $aftersales_data['refund_point'] = $sub_item_point;

            // 判断是否是最后一个售后数据了
            $freight = 0;
            $freight_point = 0;
            if ($is_refund_freight && $is_refund_freight_flag) {
                // 根据运费类型处理运费
                if ($orderInfo['freight_type'] == 'cash') {
                    // 现金运费
                    $max_freight = $orderInfo['freight_fee'];
                    $refunded_freight = $this->getAppliedTotalRefundFreight($data['company_id'], $data['order_id']);
                    $remain_freight = $max_freight - $refunded_freight;
                    $max_freight = min($max_freight, $remain_freight);
                    $freight = isset($data['freight']) && $data['freight'] > 0 && $data['freight'] <= $max_freight ? $data['freight'] : $max_freight;
                    $aftersales_data['freight'] = $freight;
                    $aftersales_data['freight_point'] = 0;
                } else if ($orderInfo['freight_type'] == 'point') {
                    // 积分运费
                    $max_freight_point = $orderInfo['freight_point'];
                    $refunded_freight_point = $this->getAppliedTotalRefundFreightPoint($data['company_id'], $data['order_id']);
                    $remain_freight_point = $max_freight_point - $refunded_freight_point;
                    $max_freight_point = min($max_freight_point, $remain_freight_point);
                    $freight_point = isset($data['freight']) && $data['freight'] > 0 && $data['freight'] <= $max_freight_point ? $data['freight'] : $max_freight_point;
                    // 积分运费单独存储，不加到 refund_point 中（在退款时 doRefund() 会处理）
                    $aftersales_data['freight'] = 0;
                    $aftersales_data['freight_point'] = $freight_point;
                }
                // 设置运费类型
                $aftersales_data['freight_type'] = $orderInfo['freight_type'];
            } else {
                // 未申请运费时，设置默认值
                $aftersales_data['freight_type'] = $orderInfo['freight_type'] ?? 'cash';
                $aftersales_data['freight'] = 0;
                $aftersales_data['freight_point'] = 0;
            }
            // 创建售后主单据
            $aftersales = $this->aftersalesRepository->create($aftersales_data);
            $left_aftersales_num = $orderInfo['left_aftersales_num'] - $apply_num;
            $normalOrderService->normalOrdersRepository->update(['company_id' => $data['company_id'], 'order_id' => $data['order_id']], ['left_aftersales_num' => $left_aftersales_num]);
            // 创建售后退款单
            $aftersalesRefundService = new AftersalesRefundService();
            $refundData = [
                'company_id' => $aftersales_data['company_id'],
                'user_id' => $aftersales_data['user_id'],
                'aftersales_bn' => $aftersales_data['aftersales_bn'],
                'order_id' => $aftersales_data['order_id'],
                'trade_id' => $trade['trade_id'], // 已支付交易单号
                'shop_id' => $aftersales_data['shop_id'] ?? 0,
                'distributor_id' => $aftersales_data['distributor_id'] ?? 0,
                'refund_type' => 0, // 0 售后申请退款
                'refund_channel' => $trade['pay_type'] == 'offline_pay' ? 'offline' : 'original', // 默认取消订单原路返回,pay_type=offline_pay为线下退款
                'refund_fee' => $sub_item_fee,
                'refund_point' => $sub_item_point,
                'return_freight' => 0, // 0 不退运费
                'pay_type' => $orderInfo['pay_type'],
                'currency' => $trade['fee_type'],
                'cur_fee_type' => $trade['cur_fee_type'],
                'cur_fee_rate' => $trade['cur_fee_rate'],
                'cur_fee_symbol' => $trade['cur_fee_symbol'],
                'cur_pay_fee' => $sub_item_fee * $trade['cur_fee_rate'], // trade表没有单独积分字段，所以这样写
                'return_point' => $total_return_point,
                'merchant_id' => $orderInfo['merchant_id'] ?? 0,
                'refund_status' => $refund_status,  
                'freight' => 0,
            ];
            // 判断是否是最后一个售后数据了
            if ($is_refund_freight && $is_refund_freight_flag) {
                // 根据freight_type存储现金（分）或积分值，与订单表逻辑一致
                $refundData['freight'] = $freight;
                $refundData['return_freight'] = 1;
                $refundData['freight_type'] = $orderInfo['freight_type'];
            } else {
                // 未申请运费时，设置默认值
                $refundData['freight_type'] = $orderInfo['freight_type'] ?? 'cash';
                $refundData['freight'] = 0;
            }
            $refund = $aftersalesRefundService->createAftersalesRefund($refundData);
            if ($data['aftersales_type'] == 'ONLY_REFUND' || $data['goods_returned']) {
                $couponjob = (new OrderRefundCompleteJob($data['company_id'], $data['order_id']))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($couponjob);
            }
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $data['company_id'],
                'operator_type' => $data['operator_type'],
                'operator_id' => $data['operator_id'],
                'remarks' => '订单售后',
                'detail' => '售后单号：' . $aftersales_bn . ' 后台申请售后，申请原因：' . $data['reason'],
                'params' => $data,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        // 统计数据更新
        $date = date('Ymd');
        $redisKey = 'OrderPayStatistics:normal:' . $data['company_id'] . ':' . $date;
        app('redis')->hincrby($redisKey, 'orderAftersales', 1);
        if (isset($orderInfo['distributor_id'])) {
            app('redis')->hincrby($redisKey, $orderInfo['distributor_id'] . '_orderAftersales', 1);
        }
        if (!empty($orderInfo['merchant_id'])) {
            app('redis')->hincrby($redisKey, $orderInfo['merchant_id'] . '_merchant_orderAftersales', 1);
        }

        //联通OME售后申请埋点
        if ($data['aftersales_type'] == 'REFUND_GOODS' || $data['aftersales_type'] == 'EXCHANGING_GOODS') {
            event(new TradeAftersalesEvent($aftersales)); // 退款退货 或换货
            event(new SaasErpAftersalesEvent($aftersales)); // SaasErp 售后申请 退款退货
        } else {
            event(new TradeRefundEvent($refund)); // 售后仅退款
            event(new SaasErpRefundEvent($refund));// SaasErp 售后申请 仅退款
            //退款成功推发票冲红
            app('log')->info('[aftersales refund success] 退款成功推发票冲红 aftersales:'.json_encode($aftersales));
            dispatch(new \OrdersBundle\Jobs\InvoiceRedJob($aftersales))->onQueue('invoice');
        }

        return $aftersales;
    }   
    
    // 判断售后单是否按num拆单全部完成
    public function isRefundFinishByNum($order_id, $company_id, $nowId, $nowNum)
    {
        /**
         *  判断所有商品是否已经退到最后一件
         *  注意：这里只判断数量，不考虑运费（运费校验在调用处单独处理）
         */
        $normalOrderService = new NormalOrderService();
        $_filter = [
            'company_id' => $company_id,
            'order_id' => $order_id
        ];
        $orderItems = $normalOrderService->normalOrdersItemsRepository->getList($_filter);
        $flag = false;
        if (!empty($orderItems['list'])) {
            foreach ($orderItems['list'] as $v) {
                if ($v['id'] == $nowId) {
                    $applied_num = $this->getAppliedNumByNum($company_id, $order_id, $v['id']); // 已申请数量（只统计有效状态的售后单）
                    if ($nowNum + $applied_num < $v['num']) {
                        $flag = false;
                        break;
                    }
                }else {
                    $applied_num = $this->getAppliedNumByNum($company_id, $order_id, $v['id']); // 已申请数量（只统计有效状态的售后单）
                    if ($applied_num < $v['num']) {
                        $flag = false;
                        break;
                    }
                }
               
                $flag = true;
            }
        }

        return $flag;
    }

    /**
     * 商家确认收货
     */
    public function confirmReceipt($params)
    {
        $filter = [
            'aftersales_bn' => $params['aftersales_bn'],
            'company_id' => $params['company_id']
        ];
        $aftersales = $this->aftersalesRepository->get($filter);
        if(!$aftersales) {
            throw new ResourceException("售后单数据异常");
        }
        if(!in_array($aftersales['aftersales_status'],['0','1'])) {
            throw new ResourceException("售后{$params['aftersales_bn']}已处理");
        }
        // $afterdetailList = $this->aftersalesDetailRepository->getList(['detail_id'=>$aftersales['detail_id']]);
    // public function getList($filter, $offset = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'])
        if(!in_array($aftersales['progress'],['1', '2'])) {
            throw new ResourceException("售后{$params['aftersales_bn']}商家没有接受申请");
        }
        // 增对单个num售后详情
        $filterDetail = [
            'aftersales_bn' => $params['aftersales_bn'],
            'company_id' => $params['company_id']
        ];
        $aftersalesDetail = $this->aftersalesDetailRepository->get($filterDetail);

        $update = [
            'progress' => '8', //商家确认收货
            'aftersales_status' => '1', //处理中
        ];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->aftersalesRepository->update($filter, $update);
            // 增加实际入库数量
            $update['refunded_num'] = $aftersalesDetail['num']; //并未返回时间入库数量，商家确定，就按申请数量为准
            $this->aftersalesDetailRepository->update($filter, $update);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $aftersales;
    }

    /**
     * 商家取消确认收货
     */
    public function cancelReceipt($params)
    {
        $filter = [
            'aftersales_bn' => $params['aftersales_bn'],
            'company_id' => $params['company_id']
        ];
        $aftersales = $this->aftersalesRepository->get($filter);
        if(!$aftersales) {
            throw new ResourceException("售后单数据异常");
        }
        if($aftersales['aftersales_status'] != '1') {
            throw new ResourceException("售后{$params['aftersales_bn']}已处理或还未处理");
        }
        $afterdetail = $this->aftersalesDetailRepository->get(['detail_id'=>$aftersales['detail_id']]);
        if($afterdetail['progress'] != '8') {
            throw new ResourceException("售后{$params['aftersales_bn']}商家没有确认收货");
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $progress = '1'; //商家接受申请，等待消费者回寄
            if ($afterdetail['sendback_data']) {
                $progress = '2'; //消费者回寄，等待商家收货确认
            }

            $update = [
                'progress' => $progress,
                'aftersales_status' => '1', //处理中
            ];
            $this->aftersalesRepository->update($filter, $update);
            $this->aftersalesDetailRepository->update($filter, $update);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        return $aftersales;
    }

    public function getAftersaleRefundInfoByOrderIdItemId($orderId, $itemId, $companyId, $supplier_id = '')
    {
        // 如果是支付取消订单，那就是原路返回子订单数据，否则就是走售后数据
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('sum(C.refunded_fee) as refunded_fee,sum(C.refunded_point) as refunded_point,sum(C.freight) as freight,C.refund_success_time,A.order_id,A.item_id,sum(A.num) as num,sum(A.refund_fee) as refund_fee,sum(A.refund_point) as refund_point')
            ->from('aftersales_detail', 'A')
            ->leftjoin('A','aftersales_refund', 'C', 'A.aftersales_bn = C.aftersales_bn')
            ->where($qb->expr()->eq('A.company_id', $companyId))
            ->andWhere($qb->expr()->eq('A.order_id', $qb->expr()->literal($orderId)))
            ->andWhere($qb->expr()->eq('A.item_id',$qb->expr()->literal($itemId)))
            ->andWhere($qb->expr()->in('C.refund_status', array_map(function($v) use ($qb) { 
                return $qb->expr()->literal($v);
            }, ["AUDIT_SUCCESS", "SUCCESS"] ))); // REFUND_SUCCESS
        if ($supplier_id) {
            $qb = $qb->andwhere($qb->expr()->eq('C.supplier_id',$qb->expr()->literal($supplier_id)));
        }    
        $info = $qb->execute()->fetch();
        
        return $info ?? [];
    }

     public function getAftersaleRefundInfoByOrderId($orderId, $companyId, $supplier_id = '')
    {
        // 如果是支付取消订单，那就是原路返回子订单数据，否则就是走售后数据
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('sum(C.refunded_fee) as refunded_fee,sum(C.refunded_point) as refunded_point,sum(C.freight) as freight,C.refund_success_time,A.order_id,sum(COALESCE(A.num, 0)) as num, sum(D.cost_price * COALESCE(A.num, 0)) as refund_cost_fee, sum(A.refund_fee) as refund_fee, sum(A.refund_point) as refund_point')
            ->from('aftersales_detail', 'A')    
            ->leftjoin('A','aftersales_refund', 'C', 'A.aftersales_bn = C.aftersales_bn')
            ->leftjoin('A','items', 'D', 'A.item_id = D.item_id')
            ->where($qb->expr()->eq('A.company_id', $companyId))
            ->andWhere($qb->expr()->eq('A.order_id', $qb->expr()->literal($orderId)))
            ->andWhere($qb->expr()->in('C.refund_status', array_map(function($v) use ($qb) { 
                return $qb->expr()->literal($v);
            }, ["AUDIT_SUCCESS", "SUCCESS"] ))); // REFUND_SUCCESS
        if ($supplier_id) {
            $qb = $qb->andwhere($qb->expr()->eq('C.supplier_id',$qb->expr()->literal($supplier_id)));
        }    
        $qb = $qb->groupBy('A.order_id');
        $info = $qb->execute()->fetch();
            
        return $info ?? [];
    }

     public function getSuccAftersaleRefundInfoByOrderIdList($companyId, $orderIdList)
    {
        if (empty($orderIdList)) {
            return [];
        }
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderIdList,
            'refund_status' => 'SUCCESS',
        ];

        $tradeList = $this->aftersalesRefundRepository->getList($filter);

        $indexTrade = [];
        foreach ($tradeList['list'] as $value) {
            $indexTrade[$value['order_id']] = $value;
        }
        
        return $indexTrade;
    }

    // 根据afterBns获取售后详情商品数据，仅用单个拆单数据
    public function getItemsByAftersalesBnByNun($company_id, $aftersalesBns = [])
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('ad.aftersales_bn,i.item_id,i.item_name,i.item_bn')
            ->from('aftersales_detail', 'ad')
            ->leftJoin('ad', 'items', 'i', 'ad.item_id = i.item_id')
            ->where($qb->expr()->eq('ad.company_id', $company_id))
            ->andWhere($qb->expr()->in('ad.aftersales_bn', array_map(function($bn) { 
               return "'" . $bn . "'";
            }, $aftersalesBns)));
        $list = $qb->execute()->fetchAll();
        
        return $list ?? [];
    }

}
