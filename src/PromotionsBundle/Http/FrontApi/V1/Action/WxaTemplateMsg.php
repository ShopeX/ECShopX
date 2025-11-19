<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

class WxaTemplateMsg extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wxapp/promotion/formid",
     *     summary="保存小程序formid",
     *     tags={"营销"},
     *     description="保存小程序formid，如果多个formid使用 , 符合连接",
     *     operationId="setFormId",
     *     @SWG\Parameter( name="formid", in="query", description="formid", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", example=true),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function setFormId(Request $request)
    {
        return $this->response->array(['status' => true]);
    }
}
