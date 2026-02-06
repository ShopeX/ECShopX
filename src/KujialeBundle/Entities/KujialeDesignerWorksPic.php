<?php

namespace KujialeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * KujialeDesignerWorksPic
 *
 * @ORM\Table(name="kujiale_designer_works_pic", indexes={@ORM\Index(name="idx_design_id", columns={"design_id"}), @ORM\Index(name="idx_plan_id", columns={"plan_id"})})
 * @ORM\Entity(repositoryClass="KujialeBundle\Repositories\KujialeDesignerWorksPicRepository")
 */
class KujialeDesignerWorksPic
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
     * @ORM\Column(name="pic_id", type="string", length=255, nullable=false, options={"comment":"渲染图ID"})
     */
    private $pic_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pic_type", type="string", length=255, nullable=true, options={"comment":"渲染图类型。0表示普通渲染图，1表示全景图，3表示俯视图"})
     */
    private $pic_type;

    /**
     * @var int|null
     *
     * @ORM\Column(name="pic_detail_type", type="bigint", nullable=true, options={"comment":"渲染图类型细分"})
     */
    private $pic_detail_type;

    /**
     * @var string|null
     *
     * @ORM\Column(name="room_name", type="string", length=255, nullable=true, options={"comment":"渲染图所属房间的名字"})
     */
    private $room_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="img", type="string", length=255, nullable=true, options={"comment":"渲染图URL"})
     */
    private $img;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pano_link", type="string", length=255, nullable=true, options={"comment":"全景图的链接地址"})
     */
    private $pano_link;

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
     * @ORM\Column(name="level", type="string", length=255, nullable=true, options={"comment":"渲染图所在房间的楼层信息，正为地上，负为地下室，不存在0层"})
     */
    private $level;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set PicId.
     *
     * @param string $picId
     *
     * @return KujialeDesignerWorksPic
     */
    public function setPicId($picId)
    {
        $this->pic_id = $picId;

        return $this;
    }

    /**
     * Get PicId.
     *
     * @return string
     */
    public function getPicId()
    {
        return $this->pic_id;
    }

    /**
     * Set PicType.
     *
     * @param string|null $picType
     *
     * @return KujialeDesignerWorksPic
     */
    public function setPicType($picType = null)
    {
        $this->pic_type = $picType;

        return $this;
    }

    /**
     * Get PicType.
     *
     * @return string|null
     */
    public function getPicType()
    {
        return $this->pic_type;
    }

    /**
     * Set PicDetailType.
     *
     * @param int|null $picDetailType
     *
     * @return KujialeDesignerWorksPic
     */
    public function setPicDetailType($picDetailType = null)
    {
        $this->pic_detail_type = $picDetailType;

        return $this;
    }

    /**
     * Get PicDetailType.
     *
     * @return int|null
     */
    public function getPicDetailType()
    {
        return $this->pic_detail_type;
    }

    /**
     * Set RoomName.
     *
     * @param int|null $roomName
     *
     * @return KujialeDesignerWorksPic
     */
    public function setRoomName($roomName = null)
    {
        $this->room_name = $roomName;

        return $this;
    }

    /**
     * Get RoomName.
     *
     * @return int|null
     */
    public function getRoomName()
    {
        return $this->room_name;
    }

    /**
     * Set img.
     *
     * @param int|null $img
     *
     * @return KujialeDesignerWorksPic
     */
    public function setImg($img = null)
    {
        $this->img = $img;

        return $this;
    }

    /**
     * Get img.
     *
     * @return int|null
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * Set panoLink.
     *
     * @param int|null $panoLink
     *
     * @return KujialeDesignerWorksPic
     */
    public function setPanoLink($PanoLink = null)
    {
        $this->pano_link = $PanoLink;

        return $this;
    }

    /**
     * Get panoLink.
     *
     * @return int|null
     */
    public function getPanoLink()
    {
        return $this->pano_link;
    }

    /**
     * Set designId.
     *
     * @param string|null $designId
     *
     * @return KujialeDesignerWorksPic
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
     * @return KujialeDesignerWorksPic
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
     * @param string|null $level
     *
     * @return KujialeDesignerWorksPic
     */
    public function setLevel($level = null)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get commName.
     *
     * @return string|null
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return KujialeDesignerWorksPic
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
     * @return KujialeDesignerWorksPic
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
}
