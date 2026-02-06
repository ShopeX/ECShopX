<?php

namespace ThemeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * PagesAdPlaceRelMemberTags 广告位与人群标签关联表
 *
 * @ORM\Table(name="pages_ad_place_rel_member_tags", options={"comment"="广告位与人群标签关联表"})
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\PagesAdPlaceRelMemberTagsRepository")
 */
class PagesAdPlaceRelMemberTags
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="ad_place_id", type="bigint", options={"comment":"广告位id"})
     */
    private $ad_place_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="tag_id", type="bigint", options={"comment":"人群标签id"})
     */
    private $tag_id;

    /**
     * Get company_id
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }
    
    /**
     * Set company_id
     *
     * @param integer $companyId
     *
     * @return PagesAdPlaceRelMemberTags
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get ad_place_id
     *
     * @return integer
     */
    public function getAdPlaceId()
    {
        return $this->ad_place_id;
    }
    
    /**
     * Set ad_place_id
     *
     * @param integer $adPlaceId
     *
     * @return PagesAdPlaceRelMemberTags
     */
    public function setAdPlaceId($adPlaceId)
    {
        $this->ad_place_id = $adPlaceId;
        
        return $this;
    }
    
    /**
     * Get tag_id
     *
     * @return integer
     */
    public function getTagId()
    {
        return $this->tag_id;
    }
    
    /**
     * Set tag_id
     *
     * @param integer $tagId
     *
     * @return PagesAdPlaceRelMemberTags
     */
    public function setTagId($tagId)
    {
        $this->tag_id = $tagId;
        
        return $this;
    }
}