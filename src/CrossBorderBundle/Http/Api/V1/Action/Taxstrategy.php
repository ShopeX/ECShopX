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
use CrossBorderBundle\Services\Taxstrategy as Strategy;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;

class Taxstrategy extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/crossborder/taxstrategy",
     *     summary="税费策略列表",
     *     tags={"跨境"},
     *     description="税费策略列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer" ),
     *     @SWG\Parameter( name="keywords", in="query", description="搜索关键字（策略名称）", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="list", type="array",
     *                  @SWG\Items( type="object",
     *                     @SWG\Property(property="id", type="string", example="5", description="策略id"),
     *                     @SWG\Property(property="taxstrategy_name", type="string", example="面膜", description="策略名称"),
     *                     @SWG\Property(property="created", type="string", example="1599708925", description="创建时间"),
     *                     @SWG\Property(property="updated", type="string", example="1599708925", description="更新时间")
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
        $Strategy = new Strategy();
        $data = $Strategy->getList($userinfo['company_id'], $params['page'], $params['pageSize'], $params['keywords']);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/crossborder/taxstrategy/{taxstrategy_id}",
     *     summary="税费策略详情",
     *     tags={"跨境"},
     *     description="税费策略详情",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *              @SWG\Property(property="id", type="string", example="5", description="策略id"),
     *              @SWG\Property(property="taxstrategy_name", type="string", example="面膜", description="策略名称"),
     *              @SWG\Property(property="state", type="string", example="1", description="状态:1正常"),
     *              @SWG\Property(property="created", type="string", example="1599708925", description="创建时间"),
     *              @SWG\Property(property="updated", type="string", example="1599708925", description="更新时间"),
     *              @SWG\Property(property="taxstrategy_content", type="array",
     *                  @SWG\Items( type="object",
     *                     @SWG\Property(property="start", type="string", example="0", description="价格区间:起始价格"),
     *                     @SWG\Property(property="end", type="string", example="100", description="价格区间:结束价格"),
     *                     @SWG\Property(property="tax_rate", type="string", example="20", description="对应区间的税率"),
     *              )),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CrossBorderErrorRespones") ) )
     * )
     */
    public function getInfo($taxstrategy_id)
    {
        // 用户信息
        $userinfo = app('auth')->user()->get();

        $filter['id'] = $taxstrategy_id;
        $filter['company_id'] = $userinfo['company_id'];
        $filter['state'] = 1;

        $Strategy = new Strategy();
        $data = $Strategy->getInfo($filter);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/crossborder/taxstrategy",
     *     summary="税费策略添加",
     *     tags={"跨境"},
     *     description="税费策略添加",
     *     operationId="isAdd",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="taxstrategy_name", in="formData", description="规则名称", type="string" ),
     *     @SWG\Parameter( name="taxstrategy_content", in="formData", description="规则", type="array", @SWG\items(type="string")),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *          @SWG\Property(  property="data", type="object",
     *              @SWG\Property(property="status", type="boolean"),
     *              @SWG\Property(property="taxstrategy_id", type="string"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CrossBorderErrorRespones") ) )
     * )
     */
    public function isAdd(Request $request)
    {

        // 请求参数
        $params = $request->all('taxstrategy_name', 'taxstrategy_content');

        // 验证数据
        $rules = [
            'taxstrategy_name' => ['required|max:20', '策略名称不能为空,且长度不大于20个字。'],
            'taxstrategy_content' => ['required', '策略内容不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        // 用户信息
        $userinfo = app('auth')->user()->get();

        $add_data['taxstrategy_name'] = $params['taxstrategy_name'];
        $add_data['taxstrategy_content'] = json_encode($params['taxstrategy_content']);

        // 操作数据
        $Strategy = new Strategy();
        $id = $Strategy->addSave($userinfo['company_id'], $add_data);

        // 新增，添加
        if ($id) {
            $response['status'] = true;
            $response['taxstrategy_id'] = $id;
            return $this->response->array($response);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }

    /**
     * @SWG\Put(
     *     path="/crossborder/taxstrategy/{taxstrategy_id}",
     *     summary="税费策略修改",
     *     tags={"跨境"},
     *     description="税费策略修改",
     *     operationId="isUpdate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="taxstrategy_name", in="formData", description="规则名称", type="string" ),
     *     @SWG\Parameter( name="taxstrategy_content", in="formData", description="规则", type="array", @SWG\items(type="string")),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *          @SWG\Property(  property="data", type="object",
     *              @SWG\Property(property="status", type="boolean"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CrossBorderErrorRespones") ) )
     * )
     */
    public function isUpdate($taxstrategy_id, Request $request)
    {
        // 请求参数
        $params = $request->all('taxstrategy_name', 'taxstrategy_content');

        // 验证数据
        $rules = [
            'taxstrategy_name' => ['required|max:20', '策略名称不能为空,且长度不大于20个字。'],
            'taxstrategy_content' => ['required', '策略内容不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        // 用户信息
        $userinfo = app('auth')->user()->get();
        $filter['company_id'] = $userinfo['company_id'];
        $filter['id'] = $taxstrategy_id;
        $filter['state'] = '1';

        $update_data['taxstrategy_name'] = $params['taxstrategy_name'];
        $update_data['taxstrategy_content'] = json_encode($params['taxstrategy_content']);
        $update_data['updated'] = time();

        // 操作数据
        $Strategy = new Strategy();
        if ($Strategy->updateSave($filter, $update_data)) {
            return $this->response->array(['status' => true]);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }

    /**
     * @SWG\Delete(
     *     path="/crossborder/taxstrategy/{taxstrategy_id}",
     *     summary="税费策略删除",
     *     tags={"跨境"},
     *     description="税费策略删除",
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
    public function isDel($taxstrategy_id)
    {
        // 用户信息
        $userinfo = app('auth')->user()->get();
        $filter['company_id'] = $userinfo['company_id'];
        $filter['id'] = $taxstrategy_id;
        $update_data['state'] = '-1';
        $update_data['updated'] = time();

        // 操作数据
        $Strategy = new Strategy();
        if ($Strategy->updateSave($filter, $update_data)) {
            return $this->response->array(['status' => true]);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }
}
