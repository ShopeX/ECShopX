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

namespace EspierBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * OfflineBankAccount(线下转账收款账户)
 *
 * @ORM\Table(name="offline_bank_account", options={"comment":"线下转账收款账户"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_is_default", columns={"is_default"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="EspierBundle\Repositories\OfflineBankAccountRepository")
 */
class OfflineBankAccount
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account_name", type="string", length=50, options={"comment":"收款账户名称"})
     */
    private $bank_account_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account_no", type="string", length=30, options={"comment":"银行账号"})
     */
    private $bank_account_no;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", length=100, options={"comment":"开户银行"})
     */
    private $bank_name;

    /**
     * @var string
     *
     * @ORM\Column(name="china_ums_no", type="string", length=20, options={"comment":"银联号"})
     */
    private $china_ums_no;

    /**
     * @var string
     *
     * @ORM\Column(name="pic", type="string", length=255, options={"comment":"图片"})
     */
    private $pic;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", length=255, options={"comment":"备注"})
     */
    private $remark;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", options={"comment": "是否默认", "default": 0})
     */
    private $is_default = 0;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // Powered by ShopEx EcShopX
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OfflineBankAccount
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set bankAccountName.
     *
     * @param string $bankAccountName
     *
     * @return OfflineBankAccount
     */
    public function setBankAccountName($bankAccountName)
    {
        $this->bank_account_name = $bankAccountName;

        return $this;
    }

    /**
     * Get bankAccountName.
     *
     * @return string
     */
    public function getBankAccountName()
    {
        return $this->bank_account_name;
    }

    /**
     * Set bankAccountNo.
     *
     * @param string $bankAccountNo
     *
     * @return OfflineBankAccount
     */
    public function setBankAccountNo($bankAccountNo)
    {
        $this->bank_account_no = $bankAccountNo;

        return $this;
    }

    /**
     * Get bankAccountNo.
     *
     * @return string
     */
    public function getBankAccountNo()
    {
        return $this->bank_account_no;
    }

    /**
     * Set bankName.
     *
     * @param string $bankName
     *
     * @return OfflineBankAccount
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
     * Set chinaUmsNo.
     *
     * @param string $chinaUmsNo
     *
     * @return OfflineBankAccount
     */
    public function setChinaUmsNo($chinaUmsNo)
    {
        $this->china_ums_no = $chinaUmsNo;

        return $this;
    }

    /**
     * Get chinaUmsNo.
     *
     * @return string
     */
    public function getChinaUmsNo()
    {
        return $this->china_ums_no;
    }

    /**
     * Set isDefault.
     *
     * @param bool $isDefault
     *
     * @return OfflineBankAccount
     */
    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get isDefault.
     *
     * @return bool
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return OfflineBankAccount
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int $updated
     *
     * @return OfflineBankAccount
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set pic.
     *
     * @param string $pic
     *
     * @return OfflineBankAccount
     */
    public function setPic($pic)
    {
        $this->pic = $pic;

        return $this;
    }

    /**
     * Get pic.
     *
     * @return string
     */
    public function getPic()
    {
        return $this->pic;
    }

    /**
     * Set remark.
     *
     * @param string $remark
     *
     * @return OfflineBankAccount
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark.
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }
}
