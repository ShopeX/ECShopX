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
use OpenapiBundle\Filter\Member\MemberCardVipGradeFilter;
use OpenapiBundle\Filter\Member\MemberFilter;
use OpenapiBundle\Http\Controllers\Controller;
use OpenapiBundle\Rules\MobileRule;
use OpenapiBundle\Services\Member\MemberCardVipGradeOrderService;
use OpenapiBundle\Services\Member\MemberCardVipGradeRelUserService;
use OpenapiBundle\Services\Member\MemberCardVipGradeService;
use OpenapiBundle\Services\Member\MemberService;
use OpenapiBundle\Traits\Member\MemberCardVipGradeOrderTrait;
use OpenapiBundle\Traits\Member\MemberCardVipGradeTrait;
use Swagger\Annotations as SWG;

/**
 * 付费会员卡等级相关
 * Class MemberCardVipGradeController
 * @package OpenapiBundle\Http\ThirdApi\V2\Action\Member
 */
class MemberCardVipGradeController extends Controller
{
    use MemberCardVipGradeTrait, MemberCardVipGradeOrderTrait {
        MemberCardVipGradeOrderTrait::handleDataToList as handleDataToOrderList;
        MemberCardVipGradeTrait::handleDataToList insteadof MemberCardVipGradeOrderTrait;
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member_card_vip_grade.list",
     *     tags={"会员"},
     *     summary="付费会员卡等级查询 - 批量",
     *     description="付费会员卡等级查询 - 批量",
     *     operationId="list",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="page", in="query", description="当前页数	1", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）", required=false, type="integer"),
     *     @SWG\Parameter(name="vip_grade_id", in="query", description="等级ID", required=false, type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"list","is_last_page","pager"},
     *               @SWG\Property(property="list", type="array", description="",required={"pager","total_count","is_last_page","list"},
     *                 @SWG\Items(required={"vip_grade_id","type","grade_name","monthly_fee","quarter_fee","year_fee","discount","guide_title","description","background_pic_url","is_default","is_disabled","external_id","created","updated"},
     *                           @SWG\Property(property="vip_grade_id", type="integer", default="2", description="付费等级ID"),
     *                           @SWG\Property(property="type", type="string", default="svip", description="付费等级类型（vip:普通付费;svip:高级付费）"),
     *                           @SWG\Property(property="grade_name", type="string", default="超级付费1", description="付费等级名称	"),
     *                           @SWG\Property(property="monthly_fee", type="string", default="0.01", description="30天付费会员，购买所需金额（以元为单位）"),
     *                           @SWG\Property(property="quarter_fee", type="string", default="2", description="90天付费会员，购买所需金额（以元为单位）"),
     *                           @SWG\Property(property="year_fee", type="string", default="3", description="365天付费会员，购买所需金额（以元为单位）"),
     *                           @SWG\Property(property="discount", type="string", default="1", description="会员折扣, 如果值为6则表示6折"),
     *                           @SWG\Property(property="guide_title", type="string", default="欢迎来到宝贝儿小屋，这是svip的引导文本", description="购买引导语"),
     *                           @SWG\Property(property="description", type="string", default="会员畅想", description="详细说明"),
     *                           @SWG\Property(property="background_pic_url", type="string", default="http://bbctest.aixue7.com/1/2019/08/20/78353c7da646858470818d61ecf040db1lEHvU1I6zlkVJ9m4IxC6Zq7EBxDAyEc", description="付费等级卡背景图"),
     *                           @SWG\Property(property="is_default", type="integer", default="1", description="是否默认（0否，1是，默认0）"),
     *                           @SWG\Property(property="is_disabled", type="integer", default="0", description="是否禁用（0否，1是，默认0）"),
     *                           @SWG\Property(property="external_id", type="string", default="", description="外部唯一标识，外部调用方自定义的值"),
     *                           @SWG\Property(property="created", type="string", default="2019-06-28 10:33:19", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                           @SWG\Property(property="updated", type="string", default="2019-06-28 10:33:19", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                 ),
     *               ),
     *                @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                @SWG\Property(property="pager", type="object", description="分页相关信息",required={"page","page_size"},
     *                     @SWG\Property(property="page", type="integer", default="1", description="当前页数"),
     *                     @SWG\Property(property="page_size", type="integer", default="10", description="每页显示数量（默认20条）"),
     *                ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function list()
    {
        // ShopEx EcShopX Service Component
        $filter = (new MemberCardVipGradeFilter())->get();
        $result = (new MemberCardVipGradeService())->list($filter, $this->getPage(), $this->getPageSize(), ["vip_grade_id" => "DESC"]);
        if (!empty($result["list"])) {
            $this->handleDataToList($result["list"]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member_card_vip_grade.create",
     *     tags={"会员"},
     *     summary="付费会员卡等级新增",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="create",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="grade_name", in="formData", description="付费等级名称", required=true, type="string"),
     *     @SWG\Parameter(name="monthly_fee", in="formData", description="30天付费会员，购买所需金额（30、90、365天购买金额必填一项，以元为单位）", required=true, type="string"),
     *     @SWG\Parameter(name="quarter_fee", in="formData", description="90天付费会员，购买所需金额（30、90、365天购买金额必填一项，以元为单位）", required=true, type="string"),
     *     @SWG\Parameter(name="year_fee", in="formData", description="365天付费会员，购买所需金额（30、90、365天购买金额必填一项，以元为单位）", required=true, type="string"),
     *     @SWG\Parameter(name="discount", in="formData", description="会员折扣（需要>=1且<=10的数字，小数点只保留一位）", required=true, type="string"),
     *     @SWG\Parameter(name="guide_title", in="formData", description="购买引导语", required=false, type="string"),
     *     @SWG\Parameter(name="description", in="formData", description="详细说明", required=false, type="string"),
     *     @SWG\Parameter(name="background_pic_url", in="formData", description="付费等级卡背景图	", required=false, type="string"),
     *     @SWG\Parameter(name="is_default", in="formData", description="是否默认（0否，1是，默认0）", required=false, type="string"),
     *     @SWG\Parameter(name="is_disabled", in="formData", description="是否禁用（0否，1是，默认0）", required=false, type="string"),
     *     @SWG\Parameter(name="external_id", in="formData", description="外部唯一标识，外部调用方自定义", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",
     *                @SWG\Property(property="vip_grade_id", type="integer", default="2", description="付费等级ID"),
     *                @SWG\Property(property="type", type="string", default="svip", description="付费等级类型（vip:普通付费;svip:高级付费）"),
     *                @SWG\Property(property="grade_name", type="string", default="超级付费1", description="付费等级名称	"),
     *                @SWG\Property(property="monthly_fee", type="string", default="0.01", description="30天付费会员，购买所需金额（以元为单位）"),
     *                @SWG\Property(property="quarter_fee", type="string", default="2", description="90天付费会员，购买所需金额（以元为单位）"),
     *                @SWG\Property(property="year_fee", type="string", default="3", description="365天付费会员，购买所需金额（以元为单位）"),
     *                @SWG\Property(property="discount", type="string", default="1", description="会员折扣, 如果值为6则表示6折"),
     *                @SWG\Property(property="guide_title", type="string", default="欢迎来到宝贝儿小屋，这是svip的引导文本", description="购买引导语"),
     *                @SWG\Property(property="description", type="string", default="会员畅想", description="详细说明"),
     *                @SWG\Property(property="background_pic_url", type="string", default="http://bbctest.aixue7.com/1/2019/08/20/78353c7da646858470818d61ecf040db1lEHvU1I6zlkVJ9m4IxC6Zq7EBxDAyEc", description="付费等级卡背景图"),
     *                @SWG\Property(property="is_default", type="integer", default="1", description="是否默认（0否，1是，默认0）"),
     *                @SWG\Property(property="is_disabled", type="integer", default="0", description="是否禁用（0否，1是，默认0）"),
     *                @SWG\Property(property="external_id", type="string", default="", description="外部唯一标识，外部调用方自定义的值"),
     *                @SWG\Property(property="created", type="string", default="2019-06-28 10:33:19", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                @SWG\Property(property="updated", type="string", default="2019-06-28 10:33:19", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function create(Request $request)
    {
        $requestData = $request->only(["grade_name", "monthly_fee", "quarter_fee", "year_fee", "discount", "guide_title", "description", "is_default", "is_disabled", "external_id"]);
        if ($messageBag = validation($requestData, [
            "grade_name" => ["required"],
            "monthly_fee" => ["required_without_all:quarter_fee,year_fee", "numeric", "min:0"],
            "quarter_fee" => ["required_without_all:monthly_fee,year_fee", "numeric", "min:0"],
            "year_fee" => ["required_without_all:monthly_fee,quarter_fee", "numeric", "min:0"],
            "discount" => ["required", "numeric", "between:1,10"],
            "guide_title" => ["nullable"],
            "description" => ["nullable"],
            "is_default" => ["nullable"],
            "is_disabled" => ["nullable"]
        ], [
            "grade_name.required" => "未填写付费等级名称",
            "discount.required" => "未填写会员折扣",
            "monthly_fee.required_without_all" => "购买金额必填一项",
            "quarter_fee.required_without_all" => "购买金额必填一项",
            "year_fee.required_without_all" => "购买金额必填一项",
            "*.*" => "请求参数错误"
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        $requestData["company_id"] = $this->getCompanyId();
        $info = (new MemberCardVipGradeService())->create($requestData);
        $list[] = &$info;
        $this->handleDataToList($list);
        return $this->response->array($info);
    }

    /**
     * @SWG\Patch(
     *     path="/ecx.member_card_vip_grade.update",
     *     tags={"会员"},
     *     summary="付费会员卡等级更新",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="update",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="vip_grade_id", in="formData", description="付费等级ID", required=true, type="integer"),
     *     @SWG\Parameter(name="grade_name", in="formData", description="付费等级名称", required=false, type="string"),
     *     @SWG\Parameter(name="monthly_fee", in="formData", description="30天付费会员，购买所需金额（30、90、365天购买金额必填一项，以元为单位）", required=false, type="string"),
     *     @SWG\Parameter(name="quarter_fee", in="formData", description="90天付费会员，购买所需金额（30、90、365天购买金额必填一项，以元为单位）", required=false, type="string"),
     *     @SWG\Parameter(name="year_fee", in="formData", description="365天付费会员，购买所需金额（30、90、365天购买金额必填一项，以元为单位）", required=false, type="string"),
     *     @SWG\Parameter(name="discount", in="formData", description="会员折扣（需要>=1且<=10的数字，小数点只保留一位）", required=false, type="string"),
     *     @SWG\Parameter(name="guide_title", in="formData", description="购买引导语", required=false, type="string"),
     *     @SWG\Parameter(name="description", in="formData", description="详细说明", required=false, type="string"),
     *     @SWG\Parameter(name="background_pic_url", in="formData", description="付费等级卡背景图	", required=false, type="string"),
     *     @SWG\Parameter(name="is_default", in="formData", description="是否默认（0否，1是，默认0）", required=false, type="string"),
     *     @SWG\Parameter(name="is_disabled", in="formData", description="是否禁用（0否，1是，默认0）", required=false, type="string"),
     *     @SWG\Parameter(name="external_id", in="formData", description="外部唯一标识，外部调用方自定义", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",
     *                @SWG\Property(property="vip_grade_id", type="integer", default="2", description="付费等级ID"),
     *                @SWG\Property(property="type", type="string", default="svip", description="付费等级类型（vip:普通付费;svip:高级付费）"),
     *                @SWG\Property(property="grade_name", type="string", default="超级付费1", description="付费等级名称	"),
     *                @SWG\Property(property="monthly_fee", type="string", default="0.01", description="30天付费会员，购买所需金额（以元为单位）"),
     *                @SWG\Property(property="quarter_fee", type="string", default="2", description="90天付费会员，购买所需金额（以元为单位）"),
     *                @SWG\Property(property="year_fee", type="string", default="3", description="365天付费会员，购买所需金额（以元为单位）"),
     *                @SWG\Property(property="discount", type="string", default="1", description="会员折扣, 如果值为6则表示6折"),
     *                @SWG\Property(property="guide_title", type="string", default="欢迎来到宝贝儿小屋，这是svip的引导文本", description="购买引导语"),
     *                @SWG\Property(property="description", type="string", default="会员畅想", description="详细说明"),
     *                @SWG\Property(property="background_pic_url", type="string", default="http://bbctest.aixue7.com/1/2019/08/20/78353c7da646858470818d61ecf040db1lEHvU1I6zlkVJ9m4IxC6Zq7EBxDAyEc", description="付费等级卡背景图"),
     *                @SWG\Property(property="is_default", type="integer", default="1", description="是否默认（0否，1是，默认0）"),
     *                @SWG\Property(property="is_disabled", type="integer", default="0", description="是否禁用（0否，1是，默认0）"),
     *                @SWG\Property(property="external_id", type="string", default="", description="外部唯一标识，外部调用方自定义的值"),
     *                @SWG\Property(property="created", type="string", default="2019-06-28 10:33:19", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                @SWG\Property(property="updated", type="string", default="2019-06-28 10:33:19", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function update(Request $request)
    {
        $requestData = $request->only(["vip_grade_id", "grade_name", "monthly_fee", "quarter_fee", "year_fee", "discount", "guide_title", "description", "is_default", "is_disabled", "external_id"]);
        if ($messageBag = validation($requestData, [
            "vip_grade_id" => ["required"],
            "grade_name" => ["nullable"],
            "monthly_fee" => ["nullable", "numeric", "min:0"],
            "quarter_fee" => ["nullable", "numeric", "min:0"],
            "year_fee" => ["nullable", "numeric", "min:0"],
            "discount" => ["nullable", "numeric", "between:1,10"],
            "guide_title" => ["nullable"],
            "description" => ["nullable"],
            "is_default" => ["nullable"],
            "is_disabled" => ["nullable"]
        ], [
            "*.*" => "请求参数错误"
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS);
        }
        // 设置过滤条件
        $filter = (new MemberCardVipGradeFilter())->get();
        $info = (new MemberCardVipGradeService())->updateDetail($filter, $requestData);
        $list[] = &$info;
        $this->handleDataToList($list);
        return $this->response->array($info);
    }

    /**
     * @SWG\Delete(
     *     path="/ecx.member_card_vip_grade.delete",
     *     tags={"会员"},
     *     summary="付费会员卡等级删除",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="delete",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="vip_grade_id", in="formData", description="付费等级ID", required=true, type="integer"),
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
        $requestData = $request->only(["vip_grade_id"]);
        if ($messageBag = validation($requestData, [
            "vip_grade_id" => ["required"],
        ], [
            "*.*" => "请求参数错误"
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        // 设置过滤条件
        $filter = (new MemberCardVipGradeFilter())->get();
        (new MemberCardVipGradeService())->delete($filter);
        return $this->response->array([]);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member_card_vip_grade_order.list",
     *     tags={"会员"},
     *     summary="付费会员卡等级购买记录查询 - 批量",
     *     description="付费会员卡等级购买记录查询 - 批量",
     *     operationId="orderList",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *     @SWG\Parameter(name="page", in="query", description="当前页数	1", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）", required=false, type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"list","is_last_page","pager"},
     *               @SWG\Property(property="total_count", type="integer", default="1961", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(required={"order_id","price","user_id","mobile","vip_grade_id","lv_type","title","card_type","discount","created","updated"},
     *                           @SWG\Property(property="order_id", type="string", default="3476574000070583", description="订单号"),
     *                           @SWG\Property(property="price", type="string", default="0.00", description="实付金额（以元为单位）"),
     *                           @SWG\Property(property="user_id", type="integer", default="20583", description="会员ID"),
     *                           @SWG\Property(property="mobile", type="string", default="18434286466", description="会员手机号"),
     *                           @SWG\Property(property="vip_grade_id", type="integer", default="1", description="付费会员卡等级ID	"),
     *                           @SWG\Property(property="lv_type", type="string", default="vip", description="付费等级类型（vip:普通付费;svip:高级付费）"),
     *                           @SWG\Property(property="title", type="string", default="一般付费", description="付费会员卡等级名称"),
     *                           @SWG\Property(property="card_type", type="integer", default="5", description="付费会员卡有效天数（30、90、365）"),
     *                           @SWG\Property(property="discount", type="string", default="2.0", description="会员折扣(保留一位小数)"),
     *                           @SWG\Property(property="created", type="string", default="2021-07-08 14:21:59", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                           @SWG\Property(property="updated", type="string", default="2021-07-08 14:21:59", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                 ),
     *               ),
     *                @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                @SWG\Property(property="pager", type="object", description="分页相关信息",required={"page","page_size"},
     *                     @SWG\Property(property="page", type="integer", default="1", description="当前页数"),
     *                     @SWG\Property(property="page_size", type="integer", default="10", description="每页显示数量（默认20条）"),
     *                ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function orderList(Request $request)
    {
        $filter = [
            "company_id" => $this->getCompanyId(),
            "order_status" => "DONE"
        ];
        $result = (new MemberCardVipGradeOrderService())->list($filter, $this->getPage(), $this->getPageSize(), ["order_id" => "DESC"]);
        if (!empty($result["list"])) {
            $this->handleDataToOrderList($result["list"]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member_card_vip_grade.detail",
     *     tags={"会员"},
     *     summary="付费会会员卡等级详情 - 根据手机号查询",
     *     description="付费会会员卡等级详情 - 根据手机号查询",
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
     *            @SWG\Property(property="data", type="object", description="",required={"vip_grade_id","type","grade_name","monthly_fee","quarter_fee","year_fee","discount","guide_title","description","background_pic_url","is_disabled","external_id","created","updated"},
     *                    @SWG\Property(property="vip_grade_id", type="integer", default="2", description="付费等级ID"),
     *                    @SWG\Property(property="type", type="string", default="svip", description="付费等级类型（vip:普通付费;svip:高级付费）"),
     *                    @SWG\Property(property="grade_name", type="string", default="超级付费1", description="付费等级名称"),
     *                    @SWG\Property(property="monthly_fee", type="string", default="0.01", description="30天付费会员，购买所需金额（以元为单位）"),
     *                    @SWG\Property(property="quarter_fee", type="string", default="2", description="90天付费会员，购买所需金额（以元为单位）"),
     *                    @SWG\Property(property="year_fee", type="string", default="3", description="365天付费会员，购买所需金额（以元为单位）"),
     *                    @SWG\Property(property="discount", type="string", default="1", description="会员折扣, 如果值为6则表示6折"),
     *                    @SWG\Property(property="guide_title", type="string", default="欢迎来到宝贝儿小屋，这是svip的引导文本", description="购买引导语"),
     *                    @SWG\Property(property="description", type="string", default="会员畅想", description="详细说明"),
     *                    @SWG\Property(property="background_pic_url", type="string", default="http://bbctest.aixue7.com/1/2019/08/20/78353c7da646858470818d61ecf040db1lEHvU1I6zlkVJ9m4IxC6Zq7EBxDAyEc", description="付费等级卡背景图"),
     *                    @SWG\Property(property="is_default", type="integer", default="1", description="是否默认（0否，1是，默认0）"),
     *                    @SWG\Property(property="is_disabled", type="integer", default="0", description="是否禁用（0否，1是，默认0）"),
     *                    @SWG\Property(property="external_id", type="string", default="", description="外部唯一标识，外部调用方自定义的值"),
     *                    @SWG\Property(property="created", type="string", default="2019-06-28 10:33:19", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                    @SWG\Property(property="updated", type="string", default="2019-06-28 10:33:19", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
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
        // 获取用户关联的付费会员等级信息
        $relInfo = (new MemberCardVipGradeRelUserService())->find(["company_id" => $filter["company_id"], "user_id" => $userInfo["user_id"]]);
        if (!empty($relInfo)) {
            $vipGradeInfo = (new MemberCardVipGradeService())->find(["company_id" => $filter["company_id"], "vip_grade_id" => $relInfo["vip_grade_id"]]);
        } else {
            $vipGradeInfo = [];
        }
        $list[] = &$vipGradeInfo;
        $this->handleDataToList($list);
        // 隐藏是否为默认值的字段
        unset($vipGradeInfo["is_default"]);
        return $this->response->array($vipGradeInfo);
    }
}
