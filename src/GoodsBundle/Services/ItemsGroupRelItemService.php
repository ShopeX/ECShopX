<?php

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ItemsGroupRelItem;

class ItemsGroupRelItemService
{
    /** @var \GoodsBundle\Repositories\ItemsGroupRelItemRepository */
    public $repository;

    /**
     * ItemsTagsService 构造函数.
     */
    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(ItemsGroupRelItem::class);
    }

    /**
     * 获取商品分组下的商品 goods_id
     */
    public function getGroupItemsById($group_id, $data_type = 'string')
    {
        $rsRelItems = $this->repository->getLists(['group_id' => $group_id], 'goods_id');
        if (!$rsRelItems) {
            return false;
        }
        if ($data_type == 'string') {
            return implode(',', array_column($rsRelItems, 'goods_id'));
        }
        return array_column($rsRelItems, 'goods_id');
    }

    /**
     * 获取商品分组下的商品 goods_id
     */
    public function getGroupItems($group_key, $data_type = 'string')
    {
        $itemsGroupService = new ItemsGroupService();
        $itemsGroup = $itemsGroupService->repository->getInfo(['group_key' => $group_key]);
        if (!$itemsGroup) {
            return '';
        }
        
        $rsRelItems = $this->repository->getLists(['group_id' => $itemsGroup['id']], 'goods_id');
        if ($data_type == 'string') {
            return implode(',', array_column($rsRelItems, 'goods_id'));
        }
        return array_column($rsRelItems, 'goods_id');
    }
    
    /**
     * 写入分组商品数据
     */
    public function createRelItem($group_id, $group_type, $dataInfo)
    {
        if (is_string($dataInfo['rel_goods_ids'])) {
            $dataInfo['rel_goods_ids'] = explode(',', $dataInfo['rel_goods_ids']);
        }
        $dataInfo['rel_goods_ids'] = array_filter(array_unique($dataInfo['rel_goods_ids']));
        if (!$dataInfo['rel_goods_ids']) {
            return false;
        }

        $delGoodsIds = [];//需要删除的
        $newGoodsIds = $dataInfo['rel_goods_ids'];//需要新写入的
        $filter = [
            'group_id' => $group_id,
        ];
        $rs = $this->repository->getLists($filter, 'goods_id');
        if ($rs) {
            $oldGoodsIds = array_column($rs, 'goods_id');
            $delGoodsIds = array_diff($oldGoodsIds, $dataInfo['rel_goods_ids']);
            $newGoodsIds = array_diff($dataInfo['rel_goods_ids'], $oldGoodsIds);
            $rs = null;
            $oldGoodsIds = null;
        }
        if ($delGoodsIds) {
            $filter = [
                'group_id' => $group_id,
                'goods_id' => $delGoodsIds,
            ];
            $this->repository->deleteBy($filter);
            $delGoodsIds = null;
        }

        $batchSize = 500;
        $batchData = [];
        $count = 0;
        foreach ($newGoodsIds as $goods_id) {
            $batchData[] = [
                'company_id' => $dataInfo['company_id'],
                'regionauth_id' => $dataInfo['regionauth_id'],
                'group_id' => $group_id,
                'group_type' => $group_type,
                'goods_id' => $goods_id,
                'created' => time(),
                'updated' => time(),
            ];
            $count++;
            // 每500条数据执行一次插入
            if ($count % $batchSize === 0) {
                $this->repository->createQuick($batchData);
                $batchData = []; // 清空批次数据
            }
        }

        // 插入剩余不足500条的数据
        if (!empty($batchData)) {
            $this->repository->createQuick($batchData);
        }
        
        $newGoodsIds = null;
        $batchData = null;
        return true;
    }
}

