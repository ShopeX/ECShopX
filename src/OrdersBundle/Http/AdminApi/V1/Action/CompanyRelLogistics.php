<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Http\AdminApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\CompanyRelLogisticsServices;

class CompanyRelLogistics extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/logistics/list",
     *     summary="获取启用物流公司列表",
     *     tags={"订单"},
     *     description="获取启用物流公司列表",
     *     operationId="getCompanyLogisticsList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Property(property="total_count", type="integer", description="总记录条数"),
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="value", type="string", description="物流编码"),
     *                     @SWG\Property(property="name", type="string", description="物流名称"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function getCompanyLogisticsList(Request $request)
    {
        $inputData = $request->input();
        $authInfo = $this->auth->user();
        $filter['company_id'] = $authInfo['company_id'];

        $kuaidiType = app('redis')->get('kuaidiTypeOpenConfig:'. sha1($filter['company_id']));

        if ($kuaidiType == 'kuaidi100') {
            $cols = 'kuaidi_code as value,corp_name as name';
        } else {
            $cols = 'corp_code as value,corp_name as name';
        }

        $companyRelLogisticsServices = new CompanyRelLogisticsServices();
        $companyRelLogisticsList = $companyRelLogisticsServices->getCompanyRelLogistics($filter, 1, 100, ['id' => 'DESC'], $cols);
        $other = [[
            "value" => 'OTHER',
            "name" => "其他",
        ]];
        $companyRelLogisticsList['list'] = array_merge($other, $companyRelLogisticsList['list']);

        return $this->response->array($companyRelLogisticsList);
    }
}
