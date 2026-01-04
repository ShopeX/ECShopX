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

namespace BsPayBundle\Services;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\TradeService;
use MembersBundle\Services\MemberService;
use DistributionBundle\Services\DistributorService;
use AftersalesBundle\Entities\AftersalesRefund;

use BsPayBundle\Entities\DivFee;
use BsPayBundle\Entities\UserEnt;
use BsPayBundle\Services\DivFeeService;

use CompanysBundle\Entities\Operators;
use MerchantBundle\Entities\Merchant;

class BspayTradeService
{
    public $tradeService;
    public $aftersalesRefundRepository;
    public $bspayDivFeeRepository;
    public $operatorsRepository;
    public $userEntRepository;
    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->tradeService = new TradeService();
        $this->aftersalesRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
        $this->bspayDivFeeRepository = app('registry')->getManager('default')->getRepository(DivFee::class);
        $this->operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
        $this->userEntRepository = app('registry')->getManager('default')->getRepository(UserEnt::class);
    }

    public function getTradeList($filter, $pageSize = -1, $page = 1)
    {
        $filter['pay_type'] = 'bspay'; // 待分账的支付单的支付方式都是bspay
        $filter['trade_state'] = ['SUCCESS'];
        $filter['trade_source_type|neq'] = 'membercard';
        //分账导出异步,需要传这个参数
        $output_operator_type = $filter['operator_type'] ?? app('auth')->user()->get('operator_type'); 
        unset($filter['operator_type']);
        $conn = app('registry')->getConnection('default');
        $status = $filter['status'] ?? null;
        unset($filter['status']);
        $canDiv = $filter['can_div'] ?? null;
        unset($filter['can_div']);
        $qb = $conn->createQueryBuilder();
        $cols = 'a.trade_id as tradeId, a.pay_type as payType, a.order_id as orderId,
            a.total_fee as totalFee,a.pay_fee as payFee,
            coalesce(sum(b.refunded_fee),0) as refundedFee,a.bspay_div_status as bspayDivStatus,
            a.bspay_fee_mode as bspayFeeMode,a.bspay_fee as bspayFee,a.time_start as timeStart,
            a.distributor_id as distributorId,a.pay_channel as payChannel, 
            c.order_auto_close_aftersales_time as closeAftersalesTime, c.order_id as normalOrderId';
        $qb->from('trade', 'a')
            ->leftJoin('a', 'aftersales_refund', 'b', 'a.order_id = b.order_id and b.refund_status = '.$qb->expr()->literal("SUCCESS"))
            ->leftJoin('a', 'orders_normal_orders', 'c', 'a.order_id = c.order_id');
        $this->getFilter($filter, $qb);
        $qb->groupBy('a.trade_id');
        $qb->orderBy('time_start', 'DESC');
        $having = [];
        if (isset($status)) {
            if ($status == 'PARTIAL_REFUND') {
                $having[] = '(refundedFee > 0 and refundedFee < payFee)';
                // $qb->having('refundedFee > 0 and refundedFee < payFee');
            }
            if ($status == 'FULL_REFUND') {
                $having[] = '(refundedFee = payFee)';
                // $qb->having('refundedFee = payFee');
            }
            if ($status == 'SUCCESS') {
                $having[] = '(refundedFee = 0)';
                // $qb->having('refundedFee = 0');
            }
        }

        if (isset($canDiv)) {
            if ($canDiv) {
                $having[] = '(refundedFee < payFee and ((normalOrderId is null) or (normalOrderId is not null and closeAftersalesTime > 0 and closeAftersalesTime < '.time().')))';
                // $qb->having('refundedFee < payFee and ((normalOrderId is null) or (normalOrderId is not null and closeAftersalesTime > 0 and closeAftersalesTime < '.time().'))');
            } else {
                $having[] = '(refundedFee >= payFee or (normalOrderId is not null and (closeAftersalesTime is null or closeAftersalesTime = 0 or closeAftersalesTime > '.time().')))';
                // $qb->having('refundedFee >= payFee or (normalOrderId is not null and (closeAftersalesTime is null or closeAftersalesTime = 0 or closeAftersalesTime > '.time().'))');
            }
        }

        if ($having) {
            $qb->having(implode(' and ', $having));
        }

        $list = $qb->select($cols)->execute()->fetchAll();
        $totalFee = 0;
        $payFee = 0;
        $bspayFee = 0;
        $divFee = 0;
        array_walk($list, function ($row) use (&$totalFee, &$payFee, &$bspayFee) {
            $totalFee += $row['payFee'];
            $payFee += $row['payFee'] - $row['refundedFee'];
            $bspayFee += $row['bspayFee'];
        });

        $tradeIds = array_column($list, 'tradeId');
        $divFee = $this->totalDivFee(['trade_id' => $tradeIds,'operator_type' => $output_operator_type]);
        $totalResult = compact('totalFee', 'payFee', 'bspayFee', 'divFee');
        $count = count($list);

        //分页数据
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
              ->setMaxResults($pageSize);
        }
        $list = $qb->select($cols)->execute()->fetchAll();
        //三个月
        // $tradeList = $this->tradeService->getTradeList($filter, $orderBy, $pageSize, $page);
        if (!$list) {
            return ['list' => $list, 'total_count' => $count, 'total' => $totalResult];
        }


        $distributorIds = array_column($list, 'distributorId');
        $distributors = $this->getDistributors(['distributor_id' => $distributorIds]);
        $distributors = array_column($distributors, null, 'distributor_id');
        $tradeIds = array_column($list, 'tradeId');
        
        //查询斗拱的已分账数据
        $_filter = [
            'trade_id' => $tradeIds,
            'operator_type' => $output_operator_type,
        ];
        $divFeeList = $this->getDivFeeList($_filter);
        $divFeeList['list'] = array_column($divFeeList['list'], null, 'trade_id');
        array_walk($list, function (&$row) use ($divFeeList, $distributors) {
            if (!$row['bspayDivStatus']) {
                $row['bspayDivStatus'] = 'NOTDIV';
            }
            if ($row['refundedFee'] <= 0) {
                $row['tradeState'] = 'SUCCESS';
            } elseif ($row['refundedFee'] < $row['payFee']) {
                $row['tradeState'] = 'PARTIAL_REFUND';
            } else {
                $row['tradeState'] = 'FULL_REFUND';
            }
            //当前交易单的已分账金额
            if (isset($divFeeList['list'][$row['tradeId']])) {
                $row['divFee'] = $divFeeList['list'][$row['tradeId']]['div_fee'];
            } else {
                $row['divFee'] = 0;
            }
            if (isset($distributors[$row['distributorId']])) {
                $row['distributor_name'] = $distributors[$row['distributorId']]['name'];
            }
            if ($row['refundedFee'] < $row['payFee'] && (!isset($row['normalOrderId']) || ($row['closeAftersalesTime'] > 0 and $row['closeAftersalesTime'] < time()))) {
                $row['canDiv'] = true;
            } else {
                $row['canDiv'] = false;
            }
        });
        $result = [
            'list' => $list,
            'total' => $totalResult,
            'total_count' => $count
        ];
        return $result;
    }

    public function getTradeCount($filter)
    {
        return $this->tradeService->getTradeCount($filter);
    }

    public function getDivFeeList($params)
    {
        $divFeeService = new DivFeeService();
        return $divFeeService->lists($params, 'trade_id,div_fee', 1, -1);
    }

    public function totalDivFee($params)
    {
        if (!$params['trade_id']) {
            return 0;
        }
        return (new DivFeeService())->divFeeRepository->sum($params, 'div_fee');
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function getFilter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k("a.".$v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in("a.".$field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq("a.".$field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }

    /**
     * 检查数据
     */
    public function check($params)
    {
        if (!preg_match("/^(([0-9]+.[0-9]*[1-9][0-9]*)|([0-9]*[1-9][0-9]*.[0-9]+)|([0-9]*[1-9][0-9]*))|0?.0+|0$/", $params['cash_amt'])) {
            throw new ResourceException("店铺账号提现金额必须是大于等于0的整数");
        }
        $params['cash_amt'] = bcmul($params['cash_amt'], 100);//元=>分
        //提现金额不能超过100万
        if ($params['cash_amt'] > 100000000) {
            throw new ResourceException("店铺账号提现金额不能超过100万元");
        }
        return $params;
    }

    public function getTradeInfo($trade_id)
    {
        $tradeInfo = $this->tradeService->getInfoById($trade_id);
        if (!$tradeInfo) {
            return [];
        }
        $tradeList = $this->getTradeList(['company_id' => $tradeInfo['company_id'], 'order_id' => $tradeInfo['order_id']]);
        $tradeInfo['trade_state'] = $tradeList['list'][0]['tradeState'];
        $memberService = new MemberService();
        $userInfo = $memberService->getMemberInfo(['user_id' => $tradeInfo['user_id']]);
        $tradeInfo['refund_list'] = $this->getRefundList($tradeInfo);
        unset($tradeInfo['inital_request'], $tradeInfo['inital_response']);
        $tradeInfo['username'] = isset($userInfo['username']) ? $userInfo['username'] : ''; //订单所属用户
        $tradeInfo['div_fee_info'] = $this->getDivFeeListByTradeId($tradeInfo);
        $tradeInfo['distributor_name'] = $this->getDistributor($tradeInfo['distributor_id']);
        if ($tradeInfo['merchant_id'] > 0) {
            $merchantRepository = app('registry')->getManager('default')->getRepository(Merchant::class);
            $merchant = $merchantRepository->getInfoById($tradeInfo['merchant_id']);
            
        }
        $tradeInfo['mer_name'] = $merchant['merchant_name'] ?? '-';
        // $tradeInfo['mer_name'] = $this->getUserName();
        return $tradeInfo;
    }

    //根据交易单id获取分账数据列表
    public function getDivFeeListByTradeId($trade)
    {
        $filter['trade_id'] = $trade['trade_id'];
        $operator_type = app('auth')->user()->get('operator_type');
        if ($operator_type != 'admin') { //增加筛选条件, 分账详情子商户只能看到自己的, 主商户可以看到这笔交易单下所有的分账记录
            $filter['operator_type'] = $operator_type;
        }
        $list = $this->bspayDivFeeRepository->lists($filter);
        if (!$list['list']) {
            return ['total_div_fee' => 0, 'list' => []];
        }
        $total_div_fee = $this->bspayDivFeeRepository->sum(['trade_id' => $trade['trade_id']], 'div_fee');
        $service = new DistributorService();
        $distributor_id = $list['list'][0]['distributor_id'];
        $distributor = $service->getInfoSimple(['distributor_id' => $distributor_id]);
        $distributor_name = $distributor['name'] ?? '自营';
        $merchant_id = $trade['merchant_id'];
        foreach ($list['list'] as &$row) {
            if ($row['operator_type'] == 'admin') {
                $row['username'] = '总部';
            } elseif ($row['operator_type'] == 'merchant') {
                $merchantRepository = app('registry')->getManager('default')->getRepository(Merchant::class);
                $merchant = $merchantRepository->getInfoById($trade['merchant_id']);
                $row['username'] = isset($merchant['merchant_name']) ? $merchant['merchant_name'] : '';
            } elseif ($row['operator_type'] == 'distributor') {
                $row['username'] = $distributor_name;
            }
        }
        return [
            'total_div_fee' => $total_div_fee,
            'created' => $list['list'][0]['created'],
            'list' => $list
        ];
    }

    //退款信息
    public function getRefundList($tradeInfo)
    {
        $refundList = $this->aftersalesRefundRepository->getList(['company_id' => $tradeInfo['company_id'],'trade_id' => $tradeInfo['trade_id']], 0, -1);
        array_walk($refundList['list'], function (&$row) {
            $tmpRow = [
                'refund_bn' => $row['refund_bn'],
                'order_id' => $row['order_id'],
                'refunded_fee' => $row['refunded_fee'],
                'created' => $row['create_time']
            ];
            $row = $tmpRow;
        });
        return $refundList['list'];
    }

    //商户名称
    public function getUserName()
    {
        $company_id = app('auth')->user()->get('company_id');
        $info = $this->userEntRepository->getInfo(['company_id' => $company_id]);
        return  $info['mer_name'] ?? '-';
    }

    //店铺名称
    public function getDistributor($distributor_id)
    {
        if ($distributor_id > 0) {
            $distributorService = new DistributorService();
            $distributor = $distributorService->getInfoSimple(['distributor_id' => $distributor_id]);
            return $distributor['name'];
        } else {
            return '自营';
        }
    }

    public function getDistributors($filter)
    {
        $distributorService = new DistributorService();
        $data = $distributorService->getDistributorOriginalList($filter);
        array_walk($data['list'], function (&$row) {
            $row = ['distributor_id' => $row['distributor_id'], 'name' => $row['name']];
        });
        return $data['list'];
    }
}
