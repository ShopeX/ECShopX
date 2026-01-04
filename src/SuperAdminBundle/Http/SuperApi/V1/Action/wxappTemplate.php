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
use SuperAdminBundle\Services\WxappTemplateService;
use Dingo\Api\Exception\DeleteResourceFailedException;
use WechatBundle\Services\OpenPlatform;
use WechatBundle\Services\WeappService;

class wxappTemplate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/super/admin/wxapp",
     *     summary="获取小程序模板列表",
     *     tags={"平台管理"},
     *     description="获取小程序模板列表",
     *     operationId="getTemplateList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="7", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/WxappTemplateInfo"
     *                       ),
     *                  ),
     *                  @SWG\Property( property="queryquota", type="object",
     *                          @SWG\Property( property="errcode", type="string", example="0", description="错误码"),
     *                          @SWG\Property( property="errmsg", type="string", example="ok", description="错误信息"),
     *                          @SWG\Property( property="rest", type="string", example="20", description="审核剩余次数"),
     *                          @SWG\Property( property="limit", type="string", example="20", description="审核次数限制"),
     *                          @SWG\Property( property="speedup_rest", type="string", example="0", description="加急审核剩余次数"),
     *                          @SWG\Property( property="speedup_limit", type="string", example="0", description="加急审核限制"),
     *                  ),
     *                  @SWG\Property( property="domain", type="object",
     *                          @SWG\Property( property="requestdomain", type="string", example="http://a.com", description="请求域名"),
     *                          @SWG\Property( property="wsrequestdomain", type="string", example="", description="websocket合法域名"),
     *                          @SWG\Property( property="uploaddomain", type="string", example="", description="upload合法域名"),
     *                          @SWG\Property( property="downloaddomain", type="string", example="", description="download合法域名"),
     *                          @SWG\Property( property="webviewdomain", type="string", example="", description="webview合法域名"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getTemplateList(Request $request)
    {
        $wxappTempService = new WxappTemplateService();
        $data = $wxappTempService->lists();

        $openPlatform = new OpenPlatform();
        $data['queryquota'] = $openPlatform->getWxaQueryquota();
        $data['domain'] = $wxappTempService->getDomain();

        return $this->response->array($data);
    }

    public function modifyDomain(Request $request)
    {
        //$postdata = $request->input();
        //if (!$postdata['keyname']) {
        //    throw new DeleteResourceFailedException("参数错误");
        //}
        //$wxappTemplateService = new WxappTemplateService();
        //$filter['key_name'] = $postdata['keyname'];
        //$templateData = $wxappTemplateService->getInfo($filter);
        //$weappService = new WeappService();
        //try {
        //    $weappService->modifyDomain($templateData['domain']);
        //} catch (\Exception $e) {
        //    throw new DeleteResourceFailedException($e->getMessage());
        //}
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/superadmin/speedupaudit",
     *     summary="加急小程序审核",
     *     tags={"平台管理"},
     *     description="加急小程序审核",
     *     operationId="speedupaudit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="appid",in="query",description="小程序appid",required=true,type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function speedupaudit(Request $request)
    {
        $openPlatform = new OpenPlatform();
        $openPlatform->speedupaudit($request->input('appid'));
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/superadmin/domain",
     *     summary="设置小程序需要用到的域名",
     *     tags={"平台管理"},
     *     description="设置小程序需要用到的域名",
     *     operationId="speedupaudit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="domain[requestdomain]",in="query",description="request合法域名",required=true,type="string"),
     *     @SWG\Parameter(name="domain[wsrequestdomain]",in="query",description="wsrequest合法域名",required=false,type="string"),
     *     @SWG\Parameter(name="domain[uploaddomain]",in="query",description="upload合法域名",required=false,type="string"),
     *     @SWG\Parameter(name="domain[downloaddomain]",in="query",description="download合法域名",required=false,type="string"),
     *     @SWG\Parameter(name="domain[webviewdomain]",in="query",description="webview合法域名",required=false,type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function setDomain(Request $request)
    {
        $postdata = $request->input();
        if (!isset($postdata['domain']['requestdomain']) || !$postdata['domain']['requestdomain']) {
            throw new DeleteResourceFailedException("request合法域名必填");
        }

        $wxappTempService = new WxappTemplateService();
        $wxappTempService->setDomain($postdata['domain']);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/super/admin/wxapp",
     *     summary="新增小程序模板",
     *     tags={"平台管理"},
     *     description="新增小程序模板",
     *     operationId="addWxappTemplate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="key_name",in="query",description="小程序唯一标识",required=true,type="string"),
     *     @SWG\Parameter(name="name",in="query",description="小程序模板名称",required=true,type="string"),
     *     @SWG\Parameter(name="tag",in="query",description="标签",required=false,type="string"),
     *     @SWG\Parameter(name="template_id",in="query",description="小程序模板id",required=false,type="integer"),
     *     @SWG\Parameter(name="template_id_2",in="query",description="模板id(直播版)",required=false,type="integer"),
     *     @SWG\Parameter(name="version",in="query",description="小程序模板版本",required=false,type="string"),
     *     @SWG\Parameter(name="description",in="query",description="详细描述",required=false,type="string"),
     *     @SWG\Parameter(name="domain",in="query",description="合法域名",required=false,type="string"),
     *     @SWG\Parameter(name="is_disabled",in="query",description="是否禁用",required=false,type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/WxappTemplateInfo"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function addWxappTemplate(Request $request)
    {
        $wxappTempService = new WxappTemplateService();
        $postdata = $request->input();
        $data = $wxappTempService->create($postdata);
        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/super/admin/wxapp",
     *     summary="更新小程序模板",
     *     tags={"平台管理"},
     *     description="更新小程序模板",
     *     operationId="updateWxappTemplate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="id",in="query",description="小程序模板ID",required=true,type="integer"),
     *     @SWG\Parameter(name="key_name",in="query",description="小程序唯一标识",required=true,type="string"),
     *     @SWG\Parameter(name="name",in="query",description="小程序模板名称",required=false,type="string"),
     *     @SWG\Parameter(name="tag",in="query",description="标签",required=false,type="string"),
     *     @SWG\Parameter(name="template_id",in="query",description="小程序模板id",required=false,type="integer"),
     *     @SWG\Parameter(name="template_id_2",in="query",description="模板id(直播版)",required=false,type="integer"),
     *     @SWG\Parameter(name="version",in="query",description="小程序模板版本",required=false,type="string"),
     *     @SWG\Parameter(name="description",in="query",description="详细描述",required=false,type="string"),
     *     @SWG\Parameter(name="domain",in="query",description="合法域名",required=false,type="string"),
     *     @SWG\Parameter(name="is_disabled",in="query",description="是否禁用",required=false,type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/WxappTemplateInfo"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function updateWxappTemplate(Request $request)
    {
        $wxappTempService = new WxappTemplateService();
        $postdata = $request->input();

        if (!$postdata['id']) {
            throw new DeleteResourceFailedException("参数错误");
        }

        $filter['id'] = $postdata['id'];

        unset($postdata['id']);
        $data = $wxappTempService->updateOneBy($filter, $postdata);
        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/super/admin/upgradeTemp",
     *     summary="更新小程序模板",
     *     tags={"平台管理"},
     *     description="更新小程序模板",
     *     operationId="upgradeTemp",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="id",in="query",description="小程序模板ID",required=true,type="integer"),
     *     @SWG\Parameter(name="key_name",in="query",description="小程序唯一标识",required=true,type="string"),
     *     @SWG\Parameter(name="name",in="query",description="小程序模板名称",required=false,type="string"),
     *     @SWG\Parameter(name="tag",in="query",description="标签",required=false,type="string"),
     *     @SWG\Parameter(name="template_id",in="query",description="小程序模板id",required=false,type="integer"),
     *     @SWG\Parameter(name="template_id_2",in="query",description="模板id(直播版)",required=false,type="integer"),
     *     @SWG\Parameter(name="version",in="query",description="小程序模板版本",required=false,type="string"),
     *     @SWG\Parameter(name="description",in="query",description="详细描述",required=false,type="string"),
     *     @SWG\Parameter(name="domain",in="query",description="合法域名",required=false,type="string"),
     *     @SWG\Parameter(name="is_disabled",in="query",description="是否禁用",required=false,type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function upgradeTemp(Request $request)
    {
        $wxappTempService = new WxappTemplateService();
        $postdata = $request->input();
        if (!$postdata['id']) {
            throw new DeleteResourceFailedException("参数错误");
        }

        $filter['id'] = $postdata['id'];

        unset($postdata['id']);
        $data = $wxappTempService->updateOneBy($filter, $postdata);
        return $this->response->array($data);
    }

    /**
     * @SWG\Delete(
     *     path="/super/admin/wxapp/{id}",
     *     summary="禁用小程序模板",
     *     tags={"平台管理"},
     *     description="禁用小程序模板",
     *     operationId="deleteWxappTemplate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="id",in="path",description="菜单ID",required=true,type="integer"),
     *     @SWG\Parameter(name="status",in="query",description="状态(0 可用,1 禁用)",required=true,type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function deleteWxappTemplate($id, Request $request)
    {
        $wxappTempService = new WxappTemplateService();
        if (!$id) {
            throw new DeleteResourceFailedException("参数错误");
        }

        $filter['id'] = $id;
        $disabled = $request->input('status');

        $data['status'] = $wxappTempService->updateOneBy($filter, ['is_disabled' => $disabled]);
        return $this->response->array($data);
    }

    /**
     * @SWG\Definition(
     *     definition="WxappTemplateInfo",
     *     description="小程序模板信息",
     *     type="object",
     *     @SWG\Property( property="id", type="string", example="8", description="模板ID"),
     *     @SWG\Property( property="key_name", type="string", example="yykweishop", description="小程序英文描述"),
     *     @SWG\Property( property="name", type="string", example="导购", description="小程序模板名称"),
     *     @SWG\Property( property="tag", type="string", example="null", description="小程序标签"),
     *     @SWG\Property( property="template_id", type="string", example="62", description="小程序模板ID"),
     *     @SWG\Property( property="template_id_2", type="string", example="0", description="模板id(直播版)"),
     *     @SWG\Property( property="version", type="string", example="v3.4.3", description="版本号"),
     *     @SWG\Property( property="is_only", type="string", example="false", description="是否为唯一属性，如果为唯一属性那么当前模版只能绑定一个小程序"),
     *     @SWG\Property( property="description", type="string", example="null", description="模板详细描述"),
     *     @SWG\Property( property="domain", type="object", description="合法域名配置",
     *          @SWG\Property( property="requestdomain", type="string", example="https://ecshopx.shopex123.com", description="请求合法域名"),
     *          @SWG\Property( property="wsrequestdomain", type="string", example="wss://b-websocket.shopex123.com", description="websocket合法域名"),
     *          @SWG\Property( property="uploaddomain", type="string", example="https://ecshopx.shopex123.comm", description="upload合法域名"),
     *          @SWG\Property( property="downloaddomain", type="string", example="https://ecshopx.shopex123.com", description="download合法域名"),
     *     ),
     *     @SWG\Property( property="is_disabled", type="string", example="false", description="是否禁用"),
     *     @SWG\Property( property="created", type="string", example="1606288337", description="创建时间"),
     *     @SWG\Property( property="updated", type="string", example="1606288337", description="修改时间"),
     * )
     */
}
