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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Member;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Filter\Member\MemberFilter;
use OpenapiBundle\Http\Controllers\Controller;
use OpenapiBundle\Jobs\CreateMemberJob;
use OpenapiBundle\Rules\MobileRule;
use OpenapiBundle\Services\Member\MemberCardVipGradeRelUserService;
use OpenapiBundle\Services\Member\MemberInfoService;
use OpenapiBundle\Services\Member\MemberService;
use OpenapiBundle\Traits\Member\MemberTrait;
use Swagger\Annotations as SWG;

/**
 * 会员信息 V1
 * Class MemberInfoController
 * @package OpenapiBundle\Http\Api\V2\Action\Member
 */
class MemberController extends Controller
{
    use MemberTrait;

    /**
     * @SWG\Post(
     *     path="/ecx.member.batch_create",
     *     tags={"会员"},
     *     summary="创建会员 - 批量 - 异步",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="batchCreate",
     *
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(
     *         name="data",
     *         in="formData",
     *         description="json数据，批量创建会员的多个会员信息（具体创建会员的规则与创建单个会员相同）",
     *         required=true,
     *         type="array",
     *         @SWG\Items(
     *             type="array",
     *             @SWG\Items(
     *                 @SWG\Property(property="mobile", description="会员手机号（会员ID、会员手机号，二选一必填）", type="string", default="17366666666"),
     *                 @SWG\Property(property="inviter_mobile", description="推荐人的手机号", type="string", default=""),
     *                 @SWG\Property(property="status", description="用户的状态【0 已禁用】【1 未禁用】", type="integer", default="1"),
     *                 @SWG\Property(property="remarks", description="用户的备注信息", type="string", default=""),
     *                 @SWG\Property(property="username", description="姓名/昵称", type="string", default=""),
     *                 @SWG\Property(property="avatar", description="用户头像的url", type="string", default=""),
     *                 @SWG\Property(property="sex", description="用户的性别【0 未知】【1 男】【2 女】", type="integer", default="0"),
     *                 @SWG\Property(property="birthday", description="用户的生日 yyyy-MM-dd HH:mm:ss 或 yyyy-MM-dd", type="string", default=""),
     *                 @SWG\Property(property="habbit", description="用户的爱好(json数据，数组对象里，对象的name为爱好名称，对象的ischecked为爱好是否为选中（true表示选中，false表示未选中）)", type="array",
     *                     @SWG\Items(
     *                         type="array",
     *                         @SWG\Items(
     *                             @SWG\Property(property="name", description="爱好名称", type="string", default="爱好1"),
     *                             @SWG\Property(property="ischecked", description="爱好是否为选中（true表示选中，false表示未选中）", type="boolean", default="false"),
     *                         ),
     *                     ),
     *                 ),
     *                 @SWG\Property(property="edu_background", description="用户的学历【0 硕士及以上】【1 本科】【2 大专】【3 高中/中专及以下】【4 其他】", type="integer", default=""),
     *                 @SWG\Property(property="income", description="用户的年收入【0 5万以下】【1 5万 ~ 15万】【2 15万 ~ 30万】【3 30万以上】【4 其他】", type="integer", default=""),
     *                 @SWG\Property(property="industry", description="用户的行业【0 金融/银行/投资】【1 计算机/互联网】【2 媒体/出版/影视/文化】【3 政府/公共事业】【4 房地产/建材/工程】【5 咨询/法律】【6 加工制造】【7 教育培训】【8 医疗保健】【9 运输/物流/交通】【10 零售/贸易】【11 旅游/度假】【12 其他】", type="integer", default="12"),
     *                 @SWG\Property(property="email", description="用户的邮箱", type="string", default=""),
     *                 @SWG\Property(property="address", description="用户的地址", type="string", default=""),
     *             )
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",required={"status"},
     *                @SWG\Property(property="status", type="integer", default="", description="操作状态"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function batchCreate(Request $request)
    {
        // 获取企业id
        $companyId = $this->getCompanyId();
        // 获取批量数据
        if (!$request->has("data")) {
            return $this->response->array(["status" => 0]);
        }
        $data = (array)jsonDecode($request->input("data"));
        if (count($data) > 50) {
            throw new ErrorException(ErrorCode::MEMBER_ERROR, "批量创建最多支持50个！");
        }
        // 队列数据
        $jobData = [];
        // 批量验证
        foreach ($data as $datum) {
            // 替换请求体的内容
            $request->replace($datum);
            // 参数验证 && 拼接参数
            $jobData[] = $this->checkCreateFormDataAndHandle($request);
        }
        // 异步批量处理
        foreach ($jobData as $jobDatum) {
            $job = (new CreateMemberJob($companyId, $jobDatum))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }

        return $this->response->array(["status" => 1]);
    }

    /**
     * @SWG\Patch(
     *     path="/ecx.member_info.update",
     *     tags={"会员"},
     *     summary="修改会员 - 信息",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="updateDetail",
     *
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="user_id", in="formData", description="会员ID（会员ID、会员手机号，二选一必填）", required=false, type="integer",),
     *     @SWG\Parameter(name="mobile", in="formData", description="会员手机号（会员ID、会员手机号，二选一必填）", required=false, type="string",),
     *     @SWG\Parameter(name="inviter_mobile", in="formData", description="推荐人的手机号", required=false, type="string"),
     *     @SWG\Parameter(name="status", in="formData", description="用户的状态【0 已禁用】【1 未禁用】", required=false, type="integer"),
     *     @SWG\Parameter(name="remarks", in="formData", description="用户的备注信息", required=false, type="string"),
     *     @SWG\Parameter(name="username", in="formData", description="姓名/昵称", required=false, type="string"),
     *     @SWG\Parameter(name="avatar", in="formData", description="用户头像的url", required=false, type="string"),
     *     @SWG\Parameter(name="sex", in="formData", description="用户的性别【0 未知】【1 男】【2 女】", required=false, type="integer"),
     *     @SWG\Parameter(name="birthday", in="formData", description="用户的生日 yyyy-MM-dd HH:mm:ss 或 yyyy-MM-dd", required=false, type="string"),
     *     @SWG\Property(property="habbit", description="用户的爱好(json数据，数组对象里，对象的name为爱好名称，对象的ischecked为爱好是否为选中（true表示选中，false表示未选中）)", type="array",
     *         @SWG\Items(
     *             type="array",
     *             @SWG\Items(
     *                 @SWG\Property(property="name", description="爱好名称", type="string", default="爱好1"),
     *                 @SWG\Property(property="ischecked", description="爱好是否为选中（true表示选中，false表示未选中）", type="boolean", default="false"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Parameter(name="edu_background", in="formData", description="用户的学历【0 硕士及以上】【1 本科】【2 大专】【3 高中/中专及以下】【4 其他】", required=false, type="integer"),
     *     @SWG\Parameter(name="income", in="formData", description="用户的年收入【0 5万以下】【1 5万 ~ 15万】【2 15万 ~ 30万】【3 30万以上】【4 其他】", required=false, type="integer"),
     *     @SWG\Parameter(name="industry", in="formData", description="用户的行业【0 金融/银行/投资】【1 计算机/互联网】【2 媒体/出版/影视/文化】【3 政府/公共事业】【4 房地产/建材/工程】【5 咨询/法律】【6 加工制造】【7 教育培训】【8 医疗保健】【9 运输/物流/交通】【10 零售/贸易】【11 旅游/度假】【12 其他】", required=false, type="integer"),
     *     @SWG\Parameter(name="email", in="formData", description="用户的邮箱", required=false, type="string"),
     *     @SWG\Parameter(name="address", in="formData", description="用户的地址", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="array", description="",
     *               @SWG\Items(
     *               ),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function updateDetail(Request $request)
    {
        // 过滤条件
        $filter = [
            "company_id" => $this->getCompanyId(), // 设置企业id
        ];
        // 设置用户id和手机号
        $this->checkMobile($request, $filter);
        // 参数验证
        if ($messageBag = validation($request->input(), [
            "inviter_mobile" => ["nullable", new MobileRule()],
            "status" => ["nullable", Rule::in([0, 1])],
            "remarks" => ["nullable", "string"],
            "username" => ["nullable", "string"],
            "avatar" => ["nullable", "string"],
            "sex" => ["nullable", Rule::in(key(MemberInfoService::SEX_MAP))],
            "birthday" => ["nullable", "date"],
            "habbit" => ["nullable"],
//            "habbit.*.name"      => ["required", "string"],
//            "habbit.*.ischecked" => ["required", "boolean"],
            "edu_background" => ["nullable", Rule::in(array_keys(MemberInfoService::EDU_BACKGROUND_MAP))],
            "income" => ["nullable", Rule::in(array_keys(MemberInfoService::INCOME_MAP))],
            "industry" => ["nullable", Rule::in(array_keys(MemberInfoService::INDUSTRY_MAP))],
            "email" => ["nullable", "email"],
            "address" => ["nullable", "string"],
        ], [
            "inviter_mobile.*" => "推荐人手机号填写错误",
            "status.*" => "会员状态填写错误",
            "remarks.*" => "备注填写错误",
            "username.*" => "姓名/昵称填写错误",
            "avatar.*" => "头像url填写错误",
            "sex.*" => "性别填写错误",
            "birthday.*" => "生日填写错误",
            "habbit.*" => "爱好填写错误",
            "edu_background.*" => "学历填写错误",
            "income.*" => "年收入填写错误",
            "industry.*" => "行业填写错误",
            "email.*" => "email填写错误",
            "address.*" => "地址填写错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        // 更新的数据
        $requestData = [
            "inviter_mobile" => (string)$request->input("inviter_mobile", 0), // 推荐人手机号
            "status" => (int)$request->input("status", 1), // 会员的状态，【0 已禁用】【1 未禁用】
            "remakes" => (string)$request->input("remakes"), // 备注
            "username" => (string)$request->input("username"), // 姓名
            "avatar" => (string)$request->input("avatar"), // 头像url
            "sex" => (string)$request->input("sex", 0), // 性别，【0 未知】【1 男】【2 女】
            "birthday" => (string)$request->input("birthday"), // 生日，日期格式 2021-06-16 15:35:41
            "habbit" => (array)jsonDecode($request->input("habbit")), // 爱好
            "edu_background" => (int)$request->input("edu_background", 4), // 学历
            "income" => (int)$request->input("income", 4), // 年收入
            "industry" => (int)$request->input("industry", 12), // 行业
            "email" => (string)$request->input("email"), // email
            "address" => (string)$request->input("address"), // 地址
        ];
        // 更新
        (new MemberService())->update($filter, $requestData);
        return $this->response->array([]);
    }

    /**
     * @SWG\Patch(
     *     path="/ecx.member_mobile.update",
     *     tags={"会员"},
     *     summary="修改会员 - 手机号",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="updateDetail",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="user_id", in="formData", description="会员ID（会员ID、会员手机号，二选一必填）", required=false, type="integer"),
     *     @SWG\Parameter(name="mobile", in="formData", description="会员手机号（会员ID、会员手机号，二选一必填）", required=false, type="string"),
     *     @SWG\Parameter(name="new_mobile", in="formData", description="会员新手机号", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="array", description="",
     *               @SWG\Items(
     *               ),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function updateMobile(Request $request)
    {
        // 设置过滤条件
        $filter = ["company_id" => $this->getCompanyId()];
        // 设置用户id和手机号
        $this->checkMobile($request, $filter);
        // 参数验证
        if ($messageBag = validation($request->input(), [
            "new_mobile" => ["required", new MobileRule()],
        ], [
            "new_mobile.required" => "会员新手机号参数错误"
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        // 更新手机号
        (new MemberService())->updateMobile($filter, (string)$request->input("new_mobile"));
        return $this->response->array([]);
    }

    /**
     * @SWG\Patch(
     *     path="/ecx.member_card_code_grade.update",
     *     tags={"会员"},
     *     summary="修改会员 - 卡号、等级",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="delete",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="mobile", in="formData", description="手机号", required=true, type="string"),
     *     @SWG\Parameter(name="grade_id", in="formData", description="等级ID", required=false, type="integer"),
     *     @SWG\Parameter(name="card_code", in="formData", description="会员卡号", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="string", default="", description=""),
     *         ),
     *     ),
     * )
     */
    public function updateCardCodeAndGrade(Request $request)
    {
        // 过滤条件
        $requestFilter = $request->only(["mobile"]);
        $filter = (new MemberFilter($requestFilter))->get();
        // 更新数据
        $requestData = $request->only(["user_card_code", "grade_id"]);
        // 参数验证
        if ($messageBag = validation(array_merge($requestData, $filter), [
            "mobile" => ["required", new MobileRule()],
            "user_card_code" => ["nullable", "string"],
            "grade_id" => ["nullable", "numeric"],
        ], [
            "mobile.required" => "手机号必填",
            "user_card_code.*" => "会员卡号参数错误",
            "grade_id.*" => "等级ID参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        // 更新数据
        (new MemberService())->updateDetail($filter, $requestData);
        return $this->response->array([]);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member_card_grade_rel.list",
     *     tags={"会员"},
     *     summary="会员等级关联的会员查询 - 批量",
     *     description="会员等级关联的会员查询 - 批量",
     *     operationId="listByGradeOrVipGrade",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="page", in="query", description="当前页数	1", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）", required=false, type="integer"),
     *     @SWG\Parameter(name="grade_id", in="query", description="会员等级ID（会员等级ID、付费会员等级ID，二选一必填）", required=false, type="string"),
     *     @SWG\Parameter(name="vip_grade_id", in="query", description="付费会员等级ID（会员等级ID、付费会员等级ID，二选一必填）", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", example="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",required={"total_count","list","is_last_page","pager"},
     *                @SWG\Property(property="total_count", type="integer", example="187", description="列表数据总数量"),
     *                @SWG\Property(property="list", type="array", description="",
     *                  @SWG\Items(required={"mobile","username"},
     *                            @SWG\Property(property="mobile", type="string", example="13042418048", description="会员手机号"),
     *                            @SWG\Property(property="username", type="string", example="一米阳光", description="会员姓名/昵称	"),
     *                  ),
     *                ),
     *                @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                @SWG\Property(property="pager", type="object", description="分页相关信息",required={"page","page_size"},
     *                     @SWG\Property(property="page", type="integer", default="1", description="当前页数"),
     *                     @SWG\Property(property="page_size", type="integer", default="10", description="每页显示数量（默认20条）"),
     *                ),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function listByGradeOrVipGrade(Request $request)
    {
        // 设置过滤条件
        $companyId = $this->getCompanyId();
        // 参数验证
        $requestFilter = $request->only(["grade_id", "vip_grade_id"]);
        if ($messageBag = validation($requestFilter, [
            "grade_id" => ["required_without_all:vip_grade_id", "integer"],
            "vip_grade_id" => ["required_without_all:grade_id", "integer"],
        ], [
            "grade_id.*" => "会员等级ID参数错误",
            "vip_grade_id.*" => "付费会员等级ID参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        // 同时存在就报错
        if (isset($requestFilter["grade_id"]) && isset($requestFilter["vip_grade_id"])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "会员等级ID与付费会员等级ID不能同时存在");
        }
        if (isset($requestFilter["grade_id"])) {
            // 会员等级查询
            $result = (new MemberService())->list(["company_id" => $companyId, "grade_id" => $requestFilter["grade_id"]], $this->getPage(), $this->getPageSize(), ["user_id" => "DESC"]);
            if (!empty($result["list"])) {
                $this->appendDetailToList($companyId, $result["list"]);
            }
        } else {
            // 付费会员等级查询
            $result = (new MemberCardVipGradeRelUserService())->list(["company_id" => $companyId, "vip_grade_id" => $requestFilter["vip_grade_id"]], $this->getPage(), $this->getPageSize(), ["user_id" => "DESC"]);
            if (!empty($result["list"])) {
                $this->appendInfoToList($companyId, $result["list"], true);
                $this->appendDetailToList($companyId, $result["list"]);
            }
        }
        // 数据统一处理
        foreach ($result["list"] as &$item) {
            $item = [
                "mobile" => (string)($item["mobile"] ?? ""),
                "username" => (string)($item["username"] ?? ""),
            ];
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.create",
     *     tags={"会员"},
     *     summary="创建会员 - 单个",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="create",
     *
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="mobile", in="formData", description="手机号", required=true, type="string"),
     *     @SWG\Parameter(name="inviter_mobile", in="formData", description="推荐人的手机号", required=false, type="string"),
     *     @SWG\Parameter(name="salesperson_mobile", in="formData", description="导购手机号", required=false, type="string"),
     *     @SWG\Parameter(name="union_id", in="formData", description="用户的unionid", required=false, type="string"),
     *     @SWG\Parameter(name="status", in="formData", description="用户的状态【0 已禁用】【1 未禁用】", required=false, type="integer"),
     *
     *     @SWG\Parameter(name="tag_names", in="formData", description="标签名（标签名、标签ID可二选一填写，商派系统已创建的标签），多个用逗号隔开", required=false, type="string"),
     *     @SWG\Parameter(name="tag_ids", in="formData", description="标签ID（标签名、标签ID可二选一填写，商派系统已创建的标签），多个用逗号隔开", required=false, type="string"),
     *
     *     @SWG\Parameter(name="card_code", in="formData", description="用户的会员卡号，如果没填则会自动生成", required=false, type="string"),
     *     @SWG\Parameter(name="grade_id", in="formData", description="会员等级ID（商派系统已创建的等级）", required=false, type="integer"),
     *     @SWG\Parameter(name="username", in="formData", description="姓名/昵称", required=false, type="string"),
     *     @SWG\Parameter(name="avatar", in="formData", description="用户头像的url", required=false, type="string"),
     *     @SWG\Parameter(name="sex", in="formData", description="用户的性别【0 未知】【1 男】【2 女】", required=false, type="integer"),
     *     @SWG\Parameter(name="birthday", in="formData", description="用户的生日 yyyy-MM-dd HH:mm:ss 或 yyyy-MM-dd", required=false, type="string"),
     *     @SWG\Property(property="habbit", description="用户的爱好(json数据，数组对象里，对象的name为爱好名称，对象的ischecked为爱好是否为选中（true表示选中，false表示未选中）)", type="array",
     *         @SWG\Items(
     *             type="array",
     *             @SWG\Items(
     *                 @SWG\Property(property="name", description="爱好名称", type="string", default="爱好1"),
     *                 @SWG\Property(property="ischecked", description="爱好是否为选中（true表示选中，false表示未选中）", type="boolean", default="false"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Parameter(name="edu_background", in="formData", description="用户的学历【0 硕士及以上】【1 本科】【2 大专】【3 高中/中专及以下】【4 其他】", required=false, type="integer"),
     *     @SWG\Parameter(name="income", in="formData", description="用户的年收入【0 5万以下】【1 5万 ~ 15万】【2 15万 ~ 30万】【3 30万以上】【4 其他】", required=false, type="integer"),
     *     @SWG\Parameter(name="industry", in="formData", description="用户的行业【0 金融/银行/投资】【1 计算机/互联网】【2 媒体/出版/影视/文化】【3 政府/公共事业】【4 房地产/建材/工程】【5 咨询/法律】【6 加工制造】【7 教育培训】【8 医疗保健】【9 运输/物流/交通】【10 零售/贸易】【11 旅游/度假】【12 其他】", required=false, type="integer"),
     *     @SWG\Parameter(name="email", in="formData", description="用户的邮箱", required=false, type="string"),
     *     @SWG\Parameter(name="address", in="formData", description="用户的地址", required=false, type="string"),
     *     @SWG\Parameter(name="remarks", in="formData", description="用户的备注信息", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",required={"user_id","mobile"},
     *                @SWG\Property(property="user_id", type="integer", default="", description="用户id"),
     *                @SWG\Property(property="mobile", type="string", default="", description="手机号"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function create(Request $request)
    {
        // 获取企业id
        $auth = (array)$request->attributes->get("auth");
        $companyId = $auth["company_id"];
        // 参数验证 && 拼接参数
        $requestData = $this->checkCreateFormDataAndHandle($request);
        // 插入数据
        $result = (new MemberService())->createDetail($companyId, $requestData);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.list",
     *     tags={"会员"},
     *     summary="查询会员 - 批量",
     *     description="查询会员 - 批量",
     *     operationId="list",
     *
     * @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     * @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     * @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     * @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     * @SWG\Parameter(name="mobile", in="query", description="会员手机号", required=false, type="string"),
     * @SWG\Parameter(name="source_from", in="query", description="来源渠道【default 小程序端/后台导入】【openapi 通过公共api创建的会员】", required=false, type="string"),
     * @SWG\Parameter(name="inviter_mobile", in="query", description="推荐人的会员手机号", required=false, type="string"),
     * @SWG\Parameter(name="salesperson_mobile", in="query", description="绑定的导手机号", required=false, type="string"),
     * @SWG\Parameter(name="status", in="query", description="会员状态【0 已禁用】【1 未禁用】", required=false, type="integer"),
     * @SWG\Parameter(name="tag_id", in="query", description="会员标签ID", required=false, type="integer"),
     * @SWG\Parameter(name="tag_name", in="query", description="会员标签名称", required=false, type="string"),
     * @SWG\Parameter(name="have_consume", in="query", description="有无购买记录【0 没有购买记录】【1 有购买记录】", required=false, type="integer"),
     * @SWG\Parameter(name="user_card_code", in="query", description="会员卡号", required=false, type="string"),
     * @SWG\Parameter(name="grade_id", in="query", description="会员等级ID", required=false, type="integer"),
     * @SWG\Parameter(name="grade_name", in="query", description="会员等级名称", required=false, type="string"),
     * @SWG\Parameter(name="vip_grade_id", in="query", description="付费等级ID", required=false, type="integer"),
     * @SWG\Parameter(name="vip_grade_name", in="query", description="付费等级名称", required=false, type="string"),
     * @SWG\Parameter(name="start_date", in="query", description="开始时间（会员创建时间, 时间格式：yyyy-MM-dd HH:mm:ss）", required=false, type="string"),
     * @SWG\Parameter(name="end_date", in="query", description="结束时间（会员创建时间, 时间格式：yyyy-MM-dd HH:mm:ss）", required=false, type="string"),
     * @SWG\Parameter(name="page", in="query", description="当前页数    1", required=false, type="integer"),
     * @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）", required=false, type="integer"),
     * @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *        @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="响应数据", required={"page","page_size","total_count","is_last_page","list"},
     *               @SWG\Property(property="pager", type="object", description="分页相关信息",
     *                    @SWG\Property(property="page", type="integer", default="1", description="当前页数"),
     *                    @SWG\Property(property="page_size", type="integer", default="10", description="每页显示数量（默认20条）"),
     *               ),
     *               @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *               @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *               @SWG\Property(property="list", type="array", description="列表信息（集合）",
     *                 @SWG\Items(required={"user_id","mobile","source_from","created","updated","remarks","grade_id","user_card_code","username","avatar","sex","birthday","habbit","edu_background","income","industry","email","address","unionid","status","inviter","salesperson","tags","vip_grades"},
     *                           @SWG\Property(property="user_id", type="string", default="20576", description="会员ID"),
     *                           @SWG\Property(property="mobile", type="string", default="13042411111", description="会员手机号"),
     *                           @SWG\Property(property="source_from", type="string", default="default", description="来源渠道 【default 小程序端/后台导入】【openapi 通过公共api创建的会员】"),
     *                           @SWG\Property(property="created", type="string", default="2021-06-22 11:22:55", description="会员创建时间(日期格式:yyyy-MM-dd HH:mm:ss)"),
     *                           @SWG\Property(property="updated", type="string", default="2021-06-22 11:22:55", description="会员更新时间(日期格式:yyyy-MM-dd HH:mm:ss)"),
     *                           @SWG\Property(property="remarks", type="string", default="", description="备注"),
     *                           @SWG\Property(property="grade_id", type="string", default="4", description="会员等级"),
     *                           @SWG\Property(property="vip_grade_id", type="string", default="4", description="付费会员等级"),
     *                           @SWG\Property(property="user_card_code", type="string", default="0CA4D083E1A0", description="会员卡号"),
     *                           @SWG\Property(property="username", type="string", default="一米阳光", description="姓名/昵称"),
     *                           @SWG\Property(property="avatar", type="string", default="https://thirdwx.qlogo.cn/mmopen/vi_32/OCMJXwhqBk9tEZ6WKWv6aEm89XTtOXI9J2zS5DJXwr0mSXbN2RXB7Ct7p7c37N6icC5lsOzLP6fTGtfCzFans0Q/132", description="头像url"),
     *                           @SWG\Property(property="sex", type="integer", default="0", description="性别【0 未知】【1 男】【2 女】"),
     *                           @SWG\Property(property="birthday", type="string", default="", description="生日 (日期格式:yyyy-MM-dd HH:mm:ss)"),
     *                           @SWG\Property(property="habbit", type="array", description="爱好（集合）",
     *                               @SWG\Items(
     *                                   @SWG\Property(property="ischecked", type="boolean", default="false", description="是否选中, 【true 选中】【false 未选中】"),
     *                                   @SWG\Property(property="name", type="string", default="游戏", description="该选项的名字"),
     *                               ),
     *                           ),
     *                           @SWG\Property(property="edu_background", type="integer", default="0", description="学历【0 硕士及以上】【1 本科】【2 大专】【3 高中/中专及以下】【4 其他】"),
     *                           @SWG\Property(property="income", type="integer", default="0", description="用户的年收入【0 5万以下】【1 5万 ~ 15万】【2 15万 ~ 30万】【3 30万以上】【4 其他】"),
     *                           @SWG\Property(property="industry", type="integer", default="0", description="行业【0 金融/银行/投资】【1 计算机/互联网】【2 媒体/出版/影视/文化】【3 政府/公共事业】【4 房地产/建材/工程】【5 咨询/法律】【6 加工制造】【7 教育培训】【8 医疗保健】【9 运输/物流/交通】【10 零售/贸易】【11 旅游/度假】【12 其他】"),
     *                           @SWG\Property(property="email", type="string", default="", description="邮箱"),
     *                           @SWG\Property(property="address", type="string", default="", description="地址"),
     *                           @SWG\Property(property="unionid", type="string", default="", description="微信unionid"),
     *                           @SWG\Property(property="status", type="integer", default="1", description="会员状态【0 已禁用】【1 未禁用】， 默认是1"),
     *                           @SWG\Property(property="inviter", type="object", description="推荐人的信息", required={"id","name","mobile"},
     *                               @SWG\Property(property="id", type="string", default="20575", description="推荐人ID"),
     *                               @SWG\Property(property="name", type="string", default="陈某人", description="推荐人姓名/昵称"),
     *                               @SWG\Property(property="mobile", type="string", default="18434211111", description="推荐人手机号"),
     *                           ),
     *                           @SWG\Property(property="salesperson", type="object", description="绑定的导购信息", required={"id","name","mobile"},
     *                               @SWG\Property(property="id", type="string", default="55", description="绑定的导购ID"),
     *                               @SWG\Property(property="name", type="string", default="陆凯杰", description="绑定的导购姓名/昵称"),
     *                               @SWG\Property(property="mobile", type="string", default="13917611111", description="绑定的导购手机号"),
     *                           ),
     *                           @SWG\Property(property="tags", type="array", description="会员标签（集合）",
     *                               @SWG\Items(required={"id","name"},
     *                                   @SWG\Property(property="id", type="string", default="1", description="标签ID"),
     *                                   @SWG\Property(property="name", type="string", default="内部会员", description="标签名"),
     *                               ),
     *                           ),
     *                           @SWG\Property(property="vip_grades", type="array", description="所拥有的付费会员等级（集合）",
     *                               @SWG\Items(required={"id","type","end_date"},
     *                                   @SWG\Property(property="id", type="string", default="1", description="付费会员的等级ID"),
     *                                   @SWG\Property(property="type", type="string", default="vip", description="付费会员的类型【vip 普通vip】【svip 进阶vip】"),
     *                                   @SWG\Property(property="end_date", type="integer", default="1763622468", description="标签名"),
     *                               ),
     *                           ),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        // 设置过滤条件
        $filter = new MemberFilter($this->getListRequestData($request), $memberResult = new \stdClass());
        $this->memberResult = $memberResult;
        // 获取企业id
        $companyId = $this->memberResult->company_id;
        // 获取结果集（这里返回的结果不完整，还需要在下文对数据做追加处理）
        $result = (new MemberService())->listWithJoin($filter->get(), $this->getPage(), $this->getPageSize());
        if (!empty($result["list"])) {
            // 追加推荐人信息
            $this->appendInviterToList($companyId, $result["list"]);
            // 追加导购信息
            $this->appendSalespersonToList($companyId, $result["list"]);
            // 追加标签信息
            $this->appendTagToList($companyId, $result["list"]);
            // 追加付费会员信息
            $this->appendVipGradeToList($companyId, $result["list"]);
            // 处理时间格式
            $this->handleDataToList($companyId, $result["list"]);
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.detail",
     *     tags={"会员"},
     *     summary="查询会员 - 详情",
     *     description="查询会员 - 详情",
     *     operationId="detail",
     *
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="mobile", in="query", description="会员手机号", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="", required={"user_id","mobile","source_from","created","updated","remarks","grade_id","user_card_code","username","avatar","sex","birthday","habbit","edu_background","income","industry","email","address","unionid","status","inviter","salesperson","tags","vip_grades","point","deposit"},
     *                @SWG\Property(property="user_id", type="string", default="20576", description="会员ID"),
     *                @SWG\Property(property="mobile", type="string", default="13042411111", description="会员手机号"),
     *                @SWG\Property(property="source_from", type="string", default="default", description="来源渠道 【default 小程序端/后台导入】【openapi 通过公共api创建的会员】"),
     *                @SWG\Property(property="created", type="string", default="2021-06-22 11:22:55", description="会员创建时间(日期格式:yyyy-MM-dd HH:mm:ss)"),
     *                @SWG\Property(property="updated", type="string", default="2021-06-22 11:22:55", description="会员更新时间(日期格式:yyyy-MM-dd HH:mm:ss)"),
     *                @SWG\Property(property="remarks", type="string", default="", description="备注"),
     *                @SWG\Property(property="grade_id", type="string", default="4", description="会员等级"),
     *                @SWG\Property(property="vip_grade_id", type="string", default="4", description="付费会员等级"),
     *                @SWG\Property(property="user_card_code", type="string", default="0CA4D083E1A0", description="会员卡号"),
     *                @SWG\Property(property="username", type="string", default="一米阳光", description="姓名/昵称"),
     *                @SWG\Property(property="avatar", type="string", default="https://thirdwx.qlogo.cn/mmopen/vi_32/OCMJXwhqBk9tEZ6WKWv6aEm89XTtOXI9J2zS5DJXwr0mSXbN2RXB7Ct7p7c37N6icC5lsOzLP6fTGtfCzFans0Q/132", description="头像url"),
     *                @SWG\Property(property="sex", type="integer", default="0", description="性别【0 未知】【1 男】【2 女】"),
     *                @SWG\Property(property="birthday", type="string", default="", description="生日 (日期格式:yyyy-MM-dd HH:mm:ss)"),
     *                @SWG\Property(property="habbit", type="array", description="爱好（集合）",
     *                    @SWG\Items(
     *                        @SWG\Property(property="ischecked", type="boolean", default="false", description="是否选中, 【true 选中】【false 未选中】"),
     *                        @SWG\Property(property="name", type="string", default="游戏", description="该选项的名字"),
     *                    ),
     *                ),
     *                @SWG\Property(property="edu_background", type="integer", default="0", description="学历【0 硕士及以上】【1 本科】【2 大专】【3 高中/中专及以下】【4 其他】"),
     *                @SWG\Property(property="income", type="integer", default="0", description="用户的年收入【0 5万以下】【1 5万 ~ 15万】【2 15万 ~ 30万】【3 30万以上】【4 其他】"),
     *                @SWG\Property(property="industry", type="integer", default="0", description="行业【0 金融/银行/投资】【1 计算机/互联网】【2 媒体/出版/影视/文化】【3 政府/公共事业】【4 房地产/建材/工程】【5 咨询/法律】【6 加工制造】【7 教育培训】【8 医疗保健】【9 运输/物流/交通】【10 零售/贸易】【11 旅游/度假】【12 其他】"),
     *                @SWG\Property(property="email", type="string", default="", description="邮箱"),
     *                @SWG\Property(property="address", type="string", default="", description="地址"),
     *                @SWG\Property(property="unionid", type="string", default="", description="微信unionid"),
     *                @SWG\Property(property="status", type="integer", default="1", description="会员状态【0 已禁用】【1 未禁用】， 默认是1"),
     *                @SWG\Property(property="inviter", type="object", description="推荐人的信息", required={"id","name","mobile"},
     *                    @SWG\Property(property="id", type="string", default="20575", description="推荐人ID"),
     *                    @SWG\Property(property="name", type="string", default="陈某人", description="推荐人姓名/昵称"),
     *                    @SWG\Property(property="mobile", type="string", default="18434211111", description="推荐人手机号"),
     *                ),
     *                @SWG\Property(property="salesperson", type="object", description="绑定的导购信息", required={"id","name","mobile"},
     *                    @SWG\Property(property="id", type="string", default="55", description="绑定的导购ID"),
     *                    @SWG\Property(property="name", type="string", default="陆凯杰", description="绑定的导购姓名/昵称"),
     *                    @SWG\Property(property="mobile", type="string", default="13917611111", description="绑定的导购手机号"),
     *                ),
     *                @SWG\Property(property="tags", type="array", description="会员标签（集合）",
     *                    @SWG\Items(required={"id","name"},
     *                        @SWG\Property(property="id", type="string", default="1", description="标签ID"),
     *                        @SWG\Property(property="name", type="string", default="内部会员", description="标签名"),
     *                    ),
     *                ),
     *                @SWG\Property(property="vip_grades", type="array", description="所拥有的付费会员等级（集合）",
     *                    @SWG\Items(required={"id","type","end_date"},
     *                        @SWG\Property(property="id", type="string", default="1", description="付费会员的等级ID"),
     *                        @SWG\Property(property="type", type="string", default="vip", description="付费会员的类型【vip 普通vip】【svip 进阶vip】"),
     *                        @SWG\Property(property="end_date", type="integer", default="1763622468", description="标签名"),
     *                    ),
     *                ),
     *                @SWG\Property(property="point", type="integer", default="8", description="剩余可用积分"),
     *                @SWG\Property(property="deposit", type="object", description="会员储值相关", required={"have","total"},
     *                    @SWG\Property(property="have", type="string", default="11.11", description="储值余额（单位为元）"),
     *                    @SWG\Property(property="total", type="string", default="22.22", description="累计储值金额（单位为元）"),
     *                ),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function detail(Request $request)
    {
        // 设置过滤条件
        $filter = (new MemberFilter([], $memberResult = new \stdClass()))->get();
        $this->memberResult = $memberResult;
        // 设置用户id和手机号
        $this->checkMobile($request, $filter);
        // 企业id
        $companyId = $filter["company_id"];
        // 查询结果
        $result = (new MemberService())->listWithJoin($filter, 1, 1, [], "*", false);
        if (!empty($result["list"])) {
            // 追加推荐人信息
            $this->appendInviterToList($companyId, $result["list"]);
            // 追加导购信息
            $this->appendSalespersonToList($companyId, $result["list"]);
            // 追加标签信息
            $this->appendTagToList($companyId, $result["list"]);
            // 追加付费会员信息
            $this->appendVipGradeToList($companyId, $result["list"]);
            // 追加积分信息
            $this->appendPointToList($companyId, $result["list"]);
            // 追加储值信息
            $this->appendDepositToList($companyId, $result["list"]);
        }
        $info = (array)array_shift($result["list"]);
        return $this->response->array($info);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.card_grade.update",
     *     tags={"会员"},
     *     summary="修改会员 - 等级",
     *     description="会员的等级变更",
     *     operationId="updateGrade",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="plat_account", in="query", description="商派会员Id", required=true, type="string"),
     *     @SWG\Parameter(name="grade_id", in="query", description="等级ID（数云）", required=false, type="integer"),
     *     @SWG\Parameter(name="grade_level", in="query", description="等级", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="string", default="", description=""),
     *         ),
     *     ),
     * )
     */
    public function updateGrade(Request $request)
    {
        // 过滤条件
        $requestFilter = $request->only(["plat_account"]);
        $filter = (new MemberFilter($requestFilter))->get();
        // 更新数据
        $requestData = $request->only(["grade_id", "grade_level"]);
        // 参数验证
        if ($messageBag = validation(array_merge($requestData, $filter), [
            "user_id" => ["required", "numeric"],
            "grade_id" => ["required", "numeric"],
            "grade_level" => ["required", "string"],
        ], [
            "user_id.required" => "商派会员Id必填",
            "grade_id.required" => "等级ID参数必填",
            "grade_level.required" => "等级参数必填",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        $updateData = [
            'external_id' => $requestData['grade_id'],
        ];
        // 更新数据
        $memberService = new MemberService();
        $memberService->updateDetail($filter, $updateData);
        return $this->response->array([]);
    }
}
