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

namespace WechatBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;
use WechatBundle\Services\Wxapp\CustomizePageService;
use WechatBundle\Entities\WeappSetting;

class CustomizePage extends Controller
{
    public $CustomizePageService;
    public $limit;
    public $weappSetting;

    public function __construct()
    {
        $this->CustomizePageService = new CustomizePageService();
        //$this->weappSetting = app('registry')->getManager('default')->getRepository(WeappSetting::class);
        $this->weappSetting = getRepositoryLangue(WeappSetting::class);
        $this->limit = 20;
    }
    /**
     * @SWG\Post(
     *     path="/wxa/customizepage",
     *     summary="新增自定义页面",
     *     tags={"微信"},
     *     description="新增自定义页面",
     *     operationId="createCustomizePage",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="page_description", in="query", description="自定义页面描述", required=false, type="string"),
     *     @SWG\Parameter( name="page_name", in="query", description="自定义页面名称", required=false, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="自定义页面是否开启", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="100"),
     *                  @SWG\Property( property="template_name", type="string", example="11223"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="page_name", type="string", example="11223"),
     *                  @SWG\Property( property="page_description", type="string", example="11223"),
     *                  @SWG\Property( property="page_share_title", type="string", example="null"),
     *                  @SWG\Property( property="page_share_desc", type="string", example="null"),
     *                  @SWG\Property( property="page_share_imageUrl", type="string", example="null"),
     *                  @SWG\Property( property="is_open", type="string", example="1"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */

