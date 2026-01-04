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

namespace DepositBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use DepositBundle\Entities\RechargeRule;

class RechargeRuleRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'deposit_recharge_rule';

    /**
     * 创建充值面额规则
     *
     * @param int $companyId 企业ID
     * @param string $money 充值固定面额
     *
     * @param array $data
     * ruleType 充值赠送类型 (充值送礼品／充值满送)
     * ruleValue 充值赠送说明（送礼品的文字说明／满送的充值金额）
     */
    public function createRechargeRule($companyId, $money, $data)
    {
        $conn = app('registry')->getConnection('default');

        if ($this->findOneBy(['company_id' => $companyId, 'money' => $money])) {
            throw new BadRequestHttpException('当前面额数已存在，不能重复添加');
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count(['company_id' => $companyId]);
        if ($total >= 14) {
            throw new BadRequestHttpException('最多添加14个面额');
        }

        $insertData['company_id'] = $companyId;
        $insertData['money'] = $money;
        $insertData['rule_type'] = $data['rule_type'];
        $insertData['rule_data'] = $data['rule_data'];
        $insertData['create_time'] = time();

        $conn->insert($this->table, $insertData);
        return $conn->lastInsertId();
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
    public function updateRechargeRule($id, $companyId, $money, $data)
    {
        $conn = app('registry')->getConnection('default');

        $filter['id'] = $id;
        $filter['company_id'] = $companyId;
        $ruleData = $this->findOneBy($filter);
        if (!$ruleData) {
            throw new UpdateResourceFailedException('修改的充值规则不存在');
        }

        if ($rechargeRuleData = $this->findOneBy(['company_id' => $companyId, 'money' => $money])) {
            if ($rechargeRuleData->getId() != $id) {
                throw new UpdateResourceFailedException('当前面额数已存在，不能重复添加');
            }
        }

        $updateData['money'] = $money;
        $updateData['rule_type'] = $data['rule_type'];
        $updateData['rule_data'] = $data['rule_data'];
        $insertData['create_time'] = time();

        $conn->update($this->table, $updateData, $filter);
        return $id;
    }

    /**
     * 根据ID 删除充值面额规则
     *
     * @param int $id
     */
    public function deleteRechargeRuleById($id, $companyId)
    {
        $conn = app('registry')->getConnection('default');

        $filter['id'] = $id;
        $filter['company_id'] = $companyId;

        $conn->delete($this->table, $filter);

        return $id;
    }

    /**
     * 获取充值面额规则
     *
     * @param array $filter 查询条件
     */
    public function getRechargeRuleList($filter, $pageSize = 20, $page = 1, $orderBy = ['create_time' => 'DESC'])
    {
        $list = $this->findBy($filter, $orderBy, $pageSize, $pageSize * ($page - 1));
        $data = [];
        foreach ($list as $v) {
            $value = normalize($v);
            $data[] = $value;
        }
        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($filter);
        $res['total_count'] = intval($total);
        $res['list'] = $data;
        return $res;
    }

    public function getRechargeRuleByMoney($companyId, $money)
    {
        $filter['company_id'] = $companyId;
        $filter['money'] = $money;
        $data = $this->findOneBy($filter);
        return $data;
    }

    /**
     * 根据充值金额获取对于的规则
     */
    public function getRechargeRuleById($companyId, $id)
    {
        $filter['company_id'] = $companyId;
        $filter['id'] = $id;

        $data = $this->findOneBy($filter);
        return $data;
    }
}
