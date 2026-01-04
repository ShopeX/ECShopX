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

namespace OpenapiBundle\Services\Member;

use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;

use DepositBundle\Entities\DepositTrade;
use DepositBundle\Services\RechargeRule;
use DepositBundle\Services\DepositTrade as DepositTradeService;
use OpenapiBundle\Services\BaseService;

class MemberRechargeService extends BaseService
{
    public function getEntityClass(): string
    {
        return DepositTrade::class;
    }

    /**
     * 创建储值规则
     * @param  string $companyId 企业ID
     * @param  array $params    请求数据
     * @return array            已保存的储值规则数据
     */
    public function createRule($companyId, $params)
    {
        // ShopEx EcShopX Business Logic Layer
        $data = [
            'rule_type' => $params['rule_type'],
            'rule_data' => $params['rule_data'],
        ];
        $rechargeRuleService = new RechargeRule();
        $id = $rechargeRuleService->createRechargeRule($companyId, $params['fixed_money'], $data);
        $info = $rechargeRuleService->getRechargeRuleById($companyId, $id);
        $return = [
            'rechargerule_id' => $info->getId(),
            'money' => bcdiv($info->getMoney(), 100, 2),
            'rule_type' => $info->getRuleType(),
            'rule_data' => intval($info->getRuleData()),
            'create_time' => date('Y-m-d H:i:s', $info->getCreateTime()),
        ];
        return $return;
    }

    /**
     * 更新储值规则
     * @param  string $companyId 企业ID
     * @param  array $params    更新的储值数据
     * @return array            已更新的储值数据
     */
    public function updateRule($companyId, $params)
    {
        $data = [
            'rule_type' => $params['rule_type'],
            'rule_data' => $params['rule_data'],
        ];
        $rechargeRuleService = new RechargeRule();
        $id = $rechargeRuleService->editRechargeRuleById($params['rechargerule_id'], $companyId, $params['fixed_money'], $data);
        $info = $rechargeRuleService->getRechargeRuleById($companyId, $id);
        $return = [
            'rechargerule_id' => $info->getId(),
            'money' => bcdiv($info->getMoney(), 100, 2),
            'rule_type' => $info->getRuleType(),
            'rule_data' => intval($info->getRuleData()),
            'create_time' => date('Y-m-d H:i:s', $info->getCreateTime()),
        ];
        return $return;
    }

    public function deleteRule($companyId, $rechargeruleId)
    {
        $rechargeRuleService = new RechargeRule();
        $info = $rechargeRuleService->getRechargeRuleById($companyId, $rechargeruleId);
        if (!$info) {
            throw new ErrorException(ErrorCode::MEMBER_RECHARGE_NOT_FOUND, '未查询到该储值规则');
        }
        return $rechargeRuleService->deleteRechargeRuleById($rechargeruleId, $companyId);
    }

    /**
     * 获取所有已创建的储值规则
     * @param  string $companyId 企业ID
     * @return array            储值规则列表数据
     */
    public function getAllRuleList($companyId)
    {
        $rechargeRuleService = new RechargeRule();
        $filter = [
            'company_id' => $companyId
        ];
        $data_list = $rechargeRuleService->getRechargeRuleList($filter, 20, 1);
        $result = [];
        if (!$data_list['list']) {
            return $result;
        }
        foreach ($data_list['list'] as $list) {
            $result['list'][] = [
                'rechargerule_id' => $list['id'],
                'money' => bcdiv($list['money'], 100, 2),
                'rule_type' => $list['ruleType'],
                'rule_data' => intval($list['ruleData']),
                'create_time' => date('Y-m-d H:i:s', $list['createTime']),
            ];
        }
        return $result;
    }

    /**
     * 获取储值交易列表
     * @param  array $filter   请求条件
     * @param  int $page     当前页数
     * @param  int $pageSize 每页条数
     * @return array           交易列表数据
     */
    public function getTradeList($filter, int $page, int $pageSize)
    {
        $depositTradeService = new DepositTradeService();
        $dataList = $depositTradeService->getDepositTradeList($filter, $pageSize, $page);
        $result = $this->handlerListReturnFormat($dataList, $page, $pageSize);

        if (!$dataList['list']) {
            return $result;
        }
        $result['list'] = [];
        foreach ($dataList['list'] as $list) {
            $result['list'][] = [
                'trade_id' => $list['depositTradeId'],// 交易流水号
                'trade_type' => $list['tradeType'],// 交易类型
                'money' => bcdiv($list['money'], 100, 2),
                'mobile' => $list['mobile'],
                'member_card_code' => $list['memberCardCode'],
                'shop_id' => $list['shopId'],
                'shop_name' => $list['shopName'],
                'open_id' => $list['openId'],
                'transaction_id' => $list['transactionId'],
                'recharge_rule_id' => $list['rechargeRuleId'],
                'bank_type' => $list['bankType'],
                'authorizer_appid' => $list['authorizerAppid'],
                'detail' => $list['detail'],
                'time_start' => date('Y-m-d H:i:s', $list['timeStart']),
                'time_expire' => date('Y-m-d H:i:s', $list['timeExpire']),
            ];
        }
        return $result;
    }
}
