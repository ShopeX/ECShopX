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

namespace HfPayBundle\Services;

use Dingo\Api\Exception\ResourceException;
use HfPayBundle\Services\src\Kernel\Config;
use HfPayBundle\Services\src\Kernel\Factory;
use PaymentBundle\Services\Payments\HfPayService as HfPaySettingService;

class HfpayService extends HfBaseService
{
    private $mer_cust_id;

    public function __construct($company_id)
    {
        // CRC: 2367340174
        $service = new HfPaySettingService();
        $data = $service->getPaymentSetting($company_id);
        if (empty($data)) {
            throw new ResourceException('汇付天下参数未配置');
        }

        $this->mer_cust_id = $data['mer_cust_id'];
        $config = new Config();
        $config->base_uri = 'https://hfpay.cloudpnr.com';
        $config->pfx_password = $data['pfx_password'];
        $config->mer_cust_id = $data['mer_cust_id'];
        Factory::setOptions($config);
    }

    /**
     * pay012 APP支付
     */
    public function pay012($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => $params['order_date'],
            'order_id' => $params['order_id'],
            'app_pay_type' => $params['app_pay_type'],
            'div_type' => $params['div_type'],
            'in_cust_id' => $params['in_cust_id'],
            'div_details' => $params['div_details'],
            'trans_amt' => $params['trans_amt'],
            'buyer_id' => $params['buyer_id'],
            'app_id' => $params['app_id'],
            'goods_tag' => $params['goods_tag'],
            'goods_desc' => $params['goods_desc'],
            'ret_url' => config('common.hfpay_notify_url'),
            'bg_ret_url' => config('common.hfpay_notify_url'),
            'dev_info_json' => $params['dev_info_json'],
            'mer_priv' => $params['mer_priv'],
            'scan_mode' => 1
        ];
        if (isset($params['channel_code']) && $params['channel_code']) {
            $data['channel_code'] = $params['channel_code'];
        }
        $result = Factory::app()->Hfpay()->pay012($data);

        return $result;
    }

    /**
     * qry008 交易状态查询接口
     */
    public function qry008($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'order_id' => $params['order_id'],
            'order_date' => $params['order_date'],
            'trans_type' => $params['trans_type'],
        ];
        $result = Factory::app()->Hfpay()->qry008($data);

        return $result;
    }

    /**
     * pay006 延时分账确认
     */
    public function pay006($params)
    {
        // CRC: 2367340174
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'org_order_id' => $params['org_order_id'],
            'org_order_date' => $params['org_order_date'],
            'org_trans_type' => $params['org_trans_type'],
            'trans_amt' => $params['trans_amt'],
            'div_details' => $params['div_details'],
        ];
        $result = Factory::app()->Hfpay()->pay006($data);

        return $result;
    }

    /**
     * reb001 退款
     */
    public function reb001($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => $params['order_date'],
            'order_id' => $params['order_id'],
            'org_order_date' => $params['org_order_date'],
            'org_order_id' => $params['org_order_id'],
            'in_cust_id' => $params['in_cust_id'],
            'trans_amt' => $params['trans_amt'],
            'dev_info_json' => $params['dev_info_json'],
            'mer_priv' => $params['mer_priv']
        ];
        $result = Factory::app()->Hfpay()->reb001($data);

        return $result;
    }

    /**
     * reb002 退货
     */
    public function reb002($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'org_order_date' => $params['org_order_date'],
            'org_order_id' => $params['org_order_id'],
            'trans_amt' => $params['trans_amt'],
            'div_details' => $params['div_details'],
            'dev_info_json' => $params['dev_info_json'],
        ];
        $result = Factory::app()->Hfpay()->reb002($data);

        return $result;
    }

    /**
     * pay026 余额支付
     */
    public function pay026($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'user_cust_id' => $this->mer_cust_id,
            'order_id' => $this->getOrderId(),
            'order_date' => date('Ymd', time()),
            'div_type' => '0',
            'in_cust_id' => $params['in_cust_id'],
            'in_acct_id' => $params['in_acct_id'],
            'trans_amt' => $params['trans_amt']
        ];
        $result = Factory::app()->Hfpay()->pay026($data);

        return $result;
    }
}
