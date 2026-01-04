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

namespace AliyunsmsBundle\Http\Api\V1\Action;

use AliyunsmsBundle\Services\SceneService;
use AliyunsmsBundle\Services\SettingService;
use AliyunsmsBundle\Services\SettingServService;
use AliyunsmsBundle\Services\SignService;
use AliyunsmsBundle\Services\TemplateService;
use CrossBorderBundle\Services\Set;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use CompanysBundle\Services\CompanysService;
use AliyunsmsBundle\Http\Api\V1\Action\Setting;

class Setting extends Controller
{
    /**
     * @SWG\Get(
     *     path="/aliyunsms/config",
     *     summary="短信基础配置",
     *     tags={"阿里短信"},
     *     description="短信基础配置",
     *     operationId="getConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="accesskey_id", type="string", example="", description=""),
     *                  @SWG\Property( property="accesskey_secret", type="string", example="", description=""),
     *                  @SWG\Property( property="scene_num", type="integer", example="", description="自动发送短信场景数"),
     *                  @SWG\Property( property="sign_num", type="integer", example="", description="短信签名数"),
     *                  @SWG\Property( property="template_num", type="integer", example="", description="短信模板数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function getConfig(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new SettingService();
        $data = $service->getConfig(['company_id' => $companyId]);
        $status = $service->getStatus($companyId);
        $scene_num = (new SceneService())->count(['company_id' => $companyId]);
        $sign_num = (new SignService())->count(['company_id' => $companyId, 'status' => 1]);
        $template_num = (new TemplateService())->count(['company_id' => $companyId, 'status' => 1]);

        $data['scene_num'] = $scene_num ?? 0;
        $data['sign_num'] = $sign_num ?? 0;
        $data['template_num'] = $template_num ?? 0;
        $data['status'] = $status;
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/aliyunsms/config",
     *     summary="设置短信基础配置",
     *     tags={"阿里短信"},
     *     description="设置短信基础配置",
     *     operationId="setConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="accesskey_id", in="formData", description="accesskey_id", required=true, type="string"),
     *     @SWG\Parameter( name="accesskey_secret", in="formData", description="accesskey_secret", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function setConfig(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputData = $request->input();
        $rules = [
            'accesskey_id' => ['required', '请输入Accesskey ID'],
            'accesskey_secret' => ['required', '请输入Accesskey secret'],
        ];
        $errorMessage = validator_params($inputData, $rules);

        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $service = new Setting();
        $inputData['company_id'] = $companyId;
        $service = new SettingService();
        $service->setConfig($inputData);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/aliyunsms/status",
     *     summary="设置短信状态",
     *     tags={"阿里短信"},
     *     description="设置短信状态",
     *     operationId="setStatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="formData", description="status:true/false", required=true, type="boolean"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function setStatus(Request $request) {
        $companyId = app('auth')->user()->get('company_id');
        $status = $request->input('status', false);
        $service = new SettingService();
        $service->setStatus($companyId, $status);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/aliyunsms/status",
     *     summary="获取短信状态",
     *     tags={"阿里短信"},
     *     description="获取短信状态",
     *     operationId="getStatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="aliyunsms_status", type="boolean", description="阿里云短信状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function getStatus(Request $request) {
        $companyId = app('auth')->user()->get('company_id');
        $service = new SettingService();
        $status = $service->getStatus($companyId);
        return $this->response->array(['aliyunsms_status' => $status]);
    }
}
