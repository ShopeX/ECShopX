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
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\DiscountCardService;
use KaquanBundle\Services\UserDiscountService;

class DiscountCard extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/getCardList",
     *     summary="获取卡券列表",
     *     tags={"卡券"},
     *     description="获取卡券列表信息",
     *     operationId="getDiscountCardList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="card_type", in="query", description="卡券类型 discount:折扣券;cash:代金券;new_gift:兑换券", required=false, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="end_date", in="query", description="结束时间", required=false, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_no", in="query", description="当前页数", required=false, type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="page_size", in="query", description="返回列表的数量，可选，默认 20, 取值在 1 到 20 之间", required=false, type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id", in="query", description="店铺ID", required=false, type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="card_id", in="query", description="卡券id", required=false, type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="总条数"),
     *                 @SWG\Property(
     *                      property="pagers", type="object",
     *                      @SWG\Property(property="total", type="integer", description="总条数"),
     *                  ),
     *                 @SWG\Property(
     *                     property="list", type="array",
     *                     @SWG\Items(
     *                          ref="#/definitions/DiscountCard"
     *                     ),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getDiscountCardList(Request $request)
    {
        $authInfo = $request->get('auth');

        $page = $request->input('page_no', 1);
        $count = $request->input('page_size', 8);
        $distributorId = $request->input('distributor_id');
        $offset = ($page - 1) * $count;
        $filter = [];

        // if ($request->input('use_platform')) {
        //     $filter['use_platform'] = $request->input('use_platform');
        // }
        // if ($request->input('status')) {
        //     $filter['status'] = $request->input('status');
        // }
        if ($request->input('card_type')) {
            $filter['card_type'] = $request->input('card_type');
        } else {
            $filter['card_type'] = ['cash', 'discount', 'new_gift', 'money'];
        }
        // if ($request->input('title')) {
        //     $filter['title|like'] = $request->input('title');
        // }
        if ($request->input('end_date')) {
            $filter['end_date'] = time();
        }
        if ($distributorId != 'undefined' && !is_null($distributorId)) {
            $filter['distributor_id'] = $distributorId;
        }
        if ($distributorId == 'all') {
            unset($filter['distributor_id']);
        }
        $filter['receive'] = '1';
        $filter['company_id'] = $authInfo['company_id'];

        $discountCardService = new KaquanService(new DiscountCardService());

        // 根据itemid查询数据库
        if ($request->input('item_id')) {
            $itemId = $request->input('item_id');
            $filter['item_id'] = $itemId;
            $itemsService = new ItemsService();
            $itemInfo = $itemsService->getItem(['item_id' => $filter['item_id'],'company_id' => $authInfo['company_id']]);
            $filter['default_item_id'][] = $itemInfo['default_item_id'] ?? '';
        } else {
            //$result = $discountCardService->getKaquanList($offset, $count, $filter);
        }

        if ($request->input('card_id')) {
            $cardId = $request->input('card_id');
            $filter['card_id'] = (int)$cardId;
            unset($filter['receive']); // 如果指定ID，则不判断是否只能前台直接领取
        }

        $result = $discountCardService->getKaquanListByItemId($filter, $page, $count, ["created" => "DESC"]);

        //获取用户领取的优惠券
        $userDiscountService = new UserDiscountService();

        if (isset($authInfo['user_id'])) {
            foreach ($result['list'] as &$value) {
                $value['user_get_num'] = $userDiscountService->getUserGetNum($authInfo['user_id'], $value['card_id'], $value['company_id']);
                if ($value['get_limit'] <= $value['user_get_num']) {
                    $value['ifget'] = 1;
                }
                if ($value['end_date'] && $value['end_date'] <= time()) {
                    $value['gameOver'] = 1;
                }

                if ($value['quantity'] <= $value['get_num']) {
                    $value['numNull'] = 1;
                }
            }
        }

        // 追加店铺id
        // TODO 2021-12-16遗留的问题：上文distributor_id是字符串，多个店铺id之间是用逗号隔开的，但这里处理完后只返回了一个店铺id
        (new DiscountCardService())->appendDistributorId((int)$filter["company_id"], $result['list']);

        // 追加优惠券的店铺信息 （即将被废弃）
        // TODO 2021-12-16发版时遗留的问题，之后需要统一成appendDistributorInfo方法返回的值
        (new DistributorService())->appendDistributorList((int)$authInfo['company_id'], $result["list"]);

        // 追加优惠券的店铺信息
        (new DistributorService())->appendDistributorInfo((int)$authInfo['company_id'], $result["list"]);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/getCardDetail/{cardId}",
     *     summary="获取卡券详情",
     *     tags={"卡券"},
     *     description="获取卡券详情",
     *     operationId="getDiscountCardDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="card_id", in="path", description="卡券id", required=false, type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                      ref="#/definitions/DiscountCard"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getDiscountCardDetail($cardId, Request $request)
    {
        $authInfo = $request->get('auth');

        $filter['company_id'] = $authInfo['company_id'];
        $filter['card_id'] = $cardId;
        $discountCardService = new KaquanService(new DiscountCardService());
        $result = $discountCardService->getKaquanDetail($filter);
        return $this->response->array($result);
    }
}
