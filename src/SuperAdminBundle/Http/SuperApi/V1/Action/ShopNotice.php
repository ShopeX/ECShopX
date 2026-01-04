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

namespace SuperAdminBundle\Http\SuperApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use SuperAdminBundle\Services\ShopNoticeService;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class ShopNotice extends Controller
{
    /**
     * @SWG\Post(
     *     path="/superadmin/notice/add",
     *     summary="新增店铺公告",
     *     tags={"店铺公告"},
     *     description="新增店铺公告",
     *     operationId="addShopNotice",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="title",in="query",description="公告标题",required=true,type="string"),
     *     @SWG\Parameter(name="type",in="query",description="公告类型",required=true,type="string"),
     *     @SWG\Parameter(name="web_link",in="query",description="网页链接",required=true,type="string"),
     *     @SWG\Parameter(name="is_publish",in="query",description="是否发布",required=true,type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/ShopNoticeInfo"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function addShopNotice(Request $request)
    {
        $params = $request->all();

        $rules = [
            'type' => ['required', '公告类型缺少！'],
            'title' => ['required', '公告标题缺少！'],
            'web_link' => ['required', '网页链接缺少！'],
            'is_publish' => ['required', '是否发布缺少！'],
        ];
        $errorMessage = validator_params($params, $rules);

        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $shopNoticeService = new ShopNoticeService();

        $postdata = $request->input();

        $data = $shopNoticeService->create($postdata);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/superadmin/notice/list",
     *     summary="获取店铺公告列表",
     *     tags={"店铺公告"},
     *     description="获取店铺公告列表",
     *     operationId="getShopNoticeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="page",in="query",description="页码",required=true,type="integer"),
     *     @SWG\Parameter(name="pageSize",in="query",description="分页大小",required=true,type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/ShopNoticeInfo"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getShopNoticeList(Request $request)
    {
        $inputData = $request->input();

        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取公告列表出错.', $validator->errors());
        }

        $shopNoticeService = new ShopNoticeService();

        $data = $shopNoticeService->getShopNoticeList($inputData);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/superadmin/notice/{notice_id}",
     *     summary="获取店铺公告详情",
     *     tags={"店铺公告"},
     *     description="获取店铺公告详情",
     *     operationId="getShopNoticeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="notice_id",in="path",description="公告ID",required=true,type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/ShopNoticeInfo"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getShopNoticeInfo($notice_id)
    {
        if (!$notice_id) {
            throw new ResourceException('参数错误');
        }

        $shopNoticeService = new ShopNoticeService();

        $data = $shopNoticeService->getShopNoticeInfo($notice_id);

        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/superadmin/notice/update",
     *     summary="更新店铺公告",
     *     tags={"店铺公告"},
     *     description="更新店铺公告",
     *     operationId="updateShopNotice",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="notice_id",in="query",description="公告ID",required=true,type="integer"),
     *     @SWG\Parameter(name="title",in="query",description="公告标题",required=true,type="string"),
     *     @SWG\Parameter(name="type",in="query",description="公告类型",required=true,type="string"),
     *     @SWG\Parameter(name="web_link",in="query",description="网页链接",required=true,type="string"),
     *     @SWG\Parameter(name="is_publish",in="query",description="是否发布",required=true,type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/ShopNoticeInfo"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function updateShopNotice(Request $request)
    {
        $shopNoticeService = new ShopNoticeService();
        $requestData = $request->input();

        if (!$requestData['notice_id']) {
            throw new DeleteResourceFailedException("参数错误");
        }

        $filter['notice_id'] = $requestData['notice_id'];

        unset($requestData['notice_id']);
        $data = $shopNoticeService->updateShopNotice($filter, $requestData);
        return $this->response->array($data);
    }

    /**
     * @SWG\Delete(
     *     path="/superadmin/notice/delete/{notice_id}",
     *     summary="删除店铺公告",
     *     tags={"店铺公告"},
     *     description="删除店铺公告",
     *     operationId="deleteShopNotice",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="notice_id",in="path",description="公告ID",required=true,type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function deleteShopNotice($notice_id, Request $request)
    {
        $shopNoticeService = new ShopNoticeService();
        if (!$notice_id) {
            throw new DeleteResourceFailedException("参数错误");
        }

        $filter['notice_id'] = $notice_id;

        $data['status'] = $shopNoticeService->deleteShopNotice($filter);

        return $this->response->array($data);
    }

    /**
     * @SWG\Definition(
     *     definition="ShopNoticeInfo",
     *     description="店铺公告信息",
     *     type="object",
     *     @SWG\Property( property="notice_id", type="string", example="1", description="通知id "),
     *     @SWG\Property( property="type", type="string", example="notice", description="公告类型。可选值有 notice-公告;helper-店主助手"),
     *     @SWG\Property( property="title", type="string", example="test", description="通知标题"),
     *     @SWG\Property( property="web_link", type="string", example="123", description="网页链接"),
     *     @SWG\Property( property="is_publish", type="string", example="0", description="是否发布 0:不发布 1:发布"),
     *     @SWG\Property( property="created", type="string", example="1612582996", description="创建时间"),
     *     @SWG\Property( property="updated", type="string", example="1612582996", description="修改时间"),
     * )
     */
}
