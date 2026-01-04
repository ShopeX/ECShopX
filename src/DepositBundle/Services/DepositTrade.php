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

namespace DepositBundle\Services;

use DepositBundle\Entities\DepositTrade as DBDepositTrade;
use DepositBundle\Entities\RechargeRule as DBRechargeRule;
use DepositBundle\Entities\UserDeposit;
use MembersBundle\Services\MemberService;
use OrdersBundle\Traits\GetPaymentServiceTrait;
use PointBundle\Services\PointMemberService;
use PopularizeBundle\Services\BrokerageService;
use PopularizeBundle\Services\PromoterService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


//发送短信引入类
use DepositBundle\Jobs\RechargeSendSmsNotice;
use CompanysBundle\Traits\GetDefaultCur;
use Dingo\Api\Exception\ResourceException;

/**
 * 会员卡储值交易
 */
class DepositTrade
{
    use GetDefaultCur;
    use GetPaymentServiceTrait;

    private $orderPrefix = 'CZ';

    /**
     * 充值
     */
    public function recharge($authorizerAppId, $wxaAppId, $data)
    {
        $depositTradeId = $this->genDepositTradeId($data['user_id']);
        $data['deposit_trade_id'] = $depositTradeId;
        $data['authorizer_appid'] = $authorizerAppId;
        $data['wxa_appid'] = $wxaAppId;
        $data['trade_type'] = 'recharge';
        $data['trade_status'] = 'NOTPAY';
        $data['time_start'] = time();

        $rechargeRuleData = app('registry')->getManager('default')->getRepository(DBRechargeRule::class)->getRechargeRuleByMoney($data['company_id'], $data['money']);
        if ($rechargeRuleData) {
            $data['recharge_rule_id'] = $rechargeRuleData->getId();
        }

        //预存款充值，输入的金额（money）是当前货币的金额，需要换算为人民币
        $cur = $this->getCur($data['company_id']);
        if ($cur && isset($cur['rate']) && $cur['rate']) {
            $data['cur_pay_fee'] = $data['money'];
            $data['cur_fee_symbol'] = $cur['symbol'];
            $data['cur_fee_rate'] = round(floatval($cur['rate']), 4);
            $data['cur_fee_type'] = $cur['currency'];
            $data['money'] = round($data['money'] * $data['cur_fee_rate']);
        }

        app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->createDepositTrade($data);

        //小程序支付渠道，调起支付接口
        if ($data['pay_type'] == 'wxpay') {
            $service = $this->getPaymentService($data['pay_type']);

            return $service->depositRecharge($authorizerAppId, $wxaAppId, $data);
        } else {
            $result['trade_info'] = [
                'order_id' => $data['deposit_trade_id'],
                'trade_id' => $data['deposit_trade_id'],
            ];

            return $result;
        }
    }

    public function rechargeNew($authorizerAppId, $wxaAppId, $data)
    {
        $depositTradeId = $this->genDepositTradeId($data['user_id']);
        $data['deposit_trade_id'] = $depositTradeId;
        $data['authorizer_appid'] = $authorizerAppId;
        $data['wxa_appid'] = $wxaAppId;
        $data['trade_type'] = 'recharge';
        $data['trade_status'] = 'NOTPAY';
        $data['time_start'] = time();

        $rechargeRuleData = app('registry')->getManager('default')->getRepository(DBRechargeRule::class)->getRechargeRuleByMoney($data['company_id'], $data['money']);
        if ($rechargeRuleData) {
            $data['recharge_rule_id'] = $rechargeRuleData->getId();
        }

        //预存款充值，输入的金额（money）是当前货币的金额，需要换算为人民币
        $cur = $this->getCur($data['company_id']);
        if ($cur && isset($cur['rate']) && $cur['rate']) {
            $data['cur_pay_fee'] = $data['money'];
            $data['cur_fee_symbol'] = $cur['symbol'];
            $data['cur_fee_rate'] = round(floatval($cur['rate']), 4);
            $data['cur_fee_type'] = $cur['currency'];
            $data['money'] = round($data['money'] * $data['cur_fee_rate']);
        }

        $result = app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->createDepositTrade($data);
        return $result;
    }


