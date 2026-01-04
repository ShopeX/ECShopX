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
