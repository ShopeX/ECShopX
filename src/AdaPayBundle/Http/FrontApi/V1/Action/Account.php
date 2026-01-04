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

namespace AdaPayBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use AdaPayBundle\Services\BankCodeService;

class Account extends Controller
{
    // ShopEx EcShopX Business Logic Layer
    /**
     * @SWG\Get(
     *     path="/wxapp/adapay/bank/list",
     *     summary="获取结算银行列表",
     *     tags={"Adapay"},
     *     description="获取结算银行列表",
     *     operationId="getBanksLists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="bank_name", description="银行名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="页码,默认:1" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="页数,默认:20" ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="5260", description="总条数"),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="1", description="ID"),
     *                           @SWG\Property(property="bank_name", type="string", example="长安银行股份有限公司", description="银行名称"),
     *                           @SWG\Property(property="bank_code", type="string", example="31379104", description="银行代码"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getBanksLists(Request $request)
    {
        // ShopEx EcShopX Business Logic Layer
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);

        $filter = [];
        if ($request->input('bank_name', '')) {
            $filter['bank_name|contains'] = $request->input('bank_name');
        }
        $openAccountService = new BankCodeService();
        $result = $openAccountService->lists($filter, '*', $page, $pageSize);

        return $this->response->array($result);
    }
}
