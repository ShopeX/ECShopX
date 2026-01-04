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

use Illuminate\Http\Request;
use Dingo\Api\Exception\StoreResourceFailedException;
use App\Http\Controllers\Controller as Controller;
use GoodsBundle\Services\ItemsTagsService;

class ItemsTags extends Controller
{
    public $itemsTagsService;
    public $limit;

    public function __construct()
    {
        $this->itemsTagsService = new ItemsTagsService();
        $this->limit = 20;
    }

    /**
     * @SWG\Post(
     *     path="/goods/tag",
     *     summary="新增商品标签",
     *     tags={"商品"},
     *     description="新增商品标签",
     *     operationId="createTags",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="标签描述", required=false, type="string"),
     *     @SWG\Parameter( name="tag_color", in="query", description="标签颜色", required=false, type="string"),
     *     @SWG\Parameter( name="font_color", in="query", description="标签文字颜色", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="99", description="标签id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="tag_name", type="string", example="test", description="标签名称"),
     *                  @SWG\Property( property="tag_color", type="string", example="rgba(203, 140, 148, 1)", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="#ffffff", description="字体颜色"),
     *                  @SWG\Property( property="description", type="string", example="xx", description="描述"),
     *                  @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                  @SWG\Property( property="front_show", type="string", example="1", description="前台是否显示 0 否 1 是"),
     *                  @SWG\Property( property="created", type="string", example="1611909936", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1611909936", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function createTags(Request $request)
    {
        $params = $request->all('tag_id', 'tag_name', 'description', 'tag_color', 'font_color', 'front_show');
        $rules = [
            'tag_name' => ['required', '标签名称不能为空'],
            'tag_color' => ['required', '标签颜色'],
            'font_color' => ['required', '标签字体颜色'],
            'front_show' => ['in:0,1', '前台显示类型错误'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $params['distributor_id'] = app('auth')->user()->get('distributor_id');

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $result = $this->itemsTagsService->createTag($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/goods/tag",
     *     summary="更新商品标签",
     *     tags={"商品"},
     *     description="更新商品标签",
     *     operationId="updateTags",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tag_id", in="query", description="tag_id", required=true, type="string"),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="标签描述", required=false, type="string"),
     *     @SWG\Parameter( name="font_color", in="query", description="标签文字颜色", required=false, type="string"),
     *     @SWG\Parameter( name="tag_color", in="query", description="标签颜色", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="92", description="标签id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="tag_name", type="string", example="审核专用商品", description="标签名称"),
     *                  @SWG\Property( property="tag_color", type="string", example="rgba(21, 0, 255, 1)", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="#ffffff", description="字体颜色"),
     *                  @SWG\Property( property="description", type="string", example="1", description="描述"),
     *                  @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                  @SWG\Property( property="front_show", type="string", example="0", description="前台是否显示 0 否 1 是"),
     *                  @SWG\Property( property="created", type="string", example="1590667252", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1611908435", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function updateTags(Request $request)
    {
        $params = $request->all('tag_id', 'tag_name', 'description', 'tag_color', 'font_color', 'front_show');

        $rules = [
            'tag_id' => ['required', 'tagId不能为空'],
            'tag_name' => ['required', '标签名称不能为空'],
            'tag_color' => ['required', '标签颜色'],
            'font_color' => ['required', '标签字体颜色'],
            'front_show' => ['in:0,1', '前台显示类型错误'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $distributor_id = app('auth')->user()->get('distributor_id');

        // $companyId = 1;
        $filter['tag_id'] = $params['tag_id'];
        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = $distributor_id;
        $result = $this->itemsTagsService->updateTag($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/goods/tag",
     *     summary="获取商品标签列表",
     *     tags={"商品"},
     *     description="获取商品标签列表",
     *     operationId="getTagsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer" ),
     *     @SWG\Parameter( name="page_size", in="query", description="每页长度", required=true, type="integer" ),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", type="integer" ),
     *     @SWG\Parameter( name="front_show", in="query", description="是否筛选出只能给前端展示的标签【null 不做筛选】【1 筛选出可以在前端展示的标签】【0 筛选出不可以在前端展示的标签】", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="4", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="tag_id", type="string", example="98", description="标签id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="tag_name", type="string", example="树脂", description="标签名称"),
     *                          @SWG\Property( property="tag_color", type="string", example="#ff1939", description="标签颜色"),
     *                          @SWG\Property( property="font_color", type="string", example="#ffffff", description="字体颜色"),
     *                          @SWG\Property( property="description", type="string", example="null", description="描述"),
     *                          @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                          @SWG\Property( property="front_show", type="string", example="0", description="前台是否显示 0 否 1 是"),
     *                          @SWG\Property( property="created", type="string", example="1593518236", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1593518236", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getTagsList(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'tag_name', 'front_show');
        $rules = [
            'page' => ['required', 'page 必填'],
            'pageSize' => ['required', 'pageSize 必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }
        $page = $params['page'] ? intval($params['page']) : 1;
        $pageSize = $params['pageSize'] ? intval($params['pageSize']) : $this->limit;

        $companyId = app('auth')->user()->get('company_id');
        // $companyId = 1;

        $filter['company_id'] = $companyId;

        if (isset($params['tag_name']) && $params['tag_name']) {
            $filter['tag_name|contains'] = $params['tag_name'];
        }
        if($request->has('tag_source') && $tag_source = $request->input('tag_source','')){
            if($tag_source == 'distributor'){
                $filter['distributor_id|gt'] = 0;
            }elseif ($tag_source == 'platform'){
                $filter['distributor_id'] = 0;
            }
        }else{
            if($request->has('isPlatform') && $request->input('isPlatform')== true){
                $filter['distributor_id|in'] = ['0',$request->input("distributor_id", app('auth')->user()->get('distributor_id'))];
            }else{
                $filter['distributor_id'] = $request->input("distributor_id", app('auth')->user()->get('distributor_id'));
            }
        }

        #平台端可以查询某个门店的tag，防止越权
        if ($request->has('distributor_id') && !empty($request->input('distributor_id')) && app('auth')->user()->get('distributor_id') == 0 && empty($filter['distributor_id|in'])) {
            $filter['distributor_id'] = $request->input('distributor_id');
        }

        if (isset($params["front_show"])) {
            $filter["front_show"] = $params["front_show"];
        }

        //商品列表获取可用的标签，店铺也可以使用平台的商品标签
        if ($params['pageSize'] == 500 && isset($filter['distributor_id']) && $filter['distributor_id']) {
            if (is_array($filter['distributor_id'])) {
                $filter['distributor_id'][] = 0;
            } else {
                $filter['distributor_id'] = [0, $filter['distributor_id']];
            }
        }

        $orderBy = ['created' => 'DESC'];
        $result = $this->itemsTagsService->getListTags($filter, $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/goods/tag/{tag_id}",
     *     summary="获取商品标签详情",
     *     tags={"商品"},
     *     description="获取商品标签详情",
     *     operationId="getTagsInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="tag_id", in="path", description="标签id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="98", description="标签id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="tag_name", type="string", example="树脂", description="标签名称"),
     *                  @SWG\Property( property="tag_color", type="string", example="#ff1939", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="#ffffff", description="字体颜色"),
     *                  @SWG\Property( property="description", type="string", example="null", description="描述"),
     *                  @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                  @SWG\Property( property="front_show", type="string", example="0", description="前台是否显示 0 否 1 是"),
     *                  @SWG\Property( property="created", type="string", example="1593518236", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1593518236", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getTagsInfo($tag_id)
    {
        $result = $this->itemsTagsService->getTagsInfo($tag_id);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/goods/tag/{tag_id}",
     *     summary="删除商品标签详情",
     *     tags={"商品"},
     *     description="删除商品标签详情",
     *     operationId="deleteTag",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="tag_id", in="path", description="标签id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function deleteTag($tag_id)
    {
        $filter['tag_id'] = $tag_id;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        // $filter['company_id'] = 1;
        $result = $this->itemsTagsService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/goods/reltag",
     *     summary="关联商品标签",
     *     tags={"商品"},
     *     description="关联商品标签",
     *     operationId="tagsRelItem",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tag_ids", in="query", description="tagId", required=true, type="string"),
     *     @SWG\Parameter( name="item_ids", in="query", description="itemId", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function tagsRelItem(Request $request)
    {
        $params = $request->all('tag_ids', 'item_ids');
        $companyId = app('auth')->user()->get('company_id');

        if (!$params['item_ids']) {
            throw new StoreResourceFailedException('请选择商品');
        }

        if ($params['tag_ids']) {
            $errMsg = '商品标签导致活动冲突';
            $result = $this->itemsTagsService->checkActivity($params['item_ids'], $params['tag_ids'], $companyId, $errMsg);
            if (!$result) {
                throw new StoreResourceFailedException($errMsg);
            }
        }

        if (is_array($params['item_ids']) && is_array($params['tag_ids'])) {
            $result = $this->itemsTagsService->createRelTags($params['item_ids'], $params['tag_ids'], $companyId);
        } elseif (!is_array($params['item_ids'])) {
            $result = $this->itemsTagsService->createRelTagsByItemId($params['item_ids'], $params['tag_ids'], $companyId);
        } elseif (is_array($params['item_ids']) && !is_array($params['tag_ids'])) {
            $result = $this->itemsTagsService->createRelTagsByTagId($params['item_ids'], $params['tag_ids'], $companyId);
        }

        //todo 这里有性能问题，暂时不需要执行
        //$this->itemsTagsService->updateItemTag(['company_id' => $companyId]);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/items/tagsearch",
     *     summary="根据tagid筛选商品",
     *     tags={"商品"},
     *     description="根据tagid筛选商品",
     *     operationId="getItemIdsByTagids",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="tag_id", in="path", description="标签id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="string", example="215", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemIdsByTagids(Request $request)
    {
        $result = [];
        if ($params['tag_id'] = $request->input('tag_id')) {
            $params['company_id'] = app('auth')->user()->get('company_id');
            // $params['company_id'] = 1;
            $result = $this->itemsTagsService->getItemIdsByTagids($params);
            return $this->response->array($result);
        }
        return $this->response->array($result);
    }
}
