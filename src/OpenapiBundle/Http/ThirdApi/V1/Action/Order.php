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

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;

use OpenapiBundle\Http\Controllers\Controller as Controller;


use MembersBundle\Services\MemberService;

use MembersBundle\Traits\GetCodeTrait;
use MembersBundle\Entities\MembersAssociations;
use OrdersBundle\Traits\GetOrderServiceTrait;

class Order extends Controller
{
    use GetCodeTrait;
    use GetOrderServiceTrait;
    /**
     * @SWG\Get(
     *     path="/ecx.order.list",
     *     summary="订单列表",
     *     tags={"订单"},
     *     description="订单列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.order.list" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="unionid", description="unionid" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="手机号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function list(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all();
        $rules = [
            'mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            'unionid' => ['sometimes|string', '请填写unionid'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }
        if ((!isset($params['unionid']) || empty($params['unionid'])) && (!isset($params['mobile']) || empty($params['mobile']))) {
            $this->api_response('fail', 'unionid或者手机号必填', null, 'E0001');
        }
        $memberService = new MemberService();
        if (isset($params['mobile']) && $params['mobile']) {
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'mobile' => $params['mobile']]);
        } else {
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $memberInfo = $membersAssociationsRepository->get(['unionid' => $params['unionid'], 'company_id' => $companyId, 'user_type' => 'wechat']);
        }
        if (!$memberInfo) {
            $this->api_response('fail', '会员信息获取失败', null, 'E0001');
        }

        $filter['user_id'] = $memberInfo['user_id'];
        $filter['company_id'] = $companyId;
        $orderService = $this->getOrderService('normal');

        $page = 1;
        $pageSize = 10;


        $count = $orderService->count($filter);
        $total_fee = $orderService->sum($filter, 'total_fee');
        if ($count <= 0) {
            $return['count'] = 0;
            $return['total_fee'] = 0;
            $return['order_avg'] = 0;
            $return['list'] = [];
            return $return;
        }
        $return['count'] = $count;
        $return['total_fee'] = bcdiv($total_fee, 100, 2);
        $return['order_avg'] = bcdiv($return['total_fee'], $return['count'], 2);
        if ($params['page'] ?? 0) {
            $page = $params['page'];
            $pageSize = $params['page_size'];
        }
        $orderList = $orderService->getOrderList($filter, $page, $pageSize);

        $tradeService = new TradeService();
        $orderIdList = array_column($orderList['list'], 'order_id');
        $tradeIndex = $tradeService->getTradeIndexByOrderIdList($companyId, $orderIdList);

        foreach ($orderList['list'] as $key => $value) {
            $order = [
                'order_id' => $value['order_id'],
                'trade_no' => $tradeIndex[$value['order_id']] ?? '-',
                'order_status' => $value['order_status'],
                'total_fee' => bcdiv($value['total_fee'], 100, 2),
                'create_time' => $value['create_time'],
            ];
            foreach ($value['items'] as $item) {
                $item = [
                    'item_name' => $item['item_name'],
                    'item_id' => $item['item_id'],
                    'item_bn' => $item['item_bn'],
                    'price' => bcdiv($item['price'], 100, 2),
                    'total_fee' => bcdiv($item['total_fee'], 100, 2),
                    'num' => $item['num'],
                    'item_spec_desc' => $item['item_spec_desc'],
                    'pic' => $item['pic']
                ];
                $return['list'][] = array_merge($order, $item);
            }
        }
        $this->api_response('true', '操作成功', $return, 'E0000');
    }
}
