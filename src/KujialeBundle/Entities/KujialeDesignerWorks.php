<?php

namespace KujialeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * KujialeDesignerWorks
 *
 * @ORM\Table(name="kujiale_designer_works", indexes={@ORM\Index(name="idx_design_id", columns={"design_id"}), @ORM\Index(name="idx_user_id", columns={"user_id"}), @ORM\Index(name="idx_plan_id", columns={"plan_id"})})
 * @ORM\Entity(repositoryClass="KujialeBundle\Repositories\KujialeDesignerWorksRepository")
 */
class KujialeDesignerWorks
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false, options={"comment":"id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="design_name", type="string", length=255, nullable=false, options={"comment":"方案名称"})
     */
    private $design_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cover_pic", type="text", length=65535, nullable=true, options={"comment":"方案封面"})
     */
    private $cover_pic;

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_origin", type="bigint", nullable=true, options={"comment":"是否原创"})
     */
    private $is_origin;

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_excellent", type="bigint", nullable=true, options={"comment":"是否优秀"})
     */
    private $is_excellent;

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_real_excellent", type="bigint", nullable=true, options={"comment":"是否优秀"})
     */
    private $is_real_excellent;

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_top", type="bigint", nullable=true, options={"comment":"是否置顶"})
     */
    private $is_top;

    /**
     * @var string|null
     *
     * @ORM\Column(name="design_id", type="string", length=255, nullable=true, options={"comment":"方案ID"})
     */
    private $design_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="plan_id", type="string", length=255, nullable=true, options={"comment":"户型ID"})
     */
    private $plan_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comm_name", type="string", length=255, nullable=true, options={"comment":"小区"})
     */
    private $comm_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true, options={"comment":"城市"})
     */
    private $city;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true, options={"comment":"户型名称"})
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tag_id", type="string", length=255, nullable=true, options={"comment":"方案分类id"})
     */
    private $tag_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="design_pano_url", type="string", length=255, nullable=true, options={"comment":"全景漫游url"})
     */
    private $design_pano_url;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_avatar", type="string", length=255, nullable=true, options={"comment":"用户头像"})
     */
    private $userAvatar;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true, options={"comment":"邮箱"})
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_name", type="string", length=255, nullable=true, options={"comment":"用户名"})
     */
    private $user_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_id", type="string", length=255, nullable=true, options={"comment":"用户id"})
     */
    private $userId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_id", type="string", length=255, nullable=true, options={"comment":"组织id"})
     */
    private $organization_id;

    /**
     * @var int
     *
     * @ORM\Column(name="created", type="integer", nullable=false, options={"comment":"创建时间"})
     */
    private $created;

    /**
     * @var int|null
     *
     * @ORM\Column(name="updated", type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $updated;

    /**
     * @var int|null
     *
     * @ORM\Column(name="view_count", type="integer", nullable=true, options={"comment":"浏览量"})
     */
    private $view_count = 0;

    /**
     * @var int|null
     *
     * @ORM\Column(name="like_count", type="integer", nullable=true, options={"comment":"点赞数"})
     */
    private $like_count = 0;

    /**
     * @var int|null
     *
     * @ORM\Column(name="ku_created", type="integer", nullable=true, options={"comment":"方案更新时间"})
     */
    private $ku_created = 0;

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
     * Set designName.
     *
     * @param string $designName
     *
     * @return KujialeDesignerWorks
     */
    public function setDesignName($designName)
    {
        $this->design_name = $designName;

        return $this;
    }

    /**
     * Get designName.
     *
     * @return string
     */
    public function getDesignName()
    {
        return $this->design_name;
    }

    /**
     * Set coverPic.
     *
     * @param string|null $coverPic
     *
     * @return KujialeDesignerWorks
     */
    public function setCoverPic($coverPic = null)
    {
        $this->cover_pic = $coverPic;

        return $this;
    }

    /**
     * Get coverPic.
     *
     * @return string|null
     */
    public function getCoverPic()
    {
        return $this->cover_pic;
    }

    /**
     * Set isOrigin.
     *
     * @param int|null $isOrigin
     *
     * @return KujialeDesignerWorks
     */
    public function setIsOrigin($isOrigin = null)
    {
        $this->is_origin = $isOrigin;

        return $this;
    }

    /**
     * Get isOrigin.
     *
     * @return int|null
     */
    public function getIsOrigin()
    {
        return $this->is_origin;
    }

    /**
     * Set isExcellent.
     *
     * @param int|null $isExcellent
     *
     * @return KujialeDesignerWorks
     */
    public function setIsExcellent($isExcellent = null)
    {
        $this->is_excellent = $isExcellent;

        return $this;
    }

    /**
     * Get isExcellent.
     *
     * @return int|null
     */
    public function getIsExcellent()
    {
        return $this->is_excellent;
    }

    /**
     * Set isRealExcellent.
     *
     * @param int|null $isRealExcellent
     *
     * @return KujialeDesignerWorks
     */
    public function setIsRealExcellent($isRealExcellent = null)
    {
        $this->is_real_excellent = $isRealExcellent;

        return $this;
    }

    /**
     * Get isRealExcellent.
     *
     * @return int|null
     */
    public function getIsRealExcellent()
    {
        return $this->is_real_excellent;
    }

    /**
     * Set isTop.
     *
     * @param int|null $isTop
     *
     * @return KujialeDesignerWorks
     */
    public function setIsTop($isTop = null)
    {
        $this->is_top = $isTop;

        return $this;
    }

    /**
     * Get isTop.
     *
     * @return int|null
     */
    public function getIsTop()
    {
        return $this->is_top;
    }

    /**
     * Set designId.
     *
     * @param string|null $designId
     *
     * @return KujialeDesignerWorks
     */
    public function setDesignId($designId = null)
    {
        $this->design_id = $designId;

        return $this;
    }

    /**
     * Get designId.
     *
     * @return string|null
     */
    public function getDesignId()
    {
        return $this->design_id;
    }

    /**
     * Set planId.
     *
     * @param string|null $planId
     *
     * @return KujialeDesignerWorks
     */
    public function setPlanId($planId = null)
    {
        $this->plan_id = $planId;

        return $this;
    }

    /**
     * Get planId.
     *
     * @return string|null
     */
    public function getPlanId()
    {
        return $this->plan_id;
    }

    /**
     * Set commName.
     *
     * @param string|null $commName
     *
     * @return KujialeDesignerWorks
     */
    public function setCommName($commName = null)
    {
        $this->comm_name = $commName;

        return $this;
    }

    /**
     * Get commName.
     *
     * @return string|null
     */
    public function getCommName()
    {
        return $this->comm_name;
    }

    /**
     * Set city.
     *
     * @param string|null $city
     *
     * @return KujialeDesignerWorks
     */
    public function setCity($city = null)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set name.
     *
     * @param string|null $name
     *
     * @return KujialeDesignerWorks
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set tagId.
     *
     * @param string|null $tagId
     *
     * @return KujialeDesignerWorks
     */
    public function setTagId($tagId = null)
    {
        $this->tag_id = $tagId;

        return $this;
    }

    /**
     * Get tagId.
     *
     * @return string|null
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set designPanoUrl.
     *
     * @param string|null $designPanoUrl
     *
     * @return KujialeDesignerWorks
     */
    public function setDesignPanoUrl($designPanoUrl = null)
    {
        $this->design_pano_url = $designPanoUrl;

        return $this;
    }

    /**
     * Get designPanoUrl.
     *
     * @return string|null
     */
    public function getDesignPanoUrl()
    {
        return $this->design_pano_url;
    }

    /**
     * Set userAvatar.
     *
     * @param string|null $userAvatar
     *
     * @return KujialeDesignerWorks
     */
    public function setUserAvatar($userAvatar = null)
    {
        $this->userAvatar = $userAvatar;

        return $this;
    }

    /**
     * Get userAvatar.
     *
     * @return string|null
     */
    public function getUserAvatar()
    {
        return $this->userAvatar;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return KujialeDesignerWorks
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set userName.
     *
     * @param string|null $userName
     *
     * @return KujialeDesignerWorks
     */
    public function setUserName($userName = null)
    {
        $this->user_name = $userName;

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string|null
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Set userId.
     *
     * @param string|null $userId
     *
     * @return KujialeDesignerWorks
     */
    public function setUserId($userId = null)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return string|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set organizationId.
     *
     * @param string|null $organizationId
     *
     * @return KujialeDesignerWorks
     */
    public function setOrganizationId($organizationId = null)
    {
        $this->organization_id = $organizationId;

        return $this;
    }

    /**
     * Get organizationId.
     *
     * @return string|null
     */
    public function getOrganizationId()
    {
        return $this->organization_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return KujialeDesignerWorks
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
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
     * @return KujialeDesignerWorks
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

   /**
     * Set ViewCount.
     *
     * @param int|null $viewCount
     *
     * @return KujialeDesignerWorks
     */
    public function setViewCount($viewCount = null)
    {
        $this->view_count = $viewCount;

        return $this;
    }

    /**
     * Get ViewCount.
     *
     * @return int|null
     */
    public function getViewCount()
    {
        return $this->view_count;
    }

    /**
     * Set LikeCount.
     *
     * @param int|null $likeCount
     *
     * @return KujialeDesignerWorks
     */
    public function setLikeCount($likeCount = null)
    {
        $this->like_count = $likeCount;

        return $this;
    }

    /**
     * Get LikeCount.
     *
     * @return int|null
     */
    public function getLikeCount()
    {
        return $this->like_count;
    }

    /**
     * Set KuCreated.
     *
     * @param int|null $kuCreated
     *
     * @return KujialeDesignerWorks
     */
    public function setKuCreated($kuCreated = null)
    {
        $this->ku_created = $kuCreated;

        return $this;
    }

    /**
     * Get KuCreated.
     *
     * @return int|null
     */
    public function getKuCreated()
    {
        return $this->ku_created;
    }
}
