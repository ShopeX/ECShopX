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
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Filter\Member\MemberCardGradeFilter;
use OpenapiBundle\Filter\Member\MemberFilter;
use OpenapiBundle\Http\Controllers\Controller;
use OpenapiBundle\Rules\MobileRule;
use OpenapiBundle\Services\Member\MemberCardGradeService;
use OpenapiBundle\Services\Member\MemberService;
use OpenapiBundle\Traits\Member\MemberCardGradeTrait;
use Swagger\Annotations as SWG;

/**
 * 会员卡等级相关
 * Class MemberCardGradeController
 * @package OpenapiBundle\Http\ThirdApi\V2\Action\Member
 */
class MemberCardGradeController extends Controller
{
    use MemberCardGradeTrait;

    /**
     * @SWG\Get(
     *     path="/ecx.member_card_grade.list",
     *     tags={"会员"},
     *     summary="会员卡等级查询 - 批量",
     *     description="会员卡等级查询 - 批量",
     *     operationId="list",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="page", in="query", description="当前页数	1", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）", required=false, type="integer"),
     *     @SWG\Parameter(name="grade_id", in="query", description="等级ID", required=false, type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description="响应描述"),
     *            @SWG\Property(property="data", type="object", description="",required={"pager","total_count","is_last_page","list"},
     *               @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(required={"grade_id","is_default","name","discount","total_consumption","background_pic_url","external_id","created","updated"},
     *                           @SWG\Property(property="grade_id", type="integer", default="4", description="等级ID"),
     *                           @SWG\Property(property="is_default", type="integer", default="1", description="是否默认【0 否】【1 是】"),
     *                           @SWG\Property(property="grade_name", type="string", default="普通会员", description="等级名称"),
     *                           @SWG\Property(property="discount", type="string", default="9", description="会员折扣，单位是折"),
     *                           @SWG\Property(property="total_consumption", type="string", default="0", description="升级条件的累计消费金额，单位为元"),
     *                           @SWG\Property(property="background_pic_url", type="string", default="http://bbctest.aixue7.com/1/2019/12/09/ab7b9466293172e51a8f5856135e3349003xcJT4n4uMYEB9SyovqxzYdaj1Wi7W", description="等级卡背景图"),
     *                           @SWG\Property(property="external_id", type="string", default="", description="外部唯一标识，外部调用方自定义的值"),
     *                           @SWG\Property(property="created", type="string", default="2019-06-25 19:11:00", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                           @SWG\Property(property="updated", type="string", default="2021-06-15 17:59:06", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                 ),
     *               ),
     *               @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *               @SWG\Property(property="pager", type="object", description="分页相关信息",required={"page","page_size"},
     *                    @SWG\Property(property="page", type="integer", default="1", description="当前页数"),
     *                    @SWG\Property(property="page_size", type="integer", default="10", description="每页显示数量（默认20条）"),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        // 过滤条件
        $filter = (new MemberCardGradeFilter())->get();
        // 结果集
        $result = (new MemberCardGradeService())->list($filter, $this->getPage(), $this->getPageSize(), ["grade_id" => "DESC"]);
        if (!empty($result["list"])) {
            $this->handleDataToList($result["list"]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member_card_grade.create",
     *     tags={"会员"},
     *     summary="会员卡等级创建",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="create",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="grade_name", in="query", description="等级名称", required=true, type="string"),
     *     @SWG\Parameter(name="discount", in="query", description="会员折扣（需要>=1且<=10的数字，小数点只保留一位）", required=true, type="string"),
     *     @SWG\Parameter(name="total_consumption", in="query", description="升级条件（>=累计消费金额值，以元为单位，默认0）", required=true, type="string"),
     *     @SWG\Parameter(name="background_pic_url", in="query", description="等级卡背景图", required=false, type="string"),
     *     @SWG\Parameter(name="external_id", in="query", description="外部唯一标识，外部调用方自定义", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",required={"grade_id","is_default","name","discount","total_consumption","background_pic_url","external_id","created","updated"},
     *                 @SWG\Property(property="grade_id", type="integer", default="4", description="等级ID"),
     *                 @SWG\Property(property="is_default", type="integer", default="1", description="是否默认【0 否】【1 是】"),
     *                 @SWG\Property(property="grade_name", type="string", default="普通会员", description="等级名称"),
     *                 @SWG\Property(property="discount", type="string", default="9", description="会员折扣，单位是折"),
     *                 @SWG\Property(property="total_consumption", type="string", default="0", description="升级条件的累计消费金额，单位为元"),
     *                 @SWG\Property(property="background_pic_url", type="string", default="http://bbctest.aixue7.com/1/2019/12/09/ab7b9466293172e51a8f5856135e3349003xcJT4n4uMYEB9SyovqxzYdaj1Wi7W", description="等级卡背景图"),
     *                 @SWG\Property(property="external_id", type="string", default="", description="外部唯一标识，外部调用方自定义的值"),
     *                 @SWG\Property(property="created", type="string", default="2019-06-25 19:11:00", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                 @SWG\Property(property="updated", type="string", default="2021-06-15 17:59:06", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function create(Request $request)
    {
        $requestData = $request->only(["grade_name", "discount", "total_consumption", "background_pic_url", "external_id"]);
        if ($messageBag = validation($requestData, [
            "grade_name" => ["required"],
            "discount" => ["required", "numeric", "between:1,10"],
            "total_consumption" => ["required", "numeric", "min:0"],
            "background_pic_url" => ["nullable"],
            "external_id" => ["nullable"],
        ], [
            "grade_name.required" => "未填写等级名称",
            "discount.required" => "未填写会员折扣",
            "total_consumption.required" => "未填写升级条件",
            "*.*" => "请求参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        $requestData["company_id"] = $this->getCompanyId();
        $grade = (new MemberCardGradeService())->create($requestData);
        if (!empty($grade)) {
            $list[] = $grade;
            $this->handleDataToList($list);
            $grade = (array)array_shift($list);
        }
        return $this->response->array($grade);
    }

    /**
     * @SWG\Patch(
     *     path="/ecx.member_card_grade.update",
     *     tags={"会员"},
     *     summary="会员卡等级修改",
     *     description="",
     *     operationId="update",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="grade_id", in="query", description="等级ID", required=true, type="string"),
     *     @SWG\Parameter(name="grade_name", in="query", description="等级名称", required=false, type="string"),
     *     @SWG\Parameter(name="discount", in="query", description="会员折扣（需要>=1且<=10的数字，小数点只保留一位）", required=false, type="string"),
     *     @SWG\Parameter(name="total_consumption", in="query", description="升级条件（>=累计消费金额值，以元为单位，默认0）", required=false, type="string"),
     *     @SWG\Parameter(name="background_pic_url", in="query", description="等级卡背景图", required=false, type="string"),
     *     @SWG\Parameter(name="external_id", in="query", description="外部唯一标识，外部调用方自定义", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",required={"grade_id","is_default","name","discount","total_consumption","background_pic_url","external_id","created","updated"},
     *                 @SWG\Property(property="grade_id", type="integer", default="4", description="等级ID"),
     *                 @SWG\Property(property="is_default", type="integer", default="1", description="是否默认【0 否】【1 是】"),
     *                 @SWG\Property(property="grade_name", type="string", default="普通会员", description="等级名称"),
     *                 @SWG\Property(property="discount", type="string", default="9", description="会员折扣，单位是折"),
     *                 @SWG\Property(property="total_consumption", type="string", default="0", description="升级条件的累计消费金额，单位为元"),
     *                 @SWG\Property(property="background_pic_url", type="string", default="http://bbctest.aixue7.com/1/2019/12/09/ab7b9466293172e51a8f5856135e3349003xcJT4n4uMYEB9SyovqxzYdaj1Wi7W", description="等级卡背景图"),
     *                 @SWG\Property(property="external_id", type="string", default="", description="外部唯一标识，外部调用方自定义的值"),
     *                 @SWG\Property(property="created", type="string", default="2019-06-25 19:11:00", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                 @SWG\Property(property="updated", type="string", default="2021-06-15 17:59:06", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function update(Request $request)
    {
        $requestData = $request->only(["grade_id", "grade_name", "discount", "total_consumption", "background_pic_url", "external_id"]);
        if ($messageBag = validation($requestData, [
            "grade_id" => ["required"],
            "grade_name" => ["nullable"],
            "discount" => ["nullable", "numeric", "between:1,10"],
            "total_consumption" => ["nullable", "numeric", "min:0"],
            "background_pic_url" => ["nullable"],
            "external_id" => ["nullable"],
        ], [
            "*.*" => "请求参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS);
        }
        $filter = (new MemberCardGradeFilter($requestData))->get();
        $grade = (new MemberCardGradeService())->updateDetail($filter, $requestData);
        if (!empty($grade)) {
            $list[] = $grade;
            $this->handleDataToList($list);
            $grade = (array)array_shift($list);
        }
        return $this->response->array($grade);
    }

    /**
     * @SWG\Delete(
     *     path="/ecx.member_card_grade.delete",
     *     tags={"会员"},
     *     summary="会员卡等级删除",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="update",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="grade_id", in="query", description="等级ID", required=true, type="string"),
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
    public function delete(Request $request)
    {
        // 0x53686f704578
        $requestData = $request->only(["grade_id"]);
        if ($messageBag = validation($requestData, [
            "grade_id" => ["required"],
        ], [
            "*.*" => "请求参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS);
        }

        $filter = (new MemberCardGradeFilter($requestData))->get();
        (new MemberCardGradeService())->delete($filter);
        return $this->response->array([]);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member_card_grade.detail",
     *     tags={"会员"},
     *     summary="会员卡等级详情 - 根据手机号查询",
     *     description="会员卡等级详情 - 根据手机号查询",
     *     operationId="detail",
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
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"grade_id","name","discount","total_consumption","background_pic_url","external_id","created","updated"},
     *                 @SWG\Property(property="grade_id", type="integer", default="4", description="等级ID"),
     *                 @SWG\Property(property="is_default", type="integer", default="1", description="是否默认【0 否】【1 是】"),
     *                 @SWG\Property(property="grade_name", type="string", default="普通会员", description="等级名称"),
     *                 @SWG\Property(property="discount", type="string", default="9", description="会员折扣，单位是折"),
     *                 @SWG\Property(property="total_consumption", type="string", default="0", description="升级条件的累计消费金额，单位为元"),
     *                 @SWG\Property(property="background_pic_url", type="string", default="http://bbctest.aixue7.com/1/2019/12/09/ab7b9466293172e51a8f5856135e3349003xcJT4n4uMYEB9SyovqxzYdaj1Wi7W", description="等级卡背景图"),
     *                 @SWG\Property(property="external_id", type="string", default="", description="外部唯一标识，外部调用方自定义的值"),
     *                 @SWG\Property(property="created", type="string", default="2019-06-25 19:11:00", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                 @SWG\Property(property="updated", type="string", default="2021-06-15 17:59:06", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function detail(Request $request)
    {
        // 参数验证
        $requestFilter = $request->only(["mobile"]);
        if ($messageBag = validation($requestFilter, [
            "mobile" => ["required", new MobileRule()]
        ], [
            "*.*" => "请求参数错误"
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS);
        }
        // 获取用户信息
        $filter = (new MemberFilter($request->only(["mobile"])))->get();
        $userInfo = (new MemberService())->find($filter);
        if (empty($userInfo)) {
            throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND);
        }
        $gradeInfo = (new MemberCardGradeService())->find(["company_id" => $filter["company_id"], "grade_id" => $userInfo["grade_id"]]);
        // 数据处理
        $list[] = &$gradeInfo;
        $this->handleDataToList($list);
        // 隐藏是否为默认值的字段
        unset($gradeInfo["is_default"]);
        return $this->response->array($gradeInfo);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.card_grade.batch_save",
     *     tags={"会员"},
     *     summary="会员卡等级设置",
     *     description="批量设置会员卡等级",
     *     operationId="batchSave",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     @     SWG\Parameter( in="query", type="string", required=true, name="grade_info", description="会员卡等级信息:json_array格式 [{grade_id:等级ID(数云);grade_name:等级名称;grade_level:等级}]" ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",required={"grade_id","is_default","name","discount","total_consumption","background_pic_url","external_id","created","updated"},
     *                 @SWG\Property(property="grade_id", type="integer", default="4", description="等级ID"),
     *                 @SWG\Property(property="is_default", type="integer", default="1", description="是否默认【0 否】【1 是】"),
     *                 @SWG\Property(property="grade_name", type="string", default="普通会员", description="等级名称"),
     *                 @SWG\Property(property="discount", type="string", default="9", description="会员折扣，单位是折"),
     *                 @SWG\Property(property="total_consumption", type="string", default="0", description="升级条件的累计消费金额，单位为元"),
     *                 @SWG\Property(property="background_pic_url", type="string", default="http://bbctest.aixue7.com/1/2019/12/09/ab7b9466293172e51a8f5856135e3349003xcJT4n4uMYEB9SyovqxzYdaj1Wi7W", description="等级卡背景图"),
     *                 @SWG\Property(property="external_id", type="string", default="", description="外部唯一标识，外部调用方自定义的值"),
     *                 @SWG\Property(property="created", type="string", default="2019-06-25 19:11:00", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                 @SWG\Property(property="updated", type="string", default="2021-06-15 17:59:06", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function batchSave(Request $request)
    {
        $requestData = $request->only(["grade_info"]);
        if ($messageBag = validation($requestData, [
            "grade_info" => ["required"],
        ], [
            "*.*" => "请求参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS);
        }
        $gradeInfo = json_decode($requestData['grade_info'], true);

        if (empty($gradeInfo) || !is_array($gradeInfo)) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS);
        }
        $companyId = $this->getCompanyId();
        $grade = (new MemberCardGradeService())->batchSave($companyId, $gradeInfo);
        if (!empty($grade)) {
            $this->handleShuyunDataToList($grade);
        }
        return $this->response->array($grade);
    }
}
