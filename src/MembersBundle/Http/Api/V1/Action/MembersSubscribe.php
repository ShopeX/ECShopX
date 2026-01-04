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

namespace MembersBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use MembersBundle\Services\GoodsArrivalNoticeService;

class MembersSubscribe extends Controller
{
    /**
     * @SWG\Get(
     *     path="/members/subscribe/list",
     *     summary="获取订阅列表",
     *     tags={"会员"},
     *     description="获取订阅列表",
     *     operationId="only",
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="页码" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="pageSize", description="条数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_start_begin", description="开始日期" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_start_end", description="结束日期" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="sub_type", description="默认goods" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="rel_id", description="关联id" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="sub_status", description="通知状态" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="21", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="sub_id", type="string", example="39", description="订阅id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="user_id", type="string", example="20391", description="用户id"),
     *                          @SWG\Property( property="open_id", type="string", example="oHxgH0QZX9nnxzR6U_IAKDlsGzqY", description="openid"),
     *                          @SWG\Property( property="rel_id", type="string", example="5190", description="关联id"),
     *                          @SWG\Property( property="sub_type", type="string", example="goods", description="订阅类型。可选值有 goods:商品缺货通知"),
     *                          @SWG\Property( property="remarks", type="string", example="测试商品1", description="备注"),
     *                          @SWG\Property( property="sub_status", type="string", example="NO", description="通知状态。可选值有 NO—未通知;SUCCESS-已通知;ERROR-通知失败"),
     *                          @SWG\Property( property="err_reason", type="string", example="null", description="通知失败原因"),
     *                          @SWG\Property( property="updated", type="string", example="1611813680", description="修改时间"),
     *                          @SWG\Property( property="created", type="string", example="1611813680", description=""),
     *                          @SWG\Property( property="username", type="string", example="匿名", description="姓名"),
     *                          @SWG\Property( property="item_name", type="string", example="测试商品1", description="商品名称"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones")))
     * )
     */
    public function getLists(Request $request)
    {
        $goodsArrivalNoticeService = new GoodsArrivalNoticeService();

        $filter['company_id'] = app('auth')->user()->get('company_id');

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $orderBy = ['created' => 'DESC'];
        if ($request->input('time_start_begin')) {
            $filter['created|gte'] = $request->input('time_start_begin');
            $filter['created|lte'] = $request->input('time_start_end');
        }

        $filter['sub_type'] = $request->input('sub_type', 'goods');

        if ($request->input('rel_id')) {
            $filter['rel_id'] = $request->input('rel_id');
        }

        if ($request->has('sub_status') && $request->get('sub_status') != '') {
            $filter['sub_status'] = $request->input('sub_status');
        }

        $data = $goodsArrivalNoticeService->lists($filter, $page, $pageSize, $orderBy);

        return $this->response->array($data);
    }
}
