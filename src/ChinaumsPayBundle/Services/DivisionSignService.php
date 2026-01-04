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

namespace ChinaumsPayBundle\Services;

use Dingo\Api\Exception\ResourceException;

use ChinaumsPayBundle\Services\SftpDataService;
use PaymentBundle\Services\Payments\ChinaumsPayService;

/**
 * 分账/划付签名
 */
class DivisionSignService
{
    private $companyId;
    private $sftp;

    public function __construct($companyId, $sftp) {
        $this->companyId = $companyId;
        $this->sftp = $sftp;
    }

    //上传签名文件
    public function uploadSignFile($localFile, $remote)
    {
        $local = storage_path($localFile);
        $md5file = md5_file($local);

        $payService = new ChinaumsPayService();
        $paymentSetting = $payService->getPaymentSetting($this->companyId);

        if (!$paymentSetting) {
            throw new ResourceException('未配置私钥文件');
        }

        $filePath = $paymentSetting['rsa_private_path'];
        if(!file_exists($filePath)) {
            throw new ResourceException('私钥文件不存在');
        }

        $password = $paymentSetting['password'];

        $pkcs12 = file_get_contents($filePath);
        openssl_pkcs12_read($pkcs12, $certs, $password);
        if(!$certs){
            throw new ResourceException('私钥文件读取错误');
        }

        $privateKey = $certs['pkey']; 
        if (openssl_sign(utf8_encode($md5file), $binarySignature, $privateKey, OPENSSL_ALGO_SHA256)) {
            $localFile = $localFile.'.chk';
            $local = $local.'.chk';
            app('filesystem')->put($localFile, bin2hex($binarySignature));
            return $this->sftp->upftp($local, $remote.'.chk');
        } else {
            throw new ResourceException('生成签名失败');
        }
    }

    //验证签名文件
    public function verifySignFile($local, $remote)
    {
        if (!$this->sftp->downftp($remote.'.chk', $local.'.chk')) {
            throw new ResourceException('签名文件下载失败');
        }

        $chkFileName = $local.'.chk';
        $md5file = md5_file($local);

        $payService = new ChinaumsPayService();
        $paymentSetting = $payService->getPaymentSetting($this->companyId);

        if (!$paymentSetting) {
            throw new ResourceException('未配置公钥文件');
        }

        $filePath = $paymentSetting['rsa_public_path'];
        if(!file_exists($filePath)) {
            throw new ResourceException('公钥文件不存在');
        }

        $cert = file_get_contents($filePath);
        $cert = '-----BEGIN CERTIFICATE-----' . PHP_EOL
            . chunk_split(base64_encode($cert), 64, PHP_EOL)
            . '-----END CERTIFICATE-----' . PHP_EOL;
        $pubKeyId = openssl_get_publickey($cert);
        $chkFile = str_replace("\r\n", "", file_get_contents($chkFileName) );
        $chkFile = str_replace(PHP_EOL, '', $chkFile);
        $signature = hex2bin($chkFile);
        $ok = openssl_verify(utf8_encode($md5file), $signature, $pubKeyId, OPENSSL_ALGO_SHA256);

        if ($ok == 1) {
            openssl_free_key($pubKeyId);
            app('log')->info('回盘验签成功');
            return true;
        } else {
            throw new ResourceException('签名验证失败');
        }
    }
}