    /**
     * 充值成功后记录
     *
     * 充值规则，用户选择需要充值的面额或自定义金额
     *
     * 通过支付方式将金额打入到用户账号，生成一笔交易单
     * 并且在支付成功回调后记录充值记录
     *
     * 商家储值金额累加，用户充值金额累加
     * 商家每家店今日充值累加
     */
    public function rechargeCallback($depositTradeId, $status, $options)
    {
        if ($status != 'SUCCESS') {
            return true;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        $data = app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->updateStatus($depositTradeId, $status, $options);
        $cur = $this->getCur($data->getCompanyId());
        try {
            $data->setTradeStatus = 'SUCCESS';
            $totalFee = $data->getCurPayFee();
            if ($data->getRechargeRuleId()) {
                $rechargeRule = new RechargeRule();
                $rechargeRuleData = $rechargeRule->getRechargeRuleById($data->getCompanyId(), $data->getRechargeRuleId());
//                $memberService = new MemberService();
//                $memberService->usePointOpen($data->getUserId(), $data->getCompanyId());
                //totalFee单位为分
                //临时，后续需要将每个不同的活动类型，进行不同的处理
                //目前只有充值满送
                if ($rechargeRuleData && $rechargeRuleData->getRuleType() == 'money' && $rechargeRuleData->getRuleData() > 0) {
                    $depositTradeData['deposit_trade_id'] = $this->genDepositTradeId($data->getUserId());
                    $depositTradeData['company_id'] = $data->getCompanyId();
                    $depositTradeData['member_card_code'] = $data->getMemberCardCode();
                    $depositTradeData['shop_id'] = $data->getShopId();
                    $depositTradeData['shop_name'] = $data->getShopName();
                    $depositTradeData['user_id'] = $data->getUserId();
                    $depositTradeData['mobile'] = $data->getMobile();
                    $depositTradeData['open_id'] = $data->getOpenId();
                    $depositTradeData['money'] = ($rechargeRuleData->getRuleData() * 100);
                    $depositTradeData['trade_type'] = 'recharge_gift';
                    $depositTradeData['trade_status'] = 'SUCCESS';
                    $depositTradeData['authorizer_appid'] = $data->getAuthorizerAppid();
                    $depositTradeData['wxa_appid'] = $data->getWxaAppid();
                    $depositTradeData['detail'] = '充值' . $data->getCurPayFee() / 100 . '送' . $rechargeRuleData->getRuleData();
                    $depositTradeData['time_start'] = time();
                    $depositTradeData['time_expire'] = time();

                    //预存款充值，输入的金额（money）是当前货币的金额，需要换算为人民币
                    if ($cur && isset($cur['rate']) && $cur['rate']) {
                        $depositTradeData['cur_pay_fee'] = ($rechargeRuleData->getRuleData() * 100);
                        $depositTradeData['cur_fee_symbol'] = $cur['symbol'];
                        $depositTradeData['cur_fee_rate'] = round(floatval($cur['rate']), 4);
                        $depositTradeData['cur_fee_type'] = $cur['currency'];
                        $depositTradeData['money'] = round($depositTradeData['cur_pay_fee'] * $depositTradeData['cur_fee_rate']);
                    }

                    app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->createDepositTrade($depositTradeData);
                    $totalFee += ($rechargeRuleData->getRuleData() * 100);
                } elseif ($rechargeRuleData && $rechargeRuleData->getRuleType() == 'point' && $rechargeRuleData->getRuleData() > 0) { // 充值返积分
                    $pointMemberService = new PointMemberService();

                    $rechargeRule = new RechargeRule();
                    $rechargeRuleInfo = $rechargeRule->getRechargeMultipleByCompanyId($data->getCompanyId());
                    if ('true' == $rechargeRuleInfo['is_open'] && time() > $rechargeRuleInfo['start_time'] && time() < $rechargeRuleInfo['end_time']) {
                        $point = $rechargeRuleData->getRuleData() * $rechargeRuleInfo['multiple'];
                        $pointMemberService->addPoint($data->getUserId(), $data->getCompanyId(), $point, 3, true, '充值订单' . $options['transaction_id'] . "赠送积分, 活动期间{$rechargeRuleInfo['multiple']}倍", $options['transaction_id']);
                    } else {
                        $point = $rechargeRuleData->getRuleData();
                        $pointMemberService->addPoint($data->getUserId(), $data->getCompanyId(), $point, 3, true, '充值订单' . $options['transaction_id'] . '赠送积分', $options['transaction_id']);
                    }
                }
            }
            // 充值返佣
//            $brokerageService = new BrokerageService();
//            $brokerageService->recharge($data->getUserId(), $data->getCompanyId(), $data->getCurPayFee(), $data->getDepositTradeId(), $data);
            $this->addDepositToRedis($data->getCompanyId(), $data->getUserId(), $totalFee, $data->getCurPayFee());
            //$this->addUserAllDepositTotal($data->getCompanyId(), $data->getUserId(), $data->getCurPayFee());
            $conn->commit();
        } catch (\Exception $e) {
            app('log')->debug('支付回调记录错误 ' . $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());
            $conn->rollback();
            throw $e;
        }

        $promoterService = new PromoterService();
        $promoterService->updateByUserId($data->getUserId(), ['is_buy' => 1]);
        $this->rechargeSendSmsNotice($data->getCompanyId(), $data->getUserId(), $data->getMobile(), $totalFee);

        return true;
    }

    /**
     * 充值发送短信通知
     */
    private function rechargeSendSmsNotice($companyId, $userId, $mobile, $totalFee)
    {
        $job = (new RechargeSendSmsNotice($companyId, $userId, $mobile, $totalFee))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
    }

    /**
     * redis中存储用户充值金额
     *
     * 1 新增商家的储值总金额
     * 2 新增用户的储值总金额
     * 3 新增当天的充值金额 (当天充值的金额不包含赠送的金额)
     *
     * @param int $companyId 企业ID
     * @param int $userId 充值用户ID
     * @param int $rechargeTotalFee 用户充值总金额（包含赠送金额）
     * @param int $rechargeMoney 用户充值金额（不包含赠送金额）
     */
    public function addDepositToRedis($companyId, $userId, $rechargeTotalFee, $rechargeMoney)
    {
        $redis = app('redis')->connection('deposit');
        $redis->hincrby('shopDepositTotal', $companyId, $rechargeTotalFee);
        $this->addUserDepositTotal($companyId, $userId, $rechargeTotalFee);
        $redis->hincrby('dayRechargeTotal' . date('Y-m-d'), $companyId, $rechargeMoney);
        return true;
    }

    /**
     * 累增企业储值总金额
     * Redis 原子操作后存储到数据库
     */
    private function addShopDepositTotal($companyId, $money, $isAdd = true)
    {
        if ($isAdd) {
            $money = intval($money);
        } else {
            $money = -intval($money);
        }
        return app('redis')->connection('deposit')->hincrby('shopDepositTotal', $companyId, $money);
    }

    /**
     * 获取企业储值总金额
     */
    public function getShopDepositTotal($companyId)
    {
        return app('redis')->connection('deposit')->hget('shopDepositTotal', $companyId);
    }

    /**
     * 用户储值金额累增
     *
     * @param $companyId
     * @param $userId
     * @param $money !这里是增减的金额数，非设置的金额数
     * @param bool $isAdd
     * @return bool
     */
    public function addUserDepositTotal($companyId, $userId, $money, bool $isAdd = true): bool
    {
        if ($isAdd) {
            $money = intval($money);
        } else {
            $money = -intval($money);
        }

        $deposit = $this->getUserDepositTotal($companyId, $userId);
        $setMoney = bcadd($money, $deposit);

        $redis = app('redis')->connection('deposit');

        // 清除缓存数据保证一致性
        $redis->hdel('userDepositTotal_' . $companyId, $userId);

        // 更新数据库
        $userDeposit = app('registry')->getManager('default')->getRepository(UserDeposit::class);

        $filter = [
            'company_id' => $companyId,
            'user_id' => $userId
        ];
        $findInfo = $userDeposit->getInfo($filter);
        if (!empty($findInfo)) {
            $updateWhere = [
                'deposit_id' => $findInfo['deposit_id']
            ];
            $userDeposit->updateOneBy($updateWhere, ['money' => $setMoney]);
        } else {
            $addData = [
                'company_id' => $companyId,
                'user_id' => $userId,
                'money' => $setMoney,
            ];
            $userDeposit->create($addData);
        }

        // 更新缓存
        $redis->hset('userDepositTotal_' . $companyId, $userId, $setMoney);

        return true;
    }

    /**
     * 获取用户储值金额
     *
     * @param $companyId
     * @param $userId
     * @return int
     */
    public function getUserDepositTotal($companyId, $userId): int
    {
        // 读缓存
        $deposit = app('redis')->connection('deposit')->hget('userDepositTotal_' . $companyId, $userId);
        if (is_null($deposit)) {
            // 读数据库
            $userDeposit = app('registry')->getManager('default')->getRepository(UserDeposit::class);
            $filter = [
                'company_id' => $companyId,
                'user_id' => $userId
            ];
            $findInfo = $userDeposit->getInfo($filter);

            return !empty($findInfo) ? $findInfo['money'] : 0;
        } else {
            return $deposit > 0 ? $deposit : 0;
        }
    }

    /**
     * 获取用户储值总金额
     * @return mixed
     */
    public function getUserAllDepositTotal($companyId, $userId)
    {
        return app('redis')->connection('deposit')->hget('userAllDepositTotal_' . $companyId, $userId);
    }

    public function addUserAllDepositTotal($companyId, $userId, $rechargeMoney)
    {
        return app('redis')->connection('deposit')->hset('userAllDepositTotal_' . $companyId, $userId, $rechargeMoney);
    }

    /**
     * 消费记录
     *
     * 用户消费后商家储值金额扣除消费金额
     * 用户储值金额扣除消费金额
     * 记录扣除信息
     */
    public function consume($data)
    {
        $companyId = $data['company_id'];
        $userId = $data['user_id'];
        $money = intval($data['money']);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //创建一笔预存款消费记录
            $data['deposit_trade_id'] = $this->genDepositTradeId($data['user_id']);
            $data['trade_type'] = 'consume';
            $data['trade_status'] = 'SUCCESS';
            $data['time_start'] = time();
            $data['time_expire'] = time();
            app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->createDepositTrade($data);

            // 减少金额数
            $userDepositTotal = $this->getUserDepositTotal($companyId, $userId);
            if (1 == bccomp($money, $userDepositTotal)) {
                throw new BadRequestHttpException('余额不足，支付失败');
            }
            $this->addUserDepositTotal($companyId, $userId, $money, false);

            $dayRechargeTotalKey = "dayConsumeTotal" . date('Y-m-d');
            $redis = app('redis')->connection('deposit');

            $redis->hincrby('shopDepositTotal', $companyId, -$money);
            $redis->hincrby($dayRechargeTotalKey, $companyId, $money);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new BadRequestHttpException('支付失败');
        }
        return $data;
    }

