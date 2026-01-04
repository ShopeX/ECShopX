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
 * 交易确认
 *
 */
namespace BsPayBundle\Services\V2\Trade;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use BsPayBundle\Services\Loader;
use BsPayBundle\Sdk\Core\BsPayClient;
use BsPayBundle\Sdk\Request\V2TradePaymentDelaytransConfirmRequest;


class PaymentDelaytransConfirm {

    public function __construct($companyId)
    {
        Loader::load($companyId);
    }

    public function checktosapUnique($dealStatus='')
    {
        $the_key = 'bspay_onfirmation';
        $exit = app('redis')->setnx($the_key,1);
        if ($exit) {
            sleep (2);
            $this->checktosapUnique($dealStatus);//递归判断
        }
        app('redis')->expire($the_key,2);
        $dealStatus = true;
        return $dealStatus;
    }

    public function handle($data)
    {
        app('log')->info('斗拱 支付确认接口开始 data====>'.var_export($data, true));
        $dealStatus = false;
        $this->checktosapUnique($dealStatus);
        app('log')->info('file:'.__FILE__.',line:'.__LINE__);
        $request = new V2TradePaymentDelaytransConfirmRequest();
        // 请求参数，不区分必填和可选，按照 api 文档 data 参数结构依次传入
        $param = array(
            "funcCode" => $request->getFunctionCode(),
            "params" => array(
                "req_seq_id" => $data['req_seq_id'],
                "req_date" => date("Ymd"),
                "huifu_id" => $data['huifu_id'],// 商户号
            )
        );
        // 设置非必填字段
        $extendInfoMap = $this->getExtendInfos($data);
        $param['params'] = array_merge($param['params'], $extendInfoMap);
        // 创建请求Client对象，调用接口
        $client = new BsPayClient();
        $result = $client->postRequest($param);
        $error_msg = '';
        if (!$result || $result->isError()) {
            $resData = $result->getErrorInfo();
            $error_msg = $resData['msg'];
        } else {
            $resData = $result->getRspDatas();
            // if ( !in_array($resData['data']['resp_code'], ['00000000'])) {
            //     $error_msg = $resData['data']['resp_desc'];
            // }
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
    public function getExtendInfos($data) {
        // 设置非必填字段
        $extendInfoMap = array();
        // 原交易请求日期
        $extendInfoMap["org_req_date"] = $data['org_req_date'];
        // 原交易请求流水号 trade_id
        $extendInfoMap["org_req_seq_id"] = $data['org_req_seq_id'];
        // 原交易全局流水号
        // $extendInfoMap["org_hf_seq_id"]= "";
        // 分账对象
        $extendInfoMap["acct_split_bunch"] = $this->getAcctSplitBunch($data['acct_infos']);
        // 安全信息
        // $extendInfoMap["risk_check_data"]= getRiskCheckData();
        // 交易类型
        // $extendInfoMap["pay_type"]= "";
        // 备注
        // $extendInfoMap["remark"]= "remark123";
        // 原交易商户订单号
        // $extendInfoMap["org_mer_ord_id"]= "";
        return $extendInfoMap;
    }

    public function getAcctSplitBunch($acct_infos) {
        $dto = array();
        // 分账明细
        $dto["acct_infos"] = $acct_infos;

        return json_encode($dto,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

}