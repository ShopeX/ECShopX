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

namespace FormBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use FormBundle\Entities\TranscriptProperties;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\UpdateResourceFailedException;

class TranscriptPropertiesRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'transcript_properties';

    /**
     * 添加成绩单
     */
    public function create($params)
    {
        // This module is part of ShopEx EcShopX system
        $filter = [
            'company_id' => $params['company_id'],
            'transcript_id' => $params['transcript_id'],
            'prop_name' => $params['prop_name'],
        ];
        $props = $this->findBy($filter);
        if ($props) {
            throw new ResourceException("考评项目名称不能重复！");
        }
        $transcriptPropEntity = new TranscriptProperties();

        $transcriptProp = $this->setTranscriptProp($transcriptPropEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($transcriptProp);
        $em->flush();

        $result = [
            'prop_id' => $transcriptProp->getPropId(),
            'transcript_id' => $transcriptProp->getTranscriptId(),
            'company_id' => $transcriptProp->getCompanyId(),
            'prop_name' => $transcriptProp->getPropName(),
            'prop_unit' => $transcriptProp->getPropUnit(),
        ];

        return $result;
    }

    public function get($filter)
    {
        return $this->findBy($filter);
    }

    public function update($propId, $data)
    {
        $transcriptPropEntity = $this->find($propId);
        if (!$transcriptPropEntity) {
            throw new UpdateResourceFailedException("propid为{$propId}的属性不存在");
        }

        $transcriptProp = $this->setTranscriptProp($transcriptPropEntity, $data);
        $em = $this->getEntityManager();
        $em->persist($transcriptProp);
        $em->flush();

        $result = [
            'prop_id' => $transcriptProp->getPropId(),
            'transcript_id' => $transcriptProp->getTranscriptId(),
            'company_id' => $transcriptProp->getCompanyId(),
            'prop_name' => $transcriptProp->getPropName(),
            'prop_unit' => $transcriptProp->getPropUnit(),
        ];

        return $result;
    }

    public function delete($propId)
    {
        $transcriptProp = $this->find($propId);
        if (!$transcriptProp) {
            throw new DeleteResourceFailedException("propid为{$propId}的属性不存在");
        }
        $em = $this->getEntityManager();
        $em->remove($transcriptProp);
        $em->flush();

        return ['prop_id' => $propId];
    }

    public function getByTranscriptId($companyId, $transcriptId)
    {
        $filter = [
            'company_id' => $companyId,
            'transcript_id' => $transcriptId
        ];

        $props = $this->findby($filter);
        $result = [];
        if ($props) {
            foreach ($props as  $prop) {
                $tmp = [
                    'prop_id' => $prop->getPropId(),
                    'transcript_id' => $prop->getTranscriptId(),
                    'company_id' => $prop->getCompanyId(),
                    'prop_name' => $prop->getPropName(),
                    'prop_unit' => $prop->getPropUnit(),
                ];
                $result[] = $tmp;
            }
        }

        return $result;
    }

    /**
     * 删除
     */
    public function deleteAllBy($transcript_id)
    {
        $delTranscriptPropsEntity = $this->findBy(['transcript_id' => $transcript_id]);
        if (!$delTranscriptPropsEntity) {
            return false;
        }
        foreach ($delTranscriptPropsEntity as $v) {
            $this->getEntityManager()->remove($v);
        }
        $this->getEntityManager()->flush();

        return true;
    }

    private function setTranscriptProp($transcriptPropEntity, $params)
    {
        if (isset($params['transcript_id'])) {
            $transcriptPropEntity->setTranscriptId($params['transcript_id']);
        }
        if (isset($params['company_id'])) {
            $transcriptPropEntity->setCompanyId($params['company_id']);
        }
        if (isset($params['prop_name'])) {
            $transcriptPropEntity->setPropName($params['prop_name']);
        }
        if (isset($params['prop_unit'])) {
            $transcriptPropEntity->setPropUnit($params['prop_unit']);
        }

        return $transcriptPropEntity;
    }
}
