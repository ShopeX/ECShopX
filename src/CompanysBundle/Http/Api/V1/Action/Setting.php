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

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;

use CompanysBundle\Services\CommonLangModService;
use CompanysBundle\Services\SettingService;
use OrdersBundle\Services\TradeSetting\CancelService;
use OrdersBundle\Services\TradeSettingService;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;

class Setting extends BaseController
{
    /**
     * @SWG\Definition(
     *     definition="CompanySetting",
     *     @SWG\Property(
     *         property="data",
     *         type="object",
     *         @SWG\Property(property="company_id", type="string"),
     *         @SWG\Property(property="community_config", type="object",
     *             @SWG\Property(property="point_ratio", type="string", description="积分比例", example="1"),
     *             @SWG\Property(property="point_desc", type="string", description="积分描述", example="这是积分说明"),
     *             @SWG\Property(property="withdraw_desc", type="string", description="提现说明", example="这是积分提现说明"),
     *         ),
     *         @SWG\Property(property="consumer_hotline", type="string", example="189156112313332", description="客服热线"),
     *         @SWG\Property(property="customer_switch", type="integer", example="1", description="客服开关"),
     *         @SWG\Property(property="fapiao_config", type="object",
     *             @SWG\Property(property="fapiao_switch", type="boolean", description="发票开关"),
     *             @SWG\Property(property="content", type="string", description="内容", example="1567926985"),
     *             @SWG\Property(property="tax_rate", type="string", description="税率", example="1567926985"),
     *             @SWG\Property(property="registration_number", type="string", description="税号", example="1567926985"),
     *             @SWG\Property(property="bankname", type="string", description="银行名", example="1567926985"),
     *             @SWG\Property(property="bankaccount", type="string", description="银行账号", example="1567926985"),
     *             @SWG\Property(property="company_phone", type="string", description="电话", example="1567926985"),
     *             @SWG\Property(property="user_name", type="string", description="用户名", example="1567926985"),
     *             @SWG\Property(property="company_address", type="string", description="公司地址", example="1567926985"),
     *             @SWG\Property(property="enterprise_id", type="string", description="企业号", example="1567926985"),
     *             @SWG\Property(property="group_id", type="string", description="组织号", example="1567926985"),
     *             @SWG\Property(property="hangxin_tax_no", type="string", description="", example="1567926985"),
     *             @SWG\Property(property="hangxin_auth_code", type="string", description="", example="1567926985"),
     *             @SWG\Property(property="NSRSBH", type="string", description="", example="1567926985"),
     *             @SWG\Property(property="FPQQLSH", type="string", description="", example="1567926985"),
     *             @SWG\Property(property="DSPTBM", type="string", description="", example="1567926985"),
     *             @SWG\Property(property="XHF_NSRSBH", type="string", description="", example="1567926985"),
     *             @SWG\Property(property="hangxin_switch", type="string", description="", example="1567926985"),
     *             @SWG\Property(property="authorizationCode", type="string", description="授权码", example="NH873FG4KW"),
     *         ),
     *         @SWG\Property(property="withdraw_bank", type="object",
     *             @SWG\Property(property="alipay", type="boolean", description="支付宝"),
     *             @SWG\Property(property="wechatpay", type=" boolean", description="微信支付", example="false"),
     *             @SWG\Property(property="bankpay", type="boolean", description="银行支付", example="true"),
     *
     *         ),
     *         @SWG\Property(property="fapiao_switch", type="string", description="发票开关"),
     *         @SWG\Property(property="created", type="string", description="创建时间", example="1567926985"),
     *         @SWG\Property(property="updated", type="string", description="创建时间", example="1567926985"),
     *     ),
     * )
     */




