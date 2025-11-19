<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * WechatTags 微信粉丝标签表
 *
 * @ORM\Table(name="wechat_tags", options={"comment"="微信粉丝标签表"})
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\WechatTagsRepository")
 */
class WechatTags
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="tag_id", type="bigint", options={"comment"="标签id"})
     */
    private $tag_id;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_name", type="string", length=50, options={"comment"="标签名称"})
     */
    private $tag_name;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="authorizer_appid", type="string", length=64, options={"comment"="公众号appid"})
     */
    private $authorizer_appid;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * Set tagId
     *
     * @param integer $tagId
     *
     * @return WechatTags
     */
    public function setTagId($tagId)
    {
        $this->tag_id = $tagId;

        return $this;
    }

    /**
     * Get tagId
     *
     * @return integer
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set tagName
     *
     * @param string $tagName
     *
     * @return WechatTags
     */
    public function setTagName($tagName)
    {
        $this->tag_name = $tagName;

        return $this;
    }

    /**
     * Get tagName
     *
     * @return string
     */
    public function getTagName()
    {
        return $this->tag_name;
    }

    /**
     * Set authorizerAppid
     *
     * @param string $authorizerAppid
     *
     * @return WechatTags
     */
    public function setAuthorizerAppid($authorizerAppid)
    {
        $this->authorizer_appid = $authorizerAppid;

        return $this;
    }

    /**
     * Get authorizerAppid
     *
     * @return string
     */
    public function getAuthorizerAppid()
    {
        return $this->authorizer_appid;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return WechatTags
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
     * Set created
     *
     * @param integer $created
     *
     * @return WechatTags
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

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return WechatTags
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
