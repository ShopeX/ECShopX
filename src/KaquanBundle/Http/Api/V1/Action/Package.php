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

namespace KaquanBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Response;
use Illuminate\Http\Request;
use KaquanBundle\Services\PackageEditService;
use KaquanBundle\Services\PackageQueryService;
use KaquanBundle\Services\PackageReceivesService;

class Package extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/voucher/package/list",
     *     summary="卡券包列表",
     *     tags={"卡券包"},
     *     description="查找已添加的卡券包列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="params.page", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="params.page_size", in="query", description="每页数量", required=true, type="string"),
     *     @SWG\Parameter( name="params.title", in="query", description="搜索标题", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="integer", example="2", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="package_id", type="string", example="1", description="卡券包ID"),
     *                          @SWG\Property( property="title", type="string", example="我的券包", description="卡券包标题"),
     *                          @SWG\Property( property="package_describe", type="string", example="描述", description="卡券包描述"),
     *                          @SWG\Property( property="limit_count", type="string", example="2", description="限领数量"),
     *                          @SWG\Property( property="get_num", type="string", example="2", description="已发放数量"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function getList(Request $request): Response
    {
        $companyId = app('auth')->user()->get('company_id');

        // 参数格式适配 前端finder组件
        $params = $request->input();
        if (isset($params['pageSize']) && $params['pageSize'] > 0) {
            $params['page_size'] = $params['pageSize'];
        }

        $rules = [
            'page' => ['required|integer|min:1', trans('KaquanBundle.page_required')],
            'page_size' => ['required|integer|min:1', trans('KaquanBundle.page_size_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $title = $params['title'] ?? '';

        $result = (new PackageQueryService())->getList($companyId, $page, $pageSize, $title);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/voucher/package/details",
     *     summary="卡券包详情",
     *     tags={"卡券包"},
     *     description="查找卡券包的详情",
     *     operationId="getDetails",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="package_id", in="query", description="卡券包ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="package_id", type="string", example="1", description="卡券包ID"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                  @SWG\Property( property="title", type="string", example="券包标题", description="卡券包标题"),
     *                  @SWG\Property( property="package_describe", type="string", example="描述", description="卡券包描述"),
     *                  @SWG\Property( property="limit_count", type="string", example="2", description="卡券包限领数量"),
     *                  @SWG\Property( property="get_num", type="string", example="2", description="已发放数量"),
     *                  @SWG\Property( property="discount_cards", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="give_num", type="string", example="1", description="卡券发送数量"),
     *                          @SWG\Property( property="takeEffect", type="string", example="领取后当天生效,90天有效", description="有效期文字"),
     *                          @SWG\Property( property="card_id", type="string", example="825", description="卡券ID"),
     *                          @SWG\Property( property="card_type", type="string", example="discount", description="卡券类型，discount:折扣券;cash:代金券;gift:兑换券;new_gift:兑换券(新)"),
     *                          @SWG\Property( property="title", type="string", example="测试优惠券", description="优惠券标题"),
     *                          @SWG\Property( property="date_type", type="string", example="DATE_TYPE_FIX_TERM", description="有效期类型"),
     *                          @SWG\Property( property="description", type="string", example="", description="卡券使用说明"),
     *                          @SWG\Property( property="begin_date", type="string", example="2021-09-29 14:40:53", description="有效期开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="2021-09-29 14:40:53", description="有效期结束时间"),
     *                          @SWG\Property( property="fixed_term", type="string", example="1", description="有效期的有效天数"),
     *                          @SWG\Property( property="quantity", type="string", example="1", description="总库存数量"),
     *                          @SWG\Property( property="receive", type="string", example="true", description="是否前台直接领取"),
     *                          @SWG\Property( property="kq_status", type="string", example="0", description="卡券状态 0:正常 1:暂停 2:关闭"),
     *                          @SWG\Property( property="grade_ids", type="string", example="", description="等级限制"),
     *                          @SWG\Property( property="vip_grade_ids", type="string", example="", description="vip等级限制"),
     *                          @SWG\Property( property="get_limit", type="string", example="1", description="卡券已发放数量"),
     *                          @SWG\Property( property="gift", type="string", example="", description="兑换券兑换内容名称"),
     *                          @SWG\Property( property="default_detail", type="string", example="", description="优惠券优惠详情"),
     *                          @SWG\Property( property="discount", type="string", example="1", description="折扣券打折额度（百分比)"),
     *                          @SWG\Property( property="least_cost", type="string", example="1", description="代金券起用金额"),
     *                          @SWG\Property( property="reduce_cost", type="string", example="1", description="代金券减免金额 or 兑换券起用金额"),
     *                          @SWG\Property( property="get_num", type="string", example="1", description="卡券已发数量"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function getDetails(Request $request): Response
    {
        $companyId = app('auth')->user()->get('company_id');
        $packageId = (int)$request->input('package_id');
        $rules = [
            'package_id' => ['required|integer|min:1', trans('KaquanBundle.package_id_required')],
        ];
        $errorMessage = validator_params(['package_id' => $packageId], $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $details = (new PackageQueryService())->getDetails($companyId, $packageId);

        return $this->response->array($details);
    }

    /**
     * @SWG\Get(
     *     path="/voucher/package/check_grade_limit",
     *     summary="检测包内优惠券等级限制，获取不可领用的等级",
     *     tags={"卡券包"},
     *     description="检测包内优惠券等级限制，获取不可领用的等级",
     *     operationId="checkCardPackageGradeLimit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="package_id_list", in="query", description="卡券包ID数组", required=true, type="string"),
     *     @SWG\Parameter( name="set_type", in="query", description="设置的类型：vip_grade-付费会员等级,grade-会员等级", required=true, type="string"),
     *     @SWG\Parameter( name="grade_id", in="query", description="等级ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="package_title", type="string", example="模板测试优惠券包", description="券包标题"),
     *                  @SWG\Property( property="title", type="string", example="模板测试优惠券", description="优惠券标题"),
     *                  @SWG\Property( property="grade_id", type="string", example="27", description="等级ID"),
     *                  @SWG\Property( property="grade_name", type="string", example="黄金会员", description="等级名称"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function checkCardPackageGradeLimit(Request $request): Response
    {
        $companyId = app('auth')->user()->get('company_id');
        $packageIdList = (array)$request->input('package_id_list');
        $setType = (string)$request->input('set_type');
        $gradeId = (int)$request->input('grade_id');

        $rules = [
            'package_id_list' => ['required', trans('KaquanBundle.package_id_list_required')],
            'set_type' => ['required|string|in:vip_grade,grade', trans('KaquanBundle.set_type_required')],
            'grade_id' => ['required|integer|min:1', trans('KaquanBundle.grade_id_required')],
        ];

        $inputData = [
            'package_id_list' => $packageIdList ?? [],
            'set_type' => $setType,
            'grade_id' => $gradeId
        ];

        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $checkResult = [];
        foreach ($packageIdList as $packageId) {
            $tempCheckResult = (new PackageQueryService())->checkCardPackageGradeLimit($companyId, $packageId, $setType, $gradeId);
            $checkResult = array_merge($checkResult, $tempCheckResult);
        }

        return $this->response->array($checkResult);
    }

    /**
     * @SWG\Get(
     *     path="/voucher/package/get_receives_log",
     *     summary="卡券包发送日志",
     *     tags={"卡券包"},
     *     description="查找卡券包发送日志",
     *     operationId="getPackageReceivesLog",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="params.package_id", in="query", description="卡券包ID", required=true, type="string"),
     *     @SWG\Parameter( name="params.page", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="params.page_size", in="query", description="每页数量", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="integer", example="2", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="user_id", type="string", example="1", description="用户ID"),
     *                          @SWG\Property( property="receive_type", type="string", example="template", description="接收类型:template/grade/vip_grade 模版领取/等级领取/vip等级领取"),
     *                          @SWG\Property( property="receive_time", type="string", example="2021-09-28 11:28:50", description="发送时间"),
     *                          @SWG\Property( property="username", type="string", example="", description="用户名"),
     *                          @SWG\Property( property="mobile", type="string", example="", description="手机号码"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function getPackageReceivesLog(Request $request): Response
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputData = $request->input();
        if (is_string($inputData)) {
            $inputData = json_decode($inputData, true);
        }
        if (isset($inputData['pageSize']) && $inputData['pageSize'] > 0) {
            $inputData['page_size'] = $inputData['pageSize'];
        }
        $rules = [
            'package_id' => ['required|integer|min:1', trans('KaquanBundle.package_id_required')],
            'page' => ['required|integer|min:1', trans('KaquanBundle.page_required')],
            'page_size' => ['required|integer|min:1', trans('KaquanBundle.page_size_required')],
        ];

        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $needEncode = (bool)$request->get('x-datapass-block');

        $result = (new PackageReceivesService())->getPackageReceivesLog($companyId, $inputData['package_id'], $inputData['page'], $inputData['page_size'], $needEncode);

        return $this->response->array(['list' => $result['list'], 'count' => $result['total_count']]);
    }

    /**
     * @SWG\Post(
     *     path="/voucher/package",
     *     summary="创建卡券包",
     *     tags={"卡券包"},
     *     description="",
     *     operationId="createPackage",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="limit_count", in="query", description="限领数量", required=true, type="string"),
     *     @SWG\Parameter( name="package_describe", in="query", description="卡券包描述", required=false, type="string"),
     *     @SWG\Parameter( name="package_content", in="query", description="卡券包内容此参数为数组，最多为20张卡券", required=true, type="string"),
     *     @SWG\Parameter( name="package_content.card_id", in="query", description="卡券包内容此参数为数组，卡券ID", required=true, type="string"),
     *     @SWG\Parameter( name="package_content.give_num", in="query", description="卡券包内容此参数为数组，领取数量", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="bool", example="true", description="创建结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function createPackage(Request $request): Response
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputData = $request->all();

        $rules = [
            'title' => ['required|max:10', trans('KaquanBundle.package_title_required')],
            'limit_count' => ['min:1', trans('KaquanBundle.limit_count_min')],
            'package_describe' => ['max:20', trans('KaquanBundle.package_describe_max')],
            'package_content' => ['required', trans('KaquanBundle.package_content_required')],
        ];
        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $result = (new PackageEditService())->createPackage($companyId, $inputData);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Patch(
     *     path="/voucher/package",
     *     summary="编辑卡券包",
     *     tags={"卡券包"},
     *     description="",
     *     operationId="editPackage",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="package_id", in="query", description="卡券包ID", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="query", description="卡券包标题", required=true, type="string"),
     *     @SWG\Parameter( name="package_describe", in="query", description="卡券包描述", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="bool", example="true", description="修改结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function editPackage(Request $request): Response
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputData = $request->all();

        $rules = [
            'package_id' => ['required|integer|min:1', trans('KaquanBundle.package_id_required')],
            'title' => ['required|max:10', trans('KaquanBundle.package_title_required')],
            'package_describe' => ['max:20', trans('KaquanBundle.package_describe_max')],
        ];
        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $result = (new PackageEditService())->editPackage($companyId, $inputData);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Delete(
     *     path="/voucher/package",
     *     summary="删除卡券包",
     *     tags={"卡券包"},
     *     description="",
     *     operationId="deletePackage",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="package_id", in="query", description="卡券包ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="bool", example="true", description="删除结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function deletePackage(Request $request): Response
    {
        $companyId = app('auth')->user()->get('company_id');

        $packageId = $request->input('package_id');
        $rules = [
            'package_id' => ['required|integer|min:1', trans('KaquanBundle.package_id_required')],
        ];
        $errorMessage = validator_params(['package_id' => $packageId], $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $result = (new PackageEditService())->deletePackage($companyId, $packageId);

        return $this->response->array(['status' => $result]);
    }
}
