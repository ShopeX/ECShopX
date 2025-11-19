<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ImBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use ImBundle\Services\EChatService;

class EChat extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/im/echat",
     *     summary="获取一洽配置",
     *     tags={"IM"},
     *     description="获取一洽配置",
     *     operationId="getInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="is_open",
     *                     description="是否开启",
     *                     type="string",
     *                     example=true
     *                 ),
     *                 @SWG\Property(
     *                     property="echat_url",
     *                     description="一洽客服链接",
     *                     type="string",
     *                     example=""
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ImErrorRespones") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $echatService = new EChatService();
        $result = $echatService->getInfo($companyId);
        return $this->response->array($result);
    }
}
