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

namespace AdaPayBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\BankCodeService;
use AdaPayBundle\Services\OpenAccountService;
use AdaPayBundle\Services\RegionService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use AdaPayBundle\Services\AlipayIndustryCategoryService;

class OpenAccount extends Controller
{
    /**
     * @SWG\Post(
     *     path="/adapay/merchant_entry/create",
     *     summary="创建开户进件申请",
     *     tags={"OpenAccount"},
     *     description="创建开户进件申请",
     *     operationId="merchantEntryCreate",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="usr_phone", description="注册手机号" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="cont_name", description="联系人姓名" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="cont_phone", description="联系人手机号码" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="customer_email", description="电子邮箱" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="mer_name", description="商户名" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="mer_short_name", description="商户名简称" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="reg_addr", description="注册地址" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="cust_addr", description="经营地址" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="cust_tel", description="商户电话" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="legal_name", description="法人/负责人 姓名" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="legal_idno", description="法人/负责人证件号码" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="legal_mp", description="法人/负责人手机号" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="legal_start_cert_id_expires", description="法人/负责人身份证有效期（始）格式 YYYYMMDD" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="legal_id_expires", description="法人/负责人身份证有效期（至）格式 YYYYMMDD" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="card_id_mask", description="结算银行卡号" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="bank_code", description="结算银行卡所属银行code" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="card_name", description="结算银行卡开户姓名" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="bank_acct_type", description="结算银行账户类型 1 : 对公， 2 : 对私。小微只能是对私" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="prov_code", description="结算银行卡省份编码" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="area_code", description="结算银行卡地区" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="entry_mer_type", description="商户类型 1-企业；2-小微" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="license_code", description="营业执照编码 企业时必填" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="mer_start_valid_date", description="商户有效日期（始） 企业时必填  格式 YYYYMMDD" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="mer_valid_date", description="商户有效日期（至） 企业时必填 格式 YYYYMMDD（若为长期有效，固定为“20991231”）" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function merchantEntryCreate(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'usr_phone' => ['required|size:11', '注册手机号格式不正确'],
            'cont_name' => ['required', '联系人姓名必填'],
            'cont_phone' => ['required|size:11', '联系人手机号码格式不正确'],
            'customer_email' => ['required|email', '电子邮箱格式不正确'],
            'mer_name' => ['required', '商户名必填'],
            'mer_short_name' => ['required', '商户名简称必填'],
            'reg_addr' => ['required', '注册地址必填'],
            'cust_addr' => ['required', '经营地址必填'],
            'cust_tel' => ['required', '商户电话必填'],
            'legal_name' => ['required', '法人/负责人 姓名 必填'],
//            'legal_type' => ['required', '法人/负责人证件类型必填'],
            'legal_idno' => ['required|idcard', '法人/负责人证件号码格式不正确'],
            'legal_mp' => ['required|size:11', '法人/负责人手机号格式不正确'],
            'legal_start_cert_id_expires' => ['required', '法人/负责人身份证有效期（始）必填'],
            'legal_id_expires' => ['required', '法人/负责人身份证有效期（至）必填'],
            'card_id_mask' => ['required', '结算银行卡号必填'],
            'bank_code' => ['required', '结算银行卡所属银行code必填'],
            'card_name' => ['required', '结算银行卡开户姓名必填'],
            'bank_acct_type' => ['required', '结算银行账户类型必填'],
            'prov_code' => ['required', '结算银行卡省份编码必填'],
            'area_code' => ['required', '结算银行卡地区编码必填'],
            'is_sms' => ['required', '是否短信提醒必传'],
//            'rsa_public_key' => ['required', '商户rsa 公钥必填'],
//            'entry_mer_type' => ['required', '商户类型必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['entry_mer_type'] = '1';
        if ($params['entry_mer_type'] == '1' && !($params['license_code'] ?? [])) {
            throw new ResourceException('营业执照编码 企业时必填');
        }

        if ($params['entry_mer_type'] == '1' && (!($params['mer_start_valid_date'] ?? []) || !($params['mer_valid_date'] ?? []))) {
            throw new ResourceException('商户有效日期 企业时必填');
        }

        if ($params['entry_mer_type'] == '2' && $params['bank_acct_type'] == '1') {
            throw new ResourceException('结算银行账户类型 小微只能是对私');
        }
        $openAccountService = new OpenAccountService();
        $result = $openAccountService->merchantEntryCreateService($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/merchant_entry/info",
     *     summary="开户进件详情",
     *     tags={"OpenAccount"},
     *     description="开户进件详情",
     *     operationId="merchantEntryInfo",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="1"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="request_id", type="string", example="merchant_entry_1_1629427090"),
     *                  @SWG\Property( property="usr_phone", type="string", example="18360189065"),
     *                  @SWG\Property( property="cont_name", type="string", example="啊啊啊"),
     *                  @SWG\Property( property="cont_phone", type="string", example="18360189065"),
     *                  @SWG\Property( property="customer_email", type="string", example="123@qq.com"),
     *                  @SWG\Property( property="mer_name", type="string", example="111"),
     *                  @SWG\Property( property="mer_short_name", type="string", example="11"),
     *                  @SWG\Property( property="license_code", type="string", example="111"),
     *                  @SWG\Property( property="reg_addr", type="string", example="上海"),
     *                  @SWG\Property( property="cust_addr", type="string", example="2222"),
     *                  @SWG\Property( property="cust_tel", type="string", example="123-123456"),
     *                  @SWG\Property( property="mer_start_valid_date", type="string", example="123"),
     *                  @SWG\Property( property="mer_valid_date", type="string", example="3221"),
     *                  @SWG\Property( property="legal_name", type="string", example="333"),
     *                  @SWG\Property( property="legal_type", type="string", example="0"),
     *                  @SWG\Property( property="legal_idno", type="string", example="111111111111111111"),
     *                  @SWG\Property( property="legal_mp", type="string", example="11111111111"),
     *                  @SWG\Property( property="legal_start_cert_id_expires", type="string", example="1234"),
     *                  @SWG\Property( property="legal_id_expires", type="string", example="12222"),
     *                  @SWG\Property( property="card_id_mask", type="string", example="147576667"),
     *                  @SWG\Property( property="bank_code", type="string", example="1025"),
     *                  @SWG\Property( property="bank_name", type="string", example=""),
     *                  @SWG\Property( property="prov_name", type="string", example=""),
     *                  @SWG\Property( property="area_name", type="string", example=""),
     *                  @SWG\Property( property="card_name", type="string", example="114"),
     *                  @SWG\Property( property="bank_acct_type", type="string", example="1"),
     *                  @SWG\Property( property="prov_code", type="string", example="2222"),
     *                  @SWG\Property( property="area_code", type="string", example="3333"),
     *                  @SWG\Property( property="rsa_public_key", type="string", example=""),
     *                  @SWG\Property( property="entry_mer_type", type="string", example="1"),
     *                  @SWG\Property( property="test_api_key", type="string", example="null"),
     *                  @SWG\Property( property="live_api_key", type="string", example="null"),
     *                  @SWG\Property( property="login_pwd", type="string", example="null"),
     *                  @SWG\Property( property="app_id_list", type="string", example="null"),
     *                  @SWG\Property( property="sign_view_url", type="string", example="null"),
     *                  @SWG\Property( property="status", type="string", example="pending"),
     *                  @SWG\Property( property="error_msg", type="string", example="null"),
     *                  @SWG\Property( property="create_time", type="string", example="1629427090"),
     *                  @SWG\Property( property="update_time", type="string", example="1629427090"),
     *          ),
     *     )),
     * )
     */
    public function merchantEntryInfo()
    {
        $companyId = app('auth')->user()->get('company_id');
        $openAccountService = new OpenAccountService();
        $result = $openAccountService->merchantEntryInfoService($companyId);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/merchant_resident/create",
     *     summary="商户入驻申请",
     *     tags={"OpenAccount"},
     *     description="商户入驻申请",
     *     operationId="merchantResidentCreate",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="fee_type", description="费率类型：01-标准费率线上，02-标准费率线下" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="wx_category", description="微信经营类目" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="model_type", description="入驻模式：1-服务商模式" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="mer_type", description="商户种类，1-政府机构,2-国营企业,3-私营企业,4-外资企业,5-个体工商户,7-事业单位,8-小微" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="province_code", description="省份编码" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="city_code", description="城市编码" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="district_code", description="区县编码" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="add_value_list", description="支付渠道配置信息"),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="adapay_fee_mode", description="手续费扣除方式" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function merchantResidentCreate(Request $request)
    {
        // 53686f704578
        $companyId = app('auth')->user()->get('company_id');

        $params = $request->all();
        $rules = [
            'fee_type' => ['required', '费率类型必填'],
            'wx_category' => ['required', '微信经营类目必填'],
            'model_type' => ['required', '入驻模式必填'],
            'mer_type' => ['required', '商户种类必填'],
            'province_code' => ['required', '省份必填'],
            'city_code' => ['required', '城市必填'],
            'district_code' => ['required', '区县必填'],
            'add_value_list' => ['required', '支付渠道配置信息必填'],
            'adapay_fee_mode' => ['required', '手续费扣除方式必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if (!is_array($params['add_value_list'])) {
            $params['add_value_list'] = json_decode($params['add_value_list'], true);
        }
        // 支付渠道配置信息检查
        foreach ($params['add_value_list'] as $payChannel => $setting) {
            switch ($payChannel) {
                case 'wx_lite':
                    if (!($setting['appid'] ?? '')) {
                        throw new ResourceException('小程序appi必填');
                    }
                    $params['add_value_list']['wx_pub'] = ['appid' => $setting['appid']];
                    break;
                case 'wx_pub':
                    if (!($setting['appid'] ?? '')) {
                        throw new ResourceException('公众号appi必填');
                    }
                    if (!($setting['path'] ?? '')) {
                        throw new ResourceException('授权目录必填');
                    }
                    $params['add_value_list']['wx_pub'] = ['appid' => $setting['appid'], 'path' => $setting['path']];
                    break;
                case 'alipay':
                case 'alipay_wap':
                case 'alipay_qr':
                    $params['add_value_list'][$payChannel] = '';
                    break;
                default:
                    throw new ResourceException('未支持支付渠道');
            }
        }

        $openAccountService = new OpenAccountService();
        $result = $openAccountService->merchantResidentCreateService($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/merchant_resident/info",
     *     summary="商户入驻详情",
     *     tags={"OpenAccount"},
     *     description="商户入驻详情",
     *     operationId="merchantResidentInfo",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="1"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="request_id", type="string", example="merchant_resident_1_1629430783"),
     *                  @SWG\Property( property="sub_api_key", type="string", example="wqeqweqwe"),
     *                  @SWG\Property( property="fee_type", type="string", example="01"),
     *                  @SWG\Property( property="app_id", type="string", example="app_2fc6b8a4-xxxx-xxxx-xxxx-34eeee33bbbbb"),
     *                  @SWG\Property( property="wx_category", type="string", example="4555"),
     *                  @SWG\Property( property="wx_category_name", type="string", example="4555"),
     *                  @SWG\Property( property="alipay_category", type="string", example="null"),
     *                  @SWG\Property( property="cls_id", type="string", example="null"),
     *                  @SWG\Property( property="model_type", type="string", example="1"),
     *                  @SWG\Property( property="mer_type", type="string", example="1"),
     *                  @SWG\Property( property="province_code", type="string", example="123146"),
     *                  @SWG\Property( property="city_code", type="string", example="114566"),
     *                  @SWG\Property( property="district_code", type="string", example="789654"),
     *                  @SWG\Property( property="province_name", type="string", example="123146"),
     *                  @SWG\Property( property="city_name", type="string", example="114566"),
     *                  @SWG\Property( property="district_name", type="string", example="789654"),
     *                  @SWG\Property( property="add_value_list", type="string", example=""),
     *                  @SWG\Property( property="status", type="string", example="pending"),
     *                  @SWG\Property( property="alipay_stat", type="string", example="null"),
     *                  @SWG\Property( property="alipay_stat_msg", type="string", example="null"),
     *                  @SWG\Property( property="wx_stat", type="string", example="null"),
     *                  @SWG\Property( property="wx_stat_msg", type="string", example="null"),
     *                  @SWG\Property( property="create_time", type="string", example="1629430783"),
     *                  @SWG\Property( property="update_time", type="string", example="1629430783"),
     *          ),
     *     )),
     * )
     */
    public function merchantResidentInfo()
    {
        $companyId = app('auth')->user()->get('company_id');
        $openAccountService = new OpenAccountService();
        $result = $openAccountService->merchantResidentInfoservice($companyId);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/bank/list",
     *     summary="获取结算银行列表",
     *     tags={"OpenAccount"},
     *     description="获取结算银行列表",
     *     operationId="getBanksLists",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="bank_name", description="银行名称" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page", description="页码" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page_size", description="页数" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="1"),
     *                  @SWG\Property( property="bank_name", type="string", example="长安银行股份有限公司"),
     *                  @SWG\Property( property="bank_code", type="string", example="31379104"),
     *               ),
     *          ),
     *     )),
     * )
     */
    public function getBanksLists(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);

        $filter = [];
        if ($request->input('bank_name', '')) {
            $filter['bank_name|contains'] = $request->input('bank_name');
        }
        $openAccountService = new BankCodeService();
        $result = $openAccountService->getLists($filter, '*', $page, $pageSize);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/regions/list",
     *     summary="获取省市列(四位码)",
     *     tags={"OpenAccount"},
     *     description="获取省市列(四位码)",
     *     operationId="getRegionsLists",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="pid", description="上级区域ID" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="1"),
     *                  @SWG\Property( property="bank_name", type="string", example="长安银行股份有限公司"),
     *                  @SWG\Property( property="bank_code", type="string", example="31379104"),
     *               ),
     *          ),
     *     )),
     * )
     */
    public function getRegionsLists(Request $request)
    {
        $result = [];
        $pid = $request->input('pid', 0);
        $filter = [
            'pid' => $pid,
        ];
        $regionService = new RegionService();
        $rs = $regionService->getLists($filter);
        $result = $rs;

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/regions_third/list",
     *     summary="获取省市区列表(六位码或九位码)",
     *     tags={"OpenAccount"},
     *     description="获取省市区列表(六位码或九位码)",
     *     operationId="getRegionsThirdLists",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="pid", description="上级区域ID" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="3"),
     *                  @SWG\Property( property="area_name", type="string", example="嘉定区"),
     *                  @SWG\Property( property="pid", type="string", example="2"),
     *                  @SWG\Property( property="area_code", type="string", example="310114"),
     *               ),
     *          ),
     *     )),
     * )
     */
    public function getRegionsThirdLists(Request $request)
    {
        $result = [];
        $pid = $request->input('pid', 0);
        $filter = [
            'pid' => $pid,
        ];
        $regionService = new RegionService();
        $result = $regionService->getRegionsThirdListsService($pid);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/wx_business_cat/list",
     *     summary="获取微信经营类目",
     *     tags={"OpenAccount"},
     *     description="获取微信经营类目",
     *     operationId="getWxBusinessCatList",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="fee_type", description="费率类型：01-标准费率线上，02-标准费率线下" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="merchant_type_name", description="商户种类名称" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="10"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1"),
     *                          @SWG\Property( property="fee_type", type="string", example="01"),
     *                          @SWG\Property( property="fee_type_name", type="string", example="标准费率线上"),
     *                          @SWG\Property( property="merchant_type_name", type="string", example="企业"),
     *                          @SWG\Property( property="business_category_id", type="string", example="756"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function getWxBusinessCatList(Request $request)
    {
        $params = $request->all('fee_type', 'merchant_type_name');
//        $page = $request->input('page', 1);
//        $pageSize = $request->input('page_size', 20);
        $openAccountService = new OpenAccountService();
        $result = $openAccountService->getWxBusinessCatListService($params);

        return $this->response->array($result);
    }

        /**
     * @SWG\Get(
     *     path="/adapay/alipay_industry_cat/list",
     *     summary="获取支付宝行业类目",
     *     tags={"OpenAccount"},
     *     description="获取支付宝行业类目",
     *     operationId="getAlipayIndustryCatList",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object"),
     *     )),
     * )
     */
    public function getAlipayIndustryCatList(Request $request)
    {
        $categoryService = new AlipayIndustryCategoryService();
        $list = $categoryService->getLists([], '*');
        $result = $categoryService->getTree($list);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/license/upload",
     *     summary="上传商户证照",
     *     tags={"OpenAccount"},
     *     description="上传商户证照",
     *     operationId="uploadLicense",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="file", description="图片" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="file_type", description="图片类型，01：三证合一码，02：法人/小微负责人身份证正面，03：法人/小微负责人身份证反面，04：门店，05：开户许可证/小微负责人银行卡正面照，06：股东身份证正面，07：股东身份证反面，08：结算账号开户证明，09：网站截图，10：行业资质文件，11：icp备案许可证明或者许可证编码，12：租赁合同，13：交易测试记录，14：业务场景证明材料" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function uploadLicense(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('file_type');
        $file = $request->file('file');

        $fileName = ($file->getClientOriginalName());
        $fileDir = 'adapay/'.time().rand(100000, 999999).$fileName;

        $filesystem = app('filesystem')->disk('import-file');
        $filesystem->put($fileDir, fopen($file->getRealPath(), 'r'));
        $params['file_url'] = $filesystem->privateDownloadUrl($fileDir);
        $params['file_dir'] = $fileDir;

        $rules = [
            'file_url' => ['required', '图片url必传'],
            'file_type' => ['required', '证照类型必传'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $openAccountService = new OpenAccountService();
        $result = $openAccountService->uploadLicenseService($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/license_submit/create",
     *     summary="提交商户证照",
     *     tags={"OpenAccount"},
     *     description="提交商户证照",
     *     operationId="submitLicense",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="legal_certId_front_url", description="法人身份证正面" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="legal_cert_id_back_url", description="法人身份证反面" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="account_opening_permit_url", description="开户许可证" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="social_credit_code_url", description="三证合一码" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="business_add", description="商户的业务网址或者商城地址 若入驻的费率类型为线上时，该字段必填" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="store_url", description="门店 入驻的费率类型为线下时，该字段必填" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="transaction_test_record_url", description="商户在业务网址或商城地址上测试的交易记录截图" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="web_pic_url", description="网站截图" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="lease_contract_url", description="租赁合同" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="settle_account_certificate_url", description="结算账号开户证明图片" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="buss_support_materials_url", description="业务场景证明材料" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="icp_registration_license_url", description="icp备案许可证明或者许可证编码" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="industry_qualify_doc_type", description="行业资质文件类型：1游戏类，2直播类，3小说图书类，4其他" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="industry_qualify_doc_license_url", description="行业资质文件" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="cert_back_image_url", description="股东身份证照片反面" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="cert_front_image_url", description="股东身份证照片正面" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="cert_id", description="股东身份证号" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="cert_name", description="股东身份证姓名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function submitLicense(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'legal_certId_front_url' => ['required', '法人身份证正面必传'],
            'legal_cert_id_back_url' => ['required', '法人身份证反面必传'],
            'account_opening_permit_url' => ['required', '开户许可证必传'],
            'is_sms' => ['required', '是否短信提醒必传'],
        ];

        if (isset($params['cert_id']) && $params['cert_id']) {
            $rules['cert_id'] = ['idcard', '股东身份证号格式错误'];
        }

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $openAccountService = new OpenAccountService();
        $result = $openAccountService->submitLicenseService($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/license_submit/info",
     *     summary="商户证照详情",
     *     tags={"OpenAccount"},
     *     description="商户证照详情",
     *     operationId="submitLicenseInfo",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="1"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="sub_api_key", type="string", example="wqeqweqwe"),
     *                  @SWG\Property( property="social_credit_code_id", type="string", example="1"),
     *                  @SWG\Property( property="legal_certId_front_id", type="string", example="11"),
     *                  @SWG\Property( property="legal_cert_id_back_id", type="string", example="55"),
     *                  @SWG\Property( property="account_opening_permit_id", type="string", example="22"),
     *                  @SWG\Property( property="business_add", type="string", example="http"),
     *                  @SWG\Property( property="store_id", type="string", example=""),
     *                  @SWG\Property( property="transaction_test_record_id", type="string", example=""),
     *                  @SWG\Property( property="web_pic_id", type="string", example=""),
     *                  @SWG\Property( property="lease_contract_id", type="string", example=""),
     *                  @SWG\Property( property="settle_account_certificate_id", type="string", example=""),
     *                  @SWG\Property( property="buss_support_materials_id", type="string", example=""),
     *                  @SWG\Property( property="icp_registration_license_id", type="string", example=""),
     *                  @SWG\Property( property="industry_qualify_doc_type", type="string", example=""),
     *                  @SWG\Property( property="industry_qualify_doc_license_id", type="string", example=""),
     *                  @SWG\Property( property="shareholder_info_list", type="string", example=""),
     *                  @SWG\Property( property="audit_status", type="string", example="I"),
     *                  @SWG\Property( property="audit_desc", type="string", example="null"),
     *                  @SWG\Property( property="create_time", type="string", example="1629434947"),
     *                  @SWG\Property( property="update_time", type="string", example="1629434947"),
     *          ),
     *     )),
     * )
     */
    public function submitLicenseInfo()
    {
        $companyId = app('auth')->user()->get('company_id');
        $openAccountService = new OpenAccountService();
        $result = $openAccountService->submitLicenseInfoService($companyId);

        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/adapay/open_account/step",
     *     summary="商户开户步骤",
     *     tags={"OpenAccount"},
     *     description="商户开户步骤",
     *     operationId="openAccountStep",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="step", type="string", example="1"),
     *                  @SWG\Property( property="info", type="object",
     *                          @SWG\Property( property="MerchantEntry", type="object",
     *                                  @SWG\Property( property="id", type="string", example="1", description="id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="request_id", type="string", example="merchant_entry_1_1629427090", description="请求id"),
     *                                  @SWG\Property( property="usr_phone", type="string", example="18360189065", description="注册手机号"),
     *                                  @SWG\Property( property="cont_name", type="string", example="啊啊啊", description="注册手机号"),
     *                                  @SWG\Property( property="cont_phone", type="string", example="18360189065", description="联系人手机号码"),
     *                                  @SWG\Property( property="customer_email", type="string", example="123@qq.com", description="电子邮箱"),
     *                                  @SWG\Property( property="mer_name", type="string", example="111", description="商户名"),
     *                                  @SWG\Property( property="mer_short_name", type="string", example="11", description="商户名简称"),
     *                                  @SWG\Property( property="license_code", type="string", example="111", description="营业执照编码"),
     *                                  @SWG\Property( property="reg_addr", type="string", example="上海", description="注册地址"),
     *                                  @SWG\Property( property="cust_addr", type="string", example="2222", description="经营地址"),
     *                                  @SWG\Property( property="cust_tel", type="string", example="123-123456", description="商户电话"),
     *                                  @SWG\Property( property="mer_start_valid_date", type="string", example="123", description="商户有效日期（始），格式 YYYYMMDD"),
     *                                  @SWG\Property( property="mer_valid_date", type="string", example="3221", description="商户有效日期（至），格式 YYYYMMDD"),
     *                                  @SWG\Property( property="legal_name", type="string", example="333", description="法人/负责人 姓名"),
     *                                  @SWG\Property( property="legal_type", type="string", example="0", description="法人/负责人证件类型，0-身份证"),
     *                                  @SWG\Property( property="legal_idno", type="string", example="111111111111111111", description="法人/负责人证件号码"),
     *                                  @SWG\Property( property="legal_mp", type="string", example="11111111111", description="法人/负责人手机号"),
     *                                  @SWG\Property( property="legal_start_cert_id_expires", type="string", example="1234", description="法人/负责人身份证有效期（始），格式 YYYYMMDD"),
     *                                  @SWG\Property( property="legal_id_expires", type="string", example="12222", description="法人/负责人身份证有效期（至），格式 YYYYMMDD"),
     *                                  @SWG\Property( property="card_id_mask", type="string", example="147576667", description="结算银行卡号"),
     *                                  @SWG\Property( property="bank_code", type="string", example="1025", description="结算银行卡所属银行code"),
     *                                  @SWG\Property( property="card_name", type="string", example="114", description="结算银行卡开户姓名"),
     *                                  @SWG\Property( property="bank_acct_type", type="string", example="1", description="结算银行账户类型，1 : 对公， 2 : 对私。小微只能是对私"),
     *                                  @SWG\Property( property="prov_code", type="string", example="2222", description="结算银行卡省份编码"),
     *                                  @SWG\Property( property="area_code", type="string", example="3333", description="结算银行卡地区编码"),
     *                                  @SWG\Property( property="bank_name", type="string", example="", description="银行名称"),
     *                                  @SWG\Property( property="prov_name", type="string", example="", description="省份名称"),
     *                                  @SWG\Property( property="area_name", type="string", example="", description="地区名称"),
     *                                  @SWG\Property( property="rsa_public_key", type="string", example="", description="商户rsa 公钥"),
     *                                  @SWG\Property( property="entry_mer_type", type="string", example="1", description="商户类型：1-企业；2-小微"),
     *                                  @SWG\Property( property="test_api_key", type="string", example="null", description="测试API Key"),
     *                                  @SWG\Property( property="live_api_key", type="string", example="wqeqweqwe", description="生产API"),
     *                                  @SWG\Property( property="login_pwd", type="string", example="null", description="初始密码"),
     *                                  @SWG\Property( property="app_id_list", type="string", example="", description="应用ID列表"),
     *                                  @SWG\Property( property="sign_view_url", type="string", example="null", description="合同查看地址"),
     *                                  @SWG\Property( property="status", type="string", example="pending", description="接口调用状态，succeeded - 成功 failed - 失败 pending - 处理中"),
     *                                  @SWG\Property( property="error_msg", type="string", example="null", description="错误描述"),
     *                                  @SWG\Property( property="create_time", type="string", example="1629427090", description="创建时间"),
     *                                  @SWG\Property( property="update_time", type="string", example="1629427090", description="更新时间"),
     *                          ),
     *                          @SWG\Property( property="MerchantResident", type="object",
     *                                  @SWG\Property( property="id", type="string", example="1", description="id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="request_id", type="string", example="merchant_resident_1_1629430783", description="请求ID"),
     *                                  @SWG\Property( property="sub_api_key", type="string", example="wqeqweqwe", description="商户开户进件返回的API Key"),
     *                                  @SWG\Property( property="fee_type", type="string", example="01", description="费率类型：01-标准费率线上，02-标准费率线下"),
     *                                  @SWG\Property( property="app_id", type="string", example="app_2fc6b8a4-xxxx-xxxx-xxxx-34eeee33bbbbb", description="商户开户进件返回的应用ID"),
     *                                  @SWG\Property( property="wx_category", type="string", example="4555", description="微信经营类目"),
     *                                  @SWG\Property( property="wx_category_name", type="string", example="4555", description="支付宝经营类目"),
     *                                  @SWG\Property( property="alipay_category", type="string", example="null", description="行业分类"),
     *                                  @SWG\Property( property="cls_id", type="string", example="null", description="行业分类"),
     *                                  @SWG\Property( property="model_type", type="string", example="1", description="入驻模式：1-服务商模式"),
     *                                  @SWG\Property( property="mer_type", type="string", example="1", description="商户种类，1-政府机构,2-国营企业,3-私营企业,4-外资企业,5-个体工商户,7-事业单位,8-小微"),
     *                                  @SWG\Property( property="province_code", type="string", example="123146", description="省份编码"),
     *                                  @SWG\Property( property="city_code", type="string", example="114566", description="城市编码"),
     *                                  @SWG\Property( property="province_name", type="string", example="123146", description="省份名称"),
     *                                  @SWG\Property( property="city_name", type="string", example="114566", description="城市名称"),
     *                                  @SWG\Property( property="district_name", type="string", example="789654", description="区县名称"),
     *                                  @SWG\Property( property="district_code", type="string", example="789654", description="区县编码"),
     *                                  @SWG\Property( property="add_value_list", type="string", example="", description="支付渠道配置信息"),
     *                                  @SWG\Property( property="status", type="string", example="pending", description="接口调用状态，succeeded - 成功 failed - 失败 pending - 处理中"),
     *                                  @SWG\Property( property="alipay_stat", type="string", example="null", description="支付宝入驻结果：S-成功，F-失败"),
     *                                  @SWG\Property( property="alipay_stat_msg", type="string", example="null", description="支付宝入驻错误描述"),
     *                                  @SWG\Property( property="wx_stat", type="string", example="null", description="微信入驻结果：S-成功，F-失败"),
     *                                  @SWG\Property( property="wx_stat_msg", type="string", example="null", description="微信入驻错误描述"),
     *                                  @SWG\Property( property="create_time", type="string", example="1629430783", description=""),
     *                                  @SWG\Property( property="update_time", type="string", example="1629430783", description=""),
     *                          ),
     *                          @SWG\Property( property="SubmitLicense", type="object",
     *                                  @SWG\Property( property="id", type="string", example="1", description="id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="sub_api_key", type="string", example="wqeqweqwe", description="渠道商下商户的apiKey"),
     *                                  @SWG\Property( property="social_credit_code_url", type="string", example="1", description="统一社会信用代码"),
     *                                  @SWG\Property( property="legal_certId_front_url", type="string", example="11", description="法人身份证正面"),
     *                                  @SWG\Property( property="legal_cert_id_back_url", type="string", example="55", description="法人身份证反面"),
     *                                  @SWG\Property( property="account_opening_permit_url", type="string", example="22", description="开户许可证图片"),
     *                                  @SWG\Property( property="business_add", type="string", example="http", description="商城地址"),
     *                                  @SWG\Property( property="store_url", type="string", example="", description="门店"),
     *                                  @SWG\Property( property="transaction_test_record_url", type="string", example="", description="商户在业务网址或商城地址上测试的交易记录截图"),
     *                                  @SWG\Property( property="web_pic_url", type="string", example="", description="网站截图"),
     *                                  @SWG\Property( property="lease_contract_url", type="string", example="", description="租赁合同"),
     *                                  @SWG\Property( property="settle_account_certificate_url", type="string", example="", description="结算账号开户证明图片"),
     *                                  @SWG\Property( property="buss_support_materials_url", type="string", example="", description="业务场景证明材料"),
     *                                  @SWG\Property( property="icp_registration_license_url", type="string", example="", description="icp备案许可证明或者许可证编码"),
     *                                  @SWG\Property( property="industry_qualify_doc_type", type="string", example="", description="行业资质文件类型：1游戏类，2直播类，3小说图书类，4其他"),
     *                                  @SWG\Property( property="industry_qualify_doc_license_url", type="string", example="", description="行业资质文件"),
     *                                  @SWG\Property( property="shareholder_info_list", type="string", example="", description="股东信息"),
     *                                  @SWG\Property( property="cert_id", type="string", example="", description="身份证号"),
     *                                  @SWG\Property( property="cert_name", type="string", example="", description="身份证姓名"),
     *                                  @SWG\Property( property="cert_back_image_url", type="string", example="", description="股东身份证照片"),
     *                                  @SWG\Property( property="cert_front_image_url", type="string", example="", description="股东信息"),
     *                                  @SWG\Property( property="audit_status", type="string", example="I", description="状态 W -> 待补充，I -> 初始，P -> 通过，R -> 拒绝"),
     *                                  @SWG\Property( property="audit_desc", type="string", example="null", description="审核拒绝原因"),
     *                                  @SWG\Property( property="create_time", type="string", example="1629434947", description=""),
     *                                  @SWG\Property( property="update_time", type="string", example="1629434947", description=""),
     *                          ),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function openAccountStep(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $openAccountService = new OpenAccountService();
        $result = $openAccountService->openAccountStepService($companyId);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        if (in_array($companyId, explode(',', config('common.demo_company_id')))) {
            $datapassBlock = true;
        }
        $result = $openAccountService->openAccountStepDataMasking($result, $datapassBlock);
        return $this->response->array($result);
    }


    /**
    * @SWG\Get(
    *     path="/adapay/generate/key",
    *     summary="生成RAS密钥",
    *     tags={"OpenAccount"},
    *     description="生成RAS密钥",
    *     operationId="generateKey",
    *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
    *          @SWG\Property( property="data", type="object",
    *                  @SWG\Property( property="private_key", type="string", example=""),
    *                  @SWG\Property( property="public_key", type="string", example=""),
    *          ),
    *     )),
    * )
    */
    public function generateKey()
    {
        $openAccountService = new OpenAccountService();
        $result = $openAccountService->generateKeyService();

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/other/cat",
     *     summary="费率 入驻 商户分类",
     *     tags={"OpenAccount"},
     *     description="费率 入驻 商户分类",
     *     operationId="otherCat",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="merchant_type_name", description="商户种类名称" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="fee_type", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="code", type="string", example="01"),
     *                          @SWG\Property( property="name", type="string", example="标准费率线上"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="model_type", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="code", type="string", example="1"),
     *                          @SWG\Property( property="name", type="string", example="服务商模式"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="mer_type", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="code", type="string", example="1"),
     *                          @SWG\Property( property="name", type="string", example="政府机构"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function otherCat(Request $request)
    {
        $merchantTypeName = $request->input('merchant_type_name');
        $openAccountService = new OpenAccountService();
        $result = $openAccountService->otherCatService($merchantTypeName);

        return $this->response->array($result);
    }

    public function isOpen(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $openAccountService = new OpenAccountService();
        $info = $openAccountService->openAccountStepService($companyId, true);
        $result['status'] = $info['step'] == 4;

        return $this->response->array($result);
    }
}
