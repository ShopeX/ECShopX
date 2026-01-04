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

namespace PromotionsBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\WxaTemplateMsg\TemplateList;

// 小程序通知模版
class WxaTemplate extends Controller
{
    /**
     * @SWG\Definition(
     *     definition="WxTemplatesDetail",
     *     type="object",
     *     @SWG\Property( property="id", type="string", example="33", description="id"),
     *     @SWG\Property( property="template_name", type="string", example="yykweishop", description="小程序模板名称"),
     *     @SWG\Property( property="wxa_template_id", type="string", example="5117", description="微信小程序通知模版库id"),
     *     @SWG\Property( property="company_id", type="string", example="1", description="企业id"),
     *     @SWG\Property( property="notice_type", type="string", example="wxa", description="通知类型"),
     *     @SWG\Property( property="tmpl_type", type="string", example="会员提醒", description="模板分类"),
     *     @SWG\Property( property="template_id", type="string", example="jGmgI0D_DYKYQ7RMYvGw36aPLABXXbe2xA-1C2MG9hs", description="模板id,发送小程序通知使用"),
     *     @SWG\Property( property="title", type="string", example="注册成功提醒", description="通知标题"),
     *     @SWG\Property( property="scenes_name", type="string", example="memberCreateSucc", description="发送场景"),
     *     @SWG\Property( property="content", type="array",
     *         @SWG\Items( type="object",
     *             @SWG\Property( property="column", type="string", example="date", description="自行更改字段描述"),
     *             @SWG\Property( property="title", type="string", example="注册时间", description="标题"),
     *             @SWG\Property( property="keyword", type="string", example="date2", description="关键字"),
     *         ),
     *     ),
     *     @SWG\Property( property="is_open", type="string", example="true", description="是否开启 1:开启,0:关闭"),
     *     @SWG\Property( property="send_time_desc", type="object",
     *         @SWG\Property( property="title", type="string", example="会员注册成功后触发", description="标题"),
     *     ),
     *     @SWG\Property( property="created", type="string", example="1580979574", description=""),
     * )
     */

    /**
     * @SWG\Get(
     *     path="/wxa/notice/templates",
     *     summary="获取小程序通知模版列表",
     *     tags={"营销"},
     *     description="获取小程序通知模版列表",
     *     operationId="getWxaTemplateList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="通知的小程序模版唯一标识", required=true, type="string"),
     *     @SWG\Parameter( name="wxapp_appid", in="query", description="通知的小程序appid", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="list", type="object",
     *                  @SWG\Property( property="aftersalesRefuse", type="object",
     *                      ref="#/definitions/WxTemplatesDetail"
     *                  ),
     *                  @SWG\Property( property="goodsArrivalNotice", type="object",
     *                      ref="#/definitions/WxTemplatesDetail"
     *                  ),
     *                  @SWG\Property( property="memberCreateSucc", type="object",
     *                      ref="#/definitions/WxTemplatesDetail"
     *                  ),
     *                  @SWG\Property( property="orderDeliverySucc", type="object",
     *                      ref="#/definitions/WxTemplatesDetail"
     *                  ),
     *                  @SWG\Property( property="payOrdersRemind", type="object",
     *                      ref="#/definitions/WxTemplatesDetail"
     *                  ),
     *                  @SWG\Property( property="paymentSucc", type="object",
     *                      ref="#/definitions/WxTemplatesDetail"
     *                  ),
     *                  @SWG\Property( property="registrationResultNotice", type="object",
     *                      ref="#/definitions/WxTemplatesDetail"
     *                  ),
     *                  @SWG\Property( property="userGetCardSucc", type="object",
     *                      ref="#/definitions/WxTemplatesDetail"
     *                  ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getWxaTemplateList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $templateName = $request->input('template_name');
        $wxappAppid = $request->input('wxapp_appid');

        $templateList = new TemplateList();

        $data['list'] = $templateList->getTemplateList($companyId, $templateName, $wxappAppid);
        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/wxa/notice/templates",
     *     summary="更新小程序通知模版",
     *     tags={"营销"},
     *     description="更新小程序通知模版",
     *     operationId="openWxaTemplate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="通知的小程序模版唯一标识", required=true, type="string"),
     *     @SWG\Parameter( name="wxapp_appid", in="query", description="通知的小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="scenes_name", in="query", description="消息模版发送场景", required=true, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否开通", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function openWxaTemplate(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $templateName = $request->input('template_name');
        $wxappAppid = $request->input('wxapp_appid');
        $scenesName = $request->input('scenes_name');
        $isOpen = $request->input('is_open');
        $sendTime = $request->input('send_time', 0);

        $isOpen = $isOpen == 'true' ? true : false;

        $templateList = new TemplateList();

        $templateList->openTemplate($companyId, $scenesName, $templateName, $wxappAppid, $isOpen, $sendTime);
        return $this->response->array(['status' => true]);
    }
}
