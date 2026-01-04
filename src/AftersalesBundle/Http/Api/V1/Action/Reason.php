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

namespace AftersalesBundle\Http\Api\V1\Action;

use GoodsBundle\Services\MultiLang\MagicLangTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use AftersalesBundle\Services\ReasonService;

class Reason extends Controller
{
    use MagicLangTrait;
    /**
     * @SWG\Get(
     *     path="/aftersales/reason/list",
     *     summary="售后原因列表获取",
     *     tags={"售后"},
     *     description="售后原因列表获取",
     *     operationId="getSreasonList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data", type="array", @SWG\Items( example="不想要了" )
     *             )
     *         )
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getSreasonList()
    {
        $companyId = app('auth')->user()->get('company_id');
        $lang = $this->getLang();
        $Reason = new ReasonService();
        $data_list = $Reason->getList($companyId, 1, $lang);

        return $this->response->array($data_list);
    }


    /**
     * @SWG\Get(
     *     path="/aftersales/reason/save",
     *     summary="售后原因列表保存",
     *     tags={"售后"},
     *     description="Saveset",
     *     operationId="Saveset",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *    @SWG\Parameter(
     *         name="reason[]",
     *         in="query",
     *         description="售后类型",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function Saveset(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $reason_list = $request->input('reason');
        $lang = $this->getLang();
        $Reason = new ReasonService();
        $data = $Reason->saveSet($companyId, $reason_list,$lang);

        return $this->response->array($data);
    }
}
