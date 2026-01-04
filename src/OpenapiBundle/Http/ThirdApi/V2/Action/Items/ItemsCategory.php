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

use GoodsBundle\Services\ItemsCategoryService;
use OpenapiBundle\Services\Items\ItemsService as OpenapiItemsService;

class ItemsCategory extends Controller
{
    /**
     * @SWG\Post(
     *     path="/ecx.item.category.add",
     *     summary="新增分类",
     *     tags={"商品"},
     *     description="新增商品分类，最多到三级分类",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.category.add" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="category_name", description="分类名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="sort", description="排序 默认:0" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="image_url", description="分类图片url" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="parent_id", description="上级分类ID 顶级为0" ),
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
    public function createItemCategory(Request $request)
    {
        $params = $request->all('category_name', 'sort', 'image_url', 'parent_id');
        $rules = [
            'category_name' => ['required', '分类名称必填'],
            'parent_id' => ['required', '上级分类ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $companyId = $request->get('auth')['company_id'];

        try {
            $itemsCategoryService = new ItemsCategoryService();
            $distributorId = 0;
            if (intval($params['parent_id']) == 0) {
                unset($params['parent_id']);
            }
            $result = $itemsCategoryService->createClassificationService($params, $companyId, $distributorId);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::GOODS_CATEGORY_ERROR, $e->getMessage());
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/ecx.item.category.delete",
     *     summary="删除分类",
     *     tags={"商品"},
     *     description="根据分类ID,删除商品分类;如果删除的分类和分类的子分类下有商品，则不能被删除。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.category.delete" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="category_id", description="分类ID" ),
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
    public function deleteItemCategory(Request $request)
    {
        $params = $request->all('category_id');

        $rules = [
            'category_id' => ['required', '分类ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];

        try {
            $itemsCategoryService = new ItemsCategoryService();
            $params = [
                'category_id' => $params['category_id'],
                'company_id' => $companyId,
            ];
            $itemsCategoryService->deleteItemsCategory($params);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::GOODS_CATEGORY_DELETE_ERROR, $e->getMessage());
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.item.category.update",
     *     summary="更新分类",
     *     tags={"商品"},
     *     description="根据分类ID,更新分类数据",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.category.update" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="category_id", description="分类ID" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="category_name", description="分类名称" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sort", description="分类排序" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="image_url", description="分类图片url" ),
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
    public function updateItemCategory(Request $request)
    {
        $params = $request->all('category_id', 'category_name', 'sort', 'image_url');

        $rules = [
            'category_id' => ['required', '分类ID必填'],
            'category_name' => ['required', '分类名称必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];

        try {
            $itemsCategoryService = new ItemsCategoryService();
            $data = [
                'category_name' => $params['category_name'],
                'sort' => $params['sort'],
                'image_url' => $params['image_url'],
            ];
            $itemsCategoryService->updateOneBy(['category_id' => $params['category_id'], 'company_id' => $companyId], $data);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::GOODS_CATEGORY_ERROR, $e->getMessage());
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.item.category.get",
     *     summary="获取分类列表",
     *     tags={"商品"},
     *     description="获取所有的分类数据",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.category.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="category_id", type="string", example="1902", description="商品分类ID"),
     *                  @SWG\Property( property="category_name", type="string", example="生活家具", description="分类名称"),
     *                  @SWG\Property( property="category_level", type="string", example="1", description="分类等级"),
     *                  @SWG\Property( property="parent_id", type="string", example="0", description="父分类id,顶级为0"),
     *                  @SWG\Property( property="path", type="string", example="1902", description="分类ID路径"),
     *                  @SWG\Property( property="sort", type="string", example="21", description="排序"),
     *                  @SWG\Property( property="image_url", type="string", example="https://bbctest.aixue7.com/image/1/2021/03/26/e8f7dd2149ea6c3f81e7b73c927d1f095doISawp5tGCXBMvRXsoDiJFPHIQRUOW", description="分类图片链接"),
     *                  @SWG\Property( property="created", type="string", example="2021-03-26 14:53:41", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="2021-03-26 14:53:41", description="修改时间"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="category_id", type="string", example="1903", description="商品分类ID"),
     *                          @SWG\Property( property="category_name", type="string", example="家具用品", description="分类名称"),
     *                          @SWG\Property( property="category_level", type="string", example="2", description="分类等级"),
     *                          @SWG\Property( property="parent_id", type="string", example="1902", description="父分类id,顶级为0"),
     *                          @SWG\Property( property="path", type="string", example="1902,1903", description="分类ID路径"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="image_url", type="string", example="https://bbctest.aixue7.com/image/1/2021/03/26/85209cb1f0ab3dadff4bba9c3b26fd83SShpC9QMiKGeFa7Zy9kVFriBuwQlNonS", description="分类图片链接"),
     *                          @SWG\Property( property="created", type="string", example="2021-03-26 15:00:58", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="2021-03-26 15:00:58", description="修改时间"),
     *                          @SWG\Property( property="children", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="category_id", type="string", example="1906", description="商品分类ID"),
     *                                  @SWG\Property( property="category_name", type="string", example="电器用品", description="分类名称"),
     *                                  @SWG\Property( property="category_level", type="string", example="3", description="分类等级"),
     *                                  @SWG\Property( property="parent_id", type="string", example="1903", description="父分类id,顶级为0"),
     *                                  @SWG\Property( property="path", type="string", example="1902,1903,1906", description="分类ID路径"),
     *                                  @SWG\Property( property="sort", type="string", example="2", description="排序"),
     *                                  @SWG\Property( property="image_url", type="string", example="", description="分类图片链接"),
     *                                  @SWG\Property( property="created", type="string", example="2021-03-26 15:02:35", description="创建时间"),
     *                                  @SWG\Property( property="updated", type="string", example="2021-03-26 15:02:35", description="修改时间"),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getItemCategoryList(Request $request)
    {
        // 0x456353686f7058
        $itemsCategoryService = new ItemsCategoryService();
        $companyId = $request->get('auth')['company_id'];
        $filter = [
            'is_main_category' => false,
            'company_id' => $companyId,
        ];
        $result = $itemsCategoryService->getItemsCategory($filter, true);
        $openapiItemsService = new OpenapiItemsService();
        $return = $openapiItemsService->formateCategoryList($result);

        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.item.maincategory.get",
     *     summary="查询商品主类目",
     *     tags={"商品"},
     *     description="查询所有的商品主类目数据",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.maincategory.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="category_id", type="string", example="1902", description="商品主类目ID"),
     *                  @SWG\Property( property="category_name", type="string", example="生活家具", description="主类目名称"),
     *                  @SWG\Property( property="category_level", type="string", example="1", description="主类目等级"),
     *                  @SWG\Property( property="parent_id", type="string", example="0", description="父主类目id,顶级为0"),
     *                  @SWG\Property( property="path", type="string", example="1902", description="主类目ID路径"),
     *                  @SWG\Property( property="sort", type="string", example="21", description="排序"),
     *                  @SWG\Property( property="image_url", type="string", example="https://bbctest.aixue7.com/image/1/2021/03/26/e8f7dd2149ea6c3f81e7b73c927d1f095doISawp5tGCXBMvRXsoDiJFPHIQRUOW", description="主类目图片链接"),
     *                  @SWG\Property( property="created", type="string", example="2021-03-26 14:53:41", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="2021-03-26 14:53:41", description="修改时间"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="category_id", type="string", example="1903", description="商品主类目ID"),
     *                          @SWG\Property( property="category_name", type="string", example="家具用品", description="主类目名称"),
     *                          @SWG\Property( property="category_level", type="string", example="2", description="主类目等级"),
     *                          @SWG\Property( property="parent_id", type="string", example="1902", description="父主类目id,顶级为0"),
     *                          @SWG\Property( property="path", type="string", example="1902,1903", description="主类目ID路径"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="image_url", type="string", example="https://bbctest.aixue7.com/image/1/2021/03/26/85209cb1f0ab3dadff4bba9c3b26fd83SShpC9QMiKGeFa7Zy9kVFriBuwQlNonS", description="主类目图片链接"),
     *                          @SWG\Property( property="created", type="string", example="2021-03-26 15:00:58", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="2021-03-26 15:00:58", description="修改时间"),
     *                          @SWG\Property( property="children", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="category_id", type="string", example="1906", description="商品主类目ID"),
     *                                  @SWG\Property( property="category_name", type="string", example="电器用品", description="主类目名称"),
     *                                  @SWG\Property( property="category_level", type="string", example="3", description="主类目等级"),
     *                                  @SWG\Property( property="parent_id", type="string", example="1903", description="父主类目id,顶级为0"),
     *                                  @SWG\Property( property="path", type="string", example="1902,1903,1906", description="主类目ID路径"),
     *                                  @SWG\Property( property="sort", type="string", example="2", description="排序"),
     *                                  @SWG\Property( property="image_url", type="string", example="", description="主类目图片链接"),
     *                                  @SWG\Property( property="created", type="string", example="2021-03-26 15:02:35", description="创建时间"),
     *                                  @SWG\Property( property="updated", type="string", example="2021-03-26 15:02:35", description="修改时间"),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getItemMainCategoryList(Request $request)
    {
        // 0x456353686f7058
        $itemsCategoryService = new ItemsCategoryService();
        $companyId = $request->get('auth')['company_id'];
        $filter = [
            'is_main_category' => true,
            'company_id' => $companyId,
        ];
        $result = $itemsCategoryService->getItemsCategory($filter, true);
        $openapiItemsService = new OpenapiItemsService();
        $return = $openapiItemsService->formateCategoryList($result);

        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.item.maincategory.detail.get",
     *     summary="获取商品主类目详情",
     *     tags={"商品"},
     *     description="根据三级类目名称路径，查询三级类目的详情和三级类目下绑定的规格和属性数据",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.maincategory.detail.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="category", description="类目名称 格式：一级类目名称->二级类目名称->三级类目名称" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="category_id", type="string", example="1909", description="商品主类目ID"),
     *                  @SWG\Property( property="category_name", type="string", example="床上用品", description="类目名称"),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                  @SWG\Property( property="category_level", type="string", example="3", description="类目等级"),
     *                  @SWG\Property( property="path", type="string", example="1907,1908,1909", description="类目ID路径"),
     *                  @SWG\Property( property="image_url", type="string", example="null", description="类目图片链接"),
     *                  @SWG\Property( property="created", type="string", example="2021-03-26 15:25:07", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="2021-03-26 15:26:07", description="修改时间"),
     *                  @SWG\Property( property="goods_params", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="attribute_id", type="string", example="1460", description="商品参数ID"),
     *                          @SWG\Property( property="attribute_name", type="string", example="布料", description="商品参数名称"),
     *                          @SWG\Property( property="attribute_memo", type="string", example="布料成分", description="商品参数备注"),
     *                          @SWG\Property( property="attribute_sort", type="string", example="1", description="商品参数排序"),
     *                          @SWG\Property( property="is_show", type="string", example="true", description="参数类型 1：支持商品高级筛选 0"),
     *                          @SWG\Property( property="is_image", type="string", example="false", description="参数是否需要配置图片"),
     *                          @SWG\Property( property="created", type="string", example="2021-03-26 15:10:49", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="2021-03-26 15:10:49", description="更新时间"),
     *                          @SWG\Property( property="attribute_values", type="object",
     *                                  @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                                  @SWG\Property( property="list", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="attribute_value_id", type="string", example="2424", description="商品参数值id"),
     *                                          @SWG\Property( property="attribute_id", type="string", example="1460", description="商品参数ID"),
     *                                          @SWG\Property( property="attribute_value", type="string", example="羊绒", description="商品参数值名称"),
     *                                          @SWG\Property( property="sort", type="string", example="0", description="商品参数值排序"),
     *                                          @SWG\Property( property="created", type="string", example="2021-03-26 15:10:49", description="创建时间"),
     *                                          @SWG\Property( property="updated", type="string", example="2021-03-26 15:10:49", description="更新时间"),
     *                                       ),
     *                                  ),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="goods_spec", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="attribute_id", type="string", example="1459", description="商品规格ID"),
     *                          @SWG\Property( property="attribute_name", type="string", example="床上四件套尺寸", description="商品规格名称"),
     *                          @SWG\Property( property="attribute_memo", type="string", example="床褥被套尺寸", description="商品规格备注"),
     *                          @SWG\Property( property="attribute_sort", type="string", example="1", description="商品规格排序"),
     *                          @SWG\Property( property="is_image", type="string", example="false", description="属性是否需要配置图片"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="图片"),
     *                          @SWG\Property( property="created", type="string", example="2021-03-26 15:06:41", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="2021-03-26 15:06:41", description="更新时间"),
     *                          @SWG\Property( property="attribute_values", type="object",
     *                                  @SWG\Property( property="total_count", type="string", example="6", description="总条数"),
     *                                  @SWG\Property( property="list", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="attribute_value_id", type="string", example="2418", description="商品规格值ID"),
     *                                          @SWG\Property( property="attribute_id", type="string", example="1459", description="商品规格ID"),
     *                                          @SWG\Property( property="attribute_value", type="string", example="180*120(cm)", description="商品规格值名称"),
     *                                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                                          @SWG\Property( property="image_url", type="string", example="null", description="图片"),
     *                                          @SWG\Property( property="created", type="string", example="2021-03-26 15:06:41", description="创建时间"),
     *                                          @SWG\Property( property="updated", type="string", example="2021-03-26 15:06:41", description="修改时间"),
     *                                       ),
     *                                  ),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getItemMainCategoryDetail(Request $request)
    {
        $params = $request->all('category');

        $rules = [
            'category' => ['required', '主类目名称必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $companyId = $request->get('auth')['company_id'];

        $openapiItemsService = new OpenapiItemsService();
        $return = $openapiItemsService->getMainCategoryDetail($companyId, $params['category']);

        return $this->response->array($return);
    }
}
