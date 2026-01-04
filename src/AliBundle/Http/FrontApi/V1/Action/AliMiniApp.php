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
