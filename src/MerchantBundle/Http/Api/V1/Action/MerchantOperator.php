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

namespace MerchantBundle\Http\Api\V1\Action;

use CompanysBundle\Services\OperatorsService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;
use MerchantBundle\Jobs\MerchantResetPasswordNotice;
use MerchantBundle\Services\MerchantService;

class MerchantOperator extends Controller
{
    /**
     * @SWG\Get(
     *     path="/merchant/operator",
     *     summary="商户账号列表",
     *     tags={"商户"},
     *     description="商户账号列表",
     *     operationId="getOperatorList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="params.page", in="query", description="当前页数", required=true, type="string"),
     *     @SWG\Parameter( name="params.page_size", in="query", description="每页数量", required=true, type="string"),
     *     @SWG\Parameter( name="params.mobile", in="query", description="手机号", required=false, type="string"),
     *     @SWG\Parameter( name="params.merchant_name", in="query", description="商户名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="datapass_block", type="boolean", example="1", description="是否脱敏  1:是 0:否 "),
     *                  @SWG\Property( property="count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="operator_id", type="string", example="1", description="operator_id"),
     *                          @SWG\Property( property="mobile", type="string", example="18412345678", description="手机号"),
     *                          @SWG\Property( property="password", type="string", example="123456", description="密码"),
     *                          @SWG\Property( property="merchant_name", type="string", example="商派", description="商户名称"),
     *                          @SWG\Property( property="settled_type", type="string", example="enterprise:企业;soletrader:个体户", description="商户入驻类型"),
     *                          @SWG\Property( property="is_merchant_main", type="string", example="1", description="是否超级管理员，1是 0否"),
     *                      ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getOperatorList(Request $request)
    {
        // 参数格式适配 前端finder组件
        $params = $request->input('params');
        if (is_string($params)) {
            $params = json_decode($params, true);
        }
        if (is_null($params)) {
            $params = $request->all();
        }
        $rules = [
            'page' => ['required|integer|min:1', trans('MerchantBundle.current_page_positive_integer')],
            'pageSize' => ['required|integer|min:1|max:50', trans('MerchantBundle.page_size_1_to_50_integer')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $merchantService = new MerchantService();
        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        if ($operatorType == 'admin') {
            $filter['is_merchant_main'] = 1;
        }
        if (!empty($params['mobile'])) {
            $filter['mobile'] = $params['mobile'];
        }
        if (!empty($params['merchant_name'])) {
            $filter['merchant_name|like'] = $params['merchant_name'];
        }
        $filter['operator_type'] = 'merchant';
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result = $merchantService->getOperatorLists($filter, ["created" => "DESC"], $params['pageSize'], $params['page']);
        $result['datapass_block'] = $datapassBlock;
        foreach ($result['list'] as &$value) {
            if ($datapassBlock) {
                $value['mobile'] = data_masking('mobile', (string)$value['mobile']);
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/merchant/operator",
     *     summary="修改账户密码",
     *     tags={"商户"},
     *     description="修改账户密码",
     *     operationId="updateOperatorAccount",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="operator_id", in="path", description="operator_id", required=true, type="string"),
     *     @SWG\Parameter( name="password", in="path", description="password", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function updateOperatorAccount(Request $request)
    {
        $params = $request->all('operator_id', 'password');
        $rules = [
            'operator_id' => ['required', trans('MerchantBundle.operator_id_required')],
            'password' => ['required|min:6|max:16', trans('MerchantBundle.password_6_to_16_digits')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        if (!preg_match('/^[_0-9a-z]{6,16}$/i', $params['password'])) {
            throw new ResourceException(trans('MerchantBundle.password_format_incorrect'));
        }
        $filter['operator_id'] = $params['operator_id'];
        $data['password'] = password_hash($params['password'], PASSWORD_DEFAULT);
        $operatorsService = new OperatorsService();
        $operatorsService->updateOneBy($filter, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/merchant/operator/{id}",
     *     summary="重制商户密码",
     *     tags={"商户"},
     *     description="重制商户密码",
     *     operationId="resetOperatorAccount",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="operatoe_id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function resetOperatorAccount($id)
    {
        $companyId = app('auth')->user()->get('company_id');
        $password = rand(100000, 999999);
        $operatorsService = new OperatorsService();
        $filter['operator_id'] = $id;
        $filter['company_id'] = $companyId;
        $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        $operatorInfo = $operatorsService->getInfo($filter);
        if (empty($operatorInfo)) {
            throw new ResourceException(trans('MerchantBundle.account_not_exist'));
        }
        $operatorsService->updateOneBy($filter, $data);
        $msgData['password'] = $password;
        $msgData['company_id'] = $companyId;
        $msgData['mobile'] = $operatorInfo['mobile'];
        $job = (new MerchantResetPasswordNotice($msgData))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return $this->response->array(['status' => true]);
    }
}
