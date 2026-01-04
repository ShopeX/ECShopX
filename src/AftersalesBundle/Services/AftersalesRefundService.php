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

use PaymentBundle\Services\Payments\AdaPaymentService;
use Dingo\Api\Exception\ResourceException;

use AftersalesBundle\Entities\Aftersales;
use AftersalesBundle\Entities\AftersalesDetail;
use AftersalesBundle\Entities\AftersalesRefund;
use AftersalesBundle\Entities\AftersalesOfflineRefund;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Entities\NormalOrdersItems;

use MembersBundle\Services\MemberService;
use PaymentBundle\Services\Payments\AlipayService;
use PaymentBundle\Services\Payments\DepositPayService;
use PaymentBundle\Services\Payments\HfPayService;
use PaymentBundle\Services\Payments\PointPayService;
use PaymentBundle\Services\Payments\PosPayService;
use OrdersBundle\Services\TradeService;
use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\WechatPayService;
use AftersalesBundle\Jobs\RefundJob;
use PointBundle\Services\PointMemberService;

use AftersalesBundle\Traits\GetRefundBnTrait;
use ThirdPartyBundle\Events\TradeRefundFinishEvent;
use DistributionBundle\Services\DistributorService;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use PaymentBundle\Services\Payments\BsPayService;
use PaymentBundle\Services\Payments\OfflinePayService;

class AftersalesRefundService
{
    use GetRefundBnTrait;

    public $aftersalesRepository;
    public $aftersalesDetailRepository;
    /** @var \AftersalesBundle\Repositories\AftersalesRefundRepository */
    public $aftersalesRefundRepository;

