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

namespace CompanysBundle\Services;

use CompanysBundle\Entities\OperatorDataPass;
use CompanysBundle\Entities\OperatorDataPassLog;
use CompanysBundle\Repositories\OperatorDataPassLogRepository;
use CompanysBundle\Repositories\OperatorDataPassRepository;
use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Services\MemberCardService;
use MembersBundle\Services\MemberService;

class OperatorDataPassService
{
    /** @var OperatorDataPassRepository */
    public $passRepository;

    public function __construct()
    {
        $this->passRepository = app('registry')->getManager('default')->getRepository(OperatorDataPass::class);
    }

    public function apply($companyId, $operatorId, $params)
    {
        if ($params['date_type'] != 0 && ((date('w', $params['start_time']) == 6 && $params['end_time'] - $params['start_time'] <= (24 * 3600)) ||
        (date('w', $params['start_time']) == 0 && $params['end_time'] - $params['start_time'] <= 0))) {
            throw new ResourceException('日期存在错误');
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 查询重复日期范围内的所有申请
            $res = $this->searchRange($companyId, $operatorId, $params['start_time'], $params['end_time']);
            // 超级
            $errTime1 = [];
            $errTime2 = [];
            if ($res) {
                // 判断时间是否重复
                foreach ($res as $p) {
                    list($time, $week) = explode(' ', $p['rule']);
                    // 先取出交集日期
                    $startTime = max($params['start_time'], $p['start_time']);
                    $endTime = min($params['end_time'], $p['end_time']);
                    // 一种特殊情况是，如果重复时间全在一个周末，并且有一边不是全天，那也不算做是重复
                    if (
                        ($params['date_type'] != 0 || $week != '*') && (
                            (date('w', $startTime) == 6 && $endTime - $startTime <= (24 * 3600)) ||
                            (date('w', $startTime) == 0 && $endTime - $startTime <= 0)
                        )
                    ) {
                        continue;
                    }
                    // 判断时间段是否重复
                    if ($params['range'] && $time != '*') {
                        list($pStart, $pEnd) = explode('-', $time);
                        list($aStart, $aEnd) = explode('-', $params['range']);
                        $startH = max($pStart, $aStart);
                        $endH = min($pEnd, $aEnd);
                        if ($startH > $endH) {
                            continue;
                        }
                        $timeRange = $startH.'至'.$endH;
                    } else {
                        if ($time == '*' && $params['range']) {
                            list($aStart, $aEnd) = explode('-', $params['range']);
                            $timeRange = $aStart.'至'.$aEnd;
                        } elseif (!$params['range'] && $time != '*') {
                            list($pStart, $pEnd) = explode('-', $time);
                            $timeRange = $pStart.'至'.$pEnd;
                        } else {
                            $timeRange = '全天';
                        }
                    }
                    $startDate = date('Y-m-d', $startTime);
                    $endDate = date('Y-m-d', $endTime);
                    if ($p['status'] == 0) {
                        $errTime1[] = "<br />重复时间：<br />{$startDate} 至 {$endDate}<br />{$timeRange}";
                    } else {
                        $errTime2[] = "<br />重复时间：<br />{$startDate} 至 {$endDate}<br />{$timeRange}";
                    }
                }
            }
            $errInfo = '';
            if ($errTime1) {
                $errInfo = "申请权限开通时间重复，请核实后再试。<br />" . implode('', $errTime1);
            }
            if ($errTime2) {
                if ($errInfo) {
                    $errInfo .= '<br /><br />';
                }
                $errInfo .= "该时间段权限已开通，请核实后再试。<br />" . implode('', $errTime2);
            }
            if ($errInfo) {
                $conn->rollback();
                return $errInfo;
            }
            $operatorsService = new OperatorsService();
            $operators = $operatorsService->getInfo(['operator_id' => $operatorId]);
            $this->passRepository->create([
                'company_id' => $companyId,
                'operator_id' => $operatorId,
                'status' => 0,
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'rule' => $this->transToRule($params['range'], $params['date_type']),
                'reason' => $params['reason'],
                'remarks' => '',
                'create_time' => time(),
                'approve_time' => 0,
                'is_closed' => 0,
                'merchant_id' => $operators['merchant_id'],
            ]);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        return null;
    }

    // 详见数据表中rule字段定义
    protected function transRule($rule)
    {
        list($range, $dateType) = explode(' ', $rule);
        return [
            ($range == '*') ? '' : $range,
            ($dateType == '*') ? 0 : 1,
        ];
    }

    protected function transToRule($range, $dateType)
    {
        return ($range ?: '*') . ' ' . ($dateType ? '1-5' : '*');
    }

    public function searchRange($companyId, $operatorId, $startTime, $endTime)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->from('operator_data_pass');

        $xr = $criteria->expr()->andX(
            $criteria->expr()->eq('company_id', $companyId),
            $criteria->expr()->eq('operator_id', $operatorId),
            $criteria->expr()->neq('status', 2),
            $criteria->expr()->orX(
                $criteria->expr()->andX(
                    $criteria->expr()->lte('start_time', $startTime),
                    $criteria->expr()->gte('end_time', $startTime)
                ),
                $criteria->expr()->andX(
                    $criteria->expr()->lte('start_time', $endTime),
                    $criteria->expr()->gte('end_time', $endTime)
                ),
                $criteria->expr()->andX(
                    $criteria->expr()->gte('start_time', $startTime),
                    $criteria->expr()->lte('start_time', $endTime)
                ),
                $criteria->expr()->andX(
                    $criteria->expr()->gte('end_time', $startTime),
                    $criteria->expr()->lte('end_time', $endTime)
                )
            )
        );
        $criteria->andWhere($xr);

        return $criteria->select('*')->execute()->fetchAll();
    }

