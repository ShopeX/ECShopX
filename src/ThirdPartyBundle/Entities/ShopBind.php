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

namespace ThirdPartyBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ShopBind
 *
 * @ORM\Table(name="thirdparty_shop_bind", options={"comment"="矩阵节点绑定"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_node_type",   columns={"node_type"}),
 *    @ORM\Index(name="idx_status",   columns={"status"}),
 *    @ORM\Index(name="idx_node",   columns={"company_id","node_type","status"})
 * })
 * @ORM\Entity(repositoryClass="ThirdPartyBundle\Repositories\ShopBindRepository")
 */
class ShopBind
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint",options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint",options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", options={"comment":"名称"})
     */
    private $name;
    /**
     * @var string
     *
     * @ORM\Column(name="node_id", type="string", options={"comment":"节点"})
     */
    private $node_id;

    /**
     * @var string
     *
     * @ORM\Column(name="node_type", type="string", options={"comment":"节点类型"})
     */
    private $node_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint", options={"comment":"绑定状态 1:已绑定 0：未绑定"})
     */
    private $status;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ShopBind
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

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ShopBind
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set nodeId
     *
     * @param string $nodeId
     *
     * @return ShopBind
     */
    public function setNodeId($nodeId)
    {
        $this->node_id = $nodeId;

        return $this;
    }

    /**
     * Get nodeId
     *
     * @return string
     */
    public function getNodeId()
    {
        return $this->node_id;
    }

    /**
     * Set nodeType
     *
     * @param string $nodeType
     *
     * @return ShopBind
     */
    public function setNodeType($nodeType)
    {
        $this->node_type = $nodeType;

        return $this;
    }

    /**
     * Get nodeType
     *
     * @return string
     */
    public function getNodeType()
    {
        return $this->node_type;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return ShopBind
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ShopBind
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
     * @return ShopBind
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
}
