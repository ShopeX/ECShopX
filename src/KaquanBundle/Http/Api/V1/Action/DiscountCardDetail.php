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

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use KaquanBundle\Services\UserDiscountService;

class DiscountCardDetail extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/discountcard/detail/list",
     *     summary="获取卡券领取列表以及使用明细",
     *     tags={"卡券"},
     *     description="获取卡券领取列表以及使用明细详细信息",
     *     operationId="getDiscountCardDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="卡券 id",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_use",
     *         in="query",
     *         description="是否使用",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="页码" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="pageSize", description="条数" ),
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/UserDiscount"
     *                       ),
     *                  ),
     *                  @SWG\Property( property="count", type="string", example="7", description="总数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getDiscountCardDetail(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        $validator = app('validator')->make($request->all(), [
            'card_id' => 'required',
            'page' => 'int',
            'pageSize' => 'int'
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('KaquanBundle.card_detail_error'), $validator->errors());
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['card_id'] = $request->input('card_id');
        // 手机号
        if ($request->input('mobile', false)) {
            $filter['mobile'] = $request->input('mobile');
        }
        $userDiscountService = new UserDiscountService();
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        if ($request->input('is_use')) {
            $result = $userDiscountService->getDiscountUserLogsList($filter, $request->input('page', 1), $request->input('pageSize', 10));
        } else {
            // 参与活动名称
            if ($request->input('activity_name', false)) {
                $filter['activity_name|contains'] = $request->input('activity_name');
            }
            // 券状态
            if ($request->input('status', false)) {
                $filter['status'] = $request->input('status');
            }
            $result = $userDiscountService->getDiscountUserList($filter, $request->input('page', 1), $request->input('pageSize', 10));
        }
        foreach ($result['list'] as $key => $value) {
            if ($datapassBlock) {
                $value['username'] != '无' and $result['list'][$key]['username'] = data_masking('truename', (string) $value['username']);
                $value['mobile'] != '无' and $result['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
            }
        }

        return $this->response->array($result);
    }
}
