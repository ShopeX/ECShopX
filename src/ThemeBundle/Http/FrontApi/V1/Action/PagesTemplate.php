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

namespace ThemeBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use TdksetBundle\Services\TdkGlobalService;
use ThemeBundle\Services\PagesTemplateServices;
use ThemeBundle\Services\PagesTemplateSetServices;
use DistributionBundle\Entities\Distributor;

class PagesTemplate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/pagestemplate/detail",
     *     summary="小程序模板详情",
     *     tags={"模板"},
     *     description="小程序模板详情",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="店铺id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="weapp_pages",
     *         in="query",
     *         description="小程序页面类型",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="template_name",
     *         in="query",
     *         description="模板名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="页码",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="每页数量",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="weapp_setting_id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="goods_grid_tab_id",
     *         in="query",
     *         description="类型为tab时的id",
     *         required=false,
     *         type="integer",
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
     *                     @SWG\Property(property="content", type="string"),
     *                     @SWG\Property(property="tabBar", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function detail(Request $request)
    {
        $auth_info = $request->get('auth');
        $distributor_id = $request->input('distributor_id', 0);       //门店id
        $weapp_pages = $request->input('weapp_pages', 'index'); //小程序页面类型
        $template_name = $request->input('template_name');       //小程序页面类型
        $version = $request->input('version', 'v1.0.2');       //小程序版本
        $page = $request->input('page', '1');
        $page_size = $request->input('page_size', '50');
        $weapp_setting_id = $request->input('weapp_setting_id');
        $goods_grid_tab_id = $request->input('goods_grid_tab_id');
        $pages_template_id = $request->input('pages_template_id', 0);
        $e_activity_id = $request->input('e_activity_id', 0);
        $params = [
            'company_id' => $auth_info['company_id'],
            'user_id' => ($auth_info['user_id']) ?? 0,
            'distributor_id' => $distributor_id,
            'weapp_pages' => $weapp_pages,
            'template_name' => $template_name,
            'version' => $version,
            'page' => $page,
            'page_size' => $page_size,
            'weapp_setting_id' => $weapp_setting_id,
            'goods_grid_tab_id' => $goods_grid_tab_id,
            'pages_template_id' => $pages_template_id,
            'e_activity_id' => $e_activity_id,
        ];

        $rules = [
            'distributor_id' => ['required|integer', '缺少门店id'],
            'template_name' => ['required', '缺少模板展示类型'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $pages_template_services = new PagesTemplateServices();
        $result = $pages_template_services->content($params);
        //添加埋点，拿这个人最近进入的店铺
//        if(!empty($auth_info['user_id']) && !empty($e_activity_id)) {
//            $redis = app('redis')->connection('default');
//            $redis->set('activity_probe:'.$auth_info['company_id'].'_'.$auth_info['user_id'],$e_activity_id);
//        }


        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/pagestemplate/shopDetail",
     *     summary="小程序模板详情",
     *     tags={"模板"},
     *     description="小程序模板详情",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="店铺id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="template_name",
     *         in="query",
     *         description="模板名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="weapp_pages",
     *         in="query",
     *         description="小程序页面类型",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="76872"),
     *                          @SWG\Property( property="template_name", type="string", example="yykweishop"),
     *                          @SWG\Property( property="company_id", type="string", example="1"),
     *                          @SWG\Property( property="name", type="string", example="search"),
     *                          @SWG\Property( property="page_name", type="string", example="index"),
     *                          @SWG\Property( property="params", type="object",
     *                                  @SWG\Property( property="name", type="string", example="search"),
     *                                  @SWG\Property( property="base", type="object",
     *                                          @SWG\Property( property="padded", type="string", example="false"),
     *                                  ),
     *                                  @SWG\Property( property="config", type="object",
     *                                          @SWG\Property( property="fixTop", type="string", example="false"),
     *                                          @SWG\Property( property="scanCode", type="string", example="true"),
     *                                  ),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="config", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="name", type="string", example="search"),
     *                          @SWG\Property( property="base", type="object",
     *                                  @SWG\Property( property="padded", type="string", example="false"),
     *                          ),
     *                          @SWG\Property( property="config", type="object",
     *                                  @SWG\Property( property="fixTop", type="string", example="false"),
     *                                  @SWG\Property( property="scanCode", type="string", example="true"),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function shopDetail(Request $request)
    {
        $auth_info = $request->get('auth');
        $distributor_id = $request->input('distributor_id');       //门店id
        $weapp_pages = $request->input('weapp_pages', 'index'); //小程序页面类型
        $template_name = $request->input('template_name');       //小程序页面类型
        $version = $request->input('version', 'v1.0.2');       //小程序版本

        $params = [
            'company_id' => $auth_info['company_id'],
            'user_id' => ($auth_info['user_id']) ?? 0,
            'distributor_id' => $distributor_id,
            'weapp_pages' => $weapp_pages,
            'template_name' => $template_name,
            'version' => $version
        ];
        //判断店铺是否失效
        $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $createDistributorInfo = $distributorRepository->getInfoById($params['distributor_id']);
        if (empty($createDistributorInfo)) {
            throw new \Exception("当前店铺不存在！");
        }
        if (!isset($createDistributorInfo['is_valid']) || $createDistributorInfo['is_valid'] != "true") {
            throw new ResourceException('当前店铺已失效');
        }

        $rules = [
            'distributor_id' => ['required|integer|min:1', '缺少门店id'],
            'template_name' => ['required', '缺少模板展示类型'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $pages_template_services = new PagesTemplateServices();
        $result = $pages_template_services->shopContent($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/pagestemplate/setInfo",
     *     summary="小程序模板设置信息",
     *     tags={"模板"},
     *     description="小程序模板设置信息",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="query",
     *         description="company_id",
     *         required=true,
     *         type="integer",
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
     *                     @SWG\Property(property="id", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer"),
     *                     @SWG\Property(property="index_type", type="integer"),
     *                     @SWG\Property(property="is_enforce_sync", type="integer"),
     *                     @SWG\Property(property="is_open_recommend", type="integer"),
     *                     @SWG\Property(property="is_open_wechatapp_location", type="integer"),
     *                     @SWG\Property(property="is_open_scan_qrcode", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function setInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $rules = [
            'company_id' => ['required|integer|min:1', '缺少company_id'],
        ];
        $error = validator_params($filter, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $pages_template_set_services = new PagesTemplateSetServices();
        $result = $pages_template_set_services->getInfo($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/pagestemplate/getTdk",
     *     summary="获取全局tdk设置信息",
     *     tags={"模板"},
     *     description="获取全局tdk设置信息",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter( name="company_id", in="query", description="company_id", required=true, type="integer", ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="title", type="string", example="标题xx", description=""),
     *                  @SWG\Property( property="mate_description", type="string", example="描述", description=""),
     *                  @SWG\Property( property="mate_keywords", type="string", example="3,dfads, 代发两色风景,测试", description=""),
     *                  @SWG\Property( property="update_time", type="string", example="1606215862", description="更新时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function getTdk(Request $request)
    {
        $company_id = $request->get('company_id');
        $TdkGlobal = new TdkGlobalService();
        $data_list = $TdkGlobal->getInfo($company_id);

        return $this->response->array($data_list);
    }
}
