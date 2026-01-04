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

namespace SalespersonBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use SalespersonBundle\Services\SalespersonNoticeService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

class SalespersonNoticeController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/admin/wxapp/noticeunreadcount",
     *     summary="获取导购员未读消息数量",
     *     tags={"导购"},
     *     description="获取导购员未读消息数量",
     *     operationId="getUnreadNum",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="string", example="2", description="未读消息数量"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getUnreadNum(Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'] ?? 0;
        if (!$companyId) {
            throw new ResourceException('公司id必须');
        }

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $authInfo['distributor_id'],
            'salesperson_id' => $authInfo['salesperson_id']
        ];

        $salespersonNoticeService = new SalespersonNoticeService();
        $result = $salespersonNoticeService->getUnreadNum($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/noticelist",
     *     summary="获取导购员通知列表",
     *     tags={"导购"},
     *     description="获取导购员通知列表",
     *     operationId="getNoticeList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/NoticeInfo"
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total_count", type="string", example="4", description="总数"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getNoticeList(Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'] ?? 0;
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);

        if (!$companyId) {
            throw new ResourceException('公司id必须');
        }

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $authInfo['distributor_id'],
            'salesperson_id' => $authInfo['salesperson_id'],
            'is_deleted' => 0
        ];
        $salespersonNoticeService = new SalespersonNoticeService();
        $result = $salespersonNoticeService->salespersonGetNoticeList($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/notice",
     *     summary="获取通知详情并设为已读",
     *     tags={"导购"},
     *     description="获取通知详情并设为已读",
     *     operationId="getNoticeDetail",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="notice_id", in="query", description="通知ID", required=true, type="integer", default="1" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/NoticeInfo"
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getNoticeDetail(Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'] ?? 0;
        $noticeId = intval($request->input('notice_id', 0));

        if (!$noticeId) {
            throw new ResourceException('请选择通知');
        }
        if (!$companyId) {
            throw new ResourceException('公司id必须');
        }

        $salespersonNoticeService = new SalespersonNoticeService();
        $result = $salespersonNoticeService->salespersonGetNoticeDetail($noticeId, $authInfo['salesperson_id'], $companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Definition(
     *     definition="NoticeInfo",
     *     type="object",
     *     @SWG\Property( property="notice_id", type="string", example="2", description="通知id"),
     *         @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *         @SWG\Property( property="title", type="string", example="这玩意在哪看的", description="通知标题"),
     *         @SWG\Property( property="content", type="string", example="啦啦啦", description="通知内容"),
     *         @SWG\Property( property="distributor_id", type="string", example="[1,2,3]", description="店铺id"),
     *         @SWG\Property( property="all_distributor", type="string", example="1", description="店铺id"),
     *         @SWG\Property( property="notice_type", type="string", example="2", description="通知类型，1系统通知，2总部通知，3其他通知"),
     *         @SWG\Property( property="sent_times", type="string", example="0", description="发送次数"),
     *         @SWG\Property( property="is_delete", type="string", example="0", description="是否已删除"),
     *         @SWG\Property( property="withdraw", type="string", example="0", description="是否撤回"),
     *         @SWG\Property( property="last_sent_time", type="string", example="1589892145", description="最后发送时间"),
     *         @SWG\Property( property="created", type="string", example="1589892131", description="创建时间戳"),
     *         @SWG\Property( property="updated", type="string", example="1589892131", description="更新时间戳"),
     *         @SWG\Property( property="status", type="string", example="2", description="状态，1未发送，2已发送，3已撤回"),
     *         @SWG\Property( property="read_status", type="string", example="1", description="是否已读"),
     * )
     */
}
