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
use PaymentBundle\Services\Payments\HfPayService;

class AcouService extends HfBaseService
{
    private $mer_cust_id;

    public function __construct($company_id)
    {
        $service = new HfPayService();
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
     * 文件上传接口
     */
    public function file01($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'attach_no' => $params['attach_no'],
            'trans_type' => $params['trans_type'],
            'attach_type' => $params['attach_type'],
        ];
        $result = Factory::app()->Acou()->file01($data, $params['file']);

        return $result;
    }

    /**
     * 企业开户申请
     */
    public function corp01($params)
    {
        // $operate_type = ['A', 'M'];
        // $corp_license_type = [1,2];
        // $legal_id_card_type = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22];
        //10：身份证 11：护照 12：军官证 13：士兵证 14：回乡证 15：户口本 16：警官证 17：台胞证 18：组织机构代码 19：营业执照 20：税务登记证 21：统一社会信用代码证 22：其他

        $order_id = $this->getOrderId();
        $order_date = date('Ymd', time());
        $apply_id = (isset($params['hf_apply_id']) && !empty($params['hf_apply_id'])) ? $params['hf_apply_id'] : $this->getApplyId();
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => $order_date,
            'order_id' => $order_id,
            'apply_id' => $apply_id,
            'operate_type' => $params['operate_type'],
            'corp_license_type' => $params['corp_license_type'],
            'corp_name' => $params['corp_name'],
            'controlling_shareholder' => $params['controlling_shareholder'],
            'legal_name' => $params['legal_name'],
            'legal_id_card_type' => $params['legal_id_card_type'],
            'legal_id_card' => $params['legal_id_card'],
            'legal_cert_start_date' => $params['legal_cert_start_date'],
            'legal_cert_end_date' => $params['legal_cert_end_date'],
            'legal_mobile' => $params['legal_mobile'],
            'contact_name' => $params['contact_name'],
            'contact_mobile' => $params['contact_mobile'],
            'contact_email' => $params['contact_email'],
            'bank_acct_name' => $params['bank_acct_name'],
            'bank_id' => $params['bank_id'],
            'bank_acct_num' => $params['bank_acct_num'],
            'bank_prov' => $params['bank_prov'],
            'bank_area' => $params['bank_area'],
            'bank_branch' => $params['bank_branch'], //'中国工商银行股份有限公司上海市桂林路支行',//招商银行上海分行田林支行
            'bg_ret_url' => config('common.hfpay_notify_url'),
            'attach_nos' => $params['attach_nos'],
            'mer_priv' => 'corp01',
        ];
        switch ($data['corp_license_type']) {
            case '1':
                $data['business_code'] = $params['business_code'];
                $data['institution_code'] = $params['institution_code'];
                $data['tax_code'] = $params['tax_code'];
                break;
            case '2':
                $data['social_credit_code'] = $params['social_credit_code'];
                break;
            default:
                throw new ResourceException("未知的企业证照类型");
                break;
        }
        // app('log')->debug('汇付企业开户: hf_corp01_request_params =>'.var_export($data,1));
        $result = Factory::app()->Acou()->corp01($data);


