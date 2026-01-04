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

namespace GoodsBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use SystemLinkBundle\Jobs\GetItemsFromOme;
use SystemLinkBundle\Jobs\GetBrandFromOme;
use SystemLinkBundle\Jobs\GetItemsSpecFromOme;
use SystemLinkBundle\Jobs\GetItemsCategoryFromOme;

class SyncFromOme extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/goods/sync/items",
     *     summary="从oms同步商品数据",
     *     tags={"商品"},
     *     description="从oms同步商品数据",
     *     operationId="syncItemCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function syncItems(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $gotoJob = (new GetItemsFromOme($companyId, 1, time()))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * @SWG\Post(
     *     path="/goods/sync/itemCategory",
     *     summary="从oms同步商品分类",
     *     tags={"商品"},
     *     description="从oms同步商品分类",
     *     operationId="syncItemCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function syncItemCategory(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $gotoJob = (new GetItemsCategoryFromOme($companyId))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * @SWG\Post(
     *     path="/goods/sync/itemSpec",
     *     summary="从oms同步商品规格",
     *     tags={"商品"},
     *     description="从oms同步商品规格",
     *     operationId="syncItemSpec",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function syncItemSpec(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $gotoJob = (new GetItemsSpecFromOme($companyId, 1, time()))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * @SWG\Post(
     *     path="/goods/sync/brand",
     *     summary="从oms同步品牌",
     *     tags={"商品"},
     *     description="从oms同步品牌",
     *     operationId="syncBrand",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function syncBrand(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $gotoJob = (new GetBrandFromOme($companyId, 1, time()))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        $result['status'] = true;
        return response()->json($result);
    }
}
