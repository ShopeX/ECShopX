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
use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Services\SmsService;
use PromotionsBundle\Services\SmsDriver\ShopexSmsClient;
use CompanysBundle\Services\CompanysService;

use ShuyunBundle\Services\SmsService as ShuyunSmsService;

class Sms extends Controller
{
    /**
     * @SWG\Get(
     *     path="/sms/basic",
     *     summary="短信账户基本信息",
     *     tags={"营销"},
     *     description="短信账户基本信息",
     *     operationId="getSmsBasic",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="sms_remainder", type="object",
     *                          @SWG\Property( property="msg", type="string", example="1", description="msg"),
     *                          @SWG\Property( property="res", type="string", example="succ", description="状态"),
     *                          @SWG\Property( property="info", type="object",
     *                                  @SWG\Property( property="account_info", type="object",
     *                                          @SWG\Property( property="mobile", type="string", example="", description="手机号"),
     *                                          @SWG\Property( property="active", type="string", example="1", description="状态"),
     *                                          @SWG\Property( property="entid", type="string", example="8813091119380", description="entid"),
     *                                          @SWG\Property( property="biz_user_id", type="string", example="", description=""),
     *                                  ),
     *                                  @SWG\Property( property="msg", type="string", example="", description="msg"),
     *                                  @SWG\Property( property="month_residual", type="string", example="435", description="每月短信余额"),
     *                                  @SWG\Property( property="all_residual", type="string", example="435", description="全部短信余额"),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="sms_buy_url", type="string", example="http://sms.shopex.cn/?ctl=sms&act=prdsList&source=TlRNek1qRTR8ODgxMzA5MTExOTM4MHwwZTk5ZTQ1NjU1NmU5MjdmZWVjYzA3NjU4YWFiZGMzZHwxNjExODE2NzAy", description="短信购买url"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getSmsBasic(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($companyId);

        $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));

        $data['sms_remainder'] = $smsService->getSmsRemainder();
        $data['sms_buy_url'] = $smsService->getSmsBuyUrl();
        return $this->response->array($data);
    }

    /**
     * @SWG\Definition(
     *     definition="SmsTemplatesDetail",
     *     type="object",
     *     @SWG\Property( property="sms_type", type="string", example="notice", description="短信类型"),
     *     @SWG\Property( property="tmpl_type", type="string", example="trade", description="模板分类"),
     *     @SWG\Property( property="content", type="string", example="您于{{支付时间}}通过微信支付{{支付金额}}元。", description="模板内容"),
     *     @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *     @SWG\Property( property="tmpl_name", type="string", example="trade_wxpay_success", description="模板名称"),
     *     @SWG\Property( property="send_time_desc", type="object",
     *         @SWG\Property( property="tmpl_title", type="string", example="微信支付成功通知", description="模板标题"),
     *         @SWG\Property( property="title", type="string", example="微信支付完成后触发", description="描述"),
     *     ),
     *     @SWG\Property( property="created", type="string", example="1566971869", description=""),
     *
     * )
     */

