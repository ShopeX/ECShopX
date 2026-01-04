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

namespace WechatBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;
use LaravelDoctrine\Extensions\SoftDeletes\SoftDeletes;

/**
 * WechatAuth 微信授权表(公众号，小程序)
 *
 * @ORM\Table(name="wechat_authorization", options={"comment":"微信授权表(公众号,小程序)"})
 * @ORM\Entity(repositoryClass="WechatBundle\Repositories\WechatAuthRepository")
 */
class WechatAuth
{
    use Timestamps;
    use SoftDeletes;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="authorizer_appid", type="string", length=64, options={"comment":"(公众号，小程序)微信appid"})
     */
    private $authorizer_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="authorizer_appsecret", nullable=true, type="string", options={"comment":"(公众号，小程序)微信appsecret"})
     */
    private $authorizer_appsecret;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", options={"comment":"授权操作者id"})
     */
    private $operator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="authorizer_refresh_token", nullable=true, type="string", options={"comment":"(公众号，小程序)微信refresh_token"})
     */
    private $authorizer_refresh_token;

    /**
     * @var string
     *
     * @ORM\Column(name="nick_name", type="string", length=50, options={"comment":"(公众号，小程序)昵称"})
     */
    private $nick_name;

    /**
     * @var string
     *
     * @ORM\Column(name="head_img", type="string", nullable=true, options={"comment":"(公众号，小程序)头像"})
     */
    private $head_img;

    /**
     * @var integer
     *
     * @ORM\Column(name="service_type_info", type="integer", nullable=true, options={"comment":"(公众号，小程序)类型。可选值有 0代表订阅号；1代表由历史老帐号升级后的订阅号；2代表服务号；3代表小程序(自定义)"})
     */
    private $service_type_info;

    /**
     * @var integer
     *
     * @ORM\Column(name="verify_type_info", type="integer", nullable=true, options={"comment":"(公众号，小程序)认证类型。-1代表未认证;0代表微信认证;1代表新浪微博认证;2代表腾讯微博认证;3代表已资质认证通过但还未通过名称认证;4代表已资质认证通过、还未通过名称认证，但通过了新浪微博认证;5代表已资质认证通过、还未通过名称认证，但通过了腾讯微博认证"})
     */
    private $verify_type_info;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_name", type="string", length=32, nullable=true, options={"comment":"(公众号，小程序)原始 ID"})
     */
    private $user_name;

    /**
     * @var string
     *
     * @ORM\Column(name="signature", nullable=true, type="text", options={"comment":"(小程序)账号介绍"})
     */
    private $signature;

    /**
     * @var string
     *
     * @ORM\Column(name="principal_name", type="string", nullable=true, options={"comment":"(公众号，小程序)主体名称"})
     */
    private $principal_name;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", nullable=true, length=50, options={"comment":"(公众号)授权方公众号所设置的微信号，可能为空"})
     */
    private $alias;

    /**
     * @var string
     *
     * @ORM\Column(name="business_info", nullable=true, type="json_array", options={"comment":"(公众号，小程序)用以了解以下功能的开通状况（0代表未开通，1代表已开通）。open_store:是否开通微信门店功能;open_scan:是否开通微信扫商品功能;open_pay:是否开通微信支付功能;open_card:是否开通微信卡券功能;open_shake:是否开通微信摇一摇功能"})
     */
    private $business_info;

    /**
     * @var string
     *
     * @ORM\Column(name="qrcode_url", type="string", nullable=true, options={"comment":"(公众号，小程序)二维码图片的URL"})
     */
    private $qrcode_url;

    /**
     * @var string
     *
     * @ORM\Column(name="miniprograminfo", type="json_array", nullable=true, options={"comment":"(小程序)小程序配置，根据这个字段判断是否为小程序类型授权"})
     */
    private $miniprograminfo;

    /**
     * @var string
     *
     * @ORM\Column(name="func_info", type="string", nullable=true, options={"comment":"(公众号，小程序)授权给开发者的权限集列表,逗号隔开"})
     */
    private $func_info;

    /**
     * @var string
     *
     * @ORM\Column(name="bind_status", type="string", options={"comment":"绑定状态 bind绑定 unbind绑定解除"})
     */
    private $bind_status;

    /**
     * @var integer
     *
     * @ORM\Column(name="auto_publish", type="smallint", length=1, options={"default":0,"comment":"(小程序)自动发布,第三方授权模式才有用，直连用不到此配置。1:自动发布,0:不自动发布"})
     */
    private $auto_publish = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_direct", type="smallint", length=1, options={"default":0,"comment":"是否直连。1:直连模式,0:第三方授权模式"})
     */
    private $is_direct = 0;

    /**
     * Set authorizerAppid.
     *
     * @param string $authorizerAppid
     *
     * @return WechatAuth
     */
    public function setAuthorizerAppid($authorizerAppid)
    {
        $this->authorizer_appid = $authorizerAppid;

        return $this;
    }

    /**
     * Get authorizerAppid.
     *
     * @return string
     */
    public function getAuthorizerAppid()
    {
        return $this->authorizer_appid;
    }

    /**
     * Set authorizerAppsecret.
     *
     * @param string|null $authorizerAppsecret
     *
     * @return WechatAuth
     */
    public function setAuthorizerAppsecret($authorizerAppsecret = null)
    {
        $this->authorizer_appsecret = $authorizerAppsecret;

        return $this;
    }

    /**
     * Get authorizerAppsecret.
     *
     * @return string|null
     */
    public function getAuthorizerAppsecret()
    {
        return $this->authorizer_appsecret;
    }

    /**
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return WechatAuth
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return WechatAuth
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set authorizerRefreshToken.
     *
     * @param string|null $authorizerRefreshToken
     *
     * @return WechatAuth
     */
    public function setAuthorizerRefreshToken($authorizerRefreshToken = null)
    {
        $this->authorizer_refresh_token = $authorizerRefreshToken;

        return $this;
    }

    /**
     * Get authorizerRefreshToken.
     *
     * @return string|null
     */
    public function getAuthorizerRefreshToken()
    {
        return $this->authorizer_refresh_token;
    }

    /**
     * Set nickName.
     *
     * @param string $nickName
     *
     * @return WechatAuth
     */
    public function setNickName($nickName)
    {
        $this->nick_name = $nickName;

        return $this;
    }

    /**
     * Get nickName.
     *
     * @return string
     */
    public function getNickName()
    {
        return $this->nick_name;
    }

    /**
     * Set headImg.
     *
     * @param string|null $headImg
     *
     * @return WechatAuth
     */
    public function setHeadImg($headImg = null)
    {
        $this->head_img = $headImg;

        return $this;
    }

    /**
     * Get headImg.
     *
     * @return string|null
     */
    public function getHeadImg()
    {
        return $this->head_img;
    }

    /**
     * Set serviceTypeInfo.
     *
     * @param int|null $serviceTypeInfo
     *
     * @return WechatAuth
     */
    public function setServiceTypeInfo($serviceTypeInfo = null)
    {
        $this->service_type_info = $serviceTypeInfo;

        return $this;
    }

    /**
     * Get serviceTypeInfo.
     *
     * @return int|null
     */
    public function getServiceTypeInfo()
    {
        return $this->service_type_info;
    }

    /**
     * Set verifyTypeInfo.
     *
     * @param int|null $verifyTypeInfo
     *
     * @return WechatAuth
     */
    public function setVerifyTypeInfo($verifyTypeInfo = null)
    {
        $this->verify_type_info = $verifyTypeInfo;

        return $this;
    }

    /**
     * Get verifyTypeInfo.
     *
     * @return int|null
     */
    public function getVerifyTypeInfo()
    {
        return $this->verify_type_info;
    }

    /**
     * Set userName.
     *
     * @param string|null $userName
     *
     * @return WechatAuth
     */
    public function setUserName($userName = null)
    {
        $this->user_name = $userName;

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string|null
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Set signature.
     *
     * @param string|null $signature
     *
     * @return WechatAuth
     */
    public function setSignature($signature = null)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature.
     *
     * @return string|null
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Set principalName.
     *
     * @param string|null $principalName
     *
     * @return WechatAuth
     */
    public function setPrincipalName($principalName = null)
    {
        $this->principal_name = $principalName;

        return $this;
    }

    /**
     * Get principalName.
     *
     * @return string|null
     */
    public function getPrincipalName()
    {
        return $this->principal_name;
    }

    /**
     * Set alias.
     *
     * @param string|null $alias
     *
     * @return WechatAuth
     */
    public function setAlias($alias = null)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias.
     *
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set businessInfo.
     *
     * @param array|null $businessInfo
     *
     * @return WechatAuth
     */
    public function setBusinessInfo($businessInfo = null)
    {
        $this->business_info = $businessInfo;

        return $this;
    }

    /**
     * Get businessInfo.
     *
     * @return array|null
     */
    public function getBusinessInfo()
    {
        return $this->business_info;
    }

    /**
     * Set qrcodeUrl.
     *
     * @param string|null $qrcodeUrl
     *
     * @return WechatAuth
     */
    public function setQrcodeUrl($qrcodeUrl = null)
    {
        $this->qrcode_url = $qrcodeUrl;

        return $this;
    }

    /**
     * Get qrcodeUrl.
     *
     * @return string|null
     */
    public function getQrcodeUrl()
    {
        return $this->qrcode_url;
    }

    /**
     * Set miniprograminfo.
     *
     * @param array|null $miniprograminfo
     *
     * @return WechatAuth
     */
    public function setMiniprograminfo($miniprograminfo = null)
    {
        $this->miniprograminfo = $miniprograminfo;

        return $this;
    }

    /**
     * Get miniprograminfo.
     *
     * @return array|null
     */
    public function getMiniprograminfo()
    {
        return $this->miniprograminfo;
    }

    /**
     * Set funcInfo.
     *
     * @param string|null $funcInfo
     *
     * @return WechatAuth
     */
    public function setFuncInfo($funcInfo = null)
    {
        $this->func_info = $funcInfo;

        return $this;
    }

    /**
     * Get funcInfo.
     *
     * @return string|null
     */
    public function getFuncInfo()
    {
        return $this->func_info;
    }

    /**
     * Set bindStatus.
     *
     * @param string $bindStatus
     *
     * @return WechatAuth
     */
    public function setBindStatus($bindStatus)
    {
        $this->bind_status = $bindStatus;

        return $this;
    }

    /**
     * Get bindStatus.
     *
     * @return string
     */
    public function getBindStatus()
    {
        return $this->bind_status;
    }

    /**
     * Set autoPublish.
     *
     * @param int $autoPublish
     *
     * @return WechatAuth
     */
    public function setAutoPublish($autoPublish)
    {
        $this->auto_publish = $autoPublish;

        return $this;
    }

    /**
     * Get autoPublish.
     *
     * @return int
     */
    public function getAutoPublish()
    {
        return $this->auto_publish;
    }

    /**
     * Set isDirect.
     *
     * @param int $isDirect
     *
     * @return WechatAuth
     */
    public function setIsDirect($isDirect)
    {
        $this->is_direct = $isDirect;

        return $this;
    }

    /**
     * Get isDirect.
     *
     * @return int
     */
    public function getIsDirect()
    {
        return $this->is_direct;
    }
}
