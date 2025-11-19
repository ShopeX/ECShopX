<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