    /**
     * @SWG\Get(
     *     path="/sms/templates",
     *     summary="获取短信模版列表",
     *     tags={"营销"},
     *     description="获取短信模版列表",
     *     operationId="getSmsTemplateList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="object",
     *                          @SWG\Property( property="trade", type="array",
     *                              @SWG\Items( type="object",
     *                                  ref="#/definitions/SmsTemplatesDetail",
     *                               ),
     *                          ),
     *                          @SWG\Property( property="promotions", type="array",
     *                              @SWG\Items( type="object",
     *                                  ref="#/definitions/SmsTemplatesDetail",
     *                               ),
     *                          ),
     *                          @SWG\Property( property="member", type="array",
     *                              @SWG\Items( type="object",
     *                                  ref="#/definitions/SmsTemplatesDetail",
     *                               ),
     *                          ),
     *                          @SWG\Property( property="registration", type="array",
     *                              @SWG\Items( type="object",
     *                                  ref="#/definitions/SmsTemplatesDetail",
     *                               ),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getSmsTemplateList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        // 数云模式
        if (config('common.oem-shuyun')) {
            $smsService = new SmsService();
        } else {
            $companysService = new CompanysService();
            $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
            $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));
        }
        $data['list'] = $smsService->listsTemplateByCompanyId($companyId);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/sms/template/detail",
     *     summary="获取短信模版详情",
     *     tags={"营销"},
     *     description="获取短信模版详情",
     *     operationId="getSmsTemplateDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tmpl_name", in="query", description="短信模板名称", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="object",
     *                          @SWG\Property( property="trade", type="array",
     *                              @SWG\Items( type="object",
     *                                  ref="#/definitions/SmsTemplatesDetail",
     *                               ),
     *                          ),
     *                          @SWG\Property( property="promotions", type="array",
     *                              @SWG\Items( type="object",
     *                                  ref="#/definitions/SmsTemplatesDetail",
     *                               ),
     *                          ),
     *                          @SWG\Property( property="member", type="array",
     *                              @SWG\Items( type="object",
     *                                  ref="#/definitions/SmsTemplatesDetail",
     *                               ),
     *                          ),
     *                          @SWG\Property( property="registration", type="array",
     *                              @SWG\Items( type="object",
     *                                  ref="#/definitions/SmsTemplatesDetail",
     *                               ),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getSmsTemplateDetail(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('tmpl_name');
        $rules = [
            'tmpl_name' => ['required', trans('PromotionsBundle.sms_template_name_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        // 数云模式
        if (config('common.oem-shuyun')) {
            $smsService = new SmsService();
        } else {
            $companysService = new CompanysService();
            $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
            $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));
        }
        $info = $smsService->getTemplateDetail($companyId, $params['tmpl_name']);
        $info['sign'] = $smsService->getSmsSign($companyId);
        return $this->response->array($info);
    }

    /**
     * @SWG\Patch(
     *     path="/sms/template",
     *     summary="更新短信模版配置",
     *     tags={"营销"},
     *     description="更新短信模版配置",
     *     operationId="updateSmsTemplate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否开启", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="模板名称", required=true, type="string"),
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
    public function updateSmsTemplate(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        // 数云模式
        if (config('common.oem-shuyun')) {
            $smsService = new SmsService();
        } else {
            $companysService = new CompanysService();
            $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
            $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));
        }

        $templateName = $request->input('template_name');
        $params['is_open'] = $request->input('is_open', false);
        if ($request->input('content')) {
            $params['content'] = $request->input('content');
        }
        $smsService->updateTemplate($companyId, $templateName, $params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/sms/sign",
     *     summary="获取短信签名",
     *     tags={"营销"},
     *     description="获取短信签名",
     *     operationId="getSmsSign",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="sign", type="string", description="签名", example="Ecshopx"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getSmsSign(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        // 数云模式
        if (config('common.oem-shuyun')) {
            $smsService = new SmsService();
        } else {
            $companysService = new CompanysService();
            $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
            $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));
        }
        $data['sign'] = $smsService->getSmsSign($companyId);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/sms/sign",
     *     summary="设置短信签名",
     *     tags={"营销"},
     *     description="设置短信签名",
     *     operationId="saveSmsSign",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="formData", description="短信签名内容", required=true, type="string"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function saveSmsSign(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $companysService = new CompanysService();
        // 数云模式
        if (config('common.oem-shuyun')) {
            $smsService = new SmsService();
        } else {
            $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
            $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));
        }
        $sign = $request->input('sign');
        $smsService->saveSmsSign($shopexUid, $companyId, $sign);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/sms/send/test",
     *     summary="发送测试短信",
     *     tags={"营销"},
     *     description="发送测试短信（数云模式）",
     *     operationId="sendSmsTest",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="模板名称", required=true, type="string"),
     *     @SWG\Parameter( name="content", in="query", description="模板内容", required=true, type="string"),
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
    public function sendSmsTest(Request $request)
    {
        $params = $request->all('tmpl_name', 'mobile', 'content');
        $rules = [
            'tmpl_name' => ['required', trans('PromotionsBundle.sms_template_name_required')],
            'mobile' => ['required', trans('PromotionsBundle.test_mobile_required')],
            'content' => ['required', trans('PromotionsBundle.template_content_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');
        $smsService = new SmsService(new ShuyunSmsService($companyId));
        $smsService->sendTest($companyId, $params);

        return $this->response->array(['status' => true]);
    }
}