    /**
     * @SWG\Get(
     *     path="/company/setting",
     *     summary="获取当前企业的基础设置",
     *     tags={"企业"},
     *     description="获取当前企业的基础设置",
     *     operationId="getSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             ref="#/definitions/CompanySetting"
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getSetting(Request $request)
    {
        // Powered by ShopEx EcShopX
        $companyId = app('auth')->user()->get('company_id');

        $settingService = new SettingService();
        $setting = $settingService->getInfo(['company_id' => $companyId]);

        $result = array();
        if ($request->input('community_config')) {
            $result['community_config'] = isset($setting['community_config']) ?: array();
        }

        if ($request->input('withdraw_bank')) {
            $result['withdraw_bank'] = isset($setting['withdraw_bank']) ?: array();
        }

        if ($request->input('consumer_hotline')) {
            $result['consumer_hotline'] = isset($setting['consumer_hotline']) ?: '';
        }

        if ($request->input('customer_switch')) {
            $result['customer_switch'] = $setting['customer_switch'];
        }

        if (!$result) {
            $result = $setting;
        }

        return $this->response->array($result);
    }


    /**
     * @SWG\Post(
     *     path="/company/setting",
     *     summary="设置当前企业的基础设置",
     *     tags={"企业"},
     *     description="设置当前企业的基础设置",
     *     operationId="setSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="community_config", in="query", description="社区团购相关配置", required=false, type="string"),
     *     @SWG\Parameter( name="withdraw_bank", in="query", description="提现支持", required=false, type="string"),
     *     @SWG\Parameter( name="consumer_hotline", in="query", description="消费者热线，客服电话", required=false, type="string"),
     *     @SWG\Parameter( name="customer_switch", in="query", description="客服开关", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             ref="#/definitions/CompanySetting"
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $settingService = new SettingService();

        $data = $request->all('community_config', 'withdraw_bank', 'consumer_hotline', 'customer_switch');

        $setting = $settingService->getInfo(['company_id' => $companyId]);
        if ($setting) {
            $result = $settingService->updateOneBy(['company_id' => $companyId], $data);
        } else {
            $data['company_id'] = $companyId;
            $result = $settingService->create($data);
        }
        return $this->response->array($result);
    }



    /**
     * @SWG\Post(
     *     path="/setting/selfdelivery",
     *     summary="设置固定自提地址",
     *     tags={"企业"},
     *     description="设置固定自提地址",
     *     operationId="setSelfdeliveryAddress",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="唯一标示", type="string"),
     *     @SWG\Parameter( name="username", in="query", description="收货人", type="string"),
     *     @SWG\Parameter( name="telephone", in="query", description="手机号", type="string"),
     *     @SWG\Parameter( name="regions_id", in="query", description="地区标示", type="string"),
     *     @SWG\Parameter( name="adrdetail", in="query", description="详细地址", type="string"),
     *     @SWG\Parameter( name="postalCode", in="query", description="邮编", type="string"),
     *     @SWG\Parameter( name="regions", in="query", description="地区", type="string"),
     *     @SWG\Parameter( name="provinceName", in="query", description="省", type="string"),
     *     @SWG\Parameter( name="cityName", in="query", description="市", type="string"),
     *     @SWG\Parameter( name="countyName", in="query", description="区", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setSelfdeliveryAddress(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService();
        $formdata = $request->get('addreeList', []);
        if (!$formdata) {
            throw new ResourceException('地址参数有误');
        }
        $rules = [
            'username' => ['required', '收货人姓名不能为空'],
            'telephone' => ['required', '手机号不能为空'],
            'regions_id' => ['required', '地区不能为空'],
            'adrdetail' => ['required', '详细地址不能为空'],
            'regions' => ['required', '地区不能为空'],
        ];
        foreach ($formdata as $value) {
            $errorMessage = validator_params($value, $rules);
            if ($errorMessage) {
                throw new ResourceException($errorMessage);
            }
        }
        //$data = $request->all('id', 'username', 'telephone', 'regions_id', 'adrdetail', 'postalCode', 'regions');
        $result = $settingService->selfdeliveryAddressSave($companyId, $formdata);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/setting/selfdelivery",
     *     summary="设置固定自提地址",
     *     tags={"企业"},
     *     description="设置固定自提地址",
     *     operationId="setSelfdeliveryAddress",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="id", type="string",example="", description=""),
     *                     @SWG\Property(property="username", type="string",example="", description="用户名"),
     *                     @SWG\Property(property="telephone", type="string",example="", description="电话"),
     *                     @SWG\Property(property="regions_id", type="string",example="", description="地区号"),
     *                     @SWG\Property(property="adrdetail", type="string",example="", description="地址详情"),
     *                     @SWG\Property(property="postalCode", type="string",example="", description="邮编"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getSelfdeliveryAddress(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService();
        $result = $settingService->selfdeliveryAddressGet($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/setting/weburl",
     *     summary="配置外部链接",
     *     tags={"企业"},
     *     description="配置外部链接",
     *     operationId="saveWebUrlSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="mycoach", in="query", description="唯一标示", required=false, type="string"),
     *     @SWG\Parameter( name="aftersales", in="query", description="收货人", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="mycoach", type="string"),
     *                 @SWG\Property(property="aftersales", type="string"),
     *                 @SWG\Property(property="classhour", type="string", description="我的课时"),
     *                 @SWG\Property(property="arranged", type="string", description="我的已约"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function saveWebUrlSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'webUrlSetting:' . $companyId;
        $inputData = $request->all('mycoach', 'aftersales', 'classhour', 'arranged');
        app('redis')->connection('companys')->set($key, json_encode($inputData));
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/setting/weburl",
     *     summary="获取配置外部链接",
     *     tags={"企业"},
     *     description="获取配置外部链接",
     *     operationId="getWebUrlSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="mycoach", type="string"),
     *                 @SWG\Property(property="aftersales", type="string"),
     *                 @SWG\Property(property="classhour", type="string", description="我的课时"),
     *                 @SWG\Property(property="arranged", type="string", description="我的已约"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getWebUrlSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'webUrlSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : [];
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/traderate/setting",
     *     summary="设置评价状态",
     *     tags={"企业"},
     *     description="设置评价状态",
     *     operationId="rateSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="rate_status", type="boolean", description="评价状态")
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    /**
     * @SWG\Post(
     *     path="/traderate/setting",
     *     summary="设置评价状态",
     *     tags={"企业"},
     *     description="设置评价状态",
     *     operationId="rateSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="rate_status",
     *         in="header",
     *         description="评价状态",
     *         type="boolean",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="rate_status", type="boolean", description="评价状态")
     *             )
     *         )
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function rateSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'TradeRateSetting:' . $companyId;
        $inputdata = $request->all();
        if (isset($inputdata['rate_status'])) {
            $data['rate_status'] = ($inputdata['rate_status'] == 'true') ? true : false;
            app('redis')->connection('companys')->set($key, json_encode($data));
            return $this->response->array($data);
        }
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['rate_status' => false];
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/share/setting",
     *     summary="获取分享设置",
     *     tags={"企业"},
     *     description="获取分享设置",
     *     operationId="getShareSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *           @SWG\Property(
     *             property="data",
     *             type="object",
     *             @SWG\Property(property="index", type="object", description="",
     *                 @SWG\Property(property="title", type="object", description="标题", example=""),
     *                 @SWG\Property(property="desc", type="object", description="描述", example=""),
     *                 @SWG\Property(property="imageUrl", type="object", description="图片链接", example="")
     *             ),
     *             @SWG\Property(property="planting", type="object", description="种草",
     *                 @SWG\Property(property="title", type="object", description="标题", example=""),
     *                 @SWG\Property(property="desc", type="object", description="描述", example=""),
     *                 @SWG\Property(property="imageUrl", type="object", description="图片链接", example="")
     *             ),
     *             @SWG\Property(property="itemlist", type="object", description="商品列表",
     *                 @SWG\Property(property="title", type="object", description="标题", example=""),
     *                 @SWG\Property(property="desc", type="object", description="描述", example=""),
     *                 @SWG\Property(property="imageUrl", type="object", description="图片链接", example="")
     *             ),
     *             @SWG\Property(property="group", type="object", description="团购",
     *                 @SWG\Property(property="title", type="object", description="标题", example=""),
     *                 @SWG\Property(property="desc", type="object", description="描述", example=""),
     *                 @SWG\Property(property="imageUrl", type="object", description="图片链接", example="")
     *             ),
     *             @SWG\Property(property="seckill", type="object", description="秒杀",
     *                 @SWG\Property(property="title", type="object", description="标题", example=""),
     *                 @SWG\Property(property="desc", type="object", description="描述", example=""),
     *                 @SWG\Property(property="imageUrl", type="object", description="图片链接", example="")
     *             ),
     *             @SWG\Property(property="coupon", type="object", description="优惠券",
     *                 @SWG\Property(property="title", type="object", description="标题", example=""),
     *                 @SWG\Property(property="desc", type="object", description="描述", example=""),
     *                 @SWG\Property(property="imageUrl", type="object", description="图片链接", example="")
     *             ),
     *           )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getShareSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'shareSetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        $defaultData = [
            'index' => [
                'title' => '',
                'desc' => '',
                'imageUrl' => '',
            ],
            'planting' => [
                'title' => '',
                'desc' => '',
                'imageUrl' => '',
            ],
            'itemlist' => [
                'title' => '',
                'desc' => '',
                'imageUrl' => '',
            ],
            'group' => [
                'title' => '',
                'desc' => '',
                'imageUrl' => '',
            ],
            'seckill' => [
                'title' => '',
                'desc' => '',
                'imageUrl' => '',
            ],
            'coupon' => [
                'title' => '',
                'desc' => '',
                'imageUrl' => '',
            ]
        ];
        // 多语言处理
        $data = $data ? json_decode($data, true) : [];
        $ns = new CommonLangModService();
        $data = $ns->getLangDataIndexLang($data);

        $data = array_merge($defaultData, $data);

        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/share/setting",
     *     summary="保存分享设置",
     *     tags={"企业"},
     *     description="保存分享设置",
     *     operationId="setShareSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="index",
     *         in="query",
     *         description="",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="planting",
     *         in="query",
     *         description="种草",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="itemlist",
     *         in="query",
     *         description="商品",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="group",
     *         in="query",
     *         description="团购",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="seckill",
     *         in="query",
     *         description="秒杀",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="coupon",
     *         in="query",
     *         description="优惠券",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="status",
     *                     type="boolean"
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setShareSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'shareSetting:' . $companyId;
        $inputdata = $request->all(
            'index.title',
            'index.desc',
            'index.imageUrl',
            'planting.title',
            'planting.desc',
            'planting.imageUrl',
            'itemlist.title',
            'itemlist.desc',
            'itemlist.imageUrl',
            'group.title',
            'group.desc',
            'group.imageUrl',
            'seckill.title',
            'seckill.desc',
            'seckill.imageUrl',
            'coupon.title',
            'coupon.desc',
            'coupon.imageUrl'
        );
        // 多语言处理
        $ns = new CommonLangModService();
        $inputdata = $ns->setLangDataIndexLang($inputdata, ['title', 'desc']);

        $data = app('redis')->connection('companys')->set($key, json_encode($inputdata));

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/member/whitelist/setting",
     *     summary="设置白名单状态",
     *     tags={"企业"},
     *     description="设置白名单状态",
     *     operationId="whitelistSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="whitelist_status",
     *         in="query",
     *         description="白名单状态",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="whitelist_tips",
     *         in="query",
     *         description="whitelist_tips",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="whitelist_status",
     *                     type="boolean",
     *                     description="白名单状态"
     *                 ),
     *                 @SWG\Property(
     *                     property="whitelist_tips",
     *                     type="string",
     *                     description="whitelist_tips"
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    /**
     * @SWG\Get(
     *     path="/member/whitelist/setting",
     *     summary="获取白名单状态",
     *     tags={"企业"},
     *     description="设置白名单状态",
     *     operationId="whitelistSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="whitelist_status",
     *                     type="boolean",
     *                     description="白名单状态"
     *                 ),
     *                 @SWG\Property(
     *                     property="whitelist_tips",
     *                     type="string",
     *                     description="whitelist_tips"
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function whitelistSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'WhitelistSetting:' . $companyId;
        $inputdata = $request->all();
        $settingService = new SettingService();
        $config = $settingService->getWhitelistSetting($companyId);
        if (isset($inputdata['whitelist_status']) || isset($inputdata['whitelist_tips'])) {
            if (isset($inputdata['whitelist_status'])) {
                $config['whitelist_status'] = ($inputdata['whitelist_status'] == 'true') ? true : false;
            }
            if (isset($inputdata['whitelist_tips'])) {
                $config['whitelist_tips'] = empty(trim($inputdata['whitelist_tips'])) ? '登录失败，手机号不在白名单内！' : trim($inputdata['whitelist_tips']);
            }
            app('redis')->connection('companys')->set($key, json_encode($config));
        }
        return $this->response->array($config);
    }

    /**
     * @SWG\Get(
     *     path="/pickupcode/setting",
     *     summary="设置预售提货码状态",
     *     tags={"企业"},
     *     description="设置预售提货码状态",
     *     operationId="pickupcodeSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="pickupcode_status",
     *                     type="boolean",
     *                     description="是否开启提货码"
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    /**
     * @SWG\Post(
     *     path="/pickupcode/setting",
     *     summary="设置预售提货码状态",
     *     tags={"企业"},
     *     description="设置预售提货码状态",
     *     operationId="pickupcodeSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pickupcode_status",
     *         in="query",
     *         description="是否开启提货码",
     *         type="boolean",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="pickupcode_status",
     *                     type="boolean",
     *                     description="是否开启提货码"
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function pickupcodeSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'PresalePickupcodeSetting:' . $companyId;
        $inputdata = $request->all();
        if (isset($inputdata['pickupcode_status'])) {
            $data['pickupcode_status'] = ($inputdata['pickupcode_status'] == 'true') ? true : false;
            app('redis')->connection('companys')->set($key, json_encode($data));
            return $this->response->array($data);
        }
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['pickupcode_status' => false];
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/gift/setting",
     *     summary="赠品相关设置",
     *     tags={"企业"},
     *     description="赠品相关设置",
     *     operationId="getGiftSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="minus_shop_gift_store", type="boolean", description="扣减赠品库存"),
     *                 @SWG\Property(property="check_gift_store", type="boolean", description="检查库存"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getGiftSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'giftSetting:' . $companyId;
        $result = app('redis')->connection('companys')->get($key);
        $result = json_decode($result, 1);
        $result['minus_shop_gift_store'] = $result['minus_shop_gift_store'] ?? false;
        $result['check_gift_store'] = $result['check_gift_store'] ?? false;
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/gift/setting",
     *     summary="赠品相关设置",
     *     tags={"企业"},
     *     description="赠品相关设置",
     *     operationId="setGiftSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="minus_shop_gift_store",
     *         in="header",
     *         description="",
     *         type="string",
     *         description=""
     *     ),
     *     @SWG\Parameter(
     *         name="check_gift_store",
     *         in="header",
     *         description="",
     *         type="string",
     *         description=""
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setGiftSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'giftSetting:' . $companyId;
        $inputdata = $request->all('minus_shop_gift_store', 'check_gift_store');
        $inputdata['minus_shop_gift_store'] = $inputdata['minus_shop_gift_store'] == 'true' ? true : false;
        $inputdata['check_gift_store'] = $inputdata['check_gift_store'] == 'true' ? true : false;
        $data = app('redis')->connection('companys')->set($key, json_encode($inputdata));

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/sendoms/setting",
     *     summary="推oms相关设置",
     *     tags={"企业"},
     *     description="推oms相关设置",
     *     operationId="getSendOmsSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="ziti_send_oms", type="boolean", description="自提发送oms"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getSendOmsSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'sendOmsSetting:' . $companyId;
        $result = app('redis')->connection('companys')->get($key);
        $result = json_decode($result, 1);
        $result['ziti_send_oms'] = $result['ziti_send_oms'] ?? false;

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/sendoms/setting",
     *     summary="推oms相关设置",
     *     tags={"企业"},
     *     description="推oms相关设置",
     *     operationId="setSendOmsSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="ziti_send_oms",
     *         in="query",
     *         description="自提订单是否推送oms",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     type="boolean",
     *                     property="status",
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setSendOmsSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'sendOmsSetting:'. $companyId;
        $inputdata = $request->all('ziti_send_oms');
        $inputdata['ziti_send_oms'] = $inputdata['ziti_send_oms'] == 'true' ? true : false;
        $data = app('redis')->connection('companys')->set($key, json_encode($inputdata));

        return $this->response->array(['status' => true]);
    }


    /**
     * @SWG\Get(
     *     path="/nostores/setting",
     *     summary="获取前端店铺展示开关",
     *     tags={"企业"},
     *     description="获取前端店铺展示开关",
     *     operationId="getNostoresSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="nostores_status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getNostoresSetting(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $settingService = new SettingService();
        $result = $settingService->getNostoresSetting($company_id);
        $result['nostores_status'] = $result['nostores_status'] == 'true' ? true : false;
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/nostores/setting",
     *     summary="设置前端店铺展示开关",
     *     tags={"企业"},
     *     description="设置前端店铺展示开关",
     *     operationId="setNostoresSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="nostores_status",
     *         in="query",
     *         description="设置状态",
     *         required=true,
     *         type="boolean",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setNostoresSetting(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'nostores_status' => ['required', '设置状态不能为空'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $settingService = new SettingService();
        $result = $settingService->setNostoresSetting($company_id, $params);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/recharge/setting",
     *     summary="设置储值功能状态",
     *     tags={"企业"},
     *     description="设置储值功能状态",
     *     operationId="rechargeSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="recharge_status",
     *         in="query",
     *         description="储值功能状态",
     *         type="boolean",
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="recharge_status", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    /**
     * @SWG\Get(
     *     path="/recharge/setting",
     *     summary="设置储值功能状态",
     *     tags={"企业"},
     *     description="设置储值功能状态",
     *     operationId="rechargeSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *          @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="recharge_status", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function rechargeSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputdata = $request->all();
        $settingService = new SettingService();

        if (isset($inputdata['recharge_status'])) {
            $data = $settingService->setRechargeSetting($companyId, $inputdata);
            return $this->response->array($data);
        }
        $inputData = $settingService->getRechargeSetting($companyId);
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/itemStore/setting",
     *     summary="设置库存显示状态",
     *     tags={"企业"},
     *     description="设置库存显示状态",
     *     operationId="rechargeSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_show",
     *         in="query",
     *         description="是否显示",
     *         required=true,
     *         type="string",
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *          @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="is_show", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function itemStoreSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputdata = $request->all();
        $settingService = new SettingService();
        if (isset($inputdata['item_store_status'])) {
            $data = $settingService->setItemStoreSetting($companyId, $inputdata);
            return $this->response->array($data);
        }
        $inputData = $settingService->getItemStoreSetting($companyId);
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/itemSales/setting",
     *     summary="设置商品销量显示状态",
     *     tags={"企业"},
     *     description="设置商品销量显示状态",
     *     operationId="rechargeSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="item_sales_status",
     *         in="query",
     *         description="是否显示",
     *         required=true,
     *         type="string",
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *          @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="is_show", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function itemSalesSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputdata = $request->all();
        $settingService = new SettingService();
        if (isset($inputdata['item_sales_status'])) {
            $data = $settingService->setItemSalesSetting($companyId, $inputdata);
            return $this->response->array($data);
        }
        $inputData = $settingService->getItemSalesSetting($companyId);
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/invoice/setting",
     *     summary="设置发票选项显示状态",
     *     tags={"企业"},
     *     description="设置发票选项显示状态",
     *     operationId="rechargeSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_show",
     *         in="query",
     *         description="是否显示",
     *         required=true,
     *         type="string",
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *          @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="is_show", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function invoiceSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputdata = $request->all();
        $settingService = new SettingService();
        if (isset($inputdata['invoice_status'])) {
            $settingService->setInvoiceSetting($companyId, $inputdata);
            return $this->response->array(['status' => true]);
        }
        $inputData = $settingService->getInvoiceSetting($companyId);
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/itemshare/setting",
     *     summary="获取商品分享设置",
     *     tags={"企业"},
     *     description="获取商品分享设置",
     *     operationId="getItemShareSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *          @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="is_open", type="boolean", description="商品分享是否限制开关状态 true:开启 false:关闭", example="true"),
     *                 @SWG\Property(property="valid_grade", type="string", description="会员级别集合数组", example="true"),
     *                 @SWG\Property(property="msg", type="string", description="分享限制提示语", example="当前等级无法分享"),
     *                 @SWG\Property(property="page", type="string", description="提示跳转页面数组", example="pages/index"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getItemShareSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService();
        $inputData = $settingService->getItemShareSetting($companyId);
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Post(
     *     path="/itemshare/setting",
     *     summary="保存商品分享设置",
     *     tags={"企业"},
     *     description="保存商品分享设置",
     *     operationId="saveItemShareSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_open",
     *         in="query",
     *         description="是否开启 true:开启 false:关闭",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="valid_grade",
     *         in="query",
     *         description="会员级别集合 数组 is_open=true时必填",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="msg",
     *         in="query",
     *         description="分享限制提示语 is_open=true时必填",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="提示跳转页面路径 数组 is_open=true时必填",
     *         required=false,
     *         type="string",
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *          @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function saveItemShareSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputdata = $request->all();
        $rules = [
            'is_open' => ['required|in:true,false', '商品分享是否限制不能为空'],
            'valid_grade' => ['required_if:is_open,true', '可分享会员等级至少选择一个'],
            'msg' => ['required_if:is_open,true|string|max:20', '分享限制提示语不能为空,且最大长度不超过20个汉字'],
            'page' => ['required_if:is_open,true', '提示后跳转页面路径不能为空'],
        ];
        $errorMessage = validator_params($inputdata, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $settingService = new SettingService();
        $status = $settingService->setItemShareSetting($companyId, $inputdata);
        return $this->response->array(['status' => $status]);
    }

    /**
     * @SWG\Get(
     *     path="/shareParameters/setting",
     *     summary="获取小程序分享参数设置",
     *     tags={"企业"},
     *     description="获取小程序分享参数设置",
     *     operationId="getShareParametersSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="distributor_param_status", type="boolean", description="是否带门店参数 true:开启 false:关闭")
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getShareParametersSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService();
        $result = $settingService->getShareParametersSetting($companyId);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/shareParameters/setting",
     *     summary="保存小程序分享参数设置",
     *     tags={"企业"},
     *     description="保存小程序分享参数设置",
     *     operationId="saveShareParametersSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_param_status",
     *         in="query",
     *         description="是否带门店参数 true:开启 false:关闭",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="distributor_param_status", type="boolean", description="是否带门店参数开关状态 true:开启 false:关闭", example="true"),
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function saveShareParametersSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputData['distributor_param_status'] = $request->input('distributor_param_status', 'false');

        $settingService = new SettingService();
        $result = $settingService->saveShareParametersSetting($companyId, $inputData);

        return $this->response->array($result);
    }

    public function getDianwuSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService();
        $result = $settingService->getDianwuSetting($companyId);

        return $this->response->array($result);
    }

    public function saveDianwuSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputData['dianwu_show_status'] = $request->input('dianwu_show_status', false);

        $settingService = new SettingService();
        $result = $settingService->saveDianwuSetting($companyId, $inputData);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/itemPrice/setting",
     *     summary="获取小程序价格显示设置",
     *     tags={"企业"},
     *     description="获取小程序价格显示设置",
     *     operationId="getItemPriceSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="cart_page",
     *                     type="object",
     *                     @SWG\Property(property="market_price", type="boolean", description="是否显示原价"),
     *                 ),
     *                 @SWG\Property(property="order_page",
     *                     type="object",
     *                     @SWG\Property(property="market_price", type="boolean", description="是否显示原价"),
     *                 ),
     *                 @SWG\Property(property="item_page",
     *                     type="object",
     *                     @SWG\Property(property="market_price", type="boolean", description="是否显示原价"),
     *                     @SWG\Property(property="member_price", type="boolean", description="是否显示会员等级价"),
     *                     @SWG\Property(property="svip_price", type="boolean", description="是否显示SVIP价"),
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getItemPriceSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService();
        $result = $settingService->getItemPriceSetting($companyId);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/itemPrice/setting",
     *     summary="保存小程序价格显示设置",
     *     tags={"企业"},
     *     description="保存小程序价格显示设置",
     *     operationId="saveItemPriceSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="cart_page[market_price]",
     *         in="query",
     *         description="是否显示原价 true:显示 false:不显示",
     *         required=false,
     *         type="boolean",
     *     ),
     *     @SWG\Parameter(
     *         name="order_page[market_price]",
     *         in="query",
     *         description="是否显示原价 true:显示 false:不显示",
     *         required=false,
     *         type="boolean",
     *     ),
     *     @SWG\Parameter(
     *         name="item_page[market_price]",
     *         in="query",
     *         description="是否显示原价 true:显示 false:不显示",
     *         required=false,
     *         type="boolean",
     *     ),
     *     @SWG\Parameter(
     *         name="item_page[member_price]",
     *         in="query",
     *         description="是否显示会员等级价 true:显示 false:不显示",
     *         required=false,
     *         type="boolean",
     *     ),
     *     @SWG\Parameter(
     *         name="item_page[svip_price]",
     *         in="query",
     *         description="是否显示SVIP价 true:显示 false:不显示",
     *         required=false,
     *         type="boolean",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="cart_page",
     *                     type="object",
     *                     @SWG\Property(property="market_price", type="boolean", description="是否显示原价"),
     *                 ),
     *                 @SWG\Property(property="order_page",
     *                     type="object",
     *                     @SWG\Property(property="market_price", type="boolean", description="是否显示原价"),
     *                 ),
     *                 @SWG\Property(property="item_page",
     *                     type="object",
     *                     @SWG\Property(property="market_price", type="boolean", description="是否显示原价"),
     *                     @SWG\Property(property="member_price", type="boolean", description="是否显示会员等级价"),
     *                     @SWG\Property(property="svip_price", type="boolean", description="是否显示SVIP价"),
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function saveItemPriceSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $inputdata = [];
        if (isset($params['cart_page'])) {
            if (isset($params['cart_page']['market_price'])) {
                $inputdata['cart_page']['market_price'] = $params['cart_page']['market_price'] == 'true';
            }
        }

        if (isset($params['order_page'])) {
            if (isset($params['order_page']['market_price'])) {
                $inputdata['order_page']['market_price'] = $params['order_page']['market_price'] == 'true';
            }
        }

        if (isset($params['item_page'])) {
            if (isset($params['item_page']['market_price'])) {
                $inputdata['item_page']['market_price'] = $params['item_page']['market_price'] == 'true';
            }
            if (isset($params['item_page']['member_price'])) {
                $inputdata['item_page']['member_price'] = $params['item_page']['member_price'] == 'true';
            }
            if (isset($params['item_page']['svip_price'])) {
                $inputdata['item_page']['svip_price'] = $params['item_page']['svip_price'] == 'true';
            }
        }

        $settingService = new SettingService();
        $result = $settingService->saveItemPriceSetting($companyId, $inputdata);

        return $this->response->array($result);
    }

    /**
     * 通用设置
     * path="/settings"
     */
    public function getAllSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService();

