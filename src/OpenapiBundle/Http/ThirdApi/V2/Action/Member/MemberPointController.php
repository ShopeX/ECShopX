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

use Dingo\Api\Http\Response;
use Illuminate\Http\Request;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Filter\Member\MemberPointFilter;
use OpenapiBundle\Http\Controllers\Controller;
use OpenapiBundle\Rules\MobileRule;
use OpenapiBundle\Services\Member\MemberPointService;
use OpenapiBundle\Traits\Member\MemberPointTrait;
use OpenapiBundle\Traits\Member\MemberTrait;
use Swagger\Annotations as SWG;

/**
 * 会员积分相关
 * Class MemberPointController
 * @package OpenapiBundle\Http\Api\V2\Action\Member
 */
class MemberPointController extends Controller
{
    use MemberTrait, MemberPointTrait{
        MemberPointTrait::handleDataToList insteadof MemberTrait;
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member_point_log.list",
     *     tags={"会员"},
     *     summary="查询会员积分历史记录 - 批量",
     *     description="查询会员积分历史记录 - 批量",
     *     operationId="list",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="page", in="query", description="当前页数	1", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）", required=false, type="integer"),
     *     @SWG\Parameter(name="mobile", in="query", description="会员手机号", required=false, type="string"),
     *     @SWG\Parameter(name="start_date", in="query", description="开始时间的时间戳（订单创建时间, 时间格式：yyyy-MM-dd HH:mm:ss）", required=false, type="string"),
     *     @SWG\Parameter(name="end_date", in="query", description="结束时间的时间戳（订单创建时间, 时间格式：yyyy-MM-dd HH:mm:ss）", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description="响应描述"),
     *            @SWG\Property(property="data", type="object", description="",required={"pager","total_count","is_last_page","list"},
     *               @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="integer", default="7", description="记录日志IDEID"),
     *                           @SWG\Property(property="mobile", type="string", default="13042411111", description="会员手机号"),
     *                           @SWG\Property(property="operater", type="string", default="外部开发者", description="操作员姓名/昵称"),
     *                           @SWG\Property(property="type", type="integer", default="13", description="记录类型【0 其他】【1 注册送积分】 【2 推荐送积分】【3 充值送积分】【4 推广注册送积分】【5 积分换购】【6 储值送积分】【7 订单送积分】【8 会员等级返佣积分】【9 取消订单】【10 售后处理】【11 大转盘抽奖送积分】【12 管理员手动调整积分】【13 开放接口】"),
     *                           @SWG\Property(property="description", type="string", default="系统 于1625219470 给 会员(13042411111) 开放接口 +1000", description="记录描述"),
     *                           @SWG\Property(property="increase_point", type="integer", default="1000", description="增加积分值"),
     *                           @SWG\Property(property="decrease_point", type="integer", default="0", description="减去积分值"),
     *                           @SWG\Property(property="record", type="string", default="11111", description="操作员备注"),
     *                           @SWG\Property(property="order_id", type="string", default="", description="关联订单号"),
     *                           @SWG\Property(property="external_id", type="string", default="C10086", description="外部唯一标识，外部调用方自定义的值"),
     *                           @SWG\Property(property="created", type="string", default="2021-07-02 17:51:10", description="创建时间(日期格式:yyyy-MM-dd HH:mm:ss)"),
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
        $filter = (new MemberPointFilter())->get();
        $result = (new MemberPointService())->logList($filter, $this->getPage(), $this->getPageSize(), ["id" => "DESC"]);
        if (!empty($result["list"])) {
            // 追加会员信息
            $this->appendInfoToList((int)$filter["company_id"], $result["list"]);
            // 处理数据
            $this->handleDataToList($result["list"]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member_point.detail",
     *     tags={"会员"},
     *     summary="查询会员积分 - 可用积分",
     *     description="查询会员积分 - 可用积分",
     *     operationId="detail",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="mobile", in="query", description="会员手机号", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",required={"point"},
     *                @SWG\Property(property="point", type="integer", default="7048", description="剩余可用积分"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function detail(Request $request)
    {
        $requestData = $request->only(["mobile"]);
        if ($messageBag = validation($requestData, [
            "mobile" => ["required", new MobileRule()]
        ], [
            "mobile.required" => "手机号必填"
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        $filter = (new MemberPointFilter($requestData))->get();
        $info = (new MemberPointService())->find($filter);
        return $this->response->array([
            "point" => (int)($info["point"] ?? 0)
        ]);
    }

    /**
     * @SWG\Patch(
     *     path="/ecx.member_point.update",
     *     tags={"会员"},
     *     summary="修改会员积分 - 增/减积分",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="update",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="mobile", in="formData", description="手机号", required=true, type="string"),
     *     @SWG\Parameter(name="increase_point", in="formData", description="增加积分（须>=0的整数；增加积分、减去积分，二选一必填）", required=false, type="integer"),
     *     @SWG\Parameter(name="decrease_point", in="formData", description="减去积分（须>=0的整数；增加积分、减去积分，二选一必填）", required=false, type="integer"),
     *     @SWG\Parameter(name="record", in="formData", description="积分变动原因（备注）", required=false, type="string"),
     *     @SWG\Parameter(name="external_id", in="formData", description="外部唯一标识，外部调用方自定义", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", example="success", description="响应描述"),
     *             @SWG\Property(property="data", type="array", description="",
     *               @SWG\Items(
     *               ),
     *             ),

     *         ),
     *     ),
     * )
     */
    public function update(Request $request)
    {
        // 获取请求数据
        $requestData = $request->input();
        $messageBag = validation($requestData, [
            "mobile" => ["required", new MobileRule()],
            "increase_point" => ["required_without:decrease_point", "integer", "min:0"],
            "decrease_point" => ["required_without:increase_point", "integer", "min:0"],
            "record" => ["required"],
            "external_id" => ["nullable"]
        ], [
            "increase_point.required_without" => "增加积分或减去积分二选一必填",
            "decrease_point.required_without" => "增加积分或减去积分二选一必填",
            "mobile.*" => "请求参数错误：会员手机号参数错误",
            "increase_point.*" => "请求参数错误：增加积分参数错误",
            "decrease_point.*" => "请求参数错误：减去积分参数错误",
            "record.*" => "请求参数错误：积分变动原因（备注）参数错误",
            "external_id.*" => "请求参数错误：外部唯一标识参数错误",
        ]);
        if (!is_null($messageBag)) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        if (isset($requestData["increase_point"]) && isset($requestData["decrease_point"])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "增加积分和减去积分只能存在一个");
        }
        // 获取过滤条件
        $filter = (new MemberPointFilter($requestData))->get();
        if (!isset($filter["user_id"]) || $filter["user_id"] <= 0) {
            throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND);
        }
        (new MemberPointService())->update($filter, $requestData);
        return $this->response->array([]);
    }
}
