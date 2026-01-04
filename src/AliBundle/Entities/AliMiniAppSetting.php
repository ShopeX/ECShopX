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

namespace AliBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * AliMiniAppSetting 支付宝小程序配置表
 *
 * @ORM\Table(name="ali_mini_app_setting", options={"comment":"支付宝小程序配置表"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="ix_company_id", columns={"company_id"}),
 *         @ORM\UniqueConstraint(name="ix_authorizer_appid", columns={"authorizer_appid"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="AliBundle\Repositories\AliMiniAppSettingRepository")
 */
class AliMiniAppSetting
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="setting_id", type="bigint", options={"comment"="配置id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $setting_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="authorizer_appid", type="string", length=64, options={"comment":"支付宝小程序appid"})
     */
    private $authorizer_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="merchant_private_key", type="text", options={"comment":"应用私钥"})
     */
    private $merchant_private_key;

    /**
     * @var string
     *
     * @ORM\Column(name="api_sign_method", type="string", length=64, options={"comment":"api加密类型"})
     */
    private $api_sign_method;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_cert_path", type="text", nullable=true, options={"comment":"支付宝公钥证书文件路径"})
     */
    private $alipay_cert_path;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_root_cert_path", type="text", nullable=true, options={"comment":"支付宝根证书文件路径"})
     */
    private $alipay_root_cert_path;

    /**
     * @var string
     *
     * @ORM\Column(name="merchant_cert_path", type="text", nullable=true, options={"comment":"应用公钥证书文件路径"})
     */
    private $merchant_cert_path;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_public_key", type="text", nullable=true, options={"comment":"支付宝公钥字符串"})
     */
    private $alipay_public_key;

    /**
     * @var string
     *
     * @ORM\Column(name="notify_url", type="string", nullable=true, options={"comment":"支付类接口异步通知接收服务地址"})
     */
    private $notify_url;

    /**
     * @var string
     *
     * @ORM\Column(name="encrypt_key", type="string", nullable=true, options={"comment":"AES密钥"})
     */
    private $encrypt_key;

    /**
     * @var $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * get SettingId
     *
     * @return int
     */
    public function getSettingId()
    {
        return $this->setting_id;
    }

    /**
     * set SettingId
     *
     * @param int $setting_id
     *
     * @return self
     */
    public function setSettingId($setting_id)
    {
        $this->setting_id = $setting_id;
        return $this;
    }

    /**
     * get CompanyId
     *
     * @return int
     */
    public function getCompanyId()
    {
        // 0x456353686f7058
        return $this->company_id;
    }

    /**
     * set CompanyId
     *
     * @param int $company_id
     *
     * @return self
     */
    public function setCompanyId($company_id)
    {
        // 0x456353686f7058
        $this->company_id = $company_id;
        return $this;
    }

    /**
     * get AuthorizerAppid
     *
     * @return string
     */
    public function getAuthorizerAppid()
    {
        return $this->authorizer_appid;
    }

    /**
     * set AuthorizerAppid
     *
     * @param string $authorizer_appid
     *
     * @return self
     */
    public function setAuthorizerAppid($authorizer_appid)
    {
        $this->authorizer_appid = $authorizer_appid;
        return $this;
    }

    /**
     * get MerchantPrivateKey
     *
     * @return string
     */
    public function getMerchantPrivateKey()
    {
        return $this->merchant_private_key;
    }

    /**
     * set MerchantPrivateKey
     *
     * @param string $merchant_private_key
     *
     * @return self
     */
    public function setMerchantPrivateKey($merchant_private_key)
    {
        $this->merchant_private_key = $merchant_private_key;
        return $this;
    }

    /**
     * get ApiSignMethod
     *
     * @return string
     */
    public function getApiSignMethod()
    {
        return $this->api_sign_method;
    }

    /**
     * set ApiSignMethod
     *
     * @param string $api_sign_method
     *
     * @return self
     */
    public function setApiSignMethod($api_sign_method)
    {
        $this->api_sign_method = $api_sign_method;
        return $this;
    }

    /**
     * get AlipayCertPath
     *
     * @return string
     */
    public function getAlipayCertPath()
    {
        return $this->alipay_cert_path;
    }

    /**
     * set AlipayCertPath
     *
     * @param string $alipay_cert_path
     *
     * @return self
     */
    public function setAlipayCertPath($alipay_cert_path)
    {
        $this->alipay_cert_path = $alipay_cert_path;
        return $this;
    }

    /**
     * get AlipayRootCertPath
     *
     * @return string
     */
    public function getAlipayRootCertPath()
    {
        return $this->alipay_root_cert_path;
    }

    /**
     * set AlipayRootCertPath
     *
     * @param string $alipay_root_cert_path
     *
     * @return self
     */
    public function setAlipayRootCertPath($alipay_root_cert_path)
    {
        $this->alipay_root_cert_path = $alipay_root_cert_path;
        return $this;
    }

    /**
     * get MerchantCertPath
     *
     * @return string
     */
    public function getMerchantCertPath()
    {
        return $this->merchant_cert_path;
    }

    /**
     * set MerchantCertPath
     *
     * @param string $merchant_cert_path
     *
     * @return self
     */
    public function setMerchantCertPath($merchant_cert_path)
    {
        $this->merchant_cert_path = $merchant_cert_path;
        return $this;
    }

    /**
     * get AlipayPublicKey
     *
     * @return string
     */
    public function getAlipayPublicKey()
    {
        return $this->alipay_public_key;
    }

    /**
     * set AlipayPublicKey
     *
     * @param string $alipay_public_key
     *
     * @return self
     */
    public function setAlipayPublicKey($alipay_public_key)
    {
        $this->alipay_public_key = $alipay_public_key;
        return $this;
    }

    /**
     * get NotifyUrl
     *
     * @return string
     */
    public function getNotifyUrl()
    {
        return $this->notify_url;
    }

    /**
     * set NotifyUrl
     *
     * @param string $notify_url
     *
     * @return self
     */
    public function setNotifyUrl($notify_url)
    {
        $this->notify_url = $notify_url;
        return $this;
    }

    /**
     * get EncryptKey
     *
     * @return string
     */
    public function getEncryptKey()
    {
        return $this->encrypt_key;
    }

    /**
     * set EncryptKey
     *
     * @param string $encrypt_key
     *
     * @return self
     */
    public function setEncryptKey($encrypt_key)
    {
        $this->encrypt_key = $encrypt_key;
        return $this;
    }

    /**
     * get Created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * set Created
     *
     * @param $created
     *
     * @return self
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * get Updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * set Updated
     *
     * @param integer $updated
     *
     * @return self
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }

}
