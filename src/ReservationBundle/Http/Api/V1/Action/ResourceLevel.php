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

namespace ReservationBundle\Http\Api\V1\Action;

use ReservationBundle\Services\ResourceLevelManagementService as ResourceLevelManagement;
use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

class ResourceLevel extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/resource/level",
     *     summary="新增资源位",
     *     tags={"预约"},
     *     description="创建资源位信息",
     *     operationId="createData",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="shopId", in="formData", description="门店id", type="integer", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="shopName", in="formData", description="门店名称", type="integer", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="name", in="formData", description="资源位名称", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="description", in="formData", description="资源位描述", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="imageUrl", in="formData", description="资源位图片url", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="materialIds", in="formData", description="该资源位关联的物料商品id数组", type="string", required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function createData(Request $request)
    {
        $validator = app('validator')->make($request->input(), [
            'shopId' => 'required',
            'name' => 'required|string|max:10',
            'description' => 'required|string|max:100',
            'materialIds' => 'required',
        ], [
            'shopId.*' => '门店必选',
            'name.*' => '资源位名称必填|最多10字',
            'description.*' => '简介必填|最多100字',
            'materialIds.*' => '服务项目必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }

        $authInfo = app('auth')->user()->get();
        $input = $request->input();
        $postData = [
            'companyId' => intval($authInfo['company_id']),
            'shopId' => $input['shopId'],
            'shopName' => $input['shopName'],
            'name' => $input['name'],
            'description' => $input['description'],
            'image_url' => $input['imageUrl'],
            'status' => 'active',
        ];
        $materialIds = $input['materialIds'];
        $resourceLevelService = new ResourceLevelManagement();
        $result = $resourceLevelService->createResourceLevel($postData, $materialIds);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Patch(
     *     path="/resource/level",
     *     summary="更新资源位",
     *     tags={"预约"},
     *     description="更新资源位信息",
     *     operationId="updateData",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="resourceLevelId", in="query", description="资源位自增id", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="name", in="query", description="资源位名称", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="shopName", in="query", description="门店名称", type="integer", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="shopId", in="query", description="门店id", type="integer", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="imageUrl", in="query", description="资源位图片url", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="description", in="query", description="资源位描述", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="quantity", in="query", description="数量", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="materialIds", in="query", description="该资源位关联的物料商品", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="status", in="query", description="状态,active:有效，invalid: 失效", type="string", required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *         @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="status", type="boolean", example=true),
     *         )
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function updateData(Request $request)
    {
        $validator = app('validator')->make($request->input(), [
            'name' => 'required',
            'description' => 'required',
            'materialIds' => 'required',
        ], [
            'name.*' => '名称必填',
            'description.*' => '简介必填',
            'materialIds.*' => '服务项目必填',
        ]);

        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }

        $authInfo = app('auth')->user()->get();
        $input = $request->input();

        $filter['resource_level_id'] = $input['resourceLevelId'];
        $filter['company_id'] = $authInfo['company_id'];
        $filter['shop_id'] = $input['shopId'];

        $postData = [
            'companyId' => $authInfo['company_id'],
            'name' => $input['name'],
            'shopId' => $input['shopId'],
            'shopName' => $input['shopName'],
            'description' => $input['description'],
            'image_url' => $input['imageUrl'],
            'status' => 'active',
        ];

        $materialIds = $input['materialIds'];

        $resourceLevelService = new ResourceLevelManagement();
        $result = $resourceLevelService->updateResourceLevel($filter, $postData, $materialIds);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Delete(
     *     path="/resource/level",
     *     summary="删除资源位",
     *     tags={"预约"},
     *     description="删除资源位数据",
     *     operationId="deleteData",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="resource_level_id", in="query", description="资源位自增id", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="shop_id", in="query", description="门店id", type="string", required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function deleteData(Request $request)
    {
        $filter['resource_level_id'] = $request->input('resource_level_id');
        $filter['shop_id'] = $request->input('shop_id');

        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];

        $resourceLevelService = new ResourceLevelManagement();
        $result = $resourceLevelService->deleteResourceLevel($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/resource/level/{levelId}",
     *     summary="获取资源位详情",
     *     tags={"预约"},
     *     description="获取资源位详细信息",
     *     operationId="getData",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="levelId", in="path", description="资源位自增id", type="string", required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *        @SWG\Property( property="data", type="object",
     *            ref="#/definitions/ResourceLevel",
     *        ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function getData($levelId)
    {
        $filter['resource_level_id'] = $levelId;

        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];

        $resourceLevelService = new ResourceLevelManagement();
        $result = $resourceLevelService->getResourceLevel($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/resource/levellist",
     *     summary="获取资源位列表",
     *     tags={"预约"},
     *     description="获取资源位数据列表",
     *     operationId="getListData",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string", required=true,
     *     ),
     *      @SWG\Parameter(
     *          name="shopId", in="query", description="门店id", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="page", in="query", description="当前页面,获取门店列表的初始偏移位置，从1开始计数", type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize", in="query", description="每页数量,最大不能超过50", type="integer",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *        @SWG\Property( property="data", type="object",
     *            @SWG\Property( property="total_count", type="string", example="2", description="总数"),
     *                @SWG\Property( property="list", type="array",
     *                    @SWG\Items(
     *                        type="object",
     *                        ref="#/definitions/ResourceLevel",
     *                    ),
     *                ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function getListData(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }


        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];

        $input = $request->input();
        if (isset($input['shopId'])) {
            $filter['shop_id'] = $input['shopId'];
        }

        $resourceLevelService = new ResourceLevelManagement();
        $resourceLevel = $resourceLevelService->getListResourceLevel($filter);

        //获取门店信息
        // if (isset($resourceLevel['list']) && $resourceLevel['list']) {
        //     $shopIds = array_column($resourceLevel['list'], 'shopId');
        //     $storeService = new ShopsService(new WxShopsService);
        //     $storeFilter['wx_shop_id'] = $shopIds;
        //     $storeFilter['company_id'] = $authInfo['company_id'];
        //     $storeData = $storeService->getShopsList($storeFilter, 1, 100);

        //     if ($storeData) {
        //         foreach ($storeData['list'] as $value) {
        //             $shopNames[$value['wxShopId']] = $value['storeName'];
        //         }

        //         foreach ($resourceLevel['list'] as $key => $value) {
        //             $resourceLevel['list'][$key]['storeName'] = $shopNames[$value['shopId']];
        //         }
        //     }
        // }
        return $this->response->array($resourceLevel);
    }

    /**
    * @SWG\Put(
    *     path="/resource/setlevelstatus",
    *     summary="更新资源位状态",
    *     tags={"预约"},
    *     description="更新资源位状态",
    *     operationId="updateResourceLevelStatus",
    *     @SWG\Parameter(
    *          name="Authorization", in="header", description="JWT验证token", type="string", required=true,
    *     ),
    *     @SWG\Parameter(
    *          name="resourceLevelId", in="formData", description="资源位自增id", type="string", required=true,
    *     ),
    *     @SWG\Parameter(
    *          name="status", in="formData", description="状态 active:有效，invalid: 失效", type="string", required=true,
    *     ),
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
    *        @SWG\Property( property="data", type="object",
    *            @SWG\Property( property="resourceLevelId", type="string", example="140", description="资源位自增id"),
    *            @SWG\Property( property="shopId", type="string", example="466", description="门店id"),
    *            @SWG\Property( property="shopName", type="string", example="宾阳路21号小区", description="门店名称"),
    *            @SWG\Property( property="name", type="string", example="资源位名称", description="资源位名称"),
    *            @SWG\Property( property="description", type="string", example="这是简介", description="简介"),
    *            @SWG\Property( property="status", type="string", example="invalid", description="状态 active:有效，invalid: 失效"),
    *            @SWG\Property( property="imageUrl", type="string", example="https://b-img-cdn.yuanyuanke.cn/image/21/2021/01/21/f0aad8eefe62770e0e7db0cb53da675cXuyAQ0wptGfYKZuS6iuJEfFn83JE6rqI", description="图片url"),
    *            @SWG\Property( property="quantity", type="string", example="1", description="数量"),
    *            @SWG\Property( property="created", type="string", example="1611303934", description="创建时间"),
    *            @SWG\Property( property="updated", type="string", example="1611304290", description="更新时间"),
    *          ),
    *     )),
    *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
    * )
    */
    public function updateResourceLevelStatus(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $status = $request->get('status') == 'true' ? 'active' : 'invalid';
        $resource_level_id = $request->get('resourceLevelId');
        $company_id = $authInfo['company_id'];
        $resourceLevelService = new ResourceLevelManagement();
        $result = $resourceLevelService->updateOneBy(['company_id' => $company_id, 'resource_level_id' => $resource_level_id], ['status' => $status]);
        return $this->response->array($result);
    }
}
