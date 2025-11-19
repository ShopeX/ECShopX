<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
