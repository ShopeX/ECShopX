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

namespace GoodsBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use GoodsBundle\Services\ItemsCategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use WechatBundle\Services\Wxapp\CustomizePageService;
use WechatBundle\Services\Wxapp\TemplateService;

class Category extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/goods/category",
     *     summary="获取分类列表",
     *     tags={"商品"},
     *     description="获取分类列表",
     *     operationId="getCategory",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="is_main_category", in="query", description="是否是主类目 true false", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="category_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="category_name", type="string"),
     *                     @SWG\Property(property="parent_id", type="string"),
     *                     @SWG\Property(property="path", type="string"),
     *                     @SWG\Property(property="sort", type="string"),
     *                     @SWG\Property(property="image_url", type="string"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getCategoryList(Request $request)
    {
        $authInfo = $this->auth->user();
        $filter['company_id'] = $authInfo['company_id'];

        $filter['is_main_category'] = $request->input('is_main_category', false);

        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->getItemsCategory($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/category/{cat_id}",
     *     summary="获取分类子分类",
     *     tags={"商品"},
     *     description="获取分类子分类",
     *     operationId="getChildrenCategorys",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="cat_id", in="path", description="分类id", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="category_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="category_name", type="string"),
     *                     @SWG\Property(property="parent_id", type="string"),
     *                     @SWG\Property(property="path", type="string"),
     *                     @SWG\Property(property="sort", type="string"),
     *                     @SWG\Property(property="image_url", type="string"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getChildrenCategorys($cat_id, Request $request)
    {
        $authInfo = $this->auth->user();
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
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="string" ),
     *     @SWG\Parameter( name="is_main_category", in="query", description="是否是主类目 true false", type="string" ),
     *     @SWG\Parameter( name="category_level", in="query", description="分类等级", type="string" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="category_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="category_name", type="string"),
     *                     @SWG\Property(property="parent_id", type="string"),
     *                     @SWG\Property(property="path", type="string"),
     *                     @SWG\Property(property="sort", type="string"),
     *                     @SWG\Property(property="image_url", type="string"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getLevelCategoryList(Request $request)
    {
        // fe10e2f6 module
        $authInfo = $this->auth->user();
        $filter['company_id'] = $authInfo['company_id'];

        if ($request->input('distributor_id')) {
            $filter['distributor_id'] = $request->input('distributor_id');
        }

        $filter['is_main_category'] = $request->input('is_main_category', false);
        $filter['category_level'] = $request->input('category_level');

        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->lists($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/custom/goods/category",
     *     summary="获取自定义分类列表",
     *     tags={"商品"},
     *     description="获取自定义分类列表",
     *     operationId="getCustomCategoryList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="category_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="category_name", type="string"),
     *                     @SWG\Property(property="parent_id", type="string"),
     *                     @SWG\Property(property="path", type="string"),
     *                     @SWG\Property(property="sort", type="string"),
     *                     @SWG\Property(property="image_url", type="string"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getCustomCategoryList(Request $request)
    {
        // fe10e2f6 module
        $authInfo = $this->auth->user();

        $companyId = $authInfo['company_id'];
        $templateName = 'yykweishop';
        $name = null;
        $pageName = 'category';
        $version = $request->input('version', 'v1.0.1');

        $settingService = new TemplateService();

        $list = $settingService->getTemplateConf($companyId, $templateName, $pageName, $name, $version, 0);
        if (!$name) {
            $return['list'] = $list;
            $config = [];
            foreach ($list as $row) {
                if (isset($row['params']['name']) && isset($row['params']['base'])) {
                    $config[] = $row['params'];
                }
            }
            $return['config'] = $config;
            if (strpos($pageName, 'custom_') !== false) {
                $customizePageService = new CustomizePageService();
                $return['share'] = $customizePageService->getInfoById(intval(str_replace("custom_", "", $pageName)));
            }
        } else {
            $return = $list;
        }
        return $this->response->array($return);
    }
}
