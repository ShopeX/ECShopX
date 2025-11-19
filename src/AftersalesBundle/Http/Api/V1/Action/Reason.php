<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
