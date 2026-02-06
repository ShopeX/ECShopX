<?php

namespace GoodsBundle\Http\Api\V1\Action;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsGroupRelItemService;
use GoodsBundle\Services\ItemsGroupService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

class ItemsGroupController extends Controller
{
    /**
     *  保存分组商品，这里只有模板装修挂件会调用
     *  path="/goods/save_group_item",
     */
    public function saveGroupItem(Request $request)
    {
        $params = $request->all('rel_goods_ids', 'group_id', 'regionauth_id', 'pages_template_id');
        $rules = [
            'rel_goods_ids' => ['required','商品ID不能为空'],
            // 'group_id' => ['required','分组ID不能为空'],
            'regionauth_id' => ['required','区域ID不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $itemsGroupService = new ItemsGroupService();
        if (empty($params['group_id'])) {
            $group_key = 'widget-' . ($params['pages_template_id'] ?? date('Ymd-Hi'));
            $group_data = [
                'company_id' => $params['company_id'],
                'regionauth_id' => $params['regionauth_id'],
                'group_key' => $group_key,
                'remark' => '',
            ];
            $itemsGroup = $itemsGroupService->repository->create($group_data);
            if (!$itemsGroup) {
                throw new ResourceException('分组创建失败，请稍后重试');
            }
            $params['group_id'] = $itemsGroup['id'];
        } else {
            $itemsGroup = $itemsGroupService->repository->getInfoById($params['group_id']);
            if (!$itemsGroup) {
                throw new ResourceException('group_id错误');
            }
        }

        $itemsGroupRelItemService = new ItemsGroupRelItemService();
        $itemsGroupRelItemService->createRelItem($params['group_id'], 'widget', $params);
        return $this->response->array(['items_group' => $itemsGroup]);
    }

    /**
     *  path="/goods/get_group_items",
     *  summary="获取分组商品",
     */
    public function getGroupItems(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'group_id');
        $params['page'] = $params['page'] ?? 1;
        $params['pageSize'] = $params['pageSize'] ?? 100;
        $filter = [
            'group_id' => $params['group_id'],
        ];
        $orderBy = [
            'id' => 'DESC'
        ];
        $itemsGroupRelItemService = new ItemsGroupRelItemService();
        $rs = $itemsGroupRelItemService->repository->getLists($filter, 'goods_id', $params['page'], $params['pageSize'], $orderBy);
        $result = array_column($rs, 'goods_id');
        return $this->response->array(['goods_id' => $result]);
    }

}

