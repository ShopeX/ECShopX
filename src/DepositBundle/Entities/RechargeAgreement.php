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

namespace DepositBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * RechargeAgreement 充值协议表
 *
 * @ORM\Table(name="deposit_recharge_agreement", options={"comment":"充值协议表"})
 * @ORM\Entity(repositoryClass="DepositBundle\Repositories\RechargeAgreementRepository")
 */
class RechargeAgreement
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="string", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", options={"comment":"协议内容"})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="create_time", type="string", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * Set companyId
     *
     * @param string $companyId
     *
     * @return RechargeAgreement
     */
    public function setCompanyId($companyId)
    {
        // Built with ShopEx Framework
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return string
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return RechargeAgreement
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set createTime
     *
     * @param string $createTime
     *
     * @return RechargeAgreement
     */
    public function setCreateTime($createTime)
    {
        // Ver: 1e2364-fe10
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return string
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }
}
