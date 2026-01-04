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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Items;

use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;

use GoodsBundle\Services\ItemsAttributesService;
use OpenapiBundle\Services\Items\ItemsService as OpenapiItemsService;

class ItemsAttributes extends Controller
{
    /**
     * @SWG\Post(
     *     path="/ecx.item.brand.add",
     *     summary="新增品牌",
     *     tags={"商品"},
     *     description="新增商品品牌",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.brand.add" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="brand_name", description="品牌名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="image_url", description="品牌图片url" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example=true),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function createItemBrand(Request $request)
    {
        $params = $request->all('brand_name', 'image_url');
        $rules = [
            'brand_name' => ['required', '品牌名称必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $companyId = $request->get('auth')['company_id'];

        try {
            $itemsAttributesService = new ItemsAttributesService();
            $data = [
                'company_id' => $companyId,
                'attribute_type' => 'brand',
                'attribute_name' => $params['brand_name'],
                'image_url' => $params['image_url'],
            ];
            $itemsAttributesService->createAttr($data);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::GOODS_BRAND_ERROR, $e->getMessage());
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/ecx.item.brand.delete",
     *     summary="删除品牌",
     *     tags={"商品"},
     *     description="根据品牌ID,删除商品品牌",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.brand.delete" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="brand_id", description="品牌ID" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example=true),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function deleteItemBrand(Request $request)
    {
        $params = $request->all('brand_id');

        $rules = [
            'brand_id' => ['required', '品牌ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];

        try {
            $itemsAttributesService = new ItemsAttributesService();
            $filter = [
                'company_id' => $companyId,
                'attribute_id' => $params['brand_id'],
            ];
            $itemsAttributesService->deleteAttr($filter);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::GOODS_BRAND_DELETE_ERROR, $e->getMessage());
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.item.brand.update",
     *     summary="更新品牌",
     *     tags={"商品"},
     *     description="更新商品品牌",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.brand.update" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="brand_id", description="品牌ID" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="brand_name", description="品牌名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="image_url", description="品牌图片url" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example=true),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function updateItemBrand(Request $request)
    {
        $params = $request->all('brand_id', 'brand_name', 'image_url');

        $rules = [
            'brand_id' => ['required', '品牌ID必填'],
            'brand_name' => ['required', '品牌名称必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];

        try {
            $itemsAttributesService = new ItemsAttributesService();
            $filter = ['company_id' => $companyId, 'attribute_id' => $params['brand_id']];
            $data = [
                'attribute_name' => $params['brand_name'],
                'image_url' => $params['image_url'],
            ];
            $itemsAttributesService->updateAttr($filter, $data);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::GOODS_BRAND_ERROR, $e->getMessage());
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.item.brand.get",
     *     summary="查询商品品牌",
     *     tags={"商品"},
     *     description="查询商品品牌列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.brand.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数（不填默认1）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页显示数量（不填默认20条）" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *               @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *               @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *               @SWG\Property( property="pager", type="object",
     *                  ref="#definitions/Pager",
     *               ),
     *               @SWG\Property( property="list", type="array",
     *                   @SWG\Items( type="object",
     *                       @SWG\Property( property="brand_id", type="string", example="1503", description="品牌ID"),
     *                       @SWG\Property( property="brand_name", type="string", example="品牌名称", description="品牌名称"),
     *                       @SWG\Property( property="image_url", type="string", example="https://bbctest.aixue7.com/image/1/2021/04/26/e6fb8621e293129d059ecb4af079e70dFMWhNm1M60uQZfa9qoC2OHy8oajuTyPx", description="品牌图片"),
     *                       @SWG\Property( property="created", type="string", example="2021-04-26 14:48:37", description="创建时间"),
     *                       @SWG\Property( property="updated", type="string", example="2021-04-26 14:48:37", description="更新时间"),
     *                   ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getItemBrandList(Request $request)
    {
        $params = $request->all('page', 'page_size');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $filter = [
            'company_id' => $companyId,
            'attribute_type' => 'brand',
        ];
        $itemsAttributesService = new ItemsAttributesService();
        $result = $itemsAttributesService->getAttrList($filter, $params['page'], $params['page_size'], ['created' => 'desc']);
        $openapiItemsService = new OpenapiItemsService();
        $return = $openapiItemsService->formateItemBrandList($result, (int)$params['page'], (int)$params['page_size']);

        return $this->response->array($return);
    }
}
