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

namespace WorkWechatBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use WorkWechatBundle\Services\WorkWechatMessageTemplateService;

class WorkWechatMessageTemplate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/workwechat/messagetemplate/{template_id}",
     *     summary="企业微信通知模板",
     *     tags={"企业微信"},
     *     description="企业微信通知模板",
     *     operationId="getTemplate",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="template_id",
     *         in="path",
     *         description="模版id",
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="1"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="template_id", type="string", example="waitingDeliveryNotice"),
     *                  @SWG\Property( property="disabled", type="string", example="false"),
     *                  @SWG\Property( property="emphasis_first_item", type="string", example="true"),
     *                  @SWG\Property( property="title", type="string", example="有新的订单待处理"),
     *                  @SWG\Property( property="description", type="string", example="订单处理"),
     *                  @SWG\Property( property="content", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="key", type="string", example="订单号"),
     *                          @SWG\Property( property="value", type="string", example="125908123123"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones") ) )
     * )
     */
    public function getTemplate($templateId)
    {
        // Built with ShopEx Framework
        $workWechatMessageTemplateService = new WorkWechatMessageTemplateService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $workWechatMessageTemplateService->getInfo(['company_id' => $companyId, 'template_id' => $templateId]);
        return $this->response->array($result ?: []);
    }

    /**
     * @SWG\Get(
     *     path="/workwechat/messagetemplate",
     *     summary="企业微信通知模板",
     *     tags={"企业微信"},
     *     description="企业微信通知模板",
     *     operationId="getTemplateList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="waitingDeliveryNotice", type="object",
     *                          @SWG\Property( property="id", type="string", example="1"),
     *                          @SWG\Property( property="company_id", type="string", example="1"),
     *                          @SWG\Property( property="template_id", type="string", example="waitingDeliveryNotice"),
     *                          @SWG\Property( property="disabled", type="string", example="0"),
     *                          @SWG\Property( property="emphasis_first_item", type="string", example="1"),
     *                          @SWG\Property( property="title", type="string", example="有新的订单待处理"),
     *                          @SWG\Property( property="description", type="string", example="订单处理"),
     *                          @SWG\Property( property="content", type="string", example="[{'key': '订单号', 'value': '125908123123'}]"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones") ) )
     * )
     */
    public function getTemplateList()
    {
        $workWechatMessageTemplateService = new WorkWechatMessageTemplateService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $workWechatMessageTemplateService->getLists(['company_id' => $companyId]);
        if ($result) {
            $result = array_column($result, null, 'template_id');
        }
        return $this->response->array($result ?: []);
    }

    /**
     * @SWG\Put(
     *     path="/workwechat/messagetemplate/{template_id}",
     *     summary="企业微信通知模板保存",
     *     tags={"企业微信"},
     *     description="企业微信通知模板保存",
     *     operationId="saveTemplate",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="template_id",
     *         in="path",
     *         description="模板编号",
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="title", type="string", example="null"),
     *                  @SWG\Property( property="description", type="string", example="null"),
     *                  @SWG\Property( property="content", type="string", example="null"),
     *                  @SWG\Property( property="emphasis_first_item", type="string", example="null"),
     *                  @SWG\Property( property="disabled", type="string", example="null"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones") ) )
     * )
     */
    public function saveTemplate($templateId, Request $request)
    {
        $params = $request->all('title', 'description', 'content', 'emphasis_first_item', 'disabled');
        $workWechatMessageTemplateService = new WorkWechatMessageTemplateService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $workWechatMessageTemplateService->saveTemplate($companyId, $templateId, $params);
        return $this->response->array($result ?? []);
    }

    /**
     * @SWG\Put(
     *     path="/workwechat/messagetemplate/open/{template_id}",
     *     summary="企业微信通知模板开启",
     *     tags={"企业微信"},
     *     description="企业微信通知模板开启",
     *     operationId="openTemplate",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="template_id",
     *         in="path",
     *         description="模板编号",
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="disabled", type="string", example="false"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones") ) )
     * )
     */
    public function openTemplate($templateId)
    {
        $params['disabled'] = 'false';
        $workWechatMessageTemplateService = new WorkWechatMessageTemplateService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $workWechatMessageTemplateService->saveTemplate($companyId, $templateId, $params);
        return $this->response->array($result ?? []);
    }

    /**
     * @SWG\Put(
     *     path="/workwechat/messagetemplate/close/{template_id}",
     *     summary="企业微信通知模板关闭",
     *     tags={"企业微信"},
     *     description="企业微信通知模板关闭",
     *     operationId="openTemplate",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="template_id",
     *         in="path",
     *         description="template_id",
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="disabled", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/QywechatErrorRespones") ) )
     * )
     */
    public function closeTemplate($templateId)
    {
        $params['disabled'] = 'true';
        $workWechatMessageTemplateService = new WorkWechatMessageTemplateService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $workWechatMessageTemplateService->saveTemplate($companyId, $templateId, $params);
        return $this->response->array($result ?? []);
    }
}
