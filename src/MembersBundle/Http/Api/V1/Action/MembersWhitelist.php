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

namespace MembersBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

use MembersBundle\Services\MembersWhitelistService;

class MembersWhitelist extends Controller
{
    public $whitelistService;

    public function __construct()
    {
        $this->whitelistService = new MembersWhitelistService();
        $this->limit = 100;
    }

    /**
     * @SWG\Get(
     *     path="/members/whitelist/list",
     *     summary="获取会员白名单列表",
     *     tags={"会员"},
     *     description="获取会员白名单列表",
     *     operationId="getLists",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页数",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="显示数量",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="4", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items(
     *                          @SWG\Property( property="whitelist_id", type="string", example="11", description="白名单id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="mobile", type="string", example="15121097923", description="手机号"),
     *                          @SWG\Property( property="name", type="string", example="22", description="名称"),
     *                          @SWG\Property( property="created", type="string", example="1606199963", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1606199963", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getLists(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);

        $authdata = app('auth')->user()->get();

        //验证参数todo
        $postdata = $request->all();
        $postdata['page'] = $page;
        $postdata['pageSize'] = $limit;
        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:100','每页显示数量最大100'],
            'mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
        ];
        $error = validator_params($postdata, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = $authdata['company_id'];
        $filter = ['company_id' => $companyId];
        if ($postdata['mobile'] ?? false) {
            $filter['mobile'] = $postdata['mobile'];
        }
        $orderBy = ['created' => 'DESC','whitelist_id' => 'DESC'];
        $result = $this->whitelistService->lists($filter, '*', $page, $limit, $orderBy);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['datapass_block'] = $datapassBlock;
        if ($result['list']) {
            foreach ($result['list'] as $key => $value) {
                if ($datapassBlock) {
                    $result['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
                    $result['list'][$key]['name'] = data_masking('truename', (string) $value['name']);
                }
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/members/whitelist/{if}",
     *     summary="获取会员白名单信息",
     *     tags={"会员"},
     *     description="获取会员白名单信息",
     *     operationId="getInfo",
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         description="白名单id",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 @SWG\Property( property="whitelist_id", type="string", example="11", description="白名单id"),
     *                 @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                 @SWG\Property( property="mobile", type="string", example="15121097923", description="手机号"),
     *                 @SWG\Property( property="name", type="string", example="22", description="名称"),
     *                 @SWG\Property( property="created", type="string", example="1606199963", description=""),
     *                 @SWG\Property( property="updated", type="string", example="1606199963", description="修改时间"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $params = $request->all('id');
        if (!$params['id']) {
            throw new ResourceException(trans('MembersBundle/Members.id_required'));
        }
        $companyId = app('auth')->user()->get('company_id');

        $filter = [
            'whitelist_id' => $params['id'],
            'company_id' => $companyId,
        ];
        $result = $this->whitelistService->getInfo($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/members/whitelist",
     *     summary="创建会员白名单",
     *     tags={"会员"},
     *     description="创建会员白名单",
     *     operationId="createData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号码",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         description="姓名",
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 @SWG\Property( property="whitelist_id", type="string", example="11", description="白名单id"),
     *                 @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                 @SWG\Property( property="mobile", type="string", example="15121097923", description="手机号"),
     *                 @SWG\Property( property="name", type="string", example="22", description="名称"),
     *                 @SWG\Property( property="created", type="string", example="1606199963", description=""),
     *                 @SWG\Property( property="updated", type="string", example="1606199963", description="修改时间"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function createData(Request $request)
    {
        $params = $request->all("mobile", "name");

        $rules = [
            'mobile' => ['required', '手机号必填'],
            'name' => ['required', '姓名必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        if (!ismobile($params['mobile'])) {
            throw new ResourceException(trans('MembersBundle/Members.invalid_mobile'));
        }

        $params['company_id'] = app('auth')->user()->get('company_id');

        $result = $this->whitelistService->createData($params);
        $result['mobile'] = fixedencrypt($result['mobile']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/members/whitelist/{id}",
     *     summary="更改会员白名单信息",
     *     tags={"会员"},
     *     description="更改会员白名单信息",
     *     operationId="updateData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 @SWG\Property( property="whitelist_id", type="string", example="11", description="白名单id"),
     *                 @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                 @SWG\Property( property="mobile", type="string", example="15121097923", description="手机号"),
     *                 @SWG\Property( property="name", type="string", example="22", description="名称"),
     *                 @SWG\Property( property="created", type="string", example="1606199963", description=""),
     *                 @SWG\Property( property="updated", type="string", example="1606199963", description="修改时间"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateData($id, Request $request)
    {
        $params = $inputdata = $request->all('name');
        $params['id'] = $id;
        $rules = [
            'id' => ['required|min:1', '缺少id'],
            'name' => ['required|min:1', '缺少姓名'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'company_id' => $companyId,
            'whitelist_id' => $params['id'],
        ];
        unset($params['id']);

        $result = $this->whitelistService->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/members/whitelist/{id}",
     *     summary="删除会员白名单信息",
     *     tags={"会员"},
     *     description="删除会员白名单信息",
     *     operationId="updateData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteData($id)
    {
        $params['id'] = $id;
        $rules = [
            'id' => ['required|min:1', '缺少id'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'company_id' => $companyId,
            'whitelist_id' => $params['id'],
        ];

        $result = $this->whitelistService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }
}
