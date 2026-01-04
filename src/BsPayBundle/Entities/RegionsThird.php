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

namespace BsPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * RegionsThird 省市区编码(六位码或九位码)
 *
 * @ORM\Table(name="bspay_regions_third", options={"comment":"省市区编码(六位码或九位码)"},
 *     indexes={
 *         @ORM\Index(name="idx_area_name", columns={"area_name"}),
 *         @ORM\Index(name="idx_area_code", columns={"area_code"})
 *     },
 * )
 * @ORM\Entity(repositoryClass="BsPayBundle\Repositories\RegionsThirdRepository")
 */
class RegionsThird
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
     * @ORM\Column(name="area_name", type="string", length=50, options={"comment":"名称"})
     */
    private $area_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="pid", type="bigint", options={"comment":"父级ID"})
     */
    private $pid;

    /**
     * @var string
     *
     * @ORM\Column(name="area_code", type="string", length=50, options={"comment":"编码"})
     */
    private $area_code;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set areaName.
     *
     * @param string $areaName
     *
     * @return RegionsThird
     */
    public function setAreaName($areaName)
    {
        $this->area_name = $areaName;

        return $this;
    }

    /**
     * Get areaName.
     *
     * @return string
     */
    public function getAreaName()
    {
        return $this->area_name;
    }

    /**
     * Set pid.
     *
     * @param int $pid
     *
     * @return RegionsThird
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Get pid.
     *
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set areaCode.
     *
     * @param string $areaCode
     *
     * @return RegionsThird
     */
    public function setAreaCode($areaCode)
    {
        $this->area_code = $areaCode;

        return $this;
    }

    /**
     * Get areaCode.
     *
     * @return string
     */
    public function getAreaCode()
    {
        return $this->area_code;
    }
}
