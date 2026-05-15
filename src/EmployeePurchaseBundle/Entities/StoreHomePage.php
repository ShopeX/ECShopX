<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 */

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * 内购模版（门店首页配置）
 *
 * @ORM\Table(name="employee_purchase_store_home_page", options={"comment"="内购模版（门店首页配置）"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\StoreHomePageRepository")
 */
class StoreHomePage
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var int
     *
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"门店ID", "default":0})
     */
    private $distributor_id = 0;

    /**
     * @var string|null
     *
     * @ORM\Column(name="template_name", type="string", length=64, nullable=true, options={"comment":"小程序模板名称"})
     */
    private $template_name;

    /**
     * @var string
     *
     * @ORM\Column(name="page_name", type="string", length=255, options={"comment":"页面名称"})
     */
    private $page_name;

    /**
     * @var string
     *
     * @ORM\Column(name="page_description", type="string", length=500, options={"comment":"页面描述"})
     */
    private $page_description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="page_share_title", type="string", length=255, nullable=true, options={"comment":"分享标题"})
     */
    private $page_share_title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="page_share_desc", type="string", length=500, nullable=true, options={"comment":"分享描述"})
     */
    private $page_share_desc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="page_share_imageUrl", type="string", length=500, nullable=true, options={"comment":"分享图片"})
     */
    private $page_share_imageUrl;

    /**
     * @var int
     *
     * @ORM\Column(name="is_open", type="integer", options={"default":1, "comment":"是否开启"})
     */
    private $is_open = 1;

    /**
     * @var int|null
     *
     * @ORM\Column(name="weapp_customize_page_id", type="bigint", nullable=true, options={"unsigned":true})
     */
    private $weapp_customize_page_id;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $created;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

    public function getId()
    {
        return $this->id;
    }

    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    public function getCompanyId()
    {
        return $this->company_id;
    }

    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    public function setTemplateName($templateName)
    {
        $this->template_name = $templateName;

        return $this;
    }

    public function getTemplateName()
    {
        return $this->template_name;
    }

    public function setPageName($pageName)
    {
        $this->page_name = $pageName;

        return $this;
    }

    public function getPageName()
    {
        return $this->page_name;
    }

    public function setPageDescription($pageDescription)
    {
        $this->page_description = $pageDescription;

        return $this;
    }

    public function getPageDescription()
    {
        return $this->page_description;
    }

    public function setPageShareTitle($pageShareTitle)
    {
        $this->page_share_title = $pageShareTitle;

        return $this;
    }

    public function getPageShareTitle()
    {
        return $this->page_share_title;
    }

    public function setPageShareDesc($pageShareDesc)
    {
        $this->page_share_desc = $pageShareDesc;

        return $this;
    }

    public function getPageShareDesc()
    {
        return $this->page_share_desc;
    }

    public function setPageShareImageUrl($pageShareImageUrl)
    {
        $this->page_share_imageUrl = $pageShareImageUrl;

        return $this;
    }

    public function getPageShareImageUrl()
    {
        return $this->page_share_imageUrl;
    }

    public function setIsOpen($isOpen)
    {
        $this->is_open = $isOpen;

        return $this;
    }

    public function getIsOpen()
    {
        return $this->is_open;
    }

    public function setWeappCustomizePageId($weappCustomizePageId)
    {
        $this->weapp_customize_page_id = $weappCustomizePageId;

        return $this;
    }

    public function getWeappCustomizePageId()
    {
        return $this->weapp_customize_page_id;
    }

    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    public function getUpdated()
    {
        return $this->updated;
    }
}
