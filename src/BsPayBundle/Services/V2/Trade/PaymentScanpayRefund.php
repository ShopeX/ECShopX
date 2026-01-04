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
 * 扫码交易退款
 *
 */
namespace BsPayBundle\Services\V2\Trade;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use BsPayBundle\Services\Loader;
use BsPayBundle\Sdk\Core\BsPayClient;
use BsPayBundle\Sdk\Request\V2TradePaymentScanpayRefundRequest;


class PaymentScanpayRefund {

    public function __construct($companyId)
    {
        Loader::load($companyId);
    }

    public function handle($data)
    {
        $request = new V2TradePaymentScanpayRefundRequest();
        // 请求参数，不区分必填和可选，按照 api 文档 data 参数结构依次传入
        $param = array(
            "funcCode" => $request->getFunctionCode(),
            "params" => array(
                "req_seq_id" => $data['req_seq_id'],
                "req_date" => date("Ymd"),
                "huifu_id" => $data['upper_huifu_id'],// 商户号
                "ord_amt" => $data['ord_amt'],// 申请退款金额
                "org_req_date" => $data['org_req_date'],// 原交易请求日期  格式：yyyyMMdd
            )
        );
        // 设置非必填字段
        $extendInfoMap = $this->getExtendInfos($data);
        $param['params'] = array_merge($param['params'], $extendInfoMap);
        // print_r($param);exit;
        // $request->setExtendInfo($extendInfoMap);
        # 创建请求Client对象，调用接口
        $client = new BsPayClient();
        $result = $client->postRequest($param);
        app('log')->info('bspay dorefund result====>'.json_encode($result));
        $error_msg = '';
        if (!$result || $result->isError()) {
            $resData = $result->getErrorInfo();
            $error_msg = $resData['msg'];
        } else {
            $resData = $result->getRspDatas();
            if ( !in_array($resData['data']['resp_code'], ['00000000','00000100'])) {
                $error_msg = $resData['data']['resp_desc'];
            }
        }
        if ($error_msg) {
            throw new BadRequestHttpException($error_msg);
        }
        return $resData;
    }

    /**
     * 非必填字段
     *
     */
    function getExtendInfos($data) {
        // 设置非必填字段
        $extendInfoMap = array();
        // 原交易全局流水号
        // $extendInfoMap["org_hf_seq_id"]= "002900TOP3B221107142320P992ac139c0c00000";
        // 原交易微信支付宝的商户单号
        // $extendInfoMap["org_party_order_id"]= "";
        // 原交易请求流水号
        $extendInfoMap["org_req_seq_id"] = $data["trade_id"];
        // 分账对象
        if (isset($data['acct_infos'])) {
            $extendInfoMap["acct_split_bunch"] = $this->getAcctSplitBunchRucan($data['acct_infos']);
        }
        // 聚合正扫微信拓展参数集合
        // $extendInfoMap["wx_data"]= getWxData();
        // 数字货币扩展参数集合
        // $extendInfoMap["digital_currency_data"]= getDigitalCurrencyData();
        // 补贴支付信息
        // $extendInfoMap["combinedpay_data"]= getCombinedpayData();
        // 备注
        // $extendInfoMap["remark"]= "";
        // 是否垫资退款
        // $extendInfoMap["loan_flag"]= "";
        // 垫资承担者
        // $extendInfoMap["loan_undertaker"]= "";
        // 垫资账户类型
        // $extendInfoMap["loan_acct_type"]= "";
        // 安全信息
        // $extendInfoMap["risk_check_data"]= getRiskCheckData();
        // 设备信息
        // $extendInfoMap["terminal_device_data"]= getTerminalDeviceData();
        // 异步通知地址
        // $extendInfoMap["notify_url"]= "";
        return $extendInfoMap;
    }

    function getAcctSplitBunchRucan($acct_infos) {
        $dto = array();
        // 分账信息列表
        $dto["acct_infos"] = $acct_infos;

        return json_encode($dto,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

}