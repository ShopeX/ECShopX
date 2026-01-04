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

namespace GoodsBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsRelCatsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GoodsBundle\Services\ItemsService;
use PHPUnit\Framework\Constraint\Count;
use PopularizeBundle\Services\PromoterGoodsService;
use PopularizeBundle\Services\SettingService;
use Dingo\Api\Exception\ResourceException;
use SalespersonBundle\Services\SalespersonService;

class Category extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/goods/category",
     *     summary="获取分类列表",
     *     tags={"商品"},
     *     description="获取分类列表",
     *     operationId="getCategoryList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_main_category", description="是否主目录" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="distributor_id", description="分销商id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="3", description=""),
     *                  @SWG\Property( property="category_id", type="string", example="3", description="商品分类id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="category_name", type="string", example="测试类目122", description="分类名称"),
     *                  @SWG\Property( property="label", type="string", example="测试类目122", description=""),
     *                  @SWG\Property( property="parent_id", type="string", example="0", description="父分类id,顶级为0"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="path", type="string", example="3", description="路径"),
     *                  @SWG\Property( property="sort", type="string", example="11111", description="排序"),
     *                  @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                  @SWG\Property( property="goods_params", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="goods_spec", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="category_level", type="string", example="1", description="商品分类等级"),
     *                  @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                  @SWG\Property( property="crossborder_tax_rate", type="string", example="12", description="跨境税率，百分比，小数点2位"),
     *                  @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                  @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="4", description=""),
     *                          @SWG\Property( property="category_id", type="string", example="4", description="商品分类id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="category_name", type="string", example="测试类目1-1", description="分类名称"),
     *                          @SWG\Property( property="label", type="string", example="测试类目1-1", description=""),
     *                          @SWG\Property( property="parent_id", type="string", example="3", description="父分类id,顶级为0"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="path", type="string", example="3,4", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="22222222222222", description="排序"),
     *                          @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                          @SWG\Property( property="goods_params", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="goods_spec", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="category_level", type="string", example="2", description="商品分类等级"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="15.56", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                          @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                          @SWG\Property( property="children", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="5", description=""),
     *                                  @SWG\Property( property="category_id", type="string", example="5", description="商品分类id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="category_name", type="string", example="测试类目1-1-1", description="分类名称"),
     *                                  @SWG\Property( property="label", type="string", example="测试类目1-1-1", description=""),
     *                                  @SWG\Property( property="parent_id", type="string", example="4", description="父分类id,顶级为0"),
     *                                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                                  @SWG\Property( property="path", type="string", example="3,4,5", description="路径"),
     *                                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                                  @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                                  @SWG\Property( property="goods_params", type="string", example="2827", description="商品参数"),
     *                                  @SWG\Property( property="goods_spec", type="array",
     *                                      @SWG\Items( type="string", example="1346", description=""),
     *                                  ),
     *                                  @SWG\Property( property="category_level", type="string", example="3", description="商品分类等级"),
     *                                  @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                                  @SWG\Property( property="crossborder_tax_rate", type="string", example="15.4", description="跨境税率，百分比，小数点2位"),
     *                                  @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                                  @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                                  @SWG\Property( property="level", type="string", example="2", description=""),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="level", type="string", example="1", description=""),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="level", type="string", example="0", description=""),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getCategoryList(Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $filter['company_id'] = $company_id;

        if ($request->input('distributor_id')) {
            $filter['distributor_id'] = $request->input('distributor_id');
        }

        $filter['is_main_category'] = $request->input('is_main_category', false);

        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->getItemsCategory($filter, true, 1, -1, ['sort' => 'DESC', 'created' => 'ASC'], 'category_id,category_name,category_level,parent_id,image_url');
        // 分类获取不到获取商城主类目
        if (false == $filter['is_main_category'] && !$result) {
            $filter['is_main_category'] = true;
            if (isset($filter['distributor_id'])) {
                unset($filter['distributor_id']);
            }
            $result = $itemsCategoryService->getItemsCategory($filter, true, 1, -1, ['sort' => 'DESC', 'created' => 'ASC'], 'category_id,category_name,category_level,parent_id,image_url');
        }
        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/wxapp/goods/promoter/category",
     *     summary="获取推广店铺分类列表",
     *     tags={"商品"},
     *     description="获取分类列表",
     *     operationId="getPromoterCategoryList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_main_category", description="是否主目录" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="distributor_id", description="分销商id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="3", description=""),
     *                  @SWG\Property( property="category_id", type="string", example="3", description="商品分类id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="category_name", type="string", example="测试类目122", description="分类名称"),
     *                  @SWG\Property( property="label", type="string", example="测试类目122", description=""),
     *                  @SWG\Property( property="parent_id", type="string", example="0", description="父分类id,顶级为0"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="path", type="string", example="3", description="路径"),
     *                  @SWG\Property( property="sort", type="string", example="11111", description="排序"),
     *                  @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                  @SWG\Property( property="goods_params", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="goods_spec", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="category_level", type="string", example="1", description="商品分类等级"),
     *                  @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                  @SWG\Property( property="crossborder_tax_rate", type="string", example="12", description="跨境税率，百分比，小数点2位"),
     *                  @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                  @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="4", description=""),
     *                          @SWG\Property( property="category_id", type="string", example="4", description="商品分类id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="category_name", type="string", example="测试类目1-1", description="分类名称"),
     *                          @SWG\Property( property="label", type="string", example="测试类目1-1", description=""),
     *                          @SWG\Property( property="parent_id", type="string", example="3", description="父分类id,顶级为0"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="path", type="string", example="3,4", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="22222222222222", description="排序"),
     *                          @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                          @SWG\Property( property="goods_params", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="goods_spec", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="category_level", type="string", example="2", description="商品分类等级"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="15.56", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                          @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                          @SWG\Property( property="children", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="5", description=""),
     *                                  @SWG\Property( property="category_id", type="string", example="5", description="商品分类id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="category_name", type="string", example="测试类目1-1-1", description="分类名称"),
     *                                  @SWG\Property( property="label", type="string", example="测试类目1-1-1", description=""),
     *                                  @SWG\Property( property="parent_id", type="string", example="4", description="父分类id,顶级为0"),
     *                                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                                  @SWG\Property( property="path", type="string", example="3,4,5", description="路径"),
     *                                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                                  @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                                  @SWG\Property( property="goods_params", type="string", example="2827", description="商品参数"),
     *                                  @SWG\Property( property="goods_spec", type="array",
     *                                      @SWG\Items( type="string", example="1346", description=""),
     *                                  ),
     *                                  @SWG\Property( property="category_level", type="string", example="3", description="商品分类等级"),
     *                                  @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                                  @SWG\Property( property="crossborder_tax_rate", type="string", example="15.4", description="跨境税率，百分比，小数点2位"),
     *                                  @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                                  @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                                  @SWG\Property( property="level", type="string", example="2", description=""),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="level", type="string", example="1", description=""),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="level", type="string", example="0", description=""),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getPromoterCategoryList(Request $request)
    {
        // ShopEx EcShopX Business Logic Layer
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $filter['company_id'] = $company_id;

        $isSalesmanPage  = $request->input('isSalesmanPage',false);
        $distributor_id = $request->input('distributor_id',false);

        if($isSalesmanPage){
            //获取业务员推广店铺
            $listdata = ['list' => []];
            $salespersonService = new SalespersonService();
            $filter_sales['company_id'] = $authInfo['company_id'];
            $filter_sales['user_id']    = $authInfo['user_id'];

            $listdata = $salespersonService->getSalespersonList($filter_sales, 1, 500);
            $dIds = array_column($listdata['list'],'shop_id');
            if($dIds && $isSalesmanPage){
                $filter['distributor_id'] = $dIds;
            }

            if ($distributor_id  && in_array($distributor_id,$dIds)) {
                $filter['distributor_id'] = $request->input('distributor_id');
            }
        }else{
            if ($distributor_id ) {
                $filter['distributor_id'] = $request->input('distributor_id');
            }

        }

        $filter['is_main_category'] = $request->input('is_main_category', false);

        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->getItemsCategory($filter, true, 1, -1, ['sort' => 'DESC', 'created' => 'ASC'], 'category_id,category_name,category_level,parent_id,image_url');
        // 分类获取不到获取商城主类目
        if (false == $filter['is_main_category'] && !$result) {
            $filter['is_main_category'] = true;
            if (isset($filter['distributor_id'])) {
                unset($filter['distributor_id']);
            }
            $result = $itemsCategoryService->getItemsCategory($filter, true, 1, -1, ['sort' => 'DESC', 'created' => 'ASC'], 'category_id,category_name,category_level,parent_id,image_url');
        }
        // $result['filter'] = $filter; 
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/category/{cat_id}",
     *     summary="获取分类子分类",
     *     tags={"商品"},
     *     description="获取分类子分类",
     *     operationId="getChildrenCategorys",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="9", description=""),
     *                          @SWG\Property( property="category_id", type="string", example="9", description="分类id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="category_name", type="string", example="测试分类1-2", description="分类名称"),
     *                          @SWG\Property( property="label", type="string", example="测试分类1-2", description=""),
     *                          @SWG\Property( property="parent_id", type="string", example="1", description="父分类id,顶级为0"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="path", type="string", example="1,9", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="is_main_category", type="string", example="false", description="是否为商品主类目"),
     *                          @SWG\Property( property="goods_params", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="goods_spec", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="category_level", type="string", example="2", description="分类等级"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="created", type="string", example="1561964100", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1599449617", description="修改时间"),
     *                          @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getChildrenCategorys($cat_id, Request $request)
    {
        // ShopEx EcShopX Business Logic Layer
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $itemsCategoryService = new ItemsCategoryService();
        $filter = [
            'company_id' => $company_id,
            'parent_id' => $cat_id,
        ];

        if ($request->input('distributor_id')) {
            $filter['distributor_id'] = $request->input('distributor_id');
        }

        $result = $itemsCategoryService->lists($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/categorylevel",
     *     summary="获取指定等级分类列表",
     *     tags={"商品"},
     *     description="获取指定等级分类列表",
     *     operationId="getLevelCategoryList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="distributor_id", description="分销商id" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_main_category", description="是否主目录" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="category_level", description="等级" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="30", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1611", description=""),
     *                          @SWG\Property( property="category_id", type="string", example="1611", description="分类id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="category_name", type="string", example="二级x", description="分类名称"),
     *                          @SWG\Property( property="label", type="string", example="二级x", description=""),
     *                          @SWG\Property( property="parent_id", type="string", example="1610", description="父级id, 0为顶级"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="path", type="string", example="1610,1611", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                          @SWG\Property( property="goods_params", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="goods_spec", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="category_level", type="string", example="2", description="分类等级"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="null", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="created", type="string", example="1606369584", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                          @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getLevelCategoryList(Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];

        $filter['company_id'] = $company_id;

        if ($request->input('distributor_id')) {
            $filter['distributor_id'] = $request->input('distributor_id');
        }

        $filter['is_main_category'] = $request->input('is_main_category', false);
//        $filter['category_level']   = $request->input('category_level');


        $settingService = new SettingService();
        $config = $settingService->getConfig($company_id);
        if (isset($config['goods']) && $config['goods'] == 'select') {
            $itemFilter['rebate'] = 1;
        }
        $itemFilter['company_id'] = $company_id;
        $itemFilter['approve_status'] = ['onsale', 'only_show'];
        $itemFilter['audit_status'] = 'approved';
        $itemFilter['item_type'] = 'normal';
        $itemFilter['is_default'] = true;

        $distributorFilter = [
            'company_id' => $company_id,
            'is_valid' => 'true'
        ];
        $distributorService = new DistributorService();
        $validDistributorList = $distributorService->getDistributorOriginalList($distributorFilter, 1, -1);
        $validDistributorIds = array_column($validDistributorList['list'], 'distributor_id');
        $itemFilter['distributor_id'] = array_merge(['0'], $validDistributorIds);

        $itemsService = new ItemsService();
        $itemsList = $itemsService->itemsRepository->list($itemFilter, [], -1, 1, ['item_id']);

        $itemRelCatsParams['company_id'] = $company_id;
        $itemRelCatsParams['item_id'] = array_column($itemsList['list'], 'item_id');
        $itemsRelCatsService = new ItemsRelCatsService();
        $itemsRelCatsList = $itemsRelCatsService->lists($itemRelCatsParams);

        $itemsCategoryService = new ItemsCategoryService();
        $filter['category_id'] = [];
        foreach ($itemsRelCatsList['list'] as $cat) {
            $category = $itemsCategoryService->getInfo(['company_id' => $company_id, 'category_id' => $cat['category_id']]);
            if (isset($category['parent_id'])) {
                if ($category['parent_id']) {
                    $path = explode(',', $category['path']);
                    $catId = $path[0];
                } else {
                    $catId = $category['category_id'];
                }
                array_push($filter['category_id'], $catId);
            }
        }

        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->lists($filter);

        return $this->response->array($result);
    }




    /**
     * @SWG\Get(
     *     path="/wxapp/goods/shopcategorylevel",
     *     summary="获取小店上架分类",
     *     tags={"商品"},
     *     description="获取小店上架分类",
     *     operationId="getShopShelvesCategoryList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="shop_user_id", description="userId" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="distributor_id", description="分销商id" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_main_category", description="是否主目录" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="category_level", description="等级" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="30", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1611", description=""),
     *                          @SWG\Property( property="category_id", type="string", example="1611", description="分类id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="category_name", type="string", example="二级x", description="分类名称"),
     *                          @SWG\Property( property="label", type="string", example="二级x", description=""),
     *                          @SWG\Property( property="parent_id", type="string", example="1610", description="父级id, 0为顶级"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="path", type="string", example="1610,1611", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                          @SWG\Property( property="goods_params", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="goods_spec", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="category_level", type="string", example="2", description="分类等级"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="null", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="created", type="string", example="1606369584", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                          @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getShopShelvesCategoryList(Request $request)
    {
        $authInfo = $request->get('auth');

        $shopUserId = $request->input('shop_user_id', '');

        $filter['user_id'] = $shopUserId ? $shopUserId : $authInfo['user_id'];
        $filter['company_id'] = $authInfo['company_id'];

        $settingService = new SettingService();
        $config = $settingService->getConfig($authInfo['company_id']);
        if ($config['goods'] == 'all') {
            $filter['is_all_goods'] = true;
        }

        $promoterGoodsService = new PromoterGoodsService();
        $list = $promoterGoodsService->lists($filter);

        $params['company_id'] = $authInfo['company_id'];
        $params['goods_id'] = [];
        if ($config['goods'] == 'select') {
            $params['rebate'] = 1;
        }
        $params['approve_status'] = ['onsale','offline_sale'];
        for ($i = 0; $i < count($list['list']); $i++) {
            array_push($params['goods_id'], $list['list'][$i]['goods_id']);
        }
        $itemsService = new ItemsService();
        $shopItemsList = $itemsService->list($params, [], -1);
        $itemRelCatsParams['company_id'] = $authInfo['company_id'];
        $itemRelCatsParams['item_id'] = [];
        for ($i = 0; $i < count($shopItemsList['list']); $i++) {
            array_push($itemRelCatsParams['item_id'], $shopItemsList['list'][$i]['item_id']);
        }
        $itemsRelCatsService = new ItemsRelCatsService();
        $itemsRelCatsList = $itemsRelCatsService->lists($itemRelCatsParams);

        if ($request->input('distributor_id')) {
            $shopCatsParams['distributor_id'] = $request->input('distributor_id');
        }


        $listdata = ['list' => []];
        $salespersonService = new SalespersonService();
        $filter_sales['company_id'] = $authInfo['company_id'];
        $filter_sales['user_id']    = $authInfo['user_id'];

        $listdata = $salespersonService->getSalespersonList($filter_sales, 1, 500);
        $dIds = array_column($listdata['list'],'shop_id');
        if(!empty($dIds) ){
            $shopCatsParams['distributor_id'] = $dIds;
        }

        
        $shopCatsParams['is_main_category'] = $request->input('is_main_category', false);
//        if($request->input('category_level')){
//            $shopCatsParams['category_level']   = $request->input('category_level');
//        }
        $itemsCategoryService = new ItemsCategoryService();

        $shopCatsParams['company_id'] = $authInfo['company_id'];
        $shopCatsParams['category_id'] = [];
//        dd($itemsRelCatsList);
        $cats['category_id'] = [];
        for ($i = 0; $i < count($itemsRelCatsList['list']); $i++) {
            $catsList = $itemsCategoryService->lists(['company_id' => $authInfo['company_id'],'category_id' => $itemsRelCatsList['list'][$i]['category_id']]);
            if (number_format($catsList['list'][0]['parent_id']) > 0) {
                $path = explode(',', $catsList['list'][0]['path']);
                array_push($cats['category_id'], $path[0]);
            } else {
                array_push($shopCatsParams['category_id'], $catsList['list'][0]['category_id']);
            }
        }

        for ($i = 0; $i < count($cats['category_id']); $i++) {
            array_push($shopCatsParams['category_id'], $cats['category_id'][$i]);
        }
        $result = $itemsCategoryService->lists($shopCatsParams);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/category/info",
     *     summary="获取分类信息",
     *     tags={"商品"},
     *     description="获取分类信息",
     *     operationId="getCategoryInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="category_id", type="string", example="9", description="分类id"),
     *                  @SWG\Property( property="category_name", type="string", example="测试分类1-2", description="分类名称"),
     *                  @SWG\Property( property="category_label", type="string", example="1", description="分类层级"),
     *                  @SWG\Property( property="parent_id", type="string", example="1", description="父分类id,顶级为0"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="path", type="string", example="1,9", description="路径"),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                  @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                  @SWG\Property( property="customize_page_id", type="string", example="", description="分类模版ID"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="category_id", type="string", example="9", description="分类id"),
     *                          @SWG\Property( property="category_name", type="string", example="测试分类1-2", description="分类名称"),
     *                          @SWG\Property( property="category_level", type="string", example="2", description="分类等级"),
     *                          @SWG\Property( property="parent_id", type="string", example="1", description="父分类id,顶级为0"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="path", type="string", example="1,9", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                          @SWG\Property( property="customize_page_id", type="string", example="", description="分类模版ID"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getCategoryInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $params['company_id'] = $authInfo['company_id'];
        $validator = app('validator')->make($params, [
            'category_id' => 'required|integer|min:1',
            'company_id' => 'required|integer|min:1',
        ], [
            'category_id.required' => '请传递商品分类',
            'company_id.required' => '请传递品牌ID',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            throw new ResourceException($errors);
        }
        $itemsCategoryService = new ItemsCategoryService();
        // 获取分类详情
        $filter = [
            'company_id' => $params['company_id'],
            'category_id' => $params['category_id']
        ];
        $categoryInfo = $itemsCategoryService->getCategoryInfo($filter);
        if ( empty($categoryInfo) ) {
            throw new ResourceException('未获取到分类详情');
        }
        // 获取分类所有下属分类信息
        $categoryIds = $itemsCategoryService->getItemsCategoryIds($params['category_id'], $params['company_id']);
        if ( count($categoryIds) == 1 ) {// 没有下级分类
            return $this->response->array(
                $itemsCategoryService->formateCategoryInfo( $categoryInfo )
            );
        }
        $filter = [
            'company_id' => $params['company_id'],
            'category_id' => $categoryIds
        ];
        if ( $params['distributor_id'] ?? '' ) {
            $filter['distributor_id'] = $params['distributor_id'];
        }
        $orderBy = ["sort" => "DESC", "created" => "ASC"];
        $cols = 'category_id,category_name,category_level,parent_id,image_url,path,sort,distributor_id,customize_page_id';
        $categoryLists = [];
        if ( $categoryInfo['parent_id'] == 0 ) {
            // 获取多级分类信息
            $categoryLists = $itemsCategoryService->getItemsCategory($filter, true, 1, -1, $orderBy, $cols);
        } else {
            // 获取二级分类信息
            $categoryData = $itemsCategoryService->lists($filter, $orderBy, -1, 1, $cols);
            if ( $categoryData['list'] ?? '' ) {
                $categoryLists = $itemsCategoryService->getTree($categoryData['list'], $categoryInfo['parent_id']);
            }
        }
        if ( empty($categoryLists) ) {
            throw new ResourceException('未获取到分类信息');
        }
        return $this->response->array(
            $itemsCategoryService->formateCategoryInfo( reset($categoryLists) )
        );
    }
}
