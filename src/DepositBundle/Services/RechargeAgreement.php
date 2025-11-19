<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DepositBundle\Services;

use DepositBundle\Entities\RechargeAgreement as DBRechargeAgreement;

/**
 * 储值协议
 */
class RechargeAgreement
{
    /**
     * 设置储值协议
     *
     * @param int $companyId 企业ID
     * @param int $content 协议内容
     */
    public function setRechargeAgreement($companyId, $content)
    {
        return app('registry')->getManager('default')->getRepository(DBRechargeAgreement::class)->setRechargeAgreement($companyId, $content);
    }

    /**
     * 获取储值协议
     *
     * @param int $companyId 企业ID
     */
    public function getRechargeAgreementByCompanyId($companyId)
    {
        return app('registry')->getManager('default')->getRepository(DBRechargeAgreement::class)->getRechargeAgreementByCompanyId($companyId);
    }
}