    /**
     * 退款
     * @param $data
     * @return mixed
     */
    public function doRefund($data)
    {
        $companyId = $data['company_id'];
        $userId = $data['user_id'];
        $money = intval($data['money']);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //创建一笔预存款消费记录
            $data['deposit_trade_id'] = $this->genDepositTradeId($data['user_id']);
            $data['trade_type'] = 'refund';
            $data['trade_status'] = 'SUCCESS';
            $data['time_start'] = time();
            $data['time_expire'] = time();
            app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->createDepositTrade($data);

            $this->addUserDepositTotal($companyId, $userId, $money);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new BadRequestHttpException('支付失败');
        }
        return $data;
    }

    /**
     * 获取充值、消费记录列表
     */
    public function getDepositTradeList($filter, $pageSize = 20, $page = 1, $orderBy = ['time_start' => 'DESC'])
    {
        return app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->getDepositTradeList($filter, $pageSize, $page, $orderBy);
    }

    /**
     * 获取充值、消费记录列表
     */
    public function getDepositTradeInfo($depositTradeId)
    {
        return app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->findDepositTrade($depositTradeId);
    }


    public function getDepositTradeRechargeCount($userId)
    {
        if (is_array($userId)) {
            return app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->getDepositCountByUsers($userId);
        } else {
            return app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->getDepositCountByUser($userId);
        }
    }

    /**
     * 充值交易流水号
     */
    public function genDepositTradeId($userId)
    {
        $time = time();
        $startTime = 1325347200;//2012-01-01 做为初始年
        //当前时间相距初始年的天数，4位可使用20年
        $day = floor(($time - $startTime) / 86400);

        //确定每90秒的的订单生成 一天总共有960个90秒，控制在三位
        $minute = floor(($time - strtotime(date('Y-m-d'))) / 90);

        $redisId = app('redis')->connection('deposit')->hincrby('deposit_trade_id' . date('Ymd'), $minute, 1);

        //设置过期时间
        app('redis')->connection('deposit')->expire('deposit_trade_id' . date('Ymd'), 86400);

        $id = $day . str_pad($minute, 3, '0', STR_PAD_LEFT) . str_pad($redisId, 5, '0', STR_PAD_LEFT) . str_pad($userId % 10000, 4, '0', STR_PAD_LEFT);//16位

        return $this->orderPrefix . $id;
    }

    public function adminRecharge($companyId, $userId, $money)
    {
        $memberService = new MemberService();
        $memberInfo = $memberService->getMemberInfo(['user_id' => $userId, 'company_id' => $companyId]);
        if (!$memberInfo) {
            throw new ResourceException('会员不存在');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        $cur = $this->getCur($companyId);
        try {
            $depositTradeData['deposit_trade_id'] = $this->genDepositTradeId($userId);
            $depositTradeData['company_id'] = $companyId;
            $depositTradeData['member_card_code'] = $memberInfo['user_card_code'];
            $depositTradeData['user_id'] = $userId;
            $depositTradeData['mobile'] = $memberInfo['mobile'];
            $depositTradeData['money'] = $money;
            $depositTradeData['trade_type'] = 'recharge_admin';
            $depositTradeData['trade_status'] = 'SUCCESS';
            $depositTradeData['detail'] = '后台充值' . bcdiv($money, 100, 2). '元';
            $depositTradeData['time_start'] = time();
            $depositTradeData['time_expire'] = time();

            //预存款充值，输入的金额（money）是当前货币的金额，需要换算为人民币
            if ($cur && isset($cur['rate']) && $cur['rate']) {
                $depositTradeData['cur_pay_fee'] = $money;
                $depositTradeData['cur_fee_symbol'] = $cur['symbol'];
                $depositTradeData['cur_fee_rate'] = round(floatval($cur['rate']), 4);
                $depositTradeData['cur_fee_type'] = $cur['currency'];
                $depositTradeData['money'] = round($depositTradeData['cur_pay_fee'] * $depositTradeData['cur_fee_rate']);
            }

            app('registry')->getManager('default')->getRepository(DBDepositTrade::class)->createDepositTrade($depositTradeData);

            $this->addUserDepositTotal($companyId, $userId, $money);

            $conn->commit();
        } catch (\Exception $e) {
            app('log')->debug('后台充值预存款失败：' . $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return true;
    }
}
