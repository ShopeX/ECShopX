<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AliBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ValidationHttpException;
use Illuminate\Http\Request;

class AliMiniApp extends Controller
{
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/alitemplatemessage",
     *     summary="获取小程序订阅消息模板列表",
     *     tags={"支付宝"},
     *     description="获取小程序订阅消息模板列表",
     *     operationId="getTemplateMessage",
     *     @SWG\Parameter( name="source_type", in="query", description="发起订阅类型", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="template_id", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getTemplateMessage(Request $request)
    {
        $authInfo = $request->get('auth');
        $sourceType = $request->input('source_type');
        $result['template_id'] = [];
        if (!$sourceType) {
            return $this->response->array($result);
        }
        $lists = app('aliTemplateMsg')->getValidTempLists($authInfo['company_id'], $sourceType);
        if ($lists) {
            $result['template_id'] = array_column($lists, 'template_id');
        }
        return $this->response->array($result);
    }

}
