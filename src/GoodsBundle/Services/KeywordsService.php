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

namespace GoodsBundle\Services;

use GoodsBundle\Entities\Keywords;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Entities\ItemsRelTags;

class KeywordsService
{
    private $entityRepository;
    private $itemsRelTags;

    /**
     *  构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Keywords::class);
        $this->itemsRelTags = app('registry')->getManager('default')->getRepository(ItemsRelTags::class);
    }

    public function deleteById($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $lists = $this->itemsRelTags->lists($filter);
            if (isset($lists['list']) && $lists['list']) {
                $result = $this->itemsRelTags->deleteBy($filter);
            }
            $result = $this->entityRepository->deleteBy($filter);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    public function addKeywords($data)
    {
        if (isset($data['id'])) {
            $row = $this->entityRepository->getInfo(['id' => $data['id']]);
            if (!$row) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.record_not_exists'));
            }
            return $this->updateOneBy(['id' => $data['id']], $data);
        }
        return $this->create($data);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    public function getByShop($filter)
    {
        $result = $this->lists($filter);
        if (!$result['total_count']) {
            $filter['distributor_id'] = 0; //取默认店铺值
            $result = $this->lists($filter);
        }
        return $result;
    }
}
