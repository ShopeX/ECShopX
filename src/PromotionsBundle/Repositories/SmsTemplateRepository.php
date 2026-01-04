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