        //评价状态
        $key = 'TradeRateSetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        $result['traderate_setting'] = $data ? json_decode($data, true) : ['rate_status' => false];

        //小程序分享参数设置
        $result['share_parameters_setting'] = $settingService->getShareParametersSetting($companyId);

        //白名单状态
        $result['whitelist_setting'] =  $settingService->getWhitelistSetting($companyId);

        //预售提货码状态
        $key = 'PresalePickupcodeSetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        $result['pickupcode_setting'] = $data ? json_decode($data, true) : ['pickupcode_status' => false];

        //赠品相关设置

        $key = 'giftSetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        $result['gift_setting'] = $data ? json_decode($data, true) : ['minus_shop_gift_store' => false, 'check_gift_store' => false];

        //推oms相关设置
        $key = 'sendOmsSetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        $result['sendoms_setting'] = $data ? json_decode($data, true) : ['ziti_send_oms' => false];

        //前端店铺展示开关
        $result['nostores_setting'] = $settingService->getNostoresSetting($companyId);

        //储值功能状态
        $result['recharge_setting'] = $settingService->getRechargeSetting($companyId);

        //取消订单配置信息
        $tradeSettingService = new TradeSettingService(new CancelService());
        $result['cancel_setting'] = $tradeSettingService->getSetting($companyId);

