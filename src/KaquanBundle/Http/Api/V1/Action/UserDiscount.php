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

namespace KaquanBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use KaquanBundle\Services\UserDiscountService;
use CompanysBundle\Services\OperatorCartService;

class UserDiscount extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/api/getUserCardList",
     *     summary="获取用户已领取的优惠券列表",
     *     tags={"卡券"},
     *     description="获取用户已领取的优惠券列表",
     *     operationId="getUserCardList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="amount", in="query", description="使用优惠券之前的订单金额", required=false, type="string"),
     *     @SWG\Parameter(name="code", in="query", description="优惠券的 code 码", required=false, type="string"),
     *     @SWG\Parameter(name="card_id", in="query", description="指定优惠券card_id", required=false, type="string"),
     *     @SWG\Parameter(name="page_no", in="query", description="page", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="limit", required=false, type="integer"),
     *     @SWG\Parameter(name="distributor_id", in="query", description="店铺id", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Items(ref="#/definitions/CardList")
     *      )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getUserCardList(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $request->get('user_id', 0);
        if (!$filter['user_id']) {
            return $this->response->array(['list' => [], 'count' => 0]);
        }

        $filter['card_type'] = ['discount', 'cash'];
        $filter['use_platform'] = 'mall';

        if ($request->input('code')) {
            $filter['code'] = $request->input('code');
        }

        if ($request->input('card_id')) {
            $filter['card_id'] = $request->input('card_id');
        }

        if ($request->input('amount')) {
            $filter['least_cost|lte'] = $request->input('amount');
        }

        $filter['distributor_id'] = $request->input('distributor_id', 0);

        $valid = true;
        $filter['status'] = [1,4];
        $filter['begin_date|lte'] = time();
        $filter['end_date|gt'] = time();

        $cartFilter = [
            'company_id' => $authInfo['company_id'],
            'distributor_id' => $filter['distributor_id'],
            'operator_id' => $authInfo['operator_id'],
        ];
        $operatorCartService = new OperatorCartService();
        $cartData = $operatorCartService->getCartdataList($cartFilter, $filter['user_id'], true);
        $cartData = reset($cartData['valid_cart']);
        $items = [];
        foreach ($cartData['list'] as $item) {
            $items[$item['item_id']]['num'] = $item['num'];
            $items[$item['item_id']]['totalFee'] = $item['total_fee'] ?? 0;
        }
        if ($items) {
            $filter['item_id'] = array_keys($items);
        }

        $page = $request->input('page_no', 1);
        $pageSize = $request->input('page_size', 20);

        $userDiscountService = new UserDiscountService();
        $cardLists = $userDiscountService->getNewUserCardList($filter, $page, $pageSize, false, true);

        if (!$cardLists['list']) {
            return $this->response->array(['list' => [], 'count' => 0]);
        }

        foreach ($cardLists['list'] as &$cardata) {
            $cardata['valid'] = $valid;
            $cardData['locked'] = false;

            if (!$valid && $cardata['status'] == 2) {
                $cardata['tagClass'] = 'used';
            } elseif (!$valid && $cardata['begin_date'] > time()) {
                $cardata['tagClass'] = 'notstarted';
            } elseif (!$valid && $cardata['end_date'] < time()) {
                $cardata['tagClass'] = 'overdue';
            }

            if ($valid && $cardata['status'] == 4) {
                $cardData['locked'] = true;
            }

            $cardata['begin_date'] = date('Y-m-d', $cardata['begin_date']);
            $cardata['end_date'] = date('Y-m-d', $cardata['end_date']);
            $cardata['coupon'] = [
                'card_id' => $cardata['card_id'],
                'title' => $cardata['title'],
                'code' => $cardata['code'],
                'card_type' => $cardata['card_type'],
                'valid' => $valid,
            ];
            if ($cardata['card_type'] == "cash") {
                $cardata['coupon']['least_cost'] = $cardata['least_cost'];
                $cardata['coupon']['reduce_cost'] = $cardata['reduce_cost'];
            } elseif ($cardata['card_type'] == "discount") {
                $cardata['coupon']['discount'] = $cardata['discount'];
            }

            if ($request->input('amount') && !$request->input('items') && $cardata['least_cost'] < $request->input('amount')) {
                $cardata['valid'] = false;
                $cardata['coupon']['valid'] = false;
            }
        }

        //获取有效优惠券时，判断是否适合某个商品 或 店铺
        if ($valid) {
            $this->checkCardValid($authInfo['company_id'], $cardLists, $filter['distributor_id'], $items);
        }
        return $this->response->array($cardLists);
    }

    //检测某个某个商品是否适合于该卡券
    private function checkCardValid($companyId, &$cardLists, $distributorId, $items)
    {
        if ($items) {
            //$pointService = new PointMemberRuleService($companyId);
            //$pointFee = $pointService->pointToMoney($inputData['point_use'] ?? 0);
            $pointFee = 0;

            $itemIds = array_keys($items);
            foreach ($cardLists['list'] as &$cardata) {
                $amount = $this->countItemAmount($itemIds, $cardata['rel_item_ids'], $items);
                if (!$amount) {
                    $cardata['valid'] = false;
                    $cardata['coupon']['valid'] = false;
                } else {
                    if ($cardata['card_type'] == 'discount' && $cardata['least_cost'] && $cardata['least_cost'] > $amount - $pointFee) {
                        $cardata['valid'] = false;
                        $cardata['coupon']['valid'] = false;
                    } elseif ($cardata['card_type'] == 'cash' && $cardata['least_cost'] > $amount - $pointFee) {
                        $cardata['valid'] = false;
                        $cardata['coupon']['valid'] = false;
                    }
                }

                if (is_string($cardata['rel_item_ids'])) {
                    $cardata['rel_item_ids'] = array_filter(explode(',', $cardata['rel_item_ids']));
                }

                if (is_array($cardata['rel_item_ids'])) {
                    if ($cardata['use_bound'] == 5) {
                        // 排除一些关联商品
                        $hash = [];
                        foreach ($cardata['rel_item_ids'] as $itemId) {
                            $hash[$itemId] = true;
                        }
                        // 该卡券是否有效
                        $valid = false;
                        foreach ($itemIds as $id) {
                            if (!isset($hash[$id]) || !$hash[$id]) {
                                $valid = true;
                                break;
                            }
                        }
                        if (!$valid) {
                            $cardata['valid'] = false;
                            $cardata['coupon']['valid'] = false;
                        }
                    }
                }
                if (is_array($cardata['rel_distributor_ids']) && !in_array($distributorId, $cardata['rel_distributor_ids'])) {
                    $cardata['valid'] = false;
                    $cardata['coupon']['valid'] = false;
                }
            }
        }

        return $cardLists;
    }

    private function countItemAmount($itemList, $cardItem, $inputItem)
    {
        $amount = 0;
        if (is_array($cardItem)) {
            foreach ($itemList as $itemId) {
                if (in_array($itemId, $cardItem) && $inputItem[$itemId]['totalFee'] > 0) {
                    $amount += $inputItem[$itemId]['totalFee'];
                }
            }
        } else {
            foreach ($itemList as $itemId) {
                if ($inputItem[$itemId]['totalFee']) {
                    $amount += $inputItem[$itemId]['totalFee'];
                }
            }
        }
        return $amount;
    }
}
