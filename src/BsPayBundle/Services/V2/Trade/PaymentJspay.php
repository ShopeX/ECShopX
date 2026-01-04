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
 * 聚合正扫
 *
 */
namespace BsPayBundle\Services\V2\Trade;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use BsPayBundle\Services\Loader;
use BsPayBundle\Sdk\Core\BsPayClient;
// use BsPayBundle\Sdk\Request\V2TradePaymentJspayRequest;
use BsPayBundle\Sdk\Request\V3TradePaymentJspayRequest;


class PaymentJspay {

    public function __construct($companyId)
    {
        Loader::load($companyId);
    }

    public function handle($data)
    {
        $request = new V3TradePaymentJspayRequest();
        // 请求参数，不区分必填和可选，按照 api 文档 data 参数结构依次传入
        $param = array(
            "funcCode" => $request->getFunctionCode(),
            "params" => array(
                "req_seq_id" => $data['trade_id'],// 使用交易单号
                "req_date" => date("Ymd"),
                "huifu_id" => $data['upper_huifu_id'],// 商户号
                "goods_desc" => $data['goods_desc'],// 商品描述
                "trade_type" => $this->getTradeType($data['pay_channel']),
                "trans_amt" => $data['trans_amt'],//交易金额
            )
        );
        // 设置非必填字段
        $extendInfoMap = $this->getExtendInfos($data);
        $param['params'] = array_merge($param['params'], $extendInfoMap);
        app('log')->info('bspay dopay PaymentJspay param=====>'.json_encode($param));
        // $request->setExtendInfo($extendInfoMap);
        # 创建请求Client对象，调用接口
        $client = new BsPayClient();
        $result = $client->postRequest($param);
        app('log')->info('bspay dopay PaymentJspay result=====>'.json_encode($result));
        $error_msg = '';
        if (!$result || $result->isError()) {
            $resData = $result->getErrorInfo();
            $error_msg = $resData['msg'];
        } else {
            $resData = $result->getRspDatas();
            if ( !in_array($resData['data']['resp_code'], ['00000000', '00000100'])) {
                $error_msg = $resData['data']['resp_desc'];
            }
        }
        if ($error_msg) {
            throw new BadRequestHttpException($error_msg);
        }
        return $resData;
    }

    private function getTradeType($type)
    {
        $allType = [
            'wx_lite' => 'T_MINIAPP',
            'wx_pub' => 'T_JSAPI',
            'wx_qr' => 'T_JSAPI',
            'alipay_wap' => 'A_NATIVE',
            'alipay_qr' => 'A_NATIVE',
        ];
        return $allType[$type] ?? '';
    }

    /**
     * 非必填字段
     *
     */
    public function getExtendInfos($data) {
        // 设置非必填字段
        $extendInfoMap = array();
        // 交易有效期
        $extendInfoMap["time_expire"] = $data['time_expire'];
        // 聚合正扫微信拓展参数集合
        switch ($data['pay_channel']) {
            case 'wx_lite':
            case 'wx_pub':
            case 'wx_qr':
                $extendInfoMap["wx_data"] = $this->getWxData($data);
                break;
            case 'alipay_wap':
            case 'alipay_qr':  // PC端支付宝扫码也需要支付宝扩展参数
                $extendInfoMap['alipay_data'] = $this->getAlipayData($data);
                break;
        }
        
        // 分账对象
        // $extendInfoMap["acct_split_bunch"]= getAcctSplitBunch();
        // 传入分帐遇到优惠的处理规则
        $extendInfoMap["term_div_coupon_type"] = "0";
        // 补贴支付信息
        // $extendInfoMap["combinedpay_data"]= getCombinedpayData();
        // 账户号
        // $extendInfoMap["acct_id"]= "";
        // 手续费扣款标志
        // $extendInfoMap["fee_flag"]= "";
        // 禁用信用卡标记
        $extendInfoMap["limit_pay_type"] = "NO_CREDIT";
        // 是否延迟交易
        $extendInfoMap["delay_acct_flag"] = $data['delay_acct_flag'] ?? 'N';
        // 商户贴息标记
        // $extendInfoMap["fq_mer_discount_flag"]= "N";
        // 渠道号
        // $extendInfoMap["channel_no"]= "";
        // 场景类型
        // $extendInfoMap["pay_scene"]= "02";
        // 安全信息
        // $extendInfoMap["risk_check_data"]= getRiskCheckData();
        // 设备信息
        // $extendInfoMap["terminal_device_data"]= getTerminalDeviceData();
        // 备注
        $extendInfoMap["remark"] = $data['remark'];
        // 异步通知地址
        $extendInfoMap["notify_url"] = $data['notify_url'];
        return $extendInfoMap;
    }

