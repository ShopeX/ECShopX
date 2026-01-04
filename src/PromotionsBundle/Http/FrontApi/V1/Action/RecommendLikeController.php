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

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\RecommendLikeService;
use MembersBundle\Services\MemberItemsFavService;

class RecommendLikeController extends Controller
{
    public function __construct()
    {
        $this->service = new RecommendLikeService();
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotions/recommendlike",
     *     summary="获取猜你喜欢商品列表",
     *     tags={"营销"},
     *     description="获取猜你喜欢商品列表",
     *     operationId="updateRecommendLike",
     *     @SWG\Parameter( name="page", in="query", description="页数,默认：1", required=false, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数,默认：40", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="总数", example="23"),
     *                 @SWG\Property(property="list", type="array",
     *                     @SWG\Items(
     *                         ref="#/definitions/GoodsList"
     *                     ),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */

    public function getRecommendLikeLists(Request $request)
    {
        // ShopEx EcShopX Core Module
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'] ?? 0;
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 6);
        $distributor_id = $request->get('distributor_id', 0);
        if (!empty($distributor_id)) {
            $filter['distributor_id'] = $distributor_id;
        }
        $filter['is_can_sale'] = 1;
        $orderBy = ['sort' => 'desc', 'item_id' => 'asc'];
        $result = $this->service->getListData($filter, $page, $pageSize, $orderBy);

        return $this->response->array($result);
    }
}
