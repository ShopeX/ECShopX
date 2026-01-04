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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * DistributorWechatRel 店务端微信关联表
 *
 * @ORM\Table(name="distributor_wechat_rel", options={"comment":"店务端微信关联表"},
 *    uniqueConstraints={
 *         @ORM\UniqueConstraint(name="ix_company_operator", columns={"company_id", "app_type", "operator_id"}),
 *         @ORM\UniqueConstraint(name="ix_company_wx_user", columns={"company_id", "app_id", "openid"}),
 *     },
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_operator_id", columns={"operator_id"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\DistributorWechatRelRepository")
 */
class DistributorWechatRel
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"微信用户关联表自增id"})
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
     * @ORM\Column(name="app_id", type="string", nullable=true,  options={"comment":"微信app_id", "default": ""})
     */
    private $app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_type", type="string",  options={"comment":"类型：wx[公众号],wxa[小程序]", "default": "wx"})
     */
    private $app_type;

    /**
     * @var string
     *
     * @ORM\Column(name="openid", type="string", nullable=true,  options={"comment":"微信openid", "default": ""})
     */
    private $openid;

    /**
     * @var string
     *
     * @ORM\Column(name="unionid", type="string",  options={"comment":"微信unionid", "default": ""})
     */
    private $unionid;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true, options={"comment":"系统账户id", "default": 0})
     */
    private $operator_id;

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
    public function getId(): int
    {
        // ShopEx EcShopX Core Module
        return $this->id;
    }

    /**
     * get CompanyId
     *
     * @return int
     */
    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    /**
     * set CompanyId
     *
     * @param int $company_id
     *
     * @return self
     */
    public function setCompanyId(int $company_id): self
    {
        // Ref: 1996368445
        $this->company_id = $company_id;
        return $this;
    }

    /**
     * get AppId
     *
     * @return string
     */
    public function getAppId(): string
    {
        return $this->app_id;
    }

    /**
     * set AppId
     *
     * @param string $app_id
     *
     * @return self
     */
    public function setAppId(string $app_id): self
    {
        $this->app_id = $app_id;
        return $this;
    }

    /**
     * get AppType
     *
     * @return string
     */
    public function getAppType(): string
    {
        return $this->app_type;
    }

    /**
     * set AppType
     *
     * @param string $app_type
     *
     * @return self
     */
    public function setAppType(string $app_type): self
    {
        $this->app_type = $app_type;
        return $this;
    }

    /**
     * get Openid
     *
     * @return string
     */
    public function getOpenid(): string
    {
        return $this->openid;
    }

    /**
     * set Openid
     *
     * @param string $openid
     *
     * @return self
     */
    public function setOpenid(string $openid): self
    {
        $this->openid = $openid;
        return $this;
    }

    /**
     * get Unionid
     *
     * @return string
     */
    public function getUnionid(): string
    {
        return $this->unionid;
    }

    /**
     * set Unionid
     *
     * @param string $unionid
     *
     * @return self
     */
    public function setUnionid(string $unionid): self
    {
        $this->unionid = $unionid;
        return $this;
    }

    /**
     * get OperatorId
     *
     * @return int
     */
    public function getOperatorId(): int
    {
        return $this->operator_id;
    }

    /**
     * set OperatorId
     *
     * @param int $operator_id
     *
     * @return self
     */
    public function setOperatorId(int $operator_id): self
    {
        $this->operator_id = $operator_id;
        return $this;
    }

    /**
     * get BoundTime
     *
     * @return int
     */
    public function getBoundTime(): int
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
    public function setBoundTime(int $bound_time): self
    {
        $this->bound_time = $bound_time;
        return $this;
    }


}
