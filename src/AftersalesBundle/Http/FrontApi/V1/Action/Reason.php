<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AftersalesBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use AftersalesBundle\Services\ReasonService;
use GoodsBundle\Services\MultiLang\MagicLangTrait;

class Reason extends Controller
{
    use MagicLangTrait;
    /**
     * @SWG\Get(
     *     path="/aftersales/reason/list",
     *     summary="获取售后原因列表",
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
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items()
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getSreasonList(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        $lang = $this->getLang();
        $Reason = new ReasonService();
        $data_list = $Reason->getList($companyId, 0, $lang);

        return $this->response->array($data_list);
    }
}
