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

namespace KaquanBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Services\PackageQueryService;
use KaquanBundle\Services\PackageReceivesService;
use KaquanBundle\Services\UserDiscountService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

class UserDiscount extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/user/receiveCard",
     *     summary="用户领取卡券",
     *     tags={"卡券"},
     *     description="小程序领取卡券处理",
     *     operationId="receiveCard",
     *     @SWG\Parameter( name="card_id", in="query", description="指定优惠券card_id", required=false, type="string"),
     *     @SWG\Parameter( name="salesperson_id", in="query", description="导购id", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                      property="status", type="object",
     *                     @SWG\Property(property="total_lastget_num", type="integer", example="", description="优惠券剩余库存"),
     *                     @SWG\Property(property="lastget_num", type="integer", example="", description="用户剩余可领库存"),
     *                  ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function receiveCard(Request $request)
    {
        $user = $request->get('auth');
        $userDiscountService = new UserDiscountService();
        $companyId = $user['company_id'];
        $cardId = $request->input('card_id');
        $salespersonId = $request->input('salesperson_id', 0);
        $userId = $user['user_id'];
        if (!$user['user_id'] || !$user['mobile']) {
            throw new ResourceException('您还不是会员，无法领取优惠券');
        }
        $result = $userDiscountService->userGetCard($companyId, $cardId, $userId, "商城本地领取", $salespersonId);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post (
     *     path="/wxapp/user/exchangeCard",
     *     summary="使用兑换卡券",
     *     tags={"卡券"},
     *     description="使用兑换卡券",
     *     operationId="exchangeCard",
     *     @SWG\Parameter( name="user_card_id", in="query", description="用户领取兑换卡券id", required=true, type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品id", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                      property="status", type="object",
     *                     @SWG\Property(property="status", type="string", example="true", description="状态"),
     *                  ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function exchangeCard(Request $request)
    {
        $user = $request->get('auth');
        $userDiscountService = new UserDiscountService();
        $companyId = $user['company_id'];

        $validator = app('validator')->make($request->all(), [
            'user_card_id' => 'required|integer',
            'item_id' => 'required|integer',
            'distributor_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数错误');
        }

        $cardId = $request->input('user_card_id');
        $itemId = $request->input('item_id');
        $distributorId = $request->input('distributor_id');

        $result = $userDiscountService->exchangeCard($companyId, $cardId, $itemId, $distributorId, $user['user_id']);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get (
     *     path="/wxapp/user/exchangeCardInfo",
     *     summary="获取兑换卡券使用信息",
     *     tags={"卡券"},
     *     description="获取兑换卡券使用信息",
     *     operationId="exchangeCardInfo",
     *     @SWG\Parameter( name="user_card_id", in="query", description="用户领取兑换卡券id", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="distributor_info", type="object", description="兑换店铺信息"),
     *                 @SWG\Property( property="barcode_url", type="string", example="data:image/png;base64,/9j/4AAQSkZJRgABAQEAYABgAA...", description="base64条形码"),
     *                 @SWG\Property( property="qrcode_url", type="string", example="data:image/png;base64,/9j/4AAQSkZJRgABAQEAYABgAA...", description="base64二维码图片"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function exchangeCardInfo(Request $request)
    {
        $user = $request->get('auth');
        $companyId = $user['company_id'];

        $userCardId = $request->get('user_card_id');

        if (!$userCardId) {
            throw new ResourceException('user_card_id 必填');
        }

        $userDiscountService = new UserDiscountService();
        return $userDiscountService->exchangeCardInfo($companyId, $userCardId, $user['user_id']);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/user/consumCard",
     *     summary="用户核销卡券",
     *     tags={"卡券"},
     *     description="用户核销卡券",
     *     operationId="ConsumCard",
     *     @SWG\Parameter( name="code", in="query", description="优惠券code码", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                      property="status", type="object",
     *                     @SWG\Property(property="status", type="string", example="true", description="领取状态"),
     *                  ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function ConsumCard(Request $request)
    {
        if (!$request->input('code')) {
            throw new ResourceException('核销卡券失败,code码必填.');
        }
        $user = $request->get('auth');
        if (!$user['user_id'] || !$user['mobile']) {
            throw new ResourceException('核销卡券失败，信息有误');
        }
        $input = $request->input();

        $code = $input['code'];


        $userDiscountService = new UserDiscountService();
        $companyId = $user['company_id'];

        $params['consume_outer_str'] = '买单核销';
        $params['user_id'] = $user['user_id'];

        $result = $userDiscountService->userConsumeCard($companyId, $code, $params);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/user/removeCard",
     *     summary="用户删除已领取的卡券",
     *     tags={"卡券"},
     *     description="用户删除已领取的卡券",
     *     operationId="DeleteUserCard",
     *     @SWG\Parameter( name="id", in="query", description="指定id,code码和卡券id二选一必填", required=false, type="string"),
     *     @SWG\Parameter( name="code", in="query", description="优惠券code码", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="string", example="true", description="删除状态"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function DeleteUserCard(Request $request)
    {
        if (!$request->input('id') && !$request->input('code')) {
            throw new ResourceException('核销卡券失败,code码 和 卡券id 二选一必填.');
        }
        $user = $request->get('auth');
        if (!$user['user_id'] || !$user['mobile']) {
            throw new ResourceException('删除卡券失败，信息有误');
        }
        $input = $request->input();
        $id = 0;
        $code = null;
        if (isset($input['id']) && $input['id']) {
            $id = $input['id'];
        }
        if (isset($input['code']) && $input['code']) {
            $code = $input['code'];
        }

        $userDiscountService = new UserDiscountService();
        $companyId = $user['company_id'];
        $userId = $user['user_id'];
        $result = $userDiscountService->userDelCard($companyId, $userId, $id, $code);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/user/getCardList",
     *     summary="获取用户已领取的优惠券列表",
     *     tags={"卡券"},
     *     description="获取用户已领取的优惠券列表",
     *     operationId="getUserCardList",
     *     @SWG\Parameter(
     *         name="amount", in="query", description="使用优惠券之前的订单金额", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="code", in="query", description="优惠券的 code 码", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="use_scenes", in="query", description="可被核销的方式", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="card_id", in="query", description="指定优惠券card_id", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="shop_id", in="query", description="微信门店 id 号", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page", in="query", description="page", required=false, type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="page_size", in="query", description="limit", required=false, type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="use_platform", in="query", description="优惠券适用平台（mall:线上商城专用, store:门店专用）", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="status", in="query", description="用户领取的优惠券使用状态{1:未使用,2:已核销,3:已转赠,5:已过期,6:作废}", required=false, type="string"
     *     ),
     *     @SWG\Response(
     *          response=200,
     *          description="成功返回结构",
     *          @SWG\Schema(
     *              @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/UserDiscount"
     *                       ),
     *                  ),
     *                  @SWG\Property( property="count", type="string", example="1", description="总条数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getUserCardList(Request $request)
    {
        $shopId = 0;
        $count = 0;
        $page = $request->input('page_no', 1);
        $limit = $request->input('page_size', 20);

        if ($request->input('shop_id')) {
            $shopId = $request->input('shop_id');
        }
        //实际金额大于配置金额 least_cost
        if ($request->input('amount')) {
            $filter['least_cost|lte'] = $request->input('amount');
            //结束时间大于当前时间,开始时间小于等于当前时间
            //此处的时间是东八区
            $filter['begin_date|lte'] = time() ;
            $filter['card_type|notIn'] = ['gift'];
        }
        $filter['end_date|gt'] = time() ;

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

        $user = $request->get('auth');

        if (!$user['user_id'] || !$user['mobile']) {
            throw new ResourceException('获取优惠券失败，信息有误');
        }

        if ($request->input('status')) {
            $filter['status'] = $request->input('status');
        }

        //未使用的优惠券
        $filter['company_id'] = $user['company_id'];
        $filter['user_id'] = $user['user_id'];

        $userDiscountService = new UserDiscountService();
        $cardLists = $userDiscountService->getUserDiscountList($filter, $page, $limit);
        $cardDataLists = [];
        if ($cardLists['list']) {
            foreach ($cardLists['list'] as $key => $info) {
                $shops = $info['rel_shops_ids'];
                if (is_array($shops) && $shopId && !in_array($shopId, $shops)) {
                    continue;
                }
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

            $poiFilter['company_id'] = $user['company_id'];
            $poiFilter['expired_at|gt'] = time();
            $shopsService = new ShopsService(new WxShopsService());
            $poiList = $shopsService->getShopsList($poiFilter, 1, 50);
            $storeList = [];
            foreach ($poiList['list'] as $shop) {
                $storeList[$shop['wxShopId']] = $shop['storeName'];
            }

            foreach ($cardDataLists as &$newInfo) {
                $shopData = [];
                if (is_array($newInfo['rel_shops_ids'])) {
                    foreach ($newInfo['rel_shops_ids'] as $shop_id) {
                        if (isset($storeList[$shop_id])) {
                            $shopData[] = $storeList[$shop_id];
                        }
                    }
                }
                $newInfo['storeList'] = $shopData;
                if ($shopData && count($shopData) != $poiList['total_count']) {
                    $newInfo['ifall'] = false;
                } else {
                    $newInfo['ifall'] = true;
                }
                $count++;
            }
        }
        $result['list'] = $cardDataLists;
        $result['count'] = $count;
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/user/getCardDetail",
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
    public function getUserDiscountDetail(Request $request)
    {
        if ($request->input('code')) {
            $filter['code'] = $request->input('code');
        }

        if ($request->input('card_id')) {
            $filter['card_id'] = $request->input('card_id');
        }

        $user = $request->get('auth');
        if (!$user['user_id'] || !$user['mobile']) {
            throw new ResourceException('获取优惠券失败，信息有误');
        }
        $filter['company_id'] = $user['company_id'];
        $filter['user_id'] = $user['user_id'];
        if (isset($user['wxapp_appid'])) {
            $filter['wxapp_appid'] = $user['wxapp_appid'];
        }

        $userDiscountService = new UserDiscountService();
        $cardData = $userDiscountService->getUserCardInfo($filter, true);
        return $this->response->array($cardData);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/user/usedCard",
     *     summary="用户使用优惠券",
     *     tags={"卡券"},
     *     description="用户使用优惠券",
     *     operationId="userUsedCard",
     *     @SWG\Parameter(
     *         name="code", in="query", description="优惠券的 code 码", required=true, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="shop_id", in="query", description="核销门店ID", required=true, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="verify_code", in="query", description="自助核销验证码", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="remark_amount", in="query", description="自助核销备注金额", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="consume_outer_str", in="query", description="核销场景", required=false, type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                      property="status", type="object",
     *                     @SWG\Property(property="status", type="string", example="true", description="结果状态"),
     *                  ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function userUsedCard(Request $request)
    {
        if (!$request->input('code') || !$request->input('shop_id')) {
            throw new ResourceException('核销卡券失败,提交卡券信息有误.');
        }

        $code = $request->input('code');
        $params['shop_id'] = $request->input('shop_id');
        $params['consume_outer_str'] = '用户自助核销';

        if ($request->input('consume_outer_str')) {
            $params['consume_outer_str'] = $request->input('consume_outer_str');
        }
        if ($request->input('verify_code')) {
            $params['verify_code'] = $request->input('verify_code');
        }
        if ($request->input('remark_amount')) {
            $params['remark_amount'] = $request->input('remark_amount');
        }

        $user = $request->get('auth');
        if (!$user['user_id']) {
            throw new ResourceException('使用失败，信息有误');
        }
        $params['user_id'] = $user['user_id'];
        $companyId = $user['company_id'];

        $userDiscountService = new UserDiscountService();
        $cardData = $userDiscountService->userConsumeCard($companyId, $code, $params);
        return $this->response->array($cardData);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/user/receiveCardPackage",
     *     summary="领取卡券包",
     *     tags={"卡券包"},
     *     description="模版挂件通过卡券包ID领取卡券包",
     *     operationId="receivesPackage",
     *     @SWG\Parameter( name="package_id", in="query", description="卡券包ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="bool", example="true", description="领取结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function receivesPackage(Request $request)
    {
        $user = $request->get('auth');
        $packageId = $request->input('package_id');
        $rules = [
            'package_id' => ['required|integer|min:1', ' 卡券包ID必填'],
        ];
        $errorMessage = validator_params(['package_id' => $packageId], $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $salespersonId = $inputData['sales_person_id'] ?? 0;

        $result = (new PackageReceivesService())->receivesPackage($user['company_id'], $packageId, $user['user_id'], 'template', $salespersonId);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/user/currentGardCardPackage",
     *     summary="当前用户获取等级卡券包",
     *     tags={"卡券包"},
     *     description="当前用户获取等级卡券包",
     *     operationId="currentGardCardPackage",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="type", type="string", example="vip_grade,grade", description="当前用户类型"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function currentGardCardPackage(Request $request)
    {
        $user = $request->get('auth');

        $companyId = $user['company_id'];
        $userId = $user['user_id'];

        $type = (new PackageReceivesService())->currentGardCardPackage($companyId, $userId);

        return $this->response->array(['type' => $type]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/user/showCardPackage",
     *     summary="未读已领卡券列表",
     *     tags={"卡券包"},
     *     description="获取未读已领卡券列表",
     *     operationId="showCardPackage",
     *     @SWG\Parameter( name="receive_type", in="query", description="未读场景:template 模版领取,vip_grade 会员购买升级, grade 等级升级", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="receive_record_list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="receive_id", type="string", example="1", description="卡券包领取记录ID"),
     *                          @SWG\Property( property="package_id", type="string", example="1", description="卡券包ID"),
     *                          @SWG\Property( property="receive_type", type="string", example="template", description="领取场景:template 模版领取,vip_grade 会员购买升级, grade 等级升级"),
     *                          @SWG\Property( property="receive_status", type="string", example="in_progress", description="卡券包领取状态：in_progress 正在领取中,success 领取成功"),
     *                          @SWG\Property( property="front_show", type="string", example="0", description="是否已读 1/0 已读/未读"),
     *                          @SWG\Property( property="receive_time", type="string", example="2021-09-29 14:40:53", description="有效期类型"),
     *                          @SWG\Property( property="success_count", type="string", example="", description="此卡券包成功领取卡券数"),
     *                          @SWG\Property( property="receive_record_list", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="takeEffect", type="string", example="领取后当天生效,90天有效", description="有效期文字"),
     *                                          @SWG\Property( property="card_id", type="string", example="825", description="卡券ID"),
     *                                          @SWG\Property( property="card_type", type="string", example="discount", description="卡券类型，discount:折扣券;cash:代金券;gift:兑换券;new_gift:兑换券(新)"),
     *                                          @SWG\Property( property="title", type="string", example="测试优惠券", description="优惠券标题"),
     *                                          @SWG\Property( property="date_type", type="string", example="DATE_TYPE_FIX_TERM", description="有效期类型"),
     *                                          @SWG\Property( property="description", type="string", example="", description="卡券使用说明"),
     *                                          @SWG\Property( property="begin_date", type="string", example="2021-09-29 14:40:53", description="有效期开始时间"),
     *                                          @SWG\Property( property="end_date", type="string", example="2021-09-29 14:40:53", description="有效期结束时间"),
     *                                          @SWG\Property( property="fixed_term", type="string", example="1", description="有效期的有效天数"),
     *                                          @SWG\Property( property="quantity", type="string", example="1", description="总库存数量"),
     *                                          @SWG\Property( property="receive", type="string", example="true", description="是否前台直接领取"),
     *                                          @SWG\Property( property="kq_status", type="string", example="0", description="卡券状态 0:正常 1:暂停 2:关闭"),
     *                                          @SWG\Property( property="grade_ids", type="string", example="", description="等级限制"),
     *                                          @SWG\Property( property="vip_grade_ids", type="string", example="", description="vip等级限制"),
     *                                          @SWG\Property( property="get_limit", type="string", example="1", description="卡券已发放数量"),
     *                                          @SWG\Property( property="gift", type="string", example="", description="兑换券兑换内容名称"),
     *                                          @SWG\Property( property="default_detail", type="string", example="", description="优惠券优惠详情"),
     *                                          @SWG\Property( property="discount", type="string", example="90", description="折扣券打折额度（百分比)"),
     *                                          @SWG\Property( property="least_cost", type="string", example="", description="代金券起用金额"),
     *                                          @SWG\Property( property="reduce_cost", type="string", example="", description="代金券减免金额 or 兑换券起用金额"),
     *                                          @SWG\Property( property="receive_status", type="string", example="in_progress", description="卡券领取状态：in_progress 正在领取中,success 领取成功"),
     *                                          @SWG\Property( property="get_num", type="string", example="", description="卡券已发放数量"),
     *                                          @SWG\Property( property="lock_time", type="string", example="", description="兑换商品后的锁定时间"),
     *                                          @SWG\Property( property="deal_detail", type="string", example="", description="团购券详情"),
     *                                          @SWG\Property( property="accept_category", type="string", example="", description="指定可用的商品类目,代金券专用"),
     *                                          @SWG\Property( property="reject_category", type="string", example="", description="指定不可用的商品类目,代金券专用"),
     *                                          @SWG\Property( property="object_use_for", type="string", example="", description="购买xx可用类型门槛，仅用于兑换"),
     *                                          @SWG\Property( property="can_use_with_other_discount", type="string", example="", description="是否可与其他优惠共享"),
     *                                          @SWG\Property( property="use_platform", type="string", example="", description="优惠券适用平台（线上商城专用 or 门店专用）"),
     *                                          @SWG\Property( property="use_bound", type="string", example="", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                                          @SWG\Property( property="send_begin_time", type="string", example="", description="发放开始时间"),
     *                                          @SWG\Property( property="send_end_time", type="string", example="", description="发放结束时间"),
     *                                       ),
     *                        ),
     *                     ),
     *              ),
     *              @SWG\Property( property="all_card_list", type="array",
     *                          @SWG\Items( type="object",
     *                              @SWG\Property( property="takeEffect", type="string", example="领取后当天生效,90天有效", description="有效期文字"),
     *                              @SWG\Property( property="card_id", type="string", example="825", description="卡券ID"),
     *                              @SWG\Property( property="card_type", type="string", example="discount", description="卡券类型，discount:折扣券;cash:代金券;gift:兑换券;new_gift:兑换券(新)"),
     *                              @SWG\Property( property="title", type="string", example="测试优惠券", description="优惠券标题"),
     *                              @SWG\Property( property="date_type", type="string", example="DATE_TYPE_FIX_TERM", description="有效期类型"),
     *                              @SWG\Property( property="description", type="string", example="", description="卡券使用说明"),
     *                              @SWG\Property( property="begin_date", type="string", example="2021-09-29 14:40:53", description="有效期开始时间"),
     *                              @SWG\Property( property="end_date", type="string", example="2021-09-29 14:40:53", description="有效期结束时间"),
     *                              @SWG\Property( property="fixed_term", type="string", example="1", description="有效期的有效天数"),
     *                              @SWG\Property( property="quantity", type="string", example="1", description="总库存数量"),
     *                              @SWG\Property( property="receive", type="string", example="true", description="是否前台直接领取"),
     *                              @SWG\Property( property="kq_status", type="string", example="0", description="卡券状态 0:正常 1:暂停 2:关闭"),
     *                              @SWG\Property( property="grade_ids", type="string", example="", description="等级限制"),
     *                              @SWG\Property( property="vip_grade_ids", type="string", example="", description="vip等级限制"),
     *                              @SWG\Property( property="get_limit", type="string", example="1", description="卡券已发放数量"),
     *                              @SWG\Property( property="gift", type="string", example="", description="兑换券兑换内容名称"),
     *                              @SWG\Property( property="default_detail", type="string", example="", description="优惠券优惠详情"),
     *                              @SWG\Property( property="discount", type="string", example="90", description="折扣券打折额度（百分比)"),
     *                              @SWG\Property( property="least_cost", type="string", example="", description="代金券起用金额"),
     *                              @SWG\Property( property="reduce_cost", type="string", example="", description="代金券减免金额 or 兑换券起用金额"),
     *                              @SWG\Property( property="receive_status", type="string", example="in_progress", description="卡券领取状态：in_progress 正在领取中,success 领取成功"),
     *                              @SWG\Property( property="get_num", type="string", example="", description="卡券已发放数量"),
     *                              @SWG\Property( property="lock_time", type="string", example="", description="兑换商品后的锁定时间"),
     *                              @SWG\Property( property="deal_detail", type="string", example="", description="团购券详情"),
     *                              @SWG\Property( property="accept_category", type="string", example="", description="指定可用的商品类目,代金券专用"),
     *                              @SWG\Property( property="reject_category", type="string", example="", description="指定不可用的商品类目,代金券专用"),
     *                              @SWG\Property( property="object_use_for", type="string", example="", description="购买xx可用类型门槛，仅用于兑换"),
     *                              @SWG\Property( property="can_use_with_other_discount", type="string", example="", description="是否可与其他优惠共享"),
     *                              @SWG\Property( property="use_platform", type="string", example="", description="优惠券适用平台（线上商城专用 or 门店专用）"),
     *                              @SWG\Property( property="use_bound", type="string", example="", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                              @SWG\Property( property="send_begin_time", type="string", example="", description="发放开始时间"),
     *                              @SWG\Property( property="send_end_time", type="string", example="", description="发放结束时间"),
     *                           ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function showCardPackage(Request $request)
    {
        $user = $request->get('auth');
        $inputData = $request->all();
        $rules = [
            'receive_type' => ['required|in:template,grade,vip_grade', '显示类型必传'],
        ];
        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $result = (new PackageReceivesService())->showCardPackage($user['company_id'], $user['user_id'], $inputData['receive_type']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/user/getBindCardList",
     *     summary="得到的卡券包中卡券信息",
     *     tags={"卡券包"},
     *     description="得到的卡券包中卡券信息",
     *     operationId="showCardPackage",
     *     @SWG\Parameter( name="type", in="query", description="场景:vip_grade 会员购买升级, grade 等级升级", required=true, type="string"),
     *     @SWG\Parameter( name="grade_id", in="query", description="等级ID,从 /wxapp/member 接口获取 付费会员取:vip_grade_id 普通会员取 nextGradeInfo.grade_id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="10", description="内含卡券总数量"),
     *                  @SWG\Property( property="list", type="array",
     *                         @SWG\Items( type="object",
     *                             @SWG\Property( property="give_num", type="string", example="2", description="发送数量"),
     *                             @SWG\Property( property="takeEffect", type="string", example="领取后当天生效,90天有效", description="有效期文字"),
     *                             @SWG\Property( property="card_id", type="string", example="825", description="卡券ID"),
     *                             @SWG\Property( property="card_type", type="string", example="discount", description="卡券类型，discount:折扣券;cash:代金券;gift:兑换券;new_gift:兑换券(新)"),
     *                             @SWG\Property( property="title", type="string", example="测试优惠券", description="优惠券标题"),
     *                             @SWG\Property( property="date_type", type="string", example="DATE_TYPE_FIX_TERM", description="有效期类型"),
     *                             @SWG\Property( property="description", type="string", example="", description="卡券使用说明"),
     *                             @SWG\Property( property="begin_date", type="string", example="2021-09-29 14:40:53", description="有效期开始时间"),
     *                             @SWG\Property( property="end_date", type="string", example="2021-09-29 14:40:53", description="有效期结束时间"),
     *                             @SWG\Property( property="fixed_term", type="string", example="1", description="有效期的有效天数"),
     *                             @SWG\Property( property="quantity", type="string", example="1", description="总库存数量"),
     *                             @SWG\Property( property="receive", type="string", example="true", description="是否前台直接领取"),
     *                             @SWG\Property( property="kq_status", type="string", example="0", description="卡券状态 0:正常 1:暂停 2:关闭"),
     *                             @SWG\Property( property="grade_ids", type="string", example="", description="等级限制"),
     *                             @SWG\Property( property="vip_grade_ids", type="string", example="", description="vip等级限制"),
     *                             @SWG\Property( property="get_limit", type="string", example="1", description="卡券已发放数量"),
     *                             @SWG\Property( property="gift", type="string", example="", description="兑换券兑换内容名称"),
     *                             @SWG\Property( property="default_detail", type="string", example="", description="优惠券优惠详情"),
     *                             @SWG\Property( property="discount", type="string", example="90", description="折扣券打折额度（百分比)"),
     *                             @SWG\Property( property="least_cost", type="string", example="", description="代金券起用金额"),
     *                             @SWG\Property( property="reduce_cost", type="string", example="", description="代金券减免金额 or 兑换券起用金额"),
     *                             @SWG\Property( property="get_num", type="string", example="", description="卡券已发放数量"),
     *                             @SWG\Property( property="lock_time", type="string", example="", description="兑换商品后的锁定时间"),
     *                             @SWG\Property( property="deal_detail", type="string", example="", description="团购券详情"),
     *                             @SWG\Property( property="accept_category", type="string", example="", description="指定可用的商品类目,代金券专用"),
     *                             @SWG\Property( property="reject_category", type="string", example="", description="指定不可用的商品类目,代金券专用"),
     *                             @SWG\Property( property="object_use_for", type="string", example="", description="购买xx可用类型门槛，仅用于兑换"),
     *                             @SWG\Property( property="can_use_with_other_discount", type="string", example="", description="是否可与其他优惠共享"),
     *                             @SWG\Property( property="use_platform", type="string", example="", description="优惠券适用平台（线上商城专用 or 门店专用）"),
     *                             @SWG\Property( property="use_bound", type="string", example="", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                             @SWG\Property( property="send_begin_time", type="string", example="", description="发放开始时间"),
     *                             @SWG\Property( property="send_end_time", type="string", example="", description="发放结束时间"),
     *                         ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function getCardListByBindType(Request $request)
    {
        $user = $request->get('auth');
        $inputData = $request->all();
        $rules = [
            'grade_id' => ['required|integer|min:1', '等级设置ID必传'],
            'type' => ['required|in:vip_grade,grade', '类型必传'],
        ];
        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $list = (new PackageQueryService())->getCardListByBindType($user['company_id'], $inputData['grade_id'], $inputData['type']);
        $totalCount = array_sum(array_column($list, 'give_num'));

        $result = [
            'list' => $list,
            'total_count' => $totalCount
        ];

        return $this->response->array($result);
    }
}
