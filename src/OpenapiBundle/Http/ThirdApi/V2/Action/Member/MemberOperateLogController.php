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
use OpenapiBundle\Filter\Member\MemberOperateLogFilter;
use OpenapiBundle\Http\Controllers\Controller;
use OpenapiBundle\Services\Member\MemberOperateLogService;
use OpenapiBundle\Traits\Member\MemberOperateLogTrait;
use OpenapiBundle\Traits\Member\MemberTrait;
use Swagger\Annotations as SWG;

/**
 * 会员操作相关
 * Class MemberOperateLogController
 * @package OpenapiBundle\Http\Api\V2\Action\Member
 */
class MemberOperateLogController extends Controller
{
    // Hash: 0d723eca
    use MemberTrait, MemberOperateLogTrait {
        MemberOperateLogTrait::handleDataToList insteadof MemberTrait;
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member_operate_log.list",
     *     tags={"会员"},
     *     summary="查询会员信息操作日志 - 批量",
     *     description="查询会员信息操作日志 - 批量",
     *     operationId="list",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="page", in="query", description="当前页数", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）", required=false, type="integer"),
     *     @SWG\Parameter(name="mobile", in="query", description="会员手机号", required=true, type="string"),
     *     @SWG\Parameter(name="start_date", in="query", description="开始时间（时间格式：yyyy-MM-dd HH:mm:ss）", required=false, type="string"),
     *     @SWG\Parameter(name="end_date", in="query", description="结束时间（时间格式：yyyy-MM-dd HH:mm:ss）", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"pager","total_count","is_last_page","list"},
     *               @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", default="5", description="操作ID"),
     *                           @SWG\Property(property="operate_type", type="integer", default="2", description="操作类型【1 修改会员信息】【2 修改手机号】【3 修改会员等级】"),
     *                           @SWG\Property(property="old_data", type="string", default="13042418049", description="修改前的数据（不一定是json）"),
     *                           @SWG\Property(property="new_data", type="string", default="13042418048", description="修改后的数据（不一定是json）"),
     *                           @SWG\Property(property="operater", type="string", default="外部开发者", description="操作员姓名/昵称"),
     *                           @SWG\Property(property="created", type="string", default="2021-06-30 16:40:43", description="操作时间(日期格式:yyyy-MM-dd HH:mm:ss)"),
     *                           @SWG\Property(property="description", type="string", default="外部开发者 于2021-06-30 16:40:43进行了修改手机号的操作, 将 13042418049 改为 13042418048", description="操作描述"),
     *                           @SWG\Property(property="mobile", type="string", default="13042418048", description="会员手机号"),
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
        $filter = (new MemberOperateLogFilter())->get();
        // 查询列表数据
        $result = (new MemberOperateLogService())->list($filter, $this->getPage(), $this->getPageSize(), ["created" => "DESC"]);
        if (!empty($result["list"])) {
            // 追加用户信息
            $this->appendInfoToList((int)$filter["company_id"], $result["list"]);
            // 处理数据
            $this->handleDataToList((int)$filter["company_id"], $result["list"]);
        }
        return $this->response->array($result);
    }
}
