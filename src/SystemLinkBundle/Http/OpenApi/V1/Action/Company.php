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

namespace SystemLinkBundle\Http\OpenApi\V1\Action;

use Illuminate\Http\Request;
use SystemLinkBundle\Http\Controllers\Controller as Controller;

use CompanysBundle\Services\OperatorsService;
use CompanysBundle\Services\CompanysService;
use CompanysBundle\Events\CompanyCreateEvent;
use CompanysBundle\Services\AuthService;

class Company extends Controller
{
    /**
     * @SWG\Post(
     *     path="/systemlink/openapi/create",
     *     summary="站点开通",
     *     tags={"company"},
     *     description="站点开通",
     *     operationId="create",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ecapi.site.create", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="eid", in="query", description="企业id", required=true, type="string"),
     *     @SWG\Parameter( name="shopexid", in="query", description="shopexid", required=true, type="string"),
     *     @SWG\Parameter( name="issue_id", in="query", description="工单号", required=true, type="string"),
     *     @SWG\Parameter( name="tel", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Parameter( name="email", in="query", description="email", required=false, type="string"),
     *     @SWG\Parameter( name="days", in="query", description="站点开通天数", required=true, type="integer"),
     *     @SWG\Parameter( name="good_code", in="query", description="商品code", required=true, type="string"),
     *     @SWG\Parameter( name="product_code", in="query", description="基础系统code", required=true, type="string"),
     *     @SWG\Parameter( name="is_try", in="query", description="是否试用", required=true, default="1", type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="开通成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function create(Request $request)
    {
        $params = $request->all();
        $params['days'] = 365 * 20;

        $rules = [
            'eid' => ['required', '企业id必填'],
            'shopexid' => ['required', 'shopexid必填'],
            // 'issue_id' => ['required', '工单号必填'],
            'tel' => ['required', '手机号必填'],
            // 'email'        => ['required', '邮箱必填'],
            'days' => ['required', '站点开通天数必填'],
            // 'good_code' => ['required', '商品code必填'],
            // 'product_code' => ['required', '基础系统code必填'],
            // 'is_try' => ['required', '是否试用必填'],
            'company_name' => ['required', '公司名称必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $operatorService = new OperatorsService();
        $operator = $operatorService->getInfo(['mobile' => $params['tel'], 'operator_type' => 'admin']);
        if ($operator) {
            $this->api_response('fail', '账号已开通');
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $operatorData = [
                'eid' => $params['eid'],
                'mobile' => $params['tel'],
                'passport_uid' => $params['shopexid'],
                'password' => uniqid(),
                'operator_type' => 'admin',
            ];
            // 创建商家超级管理员
            $operator = $operatorService->createOperator($operatorData);

            $companyData = [
                'eid' => $params['eid'],
                'passport_uid' => $params['shopexid'],
                'company_admin_operator_id' => $operator['operator_id'],
                'company_name' => $params['company_name'],
                'is_disabled' => 0,
                'third_params' => [],
                'menu_type' => 2,
            ];
            $companyService = new CompanysService();
            $company = $companyService->create($companyData);
            $operatorService->updateOneBy(['operator_id' => $operator['operator_id']], ['company_id' => $company['company_id']]);

            $licenseData = [
                'available_days' => $params['days'],
                'issue_id' => $params['issue_id'] ?? 'shuyun_'.$params['shopexid'],
                'company_id' => $company['company_id'],
                'eid' => $params['eid'],
                'passport_uid' => $params['shopexid'],
                'goods_code' => $params['good_code'] ?? '',
                'product_code' => $params['product_code'] ?? '',
                'source' => 'purchased',
            ];
            app('authorization')->createOnlineCompanyLicense($licenseData);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            $this->api_response('fail', $e->getMessage());
        }
        app('log')->info('账号开通完成');
        //邮件短信通知
        $activeAt = time();
        $expiredAt = $activeAt + $params['days'] * 86400;
        $eventData = [
            'issue_id' => $params['issue_id'] ?? 'shuyun_'.$params['shopexid'],
            'company_id' => $company['company_id'],
            'mobile' => $params['tel'],
            'email' => $params['email'] ?? '',
            'active_at' => $activeAt,
            'expired_at' => $expiredAt,
            'available_days' => $params['days'],
        ];
        app('log')->debug('CompanyCreateEventData: '.var_export($eventData, 1));
        event(new CompanyCreateEvent($eventData));

        $this->api_response('true', '开通成功');
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/openapi/close",
     *     summary="站点关闭",
     *     tags={"company"},
     *     description="站点关闭",
     *     operationId="close",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ecapi.site.close", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="issue_id", in="query", description="工单号", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function close(Request $request)
    {
        $params = $request->all();

        $rules = [
            'issue_id' => ['required', '工单号必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $licenseData = [
            'expired_at' => time(),
            'issue_id' => $params['issue_id'],
        ];

        try {
            app('authorization')->updateOnlineCompanyLicense($licenseData);
        } catch (\Exception $e) {
            $this->api_response('fail', $e->getMessage());
        }

        $this->api_response('true', '操作成功');
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/openapi/renew",
     *     summary="站点续费",
     *     tags={"company"},
     *     description="站点续费",
     *     operationId="renew",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ecapi.site.renew", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="issue_id", in="query", description="工单号", required=true, type="string"),
     *     @SWG\Parameter( name="cycle_end", in="query", description="服务结束时间", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="{status:true,url:https://openapi.shopex.cn}", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function renew(Request $request)
    {
        $params = $request->all();

        $rules = [
            'issue_id' => ['required', '工单号必填'],
            'cycle_end' => ['required', '服务结束时间必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $licenseData = [
            'expired_at' => $params['cycle_end'],
            'issue_id' => $params['issue_id'],
        ];

        try {
            app('authorization')->updateOnlineCompanyLicense($licenseData);
        } catch (\Exception $e) {
            $this->api_response('fail', $e->getMessage());
        }

        $this->api_response('true', '操作成功');
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/openapi/checkisopen",
     *     summary="检查站点是否开通",
     *     tags={"company"},
     *     description="检查站点是否开通",
     *     operationId="checkisopen",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ecapi.site.checkopen", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="tel", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function checkisopen(Request $request)
    {
        $params = $request->all();
        $rules = [
            // 'eid'          => ['required', '企业id必填'],
            // 'shopexid'     => ['required', 'shopexid必填'],
            // 'issue_id'     => ['required', '工单号必填'],
            'tel' => ['required', '手机号必填'],
            // 'days'         => ['required', '站点开通天数必填'],
            // 'good_code'    => ['required', '商品code必填'],
            // 'product_code' => ['required', '基础系统code必填'],
            // 'is_try'       => ['required', '是否试用必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }
        $data = ['status' => false];
        $operatorService = new OperatorsService();
        $operator = $operatorService->getInfo(['mobile' => $params['tel'], 'operator_type' => 'admin']);
        if ($operator) {
            $authService = new AuthService();
            $data['url'] = $authService->getOuthorizeurl();
            $data['status'] = true;
        }
        $this->api_response('true', '', $data);
    }
}
