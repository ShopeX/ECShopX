<?php

namespace GoodsBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use GoodsBundle\Services\ItemsGroupRelItemService;
use Illuminate\Http\Request;

class ItemsGroupController extends Controller
{
    /**
     * 获取商品分组下的商品
     * path="/wxapp/goods/get_group_items"
     */
    public function getGroupItems(Request $request)
    {
        $result = ['items' => []];
        // $authInfo = $request->get('auth');
        //
        // $filter['company_id'] = $authInfo['company_id'];
        // $filter['regionauth_id'] = $request->get('regionauth_id', 0);
        // $filter['group_id'] = intval($request->get('group_id', 0));
        // if (!$filter['group_id']) {
        //     return $this->response->array($result);
        // }
        //
        // $page = intval($request->input('page', 1));
        // $pageSize = min(intval($request->input('pageSize', 10)), 20);
        //
        // $params = $request->all('page', 'pageSize', 'group_id');
        // $filter = [
        //     'group_id' => $params['group_id'],
        // ];
        // $itemsGroupRelItemService = new ItemsGroupRelItemService();
        // $rs = $itemsGroupRelItemService->getWxappGroupItems($filter, $page, $pageSize);
        // if ($rs) {
        //     $goods_id = array_column($rs, 'goods_id');
        //     //处理商品促销标签等
        //     foreach ($rs as $v) {
        //         $result['items'][] = $v;
        //     }
        // }
        return $this->response->array($result);
    }
    
}


