<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CompanyRelDada 商城关联配送配置表
 *
 * @ORM\Table(name="company_rel_delivery", options={"comment":"商城关联配送配置表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\CompanyRelDeliveryRepository")
 */
class CompanyRelDelivery
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
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="boolean", options={"comment":"配送类型【1 商家自配-按整单计算】【2 商家自配-按距离计算】"})
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="boolean", options={"comment":"状态【1 启用】【0 禁用】"})
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="rules", type="text", options={"comment":"json存储，运费规则"})
     */
    private $rules;

    /**
     * @var integer
     *
     * @ORM\Column(name="other_params", type="text", options={"comment":"json存储，其他参数"})
     */
    private $other_params;

    /**
     * @var integer
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="integer", nullable=true,  options={"comment":"创建时间"})
     */
    private $created;

    /**
     * @var integer
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated", type="integer", nullable=true,  options={"comment":"更新时间"})
     */
    private $updated;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // ShopEx EcShopX Service Component
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return CompanyRelDelivery
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
     * Set type.
     *
     * @param bool $type
     *
     * @return CompanyRelDelivery
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return bool
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status.
     *
     * @param bool $status
     *
     * @return CompanyRelDelivery
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set rules.
     *
     * @param string $rules
     *
     * @return CompanyRelDelivery
     */
    public function setRules($rules)
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Get rules.
     *
     * @return string
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Set otherParams.
     *
     * @param string $otherParams
     *
     * @return CompanyRelDelivery
     */
    public function setOtherParams($otherParams)
    {
        $this->other_params = $otherParams;

        return $this;
    }

    /**
     * Get otherParams.
     *
     * @return string
     */
    public function getOtherParams()
    {
        return $this->other_params;
    }

    /**
     * Set created.
     *
     * @param int|null $created
     *
     * @return CompanyRelDelivery
     */
    public function setCreated($created = null)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int|null
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return CompanyRelDelivery
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
