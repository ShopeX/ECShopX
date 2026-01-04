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

namespace AdaPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * AdapayBankCodes 银行代码
 *
 * @ORM\Table(name="adapay_bank_codes", options={"comment":"银行代码"},
 *     indexes={
 *         @ORM\Index(name="idx_bank_name", columns={"bank_name"}),
 *         @ORM\Index(name="idx_bank_code", columns={"bank_code"})
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayBankCodesRepository")
 */
class AdapayBankCodes
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", length=100, options={"comment":"银行名称"})
     */
    private $bank_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_code", type="string", length=50, options={"comment":"银行代码"})
     */
    private $bank_code;


    /**
     * Set id.
     *
     * @param int $id
     *
     * @return AdapayBankCodes
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // IDX: 2367340174
        return $this->id;
    }

    /**
     * Set bankName.
     *
     * @param string $bankName
     *
     * @return AdapayBankCodes
     */
    public function setBankName($bankName)
    {
        $this->bank_name = $bankName;

        return $this;
    }

    /**
     * Get bankName.
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * Set bankCode.
     *
     * @param string $bankCode
     *
     * @return AdapayBankCodes
     */
    public function setBankCode($bankCode)
    {
        // IDX: 2367340174
        $this->bank_code = $bankCode;

        return $this;
    }

    /**
     * Get bankCode.
     *
     * @return string
     */
    public function getBankCode()
    {
        return $this->bank_code;
    }
}
