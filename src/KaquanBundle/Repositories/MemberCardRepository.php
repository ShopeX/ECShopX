<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace KaquanBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use KaquanBundle\Entities\MemberCard;

class MemberCardRepository extends EntityRepository
{
    public function update($filter, $postdata)
    {
        $membercardEntity = $this->findOneBy($filter);
        if (!$membercardEntity) {
            $membercardEntity = new MemberCard();
        }

        $em = $this->getEntityManager();
        $membercard = $this->setMemberCardData($membercardEntity, $postdata);
        $em->persist($membercard);
        $em->flush();

        $result = $this->getMemberCardData($membercardEntity);
        return $result;
    }

    public function get($filter)
    {
        $membercard = $this->findOneBy($filter);
        $result = [];
        if ($membercard) {
            $result = $this->getMemberCardData($membercard);
        }

        return $result;
    }

    private function setMemberCardData($membercardEntity, $postdata)
    {
        if (isset($postdata['company_id'])) {
            $membercardEntity->setCompanyId($postdata['company_id']);
        }
        if (isset($postdata['brand_name'])) {
            $membercardEntity->setBrandName($postdata['brand_name']);
        }
        if (isset($postdata['logo_url'])) {
            $membercardEntity->setLogoUrl($postdata['logo_url']);
        }
        if (isset($postdata['title'])) {
            $membercardEntity->setTitle($postdata['title']);
        }
        if (isset($postdata['color'])) {
            $membercardEntity->setColor($postdata['color']);
        }
        if (isset($postdata['code_type'])) {
            $membercardEntity->setCodeType($postdata['code_type']);
        }
        if (isset($postdata['background_pic_url'])) {
            $membercardEntity->setBackgroundPicUrl($postdata['background_pic_url']);
        }

        return $membercardEntity;
    }

    public function getMemberCardData($membercardEntity)
    {
        return [
            'company_id' => $membercardEntity->getCompanyId(),
            'brand_name' => $membercardEntity->getBrandName(),
            'logo_url' => $membercardEntity->getLogoUrl(),
            'title' => $membercardEntity->getTitle(),
            'color' => $membercardEntity->getColor(),
            'code_type' => $membercardEntity->getCodeType(),
            'background_pic_url' => $membercardEntity->getBackgroundPicUrl(),
            'created' => $membercardEntity->getCreated(),
            'updated' => $membercardEntity->getUpdated(),
        ];
    }
}
