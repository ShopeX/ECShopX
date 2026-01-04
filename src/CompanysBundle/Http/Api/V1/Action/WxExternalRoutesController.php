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
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use CompanysBundle\Services\WxExternalRoutesService;

use Dingo\Api\Exception\ResourceException;

class WxExternalRoutesController extends BaseController
{
    private $wxExternalRoutes;

    public function __construct(WxExternalRoutesService $wxExternalRoutesService)
    {
        $this->wxExternalRoutes = new $wxExternalRoutesService();
    }

    /**
     * @SWG\Get(
     *     path="/wxexternalroutes/list",
     *     summary="获取外部小程序路径列表",
     *     tags={"企业"},
     *     description="获取外部小程序路径列表",
     *     operationId="getWxExternalRoutesList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="company_id", in="query", description="公司id", type="string", required=true),
     *     @SWG\Parameter( name="page", in="query", description="页码,默认 1", type="integer", required=true),
     *     @SWG\Parameter( name="page_size", in="query", description="分页条数, 默认20", type="integer", required=true),
     *     @SWG\Parameter( name="route_name", in="query", description="页面名称", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="路径总数"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="wx_external_routes_id", type="integer", description="路径ID"),
     *                         @SWG\Property(property="wx_external_config_id", type="integer", description="配置ID"),
     *                         @SWG\Property(property="route_info", type="string", description="app_id"),
     *                         @SWG\Property(property="route_name", type="string", description="页面名称"),
     *                         @SWG\Property(property="route_desc", type="string", description="描述"),
     *                      )
     *                  ),
     *              ),
     *          ),
     *    ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getWxExternalRoutesList(Request $request)
    {
        $params = $request->all('wx_external_config_id', 'page', 'page_size', 'route_name');

        if (!isset($params['wx_external_config_id']) || empty($params['wx_external_config_id'])) {
            throw new ResourceException('配置ID必填');
        }

        $companyId = app('auth')->user()->get('company_id');
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);

        $filter['company_id'] = $companyId;
        $filter['wx_external_config_id'] = $params['wx_external_config_id'];

        if (isset($params['route_name']) && !empty($params['route_name'])) {
            $filter['route_name'] = $params['route_name'];
        }
        $result = $this->wxExternalRoutes->getWxExternalRoutesList($filter, '*', $page, $page_size);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxExternalRoutes/create",
     *     summary="创建外部小程序路径",
     *     tags={"企业"},
     *     description="创建外部小程序路径",
     *     operationId="createWxExternalRoutes",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="wx_external_config_id", in="formData", description="配置id", type="integer", required=true),
     *     @SWG\Parameter( name="route_desc", in="formData", description="描述", type="string"),
     *     @SWG\Parameter( name="route_name", in="formData", description="页面名称", type="string", required=true),
     *     @SWG\Parameter( name="route_info", in="formData", description="路径", type="string", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="wx_external_routes_id", type="integer", description="路径ID"),
     *                     @SWG\Property(property="wx_external_config_id", type="integer", description="配置ID"),
     *                     @SWG\Property(property="route_info", type="string", description="路径"),
     *                     @SWG\Property(property="route_name", type="string", description="页面名称"),
     *                     @SWG\Property(property="route_desc", type="string", description="描述"),
     *                     @SWG\Property(
                                property="created_at",
                                type="array",
                                @SWG\Items(
                                    type="object",
                                    @SWG\Property(property="date", type="string", description="日期"),
                                ),
                                description="创建时间"
                            ),
     *                     @SWG\Property(
                                property="updated_at",
                                type="array",
                                @SWG\Items(
                                    type="object",
                                    @SWG\Property(property="date", type="string", description="日期"),
                                ),
                                description="修改时间"
                            ),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function createWxExternalRoutes(Request $request)
    {
        $params = $request->all('wx_external_config_id', 'route_info', 'route_name', 'route_desc');
        $rules = [
            'wx_external_config_id' => ['required', '请填写配置ID'],
            'route_name' => ['required', '请填写页面名称'],
            'route_info' => ['required', '请填写页面路径'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');

        $result = $this->wxExternalRoutes->createWxExternalRoutes($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/WxExternalRoutes/update/{wx_external_routes_id}",
     *     summary="更新外部小程序路径",
     *     tags={"企业"},
     *     description="更新外部小程序路径",
     *     operationId="updateWxExternalRoutes",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="wx_external_routes_id", in="formData", description="路径ID", type="integer", required=true),
     *     @SWG\Parameter( name="route_name", in="formData", description="页面名称", type="string", required=true),
     *     @SWG\Parameter( name="route_info", in="formData", description="页面路径", type="string", required=true),
     *     @SWG\Parameter( name="route_desc", in="formData", description="描述", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="wx_external_routes_id", type="integer", description="路径ID"),
     *                     @SWG\Property(property="wx_external_config_id", type="integer", description="配置ID"),
     *                     @SWG\Property(property="route_info", type="string", description="页面路径"),
     *                     @SWG\Property(property="route_name", type="string", description="页面名称"),
     *                     @SWG\Property(property="route_desc", type="string", description="描述"),
     *                     @SWG\Property(
                                property="created_at",
                                type="array",
                                @SWG\Items(
                                    type="object",
                                    @SWG\Property(property="date", type="string", description="日期"),
                                ),
                                description="创建时间"
                            ),
     *                     @SWG\Property(
                                property="updated_at",
                                type="array",
                                @SWG\Items(
                                    type="object",
                                    @SWG\Property(property="date", type="string", description="日期"),
                                ),
                                description="修改时间"
                            ),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function updateWxExternalRoutes(Request $request)
    {
        $params = $request->all('wx_external_routes_id', 'route_desc', 'route_name', 'route_info', 'wx_external_config_id');
        $rules = [
            'wx_external_routes_id' => ['required', '请填写路径ID'],
            'route_name' => ['required', '请填写页面名称'],
            'route_info' => ['required', '请填写小程序路径'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');

        $result = $this->wxExternalRoutes->updateWxExternalRoutes($companyId, $params['wx_external_routes_id'], $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/wxexternalconfig/{wx_external_routes_id}",
     *     summary="删除外部小程序路径",
     *     tags={"企业"},
     *     description="删除外部小程序路径",
     *     operationId="deleteWxExternalRoutes",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="wx_external_routes_id", in="path", description="路径ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态"),
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function deleteWxExternalRoutes(Request $request, $wx_external_config_id)
    {
        if (intval($wx_external_config_id) <= 0) {
            throw new ResourceException('路径不存在');
        }

        $companyId = app('auth')->user()->get('company_id');

        $result = $this->wxExternalRoutes->deleteWxExternalRoutes($companyId, intval($wx_external_config_id));

        return $this->response->array(['status' => $result]);
    }
}
