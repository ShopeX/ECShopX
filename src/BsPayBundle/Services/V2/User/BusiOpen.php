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

/**
 * 个人用户基本信息开户
 *
 */
namespace BsPayBundle\Services\V2\User;

use BsPayBundle\Services\Loader;
use BsPayBundle\Sdk\Core\BsPayClient;
use BsPayBundle\Sdk\Request\V2UserBusiOpenRequest;


class BusiOpen {

    public function __construct($companyId)
    {
        Loader::load($companyId);
    }

    public function handle($data)
    {
        $request = new V2UserBusiOpenRequest();
        // 请求参数，不区分必填和可选，按照 api 文档 data 参数结构依次传入
        $param = array(
            "funcCode" => $request->getFunctionCode(),
            "params" => array(
                "req_seq_id" => $data['req_seq_id'],
                "req_date" => date("Ymd"),
                "upper_huifu_id" => $data['upper_huifu_id'],
                "huifu_id" => $data['huifu_id'],
            ),
        );
        // 设置非必填字段
        $_data['card_info'] = [
            'card_type' => $data['card_type'],
            'card_name' => $data['card_name'],
            'card_no' => $data['card_no'],
            'prov_id' => $data['prov_id'],
            'area_id' => $data['area_id'],
            'bank_code' => $data['bank_code'],
            'branch_name' => $data['branch_name'],
            'cert_type' => '00',
            'cert_no' => $data['cert_no'],
            'cert_validity_type' => $data['cert_validity_type'],
            'cert_begin_date' => $data['cert_begin_date'],
            'cert_end_date' => $data['cert_end_date'],
            'mp' => $data['mp'],
        ];
        $extendInfoMap = $this->getExtendInfos($_data);
        $param['params'] = array_merge($param['params'], $extendInfoMap);
        // $request->setExtendInfo($extendInfoMap);
        # 创建请求Client对象，调用接口
        $client = new BsPayClient();
        $result = $client->postRequest($param);
        return $result; 
    }

    /**
     * 非必填字段
     *
     */
    function getExtendInfos($data) {
        // 设置非必填字段
        $extendInfoMap = array();
        // 结算信息配置
        // $extendInfoMap["settle_config"]= "";
        // 结算卡信息
        $extendInfoMap["card_info"]= json_encode($data['card_info']);
        
        // 取现配置列表（固定配置）
        $extendInfoMap["cash_config"] = $this->buildCashConfig();
        
        // 文件列表
        // $extendInfoMap["file_list"]= "";
        // 延迟入账开关
        // $extendInfoMap["delay_flag"]= "";
        return $extendInfoMap;
    }

    /**
     * 构建取现配置参数（固定配置）
     *
     * @return string
     */
    private function buildCashConfig() {
        $config[0] = array(
            'cash_type' => 'T1',
            'fix_amt' => '0.00',
            'fee_rate' => '0.00',
            'weekday_fix_amt' => '0.00',
            'weekday_fee_rate' => '0.00',
            'out_fee_flag' => '2',
            'is_priority_receipt' => 'N',
            'out_fee_acct_type' => '01'
        );
        
        return json_encode($config);
    }

}