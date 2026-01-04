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

use CompanysBundle\Services\OperatorsService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use SuperAdminBundle\Services\CompanysService;
use CompanysBundle\Services\OperatorLogsService;
use CompanysBundle\Services\OperatorLogs\MysqlService;

class Companys extends Controller
{
    /**
     * @SWG\Get(
     *     path="/superadmin/companys/list",
     *     summary="获取商家列表",
     *     tags={"商家"},
     *     description="获取商家列表",
     *     operationId="getCompanysList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="company_name", in="query", description="公司名称", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码", required=false, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页数量", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="100", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="company_id", type="string", example="109", description="公司id"),
     *                          @SWG\Property( property="company_name", type="string", example="", description="公司名称"),
     *                          @SWG\Property( property="eid", type="string", example="66210101550", description="企业id"),
     *                          @SWG\Property( property="passport_uid", type="string", example="88210134288", description="passport_uid"),
     *                          @SWG\Property( property="company_admin_operator_id", type="string", example="195", description="公司管理员id"),
     *                          @SWG\Property( property="industry", type="string", example="null", description="所属行业"),
     *                          @SWG\Property( property="created", type="string", example="1612148167", description="创建时间"),
     *                          @SWG\Property( property="created_date", type="string", example="2021-02-01 10:56:07", description="创建时间"),
     *                          @SWG\Property( property="expiredAt", type="string", example="1613444167", description="过期时间"),
     *                          @SWG\Property( property="expiredAt_date", type="string", example="2021-02-16 10:56:07", description="过期时间"),
     *                          @SWG\Property( property="is_disabled", type="string", example="false", description="是否禁用"),
     *                          @SWG\Property( property="third_params", type="array",
     *                              @SWG\Items( type="string", example="undefined", description="第三方参数"),
     *                          ),
     *                          @SWG\Property( property="salesman_limit", type="string", example="20", description="导购员数量"),
     *                          @SWG\Property( property="is_open_pc_template", type="string", example="0", description="是否开启PC模板"),
     *                          @SWG\Property( property="operator", type="object",
     *                                  @SWG\Property( property="operator_id", type="string", example="195", description="操作员id"),
     *                                  @SWG\Property( property="mobile", type="string", example="18051847273", description="手机号"),
     *                                  @SWG\Property( property="login_name", type="string", example="null", description="登录账号名"),
     *                                  @SWG\Property( property="password", type="string", example="$2y$10$nn7xiCuZQD7YuixiPAIJtehTIuWuNI...", description="密码"),
     *                                  @SWG\Property( property="eid", type="string", example="66210105550", description="企业id"),
     *                                  @SWG\Property( property="passport_uid", type="string", example="88102334288", description="passport_uid"),
     *                                  @SWG\Property( property="operator_type", type="string", example="admin", description="操作员类型类型。admin:超级管理员;staff:员工;distributor:店铺管理员"),
     *                                  @SWG\Property( property="shop_ids", type="string", example="null", description="店铺id集合"),
     *                                  @SWG\Property( property="distributor_ids", type="string", example="null", description="门店id集合"),
     *                                  @SWG\Property( property="company_id", type="string", example="109", description="公司id"),
     *                                  @SWG\Property( property="username", type="string", example="null", description="姓名"),
     *                                  @SWG\Property( property="head_portrait", type="string", example="null", description="头像"),
     *                                  @SWG\Property( property="regionauth_id", type="string", example="0", description="区域id"),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getCompanysList(Request $request)
    {
        $inputData = $request->input();
        $filter = [];
        if (isset($inputData['company_name']) && $inputData['company_name']) {
            $filter['company_name|contains'] = $inputData['company_name'];
        }
        $page = $inputData['page'] ?? 1;
        $pageSize = $inputData['pageSize'] ?? 20;
        $salespersonService = new CompanysService();
        $result = $salespersonService->companys_list($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(path="/superadmin/companys",
     *   tags={"商家"},
     *   summary="修改公司信息",
     *   description="修改公司信息",
     *   operationId="updateCompany",
     *   produces={"application/json"},
     *   @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *   @SWG\Parameter(
     *     in="query",
     *     name="company_id",
     *     description="公司id",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="company_name",
     *     description="公司名称",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="expiredAt",
     *     description="有效期(Y-m-d)",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="is_disabled",
     *     description="是否禁用(true, false)",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="third_params",
     *     description="第三方参数",
     *     type="string"
     *   ),
     *   @SWG\Parameter( in="query", name="salesman_limit", description="导购员数量", type="integer" ),
     *   @SWG\Parameter( in="query", name="is_open_pc_template", description="是否开启PC模板", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="company_name", type="string", example="", description="公司名称"),
     *              @SWG\Property( property="expiredAt", type="string", example="1613444167", description="过期时间"),
     *              @SWG\Property( property="is_disabled", type="string", example="false", description="是否禁用"),
     *              @SWG\Property( property="third_params", type="array",
     *                  @SWG\Items( type="string", example="undefined", description="第三方参数"),
     *              ),
     *              @SWG\Property( property="salesman_limit", type="string", example="20", description="导购员数量"),
     *              @SWG\Property( property="is_open_pc_template", type="string", example="0", description="是否开启PC模板"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function updateCompany(Request $request)
    {
        $params = $request->input();
        $rules = [
            'company_id' => ['required', '公司id必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $companysService = new CompanysService();
        $result = $companysService->modifyCompanyInfo($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/superadmin/companys/logs",
     *     summary="获取商家日志列表",
     *     tags={"商家"},
     *     description="获取商家日志列表",
     *     operationId="getCompanysLogs",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司id", required=true, type="integer"),
     *     @SWG\Parameter( name="request_uri", in="query", description="请求资源", required=false, type="string"),
     *     @SWG\Parameter( name="params", in="query", description="操作参数", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码", required=false, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页数量", required=false, type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", required=false, type="integer"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="12334", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="log_id", type="string", example="125172", description="日志ID "),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                          @SWG\Property( property="operator_id", type="string", example="1", description="操作者id"),
     *                          @SWG\Property( property="request_uri", type="string", example="index.php/api/goods/category/1", description="请求资源信息"),
     *                          @SWG\Property( property="ip", type="string", example="58.33.145.138", description="ip地址"),
     *                          @SWG\Property( property="params", type="object",
     *                                  @SWG\Property( property="category_name", type="string", example="婴幼儿奶粉2", description="类目名称"),
     *                          ),
     *                          @SWG\Property( property="operator_name", type="string", example="更新单条分类信息", description="操作内容 "),
     *                          @SWG\Property( property="created", type="string", example="1612516650", description="创建时间"),
     *                          @SWG\Property( property="log_type", type="string", example="operator", description="操作日志类型"),
     *                          @SWG\Property( property="username", type="string", example="超级管理员", description="姓名"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getCompanysLogs(Request $request)
    {
        $inputData = $request->input();
        $rules = [
            'company_id' => ['required', '公司id必填'],
        ];
        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $filter = ['company_id' => $inputData['company_id']];
        if ($request->get('request_uri')) {
            $filter['request_uri|contains'] = $request->get('request_uri');
        }
        if ($request->get('params')) {
            $filter['params|contains'] = $request->get('params');
        }
        if ($request->get('start_time')) {
            $filter['created|lte'] = $request->get('start_time');
        }
        if ($request->get('end_time')) {
            $filter['created|gte'] = $request->get('end_time');
        }

        $page = $inputData['page'] ?? 1;
        $pageSize = $inputData['pageSize'] ?? 20;
        $operatorLogsService = new OperatorLogsService(new MysqlService());
        $result = $operatorLogsService->getLogsList($filter, $page, $pageSize, ['created' => 'DESC']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/distribution/protocol",
     *     summary="更改店务端用户协议",
     *     tags={"companys"},
     *     description="更改店务端用户协议",
     *     operationId="updateDistributionProtocol",
     *     @SWG\Parameter(
     *       in="query",
     *       name="title",
     *       description="协议标题",
     *       required=true,
     *       type="string"
     *     ),
     *     @SWG\Parameter(
     *       in="query",
     *       name="content",
     *       description="协议内容",
     *       required=true,
     *       type="string"
     *     ),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\response(
     *        response=200,
     *        description="成功返回结构",
     *        @SWG\schema(
     *            @SWG\property(
     *                property="data",
     *                type="object",
     *                @SWG\Property(property="id", type="string", description="协议id", example="1"),
     *                @SWG\Property(property="type", type="string", description="协议类型： app迎使用商派云店", example="app"),
     *                @SWG\Property(property="title", type="string", description="协议标题", example="欢迎使用商派云店"),
     *                @SWG\Property(property="content", type="string", description="协议内容", example="本《最终用户使用许可协议》（以下称《协议》）是您（个人或单一实体）与..."),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function UpdateDistributionProtocol(Request $request)
    {
        $opService = new OperatorsService();
        $licenseInfo = $opService->setLicenseInfo([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
        ]);
        return $this->response->array($licenseInfo);
    }
}
