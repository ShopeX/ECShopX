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
use PromotionsBundle\Entities\BargainLog;

use Doctrine\Common\Collections\Criteria;

class BargainLogRepository extends EntityRepository
{
    public function create($params)
    {
        $bargainLogEntity = new BargainLog();
        $bargainLog = $this->setBargainLogData($bargainLogEntity, $params);
        $em = $this->getEntityManager();
        $em->persist($bargainLog);
        $em->flush();
        $result = $this->getBargainLogData($bargainLog);

        return $result;
    }

    public function get($filter)
    {
        $log = $this->findOneBy($filter);
        $result = [];
        if ($log) {
            $result = $this->getBargainLogData($log);
        }
        return $result;
    }

    public function getList($filter, $offset = 0, $limit = -1, $orderBy = ['created' => 'desc'])
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

        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy);
            if ($limit > 0) {
                $criteria = $criteria->setFirstResult($offset)
                ->setMaxResults($limit);
            }
            $list = $this->matching($criteria);
            $bargainLogList = [];
            if ($list) {
                foreach ($list as $bargainLog) {
                    $bargain = $this->getBargainLogData($bargainLog);
                    $bargainLogList[] = $bargain;
                }
            }
            $res['list'] = $bargainLogList;
        }

        return $res;
    }

    private function setBargainLogData($bargainLogEntity, $data)
    {
        if (isset($data['company_id']) && $data['company_id']) {
            $bargainLogEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['authorizer_appid']) && $data['authorizer_appid']) {
            $bargainLogEntity->setAuthorizerAppid($data['authorizer_appid']);
        }
        if (isset($data['wxa_appid']) && $data['wxa_appid']) {
            $bargainLogEntity->setWxaAppid($data['wxa_appid']);
        }
        if (isset($data['bargain_id']) && $data['bargain_id']) {
            $bargainLogEntity->setBargainId($data['bargain_id']);
        }
        if (isset($data['user_id']) && $data['user_id']) {
            $bargainLogEntity->setUserId($data['user_id']);
        }
        if (isset($data['open_id']) && $data['open_id']) {
            $bargainLogEntity->setOpenId($data['open_id']);
        }
        if (isset($data['nickname']) && $data['nickname']) {
            $bargainLogEntity->setNickname($data['nickname']);
        }
        if (isset($data['headimgurl']) && $data['headimgurl']) {
            $bargainLogEntity->setHeadimgurl($data['headimgurl']);
        }
        if (isset($data['cutdown_num']) && is_numeric($data['cutdown_num'])) {
            $bargainLogEntity->setCutdownNum($data['cutdown_num']);
        }

        return $bargainLogEntity;
    }

    public function getBargainLogData($bargainLogEntity)
    {
        return [
            'bargain_log_id' => $bargainLogEntity->getBargainLogId(),
            'company_id' => $bargainLogEntity->getCompanyId(),
            'authorizer_appid' => $bargainLogEntity->getAuthorizerAppid(),
            'wxa_appid' => $bargainLogEntity->getWxaAppid(),
            'bargain_id' => $bargainLogEntity->getBargainId(),
            'user_id' => $bargainLogEntity->getUserId(),
            'open_id' => $bargainLogEntity->getOpenId(),
            'nickname' => $bargainLogEntity->getNickname(),
            'headimgurl' => $bargainLogEntity->getHeadimgurl(),
            'cutdown_num' => $bargainLogEntity->getCutdownNum(),
            'created' => $bargainLogEntity->getCreated(),
            'updated' => $bargainLogEntity->getUpdated(),
        ];
    }
}
