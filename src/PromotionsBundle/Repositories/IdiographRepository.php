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

namespace PromotionsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Entities\SmsIdiograph;

class IdiographRepository extends EntityRepository
{
    public $table = 'sms_idiograph';

    public function create($shopexUid, $companyId, $content)
    {
        // ShopEx EcShopX Core Module
        $idiograph = new SmsIdiograph();
        $idiograph->setCompanyId($companyId);
        $idiograph->setShopexUid($shopexUid);
        $idiograph->setIdiograph($content);
        $idiograph->setCreated(time());

        $em = $this->getEntityManager();
        $em->persist($idiograph);
        $em->flush();

        $result['id'] = $idiograph->getId();
        return $result;
    }

    public function update($shopexUid, $companyId, $content)
    {
        $idiograph = $this->findOneBy(['shopex_uid' => $shopexUid, 'company_id' => $companyId]);
        if (!$idiograph) {
            throw new ResourceException(trans('PromotionsBundle.sms_signature_not_exist'));
        }

        $idiograph->setIdiograph($content);

        $em = $this->getEntityManager();
        $em->persist($idiograph);
        $em->flush();

        $result['id'] = $idiograph->getId();
        return $result;
    }

    public function get($filter)
    {
        return $this->findOneBy($filter);
    }
}
