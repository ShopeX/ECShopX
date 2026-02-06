<?php

namespace GoodsBundle\Services;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Entities\ItemsGroup;

class ItemsGroupService
{
    /** @var \GoodsBundle\Repositories\ItemsGroupRepository */
    public $repository;

    /**
     * ItemsTagsService 构造函数.
     */
    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(ItemsGroup::class);
    }
    
    //自动创建商品分组
    public function getGroupId($group_type = '', $dataInfo = [])
    {
        if ($group_type == 'coupon') {
            $group_key = $group_type . '-' . $dataInfo['card_id'];
        } elseif ($group_type == 'marketing') {
            $group_key = $group_type . '-' . $dataInfo['marketing_id'];
        }
        //查询分组是否存在
        $group_data = [
            'company_id' => $dataInfo['company_id'],
            'regionauth_id' => $dataInfo['regionauth_id'],
            'group_key' => $group_key,
        ];
        $itemsGroupService = new ItemsGroupService();
        $itemsGroup = $itemsGroupService->repository->getInfo($group_data);
        if ($itemsGroup) {
            return $itemsGroup['id'];
        }
        //新增商品分组
        $group_data['remark'] = $dataInfo['remark'] ?? date('Y-m-d H:i:s');
        $itemsGroup = $itemsGroupService->repository->create($group_data);
        if (!$itemsGroup) {
            throw new ResourceException('商品分组创建失败，请稍后重试');
        }
        return $itemsGroup['id'];
    }

    public function delGroupData($group_key)
    {
        $itemsGroupService = new ItemsGroupService();
        $itemsGroup = $itemsGroupService->repository->getLists(['group_key' => $group_key]);
        if ($itemsGroup) {
            foreach ($itemsGroup as $v) {
                $group_id = $v['id'];
                $itemsGroupRelItemService = new ItemsGroupRelItemService();
                if ($itemsGroupRelItemService->repository->getInfo(['group_id' => $group_id])) {
                    $itemsGroupRelItemService->repository->deleteBy(['group_id' => $group_id]);
                }
                $itemsGroupService->repository->deleteById($group_id);
            }
        }
    }
}

