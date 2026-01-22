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
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
// use Illuminate\Http\Response;
use CompanysBundle\Services\CompanysService;
use CompanysBundle\Services\SettingService;
use CompanysBundle\Ego\CompanysActivationEgo;
use SystemLinkBundle\Services\ThirdSettingService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use DistributionBundle\Services\DistributorService;

class Companys extends BaseController
{
    /** @var CompanysService */
    private $companysService;

    /** @var SettingService */
    private $settingService;

    /**
     * @param CompanysService  $companysService
     */
    public function __construct(CompanysService $companysService)
    {
        $this->companysService = $companysService;
        $this->settingService = new SettingService();
    }

    /**
     * @SWG\Post(
     *     path="/company/activate",
     *     summary="系统激活",
     *     tags={"企业"},
     *     description="系统激活",
     *     operationId="active",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="active_code",
     *         in="query",
     *         description="激活码",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="shop_num",
     *         in="query",
     *         description="资源包含门店数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="source",
     *         in="query",
     *         description="资源来源, preview:初次登陆赠送,purchased：购买,gift: 赠品",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="available_days",
     *         in="query",
     *         description="有效时长",
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
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function active(Request $request)
    {
        $params = $request->all('active_code', 'shop_num', 'source', 'available_days');
        $params['user'] = app('auth')->user();
        $result = app('authorization')->createCompanyLicense($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/company/resources",
     *     summary="获取当前可用资源包列表",
     *     tags={"企业"},
     *     description="获取当前可用资源包列表",
     *     operationId="getResourceList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="is_valid",
     *         in="query",
     *         description="是否有效",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="left_shop_min",
     *         in="query",
     *         description="最少剩余门店数",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="left_shop_max",
     *         in="query",
     *         description="最多剩余门店数",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_no",
     *         in="query",
     *         description="当前页数",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="返回列表的数量，可选，默认取100",
     *         type="string",
     *     ),
     *   @SWG\Response(
     *       response="200",
     *       description="成功返回结构",
     *       @SWG\Schema(
     *              @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer"),
     *                 @SWG\Property(property="list", type="array", @SWG\Items(
     *                         @SWG\Property(property="resourceId", type="string", example="", description=""),
     *                         @SWG\Property(property="resourceName", type="string", example="", description=""),
     *                         @SWG\Property(property="companyId", type="string", example="", description=""),
     *                         @SWG\Property(property="eid", type="string", example="661309471969", description=""),
     *                         @SWG\Property(property="passportUid", type="string", example="", description=""),
     *                         @SWG\Property(property="shopNum", type="integer", example="1", description=""),
     *                         @SWG\Property(property="leftShopNum", type="integer", example="1", description=""),
     *                         @SWG\Property(property="source", type="string", example="", description=""),
     *                         @SWG\Property(property="availableDays", type="integer", example="", description=""),
     *                         @SWG\Property(property="activeAt", type="string", example="", description=""),
     *                         @SWG\Property(property="expiredAt", type="string", example="", description=""),
     *                         @SWG\Property(property="activeCode", type="string", example="", description=""),
     *                         @SWG\Property(property="issueId", type="boolean", example="", description=""),
     *                         @SWG\Property(property="goodsCode", type="boolean", example="", description=""),
     *                         @SWG\Property(property="productCode", type="boolean", example="", description=""),
     *                 ))
     *              ),
     *        ),
     *    ),
     *    @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     *   ),
     * )
     */
    public function getResourceList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $page = isset($params['page_no']) && $params['page_no'] ? $params['page_no'] : 1;
        $limit = isset($params['page_size']) && $params['page_size'] ? $params['page_size'] : 100000;
        $offset = ($page - 1) * $limit;
        $filter['company_id'] = $companyId;
        if (isset($params['is_valid']) && $params['is_valid']) {
            $filter['left_shop_num|gte'] = 0;
            $filter['expired_at|gt'] = time();
        }
        if (isset($params['left_shop_min']) && isset($params['left_shop_max'])) {
            $filter['left_shop_num|gte'] = $params['left_shop_min'];
            $filter['left_shop_num|lt'] = $params['left_shop_max'];
        } elseif (isset($params['left_shop_max']) && !isset($params['left_shop_min'])) {
            $filter['left_shop_num|lte'] = $params['left_shop_max'];
        } elseif (!isset($params['left_shop_max']) && isset($params['left_shop_min'])) {
            $filter['left_shop_num|gte'] = $params['left_shop_min'];
        }
        if (isset($params['leftDays']) && $params['leftDays']) {
            $filter['expired_at|gt'] = time();
            $filter['expired_at|lt'] = strtotime('+'.$params['leftDays'].'days');
        }

