<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use ThirdPartyBundle\Services\SaasErpLogService;

class SaasErp extends Controller
{
    /**
     * @SWG\Get(
     *     path="/saaserp/log/list",
     *     summary="获取saasErp通信日志列表",
     *     tags={"ShopexErp"},
     *     description="获取saasErp通信日志列表",
     *     operationId="getLogList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=true, type="string"),
     *     @SWG\Parameter( name="api_type", in="query", description="类型", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getLogList(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $orderBy = ['created' => 'desc'];
        $filter['company_id'] = app('auth')->user()->get('company_id');
        if ($request->get('api_type')) {
            $filter['api_type'] = $request->get('api_type');
        }
        if ($request->get('status')) {
            $filter['status'] = $request->get('status');
        }

        if ($request->get('content')) {
            $filter['params|contains'] = $request->get('content');
        }

        if ($request->get('updated')) {
            list($startDate, $endDate) = $request->get('updated');
            $filter['updated|lte'] = strtotime($endDate." 23:59:59");
            $filter['updated|gte'] = strtotime($startDate." 00:00:00");
        }

        $saasErpLogService = new SaasErpLogService();
        $result = $saasErpLogService->lists($filter, $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }
}
