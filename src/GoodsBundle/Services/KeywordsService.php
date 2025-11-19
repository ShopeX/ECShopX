<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