    function getGoodsDetailWxRucan($goods) {
        $dto = array();
        // 商品编码
        $dto["goods_id"] = $goods['goods_id'];
        // 商品名称
        $dto["goods_name"] = $goods['name'];
        // 商品单价(元)
        $dto["price"] = $goods['price'];
        // 商品数量
        $dto["quantity"] = $goods['num'];
        // 微信侧商品编码
        // $dto["wxpay_goods_id"] = "";

        $dtoList = array();
        array_push($dtoList, $dto);
        return $dtoList;
    }

    public function getDetail($goods) {
        $dto = array();
        // 单品列表
        $dto["goods_detail"] = $this->getGoodsDetailWxRucan($goods);
        // 订单原价(元)
        // $dto["cost_price"] = "";
        // 商品小票ID
        // $dto["receipt_id"] = "";

        return $dto;
    }

    public function getStoreInfo() {
        $dto = array();
        // 门店id
        // $dto["id"] = "";
        // 门店名称
        // $dto["name"] = "";
        // 门店行政区划码
        // $dto["area_code"] = "";
        // 门店详细地址
        // $dto["ass"] = "";

        $dtoList = array();
        array_push($dtoList, $dto);
        return $dtoList;
    }

    public function getSceneInfo() {
        $dto = array();
        // 门店信息
        // $dto["store_info"] = getStoreInfo();

        return $dto;
    }

    public function getWxData($data) {
        $dto = array();
        // 子商户公众账号id
        $dto["sub_appid"] = $data['sub_appid'];
        // 用户标识
        // $dto["openid"] = $data['openid'];
        // 子商户用户标识
        $dto["sub_openid"] = $data['sub_openid'];
        // 附加数据
        // $dto["attach"] = $data['attach'] ?? '';
        // 商品描述
        $dto["body"] = $data['body'];
        // 商品详情
        // $dto["detail"] = $this->getDetail($data['goods']);
        // 设备号
        // $dto["device_info"] = "";
        // 订单优惠标记
        // $dto["goods_tag"] = "";
        // 实名支付
        // $dto["identity"] = "";
        // 开发票入口开放标识
        // $dto["receipt"] = "";
        // 场景信息
        // $dto["scene_info"] = getSceneInfo();
        // 终端ip
        $dto["spbill_create_ip"] = $data['spbill_create_ip'];
        // 单品优惠标识
        // $dto["promotion_flag"] = "";
        // 新增商品ID
        // $dto["product_id"] = "";
        // 指定支付者
        // $dto["limit_payer"] = "";

        return json_encode($dto,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    public function getAlipayData($data)
    {
        $dto = array();
        // 买家的支付宝唯一用户号
        $dto["buyer_id"] = $data['buyer_id'] ?? "";
        // 支付宝的店铺编号
        // $dto["alipay_store_id"] = "";
        // 买家支付宝账号
        // $dto["buyer_logon_id"] = "String";
        // 业务扩展参数
        // $dto["extend_params"] = getExtendParams();
        // 订单包含的商品列表信息
        // $dto["goods_detail"] = $this->getGoodsDetail($data);
        // 商户原始订单号
        $dto["merchant_order_no"] = $data['order_id'];
        // 商户操作员编号
        // $dto["operator_id"] = "123213213";
        // 销售产品码
        // $dto["product_code"] = "String";
        // 卖家支付宝用户号
        // $dto["seller_id"] = "String";
        // 商户门店编号
        // $dto["store_id"] = "";
        // 外部指定买家
        // $dto["ext_user_info"] = getExtUserInfo();
        // 订单标题
        $dto["subject"] = $data['body'];
        // 商家门店名称
        // $dto["store_name"] = "";

        return json_encode($dto,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    public function getGoodsDetail($data) {
        $dto = array();
        // 商品的编号
        $dto["goods_id"] = "12312321";
        // 商品名称(元)
        $dto["goods_name"] = "阿里";
        // 商品单价
        $dto["price"] = "0.01";
        // 商品数量
        $dto["quantity"] = "20";
        // 商品描述信息
        $dto["body"] = "";
        // 商品类目树
        $dto["categories_tree"] = "String";
        // 商品类目
        $dto["goods_category"] = "";
        // 商品的展示地址
        $dto["show_url"] = "";

        $dtoList = array();
        array_push($dtoList, $dto);
        return $dtoList;
    }

    public function getAcctInfosRucan() {
        $dto = array();
        // 分账金额
        // $dto["div_amt"] = "test";
        // 被分账方ID
        // $dto["huifu_id"] = "test";
        // 账户号
        // $dto["acct_id"] = "";

        $dtoList = array();
        array_push($dtoList, $dto);
        return $dtoList;
    }

    public function getAcctSplitBunch() {
        $dto = array();
        // 分账明细
        // $dto["acct_infos"] = getAcctInfosRucan();

        return json_encode($dto,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

}