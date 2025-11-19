<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace FormBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use FormBundle\Entities\UserTranscripts;
use Doctrine\Common\Collections\Criteria;

class UserTranscriptsRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'user_transcripts';

    /**
     * 添加用户成绩单
     */
    public function create($params)
    {
        $userTranscriptEntity = new UserTranscripts();
        $userTranscript = $this->setUserTranscriptData($userTranscriptEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($userTranscript);
        $em->flush();
        $result = [
            'record_id' => $userTranscript->getRecordId(),
            'user_id' => $userTranscript->getUserId(),
            'company_id' => $userTranscript->getCompanyId(),
            'shop_id' => $userTranscript->getShopId(),
            'transcript_id' => $userTranscript->getTranscriptId(),
            'transcript_name' => $userTranscript->getTranscriptName(),
            'indicator_details' => $userTranscript->getIndicatorDetails(),
        ];

        return $result;
    }

    public function get($record_id)
    {
        return $this->find($record_id);
    }

    public function list($filter, $orderBy = ['created' => 'DESC'], $offset = 0, $limit = 1000)
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                    continue;
                } elseif (is_array($value)) {
                    $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
                } else {
                    $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
                }
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $transcripts = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($offset)
                ->setMaxResults($limit);
            $list = $this->matching($criteria);
            foreach ($list as $v) {
                $tmp = [
                    'record_id' => $v->getRecordId(),
                    'user_id' => $v->getUserId(),
                    'company_id' => $v->getCompanyId(),
                    'shop_id' => $v->getShopId(),
                    'transcript_id' => $v->getTranscriptId(),
                    'transcript_name' => $v->getTranscriptName(),
                    'indicator_details' => $v->getIndicatorDetails(),
                    'created' => $v->getCreated(),
                    'updated' => $v->getUpdated(),
                ];
                $transcripts[] = $tmp;
            }
        }
        $res['list'] = $transcripts;
        return $res;
    }

    private function setUserTranscriptData($userTranscriptEntity, $postdata)
    {
        if (isset($postdata['user_id'])) {
            $userTranscriptEntity->setUserId($postdata['user_id']);
        }
        if (isset($postdata['company_id'])) {
            $userTranscriptEntity->setCompanyId($postdata['company_id']);
        }
        if (isset($postdata['shop_id'])) {
            $userTranscriptEntity->setShopId($postdata['shop_id']);
        }
        if (isset($postdata['transcript_id'])) {
            $userTranscriptEntity->setTranscriptId($postdata['transcript_id']);
        }
        if (isset($postdata['transcript_name'])) {
            $userTranscriptEntity->setTranscriptName($postdata['transcript_name']);
        }
        if (isset($postdata['indicator_details'])) {
            $userTranscriptEntity->setIndicatorDetails($postdata['indicator_details']);
        }

        return $userTranscriptEntity;
    }
}
