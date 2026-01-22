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
use CompanysBundle\Services\WxExternalConfigService;

// use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class WxExternalConfigController extends BaseController
{
    private $wxExternalConfig;

    public function __construct(WxExternalConfigService $wxExternalConfigService)
    {
        $this->wxExternalConfig = new $wxExternalConfigService();
    }

    /**
     * @SWG\Get(
     *     path="/wxexternalconfig/list",
     *     summary="获取外部小程序配置列表",
     *     tags={"企业"},
     *     description="获取外部小程序配置列表",
     *     operationId="getWxExternalConfigList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="company_id", in="query", description="公司id", type="string", required=true),
     *     @SWG\Parameter( name="page", in="query", description="页码,默认 1", type="integer", required=true),
     *     @SWG\Parameter( name="page_size", in="query", description="分页条数, 默认20", type="integer", required=true),
     *     @SWG\Parameter( name="app_name", in="query", description="小程序名称", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="配置总数"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="wx_external_config_id", type="integer", description="配置ID"),
     *                         @SWG\Property(property="app_id", type="string", description="app_id"),
     *                         @SWG\Property(property="app_name", type="string", description="小程序名称"),
     *                         @SWG\Property(property="app_desc", type="string", description="描述"),
     *                      )
     *                  ),
     *              ),
     *          ),
     *    ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getWxExternalConfigList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'app_name');

        $companyId = app('auth')->user()->get('company_id');
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);

        $filter['company_id'] = $companyId;

        if (isset($params['app_name']) && !empty($params['app_name'])) {
            $filter['app_name'] = $params['app_name'];
        }
        $result = $this->wxExternalConfig->getWxExternalConfigList($filter, '*', $page, $page_size);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxexternalconfig/create",
     *     summary="创建外部小程序配置",
     *     tags={"企业"},
     *     description="创建外部小程序配置",
     *     operationId="createWxExternalConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="company_id", in="formData", description="公司id", type="string", required=true),
     *     @SWG\Parameter( name="app_id", in="formData", description="app_id", type="string", required=true),
     *     @SWG\Parameter( name="app_name", in="formData", description="小程序名称", type="string", required=true),
     *     @SWG\Parameter( name="app_desc", in="formData", description="描述", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="wx_external_config_id", type="integer", description="配置ID"),
     *                     @SWG\Property(property="app_id", type="string", description="app_id"),
     *                     @SWG\Property(property="app_name", type="string", description="小程序名称"),
     *                     @SWG\Property(property="app_desc", type="string", description="描述"),
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
    public function createWxExternalConfig(Request $request)
    {
        $params = $request->all('app_id', 'app_desc', 'app_name');
        $rules = [
            'app_id' => ['required', '请填写app_id'],
            'app_name' => ['required', '请填小程序名称'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');

        $result = $this->wxExternalConfig->createWxExternalConfig($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wxexternalconfig/update/{wx_external_config_id}",
     *     summary="更新外部小程序配置",
     *     tags={"企业"},
     *     description="更新外部小程序配置",
     *     operationId="updateWxExternalConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="wx_external_config_id", in="formData", description="配置ID", type="integer", required=true),
     *     @SWG\Parameter( name="app_name", in="formData", description="小程序名称", type="string", required=true),
     *     @SWG\Parameter( name="app_desc", in="formData", description="描述", type="string", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="wx_external_config_id", type="integer", description="配置ID"),
     *                     @SWG\Property(property="app_id", type="string", description="app_id"),
     *                     @SWG\Property(property="app_name", type="string", description="小程序名称"),
     *                     @SWG\Property(property="app_desc", type="string", description="描述"),
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
    public function updateWxExternalConfig(Request $request, $wx_external_config_id)
    {
        $params = $request->all('app_desc', 'app_name', 'app_id');
        $params['wx_external_config_id'] = $wx_external_config_id;
        $rules = [
            'wx_external_config_id' => ['required', '请填写配置ID'],
            'app_name' => ['required', '请填写小程序名称'],
            // 'app_desc' => ['required', '请填写小程序描述'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');

        $result = $this->wxExternalConfig->updateWxExternalConfig($companyId, $params['wx_external_config_id'], $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxexternalconfigroutes/list",
     *     summary="获取外部小程序配置路径列表",
     *     tags={"企业"},
     *     description="获取外部小程序配置路径列表",
     *     operationId="getConfigRoutesList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="company_id", in="query", description="公司id", type="string", required=true),
     *     @SWG\Parameter( name="page", in="query", description="页码,默认 1", type="integer", required=true),
     *     @SWG\Parameter( name="page_size", in="query", description="分页条数, 默认20", type="integer", required=true),
     *     @SWG\Parameter( name="app_id", in="query", description="app_id", type="string"),
     *     @SWG\Parameter( name="route_info", in="query", description="页面路径", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="配置总数"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="wx_external_config_id", type="integer", description="配置ID"),
     *                         @SWG\Property(property="app_id", type="string", description="app_id"),
     *                         @SWG\Property(property="app_name", type="string", description="小程序名称"),
     *                         @SWG\Property(property="app_desc", type="string", description="小程序描述"),
     *                         @SWG\Property(property="route_info", type="string", description="页面路径"),
     *                         @SWG\Property(property="route_name", type="string", description="路径名称"),
     *                         @SWG\Property(property="route_desc", type="string", description="路径描述"),
     *                      )
     *                  ),
     *              ),
     *          ),
     *    ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getConfigRoutesList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'app_id', 'route_info');

        $companyId = app('auth')->user()->get('company_id');
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);

        $filter['company_id'] = $companyId;

        if (isset($params['app_id']) && !empty($params['app_id'])) {
            $filter['app_id'] = $params['app_id'];
        }
        if (isset($params['route_info']) && !empty($params['route_info'])) {
            $filter['route_info'] = $params['route_info'];
        }
        $result = $this->wxExternalConfig->getConfigRoutesList($filter, $page, $page_size);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/wxexternalconfig/{wx_external_config_id}",
     *     summary="删除外部小程序配置",
     *     tags={"企业"},
     *     description="删除外部小程序配置",
     *     operationId="deleteWxExternalConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="wx_external_config_id", in="path", description="配置ID", type="integer", required=true),
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
    public function deleteWxExternalConfig(Request $request, $wx_external_config_id)
    {
        if (intval($wx_external_config_id) <= 0) {
            throw new ResourceException('配置不存在');
        }

        $companyId = app('auth')->user()->get('company_id');

        $result = $this->wxExternalConfig->deleteWxExternalConfig($companyId, intval($wx_external_config_id));

        return $this->response->array(['status' => $result]);
    }
}