    public function getList($filter, $page, $pageSize)
    {
        $result = $this->passRepository->getListsJoinOp($filter, 'p.*,o.login_name,o.head_portrait,o.operator_type', $page, $pageSize, ['create_time' => 'desc']);
        foreach ($result as $k => $p) {
            $result[$k] = $this->exPassItem($p);
        }
        return $result;
    }

    public function exPassItem($item)
    {
        list($item['range'], $item['date_type']) = $this->transRule($item['rule']);
        $item['start_time'] = date('Y-m-d', intval($item['start_time']));
        $item['end_time'] = date('Y-m-d', intval($item['end_time']));
        $exDataType = '每天';
        $exRange = '';
        if ($item['range']) {
            $exRange = ' '.str_replace('-', ' 到 ', $item['range']).' ';
        }
        if ($item['date_type']) {
            $exDataType = '每周一到周五';
        }
        $item['ex'] = "{$exDataType}{$exRange}有权限，生效时间：{$item['start_time']}，结束时间：{$item['end_time']}";
        return $item;
    }

    public function count($filter)
    {
        return $this->passRepository->countJoinOp($filter);
    }

    public function approve($id, $params)
    {
        $status = $params['status'];
        $isClosed = $params['is_closed'];
        $remarks = $params['remarks'] ?: '';
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $detail = $this->passRepository->getInfo([
                'pass_id' => $id
            ]);
            if (!$detail) {
                throw new ResourceException('数据不存在');
            }
            if ($status) {
                if ($detail['status'] != 0 || !in_array($status, [1, 2])) {
                    throw new ResourceException('状态错误');
                }
                $update = ['status' => $status, 'approve_time' => time()];
                if ($remarks) {
                    $update['remarks'] = $remarks;
                }
            } elseif ($isClosed !== null) {
                if ($detail['status'] != 1) {
                    throw new ResourceException('状态错误');
                }
                $update = ['is_closed' => $isClosed ? 1 : 0];
            } else {
                throw new ResourceException('状态错误');
            }
            $this->passRepository->updateOneBy(['pass_id' => $id], $update);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function detail($id)
    {
        $detail = $this->passRepository->getInfo([
            'pass_id' => $id
        ]);
        if (!$detail) {
            throw new ResourceException('数据不存在');
        }
        $detail = $this->exPassItem($detail);
        $opService = new OperatorsService();
        $operatorInfo = $opService->getInfo([
            'company_id' => $detail['company_id'],
            'operator_id' => $detail['operator_id']
        ], true);
        $detail['operator_info'] = $operatorInfo;
        return $detail;
    }

    /**
     * 判断用户当前时间是否可以查看敏感信息
     * @param $companyId
     * @param $operatorId
     * @return bool
     */
    public function check($companyId, $operatorId): bool
    {
        $now = time();
        $todayTime = strtotime(date('Y-m-d', $now));
        $filter = [
            'company_id' => $companyId,
            'operator_id' => $operatorId,
            'start_time|lte' => $todayTime,
            'end_time|gte' => $todayTime,
            'status' => 1,
            'is_closed' => 0,
        ];
        $list = $this->passRepository->getLists($filter);
        foreach ($list as $p) {
            list($range, $dateType) = $this->transRule($p['rule']);
            if ($dateType == 1) {
                $week = date('w', $now);
                if ($week == 0 || $week == 6) {
                    continue;
                }
            }
            if ($range) {
                list($rStart, $rEnd) = explode('-', $range);
                $rn = date('H:i', $now);
                if ($rn < $rStart || $rn > $rEnd) {
                    continue;
                }
            }
            return true;
        }
        return false;
    }

    public function logs($companyId, $operatorId, $page, $pageSize)
    {
        $filter = [
            'company_id' => $companyId,
            'operator_id' => $operatorId,
        ];
        // 获取操作员信息
        $opServer = new OperatorsService();
        $operatorInfo = $opServer->getInfo($filter);
        /** @var OperatorDataPassLogRepository $logRepo */
        $logRepo = app('registry')->getManager('default')->getRepository(OperatorDataPassLog::class);
        $result = $logRepo->lists($filter, '*', $page, $pageSize, ['create_time' => 'desc']);
        foreach ($result['list'] as $k => $l) {
            parse_str(parse_url($l['url'])['query'] ?? '', $args);
            $user_str = '';
            if (isset($args['user_id'])) {
                // 查询会员信息
                $filter = [
                    'user_id' => $args['user_id'],
                    'company_id' => $companyId,
                ];
                $memberService = new MemberService();
                $memberInfo = $memberService->getMemberInfo($filter);
                if ($memberInfo) {
                    $memberCardService = new MemberCardService();
                    $gradeInfo = $memberCardService->getGradeByGradeId($memberInfo['grade_id']);
                    $user_str = $gradeInfo['grade_name'].$memberInfo['username'].'的';
                }
            }
            $result['list'][$k]['content'] = $operatorInfo['login_name'].'查看了'.$user_str.$this->parseLogPageName($l['path'], $args);
        }
        return $result;
    }

    /**
     * 获取该页面的特定文案
     * function rr() {
     *   for file in `ls $1`
     *   do
     *     if [ -d $1"/"$file ]
     *     then
     *       rr $1"/"$file
     *     else
     *       cat $1"/"$file | grep "'datapass'" >> ~/flog.txt
     *     fi
     *   done
     * }
     * rr "./routes/api"
     * vi 宏 0df'f'df'..i ^[f'd/'as'^Mdf'df'i ^[f,d$x
     * @param $path
     * @param $args
     * @return mixed|string
     */
    public function parseLogPageName($path, $args)
    {
        switch ($path) {
            case 'order.list.get':
                if ($args['is_distribution'] ?? false) {
                    return '店铺订单';
                }
                if (isset($args['order_type']) && $args['order_type'] == 'normal') {
                    return '实物订单';
                } else {
                    return '服务订单';
                }
                // no break
            case 'order.info.get':
                return '订单详情';
            case 'aftersales.list':
                return '售后列表';
            case 'order.trade.list':
                return '交易单';
            case 'distributor.list':
                return '店铺列表';
            case 'member.list':
                return '会员列表';
            case 'member.info':
                return '会员详情';
            case 'order.rights.transfer.list':
                return '权益转让';
            case 'rights.log.list':
                if (isset($args['user_id'])) {
                    return '核销记录';
                } else {
                    return '服务核销单';
                }
                // no break
            case 'vipgrade.order.list':
                if (!isset($args['user_id'])) {
                    return '等级购买记录';
                } else {
                    return '付费会员卡记录';
                }
                // no break
            case 'member.whitelist.list':
                return '白名单列表';
            case 'order.rights.list.get':
                return '会员权益';
            case 'deposit.trades':
                return '会员储值';
            case 'distribution.aftersalesaddress.list':
                return '售后地址';
            case 'selfhelp.registrationRecord.list':
                return '报名记录管理';
            case 'selfhelp.registrationRecord.info':
                return '报名详情';
            case 'member.export':
                return '导出用户信息';
            case 'popularize.promoter.list.get':
                return '推广员列表';
            case '':
                return '推广员详情';
            case 'adapay.drawcash.getList':
                return '提现申请';
            case 'adapay.open_account.step':
                return '开户信息';
            case 'adapay.sub_approve.info':
                return '子商户审批详情';
            case 'adapay.dealer.list':
                return '经销商列表';
            case 'adapay.dealer.info':
                return '经销商详情';
            case 'distributor.info':
                return '店铺详情';
            case 'popularize.promoter.export':
                return '导出推广员业绩';
            case 'popularize.cash_withdrawals.list.get':
                return '佣金提现列表';
            case 'popularize.task.brokerage.logs':
                return '任务佣金明细';
            case 'popularize.task.brokerage.count.export':
                return '导出任务佣金统计';
            case 'popularize.task.brokerage.count':
                return '任务佣金统计';
            case 'order.list.export':
                return '导出订单列表';
            case 'rights.list.export':
                return '导出权益列表';
            case 'trades.list.export':
                return '导出交易单列表';
            case 'rights.log.list.export':
                return '导出核销权益列表';
            case 'card.detail.list':
                return '卡券领取列表';
            case 'voucher.package.receives_log':
                return '卡券包领取日志';
            case 'selfhelp.registrationRecord.export':
                return '导出报名记录';
            case 'promotions.give.info':
                return '优惠券发送失败详情';
            case 'specific.crowd.discount.loglist':
                return '定向促销优惠日志';
            case 'adapay.dealer.distributorList':
                return '经销商关联店铺';
            case 'account.list':
                return '企业员工信息列表';
            case 'shop.salesperson.lists':
                return '门店人员列表';
            case 'shop.salesperson.getinfo':
                return '门店人员详情';
            case 'popularize.promoter.children.list':
                return '推广员直属下级';
            case 'goods.epidemicRegister.list':
                return '疫情商品登记列表';
            case 'goods.epidemicRegister.export':
                return '疫情商品登记导出';
            case 'order.process.log.get':
                return '订单操作日志';
            case 'adapay.member.list':
                return 'adapay开户列表(店铺端 经销商端)';
            case 'merchant.detail.get':
                return '商户详情';
            case 'merchant.list':
                return '商户列表';
            case 'merchant.settlement.apply.detail':
                return '商户入驻申请详情';
            default:
                break;
        }
        return $path;
    }
}
