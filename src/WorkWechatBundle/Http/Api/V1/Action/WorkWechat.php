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

namespace WorkWechatBundle\Http\Api\V1\Action;

use Dingo\Api\Exception\ResourceException;
use EasyWeChat\Factory;
use SalespersonBundle\Services\SalespersonService;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use WorkWechatBundle\Services\DistributorWorkWechatService;
use WorkWechatBundle\Services\WorkWechatRelService;
use WorkWechatBundle\Services\WorkWechatService;
use DistributionBundle\Services\DistributorService;
use WorkWechatBundle\Services\WorkWechatVerifyDomainService;

class WorkWechat extends Controller
{
    /**
     * @SWG\Get(
     *     path="/workwechat/config",
     *     summary="获取企业微信配置",
     *     tags={"企业微信"},
     *     description="获取企业微信配置",
     *     operationId="getConfig",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="show", type="string", example="1"),
     *                  @SWG\Property( property="corpid", type="string", example="ww21a77804a566228f"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="agents", type="object",
     *                          @SWG\Property( property="app", type="object",
     *                                  @SWG\Property( property="appid", type="string", example="wx909d98696cccef0c"),
     *                                  @SWG\Property( property="agent_id", type="string", example="1000006"),
     *                                  @SWG\Property( property="secret", type="string", example="vpm0ryoy9BcWHC1h5KlitouJXDOGbtdZNj-AcfZ8SOk"),
     *                                  @SWG\Property( property="token", type="string", example="bKP4RHzS"),
     *                                  @SWG\Property( property="aes_key", type="string", example="RFIUdjpUpnhCmrB9y6LO91MXeRLnxBeUGJDAlNUqnzZ"),
     *                          ),
     *                          @SWG\Property( property="customer", type="object",
     *                                  @SWG\Property( property="secret", type="string", example="EZuEHy1CsYP7zMCBZKv5AreNM2qEmZVWSnXELNEONig"),
     *                                  @SWG\Property( property="token", type="string", example="GWuqzsTyMFdOf"),
     *                                  @SWG\Property( property="aes_key", type="string", example="AidHPzoFrkSQByitsfACxcLgJtYSX1CX3NhBX7LQ5PE"),
     *                          ),
     *                          @SWG\Property( property="report", type="object",
     *                                  @SWG\Property( property="secret", type="string", example="qzR6Kb2SQoBOspu6dKkL49VNAAF5TRcb1_FZ7DGbF88"),
     *                                  @SWG\Property( property="token", type="string", example="VK8iNYbV"),
     *                                  @SWG\Property( property="aes_key", type="string", example="HFGbVVBlccqhoz7Nx64IeTXh1EZaeuXEtKH2UAzPaGW"),
     *                                  @SWG\Property( property="URL", type="string", example="api/operator/workwechat/authorizeurl?company_id=43"),
     *                          ),
     *                          @SWG\Property( property="dianwu", type="object",
     *                                  @SWG\Property( property="agent_id", type="string", example="1000006"),
     *                                  @SWG\Property( property="secret", type="string", example="vpm0ryoy9BcWHC1h5KlitouJXDOGbtdZNj-AcfZ8SOk"),
     *                                  @SWG\Property( property="h5_url", type="string", example="http://xxx.com"),
     *                                  @SWG\Property( property="h5_host", type="string", example="xxx.com"),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones") ) )
     * )
     */
    public function getConfig(Request $request)
    {
        $workWechatService = new WorkWechatService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $workWechatService->getViewConfig($companyId);
        if (isset($result['agents']['customer']['URL'])) {
            $url = $request->url();
            $result['agents']['customer']['URL'] = substr($url, 0, strpos($url, '/workwechat/config')).$result['agents']['customer']['URL'];
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/workwechat/config",
     *     summary="保存企业微信配置",
     *     tags={"企业微信"},
     *     description="保存企业微信配置",
     *     operationId="setConfig",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="show",
     *         in="formData",
     *         description="是否开启企业微信",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="corpid",
     *         in="formData",
     *         description="企业微信corpid",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[app][appid]",
     *         in="formData",
     *         description="企业微信appid",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[app][agent_id]",
     *         in="formData",
     *         description="agent_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[app][secret]",
     *         in="formData",
     *         description="企业微信secret",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[app][token]",
     *         in="formData",
     *         description="企业微信token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[app][aes_key]",
     *         in="formData",
     *         description="企业微信aes_key",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[customer][secret]",
     *         in="formData",
     *         description="企业微信客户联系Secret",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[customer][token]",
     *         in="formData",
     *         description="企业微信客户联系token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[customer][aes_key]",
     *         in="formData",
     *         description="企业微信客户联系aes_key",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[report][secret]",
     *         in="formData",
     *         description="企业微信管理工具Secret",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[report][token]",
     *         in="formData",
     *         description="企业微信管理工具token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[report][aes_key]",
     *         in="formData",
     *         description="企业微信管理工具aes_key",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[dianwu][agent_id]",
     *         in="formData",
     *         description="企业微信自建应用agent_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="agents[dianwu][secret]",
     *         in="formData",
     *         description="企业微信自建应用secret",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="show", type="string", example="1"),
     *                  @SWG\Property( property="corpid", type="string", example="ww21a77804a566228f"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="agents", type="object",
     *                          @SWG\Property( property="app", type="object",
     *                                  @SWG\Property( property="appid", type="string", example="wx909d98696cccef0c"),
     *                                  @SWG\Property( property="agent_id", type="string", example="1000006"),
     *                                  @SWG\Property( property="secret", type="string", example="vpm0ryoy9BcWHC1h5KlitouJXDOGbtdZNj-AcfZ8SOk"),
     *                                  @SWG\Property( property="token", type="string", example="bKP4RHzS"),
     *                                  @SWG\Property( property="aes_key", type="string", example="RFIUdjpUpnhCmrB9y6LO91MXeRLnxBeUGJDAlNUqnzZ"),
     *                          ),
     *                          @SWG\Property( property="customer", type="object",
     *                                  @SWG\Property( property="secret", type="string", example="EZuEHy1CsYP7zMCBZKv5AreNM2qEmZVWSnXELNEONig"),
     *                                  @SWG\Property( property="token", type="string", example="GWuqzsTyMFdOf"),
     *                                  @SWG\Property( property="aes_key", type="string", example="AidHPzoFrkSQByitsfACxcLgJtYSX1CX3NhBX7LQ5PE"),
     *                          ),
     *                          @SWG\Property( property="report", type="object",
     *                                  @SWG\Property( property="secret", type="string", example="qzR6Kb2SQoBOspu6dKkL49VNAAF5TRcb1_FZ7DGbF88"),
     *                                  @SWG\Property( property="token", type="string", example="VK8iNYbV"),
     *                                  @SWG\Property( property="aes_key", type="string", example="HFGbVVBlccqhoz7Nx64IeTXh1EZaeuXEtKH2UAzPaGW"),
     *                          ),
     *                          @SWG\Property( property="dianwu", type="object",
     *                                  @SWG\Property( property="agent_id", type="string", example="1000006"),
     *                                  @SWG\Property( property="secret", type="string", example="vpm0ryoy9BcWHC1h5KlitouJXDOGbtdZNj-AcfZ8SOk"),
     *                                  @SWG\Property( property="h5_url", type="string", example="http://dianwu.ex-sandbox.com/pages/auth/welcome?company_id=1"),
     *                                  @SWG\Property( property="h5_host", type="string", example="dianwu.ex-sandbox.com"),
     *                                  @SWG\Property( property="verify_file_name", type="string", example="xxx.txt"),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones") ) )
     * )
     */
    public function setConfig(Request $request)
    {
        $params = $request->all('show', 'corpid', 'agents.app', 'agents.customer', 'agents.report', 'agents.dianwu');
        $workWechatService = new WorkWechatService();

        $companyId = app('auth')->user()->get('company_id');
        $result = $workWechatService->saveWorkWechatConfig($companyId, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/workwechat/distributor/js/config",
     *     summary="获取企业微信店务端js配置",
     *     tags={"WorkWechat"},
     *     description="获取企业微信店务端js配置",
     *     operationId="getDistributorJsConfig",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="url",
     *         in="formData",
     *         description="当前页面url",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="beta", type="boolean", description=""),
     *                  @SWG\Property( property="debug", type="boolean", description="开启调试模式"),
     *                  @SWG\Property( property="appId", type="string", description=""),
     *                  @SWG\Property( property="timestamp", type="string", description=""),
     *                  @SWG\Property( property="nonceStr", type="string", description=""),
     *                  @SWG\Property( property="signature", type="string", description=""),
     *                  @SWG\Property( property="url", type="string", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones") ) )
     * )
     */
    public function getDistributorJsConfig(Request $request)
    {
        $url = $request->get('url');
        if (!$url) {
            throw new ResourceException('当前页面url必填');
        }
        $companyId = app('auth')->user()->get('company_id');
        $workWechatService = new DistributorWorkWechatService();
        $result = $workWechatService->getJsConfig($companyId, $url);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/workwechat/rellist/{salespersonId}",
     *     summary="导购员企业微信关联信息",
     *     tags={"企业微信"},
     *     description="导购员企业微信关联信息",
     *     operationId="getWorkWechatList",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="path", type="string", required=true, name="salespersonId", description="营业员ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="页数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="分页条数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_friend" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_bind" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="924"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="2441"),
     *                          @SWG\Property( property="company_id", type="string", example="0"),
     *                          @SWG\Property( property="work_userid", type="string", example="176801394625773407"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="0"),
     *                          @SWG\Property( property="external_userid", type="string", example="wmbaVxBgAA0gcwpPaDpjuX18tIegBIhA"),
     *                          @SWG\Property( property="unionid", type="string", example="ofQlA00DK7fhLtOmQEL2Xn17O8Cs"),
     *                          @SWG\Property( property="user_id", type="string", example="0"),
     *                          @SWG\Property( property="is_friend", type="string", example="1"),
     *                          @SWG\Property( property="is_bind", type="string", example="0"),
     *                          @SWG\Property( property="bound_time", type="string", example="0"),
     *                          @SWG\Property( property="add_friend_time", type="string", example="1605850973"),
     *                          @SWG\Property( property="user_info", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                          @SWG\Property( property="salesperson_info", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones")))
     * )
     */
    public function getWorkWechatList($salespersonId, Request $request)
    {
        $page = (int)$request->input('page', 1);
        $pageSize = (int)$request->input('page_size', 20);
        $filter = [
            'salesperson_id' => $salespersonId
        ];
        if ($request->input('is_friend', 0)) {
            $filter['is_friend'] = (int)$request->input('is_friend', 0);
        }
        if ($request->input('is_bind', 0)) {
            $filter['is_bind'] = (int)$request->input('is_bind', 0);
        }
        $workWechatRelService = new WorkWechatRelService();
        $result = $workWechatRelService->getWorkWechatRel($filter, '*', $page, $pageSize, ['id' => 'DESC']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/workwechat/rellogs/{userId}",
     *     summary="导购员企业微信关联信息日志",
     *     tags={"企业微信"},
     *     description="导购员企业微信关联信息日志",
     *     operationId="getWorkWechatLogsList",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="path", type="string", required=true, name="userId", description="用户id" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="页数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="分页条数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_friend" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_bind" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="924"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="2441"),
     *                          @SWG\Property( property="company_id", type="string", example="0"),
     *                          @SWG\Property( property="log_type", type="string", example="日志类型：初始绑定，绑定变更"),
     *                          @SWG\Property( property="work_userid", type="string", example="176801394625773407"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="0"),
     *                          @SWG\Property( property="external_userid", type="string", example="wmbaVxBgAA0gcwpPaDpjuX18tIegBIhA"),
     *                          @SWG\Property( property="unionid", type="string", example="ofQlA00DK7fhLtOmQEL2Xn17O8Cs"),
     *                          @SWG\Property( property="user_id", type="string", example="0"),
     *                          @SWG\Property( property="is_friend", type="string", example="1"),
     *                          @SWG\Property( property="remarks", type="string"),
     *                          @SWG\Property( property="created", type="string"),
     *                          @SWG\Property( property="salesperson_info", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones")))
     * )
     */
    public function getWorkWechatLogsList($userId, Request $request)
    {
        $page = (int)$request->input('page', 1);
        $pageSize = (int)$request->input('page_size', 20);
        $filter = [
            'user_id' => $userId
        ];
        if ($request->input('is_friend', 0)) {
            $filter['is_friend'] = (int)$request->input('is_friend', 0);
        }
        if ($request->input('is_bind', 0)) {
            $filter['is_bind'] = (int)$request->input('is_bind', 0);
        }
        $workWechatRelService = new WorkWechatRelService();
        $result = $workWechatRelService->getWorkWechatRelLogs($filter, '*', $page, $pageSize, ['id' => 'DESC']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/workwechat/report",
     *     summary="获取企业微信通讯录",
     *     tags={"企业微信"},
     *     description="获取企业微信通讯录",
     *     operationId="getReport",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="1"),
     *                  @SWG\Property( property="name", type="string", example="商派软件有限公司"),
     *                  @SWG\Property( property="parentid", type="string", example="0", description="父级ID"),
     *                  @SWG\Property( property="order", type="string", example="100000000"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="8"),
     *                          @SWG\Property( property="name", type="string", example="商派内部导购权限"),
     *                          @SWG\Property( property="parentid", type="string", example="1"),
     *                          @SWG\Property( property="order", type="string", example="99997000"),
     *                          @SWG\Property( property="children", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="12"),
     *                                  @SWG\Property( property="name", type="string", example="售前"),
     *                                  @SWG\Property( property="parentid", type="string", example="8"),
     *                                  @SWG\Property( property="order", type="string", example="100000000"),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones")))
     * )
     */
    public function getReport()
    {
        $companyId = app('auth')->user()->get('company_id');
        $config = app('wechat.work.wechat')->getConfig($companyId);
        $departmentList = Factory::work($config)->department->list();

        $result = make_tree($departmentList['department'] ?? []);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/workwechat/report/{department_id}",
     *     summary="获取企业微信部门成员列表",
     *     tags={"企业微信"},
     *     description="获取企业微信部门成员列表",
     *     operationId="getReportUserLists",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="path", type="string", required=true, name="department_id", description="department_id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="userid", type="string", example="zling"),
     *                  @SWG\Property( property="name", type="string", example="张伶"),
     *                  @SWG\Property( property="department", type="array",
     *                      @SWG\Items( type="string", example="1"),
     *                  ),
     *                  @SWG\Property( property="position", type="string", example=""),
     *                  @SWG\Property( property="mobile", type="string", example="18621616163"),
     *                  @SWG\Property( property="gender", type="string", example="1"),
     *                  @SWG\Property( property="email", type="string", example=""),
     *                  @SWG\Property( property="avatar", type="string", example="http://wework.qpic.cn/bizmail/xINkhYWhfZ6ya4Xicb7mic6QSqP7089F1M1Gb6bAjPjvwmIQyzSU3iaPA/0"),
     *                  @SWG\Property( property="status", type="string", example="2"),
     *                  @SWG\Property( property="enable", type="string", example="0"),
     *                  @SWG\Property( property="isleader", type="string", example="0"),
     *                  @SWG\Property( property="extattr", type="object",
     *                          @SWG\Property( property="attrs", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="hide_mobile", type="string", example="0"),
     *                  @SWG\Property( property="telephone", type="string", example=""),
     *                  @SWG\Property( property="order", type="array",
     *                      @SWG\Items( type="string", example="0"),
     *                  ),
     *                  @SWG\Property( property="external_profile", type="object",
     *                          @SWG\Property( property="external_attr", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                          @SWG\Property( property="external_corp_name", type="string", example=""),
     *                  ),
     *                  @SWG\Property( property="main_department", type="string", example="1"),
     *                  @SWG\Property( property="qr_code", type="string", example="https://open.work.weixin.qq.com/wwopen/userQRCode?vcode=vc9bb734f442bb1fcc"),
     *                  @SWG\Property( property="alias", type="string", example=""),
     *                  @SWG\Property( property="is_leader_in_dept", type="array",
     *                      @SWG\Items( type="string", example="0"),
     *                  ),
     *                  @SWG\Property( property="address", type="string", example=""),
     *                  @SWG\Property( property="thumb_avatar", type="string", example="http://wework.qpic.cn/bizmail/xINkhYWhfZ6ya4Xicb7mic6QSqP7089F1M1Gb6bAjPjvwmIQyzSU3iaPA/100"),
     *                  @SWG\Property( property="id", type="string", example="zling"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones")))
     * )
     */
    public function getReportUserLists($department_id = 1)
    {
        $companyId = app('auth')->user()->get('company_id');
        $config = app('wechat.work.wechat')->getConfig($companyId);
        $wechatResult = Factory::work($config)->user->getDetailedDepartmentUsers($department_id);
        $result = $wechatResult['userlist'] ?? [];
        foreach ($result as &$vi) {
            $vi['id'] = $vi['userid'];
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/workwechat/report/syncDistributor",
     *     summary="同步企微部门信息到店铺",
     *     tags={"企业微信"},
     *     description="同步企微部门信息到店铺",
     *     operationId="syncDistributor",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="department_id", description="department_id" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="distributor_id", description="经销商编号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="distributor_id", type="string", example="116"),
     *                  @SWG\Property( property="shop_id", type="string", example="0"),
     *                  @SWG\Property( property="is_distributor", type="string", example="true"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="mobile", type="string", example="19-0-0"),
     *                  @SWG\Property( property="address", type="string", example="null"),
     *                  @SWG\Property( property="name", type="string", example="内容获客"),
     *                  @SWG\Property( property="auto_sync_goods", type="string", example="false"),
     *                  @SWG\Property( property="logo", type="string", example="null"),
     *                  @SWG\Property( property="contract_phone", type="string", example="0"),
     *                  @SWG\Property( property="banner", type="string", example="null"),
     *                  @SWG\Property( property="contact", type="string", example="null"),
     *                  @SWG\Property( property="is_valid", type="string", example="true"),
     *                  @SWG\Property( property="lng", type="string", example="null"),
     *                  @SWG\Property( property="lat", type="string", example="null"),
     *                  @SWG\Property( property="child_count", type="string", example="0"),
     *                  @SWG\Property( property="is_default", type="string", example="0"),
     *                  @SWG\Property( property="is_audit_goods", type="string", example="false"),
     *                  @SWG\Property( property="is_ziti", type="string", example="true"),
     *                  @SWG\Property( property="regions_id", type="string", example="null"),
     *                  @SWG\Property( property="regions", type="string", example="null"),
     *                  @SWG\Property( property="is_domestic", type="string", example="1"),
     *                  @SWG\Property( property="is_direct_store", type="string", example="1"),
     *                  @SWG\Property( property="province", type="string", example="null"),
     *                  @SWG\Property( property="is_delivery", type="string", example="true"),
     *                  @SWG\Property( property="city", type="string", example="null"),
     *                  @SWG\Property( property="area", type="string", example="null"),
     *                  @SWG\Property( property="hour", type="string", example="null"),
     *                  @SWG\Property( property="created", type="string", example="1611564115"),
     *                  @SWG\Property( property="updated", type="string", example="1611564115"),
     *                  @SWG\Property( property="shop_code", type="string", example="null"),
     *                  @SWG\Property( property="wechat_work_department_id", type="string", example="19"),
     *                  @SWG\Property( property="distributor_self", type="string", example="0"),
     *                  @SWG\Property( property="regionauth_id", type="string", example="0"),
     *                  @SWG\Property( property="is_open", type="string", example="false"),
     *                  @SWG\Property( property="rate", type="string", example="null"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones")))
     * )
     */
    public function syncDistributor(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributorService = new DistributorService();
        $distributor_id = $request->post('distributor_id', 0);
        $department_info = $request->post('department_id', '');

        if (!$department_info) {
            throw new ResourceException('请至少选择一个部门');
        }
        $department_info = json_decode($department_info, true);
        if ($distributor_id) {
            $result = $this->updateDistributor($distributorService, $distributor_id, $department_info, $companyId);
        } else {
            $result = $distributorService->syncDepartmentToDistributor($companyId, $department_info);
        }

        return $this->response->array($result);
    }

    /**
     * Notes: 更新部门 和 企微的 绑定关系
     * Author:Michael-Ma
     * Date:  2020年06月11日 15:50:33
     *
     * @param $distributorService
     * @param $departmentInfo
     * @param $departmentData
     * @param $companyId
     *
     * @return
     */
    private function updateDistributor($distributorService, $departmentInfo, $departmentData, $companyId)
    {
        return $distributorService->updateDepartmentToDistributor($companyId, $departmentData, $departmentInfo);
    }

    /**
     * @SWG\Post(
     *     path="/workwechat/report/syncSalesperson",
     *     summary="同步企微部门成员到导购员",
     *     tags={"企业微信"},
     *     description="同步企微部门成员到导购员",
     *     operationId="syncSalesperson",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="department_id",
     *         in="formData",
     *         description="部门编号",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="user_ids",
     *         in="formData",
     *         description="用户ID",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="update_salesperson_reulst", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="salesperson_id", type="string"),
     *                          @SWG\Property( property="name", type="string"),
     *                          @SWG\Property( property="mobile", type="string"),
     *                          @SWG\Property( property="created_time", type="string"),
     *                          @SWG\Property( property="salesperson_type", type="string"),
     *                          @SWG\Property( property="company_id", type="string"),
     *                          @SWG\Property( property="user_id", type="string"),
     *                          @SWG\Property( property="child_count", type="string"),
     *                          @SWG\Property( property="is_valid", type="string"),
     *                          @SWG\Property( property="shop_id", type="string"),
     *                          @SWG\Property( property="shop_name", type="string"),
     *                          @SWG\Property( property="number", type="string"),
     *                          @SWG\Property( property="friend_count", type="string"),
     *                          @SWG\Property( property="avatar", type="string"),
     *                          @SWG\Property( property="work_userid", type="string"),
     *                          @SWG\Property( property="work_configid", type="string"),
     *                          @SWG\Property( property="work_qrcode_configid", type="string"),
     *                          @SWG\Property( property="role", type="string"),
     *                          @SWG\Property( property="created", type="string"),
     *                          @SWG\Property( property="updated", type="string"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="create_salesperson_result", type="array",
     *                      @SWG\Items( type="string"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones") ) )
     * )
     */
    public function syncSalesperson(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $config = app('wechat.work.wechat')->getConfig($companyId);

        $department_id = $request->post('department_id', 0);
        $user_ids = $request->post('user_ids', '');
        $userData = [];
        $app = Factory::work($config)->user;

        if ($user_ids && is_string($user_ids)) { // 同步 指定的 企业微信用户ID
            $user_id_arr = json_decode($user_ids, true);
            foreach ($user_id_arr as $v) {
                $userArr = $app->get($v);
                $userArr && $userData[] = $userArr;
            }
        } else {
            $wechatData = $app->getDetailedDepartmentUsers($department_id);
            $userData = $wechatData['userlist'] ?? [];
        }

        $result = (new SalespersonService())->syncUserToSalesperson($companyId, $userData);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(path="/workwechat/domain/verify",
     *   tags={"企业微信"},
     *   summary="企业微信校验域名文件上传",
     *   description="企业微信校验域名文件上传",
     *   operationId="workwecahtDoaminVerify",
     *   @SWG\Parameter(name="file",in="formData",description="域名校验文件",required=true,type="file"),
     *   @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="status", type="string", example="true", description=""),
     *            ),
     *         ),
     *    ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones") ) )
     * )
     */
    public function verifyDomain(Request $request)
    {
        $file = $request->file('file');
        $company_id = app('auth')->user()->get('company_id');
        $operator_id = app('auth')->user()->get('operator_id');

        if (!$file) {
            throw new ResourceException('请选择上传文件');
        }

        if ($file->getClientOriginalExtension() != 'txt') {
            throw new ResourceException('上传文件只支持 txt 格式');
        }

        if ($file->getSize() > 5000) {
            throw new ResourceException('上传文件过大');
        }

        $file_name = $file->getClientOriginalName();

        $r = preg_match('/([a-zA-Z0-9_]{10,50})\.txt/', $file_name, $matches);
        if (!$r) {
            throw new ResourceException('不允许的文件名');
        }

        $params = [
            'name' => $matches[1],
            'contents' => file_get_contents($file->getRealPath()),
            'company_id' => $company_id,
            'operator_id' => $operator_id,
        ];

        $verify_domain_service = new WorkWechatVerifyDomainService();
        $verify_domain_service->saveVerifyInfo($params);

        return $this->response->array(["status" => true]);
    }
}
