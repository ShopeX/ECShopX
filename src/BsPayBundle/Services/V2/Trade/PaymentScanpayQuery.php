<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 扫码交易查询
 *
 */
namespace BsPayBundle\Services\V2\Trade;

use BsPayBundle\Services\Loader;
use BsPayBundle\Sdk\Core\BsPayClient;
use BsPayBundle\Sdk\Request\V2TradePaymentScanpayQueryRequest;


class PaymentScanpayQuery {

    public function __construct($companyId)
    {
        Loader::load($companyId);
    }

    public function handle($data)
    {
        $request = new V2TradePaymentScanpayQueryRequest();
        // 请求参数，不区分必填和可选，按照 api 文档 data 参数结构依次传入
        $param = array(
            "funcCode" => $request->getFunctionCode(),
            "params" => array(
                "huifu_id" => $data['upper_huifu_id'],// 商户号
                "org_req_date" => $data['bspay_req_date'],// 原交易请求日期  格式：yyyyMMdd
                "org_req_seq_id" => $data['trade_id'],// 服务订单创建请求流水号
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
        return $result;
    }

    /**
     * 非必填字段
     *
     */
    public function getExtendInfos() {
        // 设置非必填字段
        $extendInfoMap = array();
        return $extendInfoMap;
    }

}