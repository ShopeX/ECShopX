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

namespace CompanysBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\OperatorCartService;
use CompanysBundle\Services\OperatorPendingOrderService;
use MembersBundle\Services\MemberService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemRelAttributesService;
use GoodsBundle\Services\ItemsAttributesService;

class OperatorPendingOrderController extends BaseController
{
    public function listPendingData(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $filter['company_id'] = $authInfo['company_id'];
        $filter['operator_id'] = $authInfo['operator_id'];
        $filter['distributor_id'] = $request->get('distributor_id', 0);

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $operatorPendingOrderService = new OperatorPendingOrderService();
        $result = $operatorPendingOrderService->lists($filter, '*', $page, $pageSize, ['created' => 'DESC']);

        $userIds = array_column($result['list'], 'user_id');
        $userIds = array_filter($userIds);
        if ($userIds) {
            $memberService = new MemberService();
            $memberList = $memberService->getMemberList(['user_id' => $userIds, 'company_id' => $authInfo['company_id']], 1, $pageSize);
            $memberList = array_column($memberList, null, 'user_id');
        }

        $itemIds = [];
        foreach ($result['list'] as $key => $val) {
            $result['list'][$key]['pending_data'] = json_decode($val['pending_data'], true);
            if ($val['user_id'] > 0 && isset($memberList[$val['user_id']])) {
                $result['list'][$key]['memberInfo'] = $memberList[$val['user_id']];
            }

            $itemIds = array_merge($itemIds, array_column($result['list'][$key]['pending_data'], 'item_id'));
        }
        if ($itemIds) {
            $itemsService = new ItemsService();
            $itemList = $itemsService->getItems($itemIds, $authInfo['company_id'], ['item_id', 'item_name', 'item_bn', 'price', 'pics', 'is_medicine', 'is_prescription']);
            $itemList = array_column($itemList, null, 'item_id');

            // 规格等数据
            $itemRelAttributesService = new ItemRelAttributesService();
            $attrList = $itemRelAttributesService->lists(['item_id' => $itemIds, 'attribute_type' => 'item_spec'], 1, -1, ['attribute_sort' => 'asc']);
            if ($attrList) {
                $itemsAttributesService = new ItemsAttributesService();
                $attrData = $itemsAttributesService->getItemsRelAttrValuesList($attrList['list']);
            }
        }

        foreach ($result['list'] as $key => $val) {
            $result['list'][$key]['total_num'] = 0;
            foreach ($val['pending_data'] as $k => $v) {
                if (isset($itemList[$v['item_id']])) {
                    $result['list'][$key]['pending_data'][$k] = array_merge($v, $itemList[$v['item_id']]);

                    if (isset($attrData['item_spec']) && isset($attrData['item_spec'][$v['item_id']])) {
                        $itemSpecStr = [];
                        foreach ($attrData['item_spec'][$v['item_id']] as $spec) {
                            $itemSpecStr[] = $spec['spec_name'] . ':' . $spec['spec_value_name'];
                        }
                        $result['list'][$key]['pending_data'][$k]['item_spec_desc'] = implode(',', $itemSpecStr);
                    }
                }
                $result['list'][$key]['total_num'] += $v['num'];
            }
        }

        return $this->response->array($result);
    }

    public function pendingCartData(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $params['company_id'] = $authInfo['company_id'];
        $params['operator_id'] = $authInfo['operator_id'];
        $params['distributor_id'] = $request->get('distributor_id', 0);
        $params['user_id'] = $request->get('user_id', 0);
        $operatorPendingOrderService = new OperatorPendingOrderService();
        $result = $operatorPendingOrderService->pendingCartData($params);
        return $this->response->array($result);
    }

    public function pendingOrderData(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $params['company_id'] = $authInfo['company_id'];
        $params['operator_id'] = $authInfo['operator_id'];
        $params['distributor_id'] = $request->get('distributor_id', 0);
        $params['user_id'] = $request->get('user_id', 0);
        $params['order_id'] = $request->get('order_id');
        if (!$params['order_id']) {
            throw new ResourceException('缺少订单号');
        }
        $operatorPendingOrderService = new OperatorPendingOrderService();
        $result = $operatorPendingOrderService->pendingOrderData($params);
        return $this->response->array($result);
    }

    public function fetchPendingData(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $params['company_id'] = $authInfo['company_id'];
        $params['operator_id'] = $authInfo['operator_id'];
        $params['distributor_id'] = $request->get('distributor_id', 0);
        $params['user_id'] = $request->get('user_id', 0);
        $params['pending_id'] = $request->get('pending_id');
        if (!$params['pending_id']) {
            throw new ResourceException('缺少挂单ID');
        }
        $operatorPendingOrderService = new OperatorPendingOrderService();
        $result = $operatorPendingOrderService->fetchPendingData($params);
        return $this->response->array($result);
    }

    public function delPendingData(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $filter['company_id'] = $authInfo['company_id'];
        $filter['operator_id'] = $authInfo['operator_id'];
        // $filter['distributor_id'] = $request->get('distributor_id', 0);
        $filter['pending_id'] = $request->get('pending_id');
        if (!$filter['pending_id']) {
            throw new ResourceException('缺少挂单ID');
        }
        $operatorPendingOrderService = new OperatorPendingOrderService();
        $result = $operatorPendingOrderService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }
}
