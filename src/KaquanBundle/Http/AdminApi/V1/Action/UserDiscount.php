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

namespace KaquanBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use SalespersonBundle\Services\SalespersonCartService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;

use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\DiscountCardService;
use KaquanBundle\Services\UserDiscountService;

use MembersBundle\Services\UserGroupService;

class UserDiscount extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/card_consume",
     *     summary="卡券扫码核销",
     *     tags={"卡券"},
     *     description="卡券扫码核销",
     *     operationId="userCardConsume",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店id", type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="string"),
     *     @SWG\Parameter( name="code", in="query", description="优惠券id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function userCardConsume(Request $request)
    {
        $authInfo = $this->auth->user();

        $shopId = $request->input('shop_id');
        if ($authInfo['shop_ids'] && $shopId && !in_array($shopId, $authInfo['shop_ids'])) {
            throw new ResourceException('门店有误,请重新确认');
        }

        $distributorId = $request->get('distributor_id');
        if ($authInfo['distributor_ids'] && $distributorId && !in_array($distributorId, $authInfo['distributor_ids'])) {
            throw new ResourceException('店铺有误,请重新确认');
        }

        if (!$request->input('code')) {
            throw new ResourceException('核销卡券失败,code码必填.');
        }

        $code = $request->input('code');

        $companyId = $authInfo['company_id'];

        $params['consume_outer_str'] = '扫码核销';
        if ($shopId) {
            $params['shop_id'] = $shopId;
        }
        if ($distributorId) {
            $params['distributor_id'] = $distributorId;
        }
        $userDiscountService = new UserDiscountService();
        $result = $userDiscountService->userConsumeCard($companyId, $code, $params);
        return $this->response->array(['status' => $result]);
    }



    /**
     * @SWG\Get(
     *     path="/wxapp/user_card_detail",
     *     summary="获取核销卡券的详细信息",
     *     tags={"卡券"},
     *     description="获取核销卡券的详细信息",
     *     operationId="getUserCardDetail",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="code", in="query", description="根据状态筛选", type="string"),
     *     @SWG\Response(
     *          response=200,
     *          description="成功返回结构",
     *          @SWG\Schema(
     *              @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="detail", type="object",
     *                      ref="#/definitions/UserDiscount"
     *                  ),
     *                  @SWG\Property( property="shop_list", type="object",
     *                      @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *                      @SWG\Property( property="list", type="array",
     *                          @SWG\Items( type="object",
     *                              ref="#/definitions/shopList"
     *                          ),
     *                      ),
     *                  ),
     *                  @SWG\Property( property="card_info", type="object",
     *                      ref="#/definitions/DiscountCard"
     *                  ),
     *                  @SWG\Property( property="card_code", type="string", example="[]", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getUserCardDetail(Request $request)
    {
        if (!$request->input('code')) {
            throw new ResourceException('核销卡券失败,code码必填.');
        }

        $filter['code'] = $request->input('code');

        $authInfo = $this->auth->user();
        $filter['company_id'] = $authInfo['company_id'];

        $userDiscountService = new UserDiscountService();
        $cardData = $userDiscountService->getUserCardInfo($filter, true);
        if ($cardData['detail']) {
            $cardData['detail']['begin_date'] = date('Y-m-d', $cardData['detail']['begin_date']);
            $cardData['detail']['end_date'] = date('Y-m-d', $cardData['detail']['end_date']);
        } else {
            throw new ResourceException('优惠券码错误或已过期');
        }
        return $this->response->array($cardData);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/salespersongivecoupons",
     *     summary="导购员发放优惠券给会员",
     *     tags={"卡券"},
     *     description="导购员发放优惠券给会员",
     *     operationId="giveUserCoupons",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="user_ids", in="formData", description="用户id,多用户使用','分割", type="string"),
     *     @SWG\Parameter( name="group_id", in="formData", description="群组id", type="string"),
     *     @SWG\Parameter( name="coupons_ids", in="formData", description="优惠券id,多优惠券使用','分割", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="result", type="string", example="true", description="返回数据"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function giveUserCoupons(Request $request)
    {
        $salesperson_info = $this->auth->user();
        $user_discount_server = new UserDiscountService();
        $users = $request->input('user_ids', '');
        $group = $request->input('group_id', '');
        $coupons = $request->input('coupons_ids', '');

        if ($group) {
            $user_group_service = new UserGroupService();
            $filter = [
                "salesperson_id" => $salesperson_info['salesperson_id'],
                "company_id" => $salesperson_info['company_id'],
                "group_id" => $group
            ];
            $users_info = $user_group_service->getUsersByGroup($filter);
            $users = [];
            foreach ($users_info['list'] as $user) {
                array_push($users, $user['user_id']);
            }
        }

        if (empty($users) || $users === []) {
            throw new ResourceException('请选择用户');
        }
        if (empty($coupons)) {
            throw new ResourceException('请选择优惠券');
        }

        $coupons = explode(',', $coupons);
        if (!is_array($users)) {
            $users = explode(',', $users);
        }

        $result = $user_discount_server->giveUserCoupons($salesperson_info, $users, $coupons);

        if ($result) {
            return $this->response->array(['result' => $result]);
        } else {
            return $this->response->array(['result' => false]);
        }
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/salespersongivecoupons/{id}",
     *     summary="导购员重试发放优惠券给会员",
     *     tags={"卡券"},
     *     description="导购员重试发放优惠券给会员",
     *     operationId="tryGiveUserCoupons",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="id", in="formData", description="发放优惠券记录id", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="result", type="string", example="true", description="返回数据"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function tryGiveUserCoupons($id, Request $request)
    {
        $salespersonInfo = $this->auth->user();
        $userDiscountServer = new UserDiscountService();

        $result = $userDiscountServer->tryGiveUserCoupons($id, $salespersonInfo);
        if ($result) {
            return $this->response->array(['result' => $result]);
        } else {
            return $this->response->array(['result' => false]);
        }
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/permissioncoupons",
     *     summary="获取导购员可发放优惠券列表",
     *     tags={"卡券"},
     *     description="获取导购员可发放优惠券列表",
     *     operationId="getPermissionCouponsList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/DiscountCard"
     *                       ),
     *                  ),
     *                  @SWG\Property( property="coupons_setting", type="object",
     *                          @SWG\Property( property="limit_cycle", type="string", example="月", description=""),
     *                          @SWG\Property( property="grant_total", type="string", example="", description=""),
     *                          @SWG\Property( property="given_num", type="string", example="0", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getPermissionCouponsList(Request $request)
    {
        $salesperson_info = $this->auth->user();
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);
//        $distributor_id = $request->input('distributor_id', 0);
        $redis_conn = app('redis')->connection('default');
        $coupons_setting = $redis_conn->hgetall('coupongrantset' . $salesperson_info['company_id']);
        if (!$coupons_setting) {
            return $this->response->array([]);
        }
        $coupons_arr = explode(",", $coupons_setting['coupons'] ?? '');

        $user_discount_service = new UserDiscountService();
        if ($coupons_arr[0] == '') {
            $list['list'] = [];
        } else {
            $filter = [];
            $filter['date_type'] = 'DATE_TYPE_FIX_TERM';
            $filter['end_date'] = time();
            $filter['company_id'] = $salesperson_info['company_id'];
            $filter['card_id'] = $coupons_arr;
//            if ($distributor_id) {
//                $filter['distributor_id|contains'] = ',' . $distributor_id . ',';
//            }

            $discountCardService = new KaquanService(new DiscountCardService());
            $list = $discountCardService->effectiveFilterLists($filter, ["created" => "DESC"], $page_size, $page);

            foreach ($list['list'] as &$value) {
                $value['get_num'] = $user_discount_service->getCardGetNum($value['card_id'], $filter['company_id']);
            }
        }

        $coupons_return = [
            'limit_cycle' => $coupons_setting['limit_cycle'] ?? $coupons_setting['limit_cycle'] == 'week' ? '周' : '月',
            'grant_total' => $coupons_setting['grant_total'] ?? ''
        ];
        $list['coupons_setting'] = $coupons_return;

        // 获取导购限制周期内已发放的优惠券数量

        $given_num = $user_discount_service->getSalespersonGivenNum($salesperson_info, $coupons_setting['limit_cycle']);
        $list['coupons_setting']['given_num'] = $given_num;

        return $this->response->array($list);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/getusercoupons",
     *     summary="导购员获取用户已领取的优惠券列表",
     *     tags={"卡券"},
     *     description="导购员获取用户已领取的优惠券列表",
     *     operationId="getUserCouponsList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="isOrder", in="query", description="检测某个商品是否适合于该卡券 1 0", type="integer", default="0"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="integer"),
     *     @SWG\Parameter( name="amount", in="query", description="实际金额", type="integer"),
     *     @SWG\Parameter( name="code", in="query", description="卡券 code 序列号", type="integer"),
     *     @SWG\Parameter( name="card_id", in="query", description="微信用户领取的卡券 id", type="integer"),
     *     @SWG\Parameter( name="use_scenes", in="query", description="可被核销的方式", type="integer"),
     *     @SWG\Parameter( name="use_platform", in="query", description="优惠券适用平台（mall:线上商城专用, store:门店专用）", type="integer"),
     *     @SWG\Parameter( name="user", in="query", description="用户id", type="integer"),
     *     @SWG\Parameter( name="valid", in="query", description="优惠券是否有效", type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/UserDiscount"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getUserCouponsList(Request $request)
    {
        $salesperson_info = $this->auth->user();

        $page = $request->input('page_no', 1);
        $limit = $request->input('page_size', 20);
        $isOrder = $request->input('isOrder', false);

        if ($request->input('distributor_id')) {
            $shopId = $request->get('distributor_id');
        }
        //实际金额大于配置金额 least_cost
        if ($request->input('amount')) {
            $filter['least_cost|lte'] = $request->input('amount');
            //结束时间大于当前时间,开始时间小于等于当前时间
            //此处的时间是东八区
            $filter['begin_date|lte'] = time();
            $filter['card_type|notIn'] = ['gift'];
        }
        $filter['end_date|gt'] = time();

        if ($request->input('code')) {
            $filter['code'] = $request->input('code');
        }

        if ($request->input('card_id')) {
            $filter['card_id'] = $request->input('card_id');
        }

        if ($request->input('use_scenes')) {
            $filter['use_scenes'] = $request->input('use_scenes');
        }

        if ($request->input('use_platform')) {
            $filter['use_platform'] = $request->input('use_platform');
        }

        $user = $request->input('user', 0);

        if ($user == 0) {
            throw new ResourceException('获取优惠券失败，信息有误');
        }

        if ($request->input('status')) {
            $filter['status'] = $request->input('status');
        }

        //未使用的优惠券
        $filter['company_id'] = $salesperson_info['company_id'];
        $filter['user_id'] = $user;

        $valid = true;
        if ($request->input('valid') === 'false' || $request->input('valid') === 0 || $request->input('valid') === false) {
            $valid = false;
            $filter['or']['status'] = 2;
            $filter['or']['begin_date|gt'] = time();
            $filter['or']['end_date|lte'] = time();
        } else {
            $filter['status'] = [1, 4];
            $filter['begin_date|lte'] = time();
            $filter['end_date|gt'] = time();
        }

        $userDiscountService = new UserDiscountService();
        $cardLists = $userDiscountService->getUserDiscountList($filter, $page, $limit);
        $cardDataLists = [];
        if ($cardLists['list']) {
            foreach ($cardLists['list'] as $key => $info) {
                $info['valid'] = $valid;
//                $cardData['locked'] = false;
                $info['coupon'] = [
                    'card_id' => $info['card_id'],
                    'title' => $info['title'],
                    'code' => $info['code'],
                    'card_type' => $info['card_type'],
                ];
                if ($info['card_type'] == "cash") {
                    $info['coupon']['least_cost'] = $info['least_cost'];
                    $info['coupon']['reduce_cost'] = $info['reduce_cost'];
                } elseif ($info['card_type'] == "discount") {
                    $info['coupon']['discount'] = $info['discount'];
                }

                $info['begin_date'] = date('Y-m-d', $info['begin_date']);
                $info['end_date'] = date('Y-m-d', $info['end_date']);

                $cardDataLists[] = $info;
            }

            foreach ($cardDataLists as &$newInfo) {
                $shopData = [];
                $newInfo['storeList'] = $shopData;
            }
            unset($newInfo);
        }

        if ($isOrder) {
            $filter = [];
            $filter['salesperson_id'] = $salesperson_info['salesperson_id'];
            $filter['distributor_id'] = $salesperson_info['distributor_id'];
            $filter['company_id'] = $salesperson_info['company_id'];
//             if ($filter['company_id'] != $salesperson_info['company_id']) {
//                 $filter['distributor_id'] = $this->getDefaultDistributorId($filter['company_id']);
//             }
            $inputData = $request->all('items', 'cart_type', 'is_checkout');
            $this->checkCardValid($filter, $cardDataLists, $inputData, $user);
        }


        $result['list'] = $cardDataLists;
//        $result['count'] = $cardLists['count'];
        return $this->response->array($result);
    }

    //检测某个某个商品是否适合于该卡券
    private function checkCardValid($filter, &$cardLists, $inputData, $userId)
    {
        $shopId = 0;
        $distributorId = isset($filter['distributor_id']) ? $filter['distributor_id'] : 0;
        if (!$distributorId) {
            $distributorId = $inputData['shop_id'] ?? 0;
        }
        $itemIds = [];
        $items = [];

        if (isset($inputData['is_checkout']) && $inputData['is_checkout'] == 'true') {
            $cartService = new SalespersonCartService();
            $cartData = $cartService->getCartdataList($filter, $userId, true);
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
                $items[$item['item_id']]['totalFee'] = $item['price'] ?? 0;
            }
        }

        $itemsList = [];
        if ($items) {
            foreach ($cardLists as &$cardata) {
                $amount = $this->countItemAmount($itemIds, $cardata['rel_item_ids'], $items);
                if (!$amount) {
                    $cardata['valid'] = false;
                    $cardata['coupon']['valid'] = false;
                } else {
                    if ($cardata['card_type'] == 'discount' && $cardata['least_cost'] && $cardata['least_cost'] > $amount) {
                        $cardata['valid'] = false;
                        $cardata['coupon']['valid'] = false;
                    } elseif ($cardata['card_type'] == 'cash' && $cardata['least_cost'] > $amount) {
                        $cardata['valid'] = false;
                        $cardata['coupon']['valid'] = false;
                    }
                }

                $cardata['itemIfall'] = true;
                $itemlist = [];
                if (is_array($cardata['rel_item_ids'])) {
                    foreach ($cardata['rel_item_ids'] as $itemId) {
                        if (isset($itemsList[$itemId])) {
                            $itemlist[] = $itemsList[$itemId];
                        }
                    }

                    if ($itemlist) {
                        $cardata['itemIfall'] = false;
                    }
                }
                $cardata['itemList'] = $itemlist;
                if (is_array($cardata['rel_distributor_ids']) && !in_array($distributorId, $cardata['rel_distributor_ids'])) {
                    $cardata['valid'] = false;
                    $cardata['coupon']['valid'] = false;
                }
                $storeList = [];
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
     *     path="/wxapp/couponrecord",
     *     summary="导购员获取赠券记录",
     *     tags={"卡券"},
     *     description="导购员获取赠券记录",
     *     operationId="getCouponsRecord",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="start", in="query", description="开始时间", type="integer" ),
     *     @SWG\Parameter( name="end", in="query", description="结束时间", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/DiscountCard"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getCouponsRecord(Request $request)
    {
        $salesperson_info = $this->auth->user();
        $start = $request->input('start', '');
        $end = $request->input('end', '');

        if ($start) {
            $start = strtotime(date('Ymd', $start));
        }
        if ($end) {
            $end = strtotime(date('Ymd', $end));
        }

        $filter = [
            'salesperson_id' => $salesperson_info['salesperson_id'],
            'company_id' => $salesperson_info['company_id']
        ];
        if ($start) {
            $filter['give_time|gte'] = $start;
        }
        if ($end) {
            $filter['give_time|lte'] = $end + 3600 * 24 - 1;
        }

        $user_discount_service = new UserDiscountService();
        $result = $user_discount_service->getCouponsRecord($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/sendCouponList",
     *     summary="获取导购发放优惠券列表",
     *     tags={"卡券"},
     *     description="获取导购发放优惠券列表",
     *     operationId="getSendCouponsList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="status", in="query", description="发放状态 0失败 1成功", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="17", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="44", description="导购员id"),
     *                          @SWG\Property( property="salesperson_name", type="string", example="新鑫鑫", description="导购员名称"),
     *                          @SWG\Property( property="user_id", type="string", example="20093", description="用户id"),
     *                          @SWG\Property( property="user_name", type="string", example="", description="会员名"),
     *                          @SWG\Property( property="coupons_id", type="string", example="350", description="优惠券id"),
     *                          @SWG\Property( property="coupons_name", type="string", example="测试666", description="优惠券名称"),
     *                          @SWG\Property( property="number", type="string", example="1", description="导购员编号"),
     *                          @SWG\Property( property="status", type="string", example="0", description=""),
     *                          @SWG\Property( property="fail_reason", type="string", example="null", description="失败原因"),
     *                          @SWG\Property( property="give_time", type="string", example="1589885595", description="发送优惠券时间"),
     *                          @SWG\Property( property="updated", type="string", example="1589885595", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getSendCouponsList(Request $request)
    {
        $authInfo = $this->auth->user();
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $status = $request->input('status', null);

        $filter = [
            'salesperson_id' => $authInfo['salesperson_id'],
            'company_id' => $authInfo['company_id']
        ];

        if (null !== $status) {
            $filter['status'] = $status ? 1 : 0;
        }

        $userDiscountService = new UserDiscountService();
        $result = $userDiscountService->getSalespersonSendCouponsList($filter, $page, $pageSize);

        return $this->response->array($result);
    }
}
