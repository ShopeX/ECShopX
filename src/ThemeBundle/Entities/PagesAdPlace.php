<?php

namespace ThemeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * pages_ad_place 广告位设置
 *
 * @ORM\Table(name="pages_ad_place", options={"comment":"广告位设置"})
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\PagesAdPlaceRepository")
 */
class PagesAdPlace
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
     * @var integer
     *
     * @ORM\Column(name="use_bound", type="integer", nullable=true, options={"comment":"适用范围: 0:全部,1:指定店铺", "default": 0})
     */
    private $use_bound = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_type", type="string", length=20, options={"comment":"广告类型：弹窗=>popup，轮播图=>carousel"})
     */
    private $ad_type;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, options={"comment":"广告位名称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="pages", type="string", length=30, options={"comment":"关联页面"})
     */
    private $pages;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="bigint", options={"comment":"开始时间"})
     */
    private $start_time;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="bigint", options={"comment":"结束时间"})
     */
    private $end_time;

    /**
     * @var text
     *
     * @ORM\Column(name="setting", type="text", nullable=true, options={"comment":"设置"})
     */
    private $setting;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="auto_play", type="integer", options={"comment":"自动播放", "default": 0})
     */
    private $auto_play = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="play_interval", type="integer", options={"comment":"播放间隔时间", "default": 3})
     */
    private $play_interval = 3;

    /**
     * @var integer
     *
     * @ORM\Column(name="auto_close", type="integer", options={"comment":"自动关闭", "default": 0})
     */
    private $auto_close = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="close_delay", type="integer", options={"comment":"关闭延迟时间", "default": 10})
     */
    private $close_delay = 10;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="bigint", options={"comment":"添加者ID: 如店铺ID", "default": 0})
     */
    private $source_id = 0;

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
     * @var string
     *
     * @ORM\Column(name="audit_status", type="string", options={"comment":"审核状态 submitting待提交 processing审核中 approved成功 rejected审核拒绝", "default":"submitting"})
     */
    private $audit_status = 'submitting';

    /**
     * @var string
     *
     * @ORM\Column(name="audit_remark", type="string", nullable=true, options={"comment":"审核备注"})
     */
    private $audit_remark;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", options={"comment":"排序", "default": 0})
     */
    private $sort = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="tracking_code", type="string", length=100, nullable=true, options={"comment":"埋点上报参数"})
     */
    private $tracking_code;

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
     * @return PagesAdPlace
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
     * @return PagesAdPlace
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
     * 设置适用范围
     *
     * @param integer $use_bound
     * @return PagesAdPlace
     */
    public function setUseBound($use_bound)
    {
        $this->use_bound = $use_bound;

        return $this;
    }

    /**
     * 获取适用范围
     *
     * @return integer
     */
    public function getUseBound()
    {
        return $this->use_bound;
    }

    /**
     * 设置广告类型
     *
     * @param string $ad_type
     * @return PagesAdPlace
     */
    public function setAdType($ad_type)
    {
        $this->ad_type = $ad_type;

        return $this;
    }

    /**
     * 获取广告类型
     *
     * @return string
     */
    public function getAdType()
    {
        return $this->ad_type;
    }

    /**
     * 设置广告位名称
     *
     * @param string $name
     * @return PagesAdPlace
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 获取广告位名称
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
     * @return PagesAdPlace
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
     * 设置开始时间
     *
     * @param integer $start_time
     * @return PagesAdPlace
     */
    public function setStartTime($start_time)
    {
        $this->start_time = $start_time;

        return $this;
    }

    /**
     * 获取开始时间
     *
     * @return integer
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * 设置结束时间
     *
     * @param integer $end_time
     * @return PagesAdPlace
     */
    public function setEndTime($end_time)
    {
        $this->end_time = $end_time;

        return $this;
    }

    /**
     * 获取结束时间
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * 设置配置信息
     *
     * @param string $setting
     * @return PagesAdPlace
     */
    public function setSetting($setting)
    {
        $this->setting = $setting;

        return $this;
    }

    /**
     * 获取配置信息
     *
     * @return string
     */
    public function getSetting()
    {
        return $this->setting;
    }

    /**
     * 设置是否自动播放
     *
     * @param integer $auto_play
     * @return PagesAdPlace
     */
    public function setAutoPlay($auto_play)
    {
        $this->auto_play = $auto_play;

        return $this;
    }

    /**
     * 获取是否自动播放
     *
     * @return integer
     */
    public function getAutoPlay()
    {
        return $this->auto_play;
    }

    /**
     * 设置播放间隔时间
     *
     * @param integer $play_interval
     * @return PagesAdPlace
     */
    public function setPlayInterval($play_interval)
    {
        $this->play_interval = $play_interval;

        return $this;
    }

    /**
     * 获取播放间隔时间
     *
     * @return integer
     */
    public function getPlayInterval()
    {
        return $this->play_interval;
    }

    /**
     * 设置是否自动关闭
     *
     * @param integer $auto_close
     * @return PagesAdPlace
     */
    public function setAutoClose($auto_close)
    {
        $this->auto_close = $auto_close;

        return $this;
    }

    /**
     * 获取是否自动关闭
     *
     * @return integer
     */
    public function getAutoClose()
    {
        return $this->auto_close;
    }

    /**
     * 设置关闭延迟时间
     *
     * @param integer $close_delay
     * @return PagesAdPlace
     */
    public function setCloseDelay($close_delay)
    {
        $this->close_delay = $close_delay;

        return $this;
    }

    /**
     * 获取关闭延迟时间
     *
     * @return integer
     */
    public function getCloseDelay()
    {
        return $this->close_delay;
    }

    /**
     * 设置添加者ID
     *
     * @param integer $source_id
     * @return PagesAdPlace
     */
    public function setSourceId($source_id)
    {
        $this->source_id = $source_id;

        return $this;
    }

    /**
     * 获取添加者ID
     *
     * @return integer
     */
    public function getSourceId()
    {
        return $this->source_id;
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
     * 获取更新时间
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * 设置审核状态
     *
     * @param string $audit_status
     * @return PagesAdPlace
     */
    public function setAuditStatus($audit_status)
    {
        $this->audit_status = $audit_status;

        return $this;
    }

    /**
     * 获取审核状态
     *
     * @return string
     */
    public function getAuditStatus()
    {
        return $this->audit_status;
    }

    /**
     * 设置审核备注
     *
     * @param string $audit_remark
     * @return PagesAdPlace
     */
    public function setAuditRemark($audit_remark)
    {
        $this->audit_remark = $audit_remark;

        return $this;
    }

    /**
     * 获取审核备注
     *
     * @return string
     */
    public function getAuditRemark()
    {
        return $this->audit_remark;
    }

    /**
     * 设置排序
     *
     * @param integer $sort
     * @return PagesAdPlace
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * 获取排序
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * 设置埋点上报参数
     *
     * @param string $tracking_code
     * @return PagesAdPlace
     */
    public function setTrackingCode($tracking_code)
    {
        $this->tracking_code = $tracking_code;

        return $this;
    }

    /**
     * 获取埋点上报参数
     *
     * @return string
     */
    public function getTrackingCode()
    {
        return $this->tracking_code;
    }
}

    