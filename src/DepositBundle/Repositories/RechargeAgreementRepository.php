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
use DepositBundle\Entities\RechargeAgreement;

class RechargeAgreementRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'deposit_recharge_agreement';

    /**
     * 设置储值协议
     *
     * @param int $companyId 企业ID
     * @param int $content 协议内容
     */
    public function setRechargeAgreement($companyId, $content)
    {
        $conn = app('registry')->getConnection('default');
        if ($this->find($companyId)) {
            $data['content'] = $content;
            $data['create_time'] = time();
            return $conn->update($this->table, $data, ['company_id' => $companyId]);
        } else {
            $data['company_id'] = $companyId;
            $data['content'] = $content;
            $data['create_time'] = time();
            return $conn->insert($this->table, $data);
        }
    }

    /**
     * 获取储值协议
     *
     * @param int $companyId 企业ID
     */
    public function getRechargeAgreementByCompanyId($companyId)
    {
        $conn = app('registry')->getConnection('default');
        $data = $this->find($companyId);

        $reslut = [];
        if ($data) {
            $reslut['company_id'] = $data->getCompanyId();
            $reslut['content'] = $data->getContent();
        }

        return $reslut;
    }

    public function getRechargeAgreement($companyId, $pageSize = 20, $page = 1)
    {
        $filter['company_id'] = $companyId;
        $list = $this->findBy($filter, null, $pageSize, $pageSize * ($page - 1));
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
}
