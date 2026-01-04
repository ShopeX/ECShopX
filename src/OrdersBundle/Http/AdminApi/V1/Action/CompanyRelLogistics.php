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
