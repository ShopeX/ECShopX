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

namespace CommunityBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * community_chief_distributor 社区拼团团长关联店铺表
 *
 * @ORM\Table(name="community_chief_distributor", options={"comment"="社区拼团团长关联店铺表"}, indexes={
 *    @ORM\Index(name="ix_chief_id", columns={"chief_id"}),
 *    @ORM\Index(name="ix_distributor_id", columns={"distributor_id"})
 * })
 * @ORM\Entity(repositoryClass="CommunityBundle\Repositories\CommunityChiefDistributorRepository")
 */
class CommunityChiefDistributor
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
     * @ORM\Column(name="chief_id", type="bigint", options={"comment":"团长ID"})
     */
    private $chief_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺ID"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="bound_time", type="bigint", nullable=true, options={"comment":"绑定时间", "default": 0})
     */
    private $bound_time;

    /**
     * get Id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * get ChiefId
     *
     * @return int
     */
    public function getChiefId()
    {
        return $this->chief_id;
    }

    /**
     * set ChiefId
     *
     * @param int $chief_id
     *
     * @return self
     */
    public function setChiefId($chief_id)
    {
        $this->chief_id = $chief_id;
        return $this;
    }

    /**
     * get DistributorId
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * set DistributorId
     *
     * @param int $distributor_id
     *
     * @return self
     */
    public function setDistributorId($distributor_id)
    {
        $this->distributor_id = $distributor_id;
        return $this;
    }

    /**
     * get BoundTime
     *
     * @return int
     */
    public function getBoundTime()
    {
        return $this->bound_time;
    }

    /**
     * set BoundTime
     *
     * @param int $bound_time
     *
     * @return self
     */
    public function setBoundTime($bound_time)
    {
        $this->bound_time = $bound_time;
        return $this;
    }


}
