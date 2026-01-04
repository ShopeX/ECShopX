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

namespace PromotionsBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\RecommendLikeService;
use GoodsBundle\Services\ItemsService;

class RecommendLikeController extends Controller
{
    public function __construct()
    {
        $this->service = new RecommendLikeService();
    }

    /**
      * @SWG\Post(
      *     path="/promotions/recommendlike",
      *     summary="添加猜你喜欢商品",
      *     tags={"营销"},
      *     description="添加猜你喜欢商品",
      *     operationId="createRecommendLike",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="items[0][item_id]", in="formData", description="商品id", type="string"),
      *     @SWG\Parameter( name="items[0][sort]", in="formData", description="商品排序", required=true, type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                  @SWG\Property( property="items", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property(property="item_id", type="string", example="1", description="商品id"),
     *                          @SWG\Property(property="item_name", type="string", example="商品名称", description="商品名称"),
     *                          @SWG\Property(property="sort", type="string", example="1", description="商品排序"),
     *                      ),
     *                  ),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function createRecommendLike(Request $request)
    {
        // $params['distributor_id'] = $request->get('distributor_id', 0);
        $params['company_id'] = app('auth')->user()->get('company_id');
        $items = $request->get('items');
        $rules = [
            'company_id' => ['required', trans('PromotionsBundle.company_id_required')],
            // 'distributor_id'    => ['required', '店铺ID必填'],
            'items.*.item_id' => ['required', trans('PromotionsBundle.product_id_required')],
            'items.*.sort' => ['required', trans('PromotionsBundle.product_sort_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if (!$items) {
            throw new ResourceException(trans('PromotionsBundle.please_select_products'));
        }

        $totalCount = $this->service->count(['company_id' => $params['company_id'], 'item_id|notin' => array_column($items, 'item_id')]);
        if ($totalCount + count($items) > 30) {
            throw new ResourceException(trans('PromotionsBundle.exceed_30_products_limit'));
        }

        $params['items'] = $items;
        $result = $this->service->saveRecommendLikeData($params['company_id'], $params);
        return $this->response->array($result);
    }

    /**
      * @SWG\Put(
      *     path="/promotions/recommendlike",
      *     summary="编辑猜你喜欢商品排序",
      *     tags={"营销"},
      *     description="编辑猜你喜欢商品排序",
      *     operationId="updateRecommendLike",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="id", in="formData", description="商品id", type="string"),
      *     @SWG\Parameter( name="item_id", in="formData", description="商品id", type="string"),
      *     @SWG\Parameter( name="sort", in="formData", description="排序", required=true, type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(property="company_id", type="integer", example="1", description="企业id"),
      *                 @SWG\Property(property="id", type="integer", example="1", description="id"),
      *                 @SWG\Property(property="item_id", type="integer", example="1", description="商品id"),
      *                 @SWG\Property(property="sort", type="integer", example="1", description="排序"),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function updateRecommendLike(Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $request->get('id');
        $params['sort'] = $request->get('sort');
        $params['item_id'] = $request->get('item_id');
        $result = $this->service->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
      * @SWG\Delete(
      *     path="/promotions/recommendlike/{id}",
      *     summary="删除猜你喜欢商品",
      *     tags={"营销"},
      *     description="删除猜你喜欢商品",
      *     operationId="delRecommendLike",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="id", in="path", description="id, 如果传入all，则全部删除", type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */

    public function delRecommendLike($id, Request $request)
    {
        if ($id == 'all') {
            $filter['company_id'] = app('auth')->user()->get('company_id');
            $result = $this->service->deleteBy($filter);
        } else {
            $result = $this->service->deleteById($id);
        }
        return $this->response->array(['status' => $result]);
    }

    /**
      * @SWG\Get(
      *     path="/promotions/recommendlikes",
      *     summary="获取猜你喜欢商品",
      *     tags={"营销"},
      *     description="获取猜你喜欢商品",
      *     operationId="getRecommendLikeItems",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="is_all", in="query", description="是否需要完整的商品数据,默认false", type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(
      *                     property="item_ids",
      *                     type="array",
      *                     @SWG\Items(
      *                         @SWG\Items( type="string", example="4956", description="商品id"),
      *                     ),
      *                 )
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function getRecommendLikeItems(Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $lists = $this->service->lists($filter, 1, -1);
        $itemIds = array_column($lists['list'], 'item_id');
        $isAll = $request->get('is_all', false);
        if (!$isAll || $isAll === 'false') {
            return $this->response->array(['item_ids' => $itemIds]);
        }
        $itemsService = new ItemsService();
        $params = [
            'company_id' => $filter['company_id'],
            'item_id' => $itemIds,
        ];
        $result = $itemsService->getItemsList($params, 1, -1);
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/promotions/recommendlike",
      *     summary="获取猜你喜欢商品列表",
      *     tags={"营销"},
      *     description="获取猜你喜欢商品列表",
      *     operationId="getRecommendLikeLists",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="page", in="query", description="页数，默认1", required=true, type="string"),
      *     @SWG\Parameter( name="pageSize", in="query", description="每页条数，默认20", required=true, type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 ref="#/definitions/GoodsBase"
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */

    public function getRecommendLikeLists(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $result = $this->service->getListData($filter, $page, $pageSize);
        return $this->response->array($result);
    }
}
