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
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangOutsideItemService;
use PromotionsBundle\Entities\BargainPromotions;

use Doctrine\Common\Collections\Criteria;

use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;


class BargainPromotionsRepository extends EntityRepository
{
    use MagicLangTrait;
    public $table = "promotions_bargain";
    public $prk = 'bargain_id';
    private $multiLangField = [
        'title',
        'ad_pic'
    ];

    public function getLangService()
    {
        return new MultiLangOutsideItemService($this->table,$this->table,$this->multiLangField);
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
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

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);

        return intval($total);
    }

    public function create($params)
    {
        // HACK: temporary solution
        $bargainPromotionsEntity = new BargainPromotions();
        $bargainPromotion = $this->setBargainData($bargainPromotionsEntity, $params);
        $em = $this->getEntityManager();
        $em->persist($bargainPromotion);
        $em->flush();
        $result = $this->getBargainData($bargainPromotion);

        $this->getLangService()->addMultiLangByParams($result[$this->prk],$params,$this->table);

        return $result;
    }

    public function update($bargain_id, $data)
    {
        $bargainPromotionsEntity = $this->find($bargain_id);
        if (!$bargainPromotionsEntity) {
            throw new UpdateResourceFailedException(trans("PromotionsBundle.bargain_activity_not_exist_with_id", ["bargain_id" => $bargain_id]));
        }
        $bargainPromotion = $this->setBargainData($bargainPromotionsEntity, $data);
        $em = $this->getEntityManager();
        $em->persist($bargainPromotion);
        $em->flush();
        $result = $this->getBargainData($bargainPromotion);


        $this->getLangService()->updateLangData($data,$this->table,$bargain_id);

        return $result;
    }

    public function get($bargainId)
    {
        $bargainPromotionsEntity = $this->find($bargainId);
        $result = [];
        if ($bargainPromotionsEntity) {
            $result = $this->getBargainData($bargainPromotionsEntity);
            $this->getLangService()->getOneLangData($result,$this->multiLangField,$this->table,$this->getLang(),$result[$this->prk],$this->table);
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
        $res['list'] = [];

        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy);
            if ($limit > 0) {
                $criteria = $criteria->setFirstResult($offset)
                ->setMaxResults($limit);
            }
            $list = $this->matching($criteria);
            $bargainList = [];
            if ($list) {
                foreach ($list as $bargain) {
                    $bargain = $this->getBargainData($bargain);
                    $bargainList[] = $bargain;
                }
            }
            $res['list'] = $bargainList;

            $res["list"] = $this->getLangService()->getListAddLang($res["list"],$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        }

        return $res;
    }

    /**
     * 删除助力活动
     */
    public function delete($bargain_id)
    {
        $delBargainEntity = $this->find($bargain_id);
        if (!$delBargainEntity) {
            throw new DeleteResourceFailedException(trans("PromotionsBundle.bargain_activity_delete_not_exist_with_id", ["bargain_id" => $bargain_id]));
        }
        $this->getEntityManager()->remove($delBargainEntity);

        return $this->getEntityManager()->flush($delBargainEntity);
    }

    private function setBargainData($bargainPromotionsEntity, $data)
    {
        if (isset($data['company_id']) && $data['company_id']) {
            $bargainPromotionsEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['title']) && $data['title']) {
            $bargainPromotionsEntity->setTitle($data['title']);
        }
        if (isset($data['ad_pic']) && $data['ad_pic']) {
            $bargainPromotionsEntity->setAdPic($data['ad_pic']);
        }
        if (isset($data['item_name']) && $data['item_name']) {
            $bargainPromotionsEntity->setItemName($data['item_name']);
        }
        if (isset($data['item_pics']) && $data['item_pics']) {
            $bargainPromotionsEntity->setItemPics($data['item_pics']);
        }
        if (isset($data['item_intro']) && $data['item_intro']) {
            $bargainPromotionsEntity->setItemIntro($data['item_intro']);
        }
        if (isset($data['mkt_price']) && $data['mkt_price']) {
            $bargainPromotionsEntity->setMktPrice($data['mkt_price']);
        }
        if (isset($data['price']) && $data['price']) {
            $bargainPromotionsEntity->setPrice($data['price']);
        }
        if (isset($data['limit_num']) && $data['limit_num']) {
            $bargainPromotionsEntity->setLimitNum($data['limit_num']);
        }
        if (isset($data['order_num']) && $data['order_num']) {
            $bargainPromotionsEntity->setOrderNum($data['order_num']);
        }
        if (isset($data['bargain_rules']) && $data['bargain_rules']) {
            $bargainPromotionsEntity->setBargainRules($data['bargain_rules']);
        }
        if (isset($data['bargain_range']) && $data['bargain_range']) {
            $bargainPromotionsEntity->setBargainRange($data['bargain_range']);
        }
        if (isset($data['people_range']) && $data['people_range']) {
            $bargainPromotionsEntity->setPeopleRange($data['people_range']);
        }
        if (isset($data['min_price']) && $data['min_price']) {
            $bargainPromotionsEntity->setMinPrice($data['min_price']);
        }
        if (isset($data['begin_time']) && $data['begin_time']) {
            $bargainPromotionsEntity->setBeginTime($data['begin_time']);
        }
        if (isset($data['end_time']) && $data['end_time']) {
            $bargainPromotionsEntity->setEndTime($data['end_time']);
        }
        if (isset($data['share_msg']) && $data['share_msg']) {
            $bargainPromotionsEntity->setShareMsg($data['share_msg']);
        }
        if (isset($data['help_pics']) && $data['help_pics']) {
            $bargainPromotionsEntity->setHelpPics($data['help_pics']);
        }
        if (isset($data['item_id']) && $data['item_id']) {
            $bargainPromotionsEntity->setItemId($data['item_id']);
        }

        return $bargainPromotionsEntity;
    }

    public function getBargainData($bargainPromotionsEntity)
    {
        $result = [
            'bargain_id' => $bargainPromotionsEntity->getBargainId(),
            'company_id' => $bargainPromotionsEntity->getCompanyId(),
            'title' => $bargainPromotionsEntity->getTitle(),
            'ad_pic' => $bargainPromotionsEntity->getAdPic(),
            'item_name' => $bargainPromotionsEntity->getItemName(),
            'item_pics' => $bargainPromotionsEntity->getItemPics(),
            'item_intro' => $bargainPromotionsEntity->getItemIntro(),
            'mkt_price' => $bargainPromotionsEntity->getMktPrice(),
            'price' => $bargainPromotionsEntity->getPrice(),
            'limit_num' => $bargainPromotionsEntity->getLimitNum(),
            'order_num' => $bargainPromotionsEntity->getOrderNum(),
            'bargain_rules' => $bargainPromotionsEntity->getBargainRules(),
            'bargain_range' => $bargainPromotionsEntity->getBargainRange(),
            'people_range' => $bargainPromotionsEntity->getPeopleRange(),
            'min_price' => $bargainPromotionsEntity->getMinPrice(),
            'begin_time' => $bargainPromotionsEntity->getBeginTime(),
            'end_time' => $bargainPromotionsEntity->getEndTime(),
            'share_msg' => $bargainPromotionsEntity->getShareMsg(),
            'help_pics' => $bargainPromotionsEntity->getHelpPics(),
            'created' => $bargainPromotionsEntity->getCreated(),
            'updated' => $bargainPromotionsEntity->getUpdated(),
            'is_expired' => false,
            'item_id' => $bargainPromotionsEntity->getItemId(),
        ];
        if ($result['end_time'] < time()) {
            $result['is_expired'] = true;
        }

        return $result;
    }

    /**
     * 获取时间段内是否有助力活动
     *
     * @param $filter 更新的条件
     */
    public function getIsHave($itemId, $begin_time, $end_time, $bargainId = '')
    {
        $criteria = Criteria::create();
        $itemId = (array)$itemId;
        $criteria = $criteria->andWhere(Criteria::expr()->in('item_id', $itemId));
        if ($bargainId) {
            $criteria = $criteria->andWhere(Criteria::expr()->neq('bargain_id', $bargainId));
        }
        $criteria = $criteria->andWhere(Criteria::expr()->orX(
            Criteria::expr()->andX(
                Criteria::expr()->lte('begin_time', $begin_time),
                Criteria::expr()->gte('end_time', $begin_time)
            ),
            Criteria::expr()->andX(
                Criteria::expr()->lte('begin_time', $end_time),
                Criteria::expr()->gte('end_time', $end_time)
            )
        ));

        $entityList = $this->matching($criteria);
        $lists = [];
        foreach ($entityList as $entity) {
            $lists[] = $this->getBargainData($entity);
        }
        $lists = $this->getLangService()->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);

        return $lists;
    }
}
