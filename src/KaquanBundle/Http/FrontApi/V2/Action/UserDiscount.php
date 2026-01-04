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

namespace KaquanBundle\Http\FrontApi\V2\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use KaquanBundle\Services\DiscountCardService;
use KaquanBundle\Services\UserDiscountService;

use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;


use DistributionBundle\Services\DistributorService;

use CompanysBundle\Traits\GetDefaultCur;
use OrdersBundle\Services\CartService;
use SalespersonBundle\Services\SalespersonCartService;

//use PointBundle\Services\PointMemberRuleService;

class UserDiscount extends BaseController
{
    use GetDefaultCur;

    /**
     * @SWG\Get(
     *     path="/wxapp/user/newGetCardList",
     *     summary="获取用户已领取的优惠券列表",
     *     tags={"卡券"},
     *     description="获取用户已领取的优惠券列表",
     *     operationId="getUserCardList",
     *     @SWG\Parameter(
     *         name="amount", in="query", description="使用优惠券之前的订单金额", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="items", in="query", description="使用优惠券券的商品", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="code", in="query", description="优惠券的 code 码", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="card_id", in="query", description="指定优惠券card_id", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="shop_id", in="query", description="门店 shop_id 号", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id", in="query", description="商品id集", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page_no", in="query", description="page", required=false, type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="page_size", in="query", description="limit", required=false, type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="use_platform", in="query", description="可选值为：picker", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page_type", in="query", description="优惠券适用平台 mall 线上商城专用 or store门店专用", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="use_scenes", in="query", description="核销场景。可选值有，ONLINE:线上商城(兑换券不可使用);QUICK:快捷买单(兑换券不可使用);SWEEP:门店支付(扫码核销);SELF:到店支付(自助核销)", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id", in="query", description="店铺id", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="is_checkout", in="query", description="", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="iscrossborder", in="query", description="是否跨境", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="isShopScreen", in="query", description="是否门店大屏", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="cxdid", in="query", description="促销单ID", required=false, type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Items(ref="#/definitions/CardList")
     *      )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getUserCardList(Request $request)
    {
        $user = $request->get('auth');
        $filter['company_id'] = $user['company_id'];
        $filter['user_id'] = $user['user_id'];

        if ($request->input('page_type') == 'picker') {
            $filter['card_type'] = ['discount', 'cash'];
        }

        if ($request->input('use_platform')) {
            $filter['use_platform'] = $request->input('use_platform');
        }
        if ($request->input('use_scenes')) {
            $filter['use_scenes'] = $request->input('use_scenes');
        }

        if ($request->input('code')) {
            $filter['code'] = $request->input('code');
        }

        if ($request->input('card_id')) {
            $filter['card_id'] = $request->input('card_id');
        }

        if ($request->input('amount')) {
            $filter['least_cost|lte'] = $request->input('amount');
        }

        if ($request->input('distributor_id') != 'undefined' || !is_null($request->input('distributor_id'))) {
            $filter['distributor_id'] = $request->input('distributor_id');
        }

        $valid = true;
        if ($request->input('valid') === 'false' || $request->input('valid') === 0 || $request->input('valid') === false) {
            $valid = false;
            $filter['or']['status'] = 2;
            $filter['or']['begin_date|gt'] = time() ;
            $filter['or']['end_date|lte'] = time() ;
        } else {
            $filter['status'] = [1,4];
            $filter['begin_date|lte'] = time() ;
            $filter['end_date|gt'] = time() ;
        }

        $page = $request->input('page_no', 1);
        $limit = $request->input('page_size', 20);
        $inputData = [];
        //获取有效的商品
        if ($valid) {
            $inputData = $request->all('items', 'distributor_id', 'shop_id', 'cart_type', 'is_checkout', 'cxdid', 'point_use', 'iscrossborder', 'isShopScreen');
            $items = $this->getValidItemsData($user['company_id'], $user['user_id'], $inputData);
            if ($items) {
                $filter['item_id'] = array_keys($items);
            }
        }
        $userDiscountService = new UserDiscountService();
        $cardLists = $userDiscountService->getNewUserCardList($filter, $page, $limit, false, true);

        if (!$cardLists['list']) {
            return $this->response->array(['list' => [], 'count' => 0]);
        }

        foreach ($cardLists['list'] as &$cardata) {
            $cardata['valid'] = $valid;
            $cardData['locked'] = false;

            if (!$valid && $cardata['status'] == 2) {
                $cardata['tagClass'] = 'used';
                $cardata['invalid_desc'] = '已使用';
            } elseif (!$valid && $cardata['begin_date'] > time()) {
                $cardata['tagClass'] = 'notstarted';
                $cardata['invalid_desc'] = '未到使用时间';
            } elseif (!$valid && $cardata['end_date'] < time()) {
                $cardata['tagClass'] = 'overdue';
                $cardata['invalid_desc'] = '已过期';
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

            if ($request->input('amount') && !$request->input('items') && $cardata['least_cost'] > $request->input('amount')) {
                $cardata['valid'] = false;
                $cardata['coupon']['valid'] = false;
                $cardata['invalid_desc'] = '订单金额需满'.bcdiv($cardata['least_cost'], 100).'元';
            }
        }

        //获取有效优惠券时，判断是否适合某个商品 或 店铺
        if ($valid) {
            $this->checkCardValid($user['company_id'], $cardLists, $inputData, $user['user_id'], $items);
        }
        $cardLists['cur'] = $this->getCur($filter['company_id']);
        return $this->response->array($cardLists);
    }

    //检测某个某个商品是否适合于该卡券
    private function checkCardValid($companyId, &$cardLists, $inputData, $userId, $items)
    {
        $shopId = 0;
        $distributorId = isset($inputData['distributor_id']) ? $inputData['distributor_id'] : 0;
        if ($distributorId) {
            $distributorService = new DistributorService();
            $filter = [
                'distributor_id' => $distributorId,
                'company_id' => $companyId
            ];
            $distrobutor = $distributorService->getInfo($filter);
            if ($distrobutor) {
                $shopId = $distrobutor['shop_id'];
            }
        }

        $shopId = isset($inputData['shop_id']) ? $inputData['shop_id'] : $shopId;

        $poiList = [];
        $shopList = [];
        if ($shopId) {
            $poiFilter['company_id'] = $companyId;
            $poiFilter['expired_at|gt'] = time();
            $shopsService = new ShopsService(new WxShopsService());
            $lists = $shopsService->getShopsList($poiFilter, 1, 50);
            foreach ($lists['list'] as $shop) {
                $shopList[$shop['wxShopId']] = $shop['storeName'];
            }
        }

        $itemsList = [];
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
                    $cardata['invalid_desc'] = '订单金额需有大于0元';
                } else {
                    if ($cardata['card_type'] == 'discount' && $cardata['least_cost'] && $cardata['least_cost'] > $amount - $pointFee) {
                        $cardata['valid'] = false;
                        $cardata['coupon']['valid'] = false;
                        $cardata['invalid_desc'] = '订单金额需满'.bcdiv($cardata['least_cost'], 100).'元';
                    } elseif ($cardata['card_type'] == 'cash' && $cardata['least_cost'] > $amount - $pointFee) {
                        $cardata['valid'] = false;
                        $cardata['coupon']['valid'] = false;
                        $cardata['invalid_desc'] = '订单金额需满'.bcdiv($cardata['least_cost'], 100).'元';
                    }
                }

                $cardata['itemIfall'] = true;
                $itemlist = [];

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
                            app('log')->debug('itemIds' . $id);
                            if (!isset($hash[$id]) || !$hash[$id]) {
                                $valid = true;
                                break;
                            }
                        }
                        if (!$valid) {
                            $cardata['valid'] = false;
                            $cardata['coupon']['valid'] = false;
                            $cardata['invalid_desc'] = '订单商品不适用';
                        }
                    } else {
                        foreach ($cardata['rel_item_ids'] as $itemId) {
                            if (isset($itemsList[$itemId])) {
                                $itemlist[] = $itemsList[$itemId];
                            }
                        }
                    }

                    if ($itemlist) {
                        $cardata['itemIfall'] = false;
                    }
                }
                $cardata['itemList'] = $itemlist;
                if (is_array($cardata['rel_distributor_ids']) && !in_array($inputData['distributor_id'], $cardata['rel_distributor_ids'])) {
                    $cardata['valid'] = false;
                    $cardata['coupon']['valid'] = false;
                    $cardata['invalid_desc'] = '不适用于该店铺';
                }
                if (is_array($cardata['rel_shops_ids']) && in_array($shopId, $cardata['rel_shops_ids'])) {
                    $cardata['valid'] = false;
                    $cardata['coupon']['valid'] = false;
                    $cardata['invalid_desc'] = '不适用于该门店';
                }
                $storeList = [];
                if (is_array($cardata['rel_shops_ids'])) {
                    foreach ($cardata['rel_shops_ids'] as $k => $shop_id) {
                        if (isset($shopList[$shop_id])) {
                            $storeList[$k] = $shopList[$shop_id];
                        }
                    }
                }
                $cardata['storeList'] = $storeList;
                if ($cardata['storeList']) {
                    $cardata['ifall'] = false;
                } else {
                    $cardata['ifall'] = true;
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

    /**
     * @SWG\Get(
     *     path="/wxapp/user/newGetCardDetail",
     *     summary="获取用户已领取的优惠券详情",
     *     tags={"卡券"},
     *     description="获取用户已领取的优惠券详情",
     *     operationId="getUserDiscountDetail",
     *     @SWG\Parameter(
     *         name="code", in="query", description="优惠券的 code 码", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="card_id", in="query", description="指定优惠券card_id", required=false, type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Items(ref="#/definitions/newGetCardDetail")
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getUserDiscountDetail(Request $request)
    {
        if ($request->input('code')) {
            $filter['code'] = $request->input('code');
        }

        if ($request->input('card_id')) {
            $filter['card_id'] = $request->input('card_id');
        }

        $user = $request->get('auth');
        $filter['company_id'] = $user['company_id'];
        $filter['user_id'] = $user['user_id'];
        $filter['wxapp_appid'] = $user['wxapp_appid'];

        $userDiscountService = new UserDiscountService();
        $cardData = $userDiscountService->getUserCardInfo($filter, true);
        $cardData['cur'] = $this->getCur($filter['company_id']);
        return $this->response->array($cardData);
    }

    private function getValidItemsData($companyId, $userId, $inputData)
    {
        $itemIds = [];
        $items = [];
        $distributorId = isset($inputData['distributor_id']) ? $inputData['distributor_id'] : 0;
        if (isset($inputData['is_checkout']) && $inputData['is_checkout'] == 'true') {
            if (isset($inputData['cxdid'], $inputData['cart_type']) && $inputData['cart_type'] == 'cxd') {
                $salespersonCartService = new SalespersonCartService();
                $filter = [
                    'cxdid' => $inputData['cxdid'],
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                ];
                $cartData = $salespersonCartService->getCartdataList($filter, $userId, true, true);
            } else {
                $cartService = new CartService();
                $cartType = (isset($inputData['cart_type']) && $inputData['cart_type']) ? $inputData['cart_type'] : 'cart';
                $cartData = $cartService->getCartList($companyId, $userId, $distributorId, $cartType, 'distributor', true, $inputData['iscrossborder'], $inputData['isShopScreen']);
            }
            $cartlist = reset($cartData['valid_cart']);
            foreach ($cartlist['list'] as $item) {
                $itemIds[] = $item['item_id'];
                $items[$item['item_id']]['num'] = $item['num'];
                $items[$item['item_id']]['totalFee'] = $item['total_fee'] ?? 0;
            }
        } elseif (isset($inputData['items']) && $inputData['items']) {
            if (!is_array($inputData['items'])) {
                $inputData['items'] = json_decode($inputData['items'], true);
            }
            $itemsData = $inputData['items'];
            foreach ($itemsData as $item) {
                $itemIds[] = $item['item_id'];
                $items[$item['item_id']]['num'] = $item['num'];
                $items[$item['item_id']]['totalFee'] = $item['total_fee'] ?? 0;
            }
        }
        return $items;
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/user/getUserCardList",
     *     summary="我的优惠券列表",
     *     tags={"卡券"},
     *     description="我的优惠券列表",
     *     operationId="getMyUserCardList",
     *     @SWG\Parameter(
     *         name="status", in="query", description="状态 1:未使用 2:已使用 3:已过期", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="cart_type", in="query", description="优惠券类型 discount:折扣券，cash:代金券，new_gift:兑换券", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page", in="query", description="page", required=false, type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize", in="query", description="limit", required=false, type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="scope_type", in="query", description="筛选用户优惠券的适用范围【all 用户的所有优惠券】【all_distributor 适用于所有店铺的优惠券】【distributor 适用于部分店铺的优惠券】", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="source_type", in="query", description="优惠券的来源类型【distributor 店铺】", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="source_id", in="query", description="优惠券的来源id", required=false, type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Items(ref="#/definitions/CardList")
     *      )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getMyUserCardList(Request $request)
    {
        $user = $request->get('auth');
        $filter['company_id'] = $user['company_id'];
        $filter['user_id'] = $user['user_id'];

        $userDiscountService = new UserDiscountService();
        // 状态 1:可使用、未到使用期优惠券 2:已使用 3:已过期、作废
        $status = $request->input('status', '1');
        $filter = $userDiscountService->myUserCardStatusFilter($filter, $status);

        if ($card_type = $request->get('card_type')) {
            $filter['card_type'] = $card_type;
        }
        // 获取适用范围
        if ($scopeType = $request->input("scope_type")) {
            $filter["scope_type"] = $scopeType;
        }
        // 优惠券来源类型
        if ($sourceType = $request->input("source_type")) {
            $filter["discount_card_source_type"] = $sourceType;
        }
        // 优惠券来源id
        if ($sourceId = $request->input("source_id")) {
            $filter["discount_card_source_id"] = $sourceId;
        }
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $cardLists = $userDiscountService->getNewUserCardList($filter, $page, $pageSize);
        if (!$cardLists['list']) {
            return $this->response->array(['list' => [], 'total_count' => 0]);
        }

        foreach ($cardLists['list'] as &$cardata) {
            $cardData['locked'] = false;

            if ($cardata['status'] == 2) {
                $cardata['tagClass'] = 'used';
            } elseif ($cardata['begin_date'] > time() && $cardata['end_date'] > time()) {
                $cardata['tagClass'] = 'notstarted';
            } elseif ($cardata['end_date'] < time()) {
                $cardata['tagClass'] = 'overdue';
            }

            if ($cardata['status'] == 4) {
                $cardData['locked'] = true;
            }

            $cardata['begin_date'] = date('Y-m-d', $cardata['begin_date']);
            $cardata['end_date'] = date('Y-m-d', $cardata['end_date']);
            $cardata['coupon'] = [
                'card_id' => $cardata['card_id'],
                'title' => $cardata['title'],
                'code' => $cardata['code'],
                'card_type' => $cardata['card_type'],
            ];
            if ($cardata['card_type'] == "cash") {
                $cardata['coupon']['least_cost'] = $cardata['least_cost'];
                $cardata['coupon']['reduce_cost'] = $cardata['reduce_cost'];
            } elseif ($cardata['card_type'] == "discount") {
                $cardata['coupon']['discount'] = $cardata['discount'];
            }
        }
        $cardLists['cur'] = $this->getCur($filter['company_id']);
        $cardLists['count'] = [];
        unset($filter['card_type']);
        $cardLists['count']['total'] = $userDiscountService->countUserCard($filter);
        $filter['card_type'] = 'discount';
        $cardLists['count']['discount'] = $userDiscountService->countUserCard($filter);
        $filter['card_type'] = 'cash';
        $cardLists['count']['cash'] = $userDiscountService->countUserCard($filter);
        $filter['card_type'] = 'new_gift';
        $cardLists['count']['new_gift'] = $userDiscountService->countUserCard($filter);

        // 追加店铺id
        (new DiscountCardService())->appendDistributorId((int)$filter["company_id"], $cardLists['list']);
        // 根据店铺id追加店铺信息
        (new DistributorService())->appendDistributorInfo((int)$filter["company_id"], $cardLists['list']);

        return $this->response->array($cardLists);
    }
}
