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

namespace WechatBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use WechatBundle\Services\WeappService;
use WechatBundle\Services\Wxapp\TemplateService;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use WechatBundle\Entities\WechatAuth;

class Wxa extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxa/authorizer",
     *     summary="获取授权小程序列表",
     *     tags={"微信"},
     *     description="获取授权的小程序列表",
     *     operationId="getWxaList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="authorizer_appid", type="string", example="wx1cd7104338462f8c"),
     *                          @SWG\Property( property="authorizer_appsecret", type="string", example=""),
     *                          @SWG\Property( property="auto_publish", type="string", example="0"),
     *                          @SWG\Property( property="is_direct", type="string", example="0"),
     *                          @SWG\Property( property="nick_name", type="string", example="拼爹"),
     *                          @SWG\Property( property="head_img", type="string", example="http://wx.qlogo.cn/mmopen/FXXXHOj2xs8temGVQEFLnGFfMmFgqicZVkf94tXUOCvEe9wKTq8l9doiaK4sd8lgM6wKXCKC5ThVSsDEHVyP0NvNP2y0qfO2wg/0"),
     *                          @SWG\Property( property="verify_type_info", type="string", example="0"),
     *                          @SWG\Property( property="qrcode_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/EzlQ7rGFibvjA3OrIZIpfviaC7o1Xj7Oallc8WAMGiaDg3ssJ2P2wriaJhWPXdJfTIuPKibqUc2QxEW1433EVnU1MHA/0"),
     *                          @SWG\Property( property="principal_name", type="string", example="商派软件有限公司"),
     *                          @SWG\Property( property="signature", type="string", example="源源客拼团小程序"),
     *                          @SWG\Property( property="weapp", type="object",
     *                                  @SWG\Property( property="authorizer_appid", type="string", example="wx1cd7104338462f8c"),
     *                                  @SWG\Property( property="operator_id", type="string", example="1"),
     *                                  @SWG\Property( property="company_id", type="string", example="1"),
     *                                  @SWG\Property( property="reason", type="string", example="null"),
     *                                  @SWG\Property( property="audit_status", type="string", example="3"),
     *                                  @SWG\Property( property="release_status", type="string", example="0"),
     *                                  @SWG\Property( property="audit_time", type="string", example="1607589705"),
     *                                  @SWG\Property( property="template_id", type="string", example="-1"),
     *                                  @SWG\Property( property="template_name", type="string", example="yykcommunitypms"),
     *                                  @SWG\Property( property="template_ver", type="string", example="v0.0.0"),
     *                                  @SWG\Property( property="release_ver", type="string", example="null"),
     *                                  @SWG\Property( property="visitstatus", type="string", example="1"),
     *                          ),
     *                          @SWG\Property( property="weappTemplate", type="object",
     *                                  @SWG\Property( property="id", type="string", example="4"),
     *                                  @SWG\Property( property="key_name", type="string", example="yykcommunitypms"),
     *                                  @SWG\Property( property="name", type="string", example="社区团购-团长端"),
     *                                  @SWG\Property( property="tag", type="string", example="1"),
     *                                  @SWG\Property( property="template_id", type="string", example="1"),
     *                                  @SWG\Property( property="template_id_2", type="string", example="null"),
     *                                  @SWG\Property( property="version", type="string", example="1"),
     *                                  @SWG\Property( property="is_only", type="string", example="true"),
     *                                  @SWG\Property( property="description", type="string", example="null"),
     *                                  @SWG\Property( property="domain", type="object",
     *                                          @SWG\Property( property="requestdomain", type="array",
     *                                              @SWG\Items( type="string", example="https://ecshopx.shopex123.com"),
     *                                          ),
     *                                          @SWG\Property( property="wsrequestdomain", type="array",
     *                                              @SWG\Items( type="string", example="wss://b-websocket.yuanyuanke.cn"),
     *                                          ),
     *                                          @SWG\Property( property="uploaddomain", type="array",
     *                                              @SWG\Items( type="string", example="https://ecshopx.shopex123.com"),
     *                                          ),
     *                                          @SWG\Property( property="downloaddomain", type="array",
     *                                              @SWG\Items( type="string", example="https://ecshopx.shopex123.com"),
     *                                          ),
     *                                          @SWG\Property( property="webviewdomain", type="array",
     *                                              @SWG\Items( type="string", example="undefined"),
     *                                          ),
     *                                  ),
     *                                  @SWG\Property( property="is_disabled", type="string", example="false"),
     *                                  @SWG\Property( property="created", type="string", example="1572253690"),
     *                                  @SWG\Property( property="updated", type="string", example="1572253690"),
     *                                  @SWG\Property( property="desc", type="string", example="null"),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getWxaList(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        $weappService = new WeappService();
        $companyId = app('auth')->user()->get('company_id');
        $list = $weappService->getWxaList($companyId);
        return $this->response->array(['list' => $list]);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/gettemplateweapplist",
     *     summary="获取授权小程序列表",
     *     tags={"微信"},
     *     description="获取授权的小程序列表",
     *     operationId="getTemplateWeappList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="template_id", type="string", example="30"),
     *                          @SWG\Property( property="domain", type="string", example=""),
     *                          @SWG\Property( property="name", type="string", example="模版名称"),
     *                          @SWG\Property( property="key_name", type="string", example="模版英文名"),
     *                          @SWG\Property( property="authorizer", type="object",
     *                              @SWG\Property( property="authorizer_appid", type="string", example="wx1cd7104338462f8c"),
     *                              @SWG\Property( property="authorizer_appsecret", type="string", example=""),
     *                              @SWG\Property( property="auto_publish", type="string", example="0"),
     *                              @SWG\Property( property="is_direct", type="string", example="0"),
     *                              @SWG\Property( property="nick_name", type="string", example="拼爹"),
     *                              @SWG\Property( property="head_img", type="string", example=""),
     *                              @SWG\Property( property="verify_type_info", type="string", example="0"),
     *                              @SWG\Property( property="qrcode_url", type="string", example=""),
     *                              @SWG\Property( property="principal_name", type="string", example="商派软件有限公司"),
     *                              @SWG\Property( property="signature", type="string", example="源源客拼团小程序"),
     *                              @SWG\Property( property="weapp", type="object",
     *                                      @SWG\Property( property="authorizer_appid", type="string", example="wx1cd7104338462f8c"),
     *                                      @SWG\Property( property="operator_id", type="string", example="1"),
     *                                      @SWG\Property( property="company_id", type="string", example="1"),
     *                                      @SWG\Property( property="reason", type="string", example="null"),
     *                                      @SWG\Property( property="audit_status", type="string", example="3"),
     *                                      @SWG\Property( property="release_status", type="string", example="0"),
     *                                      @SWG\Property( property="audit_time", type="string", example="1607589705"),
     *                                      @SWG\Property( property="template_id", type="string", example="-1"),
     *                                      @SWG\Property( property="template_name", type="string", example="yykcommunitypms"),
     *                                      @SWG\Property( property="template_ver", type="string", example="v0.0.0"),
     *                                      @SWG\Property( property="release_ver", type="string", example="null"),
     *                                      @SWG\Property( property="visitstatus", type="string", example="1"),
     *                              ),
     *                              @SWG\Property( property="weappTemplate", type="object",
     *                                      @SWG\Property( property="id", type="string", example="4"),
     *                                      @SWG\Property( property="key_name", type="string", example="yykcommunitypms"),
     *                                      @SWG\Property( property="name", type="string", example="社区团购-团长端"),
     *                                      @SWG\Property( property="tag", type="string", example="1"),
     *                                      @SWG\Property( property="template_id", type="string", example="1"),
     *                                      @SWG\Property( property="template_id_2", type="string", example="null"),
     *                                      @SWG\Property( property="version", type="string", example="1"),
     *                                      @SWG\Property( property="is_only", type="string", example="true"),
     *                                      @SWG\Property( property="description", type="string", example="null"),
     *                                      @SWG\Property( property="domain", type="object",
     *                                              @SWG\Property( property="requestdomain", type="array",
     *                                                  @SWG\Items( type="string", example="https://ecshopx.shopex123.com"),
     *                                              ),
     *                                              @SWG\Property( property="wsrequestdomain", type="array",
     *                                                  @SWG\Items( type="string", example="wss://b-websocket.yuanyuanke.cn"),
     *                                              ),
     *                                              @SWG\Property( property="uploaddomain", type="array",
     *                                                  @SWG\Items( type="string", example="https://ecshopx.shopex123.com"),
     *                                              ),
     *                                              @SWG\Property( property="downloaddomain", type="array",
     *                                                  @SWG\Items( type="string", example="https://ecshopx.shopex123.com"),
     *                                              ),
     *                                              @SWG\Property( property="webviewdomain", type="array",
     *                                                  @SWG\Items( type="string", example="undefined"),
     *                                              ),
     *                                      ),
     *                                      @SWG\Property( property="is_disabled", type="string", example="false"),
     *                                      @SWG\Property( property="created", type="string", example="1572253690"),
     *                                      @SWG\Property( property="updated", type="string", example="1572253690"),
     *                                      @SWG\Property( property="desc", type="string", example="null"),
     *                              ),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getTemplateWeappList()
    {
        $companyId = app('auth')->user()->get('company_id');
        $list = (new TemplateService())->getTemplateWeappList($companyId);
        return $this->response->array(['list' => $list]);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/gettemplateweappdetail",
     *     summary="获取小程序模版详情",
     *     tags={"微信"},
     *     description="获取小程序模版详情",
     *     operationId="getTemplateWeappDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_id", in="query", description="模版ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                      @SWG\Property( property="id", type="string", example="30"),
     *                      @SWG\Property( property="key_name", type="string", example=""),
     *                      @SWG\Property( property="name", type="string", example=""),
     *                      @SWG\Property( property="tag", type="string", example=""),
     *                      @SWG\Property( property="template_id", type="string", example=""),
     *                      @SWG\Property( property="template_id_2", type="string", example=""),
     *                      @SWG\Property( property="version", type="string", example=""),
     *                      @SWG\Property( property="is_only", type="string", example=""),
     *                      @SWG\Property( property="description", type="string", example=""),
     *                      @SWG\Property( property="domain", type="string", example=""),
     *                      @SWG\Property( property="is_disabled", type="string", example=""),
     *                      @SWG\Property( property="created", type="string", example=""),
     *                      @SWG\Property( property="updated", type="string", example=""),
     *                      @SWG\Property( property="desc", type="string", example=""),
     *                      @SWG\Property( property="authorizer", type="object",
     *                          @SWG\Property( property="authorizer_appid", type="string", example="wx1cd7104338462f8c"),
     *                          @SWG\Property( property="authorizer_appsecret", type="string", example=""),
     *                          @SWG\Property( property="auto_publish", type="string", example="0"),
     *                          @SWG\Property( property="is_direct", type="string", example="0"),
     *                          @SWG\Property( property="nick_name", type="string", example="拼爹"),
     *                          @SWG\Property( property="head_img", type="string", example=""),
     *                          @SWG\Property( property="verify_type_info", type="string", example="0"),
     *                          @SWG\Property( property="qrcode_url", type="string", example=""),
     *                          @SWG\Property( property="principal_name", type="string", example="商派软件有限公司"),
     *                          @SWG\Property( property="signature", type="string", example="源源客拼团小程序"),
     *                          @SWG\Property( property="weapp", type="object",
     *                                  @SWG\Property( property="authorizer_appid", type="string", example=""),
     *                                  @SWG\Property( property="operator_id", type="string", example="1"),
     *                                  @SWG\Property( property="company_id", type="string", example="1"),
     *                                  @SWG\Property( property="reason", type="string", example="null"),
     *                                  @SWG\Property( property="audit_status", type="string", example="3"),
     *                                  @SWG\Property( property="release_status", type="string", example="0"),
     *                                  @SWG\Property( property="audit_time", type="string", example="1607589705"),
     *                                  @SWG\Property( property="template_id", type="string", example="-1"),
     *                                  @SWG\Property( property="template_name", type="string", example="yykcommunitypms"),
     *                                  @SWG\Property( property="template_ver", type="string", example="v0.0.0"),
     *                                  @SWG\Property( property="release_ver", type="string", example="null"),
     *                                  @SWG\Property( property="visitstatus", type="string", example="1"),
     *                          )
     *                      ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getTemplateWeappDetail(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $templateId = $request->input('template_id');
        $detail = (new TemplateService())->getTemplateWeappDetail($companyId, $templateId);
        return $this->response->array($detail);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/templates",
     *     summary="获取模版列表",
     *     tags={"微信"},
     *     description="获取模版列表",
     *     operationId="getTemplateList",
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
     *                     @SWG\Property(property="authorizer_appid", type="string", description="授权小程序appid"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getTemplateList(Request $request)
    {
        $weappService = new TemplateService();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $list = $weappService->getTemplateList($authorizerAppId);
        return $this->response->array(['list' => $list]);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/{wxaAppId}",
     *     summary="获取授权小程序详情",
     *     tags={"微信"},
     *     description="获取授权小程序详情",
     *     operationId="getWxaDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="wxaAppId",
     *         in="path",
     *         description="授权小程序appid",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="authorizer_appid", type="string", example="wx912913df9fef6ddd"),
     *                  @SWG\Property( property="authorizer_appsecret", type="string", example=""),
     *                  @SWG\Property( property="auto_publish", type="string", example="0"),
     *                  @SWG\Property( property="nick_name", type="string", example="51打赏"),
     *                  @SWG\Property( property="head_img", type="string", example="http://wx.qlogo.cn/mmopen/FXXXHOj2xs8temGVQEFLnFNBwY6ticka7ed0qF8ZNemAXOAFbap0AjgovibyJhQiaXCj71V3ic51BKuBPlxSL3RcdJiaorbFUpPFn/0"),
     *                  @SWG\Property( property="service_type_info", type="string", example="3"),
     *                  @SWG\Property( property="verify_type_info", type="string", example="0"),
     *                  @SWG\Property( property="signature", type="string", example="源源客打赏小程序"),
     *                  @SWG\Property( property="principal_name", type="string", example="商派软件有限公司"),
     *                  @SWG\Property( property="business_info", type="object",
     *                          @SWG\Property( property="open_pay", type="string", example="1"),
     *                          @SWG\Property( property="open_card", type="string", example="0"),
     *                          @SWG\Property( property="open_scan", type="string", example="0"),
     *                          @SWG\Property( property="open_shake", type="string", example="0"),
     *                          @SWG\Property( property="open_store", type="string", example="0"),
     *                  ),
     *                  @SWG\Property( property="qrcode_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/cDCU0qjInLejW4HOaZWFiaS2viau5JWSodDL31JdazUNqGUZ7QnmDbn5N7jZjpR7mwTDibY8UuthKxEloMtehwZ8A/0"),
     *                  @SWG\Property( property="operator_id", type="string", example="1"),
     *                  @SWG\Property( property="bind_status", type="string", example="bind"),
     *                  @SWG\Property( property="company_id", type="string", example="1"),
     *                  @SWG\Property( property="weapp", type="object",
     *                          @SWG\Property( property="authorizer_appid", type="string", example="wx912913df9fef6ddd"),
     *                          @SWG\Property( property="operator_id", type="string", example="1"),
     *                          @SWG\Property( property="company_id", type="string", example="1"),
     *                          @SWG\Property( property="reason", type="string", example="null"),
     *                          @SWG\Property( property="audit_status", type="string", example="0"),
     *                          @SWG\Property( property="release_status", type="string", example="1"),
     *                          @SWG\Property( property="audit_time", type="string", example="1611215314"),
     *                          @SWG\Property( property="template_id", type="string", example="60"),
     *                          @SWG\Property( property="template_name", type="string", example="yykweishop"),
     *                          @SWG\Property( property="template_ver", type="string", example="v1.0.22"),
     *                          @SWG\Property( property="release_ver", type="string", example="null"),
     *                          @SWG\Property( property="visitstatus", type="string", example="1"),
     *                  ),
     *                  @SWG\Property( property="weappTemplate", type="object",
     *                          @SWG\Property( property="id", type="string", example="1"),
     *                          @SWG\Property( property="key_name", type="string", example="yykweishop"),
     *                          @SWG\Property( property="name", type="string", example="yykweishop"),
     *                          @SWG\Property( property="tag", type="string", example="微商城"),
     *                          @SWG\Property( property="template_id", type="string", example="60"),
     *                          @SWG\Property( property="template_id_2", type="string", example="60"),
     *                          @SWG\Property( property="version", type="string", example="v1.0.22"),
     *                          @SWG\Property( property="is_only", type="string", example="true"),
     *                          @SWG\Property( property="description", type="string", example="null"),
     *                          @SWG\Property( property="domain", type="object",
     *                                  @SWG\Property( property="requestdomain", type="array",
     *                                      @SWG\Items( type="string", example="https://ecshopx.shopex123.com"),
     *                                  ),
     *                                  @SWG\Property( property="wsrequestdomain", type="array",
     *                                      @SWG\Items( type="string", example="wss://b-websocket.shopex123.com"),
     *                                  ),
     *                                  @SWG\Property( property="uploaddomain", type="array",
     *                                      @SWG\Items( type="string", example="https://ecshopx.shopex123.com"),
     *                                  ),
     *                                  @SWG\Property( property="downloaddomain", type="array",
     *                                      @SWG\Items( type="string", example="https://ecshopx.shopex123.com"),
     *                                  ),
     *                                  @SWG\Property( property="webviewdomain", type="array",
     *                                      @SWG\Items( type="string", example="undefined"),
     *                                  ),
     *                          ),
     *                          @SWG\Property( property="is_disabled", type="string", example="false"),
     *                          @SWG\Property( property="created", type="string", example="1560944416"),
     *                          @SWG\Property( property="updated", type="string", example="1604555037"),
     *                          @SWG\Property( property="desc", type="string", example="null"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getWxaDetail($wxaAppId)
    {
        $companyId = app('auth')->user()->get('company_id');
        $weappService = new WeappService($wxaAppId, $companyId);
        $result = $weappService->getWxaDetail($companyId, $wxaAppId);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxa",
     *     summary="上架小程序审核",
     *     tags={"微信"},
     *     description="上架小程审核",
     *     operationId="uploadWxa",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="wxa_name", in="query", description="授权小程序名称", required=true, type="string"),
     *     @SWG\Parameter( name="templateName", in="query", description="小程序模板名称", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string", description="上架成功状态"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function uploadWxa(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        if (!$authorizerAppId) {
            $wechatAuth = app('registry')->getManager('default')->getRepository(WechatAuth::class)->findOneBy(['company_id' => $companyId, 'bind_status' => 'bind', 'service_type_info' => 3]);
            if ($wechatAuth) {
                //没有授权公众号就用小程序appid来开通绑定
                $authorizerAppId = $wechatAuth->getAuthorizerAppid();
            }
        }
        $wxaAppId = $request->input('wxaAppId');
        $weappService = new WeappService($wxaAppId, $companyId);

        $templateName = $request->input('templateName', 'yykweishop');
        $wxaName = $request->input('wxa_name');
        $operationId = app('auth')->user()->get('operator_id');
        $options = $request->input('templateOptions', array());

        #if (!isset($options['tabBar'])) {
        #    $pageName = 'tabBar';
        #    $name = 'tabBar';
        #    $settingService = new TemplateService();
        #    $tabBarData = $settingService->getTemplateConf($companyId, $templateName, $pageName, $name, 'v1.0.1');
        #    if ($tabBarData) {
        #        $row = $tabBarData[0]['params'];
        #        $options['tabBar'] = [];
        #        $options['tabBar']['list'] = $row['data'];
        #        $options['tabBar']['color'] = $row['config']['color'];
        #        $options['tabBar']['selectedColor'] = $row['config']['selectedColor'];
        #        $options['tabBar']['backgroundColor'] = $row['config']['backgroundColor'];
        #        $options['tabBar']['borderStyle'] = $row['config']['borderStyle'];
        #    }
        #}

        $isAutomatic = config('wechat.is_automatic_submit_review');
        if ($isAutomatic) {
            $weappService->submitAudit($companyId, $authorizerAppId, $operationId, $templateName, $wxaName, $options);
        } else {
            $weappService->onlyCommitTempCode($companyId, $authorizerAppId, $operationId, $templateName, $wxaName, $options);
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/codeunlimit",
     *     summary="小程序码base64图片",
     *     tags={"微信"},
     *     description="小程序码base64图片",
     *     operationId="uploadWxaCodeUnlimit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="base64Image", type="string", example="data:image/jpg;base64,********"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function uploadWxaCodeUnlimit(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        if ($request->input('wxaAppId')) {
            $wxaAppId = $request->input('wxaAppId');
        }else{
            $wxaAppId = app('auth')->user()->get('authorizer_appid');
        }
        $weappService = new WeappService($wxaAppId, $companyId);

        $page = $request->input('page', 'pages/index');
        $params = $request->input();
        foreach ($params as $key => $val) {
            if (in_array($key, ['wxaAppId', 'page']) || !$val || $val === 'undefined') {
                unset($params[$key]);
                continue;
            }
            if (is_array($val)) {
                unset($params[$key]);
                continue;
            }
            if ($key == 'distributor_id') {
                $params['did'] = $val;
                unset($params[$key]);
                continue;
            }
        }
        $scene = $params ? http_build_query($params) : '1';

        try {
            $response = $weappService->createWxaCodeUnlimit($scene, $page);
            if (is_array($response) && $response['errcode'] > 0) {
                throw new \Exception($response['errmsg']);
            }
        } catch (\Exception $e) {          
            throw new StoreResourceFailedException('小程序还从未通过审核，无法生成小程序码，请查看体验二维码');
        }
        $base64 = 'data:image/jpg;base64,' . base64_encode($response);
        return $this->response->array(['base64Image' => $base64]);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/testqrcode",
     *     summary="小程序码体验二维码base64图片",
     *     tags={"微信"},
     *     description="小程序码体验二维码base64图片",
     *     operationId="getTestQrcode",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="base64Image", type="string", example="data:image/jpg;base64,*******"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getTestQrcode(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $wxaAppId = $request->input('wxaAppId');
        $isDirect = $request->input('is_direct', 0);
        $weappService = new WeappService($wxaAppId, $companyId);

        $response = $weappService->getTestQrcode($isDirect);
        $base64 = 'data:image/jpg;base64,' . base64_encode($response);
        return $this->response->array(['base64Image' => $base64]);
    }

    /**
     * @SWG\Post(
     *     path="/wxa/tryrelease",
     *     summary="根据小程序审核状态尝试发布",
     *     tags={"微信"},
     *     description="根据小程序审核状态尝试发布",
     *     operationId="tryRelease",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function tryRelease(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $wxaAppId = $request->input('wxaAppId');
        $weappService = new WeappService($wxaAppId, $companyId);

        $message = $weappService->tryRelease();
        return $this->response->array(['message' => $message]);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/undocodeaudit",
     *     summary="小程序审核撤回",
     *     tags={"微信"},
     *     description="小程序审核撤回",
     *     operationId="undocodeaudit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function undocodeaudit(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $wxaAppId = $request->input('wxaAppId');
        $weappService = new WeappService($wxaAppId, $companyId);

        $message = $weappService->undocodeaudit();
        return $this->response->array(['message' => $message]);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/revertcoderelease",
     *     summary="回退版本",
     *     tags={"微信"},
     *     description="回退版本",
     *     operationId="revertcoderelease",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function revertcoderelease(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $wxaAppId = $request->input('wxaAppId');
        $weappService = new WeappService($wxaAppId, $companyId);

        $message = $weappService->revertcoderelease();
        return $this->response->array(['message' => $message]);
    }

    /**
     * @SWG\Post(
     *     path="/wxa/pageparams/setting_all",
     *     summary="保存小程序页面配置信息",
     *     tags={"微信"},
     *     description="保存小程序页面配置信息",
     *     operationId="setPageAllParams",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="配置的小程序模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="page_name", in="query", description="配置页面名称，默认为(index)首页", required=true, type="string"),
     *     @SWG\Parameter( name="params", in="query", description="配置规则包含挂件参数和挂件名称", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function savePageAllParams(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $templateName = $request->input('template_name');
        $pageName = $request->input('page_name', 'index');
        $config = json_decode($request->input('config'), true);

        if ($request->input('distributor_id')) {
            $version = 'shop_'.$request->input('distributor_id');
        } else {
            $version = $request->input('version', 'v1.0.1');
        }

        $settingService = new TemplateService();
        $settingService->savePageAllParams($companyId, $templateName, $pageName, $config, $version);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/wxa/pageparams/setting",
     *     summary="保存小程序页面单个挂件配置信息",
     *     tags={"微信"},
     *     description="保存小程序页面单个挂件配置信息",
     *     operationId="setPageParams",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="配置的小程序模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="配置规则名称", required=true, type="string"),
     *     @SWG\Parameter( name="page_name", in="query", description="配置页面名称，默认为(index)首页", required=true, type="string"),
     *     @SWG\Parameter( name="params", in="query", description="配置规则参数", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function setPageParams(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $templateName = $request->input('template_name');
        $pageName = $request->input('page_name', 'index');
        $configName = $request->input('name');
        $params = $request->input('params');

        $settingService = new TemplateService();
        $settingService->setTemplateConf($companyId, $templateName, $pageName, $configName, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/pageparams/setting",
     *     summary="获取小程序页面配置信息",
     *     tags={"微信"},
     *     description="获取小程序页面配置信息",
     *     operationId="getParamByTempName",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="配置的小程序模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="配置规则名称", required=true, type="string"),
     *     @SWG\Parameter( name="page_name", in="query", description="配置页面名称，默认为(index)首页", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getParamByTempName(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $templateName = $request->input('template_name');
        $settingService = new TemplateService();

        $name = $request->input('name', null);
        $pageName = $request->input('page_name', 'index');

        if ($request->input('distributor_id')) {
            $version = 'shop_'.$request->input('distributor_id');
        } else {
            $version = $request->input('version', 'v1.0.0');
        }

        $list = $settingService->getTemplateConf($companyId, $templateName, $pageName, $name, $version);

        if (!isset($list[0]['params']['is_open'])) {
            $list[0]['params']['is_open'] = true;
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
        } else {
            $return = $list;
        }

        return $this->response->array($return);
    }

    /**
     * @SWG\Put(
     *     path="/wxa/pageparams/setting",
     *     summary="更新小程序页面单个配置信息",
     *     tags={"微信"},
     *     description="更新小程序页面单个配置信息",
     *     operationId="updateParamsById",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="配置ID", required=true, type="string"),
     *     @SWG\Parameter( name="params", in="query", description="配置规则参数", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function updateParamsById(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $id = $request->input('id');
        $settingService = new TemplateService();

        $params = $request->input('params');

        $list = $settingService->updateParamsById($id, $companyId, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/wxa/config/{wxaAppId}",
     *     summary="小程序配置",
     *     tags={"微信"},
     *     description="小程序配置",
     *     operationId="saveConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="path", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="auto_publish", in="query", description="自动发布", required=true, type="boolean"),
     *     @SWG\Parameter( name="authorizer_appsecret", in="query", description="密钥", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function saveConfig($wxaAppId, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $params = $request->all('auto_publish', 'authorizer_appsecret');
        $rules = [
            // 'authorizer_appsecret' => ['required_if|min:1','密钥错误'],
            'auto_publish' => ['required|in:0,1','自动发布参数错误'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $params['authorizer_appsecret'] = trim($params['authorizer_appsecret']);
        $weappService = new WeappService();
        $weappService->updateWxaConfig($wxaAppId, $params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/wxa/onlycode",
     *     summary="仅上传小程序代码",
     *     tags={"微信"},
     *     description="仅上传小程序代码",
     *     operationId="commitTempCode",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="wxaAppId", description="授权小程序appid" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="templateName", description="模板名称" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="wxa_name", description="授权小程序" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="templateOptions", description="模板选项" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones")))
     * )
     */
    public function commitTempCode(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $wxaAppId = $request->input('wxaAppId');
        $weappService = new WeappService($wxaAppId, $companyId);

        $templateName = $request->input('templateName');
        $wxaName = $request->input('wxa_name');
        $operationId = app('auth')->user()->get('operator_id');
        $options = $request->input('templateOptions', array());
        $weappService->onlyCommitTempCode($companyId, $authorizerAppId, $operationId, $templateName, $wxaName, $options);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/wxa/submitreview",
     *     summary="提交小程序并审核",
     *     tags={"微信"},
     *     description="提交小程序并审核",
     *     operationId="submitReview",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="wxaAppId", description="授权小程序appid" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="templateName", description="模板名称" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="wxa_name"),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="templateOptions", description="模版选项" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones")))
     * )
     */
    public function submitReview(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        if (!$authorizerAppId) {
            $wechatAuth = app('registry')->getManager('default')->getRepository(WechatAuth::class)->findOneBy(['company_id' => $companyId, 'bind_status' => 'bind', 'service_type_info' => 3]);
            if ($wechatAuth) {
                //没有授权公众号就用小程序appid来开通绑定
                $authorizerAppId = $wechatAuth->getAuthorizerAppid();
            }
        }
        $wxaAppId = $request->input('wxaAppId');
        $weappService = new WeappService($wxaAppId, $companyId);

        $templateName = $request->input('templateName');
        $wxaName = $request->input('wxa_name');
        $operationId = app('auth')->user()->get('operator_id');
        $options = $request->input('templateOptions', array());
        $weappService->submitReview($companyId, $authorizerAppId, $operationId, $templateName, $wxaName, $options);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/getdomainlist",
     *     summary="获取小程序域名",
     *     tags={"微信"},
     *     description="获取小程序域名",
     *     operationId="getDomainList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="path", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="templateName", in="query", description="模版名称", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getDomainList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $wxaAppId = $request->input('wxaAppId');
        $templateName = $request->input('templateName');

        $weappService = new WeappService($wxaAppId, $companyId);
        $result = $weappService->getDomainListAll($templateName);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxa/savedomain",
     *     summary="保存小程序域名",
     *     tags={"微信"},
     *     description="保存小程序域名",
     *     operationId="saveDomain",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="formData", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="templateName", in="formData", description="模版名称", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="bool"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function saveDomain(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $wxaAppId = $request->input('wxaAppId');
        $templateName = $request->input('templateName');

        $weappService = new WeappService($wxaAppId, $companyId);
        $result = $weappService->modifyDomainByLocal($templateName);

        return $this->response->array(['status' => $result]);
    }


    /**
     * @SWG\Post(
     *     path="/wxa/cartremind/setting",
     *     summary="保存小程序购物车提醒配置",
     *     tags={"微信"},
     *     description="保存小程序购物车提醒配置",
     *     operationId="setCartremindSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="购物车是否展示", required=true, type="string"),
     *     @SWG\Parameter( name="remind_content", in="query", description="提醒内容", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function setCartremindSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('is_open', 'remind_content');
        $params['is_open'] = $params['is_open'] == 'true' ? true : false;
        $rules = [
            'remind_content' => ['required_if: is_open,true|string|min:1|max:100','提醒内容必填'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $templateService = new TemplateService();
        $templateService->setCartremindSetting($companyId, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/cartremind/setting",
     *     summary="获取小程序购物车提醒配置",
     *     tags={"微信"},
     *     description="获取小程序购物车提醒配置",
     *     operationId="getCartremindSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="配置的小程序模版名称", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="配置规则名称", required=true, type="string"),
     *     @SWG\Parameter( name="page_name", in="query", description="配置页面名称，默认为(index)首页", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="is_open", type="boolean", example=false, description="是否开启 true:开启,false:关闭"),
     *                  @SWG\Property( property="remind_content", type="string", example="", description="提醒内容"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getCartremindSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $templateService = new TemplateService();
        $data = $templateService->getCartremindSetting($companyId);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxa/privacy/setting",
     *     summary="查询小程序用户隐私保护指引",
     *     tags={"微信"},
     *     description="查询小程序用户隐私保护指引",
     *     operationId="getPrivacySetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="errcode", type="string", example="0", description="返回码"),
     *                  @SWG\Property( property="errmsg", type="string", example="ok", description="错误信息"),
     *                  @SWG\Property( property="code_exist", type="string", example="1", description="代码是否存在， 0 不存在， 1 存在 。如果最近没有通过commit接口上传代码，则会出现 code_exist=0的情况。"),
     *                  @SWG\Property( property="privacy_list", type="array",
     *                      @SWG\Items( type="string", example="UserInfo", description="代码检测出来的用户信息类型（privacy_key）"),
     *                  ),
     *                  @SWG\Property( property="setting_list", type="array",description="description",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="privacy_key", type="string", example="UserInfo", description="用户信息类型的英文名称"),
     *                          @SWG\Property( property="privacy_text", type="string", example="展示用户个人信息", description="该用户信息类型的用途"),
     *                          @SWG\Property( property="privacy_removable", type="string", example="1", description=""),
     *                          @SWG\Property( property="privacy_label", type="string", example="", description="用户信息类型的中文名称"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="update_time", type="string", example="1635936888", description="更新时间"),
     *                  @SWG\Property( property="owner_setting", type="object",description="收集方（开发者）信息配置",
     *                          @SWG\Property( property="contact_phone", type="string", example="18434286466", description="信息收集方（开发者）的手机号"),
     *                          @SWG\Property( property="contact_email", type="string", example="", description="信息收集方（开发者）的邮箱"),
     *                          @SWG\Property( property="contact_qq", type="string", example="", description="信息收集方（开发者）的qq"),
     *                          @SWG\Property( property="contact_weixin", type="string", example="", description="信息收集方（开发者）的微信号"),
     *                          @SWG\Property( property="store_expire_timestamp", type="string", example="", description="存储期限，指的是开发者收集用户信息存储多久"),
     *                          @SWG\Property( property="ext_file_media_id", type="string", example="2119697040224976898", description="自定义 用户隐私保护指引文件的media_id"),
     *                          @SWG\Property( property="notice_method", type="string", example="小程序用户隐私保护指引弹窗", description="通知方式，指的是当开发者收集信息有变动时，通过该方式通知用户"),
     *                  ),
     *                  @SWG\Property( property="privacy_desc", type="object",description="用户信息类型对应的中英文描述",
     *                          @SWG\Property( property="privacy_desc_list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="privacy_key", type="string", example="UserInfo", description="用户信息类型的英文key"),
     *                                  @SWG\Property( property="privacy_desc", type="string", example="用户信息（微信昵称、头像）", description="用户信息类型的中文描述"),
     *                               ),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="audit_status", type="string", example="0", description="审核状态"),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getPrivacySetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $wxaAppId = $request->input('wxaAppId');
        $weappService = new WeappService($wxaAppId, $companyId);

        $result = $weappService->getPrivacySetting();
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxa/privacy/setting",
     *     summary="设置小程序用户隐私保护指引",
     *     tags={"微信"},
     *     description="设置小程序用户隐私保护指引",
     *     operationId="setPrivacySetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="owner_setting", in="query", description="收集方（开发者）信息配置 object", required=true, type="string"),
     *     @SWG\Parameter( name="setting_list", in="query", description="要收集的用户信息配置，可选择的用户信息类型参考下方详情 arrary object", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string", description="状态"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function setPrivacySetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $wxaAppId = $request->input('wxaAppId');
        $weappService = new WeappService($wxaAppId, $companyId);
        $owner_setting = $request->get('owner_setting', '{}');
        $owner_setting = json_decode($owner_setting, 1);
        $setting_list = $request->get('setting_list', '{}');
        $setting_list = json_decode($setting_list, 1);

        if (isset($owner_setting['store_expire_timestamp']) && $owner_setting['store_expire_timestamp']) {
            $owner_setting['store_expire_timestamp'] = strtotime($owner_setting['store_expire_timestamp']);
        }
        $params = [
            'owner_setting' => $owner_setting,
            'setting_list' => $setting_list,
        ];
        $weappService->setPrivacySetting($params);
        // $weappService->applySecurity(['api_name' => 'wx.chooseAddress', 'content' => '用于选择收货地址，方便快速下单']);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/wxa/uploadprivacy/extfile",
     *     summary="上传小程序用户隐私保护指引",
     *     tags={"微信"},
     *     description="上传小程序用户隐私保护指引",
     *     operationId="uploadPrivacyExtFile",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="file", in="query", description="只支持传txt文件", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *            @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="errcode", type="string", example="0", description="返回码"),
     *                  @SWG\Property( property="errmsg", type="string", example="ok", description="错误信息"),
     *                  @SWG\Property( property="ext_file_media_id", type="string", example="2112311471891169280", description="文件的media_id"),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function uploadPrivacyExtFile(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $wxaAppId = $request->input('wxaAppId');
        $weappService = new WeappService($wxaAppId, $companyId);
        $file = $request->file('file');
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['txt'])) {
            throw new StoreResourceFailedException('仅支持txt');
        }
        $fileSize = $file->getSize();
        if ($fileSize <= 0) {
            throw new StoreResourceFailedException('上传文件失败');
        }
        if (round($fileSize / 1024 * 100) / 100 > 100) {
            throw new StoreResourceFailedException('大小不超过100kb');
        }

        $oldpath = $file->getPathname();
        $path = $oldpath.'.'.$file->getClientOriginalExtension();
        copy($oldpath, $path);
        $result = $weappService->uploadPrivacyExtFile($path);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/offiaccountcodeforever",
     *     summary="获取服务号永久二维码",
     *     tags={"微信"},
     *     description="获取服务号永久二维码",
     *     operationId="getOffiaccountCodeForever",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="authorizer_appid", in="query", description="授权小程序或者公众号appid", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="base64Image", type="string", example="data:image/jpg;base64,********"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getOffiaccountCodeForever(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $authorizer_appid = $request->input('authorizer_appid');
        $isBase64 = $request->input('is_base64', false);
        $weappService = new WeappService($authorizer_appid, $companyId);

        try {
            $response = $weappService->createOffiaccountCodeForever($authorizer_appid, $isBase64);
        } catch (\Exception $e) {
            $response['url'] = '';
            $response['base64Image'] = '';
        }
        return $this->response->array($response);
    }


}
