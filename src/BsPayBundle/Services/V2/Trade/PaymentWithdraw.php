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
 * 汇付取现
 *
 */
namespace BsPayBundle\Services\V2\Trade;

use BsPayBundle\Services\Loader;
use BsPayBundle\Sdk\Core\BsPayClient;
use BsPayBundle\Sdk\Request\V2TradeSettlementEncashmentRequest;

class PaymentWithdraw {

    public function __construct($companyId)
    {
        Loader::load($companyId);
    }

    public function handle($data)
    {
        $request = new V2TradeSettlementEncashmentRequest();
        // 请求参数，不区分必填和可选，按照 api 文档 data 参数结构依次传入
        $param = array(
            "funcCode" => $request->getFunctionCode(),
            "params" => array(
                "req_date" => date("Ymd"),
                "req_seq_id" => $data['req_seq_id'],
                "cash_amt" => $data['cash_amt'],
                "huifu_id" => $data['huifu_id'],
                "into_acct_date_type" => $data['into_acct_date_type'] ?? 'T1',
                "token_no" => $data['token_no'],
            )
        );
        
        // 设置非必填字段
        $extendInfoMap = $this->getExtendInfos($data);
        $param['params'] = array_merge($param['params'], $extendInfoMap);
        
        app('log')->info('bspay::withdraw::PaymentWithdraw param=====>'.json_encode($param));
        
        # 创建请求Client对象，调用接口
        $client = new BsPayClient();
        $result = $client->postRequest($param);
        
        app('log')->info('bspay::withdraw::PaymentWithdraw result=====>'.json_encode($result));
        
        // 只负责API调用，错误检查交给上层BsPayService处理
        if (!$result || $result->isError()) {
            $resData = $result->getErrorInfo();
        } else {
            $resData = $result->getRspDatas();
        }
        
        return $resData;
    }

    /**
     * 非必填字段
     *
     */
    public function getExtendInfos($data) {
        // 设置非必填字段
        $extendInfoMap = array();
        
        // 取现渠道
        if (!empty($data['enchashment_channel'])) {
            $extendInfoMap["enchashment_channel"] = $data['enchashment_channel'];
        }
        
        // 备注
        if (!empty($data['remark'])) {
            $extendInfoMap["remark"] = $data['remark'];
        }
        
        // 异步通知地址
        if (!empty($data['notify_url'])) {
            $extendInfoMap["notify_url"] = $data['notify_url'];
        }
        
        return $extendInfoMap;
    }
} 