    public function __construct()
    {
        $this->aftersalesRepository = app('registry')->getManager('default')->getRepository(Aftersales::class);
        $this->aftersalesDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class);
        $this->aftersalesRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        // ShopEx EcShopX Service Component
        return $this->aftersalesRefundRepository->$method(...$parameters);
    }

    /**
     * 调用外部退款接口
     *
     * @param params 退款单过滤条件
     * @param resubmit 是否是异常再退款，是的话则不退已经退成功的积分等信息
     * @return array
     **/
    public function doRefund($params, $resubmit = false)
    {
        $filter = [
            'refund_bn' => $params['refund_bn'],
            'company_id' => $params['company_id'],
        ];
        $refundData = $this->aftersalesRefundRepository->getInfo($filter);
        //退款金额要增加运费（根据运费类型分别处理，与订单表逻辑一致）
        if (($refundData['freight_type'] ?? 'cash') == 'cash') {
            // 现金运费（分）加到退款金额中
            $refundData['refund_fee'] += $refundData['freight'] ?? 0;
        } else if (($refundData['freight_type'] ?? 'cash') == 'point') {
            // 积分运费（积分值）加到退款积分中
            $refundData['refund_point'] += $refundData['freight'] ?? 0;
        }
        
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfo(['company_id' => $params['company_id'], 'trade_id' => $refundData['trade_id'], 'trade_state' => 'SUCCESS']);
        $tradeInfo['wxaAppid'] = '';
        $refundData['pay_fee'] = $tradeInfo['pay_fee']; // 支付单原来支付总金额,用于某些支付需要传原始支付金额
        if (($refundData['pay_type'] != 'point') && ($refundData['refund_fee'] <= 0)) { //退款金额为0的直接返回成功，主要是第三方支付，积分支付走的是refund_point，所以直接过，不影响流程
            $res['status'] = 'SUCCESS';
            $res['refund_id'] = '';
        } else {
            switch (strtolower($refundData['pay_type'])) {
                // 微信支付
                case 'wxpay':
                case 'wxpaypc':
                case 'wxpayh5':
                case 'wxpayapp':
                case 'wxpayjs':
                case 'wxpaypos':
                    $paymentsService = new PaymentsService(new WechatPayService($refundData['distributor_id']));
                    $res = $paymentsService->doRefund($params['company_id'], $tradeInfo['wxaAppid'], $refundData);
                    break;
                // 支付宝支付
                case 'alipay':
                case 'alipayapp':
                case 'alipayh5':
                case 'alipaypos':
                case 'alipaymini':
                    $paymentsService = new PaymentsService(new AlipayService($refundData['distributor_id']));
                    $res = $paymentsService->doRefund($params['company_id'], $tradeInfo['wxaAppid'], $refundData);
                    break;
                // 积分支付
                case 'point':
                    $paymentsService = new PaymentsService(new PointPayService());
                    $res = $paymentsService->doRefund($params['company_id'], $tradeInfo['wxaAppid'], $refundData);
                    break;
                // 0元订单
                case 'localpay':
                    // 直接退款完成
                    throw new ResourceException("0元订单不支持退款");
                    break;
                // 预存款
                case 'deposit':
                    $paymentsService = new PaymentsService(new DepositPayService());
                    $res = $paymentsService->doRefund($params['company_id'], $tradeInfo['wxaAppid'], $refundData);
                    break;
                case 'pos':
                    $paymentsService = new PaymentsService(new PosPayService());
                    $res = $paymentsService->doRefund($params['company_id'], $tradeInfo['wxaAppid'], $refundData);
                    break;
                case 'hfpay':
                    $paymentsService = new PaymentsService(new HfPayService());
                    $res = $paymentsService->doRefund($params['company_id'], $tradeInfo['wxaAppid'], $refundData);
                    break;
                case 'adapay':
                    $paymentsService = new PaymentsService(new AdaPaymentService());
                    $res = $paymentsService->doRefund($params['company_id'], $tradeInfo['wxaAppid'], $refundData);
                    break;
                case 'chinaums':
                    $paymentsService = new PaymentsService(new ChinaumsPayService());
                    $res = $paymentsService->doRefund($params['company_id'], $tradeInfo['wxaAppid'], $refundData, $resubmit);
                    break;
                case 'bspay':
                    $refundData['bspay_req_date'] = $tradeInfo['bspay_req_date'];
                    $refundData['transaction_id'] = $tradeInfo['transaction_id'];
                    $paymentsService = new PaymentsService(new BsPayService());
                    $res = $paymentsService->doRefund($params['company_id'], $tradeInfo['wxaAppid'], $refundData);
                    break;
                case 'offline_pay':
                    $paymentsService = new PaymentsService(new OfflinePayService());
                    $res = $paymentsService->doRefund($params['company_id'], $tradeInfo['wxaAppid'], $refundData);
                    break;
                default:
                    throw new ResourceException("未知的支付方式");
                    break;
            }
        }

        // 处理积分组合支付订单售后退积分
        if (($refundData['pay_type'] != 'point') && ($refundData['refund_point'] > 0) && !$resubmit) {
            $pointMemberService = new PointMemberService();
            $otherParams = ['point_type' => 'points_refund', 'refund_bn' => $refundData['refund_bn'], 'aftersales_bn' => $refundData['aftersales_bn'] ?? '' ];
            $pointMemberService->addPoint($refundData['user_id'], $refundData['company_id'], $refundData['refund_point'], 10, true, '退款单号:'.$refundData['refund_bn'], $refundData['order_id'], $otherParams);
        }

        $refundFilter = [
            'company_id' => $params['company_id'],
            'refund_bn' => $refundData['refund_bn'],
        ];

        $refundUpdate = [];
        if ($res['status'] == 'SUCCESS' || $res['status'] == 'PROCESSING') {
            $refundUpdate = [
                'refund_id' => $res['refund_id'],
                'refund_status' => 'SUCCESS',
                'refunded_fee' => $refundData['refund_fee'],
                'refunded_point' => $refundData['refund_point'],
                'refund_success_time' => time(),
            ];
        } else {
            $refundUpdate = [
                'refund_status' => 'CHANGE', //退款异常
            ];
        }

        $result = $this->aftersalesRefundRepository->updateOneBy($refundFilter, $refundUpdate);

        if ($result['refund_status'] == 'SUCCESS') {
            $this->updateRefundedFee($result);
        }

        event(new TradeRefundFinishEvent($result));

        return $res;
    }

    public function updateRefundedFee($refund) {
        $ordersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        if (!$refund['aftersales_bn']) {
            $orderItems = $ordersItemsRepository->getList(['order_id' => $refund['order_id']]);
            foreach ($orderItems['list'] as $row) {
                $ordersItemsRepository->update(['id' => $row['id']], ['refunded_fee' => $row['total_fee']]);
            }
        } else {
            $aftersalesDetails = $this->aftersalesDetailRepository->getList(['aftersales_bn' => $refund['aftersales_bn']]);
            $totalRefundedFee = 0;
            foreach ($aftersalesDetails['list'] as $row) {
                if ($row == end($aftersalesDetails['list'])) {
                    $refundedFee = $refund['refunded_fee'] - $totalRefundedFee;
                } else {
                    $refundedFee = 0;
                    if ($refund['refund_fee'] > 0) {
                        $refundedFee = bcmul($refund['refunded_fee'] / $refund['refund_fee'], $row['refund_fee']);
                    }
                    $totalRefundedFee += $refundedFee;
                }

                if ($refundedFee > 0) {
                    $orderItem = $ordersItemsRepository->getRow(['id' => $row['sub_order_id']]);
                    $ordersItemsRepository->update(['id' => $row['sub_order_id']], ['refunded_fee' => $orderItem['refunded_fee'] + $refundedFee]);
                }
            }
        }
    }

    // 直接创建退款单
    public function createRefundSuccess($orderInfo, $tradeInfo, $params, $full_refund = true, $orderItem = null)
    {
        $refundData = $this->__check($orderInfo, $tradeInfo, $params, $full_refund, $orderItem);
        $refundData['refund_status'] = 'SUCCESS';
        $refundData['refund_success_time'] = time();
        $refundData['refunds_memo'] = '拼团退款';
        app('log')->debug('直接创建退款单：'. var_export($refundData, 1));
        $refund = $this->aftersalesRefundRepository->create($refundData);
        return $refund;
    }

    private function __check($orderInfo, $tradeInfo, $params, $full_refund, $orderItem, $isItem = true)
    {
        //部分退款
        if (!$full_refund) {
            if (!$params['refund_fee']) {
                // throw new ResourceException("请提供退款金额！");
            }
            if ('point' == $tradeInfo['payType']) {
                if ($params['refund_fee'] > $orderItem['point']) {
                    throw new ResourceException("退款积分不能大于订单商品积分");
                }
            } else {
                if ($params['refund_fee'] > $orderItem['total_fee']) {
                    throw new ResourceException("退款金额不能大于订单商品金额");
                }
            }
        }

        if (!$tradeInfo) {
            throw new ResourceException("支付信息未找到！");
        }

        if ($orderInfo['order_status'] == 'NOTPAY') {
            throw new ResourceException("未付款订单不需要退款");
        } elseif ($orderInfo['order_status'] == 'CANCEL' && $tradeInfo['tradeState'] != 'SUCCESS') {
            throw new ResourceException("订单已取消，不需要重复取消");
        } elseif ($full_refund && $orderInfo['delivery_status'] != 'PENDING') {
            throw new ResourceException("已发货订单不能直接退款");
        }

        if (!$full_refund && !isset($params['aftersales_bn'])) {
            throw new ResourceException("请输入正确的售后单号");
        }

        //退款金额需要换算成为人民币，如果是全款退（payFee）已经是人民币，不需要进行计算
        $refundFee = $full_refund ? $tradeInfo['payFee'] : $params['refund_fee'];
        if (isset($orderInfo['fee_rate']) && $orderInfo['fee_rate'] && !$full_refund) {
            $feeRate = round(floatval($orderInfo['fee_rate']), 4);
            $refundFee = round($refundFee * $feeRate);
        }

        $refundData = [
            'company_id' => $params['company_id'],
            'user_id' => $orderInfo['user_id'],
            'refund_bn' => $this->__genRefundBn(),
            'order_id' => $orderInfo['order_id'],
            'trade_id' => $tradeInfo['tradeId'],
            'shop_id' => $orderInfo['shop_id'],
            'distributor_id' => $orderInfo['distributor_id'],
            'refund_type' => $params['refund_type'],
            'refund_channel' => (isset($params['refund_channel']) && $params['refund_channel']) ? $params['refund_channel'] : 'original',
            'refund_status' => 'READY',
            'refund_fee' => $refundFee,
            'return_freight' => 1,
            'pay_type' => $tradeInfo['payType'],
            'currency' => $tradeInfo['feeType'],
            'cur_fee_type' => isset($orderInfo['cur_fee_type']) ? $orderInfo['cur_fee_type'] : '',
            'cur_fee_rate' => isset($orderInfo['cur_fee_rate']) ? $orderInfo['cur_fee_rate'] : '',
            'cur_fee_symbol' => isset($orderInfo['cur_fee_symbol']) ? $orderInfo['cur_fee_symbol'] : '',
            'cur_pay_fee' => $full_refund ? (isset($tradeInfo['curPayFee']) && $tradeInfo['curPayFee'] ? $tradeInfo['curPayFee'] : $tradeInfo['payFee']) : $params['refund_fee'],
        ];

        if ($tradeInfo['payType'] == 'point') {
            $filter = [
                'order_id' => $orderInfo['order_id'],
                'company_id' => $params['company_id'],
                'pay_type' => 'point',
            ];
            //以下有关金额（refund_fee）的判断 都是换算成为人民币的值
            $refunds = $this->aftersalesRefundRepository->getList($filter);
            $refundedFee = array_sum(array_column($refunds['list'], 'refunded_fee'));
            $leftRefundFee = intval($orderInfo['point']) - $refundedFee;
        } else {
            // 查询不是积分支付的，已退款金额
            $filter = [
                'order_id' => $orderInfo['order_id'],
                'company_id' => $params['company_id'],
                'pay_type|neq' => 'point',
            ];

            //以下有关金额（refund_fee）的判断 都是换算成为人民币的值
            $refunds = $this->aftersalesRefundRepository->getList($filter);
            $refundedFee = array_sum(array_column($refunds['list'], 'refunded_fee'));
            $leftRefundFee = intval($tradeInfo['payFee']) - $refundedFee;
        }
        if ($refundData['refund_fee'] > $leftRefundFee) {
            throw new ResourceException("退款金额{$refundData['refund_fee']}不能大于订单可退金额:{$leftRefundFee}");
        }
        $filter = [
            'order_id' => $orderInfo['order_id'],
            'company_id' => $params['company_id'],
        ];
        if (isset($params['aftersales_bn']) && $params['aftersales_bn']) {
            $filter['aftersales_bn'] = $params['aftersales_bn'];
            $aftersales = $this->aftersalesRepository->get($filter);
            if (!$aftersales) {
                throw new ResourceException("售后单数据异常");
            }
            $refundData['aftersales_bn'] = $params['aftersales_bn'];
            $refundData['item_id'] = $aftersales['item_id'];
        }

        if ($isItem) {
            $refundData['refund_point'] = $aftersales['share_points'] ?? 0;
        } else {
            $refundData['refund_point'] = $orderInfo['point_use'] ?? 0;
        }

        $refund = $this->aftersalesRefundRepository->getInfo($filter);
        if ($refund && in_array($refund['refund_status'], ['SUCCESS','REFUNDCLOSE'])) {
            throw new ResourceException("该订单已申请过退款");
        }

        return $refundData;
    }

    /**
     * 获取退款单列表
     */
    public function getAftersalesRefundList($filter, $orderBy = ['create_time' => 'DESC'], $pageSize = 20, $page = 1)
    {
        $offset = ($page - 1) * $pageSize;
        $res = $this->aftersalesRefundRepository->getList($filter, $offset, $pageSize, $orderBy);
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        if ($res['list']) {
            foreach ($res['list'] as $key => $value) {
                $tradeInfo = $tradeRepository->getTradeList([
                    'company_id' => $filter['company_id'],
                    'order_id' => $value['order_id']
                ]);
                $res['list'][$key]['tradeInfo'] = [];

                if ($tradeInfo['list']) {
                    $res['list'][$key]['tradeInfo'] = $tradeInfo['list'][0];
                }
            }
        }

        // 附加店铺名称
        if (!empty($res['list'])) {
            $distributorIdSet = array_column($res['list'], 'distributor_id');
            $currentData = current($res['list']);
            (new DistributorService())->getListAddDistributorFields($currentData['company_id'], $distributorIdSet, $res['list']);
        }

        return $res;
    }

    /**
     * 统计退款单数量
     */
    public function refundCount($filter)
    {
        $aftersalesrefundService = new AftersalesRefundService();
        $refund_data = $aftersalesrefundService->getAftersalesRefundList($filter);
        $count = $refund_data['total_count'];

        return intval($count);
    }

    // 售前 创建退款单
    public function createRefund($params)
    {
        $refundData = [
            'refund_bn' => $this->__genRefundBn(),
            'company_id' => $params['company_id'],
            'supplier_id' => $params['supplier_id'] ?? 0,
            'user_id' => $params['user_id'],
            'order_id' => $params['order_id'],
            'trade_id' => $params['trade_id'],
            'shop_id' => $params['shop_id'] ?? 0,
            'distributor_id' => $params['distributor_id'] ?? 0,
            'refund_type' => $params['refund_type'] ?? 1, // 默认取消订单
            'refund_channel' => $params['refund_channel'],
            'refund_status' => $params['refund_status'] ?? 'READY',
            'refund_fee' => $params['refund_fee'],
            'refund_point' => $params['refund_point'],
            'return_freight' => $params['return_freight'],
            'freight' => $params['freight'] ?? 0,
            'freight_type' => $params['freight_type'] ?? 'cash',
            'pay_type' => $params['pay_type'],
            'currency' => $params['currency'],
            'cur_fee_type' => $params['cur_fee_type'],
            'cur_fee_rate' => $params['cur_fee_rate'],
            'cur_fee_symbol' => $params['cur_fee_symbol'],
            'cur_pay_fee' => $params['cur_pay_fee'],
        ];
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfo(['trade_id' => $params['trade_id']]);
        $refundData['merchant_id'] = $tradeInfo['merchant_id'];
        $refund = $this->aftersalesRefundRepository->create($refundData);
        return $refund;
    }

    // 售后 创建退款单
    public function createAftersalesRefund($params)
    {
        $refundData = [
            'refund_bn' => $this->__genRefundBn(),
            'aftersales_bn' => $params['aftersales_bn'],
            'company_id' => $params['company_id'],
            'supplier_id' => $params['supplier_id'] ?? 0,
            'user_id' => $params['user_id'],
            'order_id' => $params['order_id'],
            'trade_id' => $params['trade_id'],
            'shop_id' => $params['shop_id'] ?? 0,
            'distributor_id' => $params['distributor_id'] ?? 0,
            'refund_type' => $params['refund_type'] ?? 0, // 默认售后
            'refund_channel' => $params['refund_channel'],
            'refund_status' => $params['refund_status'] ?? 'READY',
            'refund_fee' => $params['refund_fee'],
            'refund_point' => $params['refund_point'],
            'return_freight' => $params['return_freight'],
            'freight' => $params['freight'] ?? 0,
            'freight_type' => $params['freight_type'] ?? 'cash',
            'pay_type' => $params['pay_type'],
            'currency' => $params['currency'],
            'cur_fee_type' => $params['cur_fee_type'],
            'cur_fee_rate' => $params['cur_fee_rate'],
            'cur_fee_symbol' => $params['cur_fee_symbol'],
            'cur_pay_fee' => $params['cur_pay_fee'],
            'merchant_id' => $params['merchant_id'],
        ];
        if ($params['return_point'] ?? 0) {
            $refundData['return_point'] = $params['return_point'];
        }
        $refund = $this->aftersalesRefundRepository->create($refundData);
        return $refund;
    }

    /**
     * 获取退款单列表
     */
    public function getRefundsList($filter, $offset = 0, $limit = 10, $orderBy = ['create_time' => 'DESC'])
    {
        // 如果通过手机号搜索则换成user_id
        if (isset($filter['mobile']) && isset($filter['company_id'])) {
            $memberService = new MemberService();
            $filter['user_id'] = $memberService->getUserIdByMobile($filter['mobile'], $filter['company_id']) ?? 0;
            // 测试环境还真的有user_id为0的数据
            if ($filter['user_id'] === 0) {
                return ['total_count' => 0, 'list' => []];
            }
            unset($filter['mobile']);
        }
        $res = $this->aftersalesRefundRepository->getList($filter, $offset, $limit, $orderBy);

        if ($res['list']) {
            $distributorIdList = array_column($res['list'], 'distributor_id');
            $distributorService = new DistributorService();
            $indexDistributor = $distributorService->getDistributorListById($filter['company_id'], $distributorIdList);
            foreach ($res['list'] as &$v) {
                $v['distributor_info'] = $indexDistributor[$v['distributor_id']] ?? ['name' => '平台自营'];
            }
        }

        return $res;
    }

    // 定时退款，售前取消订单调用amorepay,售后直接改状态(售后是线下退款)
    public function schedule_refund()
    {
        app('log')->info('schedule_refund::aftersalesRefundService::开始执行审核成功退款单退款初始化脚本');
        $start_time = 1607616000; // 2020-12-11日之后的退款单才处理
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('refund_bn,company_id,order_id')
                 ->from('aftersales_refund')
                 ->where($criteria->expr()->gte('create_time', $criteria->expr()->literal($start_time)))
                 ->andWhere($criteria->expr()->eq('refund_status', $criteria->expr()->literal('AUDIT_SUCCESS')));
        $refunds = $criteria->execute()->fetchAll();
        app('log')->info('schedule_refund::aftersalesRefundService::refunds===>'.json_encode($refunds, 256));
        $orderHas = []; // 已存在的订单号
        $orderHasNum = []; // 已存在的订单号数量,用于计算时间
        foreach ($refunds as $v) {
            // 因为同一个订单号可能会有多个售后单，同时一时间售后退款，微信会产生报错：单笔订单请求频率过高，请于1分钟后重试
            if (in_array($v['order_id'], $orderHas)) {
                $job = (new RefundJob($v))->onQueue('slow')->delay( 60 * $orderHasNum[$v['order_id']] + 1 );      
                $orderHasNum[$v['order_id']] += 1;
            }else {
                $orderHas[] = $v['order_id'];
                $orderHasNum[$v['order_id']] = 1;
                $job = (new RefundJob($v))->onQueue('slow');
            }
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
    }

    /**
     * 根据退款编号获取详情
     */
    public function getRefunds($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'refund_bn' => $params['refund_bn']
        ];
        if (isset($params['user_id']) && $params['user_id']) {
            $filter['user_id'] = $params['user_id'];
        }
        $aftersales = $this->aftersalesRefundRepository->getInfo($filter);
        // 线下退款，查询退款银行信息
        if ($aftersales['refund_channel'] == 'offline') {
            $aftersalesOfflineRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesOfflineRefund::class);
            $aftersales['offline_refund'] = $aftersalesOfflineRefundRepository->getInfo(['refund_bn' => $aftersales['refund_bn']]);
        }
        return $aftersales;
    }

    // 获取的退款金额
    public function getTotalRefundFee($company_id, $order_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(refunded_fee)')
            ->from('aftersales_refund')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('order_id', $qb->expr()->literal($order_id)))
            ->andWhere($qb->expr()->eq('refund_status', $qb->expr()->literal('SUCCESS')));
        $sum = $qb->execute()->fetchColumn();
        return $sum ?? 0;
    }
}
