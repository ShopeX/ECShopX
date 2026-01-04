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

namespace DataCubeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use DataCubeBundle\Services\MonitorsService;

class SourcesMonitors extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/datacube/monitors",
     *     summary="获取页面监控列表",
     *     tags={"统计"},
     *     description="获取页面监控列表",
     *     operationId="getSourcesMonitors",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="page", in="header", description="页码", type="string" ),
     *     @SWG\Parameter( name="pageSize", in="header", description="pagesize", type="string" ),
     *     @SWG\Parameter( name="regionauth_id", in="header", description="区域id", type="string" ),
     *     @SWG\Parameter( name="monitorPath", in="header", description="页面路径", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="18", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="monitorId", type="string", example="38", description=""),
     *                          @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                          @SWG\Property( property="wxappid", type="string", example="wx912913df9fef6ddd", description="小程序appid"),
     *                          @SWG\Property( property="nickName", type="string", example="51打赏", description=""),
     *                          @SWG\Property( property="monitorPath", type="string", example="pages/item/espier-detail", description=""),
     *                          @SWG\Property( property="monitorPathParams", type="string", example="id=5012", description=""),
     *                          @SWG\Property( property="created", type="string", example="1604542317", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1604542317", description="修改时间"),
     *                          @SWG\Property( property="pageName", type="string", example="商品详情页", description=""),
     *                          @SWG\Property( property="regionauthId", type="string", example="1", description="区域id"),
     *                          @SWG\Property( property="regionauth_name", type="string", example="上海佛罗伦萨小镇", description="小镇名称"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getSourcesMonitors(Request $request)
    {
        // Built with ShopEx Framework
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取监控列表出错.', $validator->errors());
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        if (isset($inputData['wxappid'])) {
            $params['wxappid'] = $inputData['wxappid'];
        }
        if (isset($inputData['regionauth_id'])) {
            $params['regionauth_id'] = $inputData['regionauth_id'];
        }
        if (isset($inputData['monitorPath'])) {
            $params['monitor_path'] = $inputData['monitorPath'];
        }

        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];
        $itemsService = new MonitorsService();
        $result = $itemsService->getMonitorsList($params, $page, $pageSize);
        
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/datacube/monitors/{monitor_id}",
     *     summary="获取页面监控详情",
     *     tags={"统计"},
     *     description="获取页面监控详情",
     *     operationId="getMonitorsDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="monitor_id", in="path", description="监控id", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="monitor_id", type="string", example="30", description="监控id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="wxappid", type="string", example="wx2b853ad44dde3dc6", description="小程序appid"),
     *                  @SWG\Property( property="nick_name", type="string", example="好想拼", description="昵称"),
     *                  @SWG\Property( property="monitor_path", type="string", example="marketing/pages/service/store-list", description="监控页面"),
     *                  @SWG\Property( property="monitor_path_params", type="string", example="", description="监控页面的参数"),
     *                  @SWG\Property( property="created", type="string", example="1585226076", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1585226076", description="修改时间"),
     *                  @SWG\Property( property="page_name", type="string", example="门店列表", description="页面名称"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getMonitorsDetail($monitor_id)
    {
        $validator = app('validator')->make(['monitor_id' => $monitor_id], [
            'monitor_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取监控页面详情出错.', $validator->errors());
        }
        $monitorService = new MonitorsService();
        $result = $monitorService->getMonitorsDetail($monitor_id);
        $company_id = app('auth')->user()->get('company_id');
        if ($company_id != $result['company_id']) {
            throw new ResourceException('获取监控页面信息有误，请确认您的监控页面的ID.');
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/datacube/monitors/{monitor_id}",
     *     summary="删除监控页面",
     *     tags={"统计"},
     *     description="删除监控页面",
     *     operationId="deleteMonitors",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="monitor_id", in="path", description="来源id", required=true, type="integer" ),
     *     @SWG\Response(  response=200, description="成功返回结构", @SWG\Schema(
     *             @SWG\Property( property="data", type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="monitor_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function deleteMonitors($monitor_id)
    {
        $params['monitor_id'] = $monitor_id;
        $validator = app('validator')->make($params, [
            'monitor_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除监控页面出错.', $validator->errors());
        }

        $company_id = app('auth')->user()->get('company_id');
        $monitorService = new MonitorsService();
        $params = [
            'monitor_id' => $monitor_id,
            'company_id' => $company_id,
        ];
        $result = $monitorService->deleteMonitors($params);

        return $this->response->noContent();
    }

    /**
     * @SWG\Post(
     *     path="/datacube/monitors",
     *     summary="添加监控链接",
     *     tags={"统计"},
     *     description="添加监控链接",
     *     operationId="addMonitors",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="wxappid", in="query", description="来源名称", required=true, type="string" ),
     *     @SWG\Parameter( name="monitor_path", in="query", description="监控路径", required=true, type="string" ),
     *     @SWG\Parameter( name="monitor_path_params", in="query", description="监控参数", required=true, type="string" ),
     *     @SWG\Parameter( name="regionauth_id", in="query", description="区域id", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema(  @SWG\Property( property="data", type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="source_id", type="integer"),
     *                     @SWG\Property(property="source_name", type="string"),
     *                     @SWG\Property(property="company_id", type="integer")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function addMonitors(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'wxappid' => 'required',
            'monitor_path' => 'required',
            'monitor_path_params.*.param_name' => 'filled|required',
            'monitor_path_params.*.value' => 'required_with:monitor_path_params.*.param_name',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('添加监控链接出错.', $validator->errors());
        }

        $monitorsService = new MonitorsService();
        $authInfo = app('auth')->user()->get();
        $data = [
            'company_id' => $authInfo['company_id'],
            'monitor_id' => $params['monitor_id'],
            'wxappid' => $params['wxappid'],
            'monitor_path' => $params['monitor_path'],
            'monitor_path_params' => '',
            'page_name' => $params['page_name'] ?? '',
            'regionauth_id' => $params['regionauth_id'] ?? '',
        ];
        if (isset($params['monitor_path_params'])) {
            $urlParams = [];
            foreach ($params['monitor_path_params'] as $v) {
                $urlParams[$v['param_name']] = $v['value'];
            }
            $data['monitor_path_params'] = http_build_query($urlParams);
        }
        $result = $monitorsService->addMonitors($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/datacube/monitorsRelSources",
     *     summary="添加监控页面关联来源",
     *     tags={"统计"},
     *     description="添加监控页面关联来源",
     *     operationId="relSources",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="monitor_id", in="query", description="监控id", required=true, type="string" ),
     *     @SWG\Parameter( name="sourceIds", in="query", description="来源ids", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *             @SWG\Property( property="data", type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="source_id", type="integer"),
     *                     @SWG\Property(property="source_name", type="string"),
     *                     @SWG\Property(property="company_id", type="integer")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function relSources(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'monitor_id' => 'required|min:1',
            'sourceIds' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('保存监控页面关联来源出错.', $validator->errors());
        }

        $monitorsService = new MonitorsService();
        $authInfo = app('auth')->user()->get();
        $data = [
            'company_id' => $authInfo['company_id'],
            'monitor_id' => $params['monitor_id'],
            'sourceIds' => $params['sourceIds'],
        ];
        $result = $monitorsService->relSources($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/datacube/monitorsRelSources/{monitor_id}",
     *     summary="获取监控页面关联来源信息",
     *     tags={"统计"},
     *     description="获取监控页面关联来源信息",
     *     operationId="getRelSources",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="monitor_id", in="path", description="监控id", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *             @SWG\Property( property="data",  type="array",
     *                 @SWG\Items( type="object", @SWG\Property(property="source_id", type="string") )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getRelSources($monitor_id)
    {
        $params = ['monitor_id' => $monitor_id];
        $validator = app('validator')->make($params, [
            'monitor_id' => 'required|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取监控页面关联来源出错.', $validator->errors());
        }

        $monitorsService = new MonitorsService();
        $authInfo = app('auth')->user()->get();
        $data = [
            'company_id' => $authInfo['company_id'],
            'monitor_id' => $monitor_id,
        ];
        $result = $monitorsService->getRelSources($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/datacube/monitorsRelSources/{monitor_id}/{source_id}",
     *     summary="删除监控页面的某个来源",
     *     tags={"统计"},
     *     description="删除监控页面的某个来源",
     *     operationId="getRelSources",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="monitor_id",
     *         in="path",
     *         description="监控id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="source_id",
     *         in="path",
     *         description="来源id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="source_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */

    public function deleteRelSources($monitor_id, $source_id)
    {
        $params = [
            'monitor_id' => $monitor_id,
            'source_id' => $source_id,
        ];
        $validator = app('validator')->make($params, [
            'monitor_id' => 'required|min:1',
            'source_id' => 'required|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除监控页面关联来源出错.', $validator->errors());
        }

        $monitorsService = new MonitorsService();
        $authInfo = app('auth')->user()->get();
        $data = [
            'company_id' => $authInfo['company_id'],
            'monitor_id' => $monitor_id,
            'source_id' => $source_id,
        ];
        $result = $monitorsService->deleteRelSources($data);

        return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/datacube/monitorsstats",
     *     summary="获取监控页面的来源统计",
     *     tags={"统计"},
     *     description="获取监控页面的来源统计",
     *     operationId="getStats",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="monitor_id",
     *         in="path",
     *         description="监控id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="date_type",
     *         in="path",
     *         description="默认时间范围",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="begin_date",
     *         in="path",
     *         description="开始时间",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="end_date",
     *         in="path",
     *         description="结束时间",
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="stats_total", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="total_pv", type="string", example="1", description="监控级别pv"),
     *                          @SWG\Property( property="total_uv", type="string", example="1", description="监控级别uv"),
     *                          @SWG\Property( property="total_visitor", type="string", example="1", description="监控级别游客访问量"),
     *                          @SWG\Property( property="total_member_visitor", type="string", example="1", description="监控级别会员访客"),
     *                          @SWG\Property( property="total_pay_num", type="string", example="18", description="监控级别支付人数"),
     *                          @SWG\Property( property="total_pay_amount", type="string", example="558.00", description="监控级别支付总价"),
     *                          @SWG\Property( property="total_register_num", type="string", example="55.80", description="监控级别注册量"),
     *                          @SWG\Property( property="total_entries_num", type="string", example="558.00", description="监控级别支付单数"),
     *                          @SWG\Property( property="total_register_entries_rate", type="string", example="55.80", description="监控级别转化率"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="stats_list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="monitor_id", type="string", example="1", description="monitor_id"),
     *                          @SWG\Property( property="source_id", type="string", example="1", description="source_id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="company_id"),
     *                          @SWG\Property( property="source_name", type="string", example="1", description="source_name"),
     *                          @SWG\Property( property="total_pv", type="string", example="1", description="sourceid级别pv"),
     *                          @SWG\Property( property="total_uv", type="string", example="1", description="sourceid级别uv"),
     *                          @SWG\Property( property="total_visitor", type="string", example="1", description="sourceid级别游客访问量"),
     *                          @SWG\Property( property="total_member_visitor", type="string", example="1", description="sourceid级别会员访客"),
     *                          @SWG\Property( property="total_pay_num", type="string", example="18", description="sourceid级别支付人数"),
     *                          @SWG\Property( property="total_pay_amount", type="string", example="558.00", description="sourceid级别支付总价"),
     *                          @SWG\Property( property="register_num", type="string", example="55.80", description="sourceid级别注册量"),
     *                          @SWG\Property( property="entries_num", type="string", example="558.00", description="sourceid级别支付单数"),
     *                          @SWG\Property( property="register_entries_rate", type="string", example="55.80", description="sourceid级别转化率"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getStats(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'monitor_id' => 'required|min:1',
            'date_type' => 'required|in:today,yesterday,before7days,before30days,beforemonth,custom',
            'begin_date' => 'required_if:date_type,custom',
            'end_date' => 'required_if:date_type,custom',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取监控页面的来源统计出错.', $validator->errors());
        }

        $monitorsService = new MonitorsService();
        $authInfo = app('auth')->user()->get();
        $data = [
            'company_id' => $authInfo['company_id'],
            'monitor_id' => $params['monitor_id'],
            'date_type' => $params['date_type'],
        ];
        if (isset($params['begin_date']) && isset($params['end_date'])) {
            $data['begin_date'] = $params['begin_date'];
            $data['end_date'] = $params['end_date'];
        }
        $result = $monitorsService->getStats($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/datacube/monitorsWxaCode64",
     *     summary="获取监控的小程序码参数",
     *     tags={"统计"},
     *     description="获取监控的小程序码参数",
     *     operationId="getMonitorWxaCode64",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="monitor_id",
     *         in="path",
     *         description="监控id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="source_id",
     *         in="path",
     *         description="来源id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="source_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getMonitorWxaCode64(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'monitor_id' => 'required|min:1',
            'source_id' => 'required|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取小程序码参数出错，请检查.', $validator->errors());
        }
        $monitorsService = new MonitorsService();
        $result = $monitorsService->getMonitorWxaCode($params['monitor_id'], $params['source_id'], 1);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/datacube/monitorsWxaCodeStream",
     *     summary="获取监控的小程序码流信息",
     *     tags={"统计"},
     *     description="获取监控的小程序码流信息",
     *     operationId="getMonitorWxaCodeStream",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="monitor_id",
     *         in="path",
     *         description="监控id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="source_id",
     *         in="path",
     *         description="来源id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="source_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getMonitorWxaCodeStream(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'monitor_id' => 'required|min:1',
            'source_id' => 'required|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取小程序码参数出错，请检查.', $validator->errors());
        }
        $monitorsService = new MonitorsService();
        $result = $monitorsService->getMonitorWxaCode($params['monitor_id'], $params['source_id']);
        return response($result)->header('content-type', 'image/jpeg');
    }
}