        return $result;
    }

    /**
     *  个体户开户申请
     */
    public function solo01($params)
    {
        // $legal_id_card_type = [10, 11, 12, 13, 14, 15, 16, 17, 22];
        // 10：身份证 11：护照 12：军官证 13：士兵证 14：回乡证 15：户口本 16：警官证 17：台胞证 22：其他

        // $occupation = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13'];
        // 01：国家机关、党群机关、企事业单位负责人 02：金融业从业人员 03：房地产业从业人员 04：商贸从业人员 05：自由职业者 06：科教文从业人员 07：制造业从业人员 08：卫生行业从业人员, 09：IT业从业人员 10：农林牧渔劳动者 11：生产工作、运输工作和部分体力劳动者 12：退休人员 13：不便分类的其他劳动者
        $data = [
            'version' => 10,
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'apply_id' => $this->getApplyId(),
            'operate_type' => $params['operate_type'],
            'solo_name' => $params['solo_name'],
            'business_code' => $params['business_code'],
            'license_start_date' => $params['license_start_date'],
            'license_end_date' => $params['license_end_date'],
            'solo_business_address' => $params['solo_business_address'],
            'solo_reg_address' => $params['solo_reg_address'],
            'solo_fixed_telephone' => $params['solo_fixed_telephone'],
            'business_scope' => $params['business_scope'],
            'legal_name' => $params['legal_name'],
            'legal_id_card_type' => $params['legal_id_card_type'],
            'legal_id_card' => $params['legal_id_card'],
            'legal_cert_start_date' => $params['legal_cert_start_date'],
            'legal_cert_end_date' => $params['legal_cert_end_date'],
            'legal_mobile' => $params['legal_mobile'],
            'contact_name' => $params['contact_name'],
            'contact_mobile' => $params['contact_mobile'],
            'contact_email' => $params['contact_email'],
            'occupation' => $params['occupation'],
            'open_license_no' => $params['open_license_no'],
            'contact_cert_num' => $params['contact_cert_num'],
            'bg_ret_url' => config('common.hfpay_notify_url'),
            'attach_nos' => $params['attach_nos'],
            'mer_priv' => 'solo01',
        ];
        $result = Factory::app()->Acou()->solo01($data);

        return $result;
    }

    /**
     * user01 个人用户开户接口
     */
    public function user01($params)
    {
        $data = [
            'version' => 10,
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'user_name' => $params['user_name'],
            'id_card_type' => 10,
            'id_card' => $params['id_card'],
            'user_mobile' => $params['user_mobile'],
        ];
        $result = Factory::app()->Acou()->user01($data);

        return $result;
    }

    /**
     *   qry009 开户状态查询
     */
    public function qry009($params)
    {
        $data = [
            'version' => 10,
            'mer_cust_id' => $this->mer_cust_id,
            'order_id' => $params['order_id'],
            'order_date' => $params['order_date'],
            'trans_type' => $params['trans_type']
        ];
        $result = Factory::app()->Acou()->qry009($data);

        return $result;
    }

    /**
     * mer001 代理商商户开户
     */
    public function mer001($params)
    {
        $data = [
            'version' => 10,
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'sub_mer_name' => $params['sub_mer_name'],
            'sub_mer_short_name' => $params['sub_mer_short_name'],
            'sub_mer_en_name' => $params['sub_mer_en_name'],
            'reg_fund' => $params['reg_fund'],
            'paid_in_fund' => $params['paid_in_fund'],
            'est_date' => $params['est_date'],
            'sub_mer_website' => $params['sub_mer_website'],
            'per_icp_code' => $params['per_icp_code'],
            'net_reg_ip' => $params['net_reg_ip'],
            'sub_mer_prov' => $params['sub_mer_prov'],
            'sub_mer_area' => $params['sub_mer_area'],
            'sub_mer_addr' => $params['sub_mer_addr'],
            'sub_mer_reg_addr' => $params['sub_mer_reg_addr'],
            'sub_mer_phone' => $params['sub_mer_phone'],
            'license_type' => $params['license_type'],
            'license_begin_date' => $params['license_begin_date'],
            'license_end_date' => $params['license_end_date'],
            'business_scope' => $params['business_scope'],
            'stockholders' => $params['stockholders'],
            'legal_name' => $params['legal_name'],
            'legal_id_card_type' => $params['legal_id_card_type'],
            'legal_id_card' => $params['legal_id_card'],
            'legal_id_start_date' => $params['legal_id_start_date'],
            'legal_id_end_date' => $params['legal_id_end_date'],
            'legal_mobile' => $params['legal_mobile'],
            'contact_name' => $params['contact_name'],
            'contact_mobile' => $params['contact_mobile'],
            'contact_email' => $params['contact_email'],
            'fee_conf_info' => $params['fee_conf_info'],
        ];
        switch ($data['license_type']) {
            case '1':
                $data['business_code'] = $params['business_code'];
                $data['institution_code'] = $params['institution_code'];
                $data['tax_code'] = $params['tax_code'];
                break;
            case '2':
                $data['social_credit_code'] = $params['social_credit_code'];
                break;
            default:
                throw new ResourceException("未知的企业证照类型");
                break;
        }

        $result = Factory::app()->Acou()->mer001($data);

        return $result;
    }

    /**
     * sett01 商户微信支付宝入驻接口
     */
    public function sett01($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'pay_way' => $params['pay_way'],
            'rate_type' => $params['rate_type'],
            'category' => $params['category'],
            'mer_phone' => $params['mer_phone'],
            'province_code' => $params['province_code'],
            'city_code' => $params['city_code'],
            'district_code' => $params['district_code'],
            'business_opera_type' => $params['business_opera_type'],
            'business_type' => $params['business_type'],
//            'bg_ret_url'          => $params['bg_ret_url'],
        ];
        if ($params['channel_code']) {
            $data['channel_code'] = $params['channel_code'];
        }
        if ($data['pay_way'] == 1) {
            $data['cls_id'] = $params['cls_id'];
        }
        $result = Factory::app()->Acou()->sett01($data);

        return $result;
    }

    /**
     * sett03 商户微信入驻配置接口
     */
    public function sett03($params)
    {
        $data = [
            'version' => 10,
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'rate_type' => $params['rate_type'],
            'conf_value' => $params['conf_value'],
//            'bg_ret_url' => $params['bg_ret_url'],
        ];
        if (isset($params['channel_code']) && !empty($params['channel_code'])) {
            $data['channel_code'] = $params['channel_code'];
        }
        $result = Factory::app()->Acou()->sett03($data);

        return $result;
    }

    /**
     * bind01 绑定取现卡接口
     */
    public function bind01($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'card_num' => $params['card_num'],
            'card_type' => $params['card_type'] ?? '1',//默认走对私
        ];
        switch ($data['card_type']) {
            case '0':
                $data['user_cust_id'] = $params['user_cust_id'];
                $data['bank_id'] = $params['bank_id'];
                break;
            case '1':
                $data['user_cust_id'] = $params['user_cust_id'];
                break;
            default:
                throw new ResourceException("未知的绑卡类型");
                break;
        }
        if (empty($data['user_cust_id'])) {
            $data['user_name'] = $params['user_name'];
            $data['id_card'] = $params['id_card'];
            $data['user_mobile'] = $params['user_mobile'];
        }
        $result = Factory::app()->Acou()->bind01($data);
        // app('log')->debug('汇付绑卡: hf_bind01_request_response =>'.var_export($result,1));
        return $result;
    }

    /**
     * unbd01 银行卡解绑接口
     */
    public function unbd01($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'bind_card_id' => $params['bind_card_id'],
            'card_buss_type' => 0,
        ];
        if (empty($params['user_cust_id'])) {
            $data['user_cust_id'] = $params['user_cust_id'];
        }
        $result = Factory::app()->Acou()->unbd01($data);

        return $result;
    }

    /**
     * sms001 短信发送接口
     */
    public function sms001($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'user_mobile' => $params['user_mobile'],
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'business_type' => $params['business_type']
        ];
        $result = Factory::app()->Acou()->sms001($data);

        return $result;
    }

    /**
     * pwd001 免密授权接口（后台版)
     */
    public function pwd001($params)
    {
        $data = [
            'version' => '10',
            'mer_cust_id' => $this->mer_cust_id,
            'user_cust_id' => $params['user_cust_id'],
            'order_date' => date('Ymd', time()),
            'order_id' => $this->getOrderId(),
            'cash_free_pwd_flag' => $params['cash_free_pwd_flag'],
            'agree_flag' => 1,
            'sms_code' => $params['sms_code']
        ];
        $result = Factory::app()->Acou()->pwd001($data);

        return $result;
    }

    /**
     * qry001 余额查询接口
     */
    public function qry001($params)
    {
        $data = [
            'version' => 10,
            'mer_cust_id' => $this->mer_cust_id,
            'user_cust_id' => $params['user_cust_id'],
            'acct_id' => $params['acct_id']
        ];
        $result = Factory::app()->Acou()->qry001($data);

        return $result;
    }

    /**
     * cash01 取现（接口版）
     */
    public function cash01($params)
    {
        $data = [
            'version' => 10,
            'mer_cust_id' => $this->mer_cust_id,
            'user_cust_id' => $params['user_cust_id'],
            'order_date' => date('Ymd', time()),
            'order_id' => $params['order_id'],
            'trans_amt' => $params['trans_amt'],
            'bind_card_id' => $params['bind_card_id'],
            'cash_type' => $params['cash_type'],
            'bg_ret_url' => config('common.hfpay_notify_url'),
            'mer_priv' => $params['mer_priv'], //店铺提现 cash01_distributor， 推广员提现 cash01_popularize
            'dev_info_json' => $params['dev_info_json'],
        ];
        $result = Factory::app()->Acou()->cash01($data);

        return $result;
    }
}
