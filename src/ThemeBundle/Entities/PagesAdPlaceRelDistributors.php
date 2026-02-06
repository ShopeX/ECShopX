<?php

namespace ThemeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * PagesAdPlaceRelDistributors 广告位与店铺关联表
 *
 * @ORM\Table(name="pages_ad_place_rel_distributors", options={"comment"="广告位与店铺关联表"})
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\PagesAdPlaceRelDistributorsRepository")
 */
class PagesAdPlaceRelDistributors
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
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     */
    private $distributor_id;

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
     * @return PagesAdPlaceRelDistributors
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
     * @return PagesAdPlaceRelDistributors
     */
    public function setAdPlaceId($adPlaceId)
    {
        $this->ad_place_id = $adPlaceId;
        
        return $this;
    }
    
    /**
     * Get distributor_id
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }
    
    /**
     * Set distributor_id
     *
     * @param integer $distributorId
     *
     * @return PagesAdPlaceRelDistributors
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;
        
        return $this;
    }
}