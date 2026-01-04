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

namespace OrdersBundle\Services;

use PaymentBundle\Services\Payments\WechatPayService;
use Exception;

class CustomsService
{
    public $result;//返回参数
    public $appid;
    public $parameters;//请求参数
    public $key = "";
    public $sign_type = "MD5";
    public $mch_customs_no = "";

    public function __construct($companyId, $appid, $distributorId)
    {
        $this->appid = $appid;

        $services = new WechatPayService($distributorId);
        $paymentSetting = $services->getPaymentSetting($companyId);
        $this->key = $paymentSetting['key'];

        $this->mch_customs_no = config('common.owner_id');
    }

    /**
     * 生成参数
     */
    public function getParameters($parameters) //merchantId,orderId
    {
        $this->parameters = $parameters;
        if ($this->parameters["customs"] == null) {//海关
            throw new Exception(trans('OrdersBundle/Order.required_params_missing'));
        }

        if ($this->parameters["out_trade_no"] == null && $this->parameters["transaction_id"] == null) {//海关
            throw new Exception(trans('OrdersBundle/Order.required_params_missing'));
        }

        //增加其他必须参数
        //$this->parameters["sign_type"] = $this->sign_type;//加密类型
        //$this->parameters["service_version"] = $this->service_version;
        //$this->parameters["input_charset"] = $this->input_charset;
        //$this->parameters["sign_key_index"] = $this->sign_key_index;
        //$this->parameters["partner"] = $this->partner;
        $this->parameters["appid"] = $this->appid;
        $this->parameters["mch_customs_no"] = $this->mch_customs_no;//商户海关备案号
        //增加其他必须参数 签名
        $this->parameters["sign"] = $this->createSign($this->para_filter($this->parameters), $this->key);//签名
        return $this->parameters;
    }

    /**
     *     作用：生成签名
     */
    public function createSign($parameters, $key)
    {
        //签名步骤一：按字典序排序参数
        ksort($parameters);
        //签名步骤二：拼接参数
        $buff = "";
        foreach ($parameters as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        //签名步骤三：在string后加入KEY
        $str = $buff.'key='.$key;
        // echo htmlspecialchars($str).'<br>';
        $mysign = "";
        if ($this->sign_type == 'MD5') {
            $mysign = md5($str);
            $mysign = strtoupper($mysign);//转成大写
        } else {
            throw new Exception(trans('OrdersBundle/Order.param_error'));
        }
        return $mysign;
    }

    /**
     * 除去数组中的空值和签名模式
     *
     * @param $parameter
     * @return array
     */
    public function para_filter($parameter)
    {
        $para = array();
        foreach ($parameter as $key => $val) {
            $filter_arr = array('sign');
            if (in_array($key, $filter_arr) || $val == "") {
                continue;
            } else {
                $para[$key] = $parameter[$key];
            }
        }
        return $para;
    }
}
