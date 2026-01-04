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

namespace WechatBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use CompanysBundle\Services\CommonLangModService;
use CompanysBundle\Services\SettingService as CompanysSettingService;
use CompanysBundle\Services\Shops\WxShopsService;
use CompanysBundle\Services\ShopsService;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\ValidationHttpException;
use EspierBundle\Services\LoginService;
use GoodsBundle\Services\ItemsCategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ImBundle\Services\EChatService;
use ImBundle\Services\ImService;
use MembersBundle\Services\MemberRegSettingService;
use MembersBundle\Services\UserService;
use MembersBundle\Services\WechatUserService;
use PointBundle\Services\PointMemberRuleService;
use PopularizeBundle\Services\PromoterService;
use PopularizeBundle\Services\SettingService;
use PromotionsBundle\Services\DistributorPromotionService;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;
use ThemeBundle\Services\PagesTemplateSetServices;
use WechatBundle\Services\OauthService;
use WechatBundle\Services\OpenPlatform;
use WechatBundle\Services\Wxapp\CustomizePageService;
use WechatBundle\Services\Wxapp\TemplateService;
use WechatBundle\Services\WeappService;

class Wxapp extends Controller
{
    protected $openPlatform;

    public function __construct()
    {
        $this->openPlatform = new OpenPlatform();
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/oauthlogin",
     *     summary="小程序oauth授权登录",
     *     tags={"微信"},
     *     description="小程序oauth授权登录",
     *     operationId="oauthlogin",
     *     @SWG\Parameter( name="code", in="query", description="登录时获取的 code", required=true, type="string"),
     *     @SWG\Parameter( name="appid", in="query", description="小程序唯一标识", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function checkOauthLogin(Request $request)
    {
        /**
         * 4.server调用微信提供的jsoncode2session接口获取openid, session_key, 调用失败应给予客户端反馈
         * , 微信侧返回错误则可判断为恶意请求, 可以不返回. 微信文档链接
         * 这是一个 HTTP 接口，开发者服务器使用登录凭证 code 获取 session_key 和 openid。其中 session_key 是对用户数据进行加密签名的密钥。
         * 为了自身应用安全，session_key 不应该在网络上传输。
         * 接口地址："https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code"
         */
        $inputData = $request->input();
        if (empty($inputData['appid'])) {
            throw new StoreResourceFailedException('缺少参数，登录失败！');
        }
        $app = $this->openPlatform->getAuthorizerApplication($inputData['appid']);

        //调用微信获取sessionkey接口，返回session_key,openid,unionid
        $wxParams = [
            'code' => $inputData['code'],
            'appid' => $inputData['appid'],
        ];
        $res = $app->auth->session($inputData['code']);

        if (!isset($res['openid'])) {
            throw new StoreResourceFailedException('小程序信息错误，请联系供应商！');
        }

        $wechatUserService = new WechatUserService();
        $wechatUserInfo = $wechatUserService->getSimpleUser(['open_id' => $res['openid'], 'authorizer_appid' => $inputData['appid']]);

        $oauthService = new OauthService();
        $result = $oauthService->accessTokenSweep($wechatUserInfo['unionid'], $inputData['token']);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/oauth/login/authorize",
     *     summary="小程序oauth授权登录确认",
     *     tags={"微信"},
     *     description="小程序oauth授权登录确认",
     *     operationId="authorizeOauthLogin",
     *     @SWG\Parameter( name="token", in="query", description="扫码获取的token", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="query", description="登录时获取的 code", required=true, type="string"),
     *     @SWG\Parameter( name="appid", in="query", description="小程序唯一标识", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="小程序确认授权 1 取消授权 0", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function authorizeOauthLogin(Request $request)
    {
        $oauthService = new OauthService();
        $input = $request->input();
        $status = $oauthService->accessTokenAuthorize($input);
        return $this->response->array(['status' => $status]);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/oauth/login/valid",
     *     summary="小程序oauth授权登录状态获取 pc接口",
     *     tags={"微信"},
     *     description="小程序oauth授权登录状态获取 pc接口",
     *     operationId="authorizeOauthLogin",
     *     @SWG\Parameter( name="token", in="query", description="扫码获取的token", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="query", description="登录时获取的 code", required=true, type="string"),
     *     @SWG\Parameter( name="appid", in="query", description="小程序唯一标识", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="小程序确认授权 1 取消授权 0", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function validOauthLogin(Request $request)
    {
        // 0x53686f704578
        $oauthService = new OauthService();
        $token = $request->input('token');
        $info = $oauthService->getAccessTokenValid($token);
        return $this->response->array($info);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/pageparams/setting",
     *     summary="获取小程序页面配置信息",
     *     tags={"微信"},
     *     description="获取小程序页面配置信息",
     *     operationId="getParamByTempName",
     *     @SWG\Parameter( name="template_name", in="query", description="配置的小程序模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="version", in="query", description="配置的小程序模版名称", required=false, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="配置规则名称", required=false, type="string"),
     *     @SWG\Parameter( name="page_name", in="query", description="页面名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="80216"),
     *                          @SWG\Property( property="template_name", type="string", example="yykweishop", description="模板名称"),
     *                          @SWG\Property( property="company_id", type="string", example="1"),
     *                          @SWG\Property( property="name", type="string", example="base", description="名称"),
     *                          @SWG\Property( property="page_name", type="string", example="color_style", description="页面名称"),
     *                          @SWG\Property( property="base_config", type="string", example="", description="商家基础配置"),
     *                          @SWG\Property( property="params", type="object",
     *                                  @SWG\Property( property="name", type="string", example="base"),
     *                                  @SWG\Property( property="data", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="primary", type="string", example="#FF4281"),
     *                                          @SWG\Property( property="accent", type="string", example="#64FFFA"),
     *                                          @SWG\Property( property="marketing", type="string", example="#FFA84B"),
     *                                       ),
     *                                  ),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="config", type="array",
     *                      @SWG\Items( type="string", example="undefined", description="自行更改字段描述"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getParamByTempName(Request $request)
    {
        $authInfo = $request->get('auth');
        $templateName = $request->input('template_name');
        $settingService = new TemplateService();

        $name = $request->input('name', null);
        $pageName = $request->input('page_name', 'index');
        $version = $request->input('version', 'v1.0.0');
        $distributorId = $request->input('distributor_id', 0);
        $custom = false;
        if (strpos($pageName, 'custom_') !== false) {
            $customizePageService = new CustomizePageService();
            $custom_id = (str_replace("custom_", "", $pageName));
            if ('salesperson' == $custom_id) {
                $custom_id = $customizePageService->getSalespersonCustomId($authInfo['company_id'], $templateName);
            }
            if ('my' == $custom_id) {
                $custom_id = $customizePageService->getMyCustomId($authInfo['company_id']);
            }            
            $custom_id = intval($custom_id);
            if (!$custom_id) {
                throw new StoreResourceFailedException('小程序模板不存在');
            }
            $pageName = 'custom_'.$custom_id;
            $custom = true;
        }
        $list = $settingService->getTemplateConf($authInfo['company_id'], $templateName, $pageName, $name, $version, ($authInfo['user_id'] ?? 0), $distributorId);
        // 过滤掉已关闭的参数
        foreach ($list as $k => $item) {
            if (isset($item['params']['is_open']) && $item['params']['is_open'] == false) {
                $list[$k]['params']['data'] = [];
            }
        }

        if ($pageName == 'category' && $distributorId) {
            $itemsCategoryService = new ItemsCategoryService();
            $filter = [
                'company_id' => $authInfo['company_id'],
                'distributor_id' => $distributorId
            ];
            $distributorCateList = $itemsCategoryService->getItemsCategory($filter);
            if ($distributorCateList) {
                $distributorCateList = $itemsCategoryService->processingParams($distributorCateList);
                $list[0]['params']['data'] = $distributorCateList;
            }
        }
        if (!$name) {
            $return['list'] = $list;
            $config = [];
            foreach ($list as $row) {
                if (isset($row['params']['name']) && isset($row['params']['base'])) {
                    $config[] = $row['params'];
                }
            }
            $return['config'] = $config;
            if ($custom) {
                $return['share'] = $customizePageService->getInfoById($custom_id);
            }
        } else {
            $return = $list;
        }

        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/share/setting",
     *     summary="获取小程序分享配置信息",
     *     tags={"微信"},
     *     description="获取小程序分享配置信息",
     *     operationId="getShareSetting",
     *     @SWG\Parameter( name="shareindex", in="query", description="分享类型", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="title", type="string", example="首页测试", description="标题"),
     *                  @SWG\Property( property="desc", type="string", example="首页自定义分享副标题", description="描述"),
     *                  @SWG\Property( property="imageUrl", type="string", example="http://bbctest.aixue7.com/image/1/2020/07/21/31ce68b58a80444a56be8dfb78fa66f233QWQ92ZhTFYfeeTiAoj0BNLqwaIgUYI", description="图片网址"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getShareSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $shareIndex = $request->input('shareindex', 'index');
        $key = 'shareSetting:'. $authInfo['company_id'];
        $data = app('redis')->connection('companys')->get($key);
        $data = $data ? json_decode($data, true) : [];
        // 多语言处理
        $ns = new CommonLangModService();
        $data = $ns->getLangDataIndexLang($data);
        $return = $data[$shareIndex] ?? [
            'title' => '',
            'desc' => '',
            'imageUrl' => '',
        ];
        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxa/promotion/articles",
     *     summary="获取小程序首页营销文章",
     *     tags={"微信"},
     *     description="获取小程序首页营销文章",
     *     operationId="getPromotionArticles",
     *     @SWG\Parameter( name="authorizer-appid", in="header", description="小程序appid(小程序访问此参必填)", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司company_id(h5app端必填)", type="integer"),
     *     @SWG\Parameter( name="template_name", in="query", description="配置的小程序模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="配置规则名称", required=false, type="string"),
     *     @SWG\Parameter( name="page_name", in="query", description="页面名称", default="index", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getPromotionArticles(Request $request)
    {
        $authInfo = $request->get('auth');
        $templateName = $request->input('template_name');
        $settingService = new TemplateService();

        $name = $request->input('name', null);
        $pageName = $request->input('page_name', 'index');

        $list = $settingService->getTemplateConf($authInfo['company_id'], $templateName, $pageName, $name);
        if ($list) {
            foreach ($list[0]['params'] as &$row) {
                if (isset($row['viewcontent'])) {
                    unset($row['viewcontent']);
                }

                if (isset($row['content'])) {
                    unset($row['content']);
                }
            }
        }
        return $this->response->array($list);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxa/promotion/articles/info",
     *     summary="获取小程序首页营销文章详情",
     *     tags={"微信"},
     *     description="获取小程序首页营销文章详情",
     *     operationId="getPromotionArticlesInfo",
     *     @SWG\Parameter( name="template_name", in="query", description="配置的小程序模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="配置规则名称", required=true, type="string"),
     *     @SWG\Parameter( name="index", in="query", description="文章index", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getPromotionArticlesInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $templateName = $request->input('template_name');
        $settingService = new TemplateService();

        $name = $request->input('name', null);
        $index = $request->input('index');
        $pageName = $request->input('page_name', 'index');

        $list = $settingService->getTemplateConf($authInfo['company_id'], $templateName, $pageName, $name);
        $res = [];
        if ($list) {
            $res = $list[0]['params'][$index];
            if (isset($res['viewcontent'])) {
                unset($res['viewcontent']);
            }
        }
        return $this->response->array($res);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/oauth/getredirecturl",
     *     summary="获取授权跳转地址",
     *     tags={"微信"},
     *     description="获取授权跳转地址",
     *     operationId="oauthRedirectUrl",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="redirect_url", type="string", example="https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx6b8c2837f47e8a09&redirect_uri=https%3A%2F%2Fecshopx.shopex123.com%2F&response_type=code&scope=snsapi_base&state=aac9f39692643f92c41afe6006e0a6e6&component_appid=wx79e3aa8bf85eaf8a#wechat_redirect"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function oauthRedirectUrl(Request $request)
    {
        $authInfo = $request->get('auth');

        $companyId = $authInfo['company_id'];
        $url = $request->get('url');

        $WoaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);
        $app = $this->openPlatform->getAuthorizerApplication($WoaAppid);
        $redirect_url = $app->oauth->scopes(['snsapi_base'])
                        ->redirect($url);
        return $this->response->array(['redirect_url' => $redirect_url]);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/oauth/getopenid",
     *     summary="获取静默登录openid",
     *     tags={"微信"},
     *     description="获取静默登录openid",
     *     operationId="getOpenId",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="open_id", type="string", example=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getOpenId(Request $request)
    {
        $code = $request->get('code', '');
        if (empty($code)) {
            throw new StoreResourceFailedException('缺少参数');
        }
        $authInfo = $request->get('auth');

        $companyId = $authInfo['company_id'];

        $WoaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);
        $app = $this->openPlatform->getAuthorizerApplication($WoaAppid);

        // $user = $app->oauth->user();
        $user = $app->oauth->userFromCode($code);
        $tokenResponse = $user->getTokenResponse();
        return $this->response->array(['open_id' => $tokenResponse['openid']]);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/newtemplate",
     *     summary="获取小程序订阅消息模板列表",
     *     tags={"微信"},
     *     description="获取小程序订阅消息模板列表",
     *     operationId="getWxaNewTmpl",
     *     @SWG\Parameter( name="source_type", in="query", description="发起订阅类型", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="template_id", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getWxaNewTmpl(Request $request)
    {
        $authInfo = $request->get('auth');
        $sourceType = $request->input('source_type');
        $tempName = $request->get('temp_name');
        $result['template_id'] = [];
        if (!$sourceType || !$tempName) {
            return $this->response->array($result);
        }
        $filter = [
            'company_id' => $authInfo['company_id'],
            'template_name' => $tempName,
        ];
        $lists = app('wxaTemplateMsg')->getValidTempLists($filter, $sourceType);
        if ($lists) {
            $result['template_id'] = array_column($lists, 'template_id');
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/getopenid",
     *     summary="小程序静默授权获取openid和unionid",
     *     tags={"微信"},
     *     description="小程序静默授权获取openid和unionid",
     *     operationId="getUserOpentIdAndUnionid",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="query", description="登录时获取的 code", required=true, type="string"),
     *     @SWG\Parameter( name="appid", in="query", description="小程序唯一标识", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getUserOpentIdAndUnionid(Request $request)
    {
        if ($authInfo = $request->get('auth')) {
            $companyId = $authInfo['company_id'];

            $WoaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);
            $app = $this->openPlatform->getAuthorizerApplication($WoaAppid);

            $user = $app->oauth->user();
            $open_id = $user->getId();
            if (!$open_id) {
                return $this->response->array(['openid' => '', 'unionid' => '']);
            }
        } else {
            $inputData = $request->input();
            if (empty($inputData['appid'])) {
                throw new StoreResourceFailedException('缺少参数，登录失败！');
            }
            $app = $this->openPlatform->getAuthorizerApplication($inputData['appid']);
            $WoaAppid = $inputData['appid'];
            $wxParams = [
                'code' => $inputData['code'],
                'appid' => $inputData['appid'],
            ];
            $res = $app->auth->session($inputData['code']); //调用微信获取sessionkey接口，返回session_key,openid,unionid
            if (!isset($res['openid'])) {
                return $this->response->array(['openid' => '', 'unionid' => '']);
            }
            $open_id = $res['openid'];
        }
        $result['openid'] = $open_id;
        $result['unionid'] = '';
        $wechatUserService = new WechatUserService();
        $wechatUserInfo = $wechatUserService->getSimpleUser(['open_id' => $open_id, 'authorizer_appid' => $WoaAppid]);
        if ($wechatUserInfo['unionid'] ?? '') {
            $result['unionid'] = $wechatUserInfo['unionid'];
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/membercenter/setting",
     *     summary="获取小程序会员中心配置信息",
     *     tags={"微信"},
     *     description="获取小程序会员中心配置信息",
     *     operationId="getMemberCenterParamByTempName",
     *     @SWG\Parameter( name="template_name", in="query", description="配置的小程序模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="version", in="query", description="配置规则名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="params", type="object",
     *                                  @SWG\Property( property="data", type="object",
     *                                          @SWG\Property( property="ziti_order", type="string", example="true"),
     *                                          @SWG\Property( property="ext_info", type="string", example="true"),
     *                                          @SWG\Property( property="group", type="string", example="true"),
     *                                          @SWG\Property( property="boost_activity", type="string", example="true"),
     *                                          @SWG\Property( property="boost_order", type="string", example="true"),
     *                                          @SWG\Property( property="complaint", type="string", example="true"),
     *                                          @SWG\Property( property="activity", type="string", example="true"),
     *                                          @SWG\Property( property="recharge", type="string", example="true"),
     *                                          @SWG\Property( property="member_code", type="string", example="true"),
     *                                  ),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getMemberCenterParamByTempName(Request $request)
    {
        $authInfo = $request->get('auth');
        $templateName = $request->input('template_name');
        $settingService = new TemplateService();

        $version = $request->input('version', 'v1.0.0');
        $pageName = ['member_center_setting', 'member_center_redirect_setting', 'member_center_menu_setting'];

        $list = $settingService->getTemplateConf($authInfo['company_id'], $templateName, $pageName, null, $version, ($authInfo['user_id'] ?? 0));
        $return = [];
        $list1 = array_column($list, 'params', 'page_name');
        if (!isset($list1['member_center_menu_setting'])) {
            $menu_setting = [
                'member_center_menu_setting' => [
                    'data' => [
                        'ziti_order' => true,
                        'ext_info' => true,
                        'group' => true,
                        'boost_activity' => true,
                        'boost_order' => true,
                        'complaint' => true,
                        'activity' => true,
                        'recharge' => true,
                        'member_code' => true,
                    ]
                ]
            ];
            $list1 = array_merge($list1, $menu_setting);
        }
        foreach ($list1 as $k => $v) {
            $return = array_merge($return, $v['data']);
        }
        $result['list'][]['params']['data'] = $return;
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/common/setting",
     *     summary="获取小程序公共配置信息",
     *     tags={"微信"},
     *     description="获取小程序公共配置信息",
     *     operationId="getCommonSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         in="path",
     *         description="客服类型 backend frontend",
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *             @SWG\Property( property="meiqia", type="object",
     *                          @SWG\Property( property="is_open", type="string", example="false", description="美洽是否开启 true:开启,false:关闭"),
     *                          @SWG\Property( property="meiqia_url", type="string", example="false", description="美洽客服链接"),
     *                          @SWG\Property( property="is_distributor_open", type="string", example="false", description="开启店铺客服 true:开启,false:关闭"),
     *                          @SWG\Property( property="enterprise_id", type="string", example="", description="企业 ID"),
     *                          @SWG\Property( property="group_id", type="string", example="", description="客服组 ID"),
     *                          @SWG\Property( property="persion_ids", type="string", example="", description="客服 ID"),
     *                          @SWG\Property( property="type", type="string", example="all", description="im配置类型: all backend frontend"),
     *                  ),
     *                  @SWG\Property( property="echat", type="object",
     *                          @SWG\Property( property="is_open", type="string", example="false", description="一洽是否开启 true:开启,false:关闭"),
     *                          @SWG\Property( property="echat_url", type="string", example="", description="echat链接"),
     *                  ),
     *                  @SWG\Property( property="nostores_status", type="boolean", example="true", description="前端店铺展示是否关闭 true:关闭 false:不关闭"),
     *                  @SWG\Property( property="whitelist_status", type="boolean", example="false", description="是否开启白名单 true:开启 false:关闭"),
     *                  @SWG\Property( property="distributor_param_status", type="boolean", example="false", description="是否带门店参数 true:开启 false:关闭"),
     *                  @SWG\Property( property="disk_driver", type="string", example="qiniu", description="文件存储"),
     *                  @SWG\Property( property="point_rule_name", type="string", example="积分", description="积分规则名称"),
     *              ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
    */
    public function getCommonSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        $imService = new ImService();
        $meiqiaSetting = $imService->getImInfo($companyId);

        $echatService = new EChatService();
        $echatSetting = $echatService->getInfo($companyId);

        $settingService = new CompanysSettingService();
        $nostoresSetting = $settingService->getNostoresSetting($companyId);
        $whitelistSetting = $settingService->getWhitelistSetting($companyId);

        //获取分享参数配置
        $shareParametersSetting = $settingService->getShareParametersSetting($companyId);

        // 获取积分规则
        $pointMemberRuleService = new PointMemberRuleService();
        $pointRuleInfo = $pointMemberRuleService->getPointRule($companyId);

        $categoryPageSetting = $settingService->getCategoryPageSetting($companyId);

        // 获取达摩CRM配置
        $dmCrmSettingService = new DmCrmSettingService();
        $dmCrmSetting = $dmCrmSettingService->getDmCrmSetting($companyId);

        $result['meiqia'] = $meiqiaSetting;
        $result['echat'] = $echatSetting;
        $result['nostores_status'] = $nostoresSetting['nostores_status'];
        $result['whitelist_status'] = $whitelistSetting['whitelist_status'];
        $result['distributor_param_status'] = $shareParametersSetting['distributor_param_status'];
        $result['disk_driver'] = env('DISK_DRIVER');
        $result['point_rule_name'] = $pointRuleInfo['name'];
        $result['category_style'] = $categoryPageSetting['style'];
        $result['dmcrm_is_open'] = $dmCrmSetting['is_open'] ?? false;

        return $this->response->array($result);
    }

    /**
    * @SWG\Get(
    *     path="/h5app/wxapp/cartremind/setting",
    *     summary="获取小程序购物车提醒配置信息",
    *     tags={"微信"},
    *     description="获取小程序购物车提醒配置信息",
    *     operationId="getCartremindSetting",
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
    *          @SWG\Property( property="data", type="object",
    *                  @SWG\Property( property="is_open", type="boolean", example=true, description="是否开启"),
    *                  @SWG\Property( property="remind_content", type="string", example="提醒内容", description="提醒内容"),
    *          ),
    *     )),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
    * )
    */
    public function getCartremindSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $templateService = new TemplateService();
        $data = $templateService->getCartremindSetting($authInfo['company_id']);
        return $this->response->array($data);
    }

    /**
      * @SWG\Get(
      *     path="wxapp/getbyshareid",
      *     summary="根据share_id获取参数",
      *     tags={"微信"},
      *     description="根据share_id获取参数",
      *     operationId="getByShareId",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
      *     @SWG\Parameter( name="share_id", in="query", description="分享id", required=true, type="string" ),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
      *          @SWG\Property( property="data", type="object",
      *          ),
      *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
      * )
      */
    public function getByShareId(Request $request)
    {
        $authInfo = $request->get('auth');
        $shareId = $request->input('share_id');
        if (!$shareId) {
            throw new ValidationHttpException('参数错误');
        }
        $weappService = new WeappService();
        $params = $weappService->getByShareId($authInfo['company_id'], $shareId);
        return $this->response->array($params);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/pagestemplate/baseinfo",
     *     summary="获取小程序模板基础配置",
     *     tags={"微信"},
     *     description="包含小程序配置、小程序导航配置、风格配色",
     *     operationId="getPagestemplateBaseinfo",
     *     @SWG\Parameter( name="template_name", in="query", description="配置的小程序模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="version", in="query", description="配置的小程序模版名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="color_style", type="object",
     *                          @SWG\Property( property="primary", type="string", example="#FF4444", description="风格配色-主色调"),
     *                          @SWG\Property( property="accent", type="string", example="#FF8855", description="风格配色-转色调"),
     *                          @SWG\Property( property="marketing", type="string", example="#B3B3B3", description="风格配色-会员色"),
     *                  ),
     *                  @SWG\Property( property="id", type="string", example="1", description=""),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                  @SWG\Property( property="index_type", type="integer", example="1", description="首页类型 1总部首页 2店铺首页"),
     *                  @SWG\Property( property="is_enforce_sync", type="integer", example="1", description="店铺首页同步状态 1强制同步 2非强制同步"),
     *                  @SWG\Property( property="is_open_recommend", type="integer", example="2", description="开启猜你喜欢 1开启 2关闭"),
     *                  @SWG\Property( property="is_open_wechatapp_location", type="integer", example="1", description="开启小程序定位 1开启 2关闭"),
     *                  @SWG\Property( property="is_open_scan_qrcode", type="integer", example="2", description="开启扫码功能 1开启 2关闭"),
     *                  @SWG\Property( property="tab_bar", type="string", example="", description="小程序菜单设置 JSON"),
     *                  @SWG\Property( property="is_open_official_account", type="string", example="2", description="开启关注公众号组件 1开启 2关闭"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getPagestemplateBaseinfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $templateName = $request->input('template_name');
        $settingService = new TemplateService();

        $version = $request->input('version', 'v1.0.0');
        // 获取风格配色
        $list = $settingService->getTemplateConf($authInfo['company_id'], $templateName, 'color_style', '', $version, ($authInfo['user_id'] ?? 0));
        $return = [
            'color_style' => [
                'primary' => $list[0]['params']['data'][0]['primary'] ?? '',
                'accent' => $list[0]['params']['data'][0]['accent'] ?? '',
                'marketing' => $list[0]['params']['data'][0]['marketing'] ?? '',
            ],
        ];

        // 获取小程序配置、小程序导航配置
        $pages_template_set_services = new PagesTemplateSetServices();
        $pagestemplateSet = $pages_template_set_services->getInfo(['company_id' => $authInfo['company_id']]);
        $return = array_merge($return, $pagestemplateSet);

        // 获取商城名称显示在小程序顶部
        $shopsService = new ShopsService(new WxShopsService());
        $wxShop = $shopsService->getWxShopsSetting($authInfo['company_id']);
        $return['title'] = $wxShop['brand_name'] ?? '';

        return $this->response->array($return);
    }


    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/pagestemplate/membercenter",
     *     summary="获取小程序会员中心配置信息",
     *     tags={"微信"},
     *     description="包含会员中心BANNER、菜单隐藏显示设置、页面跳转设置",
     *     operationId="getPagestemplateMembercenter",
     *     @SWG\Parameter( name="template_name", in="query", description="配置的小程序模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="version", in="query", description="配置规则名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="point_url_is_open", type="string", example="boolean", description="“积分”点击跳转设置 -- 外部链接是否开启 true:开启,false:关闭"),
     *              @SWG\Property( property="point_app_id", type="string", example="wx4f25fb911393034b", description="“积分”点击跳转设置 -- 外部链接小程序appid"),
     *              @SWG\Property( property="point_page", type="string", example="pages/item/list", description="“积分”点击跳转设置 -- 外部链接路径"),
     *              @SWG\Property( property="info_url_is_open", type="string", example="boolean", description="“个人信息”点击跳转设置 -- 外部链接是否开启 true:开启,false:关闭"),
     *              @SWG\Property( property="info_app_id", type="string", example="wx4f25fb911393034b", description="“个人信息”点击跳转设置 -- 外部链接小程序appid"),
     *              @SWG\Property( property="info_page", type="string", example="pages/item/list", description="“个人信息”点击跳转设置 -- 外部链接路径"),
     *              @SWG\Property( property="no_login_banner", type="string", example="https://bbctest.aixue7.com/image/1/2021/07/29/ab7ea1e7187839389f087acdc3228428ztStppxDZzh7YPM1zTNovd8tgFifS112", description="会员中心BANNER -- 未登录BANNER"),
     *              @SWG\Property( property="login_banner", type="string", example="https://bbctest.aixue7.com/image/1/2021/07/29/ab7ea1e7187839389f087acdc3228428ztStppxDZzh7YPM1zTNovd8tgFifS112", description="会员中心BANNER -- 已登录BANNER"),
     *              @SWG\Property( property="is_show", type="boolean", example="false", description="会员中心BANNER -- 是会员中心是否展示 true:展示,false:不展示"),
     *              @SWG\Property( property="url_is_open", type="boolean", example="false", description="会员中心BANNER -- 外部链接是否开启 true:开启,false:关闭"),
     *              @SWG\Property( property="app_id", type="string", example="wx4f25fb911393034b", description="会员中心BANNER -- 外部链接小程序appid"),
     *              @SWG\Property( property="page", type="string", example="pages/item/list", description="会员中心BANNER -- 外部链接路径"),
     *              @SWG\Property( property="ziti_order", type="boolean", example="true", description="是否展示自提订单 -- "),
     *              @SWG\Property( property="ext_info", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示推广信息"),
     *              @SWG\Property( property="group", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示我的拼团"),
     *              @SWG\Property( property="boost_activity", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示助力活动"),
     *              @SWG\Property( property="boost_order", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示助力订单"),
     *              @SWG\Property( property="complaint", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示投诉记录"),
     *              @SWG\Property( property="activity", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示活动预约"),
     *              @SWG\Property( property="recharge", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示储值"),
     *              @SWG\Property( property="member_code", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示会员二维码"),
     *              @SWG\Property( property="community_activity", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示社区团购"),
     *              @SWG\Property( property="community_order", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示团购订单"),
     *              @SWG\Property( property="offline_order", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示线下订单关联"),
     *              @SWG\Property( property="share_enable", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示我要分享"),
     *              @SWG\Property( property="memberinfo_enable", type="boolean", example="true", description="菜单隐藏显示设置 -- 是否展示个人信息"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getPagestemplateMembercenter(Request $request)
    {
        $authInfo = $request->get('auth');
        $templateName = $request->input('template_name');
        $settingService = new TemplateService();

        $version = $request->input('version', 'v1.0.0');
        $pageName = ['member_center_setting', 'member_center_redirect_setting'];
        // $pageName = ['member_center_setting', 'member_center_redirect_setting', 'member_center_menu_setting'];

        $list = $settingService->getTemplateConf($authInfo['company_id'], $templateName, $pageName, null, $version, ($authInfo['user_id'] ?? 0));
        $return = [];
        $tmpList = array_column($list, 'params', 'page_name');
        if (!isset($tmpList['member_center_menu_setting'])) {
            $menu_setting = [
                'member_center_menu_setting' => [
                    'data' => [
                        'ziti_order' => true,
                        'ext_info' => true,
                        'group' => true,
                        'boost_activity' => true,
                        'boost_order' => true,
                        'complaint' => true,
                        'activity' => true,
                        'recharge' => true,
                        'member_code' => true,
                    ]
                ]
            ];
            $tmpList = array_merge($tmpList, $menu_setting);
        }
        foreach ($tmpList as $k => $v) {
            $return = array_merge($return, $v['data']);
        }
        return $this->response->array($return);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/urllink",
     *     summary="获取小程序 URL Link",
     *     tags={"微信"},
     *     description="获取小程序 URL Link",
     *     operationId="wxaUrlLink",
     *     @SWG\Parameter( name="path", in="formData", description="小程序页面路径", required=true, type="string"),
     *     @SWG\Parameter( name="query", in="formData", description="进入小程序时的query参数", required=true, type="string"),
     *     @SWG\Parameter( name="env_version", in="formData", description="小程序环境。正式版为release，体验版为trial，开发版为develop", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function wxaUrlLink(Request $request)
    {
        $params = $request->all('path', 'query', 'env_version');
        $rules = [
            'path' => ['required', '小程序页面路径必填'],
            'query' => ['sometimes|required', '进入小程序时的query必填'],
            'env_version' => ['required|in:release,trial,develop', '请填写正确的小程序版本'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $user = $request->get('auth');
        $companyId = $user['company_id'];
        $templateName = 'yykweishop';
        $weappService = new WeappService();
        $wxaAppid = $weappService->getWxappidByTemplateName($companyId, $templateName);
        if (!$wxaAppid) {
            throw new StoreResourceFailedException('没有绑定小程序');
        }
        $weappService = new WeappService($wxaAppid, $companyId);
        $result = $weappService->getWxaUrlLink($params['path'], $params['query'], $params['env_version']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/urlschema",
     *     summary="获取小程序 URL Schema",
     *     tags={"微信"},
     *     description="获取小程序 URL Schema",
     *     operationId="wxaUrlSchema",
     *     @SWG\Parameter( name="path", in="formData", description="小程序页面路径", required=true, type="string"),
     *     @SWG\Parameter( name="query", in="formData", description="进入小程序时的query参数", required=true, type="string"),
     *     @SWG\Parameter( name="env_version", in="formData", description="小程序环境。正式版为release，体验版为trial，开发版为develop", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function wxaUrlSchema(Request $request)
    {
        $params = $request->all('path', 'query', 'env_version');
        $rules = [
            'path' => ['required', '小程序页面路径必填'],
            'query' => ['sometimes|required', '进入小程序时的query必填'],
            'env_version' => ['required|in:release,trial,develop', '请填写正确的小程序版本'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $user = $request->get('auth');
        $companyId = $user['company_id'];
        $templateName = 'yykweishop';
        $weappService = new WeappService();
        $wxaAppid = $weappService->getWxappidByTemplateName($companyId, $templateName);
        if (!$wxaAppid) {
            throw new StoreResourceFailedException('没有绑定小程序');
        }
        $weappService = new WeappService($wxaAppid, $companyId);
        $result = $weappService->getWxaUrlSchema($params['path'], $params['query'], $params['env_version']);

        return $this->response->array($result);
    }

}
