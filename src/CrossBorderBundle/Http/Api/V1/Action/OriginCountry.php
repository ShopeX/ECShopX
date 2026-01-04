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

namespace CrossBorderBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CrossBorderBundle\Services\Country;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;

class OriginCountry extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/crossborder/origincountry",
     *     summary="获取产地国列表",
     *     tags={"跨境"},
     *     description="获取产地国列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer" ),
     *     @SWG\Parameter( name="keywords", in="query", description="搜索关键字（国家名称）", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="list", type="array",
     *                  @SWG\Items( type="object",
     *                     @SWG\Property(property="origincountry_id", type="string", example="26", description="产地国家id"),
     *                     @SWG\Property(property="origincountry_img_url", type="string", example="http://test.com/image/1/2020/07/21/31ce6", description="产地国家图片url"),
     *                     @SWG\Property(property="origincountry_name", type="string", example="美国", description="产地国家图名称"),
     *                     @SWG\Property(property="created", type="string", example="1599708925", description="创建时间"),
     *                     @SWG\Property(property="updated", type="string", example="1599708925", description="修改时间")
     *              )),
     *              @SWG\Property(property="total_count", type="string", example="153", description="总条数"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CrossBorderErrorRespones") ) )
     * )
     */
    public function getList(Request $request)
    {
        // 用户信息
        $userinfo = app('auth')->user()->get();

        $params = $request->all('page', 'pageSize', 'keywords');
        $Country = new Country();
        $data = $Country->getList($userinfo['company_id'], $params['page'], $params['pageSize'], $params['keywords']);

        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/crossborder/origincountry",
     *     summary="产地国添加",
     *     tags={"跨境"},
     *     description="产地国信息添加",
     *     operationId="isAdd",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="origincountry_name", in="formData", description="产地国名称", type="string" ),
     *     @SWG\Parameter( name="origincountry_img_url", in="formData", description="产地国旗url", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *          @SWG\Property(  property="data", type="object",
     *              @SWG\Property(property="status", type="boolean"),
     *              @SWG\Property(property="origincountry_id", type="string", example="26", description="产地国家id"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CrossBorderErrorRespones") ) )
     * )
     */
    public function isAdd(Request $request)
    {
        // 请求参数
        $params = $request->all('origincountry_name', 'origincountry_img_url');

        // 验证数据
        $rules = [
            'origincountry_name' => ['required|max:20', '国家名称不能为空,且长度不大于20个字。'],
            'origincountry_img_url' => ['required', '国旗图片不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        // 用户信息
        $userinfo = app('auth')->user()->get();
        // 操作数据
        $Country = new Country();
        $id = $Country->saveAdd($userinfo, $params);

        // 新增，添加
        if ($id) {
            $response['status'] = true;
            $response['origincountry_id'] = $id;
            return $this->response->array($response);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }

    /**
     * @SWG\PUT(
     *     path="/crossborder/origincountry/{origincountry_id}",
     *     summary="产地国修改",
     *     tags={"跨境"},
     *     description="产地国信息修改",
     *     operationId="isUpdate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="origincountry_name", in="formData", description="产地国名称", type="string" ),
     *     @SWG\Parameter( name="origincountry_img_url", in="formData", description="产地国旗url", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *          @SWG\Property(  property="data", type="object",
     *              @SWG\Property(property="status", type="boolean"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CrossBorderErrorRespones") ) )
     * )
     */
    public function isUpdate($origincountry_id, Request $request)
    {
        // 请求参数
        $params = $request->all('origincountry_name', 'origincountry_img_url');

        // 验证数据
        $rules = [
            'origincountry_name' => ['required|max:20', '国家名称不能为空,且长度不大于20个字。'],
            'origincountry_img_url' => ['required', '国旗图片不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        // 用户信息
        $userinfo = app('auth')->user()->get();

        // 操作数据
        $Country = new Country();
        // 已存在，修改
        if ($Country->saveUpdate($userinfo, $params, $origincountry_id)) {
            return $this->response->array(['status' => true]);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }

    /**
     * @SWG\Delete(
     *     path="/crossborder/origincountry/{origincountry_id}",
     *     summary="产地国删除",
     *     tags={"跨境"},
     *     description="产地国信息删除",
     *     operationId="isDel",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *          @SWG\Property(  property="data", type="object",
     *              @SWG\Property(property="status", type="boolean"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CrossBorderErrorRespones") ) )
     * )
     */
    public function isDel($origincountry_id)
    {
        // 用户信息
        $userinfo = app('auth')->user()->get();

        // 操作数据
        $Country = new Country();
        // 删除
        if ($Country->saveDel($userinfo, $origincountry_id)) {
            return $this->response->array(['status' => true]);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }
}
