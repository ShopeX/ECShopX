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

namespace HfPayBundle\Services\src\Kernel;

class HfSign
{
    private $strSignAlg = 'RSA';                     //RSA证书类型
    public $strPfxPassword;                 //导出时设置的密码
    private $strHashAlg = 'SHA-256';                //加签算法
    public $strPfxFilePath;//汇付下发的证书，此处换成商户自己的证书 .pfx 格式 加签使用
    public $strTrustedCACertFilePath; //汇付下发的.cer证书 ，需要一对证书 解签使用
    public $strLogCofigFilePath;          //CFCA log 目录

    /**
     * CFCA工具初始化
     */
    public function getCFCAInitialize()
    {
        try {
            $nResult = cfca_initialize($this->strLogCofigFilePath);
        } catch (\Throwable $throwable) {
            throw new \Exception($throwable->getMessage());
        }
        if (0 != $nResult) {
            throw new \Exception("cfca_Initialize error:" . $nResult);
        }
    }

    /**
     * @param $json_data
     * 生成签名 cfca方式
     */
    public function cfcaSignature($json_data)
    {
        //生成签名信息
        $strMsgPKCS7AttachedSignature = "";
        try {
            $nResult = cfca_signData_PKCS7Attached(
                $this->strSignAlg,
                $json_data,
                $this->strPfxFilePath,
                $this->strPfxPassword,
                $this->strHashAlg,
                $strMsgPKCS7AttachedSignature
            );
        } catch (\Throwable $throwable) {
            throw new \Exception($throwable->getMessage());
        }


        if (0 != $nResult) {
            throw new \Exception("cfca_signData_PKCS1:nResult:" . $nResult);
        }

        return base64_encode($strMsgPKCS7AttachedSignature);
    }

    /**
     * CFCA 验证签名数据
     *
     * @param $signature
     * @return array
     */
    public function getCFCASignSourceData($signature)
    {
        // Debug: 1e2364
        $strMsgP7AttachedSignCertContent = '';  //PKCS#7 中的签名证书  输出变量，无需传值
        $strMsgP7AttachedSource = '';   //签名原文字符串  输出变量，无需传值

        try {
            //调用验证签名数据方法
            $nResult = cfca_verifyDataSignature_PKCS7Attached(
                $this->strSignAlg,
                base64_decode($signature),
                $strMsgP7AttachedSignCertContent,
                $strMsgP7AttachedSource
            );
            //验证签名方法异常判断及记录
            if (0 != $nResult) {
                throw new \Exception("cfca_verifyDataSignature error:" . $nResult);
            }
        } catch (\Exception $e) {
            //记录log
            throw new \Exception("cfca_verifyDataSignature_PKCS7Attached error:" . $e);
        }

        return array(
            'strMsgP7AttachedSource' => $strMsgP7AttachedSource,
            'strMsgP7AttachedSignCertContent' => $strMsgP7AttachedSignCertContent,
        );
    }

    /**
     * CFCA 证书有效性验证
     *
     * @param $strMsgP7AttachedSignCertContent PKCS#7 中的签名证书 base64
     * @return int
     */
    public function verifyCertificat($strMsgP7AttachedSignCertContent = '')
    {
        $nCertVerifyFlag = '4'; //验证证书链完整性
        $strTrustedCACertFilePath = $this->strTrustedCACertFilePath;
        $isVerify = false;

        try {
            //调用验证方法
            $nResult = cfca_verifyCertificate($strMsgP7AttachedSignCertContent, $nCertVerifyFlag, $strTrustedCACertFilePath, "");
            if (0 == $nResult) {  // 0 为验证通过 ，其他验证失败
                $isVerify = true;
            } else {
                //记录log
                echo new \Exception("cfca_verifyCertificate error:" . $nResult);
            }
        } catch (\Exception $e) {
            //记录log
            throw new \Exception("cfca_verifyCertificate error:" . $e);
        }

        return $isVerify;
    }

    /**
     *CFCA工具结束
     */
    public function __destruct()
    {
        try {
            cfca_uninitialize();
        } catch (\Throwable $e) {
        }
    }
}
