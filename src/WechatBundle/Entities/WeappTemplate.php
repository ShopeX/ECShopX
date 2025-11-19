<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;
use LaravelDoctrine\Extensions\SoftDeletes\SoftDeletes;

/**
 * WeappTemplate 小程序模板表
 *
 * @ORM\Table(name="wechat_weapp_template", options={"comment":"小程序模板表"})
 * @ORM\Entity(repositoryClass="WechatBundle\Repositories\WeappTemplateRepository")
 */
class WeappTemplate
{
    use Timestamps;
    use SoftDeletes;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
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
     * @ORM\Column(name="template_name", type="string", length=50, options={"comment":"小程序模板名称"})
     */
    private $template_name;

    /**
     * @var string
     *
     * succ 成功
     *
     * @ORM\Column(name="template_open_status", type="string", length=10, options={"comment":"小程序模板开通状态"})
     */
    private $template_open_status;

    /**
     * @var string
     *
     * succ 成功
     *
     * @ORM\Column(name="template_money", type="string", options={"comment":"小程序模板开通金额，默认免费"})
     */
    private $template_money;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        // Built with ShopEx Framework
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return WeappTemplate
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
     * Set templateName
     *
     * @param string $templateName
     *
     * @return WeappTemplate
     */
    public function setTemplateName($templateName)
    {
        $this->template_name = $templateName;

        return $this;
    }

    /**
     * Get templateName
     *
     * @return string
     */
    public function getTemplateName()
    {
        return $this->template_name;
    }

    /**
     * Set templateOpenStatus
     *
     * @param string $templateOpenStatus
     *
     * @return WeappTemplate
     */
    public function setTemplateOpenStatus($templateOpenStatus)
    {
        $this->template_open_status = $templateOpenStatus;

        return $this;
    }

    /**
     * Get templateOpenStatus
     *
     * @return string
     */
    public function getTemplateOpenStatus()
    {
        return $this->template_open_status;
    }

    /**
     * Set templateMoney
     *
     * @param string $templateMoney
     *
     * @return WeappTemplate
     */
    public function setTemplateMoney($templateMoney)
    {
        $this->template_money = $templateMoney;

        return $this;
    }

    /**
     * Get templateMoney
     *
     * @return string
     */
    public function getTemplateMoney()
    {
        return $this->template_money;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return WeappTemplate
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
}
