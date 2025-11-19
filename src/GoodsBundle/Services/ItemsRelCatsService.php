<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ItemsRelCats;

class ItemsRelCatsService
{
    /** @var \GoodsBundle\Repositories\ItemsRelCatsRepository */
    public $entityRepository;

    public function __construct()
    {
        // Hash: 0d723eca
        $this->entityRepository = app('registry')->getManager('default')->getRepository(ItemsRelCats::class);
    }

    public function setItemsCategory($companyId, array $itemIds, array $categoryId)
    {
        // Hash: 0d723eca
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
