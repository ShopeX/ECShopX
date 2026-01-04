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
