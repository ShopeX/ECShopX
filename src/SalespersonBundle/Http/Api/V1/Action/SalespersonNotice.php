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

namespace SalespersonBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use SalespersonBundle\Services\SalespersonNoticeService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

class SalespersonNotice extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/salespersonotice/notice",
     *     summary="添加导购通知",
     *     tags={"导购"},
     *     description="添加导购通知",
     *     operationId="addNotice",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="query", description="标题", required=true, type="string"),
     *     @SWG\Parameter( name="content", in="query", description="内容", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="result", type="string", example="true", description="添加结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function addNotice(Request $request)
    {
        $distributorIds = $request->get('distributorIds');
        if ($distributorIds) {
            throw new ResourceException("您没有此操作的权限");
        }

        $companyId = app('auth')->user()->get('company_id');
        $input = $request->input();
        $this->_checkInput($input);

        $data = [
            'title' => $input['title'],
            'content' => $input['content'],
            'company_id' => $companyId,
            'status' => 1,
            'notice_type' => 2
        ];

        $salespersonNoticeService = new SalespersonNoticeService();
        $result = $salespersonNoticeService->addNotice($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/salespersonotice/sendnotice",
     *     summary="发送导购通知",
     *     tags={"导购"},
     *     description="发送导购通知",
     *     operationId="sendNotice",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="notice_id", in="query", description="通知id", required=true, type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id，数组转json", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="result", type="string", example="true", description="添加结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function sendNotice(Request $request)
    {
        $distributorIds = $request->get('distributorIds');
        if ($distributorIds) {
            throw new ResourceException("您没有此操作的权限");
        }

        $companyId = app('auth')->user()->get('company_id');

        $noticeId = $request->input('notice_id', '');
        $distributorIds = $request->input('distributor_id', '');

        if (!$noticeId) {
            throw new ResourceException('请选择通知');
        }
        if ((!is_array($distributorIds) || count($distributorIds) == 0) && $distributorIds != 'all') {
            throw new ResourceException('请选择店铺');
        }
        $distributorId = $distributorIds != 'all' ? json_encode($distributorIds) : 'all';
        $salespersonNoticeService = new SalespersonNoticeService();
        $result = $salespersonNoticeService->sendNotice($companyId, $noticeId, $distributorId);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/salespersonotice/list",
     *     summary="导购通知列表",
     *     tags={"导购"},
     *     description="导购通知列表",
     *     operationId="getNoticeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="string"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="query", description="标题", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态，0所有状态，1已发送，2未发送，3已撤回", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="6", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="notice_id", type="string", example="1", description="通知id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="title", type="string", example="测试", description="通知标题"),
     *                          @SWG\Property( property="content", type="string", example="测试测试电饭锅的说法个", description="通知内容"),
     *                          @SWG\Property( property="distributor_id", type="string", example="[1,2,3,4,5]", description="店铺id"),
     *                          @SWG\Property( property="all_distributor", type="string", example="1", description="店铺id"),
     *                          @SWG\Property( property="notice_type", type="string", example="2", description="通知类型，1系统通知，2总部通知，3其他通知"),
     *                          @SWG\Property( property="sent_times", type="string", example="0", description="发送次数"),
     *                          @SWG\Property( property="is_delete", type="string", example="0", description="是否已删除"),
     *                          @SWG\Property( property="withdraw", type="string", example="0", description="是否撤回"),
     *                          @SWG\Property( property="last_sent_time", type="string", example="1590979890", description="最后发送时间"),
     *                          @SWG\Property( property="created", type="string", example="1589888890", description="created"),
     *                          @SWG\Property( property="updated", type="string", example="1589888890", description="updated"),
     *                          @SWG\Property( property="status", type="string", example="2", description="状态，1未发送，2已发送，3已撤回"),
     *                      ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getNoticeList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $noticeTitle = $request->input('title', '');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);
        $status = intval($request->input('status', 0));

        if ($noticeTitle) {
            $filter['title|contains'] = $noticeTitle;
        }
        if ($status) {
            $filter['status'] = $status;
        }
        $filter['company_id'] = $companyId;
        $filter['is_delete'] = 0;

        $salespersonNoticeService = new SalespersonNoticeService();
        $result = $salespersonNoticeService->getNoticeList($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/salespersonotice/detail",
     *     summary="导购通知详情",
     *     tags={"导购"},
     *     description="导购通知详情",
     *     operationId="getNoticeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="notice_id", in="query", description="通知id", required=true, type="integer"),
     *     @SWG\Parameter( name="with_log", in="query", description="是否返回发送历史店铺", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="notice_id", type="integer", example="5", description="通知id"),
     *                  @SWG\Property( property="company_id", type="integer", example="1", description="公司id"),
     *                  @SWG\Property( property="title", type="string", example="通知标题", description="通知标题"),
     *                  @SWG\Property( property="content", type="string", example="放假啦~~", description="通知内容"),
     *                  @SWG\Property( property="distributor_id", type="string", example="[1,2,3]", description="店铺id"),
     *                  @SWG\Property( property="all_distributor", type="string", example="0", description="店铺id"),
     *                  @SWG\Property( property="notice_type", type="string", example="2", description="通知类型，1系统通知，2总部通知，3其他通知"),
     *                  @SWG\Property( property="sent_times", type="string", example="0", description="发送次数"),
     *                  @SWG\Property( property="is_delete", type="string", example="0", description="是否已删除"),
     *                  @SWG\Property( property="withdraw", type="string", example="0", description="是否撤回"),
     *                  @SWG\Property( property="last_sent_time", type="string", example="1611816422", description="最后发送时间"),
     *                  @SWG\Property( property="created", type="string", example="1609817499", description="created"),
     *                  @SWG\Property( property="updated", type="string", example="1609817499", description="updated"),
     *                  @SWG\Property( property="status", type="string", example="2", description="状态，1未发送，2已发送，3已撤回"),
     *                  @SWG\Property( property="distributors", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="name", type="string", example="获客体系", description="通知店铺(全部店铺为 all)"),
     *                      ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getNoticeDetail(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $noticeId = $request->input('notice_id', '');
        $withLog = intval($request->input('with_log', 0));
        if (!$noticeId) {
            throw new ResourceException('请选择通知');
        }

        $filter = [
            'company_id' => $companyId,
            'notice_id' => $noticeId
        ];

        $salespersonNoticeService = new SalespersonNoticeService();
        $result = $salespersonNoticeService->getNoticeDetail($filter, $withLog);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/salespersonotice/notice",
     *     summary="删除导购通知",
     *     tags={"导购"},
     *     description="删除导购通知",
     *     operationId="deleteNotice",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="notice_id", in="query", description="通知id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="result", type="string", example="true", description="删除结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function deleteNotice(Request $request)
    {
        $distributorIds = $request->get('distributorIds');
        if ($distributorIds) {
            throw new ResourceException("您没有此操作的权限");
        }

        $companyId = app('auth')->user()->get('company_id');
        $noticeId = $request->input('notice_id', '');
        if (!$noticeId) {
            throw new ResourceException('请选择通知');
        }

        $filter = [
            'notice_id' => $noticeId,
            'company_id' => $companyId
        ];
        $data = [
            'is_delete' => 1
        ];
        $salespersonNoticeService = new SalespersonNoticeService();
        $res = $salespersonNoticeService->updateNotice($filter, $data);
        if ($res) {
            $result = ['success' => true];
        } else {
            $result = ['success' => false];
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/salespersonotice/notice",
     *     summary="修改导购通知",
     *     tags={"导购"},
     *     description="修改导购通知",
     *     operationId="updateNotice",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="notice_id", in="query", description="通知id", required=true, type="integer"),
     *     @SWG\Parameter( name="title", in="query", description="标题", required=true, type="string"),
     *     @SWG\Parameter( name="content", in="query", description="内容", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="result", type="string", example="true", description="更新结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function updateNotice(Request $request)
    {
        $distributorIds = $request->get('distributorIds');
        if ($distributorIds) {
            throw new ResourceException("您没有此操作的权限");
        }

        $companyId = app('auth')->user()->get('company_id');
        $input = $request->input();
        $this->_checkInput($input);

        $data = [
            'title' => $input['title'],
            'content' => $input['content'],
            'company_id' => $companyId,
        ];

        $noticeId = $request->input('notice_id', '');
        if (!$noticeId) {
            throw new ResourceException('请选择通知');
        }

        $filter = [
            'notice_id' => $noticeId,
            'company_id' => $companyId
        ];
        $salespersonNoticeService = new SalespersonNoticeService();
        $res = $salespersonNoticeService->updateNotice($filter, $data);
        if ($res) {
            $result = ['success' => true];
        } else {
            $result = ['success' => false];
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/salespersonotice/withdrawnotice",
     *     summary="撤回导购通知",
     *     tags={"导购"},
     *     description="撤回导购通知",
     *     operationId="withdrawNotice",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="notice_id", in="query", description="通知id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="stauts", type="string", example="true", description="撤回结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function withdrawNotice(Request $request)
    {
        $distributorIds = $request->get('distributorIds');
        if ($distributorIds) {
            throw new ResourceException("您没有此操作的权限");
        }
        $companyId = app('auth')->user()->get('company_id');
        $noticeId = $request->input('notice_id', 0);

        if (!$noticeId) {
            throw new ResourceException('请选择要撤回的通知');
        }

        $salespersonNoticeService = new SalespersonNoticeService();
        $result = $salespersonNoticeService->withdrawNotice($companyId, $noticeId);

        return $this->response->array($result);
    }

    private function _checkInput($input)
    {
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');

        if (!$title) {
            throw new ResourceException('标题不能为空');
        }
        if (!$content) {
            throw new ResourceException('内容不能为空');
        }
    }
}