        //库存显示状态
        $result['item_store_setting'] = $settingService->getItemStoreSetting($companyId);

        //商品销量显示状态
        $result['item_sales_setting'] = $settingService->getItemSalesSetting($companyId);

        //发票选项显示状态
        $result['invoice_setting'] = $settingService->getInvoiceSetting($companyId);

        //店务设置
        $result['dianwu_setting'] = $settingService->getDianwuSetting($companyId);

        //商品价格设置
        $result['item_price_setting'] = $settingService->getItemPriceSetting($companyId);

        //分类页样式
        $result['category_style'] = $settingService->getCategoryPageSetting($companyId);

        // 医药行业配置
        $result['medicine_setting'] = $settingService->getMedicineSetting($companyId);

        //店铺隔离
        $result['open_distributor_divided'] =$settingService->getOpenDistributorDivided($companyId);

        return $this->response->array($result);
    }

    public function getCategoryPageSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService();
        $result = $settingService->getCategoryPageSetting($companyId);

        return $this->response->array($result);
    }

    public function saveCategoryPageSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputData = $request->all();
        $rules = [
            'style' => ['required|in:category,items', '页面风格必填'],
        ];
        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $settingService = new SettingService();
        $result = $settingService->saveCategoryPageSetting($companyId, $inputData);

        return $this->response->array($result);
    }

    public function saveMedicineSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputData = $request->all();
        $rules = [
            'is_pharma_industry' => ['required', '是否为医药行业必填'],
        ];
        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $settingService = new SettingService();
        $result = $settingService->saveMedicineSetting($companyId, $inputData);

        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/openDivided/setting",
     *     summary="设置是否开启店铺隔离",
     *     tags={"企业"},
     *     description="设置库存显示状态",
     *     operationId="rechargeSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_show",
     *         in="query",
     *         description="是否显示",
     *         required=true,
     *         type="string",
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *          @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="is_show", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function openDistributorDividedSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputdata = $request->all();
        $settingService = new SettingService();
        if (isset($inputdata['open_distributor_divided'])) {
            $data = $settingService->setOpenDistributorDivided($companyId, $inputdata);
            return $this->response->array($data);
        }
        $inputData = $settingService->getOpenDistributorDivided($companyId);
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/items/startNum/setting",
     *     summary="设置商品起订量",
     *     tags={"企业"},
     *     description="设置商品起订量",
     *     operationId="itemStartNumSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="item_start_num", type="boolean", description="起订量状态")
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    /**
     * @SWG\Post(
     *     path="/items/startNum/setting",
     *     summary="设置商品起订量",
     *     tags={"企业"},
     *     description="设置商品起订量",
     *     operationId="itemStartNumSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="item_start_num",
     *         in="header",
     *         description="评价状态",
     *         type="boolean",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="item_start_num", type="boolean", description="评价状态")
     *             )
     *         )
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function itemStartNumSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputdata = $request->all();
        $settingService = new SettingService();
        if (isset($inputdata['item_start_num'])) {
            $data = $settingService->setItemStartNumSetting($companyId, $inputdata);
            return $this->response->array($data);
        }
        $inputData = $settingService->getItemStartNumSetting($companyId);
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/supplierItems/startNum/setting",
     *     summary="设置供应商商品起订量",
     *     tags={"企业"},
     *     description="设置供应商商品起订量",
     *     operationId="supplirItemStartNumSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="supplier_item_start_num", type="boolean", description="起订量状态")
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    /**
     * @SWG\Post(
     *     path="/supplierItems/startNum/setting",
     *     summary="设置供应商商品起订量",
     *     tags={"企业"},
     *     description="设置供应商商品起订量",
     *     operationId="supplirItemStartNumSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="supplier_item_start_num",
     *         in="header",
     *         description="评价状态",
     *         type="boolean",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="supplier_item_start_num", type="boolean", description="评价状态")
     *             )
     *         )
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function supplirItemStartNumSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputdata = $request->all();
        $settingService = new SettingService();
        if (isset($inputdata['supplier_item_start_num'])) {
            $data = $settingService->setSupplierItemStartNumSetting($companyId, $inputdata);
            return $this->response->array($data);
        }
        $inputData = $settingService->getSupplierItemStartNumSetting($companyId);
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Post(
     *     path="/mail/setting",
     *     summary="保存邮件配置",
     *     tags={"企业"},
     *     description="保存邮件配置",
     *     operationId="saveMailSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="EMAIL_SMTP_PORT",
     *         in="query",
     *         description="SMTP端口",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="EMAIL_RELAY_HOST",
     *         in="query",
     *         description="SMTP服务器地址",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="EMAIL_SENDER",
     *         in="query",
     *         description="发件人邮箱",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="EMAIL_USER",
     *         in="query",
     *         description="SMTP用户名",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="EMAIL_PASSWORD",
     *         in="query",
     *         description="SMTP密码",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function saveMailSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'mailSetting:' . $companyId;
        
        $inputdata = $request->all();
        $rules = [
            'EMAIL_SMTP_PORT' => ['required', 'SMTP端口不能为空'],
            'EMAIL_RELAY_HOST' => ['required', 'SMTP服务器地址不能为空'],
            'EMAIL_SENDER' => ['required|email', '发件人邮箱不能为空且格式正确'],
            'EMAIL_USER' => ['required', 'SMTP用户名不能为空'],
            'EMAIL_PASSWORD' => ['required', 'SMTP密码不能为空'],
        ];
        
        $errorMessage = validator_params($inputdata, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        
        $mailConfig = [
            'EMAIL_SMTP_PORT' => $inputdata['EMAIL_SMTP_PORT'],
            'EMAIL_RELAY_HOST' => $inputdata['EMAIL_RELAY_HOST'],
            'EMAIL_SENDER' => $inputdata['EMAIL_SENDER'],
            'EMAIL_USER' => $inputdata['EMAIL_USER'],
            'EMAIL_PASSWORD' => $inputdata['EMAIL_PASSWORD'],
        ];
        
        app('redis')->connection('companys')->set($key, json_encode($mailConfig));
        
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/mail/setting",
     *     summary="获取邮件配置",
     *     tags={"企业"},
     *     description="获取邮件配置",
     *     operationId="getMailSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="EMAIL_SMTP_PORT", type="string", description="SMTP端口"),
     *                 @SWG\Property(property="EMAIL_RELAY_HOST", type="string", description="SMTP服务器地址"),
     *                 @SWG\Property(property="EMAIL_SENDER", type="string", description="发件人邮箱"),
     *                 @SWG\Property(property="EMAIL_USER", type="string", description="SMTP用户名"),
     *                 @SWG\Property(property="EMAIL_PASSWORD", type="string", description="SMTP密码"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getMailSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'mailSetting:' . $companyId;
        
        $data = app('redis')->connection('companys')->get($key);
        $result = $data ? json_decode($data, true) : [
            'EMAIL_SMTP_PORT' => '',
            'EMAIL_RELAY_HOST' => '',
            'EMAIL_SENDER' => '',
            'EMAIL_USER' => '',
            'EMAIL_PASSWORD' => '',
        ];
        
        return $this->response->array($result);
    }

}