        return $this->companysService->getResources($filter, $orderBy = ['expired_at' => 'ASC'], $offset, $limit);
    }

    /**
     * @SWG\Get(
     *     path="/company/activate",
     *     summary="获取激活信息",
     *     tags={"企业"},
     *     description="获取激活信息",
     *     operationId="getActivateInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *   @SWG\Response(
     *       response="200",
     *       description="成功返回结构",
     *       @SWG\Schema(
     *           @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="company_id", type="integer", description="公司id", example="1"),
     *                 @SWG\Property(property="expired_at", type="string", description="过期时间", example="2878362772"),
     *                 @SWG\Property(property="resouce_id", type="integer", description="", example="1"),
     *                 @SWG\Property(property="source", type="string", description="", example="demo"),
     *                 @SWG\Property(property="is_valid", type="boolean", description=""),
     *                 @SWG\Property(property="h5_url", type="string", description=""),
     *                 @SWG\Property(property="due_reminder", type="boolean", description=""),
     *                 @SWG\Property(property="php_ecshopx_version", type="string", description="", example="standard"),
     *             ),
     *       )
     *   ),
     *
     *    @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     *   ),
     * )
     */



    public function getActivateInfo()
    {
        $companyId = app('auth')->user()->get('company_id');
        $companysActivationEgo = new CompanysActivationEgo();
        $result = $companysActivationEgo->check($companyId);
        $result['h5_url'] = trim(config('common.h5_base_url'), '/') . '?company_id=' . $companyId;
        // 到期提醒
        $result['due_reminder'] = false;
        if ($result['expired_at'] - time() < 3600 * 24 * 30) {
            $result['due_reminder'] = true;
        }
        $result['php_ecshopx_version'] = $result['product_model'];
        // 代码版本，独立部署才有
        $version_path = base_path('composer.json');
        $composer = is_file($version_path) ? file_get_contents($version_path) : '-';
        $composer = json_decode($composer, true);
        $result['version'] = $composer['version'] ?? '-';
        $result['php_version'] = PHP_VERSION;
        $result['os'] = php_uname('s');
        $result['web_server'] = $_SERVER['SERVER_SOFTWARE'];
        $result['db_version'] = app('registry')->getConnection('default')->fetchAll("select version()")[0]['version()'];
        $result['lumen_version'] = app()->version();
        $result['app_url'] = env('APP_URL');
        $result['disk_driver'] = env('DISK_DRIVER');
        $result['redis_version'] = app('redis')->getProfile()->getVersion();
        // 检查license有效期
        $result['license']['show_expier_tip'] = 0;
        /**
        if (extension_loaded('swoole_loader')) {
            $license = swoole_get_license();
            $license = reset($license);
            if (isset($license['expire_at'])) {
                $diff = $license['expire_at'] - time();
                $result['license']['left_time'] = floor($diff / 86400);
                if ($result['license']['left_time'] <= 30) {
                    $result['license']['show_expier_tip'] = 1;
                }
            }
        }
        **/
        return $this->response->array($result);
    }

    /**
     * @SWG\Patch(
     *     path="/company",
     *     summary="更新企业信息",
     *     tags={"企业"},
     *     description="更新企业信息",
     *     operationId="updateCompanyInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="industry",
     *         in="query",
     *         description="所属行业",
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
     *                     @SWG\Property(property="industry", type="string"),
     *                     @SWG\Property(property="company_id", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function updateCompanyInfo(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        if (!$companyId) {
            throw new ResourceException("无相关企业信息！");
        }
        $params = $request->all('industry');
        $params['company_id'] = $companyId;
        $filter['company_id'] = $companyId;

        $result = $this->companysService->updateInfo($filter, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/companys/setting",
     *     summary="获取商品配置信息",
     *     tags={"企业"},
     *     description="获取商品配置信息",
     *     operationId="getCompanySetting",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\items(
     *                     type="object",
     *                     @SWG\property(property="company_id", type="integer", example="1", description="公司id"),
     *                     @SWG\property(property="community_config", type="object",
     *                         @SWG\property(property="point_ratio", type="string", example="1", description="是否返回积分提现比例"),
     *                         @SWG\property(property="point_desc", type="string", example="1", description="是否返回积分说明"),
     *                         @SWG\property(property="withdraw_desc", type="string", example="1", description="提现说明"),
     *                     ),
     *                     @SWG\property(property="withdraw_bank", type="object", description="提现支持银行类型",
     *                         @SWG\property(property="alipay", type="boolean", description=""),
     *                         @SWG\property(property="wechatpay", type="boolean", description=""),
     *                         @SWG\property(property="bankpay", type="boolean", description=""),
     *                     ),
     *                     @SWG\property(property="consumer_hotline", type="string", example="189156112313332", description="客服电话"),
     *                     @SWG\property(property="customer_switch", type="integer", example="1", description="客服开关"),
     *                     @SWG\property(property="fapiao_config", type="object", description="发票配置",
     *                         @SWG\property(property="fapiao_switch", type="boolean", description="发票开关"),
     *                         @SWG\property(property="content", type="string", description="内容", example="上海航信模拟测试"),
     *                         @SWG\property(property="tax_rate", type="string", description="税率", example="13"),
     *                         @SWG\property(property="registration_number", type="string", description="税号", example="税号"),
     *                         @SWG\property(property="bankname", type="string", description="银行名", example=""),
     *                         @SWG\property(property="bankaccount", type="string", description="银行账号", example=""),
     *                         @SWG\property(property="company_phone", type="string", description="电话", example=""),
     *                         @SWG\property(property="user_name", type="string", description="开票员", example="开票员"),
     *                         @SWG\property(property="company_address", type="string", description="公司地址", example=""),
     *                         @SWG\property(property="enterprise_id", type="string", description="企业ID", example=""),
     *                         @SWG\property(property="group_id", type="string", description="客服组ID", example=""),
     *                         @SWG\property(property="hangxin_tax_no", type="string", description="", example=""),
     *                         @SWG\property(property="hangxin_auth_code", type="string", description="", example="]"),
     *                         @SWG\property(property="NSRSBH", type="string", description="开票方识别号", example="]"),
     *                         @SWG\property(property="FPQQLSH", type="string", description="发票请求唯一流水号", example=""),
     *                         @SWG\property(property="DSPTBM", type="string", description="平台编码", example=""),
     *                         @SWG\property(property="XHF_NSRSBH", type="string", description="销货方识别号", example=""),
     *                         @SWG\property(property="hangxin_switch", type="string", description="", example=""),
     *                         @SWG\property(property="authorizationCode", type="string", description="", example=""),
     *                     ),
     *                     @SWG\property(property="fapiao_switch", type="integer", example="1", description="发票开关"),
     *                     @SWG\property(property="created", type="string", example="1567926985", description=""),
     *                     @SWG\property(property="updated", type="string", example="1605061426", description=""),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    public function getCompanySetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        // $result = $this->companysService->getCompanySetting($companyId);
        // 获取自营店信息
        $shopsService = new ShopsService(new WxShopsService());
        $result = $shopsService->getWxShopsSetting($companyId);
        // 获取总店信息
        $selfDistributorInfo = (new DistributorService())->getDistributorSelf($companyId, true);

        if (!empty($selfDistributorInfo) && $result) {
            $result["brand_name"] = $selfDistributorInfo["name"] ?? "";
            $result["logo"] = $selfDistributorInfo["logo"] ?? "";
        }

        $thirdSettingService = new ThirdSettingService();
        $result['is_open_erp'] = $thirdSettingService->getShopexErpSetting($companyId)['is_open'] ?? false;

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/company/domain_setting",
     *     summary="获取域名配置信息",
     *     tags={"企业"},
     *     description="获取域名配置信息",
     *     operationId="getDomainSetting",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\items(
     *                     type="object",
     *                     @SWG\property(property="company_id", type="integer", example="1", description="公司id"),
     *                     @SWG\property(property="pc_domain", type="string", example="www.shopex.cn", description="PC域名"),
     *                     @SWG\property(property="h5_domain", type="string", example="www.shopex.cn", description="H5域名"),
     *                     @SWG\property(property="h5_default_domain", type="string", example="www.shopex.cn", description="H5默认域名"),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getDomainSetting(Request $request)
    {
        $result = [];
        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $result = $this->companysService->getDomainInfo($filter);
        $result['company_id'] = $companyId;

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/company/domain_setting",
     *     summary="保存域名配置信息",
     *     tags={"企业"},
     *     description="保存域名配置信息",
     *     operationId="setDomainSetting",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\parameter( name="pc_domain", in="query", description="PC域名", type="string", required=false),
     *     @SWG\parameter( name="h5_domain", in="query", description="H5域名", type="string", required=false),
     *     @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\items(
     *                     type="object",
     *                     @SWG\property(property="company_id", type="integer", example="1", description="公司id"),
     *                     @SWG\property(property="pc_domain", type="string", example="www.shopex.cn", description="PC域名"),
     *                     @SWG\property(property="h5_domain", type="string", example="www.shopex.cn", description="H5域名"),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setDomainSetting(Request $request)
    {
        $filter = [];
        $data = [];

        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('pc_domain', 'h5_domain');
        if (isset($params['pc_domain'])) {
            $data['pc_domain'] = $params['pc_domain'];
            $data['pc_domain'] = str_replace(['http://', 'https://'], '', $data['pc_domain']);
            $companyInfo = $this->companysService->getCompanyInfoByDomain($data['pc_domain']);
            if ($companyInfo && isset($companyInfo['company_id']) && $companyInfo['company_id'] != $companyId) {
                throw new ResourceException('域名被占用：'.$data['pc_domain']);
            }
        }

        if (isset($params['h5_domain'])) {
            $data['h5_domain'] = $params['h5_domain'];
            $data['h5_domain'] = str_replace(['http://', 'https://'], '', $data['h5_domain']);
            $companyInfo = $this->companysService->getCompanyInfoByDomain($data['h5_domain']);
            if ($companyInfo && isset($companyInfo['company_id']) && $companyInfo['company_id'] != $companyId) {
                throw new ResourceException('域名被占用：'.$data['h5_domain']);
            }
        }

        $filter['company_id'] = $companyId;
        $result = $this->companysService->updateDomainInfo($filter, $data);
        $data['company_id'] = $companyId;

        return $this->response->array($data);
    }
    
    /**
     * @SWG\Get(
     *     path="/company/applications",
     *     summary="查询应用列表",
     * )
     */
    public function getApplications(Request $request)
    {
        return app('authorization')->getApplications();
    }

    /**
     * @SWG\Get(
     *     path="/company/privacy_setting",
     *     summary="获取PC商城和H5商城隐私设置",
     *     tags={"企业"},
     *     description="获取PC商城和H5商城隐私设置",
     *     operationId="getPrivacySetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="pc_privacy_content", type="string", example="", description="PC商城隐私内容"),
     *                 @SWG\Property(property="h5_privacy_content", type="string", example="", description="H5商城隐私内容"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getPrivacySetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        if (!$companyId) {
            throw new ResourceException("无相关企业信息！");
        }
        $result = $this->settingService->getPrivacySetting($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/company/privacy_setting",
     *     summary="设置PC商城和H5商城隐私设置",
     *     tags={"企业"},
     *     description="设置PC商城和H5商城隐私设置",
     *     operationId="setPrivacySetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="pc_privacy_content",
     *         in="query",
     *         description="PC商城隐私内容",
     *         type="string",
     *         required=false,
     *     ),
     *     @SWG\Parameter(
     *         name="h5_privacy_content",
     *         in="query",
     *         description="H5商城隐私内容",
     *         type="string",
     *         required=false,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="pc_privacy_content", type="string", example="", description="PC商城隐私内容"),
     *                 @SWG\Property(property="h5_privacy_content", type="string", example="", description="H5商城隐私内容"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setPrivacySetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        if (!$companyId) {
            throw new ResourceException("无相关企业信息！");
        }
        $params = $request->all('pc_privacy_content', 'h5_privacy_content');
        $result = $this->settingService->setPrivacySetting($companyId, $params);
        return $this->response->array($result);
    }

    
}
