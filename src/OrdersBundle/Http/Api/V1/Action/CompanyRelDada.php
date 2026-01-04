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

namespace OrdersBundle\Http\Api\V1\Action;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\CompanyRelDadaService;
use ThirdPartyBundle\Services\DadaCenter\MerchantService;
use ThirdPartyBundle\Services\DadaCenter\ShopService;

class CompanyRelDada extends Controller
{
    /**
     * @SWG\Get(
     *     path="/company/dada/info",
     *     summary="获取商户达达同城配账户信息",
     *     tags={"订单"},
     *     description="获取商户达达同城配账户信息",
     *     operationId="getCompanyRelDada",
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="id", type="string", example="3", description="id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="source_id", type="string", example="1234", description="达达商户ID"),
     *               @SWG\Property(property="enterprise_name", type="string", example="测试公司", description="企业全称"),
     *               @SWG\Property(property="enterprise_address", type="string", example="普天信息软件园", description="企业地址"),
     *               @SWG\Property(property="mobile", type="string", example="18437951111", description="商户手机号"),
     *               @SWG\Property(property="city_name", type="string", example="上海", description="商户城市名称"),
     *               @SWG\Property(property="contact_name", type="string", example="zhangsan", description="联系人姓名"),
     *               @SWG\Property(property="email", type="string", example="123@163.com", description="邮箱"),
     *               @SWG\Property(property="freight_type", type="string", example="0", description="运费承担方"),
     *               @SWG\Property(property="created", type="int", example=1620811993, description="创建时间"),
     *               @SWG\Property(property="updated", type="int", example=1620899215, description="更新时间"),
     *               @SWG\Property(property="status", type="string", example="1", description="开通状态"),
     *               @SWG\Property(property="is_open", type="string", example="0", description="是否开启"),
     *               @SWG\Property(property="city_list", type="string", example="[]", description="城市列表"),
     *               @SWG\Property(property="business_list", type="object", example="[]", description="业务类型"),
     *               ),
     *            ),
     *         ),
     *     ),
     * @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function getCompanyRelDadaInfo()
    {
        // ShopEx EcShopX Core Module
        $company_id = app('auth')->user()->get('company_id');
        $companyRelDadaService = new CompanyRelDadaService();
        $filter = ['company_id' => $company_id];
        $result = $companyRelDadaService->getInfo($filter);
        if (!empty($result)) {
            $result['status'] = $result['status'] == 'true' ? "1" : "0";
            $result['is_open'] = $result['is_open'] == 'true' ? "1" : "0";
            $result['freight_type'] = $result['freight_type'] == 'true' ? "1" : "0";
        }
        try {
            $result['city_list'] = $companyRelDadaService->getCityList($company_id);
        } catch (\Exception $e) {
            //请求达达接口报错强制关闭
            $result['city_list'] = [];
            $result['is_open'] = "0";
            $result['error_message'] = $e->getMessage();
        }
        $shopService = new ShopService();
        $result['business_list'] = $shopService->getBusinessList();
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/company/dada/create",
     *     summary="商户注册",
     *     tags={"订单"},
     *     description="商户注册",
     *     operationId="createCompanyRelDada",
     *     @SWG\Parameter( name="status", in="query", description="开通状态", required=true, type="boolean"),
     *     @SWG\Parameter( name="mobile", in="query", description="商户手机号", required=true, type="string"),
     *     @SWG\Parameter( name="city_name", in="query", description="商户城市名称", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_name", in="query", description="企业全称", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_address", in="query", description="企业地址", required=true, type="string"),
     *     @SWG\Parameter( name="contact_name", in="query", description="联系人姓名", required=true, type="string"),
     *     @SWG\Parameter( name="contact_phone", in="query", description="联系人电话", required=true, type="string"),
     *     @SWG\Parameter( name="email", in="query", description="邮箱", required=true, type="string"),
     *     @SWG\Parameter( name="freight_type", in="query", description="运费承担方", required=true, type="boolean"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否开启", required=true, type="boolean"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="id", type="string", example="3", description="id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="source_id", type="string", example="1234", description="达达商户ID"),
     *               @SWG\Property(property="enterprise_name", type="string", example="测试公司", description="企业全称"),
     *               @SWG\Property(property="enterprise_address", type="string", example="普天信息软件园", description="企业地址"),
     *               @SWG\Property(property="mobile", type="string", example="18437951111", description="商户手机号"),
     *               @SWG\Property(property="city_name", type="string", example="上海", description="商户城市名称"),
     *               @SWG\Property(property="contact_name", type="string", example="zhangsan", description="联系人姓名"),
     *               @SWG\Property(property="email", type="string", example="123@163.com", description="邮箱"),
     *               @SWG\Property(property="freight_type", type="string", example="0", description="运费承担方"),
     *               @SWG\Property(property="created", type="int", example=1620811993, description="创建时间"),
     *               @SWG\Property(property="updated", type="int", example=1620899215, description="更新时间"),
     *               @SWG\Property(property="status", type="string", example="0", description="开通状态"),
     *               @SWG\Property(property="is_open", type="string", example="0", description="是否开启"),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function createCompanyRelDada(Request $request)
    {
        $params = $request->all('status', 'mobile', 'city_name', 'enterprise_name', 'enterprise_address', 'contact_name', 'contact_phone', 'email', 'freight_type', 'is_open', 'source_id');
        $rules = [
            'status' => 'required|boolean',
            'mobile' => 'required|mobile',
            'city_name' => 'required|max:255',
            'enterprise_name' => 'required|max:50',
            'enterprise_address' => 'required|max:100',
            'contact_name' => 'required|max:50',
            'contact_phone' => 'required|mobile',
            'email' => 'required|email',
            'freight_type' => 'required|boolean',
            'is_open' => 'required|boolean',
        ];
        $msg = [
            'status.required' => '开通状态必填',
            'status.boolean' => '开通状态参数类型错误',
            'mobile.required' => '商户手机号必填',
            'mobile.mobile' => '请输入正确的手机号',
            'city_name.required' => '商户城市名称必填',
            'city_name.max' => '商户城市名称最大长度255',
            'enterprise_name.required' => '企业全称必填',
            'enterprise_name.max' => '企业全称最大长度50',
            'enterprise_address.required' => '企业地址必填',
            'enterprise_address.max' => '企业地址最大长度100',
            'contact_name.required' => '联系人姓名必填',
            'contact_name.max' => '联系人姓名最大长度50',
            'contact_phone.required' => '联系人电话必填',
            'contact_phone.mobile' => '请输入正确的联系人电话',
            'email.required' => '邮箱地址必填',
            'email.email' => '邮箱地址格式错误',
            'freight_type.required' => '运费承担方必填',
            'freight_type.boolean' => '运费承担方类型错误',
            'is_open.required' => '是否开启必填',
            'is_open.boolean' => '是否开启参数类型错误',
        ];
        if (!empty($params['status'])) {
            $rules['source_id'] = 'required|max:50';
            $msg['source_id.required'] = '达达商户ID必填';
            $msg['source_id.max'] = '达达商户ID最大长度50';
        }
        $validator = app('validator')->make($params, $rules, $msg);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = current($errorsMsg)[0];
            throw new ResourceException($errmsg);
        }
        $company_id = app('auth')->user()->get('company_id');
        $companyRelDadaService = new CompanyRelDadaService();
        $info = $companyRelDadaService->getInfo(['company_id' => $company_id]);
        if (empty($params['status']) && !empty($params['is_open'])) {
            if (empty($info) || empty($info['source_id']) || ($params['mobile'] != $info['mobile'] && $params['email'] != $info['email'])) {
                $merchantService = new MerchantService();
                $source_id = $merchantService->createMerchant($company_id, $params);
                $params['source_id'] = $source_id;
            }
        }
        if (empty($info)) {
            $params['company_id'] = $company_id;
            $result = $companyRelDadaService->create($params);
        } else {
            $filter = ['company_id' => $company_id];
            $result = $companyRelDadaService->updateOneBy($filter, $params);
        }
        return $this->response->array($result);
    }
}
