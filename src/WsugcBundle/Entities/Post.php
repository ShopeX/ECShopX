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

//笔记
namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * WsugcPost 笔记
 *
 * @ORM\Table(name="wsugc_post", options={"comment"="笔记"}, indexes={
 *    @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *    @ORM\Index(name="idx_company_id", columns={"company_id"})
 * }),
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\PostRepository")
 */
class Post
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="post_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $post_id;

   /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;


      /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint",nullable=true,options={"comment":"管理员id", "default": 0})
     */
    private $operator_id;

    /**
     * @var source
     *
     * @ORM\Column(name="source", type="integer",nullable=true,options={"comment":"来源 1用户,2官方", "default": "1"})
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", options={"comment":"标题"})
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="likes", type="integer", options={"comment":"点赞数"})
     */
    private $likes = 0;

       /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string",nullable=true, options={"comment":"ip地址"})
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string",nullable=true, options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="cover", type="string",nullable=true, options={"comment":"封面图"})
     */
    private $cover;

    /**
     * @var string
     *
     * @ORM\Column(name="video", type="string", nullable=true,options={"comment":"视频"})
     */
    private $video;


    /**
     * @var string
     *
     * @ORM\Column(name="video_ratio", type="string", nullable=true,options={"comment":"视频比例"})
     */
    private $video_ratio;

    /**
     * @var string
     *
     * @ORM\Column(name="video_place", type="string", nullable=true,options={"comment":"视频位置"})
     */
    private $video_place;

    /**
     * @var string
     *
     * @ORM\Column(name="video_thumb", type="string", nullable=true,options={"comment":"视频缩略图"})
     */
    private $video_thumb;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", nullable=true,options={"comment":"坐标"})
     */
    private $position;


    /**
     * @var integer
     *
     * @ORM\Column(name="is_top", type="integer",nullable=false, options={"comment":"是否置顶.", "default": 0})
     */
    private $is_top;

    
    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", nullable=true,options={"comment":"地址"})
     */
    private $address;

    /**
     * @var topics
     *
     * @ORM\Column(name="topics", type="text", nullable=true,options={"comment":"话题"})
     */
    private $topics;

    /**
     * @var badges
     *
     * @ORM\Column(name="badges", type="text", nullable=true,options={"comment":"角标"})
     */
    private $badges;
   

    /**
     * @var goods
     *
     * @ORM\Column(name="goods", type="text", nullable=true,options={"comment":"商品"})
     */
    private $goods;

    /**
     * @var text
     *
     * @ORM\Column(name="images", type="text", nullable=true,options={"comment":"多图","default":""})
     */
    private $images;

    /**
     * @var text
     *
     * @ORM\Column(name="image_path", type="text", nullable=true,options={"comment":"多图相对路径","default":""})
    */
    private $image_path;


    /**
     * @var text
     *
     * @ORM\Column(name="image_tag", type="text", nullable=true,options={"comment":"图片的tag信息","default":""})
    */
    private $image_tag;

    /**
     * @var text
     *
     * @ORM\Column(name="content", type="text", nullable=true,options={"comment":"内容","default":""})
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="p_order", type="integer", nullable=true,options={"comment":"排序","default":0})
     */
    private $p_order;
   

        /**
     * @var integer
     *
     * @ORM\Column(name="share_nums", type="integer", nullable=true,options={"comment":"分享次数","default":0})
     */
    private $share_nums;

   /**
     * @var integer
     *
     * @ORM\Column(name="view_auth", type="integer", options={"comment":"可见状态0所有，1仅自己", "default": 0})
     */
    private $view_auth = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_draft", type="integer", options={"comment":"是否草稿0否，1是", "default": 0})
     */
    private $is_draft = 0;


    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer", options={"comment":"发布状态", "default": 0})
     */
    private $enabled = 0;



    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="integer", options={"comment":"是否无效(删除)", "default": 0})
     */
    private $disabled=0;


    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", options={"comment":"审核状态(0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝)", "default": 0})
     */
    private $status = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="title_status", type="integer", options={"comment":"标题审核状态(0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝)", "default": 0})
     */
    private $title_status = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="content_status", type="integer", options={"comment":"内容审核状态(0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝)", "default": 0})
     */
    private $content_status = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="image_status", type="integer", options={"comment":"图片审核状态(0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝)", "default": 0})
     */
    private $image_status = '0';


    /**
     * @var string
     *
     * @ORM\Column(name="trace_ids", type="text", options={"comment":"微信内容审查-图片内容追踪id集合,仅ID ,111:false,2222:false,"})
     */
    private $trace_ids = '';


    /**
     * @var string
     *
     * @ORM\Column(name="mediacheck_traceid", type="text", options={"comment":"微信内容审查-图片内容追踪id集合"})
     */
    private $mediacheck_traceid = '';
    
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
     * @var \DateTime $ai_verify_time
     *
     * @ORM\Column(name="ai_verify_time", type="bigint", nullable=true,options={"comment":"机器审核时间","default":"0"})
     */
    protected $ai_verify_time;    


    /**
     * @var \DateTime $manual_verify_time
     *
     * @ORM\Column(name="manual_verify_time", type="bigint", nullable=true,options={"comment":"人工审核时间","default":"0"})
     */
    protected $manual_verify_time; 


        /**
     * @var string
     *
     * @ORM\Column(name="ai_refuse_reason", type="string",nullable=true, options={"comment":"机器拒绝理由"})
     */
    private $ai_refuse_reason;

    /**
     * @var string
     *
     * @ORM\Column(name="manual_refuse_reason", type="string",nullable=true, options={"comment":"人工拒绝理由"})
     */
    private $manual_refuse_reason;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;



    /**
     * Get postId.
     *
     * @return int
     */
    public function getPostId()
    {
        return $this->post_id;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return WsugcPost
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        // KEY: U2hvcEV4
        return $this->user_id;
    }



        /**
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return WsugcPost
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

   /**
     * Set ip.
     *
     * @param string $ip
     *
     * @return WsugcPost
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip.
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
    /**
     * Set title.
     *
     * @param string $title
     *
     * @return WsugcPost
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set likes.
     *
     * @param string $likes
     *
     * @return Comment
     */
    public function setLikes($likes)
    {
        $this->likes = $likes;

        return $this;
    }

    /**
     * Get likes.
     *
     * @return string
     */
    public function getLikes()
    {
        return $this->likes;
    }

    /**
     * Set mobile.
     *
     * @param string $mobile
     *
     * @return WsugcPost
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set cover.
     *
     * @param string $cover
     *
     * @return WsugcPost
     */
    public function setCover($cover)
    {
        $this->cover = $cover;

        return $this;
    }

    /**
     * Get cover.
     *
     * @return string
     */
    public function getCover()
    {
        return $this->cover;
    }

    /**
     * Set video.
     *
     * @param string|null $video
     *
     * @return WsugcPost
     */
    public function setVideo($video = null)
    {
        $this->video = $video;

        return $this;
    }

    /**
     * Get video.
     *
     * @return string|null
     */
    public function getVideo()
    {
        return $this->video;
    }

 /**
     * Set isTop.
     *
     * @param int $isTop
     *
     * @return Topic
     */
    public function setIsTop($isTop)
    {
        $this->is_top = $isTop;

        return $this;
    }

    /**
     * Get isTop.
     *
     * @return int
     */
    public function getIsTop()
    {
        return $this->is_top;
    }


     /**
     * Set videoRatio.
     *
     * @param string|null $video_ratio
     *
     * @return WsugcPost
     */
    public function setVideoRatio($video_ratio = null)
    {
        $this->video_ratio = $video_ratio;

        return $this;
    }

    /**
     * Get videoRatio.
     *
     * @return string|null
     */
    public function getVideoRatio()
    {
        return $this->video_ratio;
    }

    /**
     * Set videoPlace.
     *
     * @param string|null $videoPlace
     *
     * @return WsugcPost
     */
    public function setVideoPlace($videoPlace = null)
    {
        $this->video_place = $videoPlace;

        return $this;
    }

    /**
     * Get videoPlace.
     *
     * @return string|null
     */
    public function getVideoPlace()
    {
        return $this->video_place;
    }

    /**
     * Set videoThumb.
     *
     * @param string|null $videoThumb
     *
     * @return WsugcPost
     */
    public function setVideoThumb($videoThumb = null)
    {
        $this->video_thumb = $videoThumb;

        return $this;
    }

    /**
     * Get videoThumb.
     *
     * @return string|null
     */
    public function getVideoThumb()
    {
        return $this->video_thumb;
    }

    /**
     * Set position.
     *
     * @param string|null $position
     *
     * @return WsugcPost
     */
    public function setPosition($position = null)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return string|null
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set address.
     *
     * @param string|null $address
     *
     * @return WsugcPost
     */
    public function setAddress($address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set images.
     *
     * @param string|null $images
     *
     * @return WsugcPost
     */
    public function setImages($images = null)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * Get images.
     *
     * @return string|null
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Set content.
     *
     * @param string|null $content
     *
     * @return WsugcPost
     */
    public function setContent($content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }



    /**
     * Set shareNums.
     *
     * @param int|null $shareNums
     *
     * @return WsugcPost
     */
    public function setShareNums($shareNums = null)
    {
        $this->share_nums = $shareNums;

        return $this;
    }

    /**
     * Get pOrder.
     *
     * @return int|null
     */
    public function getShareNums()
    {
        return $this->share_nums;
    }


    /**
     * Set pOrder.
     *
     * @param int|null $pOrder
     *
     * @return WsugcPost
     */
    public function setPOrder($pOrder = null)
    {
        $this->p_order = $pOrder;

        return $this;
    }

    /**
     * Get pOrder.
     *
     * @return int|null
     */
    public function getPOrder()
    {
        return $this->p_order;
    }

    /**
     * Set viewAuth.
     *
     * @param int $viewAuth
     *
     * @return WsugcPost
     */
    public function setViewAuth($viewAuth)
    {
        $this->view_auth = $viewAuth;

        return $this;
    }

    /**
     * Get viewAuth.
     *
     * @return int
     */
    public function getViewAuth()
    {
        return $this->view_auth;
    }

    /**
     * Set isDraft.
     *
     * @param int $isDraft
     *
     * @return WsugcPost
     */
    public function setIsDraft($isDraft)
    {
        $this->is_draft = $isDraft;

        return $this;
    }

    /**
     * Get isDraft.
     *
     * @return int
     */
    public function getIsDraft()
    {
        return $this->is_draft;
    }

    /**
     * Set enabled.
     *
     * @param int $enabled
     *
     * @return WsugcPost
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled.
     *
     * @return int
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return WsugcPost
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return WsugcPost
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
     * @return WsugcPost
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
     * Set verifyTime.
     *
     * @param int|null $verifyTime
     *
     * @return WsugcPost
     */
    public function setAiVerifyTime($aiVerifyTime = null)
    {
        $this->ai_verify_time = $aiVerifyTime;

        return $this;
    }

    /**
     * Get AiVerifyTime.
     *
     * @return int|null
     */
    public function getAiVerifyTime()
    {
        return $this->ai_verify_time;
    }


        /**
     * Set verifyTime.
     *
     * @param int|null $verifyTime
     *
     * @return WsugcPost
     */
    public function setManualVerifyTime($manualVerifyTime = null)
    {
        $this->manual_verify_time = $manualVerifyTime;

        return $this;
    }

    /**
     * Get manualVerifyTime.
     *
     * @return int|null
     */
    public function getManualVerifyTime()
    {
        return $this->manual_verify_time;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return WsugcPost
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set topics.
     *
     * @param \text|null $topics
     *
     * @return Post
     */
    public function setTopics($topics = null)
    {
        $this->topics = $topics;

        return $this;
    }

    /**
     * Get topics.
     *
     * @return \text|null
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
     * Set badges.
     *
     * @param \text|null $badges
     *
     * @return Post
     */
    public function setBadges($badges = null)
    {
        $this->badges = $badges;

        return $this;
    }

    /**
     * Get badges.
     *
     * @return \text|null
     */
    public function getBadges()
    {
        return $this->badges;
    }

    /**
     * Set goods.
     *
     * @param \text|null $goods
     *
     * @return Post
     */
    public function setGoods($goods = null)
    {
        $this->goods = $goods;

        return $this;
    }

    /**
     * Get goods.
     *
     * @return \text|null
     */
    public function getGoods()
    {
        return $this->goods;
    }

    /**
     * Set imagePath.
     *
     * @param string|null $imagePath
     *
     * @return Post
     */
    public function setImagePath($imagePath = null)
    {
        $this->image_path = $imagePath;

        return $this;
    }

    /**
     * Get imagePath.
     *
     * @return string|null
     */
    public function getImagePath()
    {
        return $this->image_path;
    }

    /**
     * Set imageTag.
     *
     * @param string|null $imageTag
     *
     * @return Post
     */
    public function setImageTag($imageTag = null)
    {
        $this->image_tag = $imageTag;

        return $this;
    }

    /**
     * Get imageTag.
     *
     * @return string|null
     */
    public function getImageTag()
    {
        return $this->image_tag;
    }

     /**
     * Set source.
     *
     * @param int $source
     *
     * @return Topic
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

     /**
     * Set aiRefuseReason.
     *
     * @param string|null $aiRefuseReason
     *
     * @return Comment
     */
    public function setAiRefuseReason($aiRefuseReason = null)
    {
        $this->ai_refuse_reason = $aiRefuseReason;

        return $this;
    }

    /**
     * Get aiRefuseReason.
     *
     * @return string|null
     */
    public function getAiRefuseReason()
    {
        return $this->ai_refuse_reason;
    }

    /**
     * Set manualRefuseReason.
     *
     * @param string|null $manualRefuseReason
     *
     * @return Comment
     */
    public function setManualRefuseReason($manualRefuseReason = null)
    {
        $this->manual_refuse_reason = $manualRefuseReason;

        return $this;
    }

    /**
     * Get manualRefuseReason.
     *
     * @return string|null
     */
    public function getManualRefuseReason()
    {
        return $this->manual_refuse_reason;
    }

       /**
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return Comment
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }




     /**
     * Set titleStatus.
     *
     * @param string|null $titleStatus
     *
     * @return WsugcPost
     */
    public function setTitleStatus($titleStatus = null)
    {
        $this->title_status = $titleStatus;

        return $this;
    }

    /**
     * Get titleStatus.
     *
     * @return string|null
     */
    public function getTitleStatus()
    {
        return $this->title_status;
    }


    /**
     * Set contentStatus.
     *
     * @param string|null $contentStatus
     *
     * @return WsugcPost
     */
    public function setContentStatus($contentStatus = null)
    {
        $this->content_status = $contentStatus;

        return $this;
    }

    /**
     * Get contentStatus.
     *
     * @return string|null
     */
    public function getContentStatus()
    {
        return $this->content_status;
    }

    /**
     * Set imageStatus.
     *
     * @param string|null $imageStatus
     *
     * @return WsugcPost
     */
    public function setImageStatus($imageStatus = null)
    {
        $this->image_status = $imageStatus;

        return $this;
    }

    /**
     * Get imageStatus.
     *
     * @return string|null
     */
    public function getImageStatus()
    {
        return $this->image_status;
    }


    /**
     * Set mediacheckTraceid.
     *
     * @param string|null $mediacheckTraceid
     *
     * @return WsugcPost
     */
    public function setMediacheckTraceid($mediacheckTraceid = null)
    {
        $this->mediacheck_traceid = $mediacheckTraceid;

        return $this;
    }

    /**
     * Get mediacheckTraceid.
     *
     * @return string|null
     */
    public function getMediacheckTraceid()
    {
        return $this->mediacheck_traceid;
    }


      /**
     * Set traceIds.
     *
     * @param string|null $traceIds
     *
     * @return WsugcPost
     */
    public function setTraceIds($traceIds = null)
    {
        $this->trace_ids = $traceIds;

        return $this;
    }

    /**
     * Get traceIds.
     *
     * @return string|null
     */
    public function getTraceIds()
    {
        return $this->trace_ids;
    }
}
