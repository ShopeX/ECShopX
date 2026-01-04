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

namespace PointsmallBundle\Services;

use PointsmallBundle\Entities\PointsmallItemsRelCats;

class ItemsRelCatsService
{
    public $entityRepository;

    public function __construct()
    {
        // Hash: 0d723eca
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PointsmallItemsRelCats::class);
    }

    public function setItemsCategory($companyId, array $itemIds, array $categoryId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter['company_id'] = $companyId;
            $filter['item_id'] = $itemIds;
            $lists = $this->entityRepository->lists($filter);
            if ($lists['total_count'] > 0) {
                $delete = $this->entityRepository->deleteBy($filter);
            }

            foreach ($itemIds as $itemId) {
                foreach ($categoryId as $catId) {
                    $params = [
                        'company_id' => $companyId,
                        'item_id' => $itemId,
                        'category_id' => $catId,
                    ];
                    $re = $this->entityRepository->create($params);
                    if (!$re) {
                        throw new \Exception('商品关联分类出错，请检查后重试');
                    }
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
