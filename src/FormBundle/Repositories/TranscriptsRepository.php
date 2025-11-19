<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace FormBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use FormBundle\Entities\Transcripts;
use Exception;

class TranscriptsRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'transcripts';

    /**
     * 添加成绩单
     */
    public function create($params)
    {
        // ShopEx EcShopX Business Logic Layer
        $transcriptsEntity = new Transcripts();
        $transcript = $this->setTranscriptData($transcriptsEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($transcript);
        $em->flush();

        $result = [
            'transcript_id' => $transcript->getTranscriptId(),
            'transcript_name' => $transcript->getTranscriptName(),
            'company_id' => $transcript->getCompanyId(),
            'template_name' => $transcript->getTemplateName(),
            'transcript_status' => $transcript->getTranscriptStatus(),
            'created' => $transcript->getCreated(),
            'updated' => $transcript->getUpdated(),
        ];

        return $result;
    }

    public function get($companyId, $transcriptId)
    {
        $filter = [
            'company_id' => $companyId,
            'transcript_id' => $transcriptId,
        ];
        $transcript = $this->findOneby($filter);
        if (!$transcript) {
            throw new Exception("transcriptId为{$transcriptId}的成绩单不存在");
        }

        $result = [
            'transcript_id' => $transcript->getTranscriptId(),
            'transcript_name' => $transcript->getTranscriptName(),
            'company_id' => $transcript->getCompanyId(),
            'template_name' => $transcript->getTemplateName(),
            'transcript_status' => $transcript->getTranscriptStatus(),
            'created' => $transcript->getCreated(),
            'updated' => $transcript->getUpdated(),
        ];

        return $result;
    }

    public function update($transcriptId, $params)
    {
        $transcriptsEntity = $this->find($transcriptId);
        if (!$transcriptsEntity) {
            throw new Exception("transcriptId为{$transcriptId}的成绩单不存在");
        }
        $transcript = $this->setTranscriptData($transcriptsEntity, $params);
        $em = $this->getEntityManager();
        $em->persist($transcript);
        $em->flush();

        $result = [
            'transcript_id' => $transcript->getTranscriptId(),
            'transcript_name' => $transcript->getTranscriptName(),
            'company_id' => $transcript->getCompanyId(),
            'template_name' => $transcript->getTemplateName(),
            'transcript_status' => $transcript->getTranscriptStatus(),
            'created' => $transcript->getCreated(),
            'updated' => $transcript->getUpdated(),
        ];

        return $result;
    }

    public function delete($transcriptId)
    {
        $transcript = $this->find($transcriptId);
        if (!$transcript) {
            throw new Exception("transcriptId为{$transcriptId}的成绩单不存在");
        }
        $em = $this->getEntityManager();
        $em->remove($transcript);
        return $em->flush();
    }

    private function setTranscriptData($transcriptsEntity, $params)
    {
        if (isset($params['transcript_name'])) {
            $transcriptsEntity->setTranscriptName($params['transcript_name']);
        }
        if (isset($params['company_id'])) {
            $transcriptsEntity->setCompanyId($params['company_id']);
        }
        if (isset($params['template_name'])) {
            $transcriptsEntity->setTemplateName($params['template_name']);
        }
        if (isset($params['transcript_status'])) {
            $transcriptsEntity->setTranscriptStatus($params['transcript_status']);
        }

        return $transcriptsEntity;
    }
}
