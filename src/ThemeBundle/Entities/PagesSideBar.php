<?php

namespace ThemeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * pages_side_bar 侧边栏设置
 *
 * @ORM\Table(name="pages_side_bar", options={"comment":"侧边栏设置"})
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\PagesSideBarRepository")
 */
class PagesSideBar
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", options={"comment":"设置id"})
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
     * @var integer
     *
     * @ORM\Column(name="regionauth_id", type="bigint", options={"comment":"区域id", "default": 0})
     */
    private $regionauth_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, options={"comment":"名称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="pages", type="string", length=100, options={"comment":"关联页面"})
     */
    private $pages;

    /**
     * @var integer
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"是否禁用", "default": false})
     */
    private $disabled = false;

    /**
     * @var text
     *
     * @ORM\Column(name="setting", type="text", nullable=true, options={"comment":"设置"})
     */
    private $setting;

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
     * 获取设置ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * 设置公司ID
     *
     * @param integer $company_id
     * @return PagesSideBar
     */
    public function setCompanyId($company_id)
    {
        $this->company_id = $company_id;
        
        return $this;
    }
    
    /**
     * 获取公司ID
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }
    
    /**
     * 设置区域ID
     *
     * @param integer $regionauth_id
     * @return PagesSideBar
     */
    public function setRegionauthId($regionauth_id)
    {
        $this->regionauth_id = $regionauth_id;
        
        return $this;
    }
    
    /**
     * 获取区域ID
     *
     * @return integer
     */
    public function getRegionauthId()
    {
        return $this->regionauth_id;
    }
    
    /**
     * 设置名称
     *
     * @param string $name
     * @return PagesSideBar
     */
    public function setName($name)
    {
        $this->name = $name;
        
        return $this;
    }
    
    /**
     * 获取名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * 设置关联页面
     *
     * @param string $pages
     * @return PagesSideBar
     */
    public function setPages($pages)
    {
        $this->pages = $pages;
        
        return $this;
    }
    
    /**
     * 获取关联页面
     *
     * @return string
     */
    public function getPages()
    {
        return $this->pages;
    }
    
    /**
     * 设置是否禁用
     *
     * @param boolean $disabled
     * @return PagesSideBar
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
        
        return $this;
    }
    
    /**
     * 获取是否禁用
     *
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }
    
    /**
     * 设置设置信息
     *
     * @param text $setting
     * @return PagesSideBar
     */
    public function setSetting($setting)
    {
        $this->setting = $setting;
        
        return $this;
    }
    
    /**
     * 获取设置信息
     *
     * @return text
     */
    public function getSetting()
    {
        return $this->setting;
    }

    /**
     * 设置创建时间
     *
     * @param integer $created
     * @return PagesSideBar
     */
    public function setCreated($created)
    {
        $this->created = $created;
        
        return $this;
    }

    /**
     * 获取创建时间
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * 设置更新时间
     *
     * @param integer $updated
     * @return PagesSideBar
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        
        return $this;
    }

    /**
     * 获取更新时间
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}