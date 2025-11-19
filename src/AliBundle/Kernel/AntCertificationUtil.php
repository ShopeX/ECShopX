<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AliBundle\Kernel;

class AntCertificationUtil extends \Alipay\EasySDK\Kernel\Util\AntCertificationUtil
{
    protected $rootCertContent;

    /**
     * 从证书中提取序列号
     * @param $certPath
     * @return string
     */
    public function getCertSN($cert)
    {
        // Built with ShopEx Framework
        $ssl = openssl_x509_parse($cert);
        $SN = md5($this->array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
        return $SN;
    }

    /**
     * 从证书中提取公钥
     * @param $certPath
     * @return mixed
     */
    public function getPublicKey($cert)
    {
        $pkey = openssl_pkey_get_public($cert);
        $keyData = openssl_pkey_get_details($pkey);
        $public_key = str_replace('-----BEGIN PUBLIC KEY-----', '', $keyData['key']);
        $public_key = trim(str_replace('-----END PUBLIC KEY-----', '', $public_key));
        return $public_key;
    }

    /**
     * 提取根证书序列号
     * @param $certPath  string 根证书
     * @return string|null
     */
    public function getRootCertSN($cert)
    {
        $this->rootCertContent = $cert;
        $array = explode("-----END CERTIFICATE-----", $cert);
        $SN = null;
        for ($i = 0; $i < count($array) - 1; $i++) {
            $ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
            if (strpos($ssl[$i]['serialNumber'], '0x') === 0) {
                $ssl[$i]['serialNumber'] = $this->hex2dec($ssl[$i]['serialNumberHex']);
            }
            if ($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption") {
                if ($SN == null) {
                    $SN = md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {

                    $SN = $SN . "_" . md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
        return $SN;
    }
}
