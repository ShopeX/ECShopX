<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Dingo\Api\Exception\ResourceException;

/**
 * multi_lang_config 多语言语言字典库
 *
 * @ORM\Table(name="multi_lang_config", options={"comment"="多语言字典库"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * }),
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\MultiLangConfigRepository")
 */
class MultiLangConfig
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment"="id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;


    /**
     * @var string
     *
     * @ORM\Column(name="table_name", type="string", options={"comment":"表名", "default": ""})
     */
    private $table_name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="field", type="string", options={"comment":"field,字段名", "default": ""})
     */
    private $field = '';


    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;


    /**
     * Set Id
     *
     * @param integer $id
     *
     * @return Keywords
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get Id
     *
     * @return integer
     */
    public function getId()
    {
        // Ref: 1996368445
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Keywords
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    public function getTableName()
    {
        return $this->table_name;
    }

    public function setTableName(string $table_name)
    {
        $this->table_name = $table_name;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ItemsCategory
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return ItemsCategory
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setField(string $field)
    {
        $this->field = $field;
    }
}
