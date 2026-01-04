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

namespace GoodsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use GoodsBundle\Entities\ItemsRelType;
use Dingo\Api\Exception\DeleteResourceFailedException;

class ItemsRelTypeRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'items_rel_type';

    /**
     * 添加
     */
    public function create($params)
    {
        $itemsEnt = new ItemsRelType();

        $itemsEnt->setItemId($params['item_id']);
        $itemsEnt->setLabelId($params['label_id']);
        $itemsEnt->setLabelName($params['label_name']);
        $itemsEnt->setLabelPrice($params['label_price']);
        $itemsEnt->setNumType($params['num_type']);
        $itemsEnt->setNum($params['num']);

        $itemsEnt->setLimitTime($params['limit_time']);

        $itemsEnt->setCompanyId($params['company_id']);
        if (isset($params['is_not_limit_num'])) {
            $itemsEnt->setIsNotLimitNum($params['is_not_limit_num']);
        }

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();
        $result = [
            'item_id' => $itemsEnt->getItemId(),
            'label_id' => $itemsEnt->getLabelId(),
            'label_name' => $itemsEnt->getLabelName(),
            'label_price' => $itemsEnt->getLabelPrice(),
            'num_type' => $itemsEnt->getNumType(),
            'num' => $itemsEnt->getNum(),
            'limit_time' => $itemsEnt->getLimitTime(),
            'company_id' => $itemsEnt->getCompanyId(),
            'created' => $itemsEnt->getCreated(),
            'updated' => $itemsEnt->getUpdated(),
            'is_not_limit_num' => $itemsEnt->getIsNotLimitNum(),
        ];

        return $result;
    }

    /**
     * 更新商品信息
     */
    public function update($params)
    {
        $itemsEnt = $this->findOneBy(['item_id' => $params['item_id'], 'label_id' => $params['label_id']]);
        if (!$itemsEnt) {
            $itemsEnt = new ItemsRelType();
        }
        $itemsEnt->setItemId($params['item_id']);
        $itemsEnt->setLabelId($params['label_id']);
        $itemsEnt->setLabelName($params['label_name']);
        $itemsEnt->setLabelPrice($params['label_price']);
        $itemsEnt->setNumType($params['num_type']);
        $itemsEnt->setNum($params['num']);

        $itemsEnt->setLimitTime($params['limit_time']);


        $itemsEnt->setCompanyId($params['company_id']);
        if (isset($params['is_not_limit_num'])) {
            $itemsEnt->setIsNotLimitNum($params['is_not_limit_num']);
        }

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();
        $result = [
            'item_id' => $itemsEnt->getItemId(),
            'label_id' => $itemsEnt->getLabelId(),
            'label_name' => $itemsEnt->getLabelName(),
            'label_price' => $itemsEnt->getLabelPrice(),
            'num_type' => $itemsEnt->getNumType(),
            'num' => $itemsEnt->getNum(),
            'limit_time' => $itemsEnt->getLimitTime(),
            'company_id' => $itemsEnt->getCompanyId(),
            'created' => $itemsEnt->getCreated(),
            'updated' => $itemsEnt->getUpdated(),
            'is_not_limit_num' => $itemsEnt->getIsNotLimitNum(),
        ];

        return $result;
    }

    /**
     * 删除
     */
    public function deleteAllBy($item_id)
    {
        $delItemsEntity = $this->findBy(['item_id' => $item_id]);
        if (!$delItemsEntity) {
            return false;
        }
        foreach ($delItemsEntity as $v) {
            $this->getEntityManager()->remove($v);
        }

        return $this->getEntityManager()->flush($delItemsEntity);
    }

    /**
     * 删除
     */
    public function deleteOneBy($params)
    {
        $delItemsEntity = $this->findOneBy(['item_id' => $params['item_id'], 'label_id' => $params['label_id']]);
        if (!$delItemsEntity) {
            throw new DeleteResourceFailedException("删除商品关联数值属性错误");
        }
        $this->getEntityManager()->remove($delItemsEntity);

        return $this->getEntityManager()->flush($delItemsEntity);
    }

    /**
     * 获取商品关联数值属性和类型
     */
    public function list($item_id)
    {
        $filter = [
            'item_id' => $item_id,
        ];
        $itemsList = $this->findBy($filter, ['created' => 'DESC']);

        $newItemsList = [];
        foreach ($itemsList as $v) {
            $newItemsList[] = normalize($v);
        }

        return $newItemsList;
    }
}
