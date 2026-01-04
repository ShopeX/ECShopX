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

namespace PointBundle\Services;

use GoodsBundle\Services\MultiLang\MagicLangTrait;
use PointBundle\Exception\PointResourceException;
use PopularizeBundle\Services\SettingService;

class PointMemberRuleService
{
    use MagicLangTrait;
    private $rule;
    public function __construct($companyId = '')
    {
        if ($companyId) {
            $this->rule = $this->getPointRule($companyId);
        }
    }

    /**
     * 获取积分规则
     * @param $companyId
     * @return mixed
     */
    public function getPointRule($companyId)
    {
        $config = [
            'name' => trans('MembersBundle/Members.point_ass'),
            'isOpenMemberPoint' => false,
            'gain_point' => 1,
            'gain_limit' => 9999999,
            'gain_time' => 7,
            'isOpenDeductPoint' => false,
            'deduct_proportion_limit' => 100,// 每单积分抵扣金额上限
            'deduct_point' => 0,
            'access' => 'order',
            'rule_desc' => '',
            'point_pay_first' => 0,//默认积分支付
            'can_deduct_freight' => 1,// 默认支持积分抵扣运费
        ];
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->getRedisId($companyId));
        if ($result) {
            $result = json_decode($result, true);
        }
        $name = $redis->get($this->getRedisLangId($companyId));
        $result = array_merge($config, $result ?: []);
        if(!empty($name)){
            $result['name'] = $name;
        }
        return $result;
    }

    /**
     * 返回积分名
     * @return mixed
     */
    public function getPointName()
    {
        $companyId = app('auth')->user()->get('company_id');
        $result = $this->getPointRule($companyId);
        return $result['name'];
    }

    /**
     * 获取积分规则
     * @param $companyId
     * @return mixed
     */
    public function getUsePointRule($companyId)
    {
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->getRedisId($companyId));
        $result = $result ? json_decode($result, true) : '';
        return $result['recharge'] ?? 0;
    }

    /**
     * 保存积分规则
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function savePointRule($companyId, $data)
    {
        $redis = app('redis')->connection('default');
        $oldRule = $this->getPointRule($companyId);
        $newRule = array_merge($oldRule, $data);
        if(!empty($data['name'])){
            $redis->set($this->getRedisLangId($companyId), $data['name']);
        }

        $redis->set($this->getRedisId($companyId), json_encode($newRule));
        $result = $this->getPointRule($companyId);
        $this->rule = $result;

        // 关闭积分时 积分返佣将会关闭
        if (!$this->getIsOpenPoint()) {
            (new SettingService())->closePointCommission($companyId);
        }

        return $this->getPointRule($companyId);
    }

    /**
     * 钱换积分
     * @param $companyId
     * @param $money
     * @return int
     */
    public function moneyToPoint($companyId, $money)
    {
        $this->rule = $this->getPointRule($companyId);
        if (isset($this->rule['isOpenMemberPoint']) && 'true' == $this->rule['isOpenMemberPoint']) {
            // 使用向上取整，确保用户不会因为精度问题而损失积分
            // 用户支付了钱，就应该获得对应的积分，即使需要向上取整
            $point = bcmul(bcdiv($money, 100, 2), $this->rule['deduct_point'], 2);
            return intval(ceil($point));
        } else {
            throw new PointResourceException("{point}支付未开启");
        }
    }

    /**
     * 积分换钱
     * @param $point
     * @param $originalMoney 原始金额（可选），用于忽略不超过1个积分的差异
     * @return int
     */
    public function pointToMoney($point, $originalMoney = null)
    {
        if (isset($this->rule['isOpenDeductPoint']) && $this->rule['isOpenDeductPoint'] == 'true') {
            $deductPoint = $this->rule['deduct_point'];
            if ($deductPoint == 0) {
                return 0;
            }
            // 使用更精确的计算方式，避免精度损失
            // 直接计算：积分 * 100 / deduct_point
            $calculatedMoney = intval(bcmul(bcdiv($point, $deductPoint, 4), 100, 2));

            // 如果提供了原始金额，且计算出的金额比原始金额多，但多出的价值不超过1个积分，则忽略差异
            if ($originalMoney !== null) {
                $onePointMoney = intval(bcmul(bcdiv(1, $deductPoint, 4), 100, 2));
                $diff = $calculatedMoney - $originalMoney;
                if ($diff > 0 && $diff <= $onePointMoney) {
                    return $originalMoney;
                }
            }

            return $calculatedMoney;
        } else {
            return 0;
        }
    }

    /**
     * 钱换积分
     *
     * @param $money
     * @return int
     */
    public function moneyToPointSend($money): int
    {
        // 积分关闭
        if (!isset($this->rule['isOpenDeductPoint']) || $this->rule['isOpenDeductPoint'] != 'true') {
            return 0;
        }
        // 积分抵扣关闭
        if (!isset($this->rule['isOpenMemberPoint']) || $this->rule['isOpenMemberPoint'] != 'true') {
            return 0;
        }

        if (isset($this->rule['deduct_point'])) {
            $conversionMoney = bcdiv($money, 100, 2); // 转成元
            $point = bcmul($conversionMoney, $this->rule['deduct_point'], 2); // 转为积分
            $point = ceil($point); // 积分向上取整
            return intval($point);
        } else {
            return 0;
        }
    }

    /**
     * 是否打开积分
     *
     * @return bool
     */
    public function getIsOpenPoint(): bool
    {
        if (
            !isset($this->rule['isOpenMemberPoint']) || $this->rule['isOpenMemberPoint'] != 'true' ||
            !isset($this->rule['isOpenDeductPoint']) || $this->rule['isOpenDeductPoint'] != 'true'
        ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 积分规则键名
     * @param $companyId
     * @return string
     */
    public function getRedisId($companyId)
    {
        return 'memeberpoint:rule:' . $companyId;
    }

    public function getRedisLangId($companyId)
    {
        $lang = $this->getLang();
        $lang = str_replace('-', '', $lang);
        return 'memeberpoint:rule:lang:' . $companyId.'_'.$lang;
    }

    /**
     *
     */
    public function shoppingGivePoint($companyId, $payFee)
    {
        // $this->rule = $this->getPointRule($companyId);
        // if ($this->rule['isOpenDeductPoint'] == "true" && $this->rule['deduct_shopping'] > 0) {
        //    $shoppingConfig = bcmul($this->rule['deduct_shopping'], 100);
        //    $point = bcdiv($payFee, $shoppingConfig, 0);
        //    return $point;
        // }
        return 0;
    }

    /**
    * 获取订单最大抵扣积分
    * @param memberPoint:会员积分
    * @param payFee:订单最终支付金额
    */
    public function orderMaxPoint($companyId, $memberPoint, $payFee, $freightFee = 0, $orderData)
    {
        $totalPoint = $totalMaxMoney = $totalMoneyToPoint = $totalPointZiti = 0;
        if ($this->rule['deduct_point']) {
            // 订单可用多少积分不按照总支付金额计算，使用每个订单商品单独计算
            // foreach ($orderData['items'] as $item) {
            //     $maxMoney = bcmul(bcdiv($this->rule['deduct_proportion_limit'], 100, 2), $item['total_fee']);
            //     $moneyToPoint = $this->moneyToPoint($companyId, $maxMoney);// 本商品最大抵扣积分数
            //     $totalMoneyToPoint += $moneyToPoint;
            //     $totalMaxMoney += $maxMoney;
            // }
            $totalMaxMoney = $orderData['total_fee'] - $freightFee;
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':totalMaxMoney:' . json_encode($totalMaxMoney));
            $moneyToPoint = $this->moneyToPoint($companyId, $totalMaxMoney);// 本商品最大抵扣积分数
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':moneyToPoint:' . json_encode($moneyToPoint));
            $totalMoneyToPoint = $moneyToPoint;
            $totalPointZiti = $totalMoneyToPoint;
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':totalPointZiti:' . json_encode($totalPointZiti));

            // 配置运费是否可抵扣
            $canDeductFreight = isset($this->rule['can_deduct_freight']) ? $this->rule['can_deduct_freight'] : false;
            if ($canDeductFreight) {
                $freightMaxMoney = bcmul(bcdiv($this->rule['deduct_proportion_limit'], 100, 2), $freightFee);
                $freightMoneyToPoint = $this->moneyToPoint($companyId, $freightMaxMoney);// 运费最大可抵扣积分数
                $totalMoneyToPoint += $freightMoneyToPoint;
            }

            if ($memberPoint > $totalMoneyToPoint) { // 未超出会员积分
                $useLimit = $totalMoneyToPoint;
            } else { // 超出会员积分
                $totalMaxMoney = $this->pointToMoney($memberPoint);
                $useLimit = $this->moneyToPoint($companyId, $totalMaxMoney);
            }
            $useLimit = $useLimit > 0 ? $useLimit : 0;// 本地，当前会员，最大可使用积分数
            $totalPoint = $useLimit;
        }

        return [
            'limit_point' => $totalMoneyToPoint,
            'max_point' => $totalPoint,
            'max_point_ziti' => $totalPointZiti,
            'max_money' => $totalMaxMoney,
        ];
    }


    /**
     * 订单最大可抵扣积分
     * @param  [type] $companyId [description]
     * @param  [type] $payFee    [description]
     * @return [type]            [description]
     */
    public function orderMaxMoneyToPoint($companyId, $payFee)
    {
        if ($this->rule['deduct_point']) {
            $maxMoney = bcmul(bcdiv($this->rule['deduct_proportion_limit'], 100, 2), $payFee);
            $moneyToPoint = $this->moneyToPoint($companyId, $maxMoney);// 本单最大抵扣积分数
        }
        return $moneyToPoint ?? 0;
    }


    /**
     * 检查使用的积分数是否超出配置限制比例
     * @param $point 使用的积分数
     * @param $totalFee 订单总金额
     * @return bool true:未超出，false:超出
     */
    public function moneyOutLimit($point, $totalFee)
    {
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':point:' . json_encode($point));
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':totalFee:' . json_encode($totalFee));

        // 当前积分对应的金额
        $money = $this->pointToMoney($point);
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':money:' . json_encode($money));

        // 少1个积分对应的金额（用于边界值处理）
        $money_minus1point = $this->pointToMoney($point - 1);
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':money_minus1point:' . json_encode($money_minus1point));

        // 配置的限制金额
        $limit = bcmul(bcdiv($this->rule['deduct_proportion_limit'], 100, 2), $totalFee);
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':limit:' . json_encode($limit));

        // 只有当当前积分和少1个积分都超出限制时，才认为真正超出
        // 这样可以避免在边界值附近的精度问题导致的误判
        if ($limit < $money && $limit < $money_minus1point) {
            return false; // 超出限制
        } else {
            return true;  // 未超出限制
        }
    }
}
