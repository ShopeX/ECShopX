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

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use CompanysBundle\Services\CompanysService;
use CompanysBundle\Services\RegionauthService;

class Regionauth extends BaseController
{
    /** @var $companysService */

    /**
     * @param companysService $companysService
     */
    public function __construct(CompanysService $companysService)
    {
    }

    /**
     * @SWG\Definition(
     *     definition="Regionauth",
     *     type="object",
     *     @SWG\Property(property="regionauth_id", type="string", description="地区id", example="3"),
     *     @SWG\Property(property="regionauth_name", type="string", description="地区名称", example="华北"),
     *     @SWG\Property(property="state", type="integer", description="数据状态", example="1"),
     *     @SWG\Property(property="company_id", type="integer", description="公司id", example="1"),
     *     @SWG\Property(property="created", type="string", description="创建时间", example="1603278117"),
     *     @SWG\Property(property="updated", type="string",description="更新时间", example="1603278117")
     * )
     */




    /**
     * @SWG\GET(
     *     path="/regionauth",
     *     summary="地区权限列表",
     *     tags={"企业"},
     *     description="地区权限列表获取",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer" ),
     *     @SWG\Parameter( name="state", in="query", description="状态，1正常，0禁用", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="数量", example="1"),
     *                 @SWG\Property(property="list", type="array", description="列表",
     *                     @SWG\Items(
     *                         ref="#/definitions/Regionauth"
     *                     )
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getlist(Request $request)
    {
        $userinfo = app('auth')->user()->get();
        $params = $request->all('page', 'pageSize', 'regionauth_id', 'state');
        $RegionauthService = new RegionauthService();
        $filter['company_id'] = $userinfo['company_id'];
        if (isset($params['regionauth_id'])) {
            $filter['regionauth_id'] = intval($params['regionauth_id']);
        }
        $filter['state'] = isset($params['state']) ? $params['state'] : [0, 1];
        $data = $RegionauthService->getlist($filter, $params['page'], $params['pageSize']);

        return $this->response->array($data);
    }

    // 地区权限详情-暂时无用
    public function getinfo($id, Request $request)
    {
        dump('地区权限详情');
    }


    /**
     * @SWG\Post(
     *     path="/regionauth",
     *     summary="地区权添加",
     *     tags={"企业"},
     *     description="地区权添加",
     *     operationId="createData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true, ),
     *     @SWG\Parameter( name="regionauth_name", in="query", description="地区名称", type="string", ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function add(Request $request)
    {
        $userinfo = app('auth')->user()->get();
        $params = $request->all('regionauth_name');

        // 验证数据
        $rules = [
            'regionauth_name' => ['required|max:50', '地区名称不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }


        $RegionauthService = new RegionauthService();
        $id = $RegionauthService->isadd($userinfo['company_id'], $params);

        // 新增，添加
        if ($id) {
            $response['status'] = true;
            return $this->response->array($response);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }

    /**
     * @SWG\Put(
     *     path="/regionauth/{regionauth_id}",
     *     summary="地区权修改",
     *     tags={"企业"},
     *     description="地区权修改",
     *     operationId="update",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true, ),
     *     @SWG\Parameter( name="regionauth_name", in="query", description="地区名称", type="string", ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function update($id, Request $request)
    {
        $userinfo = app('auth')->user()->get();
        $params = $request->all('regionauth_name');

        // 验证数据
        $rules = [
            'regionauth_name' => ['required|max:50', '地区名称不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $params['regionauth_id'] = $id;


        $RegionauthService = new RegionauthService();
        $id = $RegionauthService->update($userinfo['company_id'], $params);

        // 修改
        if ($id) {
            $response['status'] = true;
            return $this->response->array($response);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }

    /**
     * @SWG\Delete(
     *     path="/regionauth{regionauth_id}",
     *     summary="地区权删除",
     *     tags={"企业"},
     *     description="地区权删除",
     *     operationId="del",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true, ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function del($id)
    {
        $userinfo = app('auth')->user()->get();

        // 操作数据
        $RegionauthService = new RegionauthService();
        // 删除
        if ($RegionauthService->del($userinfo, $id)) {
            return $this->response->array(['status' => true]);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }


    /**
     * @SWG\Put(
     *     path="/regionauth/enable/{regionauth_id}",
     *     summary="状态操作",
     *     tags={"企业"},
     *     description="状态操作",
     *     operationId="enable",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true, ),
     *     @SWG\Parameter( name="enable", in="query", description="是否启用，1启用，0禁用", type="string", ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function enable($id, Request $request)
    {
        $userinfo = app('auth')->user()->get();
        $params = $request->all('enable');
        // 操作数据
        $RegionauthService = new RegionauthService();
        // 操作
        if ($RegionauthService->enable($userinfo, $id, $params)) {
            return $this->response->array(['status' => true]);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }
}
