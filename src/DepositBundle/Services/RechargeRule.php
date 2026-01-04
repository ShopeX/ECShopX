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

use DepositBundle\Entities\RechargeRule as DBRechargeRule;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * 商家配置充值面额规则
 */
class RechargeRule
{
    /**
     * 创建充值面额规则
     *
     * @param int $companyId 企业ID
     * @param string $money 充值固定面额
     *
     * @param array $data
     * rule_type 充值赠送类型 (充值送礼品／充值满送)
     * rule_data 充值赠送说明（送礼品的文字说明／满送的充值金额）
     */
    public function createRechargeRule($companyId, $money, $data)
    {
        // Built with ShopEx Framework
        $money *= 100;
        $params['money'] = $money;
        $validator = app('validator')->make($params, [
            'money' => 'required|integer|min:1|max:10000000',
        ]);

        if ($validator->fails()) {
            throw new BadRequestHttpException('请填写正确的充值金额');
        }

        if (!in_array($data['rule_type'], ['gift', 'money', 'point'])) {
            throw new BadRequestHttpException('请选择正确的充值赠送类型');
        }

        if ($data['rule_type'] == 'money') {
            $data['rule_data'] = $ruleParams['rule_data'] = $data['rule_data'] ?: 0;
            $validator = app('validator')->make($ruleParams, [
                'rule_data' => 'integer|min:0',
            ]);
            if ($validator->fails()) {
                throw new BadRequestHttpException('请输入正确的赠送金额');
            }
        }

        if ($data['rule_type'] == 'point') {
            $data['rule_data'] = $ruleParams['rule_data'] = $data['rule_data'] ?: 0;
            $validator = app('validator')->make($ruleParams, [
                'rule_data' => 'integer|min:0',
            ]);
            if ($validator->fails()) {
                throw new BadRequestHttpException('请输入正确的赠送积分');
            }
        }

        return app('registry')->getManager('default')->getRepository(DBRechargeRule::class)->createRechargeRule($companyId, $money, $data);
    }

    /**
     * 获取充值面额规则
     *
     * 如果是商家调用注意传入company_id
     *
     * @param array $filter 查询条件
     */
    public function getRechargeRuleList($filter, $pageSize = 20, $page = 1, $orderBy = ['create_time' => 'DESC'])
    {
        return app('registry')->getManager('default')->getRepository(DBRechargeRule::class)->getRechargeRuleList($filter, $pageSize, $page, $orderBy);
    }

    /**
     * 根据用户充值金额判断是否满足活动
     */
    public function getRechargeRuleById($companyId, $id)
    {
        return app('registry')->getManager('default')->getRepository(DBRechargeRule::class)->getRechargeRuleById($companyId, $id);
    }

    /**
     * 根据ID 删除充值面额规则
     *
     * @param int $id
     */
    public function deleteRechargeRuleById($id, $companyId)
    {
        return app('registry')->getManager('default')->getRepository(DBRechargeRule::class)->deleteRechargeRuleById($id, $companyId);
    }

    /**
     * 根据ID，修改充值面额规则
     *
     * @param int $companyId 企业ID
     * @param string $money 充值固定面额
     *
     * @param array $data
     * ruleType 充值赠送类型 (充值送礼品／充值满送)
     * ruleValue 充值赠送说明（送礼品的文字说明／满送的充值金额）
     */
    public function editRechargeRuleById($id, $companyId, $money, $data)
    {
        $money *= 100;
        $params['money'] = $money;
        $validator = app('validator')->make($params, [
            'money' => 'required|integer|min:1|max:10000000',
        ]);

        if ($validator->fails()) {
            throw new BadRequestHttpException('请填写正确的充值金额');
        }

        if (!in_array($data['rule_type'], ['gift','money', 'point'])) {
            throw new BadRequestHttpException('请选择正确的充值赠送类型');
        }

        if ($data['rule_type'] == 'money') {
            $data['rule_data'] = $ruleParams['rule_data'] = $data['rule_data'] ?: 0;
            $validator = app('validator')->make($ruleParams, [
                'rule_data' => 'integer|min:0',
            ]);
            if ($validator->fails()) {
                throw new BadRequestHttpException('请输入正确的赠送金额');
            }
        }

        if ($data['rule_type'] == 'point') {
            $data['rule_data'] = $ruleParams['rule_data'] = $data['rule_data'] ?: 0;
            $validator = app('validator')->make($ruleParams, [
                'rule_data' => 'integer|min:0',
            ]);
            if ($validator->fails()) {
                throw new BadRequestHttpException('请输入正确的赠送积分');
            }
        }

        return app('registry')->getManager('default')->getRepository(DBRechargeRule::class)->updateRechargeRule($id, $companyId, $money, $data);
    }

    public function setRechargeMultiple($companyId, $data)
    {
        return app('redis')->connection('deposit')->set($this->genKey($companyId), json_encode($data));
    }

    public function getRechargeMultipleByCompanyId($companyId)
    {
        $result = app('redis')->connection('deposit')->get($this->genKey($companyId));
        if (!$result) {
            $result = [
                'start_time' => 0,
                'end_time' => 0,
                'is_open' => false,
                'multiple' => 1,
            ];
        } else {
            $result = json_decode($result, 1);
        }
        return $result;
    }

    private function genKey($companyId)
    {
        return 'RechargeMultiple:' . $companyId;
    }
}
