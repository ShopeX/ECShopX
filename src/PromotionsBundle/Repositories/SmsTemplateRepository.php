<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use PromotionsBundle\Entities\SmsTemplate;

class SmsTemplateRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'sms_template';

    /**
     * 添加短信模版
     */
    public function create($params)
    {
        $entity = new SmsTemplate();

        $entity->setCompanyId($params['company_id']);
        $entity->setSmsType($params['sms_type']);
        $entity->setTmplType($params['tmpl_type']);
        $entity->setContent($params['content']);
        $entity->setIsOpen($params['is_open']);
        $entity->setTmplName($params['tmpl_name']);
        $entity->setSendTimeDesc(json_encode($params['send_time_desc']));
        $entity->setCreated(time());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        $result = [
            'tmpl_name' => $entity->getTmplName(),
        ];

        return $result;
    }

    //获取短信模版列表
    public function lists($filter, $orderBy = ['created' => 'DESC'], $pageSize = 100, $page = 1)
    {
        $entityPropArr = $this->findBy($filter, $orderBy, $pageSize, $pageSize * ($page - 1));
        $lists = [];
        foreach ($entityPropArr as $entityProp) {
            $lists[] = [
                'company_id' => $entityProp->getCompanyId(),
                'sms_type' => $entityProp->getSmsType(),
                'tmpl_type' => $entityProp->getTmplType(),
                'content' => $entityProp->getContent(),
                'is_open' => $entityProp->getIsOpen(),
                'tmpl_name' => $entityProp->getTmplName(),
                'send_time_desc' => json_decode($entityProp->getSendTimeDesc()),
                'created' => $entityProp->getCreated(),
            ];
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($filter);
        $res['total_count'] = intval($total);
        $res['list'] = $lists;

        return $res;
    }

    public function updateTemplate($companyId, $templateName, $params)
    {
        $filter = [
            'company_id' => $companyId,
            'tmpl_name' => $templateName,
        ];
        $entityProp = $this->findOneBy($filter);
        if (isset($params['is_open'])) {
            $entityProp->setIsOpen($params['is_open']);
        }
        if (isset($params['send_time_desc'])) {
            $entityProp->setSendTimeDesc(json_encode($params['send_time_desc']));
        }
        if (isset($params['content'])) {
            $entityProp->setContent($params['content']);
        }
        $em = $this->getEntityManager();
        $em->persist($entityProp);
        $em->flush();
        $result = [
            'tmpl_name' => $entityProp->getTmplName(),
        ];
        return $result;
    }

    /**
     * 获取模版
     */
    public function get($filter)
    {
        $entityProp = $this->findOneBy($filter);
        $result = [];
        if ($entityProp) {
            $result = [
                'company_id' => $entityProp->getCompanyId(),
                'sms_type' => $entityProp->getSmsType(),
                'tmpl_type' => $entityProp->getTmplType(),
                'content' => $entityProp->getContent(),
                'is_open' => $entityProp->getIsOpen(),
                'tmpl_name' => $entityProp->getTmplName(),
                'send_time_desc' => json_decode($entityProp->getSendTimeDesc()),
                'created' => $entityProp->getCreated(),
            ];
        }
        return $result;
    }
}