    public function createCustomizePage(Request $request)
    {
        // Powered by ShopEx EcShopX
        $params = $request->all('template_name', 'page_name', 'page_description', 'page_share_title', 'page_share_desc', 'page_share_imageUrl', 'is_open', 'page_type');

        $rules = [
            'template_name' => ['required', '模版名称不能为空'],
            'page_name' => ['required', '自定义页面名称不能为空'],
            'page_description' => ['required', '页面描述不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $params['is_open'] = (isset($params['is_open']) && (($params['is_open'] === "false" || $params['is_open'] === false || $params['is_open'] === 0 || $params['is_open'] === '0'))) ? false : true;
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        if ($params['is_open'] == 1 || $params['is_open'] == true || $params['is_open'] == '1' || $params['is_open'] == 'true') {
            $cfilter['is_open'] = 1;
            $cfilter['company_id'] = $companyId;
            $cfilter['page_type'] = $params['page_type'];
            $cfilter['template_name'] = $params['template_name'];
            $count = $this->CustomizePageService->count($cfilter);
            app('log')->info(__FUNCTION__.':'.__LINE__.':count:'.json_encode($count));
            if ($count > 0) {
                return $this->response->array(['status' => false, 'message' => "已经有启用的模版", 'status_code' => 422]);
            }
        }
        
        $result = $this->CustomizePageService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wxa/customizepage/{id}",
     *     summary="更新自定义页面",
     *     tags={"微信"},
     *     description="更新自定义页面",
     *     operationId="updateCustomizePage",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *		@SWG\Parameter( name="id", in="query", description="自定义页面id", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="page_description", in="query", description="自定义页面描述", required=false, type="string"),
     *     @SWG\Parameter( name="page_name", in="query", description="自定义页面名称", required=false, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="自定义页面是否开启", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Items(
     *                 type="object",
     *                 @SWG\Property(property="id", type="integer"),
     *                 @SWG\Property(property="template_name", type="string"),
     *                 @SWG\Property(property="page_description", type="string"),
     *                 @SWG\Property(property="page_name", type="string"),
     *                 @SWG\Property(property="is_open", type="string")
     *                )
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function updateCustomizePage(Request $request, $id)
    {
        $params = $request->all('template_name', 'page_name', 'page_description', 'page_share_title', 'page_share_desc', 'page_share_imageUrl', 'is_open');

        if (!$id) {
            throw new ResourceException("页面ID必传");
        }
        $params['is_open'] = ($params['is_open'] === "false" || $params['is_open'] === false || $params['is_open'] === 0 || $params['is_open'] === '0') ? false : true;
        $companyId = app('auth')->user()->get('company_id');
        $filter['id'] = $id;
        $filter['company_id'] = $companyId;

        if ($params['is_open'] == 1 || $params['is_open'] == true || $params['is_open'] == '1' || $params['is_open'] == 'true') {
            $page_type = $request->input('page_type', 'my');
            $cfilter['is_open'] = 1;
            $cfilter['id'] = ['!=', $id];
            $cfilter['company_id'] = $companyId;
            $cfilter['page_type'] = $page_type;
            $cfilter['template_name'] = $params['template_name'];
            $count = $this->CustomizePageService->count($cfilter);
            if ($count > 0) {
                return $this->response->array(['status' => false, 'message' => "已经有启用的模版", 'status_code' => 422]);
            }
        }

        $result = $this->CustomizePageService->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/customizepage/list",
     *     summary="获取自定义页面列表",
     *     tags={"微信"},
     *     description="获取自定义页面列表",
     *     operationId="getCustomizepageList",
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
     *         description="页码",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页长度",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="page_type",
     *         in="query",
     *         description="页面类型 normal:普通页面 salesperson:导购货架首页",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="20"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="99"),
     *                          @SWG\Property( property="template_name", type="string", example="yykweishop"),
     *                          @SWG\Property( property="company_id", type="string", example="1"),
     *                          @SWG\Property( property="page_name", type="string", example="活动"),
     *                          @SWG\Property( property="page_description", type="string", example="营销"),
     *                          @SWG\Property( property="is_open", type="string", example="1"),
     *                          @SWG\Property( property="page_share_title", type="string", example="null"),
     *                          @SWG\Property( property="page_share_desc", type="string", example="null"),
     *                          @SWG\Property( property="page_share_imageUrl", type="string", example="null"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getCustomizepageList(Request $request)
    {
        $params = $request->all('template_name', 'page', 'pageSize', 'page_type');
        $page = $params['page'] ? intval($params['page']) : 1;
        $pageSize = $params['pageSize'] ? intval($params['pageSize']) : $this->limit;

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $filter['template_name'] = $params['template_name'];
        $params['page_type'] = $params['page_type'] ? $params['page_type'] : 'normal';
        if ($params['page_type']) {
            $filter['page_type'] = $params['page_type'];
        }
        $orderBy = ['id' => 'DESC'];
        $result = $this->CustomizePageService->lists($filter, "*", $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }

    public function getCustomizePageInfo($id)
    {
        $filter['id'] = $id;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $info = $this->CustomizePageService->getInfoById($id);
        if (!$info) {
            throw new ResourceException('自定义页面不存在');
        }
        return $this->response->array($info);
    }

    /**
    * @SWG\Delete(
    *     path="/wxa/customizepage/{id}",
    *     summary="删除自定义页面",
    *     tags={"微信"},
    *     description="删除自定义页面",
    *     operationId="deleteCustomizePage",
    *     @SWG\Parameter(
    *         name="Authorization",
    *         in="header",
    *         description="JWT验证token",
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="id",
    *         in="path",
    *         description="页面id",
    *         required=true,
    *         type="integer"
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="成功返回结构",
    *         @SWG\Schema(
    *             @SWG\Property(
    *                 property="data",
    *                 type="array",
    *                 @SWG\items(
    *                     type="object",
    *                     @SWG\Property(property="status", type="bool"),
    *                 )
    *             ),
    *          ),
    *     ),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
    * )
    */
    public function deleteCustomizePage($id)
    {
        $filter['id'] = $id;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $pageInfo = $this->CustomizePageService->getInfoById($id);
        if ($pageInfo) {
            $params = [
                    'template_name' => $pageInfo['template_name'],
                    'company_id' => $filter['company_id'],
                    'page_name' => 'custom_'.$id
                ];
            $this->weappSetting->deleteBy($params);
            $result = $this->CustomizePageService->deleteBy($filter);
            return $this->response->array(['status' => $result]);
        } else {
            throw new ResourceException('自定义页面不存在');
        }
    }

    /**
     * @SWG\Get(
     *     path="/wxa/salesperson/customizepage",
     *     summary="获取导购货架首页模板",
     *     tags={"微信"},
     *     description="获取导购货架首页自定义模板",
     *     operationId="getSalespersonCustomizePage",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="模版名称", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="100"),
     *                  @SWG\Property( property="template_name", type="string", example="11223"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="page_name", type="string", example="11223"),
     *                  @SWG\Property( property="page_description", type="string", example="11223"),
     *                  @SWG\Property( property="page_share_title", type="string", example="null"),
     *                  @SWG\Property( property="page_share_desc", type="string", example="null"),
     *                  @SWG\Property( property="page_share_imageUrl", type="string", example="null"),
     *                  @SWG\Property( property="is_open", type="string", example="1"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */

    public function getSalespersonCustomizePage(Request $request)
    {
        $params = $request->all('template_name');

        $rules = [
            'template_name' => ['required', '模版名称不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'company_id' => $companyId,
            'page_type' => 'salesperson',
            'template_name' => $params['template_name'],
        ];
        $info = $this->CustomizePageService->getInfo($filter);
        if (!$info) {
            $data = [
                'company_id' => $companyId,
                'page_type' => 'salesperson',
                'template_name' => $params['template_name'],
                'page_name' => '导购货架首页',
                'page_description' => '导购货架首页',
                'page_share_title' => '导购货架',
                'page_share_desc' => '导购货架',
                'is_open' => 1,
            ];
            $info = $this->CustomizePageService->create($data);
        }
        $result = ['id' => $info['id']];
        return $this->response->array($result);
    }
}
