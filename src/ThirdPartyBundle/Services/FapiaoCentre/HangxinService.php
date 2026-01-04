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

namespace ThirdPartyBundle\Services\FapiaoCentre;

use OrdersBundle\Traits\GetOrderServiceTrait;
use EasyWeChat\Kernel\Support\XML; // easywechat@done
use MembersBundle\Services\MemberService;
use CompanysBundle\Services\SettingService;

use function GuzzleHttp\json_encode;

class HangxinService extends HangxinRequest
{
    use GetOrderServiceTrait;

    public $fapiao_config;
    public $settingService;

    public function __construct()
    {
        // 获取发票配置信息
        $this->settingService = new SettingService();
    }


    /**
     * 发票 开票
     *
     */
    public function getFapiao($params, $sourceType = 'normal')
    {
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-data:". json_encode($params));

        $companyId = $params['company_id'];
        $orderId = $params['order_id'];

        // 获取发票配置信息
        $setting = $this->settingService->getInfo(['company_id' => $companyId]);
        $this->fapiao_config = isset($setting['fapiao_config']) ? ($setting['fapiao_config']) : array();

        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":fapiao_config:". json_encode($this->fapiao_config));

        // 初始化返回信息 根据发票开票结果
        $rtn = array();
        // 编辑发票信息
        $fapiaoinfo = array();
        // 获取订单信息
        $orderService = $this->getOrderService($sourceType);
        $orderData = $orderService->getOrderInfo($companyId, $orderId);
        $orderInfo = $orderData['orderInfo'];
        $tradeInfo = $orderData['tradeInfo'];

        $params['pdf_type'] = 2;
        // API通用报文 编辑xml 加密
        $xmldata = $this->_formatxmlapidown($orderInfo, $tradeInfo, $fapiaoinfo, $params);//1 pdf 2 url  3 url+pdf
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":orderDataXml:". ($xmldata));

        $rtn = $this->call($xmldata);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". json_encode($rtn));
        $rtn_data = XML::parse($rtn);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". json_encode($rtn_data));
        // 返回  base64 $returnMessage = $this->decrypt3des($returnMessage);
        $returnMessage = base64_decode($rtn_data['returnStateInfo']['returnMessage']) ;
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". ($returnMessage));


        $returncontent = $this->decrypt3des($rtn_data['Data']['content']);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". json_encode($returncontent));


        if ($rtn_data['returnStateInfo']['returnCode'] == '0000') {
            $rtn_content = XML::parse($returncontent);////PDF_URL
            $rtn_content['c_url'] = $rtn_content['PDF_URL'] ?? "";
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". json_encode($rtn_content));
        }

        $query_res = array();

        $query_res['returnCode'] = $rtn_data['returnStateInfo']['returnCode'];
        $query_res['returnMessage'] = $rtn_data['returnStateInfo']['returnMessage'];
        $query_res['result'] = array();
        $query_res['result'][] = $rtn_content ?? array();
        return $query_res;



        // $content = simplexml_load_string($rtn);
        // if ($rtn_data['returnStateInfo']['returnCode'] == '0000') {
        //     if ($rtn_data['Data']['dataDescription']['zipCode'] == 1) {
        //         $content = gzdecode(base64_decode($rtn_data['Data']['content']) );
        //         app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". ($content) );

        //         $pdf = simplexml_load_string($content);
        //         app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". ($pdf) );

        //         $returncontent = $this->decrypt3des($pdf);
        //         app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". ($returncontent) );

        //         $returncontent = $this->decrypt3des($content);
        //         app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". ($returncontent) );

        //     }
        // }
    }

    /**
     * 发票 开票
     *
     */
    public function createFapiao($params, $sourceType = 'normal')
    {
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-data:". json_encode($params));

        $companyId = $params['company_id'];
        $orderId = $params['order_id'];

        // 获取发票配置信息
        $setting = $this->settingService->getInfo(['company_id' => $companyId]);
        $this->fapiao_config = isset($setting['fapiao_config']) ? ($setting['fapiao_config']) : array();

        // 初始化返回信息 根据发票开票结果
        $rtn = array();
        // 编辑发票信息
        $fapiaoinfo = array();
        // 获取订单信息
        $orderService = $this->getOrderService($sourceType);
        $orderData = $orderService->getOrderInfo($companyId, $orderId);

        $orderInfo = $orderData['orderInfo'];
        $tradeInfo = $orderData['tradeInfo'];

        $memberService = new memberService();
        $memberInfo = $memberService->getMemberInfoData($orderInfo['user_id'], $orderInfo['company_id']);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":memberInfo:". json_encode($memberInfo));

        // 编辑发票信息
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":orderInfo:". json_encode($orderInfo));

        $fapiaoinfo = $this->_formatinfo($orderInfo, $tradeInfo, $memberInfo, $params);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":fapiaoinfo:". json_encode($fapiaoinfo));

        $REQUEST_FPKJXX = array();
        $REQUEST_FPKJXX['FPKJXX_FPTXX'] = $fapiaoinfo['fapiao'];
        $REQUEST_FPKJXX['FPKJXX_XMXXS'] = $fapiaoinfo['fapiao_item'];//fapiao_ddxx
        $REQUEST_FPKJXX['FPKJXX_DDXX'] = $fapiaoinfo['fapiao_ddxx'];//fapiao_ddxx
        $datain = array("REQUEST_FPKJXX" => $REQUEST_FPKJXX);

        // API通用报文 编辑xml 加密
        $xmldata = $this->_formatxmlapikaipiao($orderInfo, $tradeInfo, $datain, $fapiaoinfo);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":orderDataXml:". ($xmldata));

        $rtn = $this->call($xmldata);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". json_encode($rtn));
        $rtn_data = XML::parse($rtn);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". json_encode($rtn_data));
        $returnMessage = base64_decode($rtn_data['returnStateInfo']['returnMessage']) ;
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". ($returnMessage));
        // $returnMessage = $this->decrypt3des($returnMessage);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":rtn:". json_encode($returnMessage));

        $query_res = array();

        $query_res['returnCode'] = $rtn_data['returnStateInfo']['returnCode'];
        $query_res['returnMessage'] = $rtn_data['returnStateInfo']['returnMessage'];
        $query_res['result'] = array();
        $query_res['result'][] = array();
        return $query_res;
    }

    /**
     * API通用报文
     *
     */
    public function _formatxmlapikaipiao($orderInfo, $tradeInfo, $datain, $fapiaoinfo)
    {
        // EcShopX core
        $requestTime = date("Y-m-d H:i:s");
        $dataExchangeId = date("Ymd");
        //API通用报文
        $xmldata = '<?xml version="1.0" encoding="utf-8"?>
        <interface xmlns="" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.chinatax.gov.cn/tirip/dataspec/interfaces.xsd"
            version="DZFP1.0">
            <globalInfo>
                <terminalCode>0</terminalCode>
                <appId>DZFP</appId>
                <version>1.1</version>
                <interfaceCode>ECXML.FPKJ.BC.E_INV</interfaceCode>
                <requestCode>'.$this->fapiao_config['DSPTBM'].'</requestCode>
                <requestTime>$requestTime</requestTime>
                <responseCode>121</responseCode>
                <dataExchangeId>'.$this->fapiao_config['DSPTBM'].'ECXML.FPKJ.BC.E_INV$dataExchangeIdeXl4EymmJ</dataExchangeId>
                <userName>'.$this->fapiao_config['DSPTBM'].'</userName>
                <passWord></passWord>
                <taxpayerId>'.$this->fapiao_config['NSRSBH'].'</taxpayerId>
                <authorizationCode>'.$this->fapiao_config['authorizationCode'].'</authorizationCode>
            </globalInfo>
            <returnStateInfo>
            <returnCode />
            <returnMessage />
            </returnStateInfo>    
            <Data>
                <dataDescription>
                    <zipCode>0</zipCode>
                    <encryptCode>1</encryptCode>
                    <codeType>3DES</codeType>
                </dataDescription>
                <content><contentdata></contentdata></content>
                </Data>
            </interface>';

        // 订单数据 content
        // $data = XML::parse(strval($request->getContent()));
        $orderDataXml = XML::build($datain);
        //正则 替换XML FPKJXX_XMXX
        $pattern = "/(<FPKJXX_XMXXS)(>)/i";
        $replacement = '$1 class="FPKJXX_XMXX;" size="' . count($fapiaoinfo['fapiao_item']) . '" $2';
        $orderDataXml = preg_replace($pattern, $replacement, $orderDataXml);
        //正则 替换XML FPKJXX_XMXX
        $pattern = "/(<FPKJXX_XMXX)(\d+)(>)/i";
        $replacement = '$1$3';
        $orderDataXml = preg_replace($pattern, $replacement, $orderDataXml);
        //正则 替换XML
        $pattern = "/(<\/FPKJXX_XMXX)(\d+)(>)/i";
        $replacement = '$1$3';
        $orderDataXml = preg_replace($pattern, $replacement, $orderDataXml);

        $orderDataXml = str_replace("<xml>", "", $orderDataXml);
        $orderDataXml = str_replace("</xml>", "", $orderDataXml);
        $orderDataXml = str_replace('<REQUEST_FPKJXX>', '<REQUEST_FPKJXX class="REQUEST_FPKJXX">', $orderDataXml);
        $orderDataXml = str_replace('<FPKJXX_FPTXX>', '<FPKJXX_FPTXX class="FPKJXX_FPTXX">', $orderDataXml);
        $orderDataXml = str_replace('<FPKJXX_DDXX>', '<FPKJXX_DDXX class="FPKJXX_DDXX">', $orderDataXml);

        //<FPKJXX_DDXX class="FPKJXX_DDXX">
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":dataenc:". ($orderDataXml));
        //3des加密
        $dataenc = $this->encrypt3des($orderDataXml);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":dataenc:". ($dataenc));
        // $dataenc = base64_encode($dataenc);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":dataenc:". ($dataenc));

        // $datadec = $this->decrypt3des($dataenc);
        // app('log')->debug("\n".__FUNCTION__."-".__LINE__.":data:". ($datadec) );

        //api conteng数据
        $str_sch = "<contentdata></contentdata>";
        $xmldata = str_replace($str_sch, $dataenc, $xmldata);
        $xmldata = str_replace('$requestTime', $requestTime, $xmldata);
        $xmldata = str_replace('$dataExchangeId', $dataExchangeId, $xmldata);

        return $xmldata;
    }


    /**
     * 订单结构体
     *
     */
    public function _formatinfo($orderInfo, $tradeInfo, $memberInfo, $params = array())
    {
        if ($orderInfo['receipt_type'] == "ziti") {
            // app('log')->debug("\n".__FUNCTION__."-".__LINE__.":memberInfo:". ($memberInfo) );
            $orderInfo['receiver_name'] = $orderInfo['receiver_name'] ? $orderInfo['receiver_name'] : "客户".$orderInfo['user_id'];
            ;
        }
        // 发票开具接口API报文
        // <REQUEST_FPKJXX class="REQUEST_FPKJXX">
        //     <FPKJXX_FPTXX class="FPKJXX_FPTXX">
        $fapiao_arr = array();
        //         <FPQQLSH>发票请求唯一流水号</FPQQLSH>
        $fapiao_arr['FPQQLSH'] = $this->fapiao_config['FPQQLSH'].$orderInfo['order_id'].$params['kptype'];//"d222311v"
        //         <DSPTBM>平台编码</DSPTBM>
        $fapiao_arr['DSPTBM'] = $this->fapiao_config['DSPTBM'];//"P1000001";
        //         <NSRSBH>开票方识别号</NSRSBH>
        $fapiao_arr['NSRSBH'] = $this->fapiao_config['NSRSBH'];//"913101010000000090";
        //         <NSRMC>开票方名称</NSRMC>
        $fapiao_arr['NSRMC'] = $this->fapiao_config['content'];//"上海航信模拟测试";
        //         <FJH>分机号</FJH>
        //         <NSRDZDAH>开票方电子档案号</NSRDZDAH>
        //         <SWJG_DM>税务机构代码</SWJG_DM>
        //         <DKBZ>代开标志</DKBZ>
        $fapiao_arr['DKBZ'] = "1";
        //         <SGBZ>收购标志</SGBZ>
        //         <PYDM>票样代码</PYDM>
        //         <KPXM>主要开票项目</KPXM>
        $fapiao_arr['KPXM'] = $orderInfo['title'];
        //         <BMB_BBH>编码表版本号</BMB_BBH>
        $fapiao_arr['BMB_BBH'] = "20.0";
        //         <XHF_NSRSBH>销货方识别号</XHF_NSRSBH>
        $fapiao_arr['XHF_NSRSBH'] = $this->fapiao_config['XHF_NSRSBH'];//"913101010000000090";
        //         <XHFMC>销货方名称</XHFMC>
        $fapiao_arr['XHFMC'] = $this->fapiao_config['content'];//"上海航信模拟测试";
        //         <XHF_DZ>销货方地址</XHF_DZ>
        $fapiao_arr['XHF_DZ'] = $this->fapiao_config['company_address'];//"商派微商城地址";
        //         <XHF_DH>销货方电话</XHF_DH>
        $fapiao_arr['XHF_DH'] = $this->fapiao_config['company_phone'];//"02111223344";
        //         <XHF_YHZH>销货方银行账号</XHF_YHZH>
        $fapiao_arr['XHF_YHZH'] = $this->fapiao_config['bankaccount'];//"622688888888";
        //         <GHFMC>购货方名称</GHFMC>
        $fapiao_arr['GHFMC'] = $orderInfo['receiver_name'];
        //         <GHF_NSRSBH>购货方识别号</GHF_NSRSBH>
        $fapiao_arr['GHF_NSRSBH'] = "";
        //         <GHF_SF>购货方省份</GHF_SF>
        $fapiao_arr['GHF_SF'] = $orderInfo['receiver_state'];
        //         <GHF_DZ>购货方地址</GHF_DZ>
        $fapiao_arr['GHF_DZ'] = $orderInfo['receiver_city'].$orderInfo['receiver_district'].$orderInfo['receiver_address'];
        //         <GHF_GDDH>购货方固定电话</GHF_GDDH>
        //         <GHF_SJ>购货方手机</GHF_SJ>
        $fapiao_arr['GHF_SJ'] = $orderInfo['receiver_mobile'];
        //         <GHF_EMAIL>购货方邮箱</GHF_EMAIL>
        //         <GHFQYLX>购货方企业类型</GHFQYLX>	01：企业 ，02：机关事业单位， 03：个人 ，04：其它
        $fapiao_arr['GHFQYLX'] = "03";
        //         <GHF_YHZH>购货方银行、账号</GHF_YHZH>
        //         <HY_DM>行业代码</HY_DM>
        //         <HY_MC>行业名称</HY_MC>
        //         <KPY>开票员</KPY>		setting
        $fapiao_arr['KPY'] = "开票员";
        //         <SKY>收款员</SKY>
        //         <FHR>复核人</FHR>
        //         <KPRQ>开票日期</KPRQ>
        //         <KPLX>开票类型</KPLX>
        $fapiao_arr['KPLX'] = $params['kptype'] ;//蓝：1 红：2
        //         <YFP_DM>原发票代码</YFP_DM>
        if ($params['kptype'] == 2) {
            $fapiao_arr['YFP_DM'] = $params['fapiaoinfo_query'][0]['FP_DM'];
        }
        //         <YFP_HM>原发票号码</YFP_HM>
        if ($params['kptype'] == 2) {
            $fapiao_arr['YFP_HM'] = $params['fapiaoinfo_query'][0]['FP_HM'];
        }
        //         <CZDM>操作代码</CZDM>
        $fapiao_arr['CZDM'] = "10";
        if ($params['kptype'] == 2) {
            $fapiao_arr['CZDM'] = "20";
        }
        //         <QD_BZ>清单标志</QD_BZ>
        $fapiao_arr['QD_BZ'] = "0";
        //         <QDXMMC>清单发票项目名称</QDXMMC>
        //         <CHYY>冲红原因</CHYY>
        if ($params['kptype'] == 2) {
            $fapiao_arr['CHYY'] = "发生售后退款退货";
        }
        //         <TSCHBZ>特殊冲红标志</TSCHBZ>
        //         <KPHJJE>价税合计金额</KPHJJE>
        $fapiao_arr['KPHJJE'] = bcdiv($tradeInfo['totalFee'], 100, 2);
        if ($params['kptype'] == 2) {
            $fapiao_arr['KPHJJE'] = bcdiv($tradeInfo['totalFee'] * -1, 100, 2);
        }
        //         <HJBHSJE>合计不含税金额</HJBHSJE>
        $fapiao_arr['HJBHSJE'] = bcdiv($tradeInfo['totalFee'], 100, 2) - bcdiv($tradeInfo['totalFee'] * ($this->fapiao_config['tax_rate'] / 100), 100, 2) ;
        if ($params['kptype'] == 2) {
            $fapiao_arr['HJBHSJE'] = bcdiv($tradeInfo['totalFee'] * -1, 100, 2) - bcdiv($tradeInfo['totalFee'] * ($this->fapiao_config['tax_rate'] / 100) * -1, 100, 2) ;
        }
        //         <HJSE>合计税额</HJSE>
        $fapiao_arr['HJSE'] = bcdiv($tradeInfo['totalFee'] * ($this->fapiao_config['tax_rate'] / 100) * -1, 100, 2) ;

        //         <BZ>备注</BZ>
        //         <BYZD1>备用字段</BYZD1>
        //     </FPKJXX_FPTXX>
        $fapiao_item_arr = array();
        //     <FPKJXX_XMXXS class="FPKJXX_XMXX;" size="1">
        foreach ($orderInfo['items'] as $k => $vitem) {
            $key = "FPKJXX_XMXX".$k;
            //         <FPKJXX_XMXX>
            //             <XMMC>项目名称</XMMC>
            $fapiao_item_arr[$key]["XMMC"] = $vitem['item_name'];
            //             <XMDW>项目单位</XMDW>
            //             <GGXH>规格型号</GGXH>
            //             <XMSL>项目数量</XMSL>
            $fapiao_item_arr[$key]["XMSL"] = $vitem['num'] ;
            if ($params['kptype'] == 2) {
                $fapiao_item_arr[$key]["XMSL"] = $vitem['num'] * -1;
            }
            //             <HSBZ>含税标志</HSBZ>
            $fapiao_item_arr[$key]["HSBZ"] = 1;
            //             <XMDJ>项目单价</XMDJ>
            $fapiao_item_arr[$key]["XMDJ"] = bcdiv((($vitem['item_fee']) - $vitem['discount_fee']) / $vitem['num'], 100, 2);
            //bcdiv( (($vitem['item_fee'] * $detail_arr["num"] )+ $vitem['freight_fee'] - $vitem['discount_fee']), 100, 2);
            //             <FPHXZ>发票行性质</FPHXZ>
            $fapiao_item_arr[$key]["FPHXZ"] = "0";
            //             <SPBM>商品编码</SPBM>商品税收分类编码，由企业提供，不足19位后面补‘0’
            $fapiao_item_arr[$key]["SPBM"] = "1090522010000000000";
            //             <ZXBM>自行编码</ZXBM>
            //             <YHZCBS>优惠政策标识</YHZCBS>
            $fapiao_item_arr[$key]["YHZCBS"] = "0";
            //             <LSLBS>零税率标识</LSLBS>
            $fapiao_item_arr[$key]["LSLBS"] = '';
            //             <ZZSTSGL>增值税特殊管理</ZZSTSGL>
            //             <KCE>扣除额</KCE>
            //             <XMJE>项目金额</XMJE>						+ $vitem['freight_fee']
            $fapiao_item_arr[$key]["XMJE"] = bcdiv((($vitem['item_fee']) - $vitem['discount_fee']), 100, 2);
            if ($params['kptype'] == 2) {
                $fapiao_item_arr[$key]["XMJE"] = bcdiv((($vitem['item_fee']) - $vitem['discount_fee']) * -1, 100, 2);
            }
            //             <SL>税率</SL>
            $fapiao_item_arr[$key]["SL"] = $this->fapiao_config['tax_rate'] / 100;
            //             <SE>税额</SE>
            $fapiao_item_arr[$key]["SE"] = ($fapiao_item_arr[$key]["XMJE"] * $fapiao_item_arr[$key]["SL"]) ;
            if ($params['kptype'] == 2) {
                $fapiao_item_arr[$key]["SE"] = ($fapiao_item_arr[$key]["XMJE"] * $fapiao_item_arr[$key]["SL"]) * -1;
            }
            //             <BYZD1>备用字段</BYZD1>
        //         </FPKJXX_XMXX>
        }

        //     </FPKJXX_XMXXS>
        //     <FPKJXX_DDXX class="FPKJXX_DDXX">
        $fapiao_ddxx = array();
        //         <DDH>订单号</DDH>
        $fapiao_ddxx["DDH"] = $orderInfo['order_id'].$params['kptype'];
        //         <THDH>退货单号</THDH>
        //         <DDDATE>订单时间</DDDATE>
        //     </FPKJXX_DDXX>
        // </REQUEST_FPKJXX>
        $data = array();
        $data['fapiao'] = $fapiao_arr;
        $data['fapiao_item'] = $fapiao_item_arr;
        $data['fapiao_ddxx'] = $fapiao_ddxx;
        return $data;
    }


    /**
     * API通用报文
     *
     */
    public function _formatxmlapidown($orderInfo, $tradeInfo, $fapiaoinfo, $params)
    {
        $requestTime = date("Y-m-d H:i:s");
        $dataExchangeId = date("Ymd");
        //API通用报文
        $xmldata = '<?xml version="1.0" encoding="utf-8"?>
        <interface xmlns="" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.chinatax.gov.cn/tirip/dataspec/interfaces.xsd"
            version="DZFP1.0">
            <globalInfo>
                <terminalCode>0</terminalCode>
                <appId>DZFP</appId>
                <version>1.1</version>
                <interfaceCode>ECXML.FPMXXZ.CX.E_INV</interfaceCode>
                <requestCode>'.$this->fapiao_config['DSPTBM'].'</requestCode>
                <requestTime>$requestTime</requestTime>
                <responseCode>121</responseCode>
                <dataExchangeId>'.$this->fapiao_config['DSPTBM'].'ECXML.FPKJ.BC.E_INV$dataExchangeIdeXl4EymmJ</dataExchangeId>
                <userName>'.$this->fapiao_config['DSPTBM'].'</userName>
                <passWord></passWord>
                <taxpayerId>'.$this->fapiao_config['NSRSBH'].'</taxpayerId>
                <authorizationCode>'.$this->fapiao_config['authorizationCode'].'</authorizationCode>
            </globalInfo>
            <returnStateInfo>
            <returnCode />
            <returnMessage />
            </returnStateInfo>    
            <Data>
                <dataDescription>
                    <zipCode>0</zipCode>
                    <encryptCode>1</encryptCode>
                    <codeType>3DES</codeType>
                </dataDescription>
                <content><contentdata></contentdata></content>
                </Data>
            </interface>';

        //0:发票开具状态查询;1:PDF文件(PDF_FILE);2:PDF文件链接地址;3:PDF文件和链接地址都返回
        $orderDataXml = '<REQUEST_FPXXXZ_NEW class="REQUEST_FPXXXZ_NEW">
                        <FPQQLSH>$FPQQLSH</FPQQLSH>
                        <DSPTBM>'.$this->fapiao_config['DSPTBM'].'</DSPTBM>
                        <NSRSBH>'.$this->fapiao_config['NSRSBH'].'</NSRSBH>
                        <DDH>$DDH</DDH>
                        <PDF_XZFS>PDF_XZFS_TYPE</PDF_XZFS>
                        </REQUEST_FPXXXZ_NEW>';

        if ($params['kptype'] == "2") {
        }
        $orderDataXml = str_replace('PDF_XZFS_TYPE', $params['pdf_type'], $orderDataXml);

        $FPQQLSH = $this->fapiao_config['FPQQLSH'].$orderInfo['order_id'].$params['kptype'];//"d222311v"
        $DDH = $orderInfo['order_id'].$params['kptype'];

        $orderDataXml = str_replace('$FPQQLSH', $FPQQLSH, $orderDataXml);
        $orderDataXml = str_replace('$DDH', $DDH, $orderDataXml);

        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":dataenc:". ($orderDataXml));
        //3des加密
        $dataenc = $this->encrypt3des($orderDataXml);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":dataenc:". ($dataenc));

        //api conteng数据
        $str_sch = "<contentdata></contentdata>";
        $xmldata = str_replace($str_sch, $dataenc, $xmldata);
        $xmldata = str_replace('$requestTime', $requestTime, $xmldata);
        $xmldata = str_replace('$dataExchangeId', $dataExchangeId, $xmldata);

        return $xmldata;
    }
}



// 发票开具API
// API说明：企业向开票服务推送开具电子发票的数据信息
// 调用方式：HTTP协议
// API编码：ECXML.FPKJ.BC.E_INV
// 调用方法：POST(XML文件流)
// 请求报文示例与数据项说明
// 【content中】请求报文示例:
// 发票开具接口API报文
// <REQUEST_FPKJXX class="REQUEST_FPKJXX">
//     <FPKJXX_FPTXX class="FPKJXX_FPTXX">
//         <FPQQLSH>发票请求唯一流水号</FPQQLSH>
//         <DSPTBM>平台编码</DSPTBM>
//         <NSRSBH>开票方识别号</NSRSBH>
//         <NSRMC>开票方名称</NSRMC>
//         <FJH>分机号</FJH>
//         <NSRDZDAH>开票方电子档案号</NSRDZDAH>
//         <SWJG_DM>税务机构代码</SWJG_DM>
//         <DKBZ>代开标志</DKBZ>
//         <SGBZ>收购标志</SGBZ>
//         <PYDM>票样代码</PYDM>
//         <KPXM>主要开票项目</KPXM>
//         <BMB_BBH>编码表版本号</BMB_BBH>
//         <XHF_NSRSBH>销货方识别号</XHF_NSRSBH>
//         <XHFMC>销货方名称</XHFMC>
//         <XHF_DZ>销货方地址</XHF_DZ>
//         <XHF_DH>销货方电话</XHF_DH>
//         <XHF_YHZH>销货方银行账号</XHF_YHZH>
//         <GHFMC>购货方名称</GHFMC>
//         <GHF_NSRSBH>购货方识别号</GHF_NSRSBH>
//         <GHF_SF>购货方省份</GHF_SF>
//         <GHF_DZ>购货方地址</GHF_DZ>
//         <GHF_GDDH>购货方固定电话</GHF_GDDH>
//         <GHF_SJ>购货方手机</GHF_SJ>
//         <GHF_EMAIL>购货方邮箱</GHF_EMAIL>
//         <GHFQYLX>购货方企业类型</GHFQYLX>
//         <GHF_YHZH>购货方银行、账号</GHF_YHZH>
//         <HY_DM>行业代码</HY_DM>
//         <HY_MC>行业名称</HY_MC>
//         <KPY>开票员</KPY>
//         <SKY>收款员</SKY>
//         <FHR>复核人</FHR>
//         <KPRQ>开票日期</KPRQ>
//         <KPLX>开票类型</KPLX>
//         <YFP_DM>原发票代码</YFP_DM>
//         <YFP_HM>原发票号码</YFP_HM>
//         <CZDM>操作代码</CZDM>
//         <QD_BZ>清单标志</QD_BZ>
//         <QDXMMC>清单发票项目名称</QDXMMC>
//         <CHYY>冲红原因</CHYY>
//         <TSCHBZ>特殊冲红标志</TSCHBZ>
//         <KPHJJE>价税合计金额</KPHJJE>
//         <HJBHSJE>合计不含税金额</HJBHSJE>
//         <HJSE>合计税额</HJSE>
//         <BZ>备注</BZ>
//         <BYZD1>备用字段</BYZD1>
//         <BYZD2>备用字段</BYZD2>
//         <BYZD3>备用字段</BYZD3>
//         <BYZD4>备用字段</BYZD4>
//         <BYZD5>备用字段</BYZD5>
//     </FPKJXX_FPTXX>
//     <FPKJXX_XMXXS class="FPKJXX_XMXX;" size="1">
//         <FPKJXX_XMXX>
//             <XMMC>项目名称</XMMC>
//             <XMDW>项目单位</XMDW>
//             <GGXH>规格型号</GGXH>
//             <XMSL>项目数量</XMSL>
//             <HSBZ>含税标志</HSBZ>
//             <XMDJ>项目单价</XMDJ>
//             <FPHXZ>发票行性质</FPHXZ>
//             <SPBM>商品编码</SPBM>
//             <ZXBM>自行编码</ZXBM>
//             <YHZCBS>优惠政策标识</YHZCBS>
//             <LSLBS>零税率标识</LSLBS>
//             <ZZSTSGL>增值税特殊管理</ZZSTSGL>
//             <KCE>扣除额</KCE>
//             <XMJE>项目金额</XMJE>
//             <SL>税率</SL>
//             <SE>税额</SE>
//             <BYZD1>备用字段</BYZD1>
//             <BYZD2>备用字段</BYZD2>
//             <BYZD3>备用字段</BYZD3>
//             <BYZD4>备用字段</BYZD4>
//             <BYZD5>备用字段</BYZD5>
//         </FPKJXX_XMXX>
//     </FPKJXX_XMXXS>
//     <FPKJXX_DDXX class="FPKJXX_DDXX">
//         <DDH>订单号</DDH>
//         <THDH>退货单号</THDH>
//         <DDDATE>订单时间</DDDATE>
//     </FPKJXX_DDXX>
// </REQUEST_FPKJXX>
// 【content中】请求数据项说明:
// 序号	数据项	数据项名称	类型	长度	必须	说明
// 1	FPQQLSH	发票请求唯一流水号	VARCHAR	50	是	每张发票的发票请求唯一流水号无重复，由企业定义。前8位是企业的DSPTBM值。长度限制20~50位,可由数字,字母和下划线组成,不能有特殊字符(如中文,单斜杠,双斜杠等)
// 2	DSPTBM	平台编码	VARCHAR	8	是	由诺e票电子发票平台提供，生产环境详见《交付表》
// 3	NSRSBH	开票方识别号	VARCHAR	20	是	开票金税盘的销方纳税人识别号，由企业提供，测试环境由平台提供
// 4	NSRMC	开票方名称	VARCHAR	200	是	开票金税盘的销方纳税人全称，由企业提供，测试环境由平台提供
// 5	FJH	分机号	NUMBER	3	否	默认：空；可指定分机盘开票
// 6	NSRDZDAH	开票方电子档案号	VARCHAR	20	否	可不填
// 7	SWJG_DM	税务机构代码	VARCHAR	11	否	可不填
// 8	DKBZ	代开标志	VARCHAR	1	是	0:自开,1:代开.默认为自开
// 9	SGBZ	收购标志	VARCHAR	1	否	1、收购票（Y），代开标志为1时，不能填Y，2、非收购票：此字段为空，3、收购票扣除额必须为空，4、收购票税率必须为0，5、成品油发票必须为C（大写），6.成品油票不支持代开
// 10	PYDM	票样代码	VARCHAR	10	否	可不填
// 11	KPXM	主要开票项目	VARCHAR	200	是	主要开票商品，或者第一条商品，取项目信息中第一条数据的项目名称（或传递大类例如：办公用品）
// 12	BMB_BBH	编码表版本号	VARCHAR	20	是	编码表版本号。该字段为税收分类编码版本号，最新版本号可关注上海爱信诺微信公众号的开票软件升级公告
// 13	XHF_NSRSBH	销方识别号	VARCHAR	20	是	开票金税盘的销方纳税人识别号，由企业提供，测试环境由平台提供
// 14	XHFMC	销方名称	VARCHAR	100	是	必填，纳税人名称
// 15	XHF_DZ	销方地址	VARCHAR	80	是
// 16	XHF_DH	销方电话	VARCHAR	20	否
// 17	XHF_YHZH	销方银行、账号	VARCHAR	100	否
// 18	GHFMC	购货方名称	VARCHAR	100	是	购货方名称，即发票抬头。
// 19	GHF_NSRSBH	购货方税号	VARCHAR	20	否	企业消费，如果填写识别号，需要传输过来
// 20	GHF_DZ	购货方地址	VARCHAR	80	否
// 21	GHF_SF	购货方省份	VARCHAR	20	否
// 22	GHF_GDDH	购货方固定电话	VARCHAR	20	否
// 23	GHF_SJ	购货方手机	VARCHAR	20	否
// 24	GHF_EMAIL	购货方邮箱	VARCHAR	50	否
// 25	GHFQYLX	购货方企业类型	VARCHAR	2	是	01：企业 ，02：机关事业单位， 03：个人 ，04：其它
// 26	GHF_YHZH	购货方银行、账号	VARCHAR	100	否
// 27	HY_DM	行业代码	VARCHAR	10	否	可不填
// 28	HY_MC	行业名称	VARCHAR	40	否	可不填
// 29	KPY	开票员	VARCHAR	8	是
// 30	SKY	收款员	VARCHAR	8	否
// 31	FHR	复核人	VARCHAR	8	否
// 32	KPRQ	开票日期	DATETIME		否	格式YYY-MM-DD HH:MI:SS(由开票系统生成)
// 33	KPLX	开票类型	NUMBER	1	是	1：正票，2：红票
// 34	YFP_DM	原发票代码	VARCHAR	12	否	如果CZDM不是10或KPLX为红票时候都是必录
// 35	YFP_HM	原发票号码	VARCHAR	8	否	如果CZDM不是10或KPLX为红票时候都是必录
// 36	TSCHBZ	特殊冲红标志	VARCHAR	1	否	可不填
// 37	CZDM	操作代码	VARCHAR	2	是	10：正票正常开具，20：退货折让红票
// 38	QD_BZ	清单标志	VARCHAR	1	是	默认为0(商品明细大于8行，平台自动生成清单)。
// 39	QDXMMC	清单发票项目名称	VARCHAR	200	否	清单标识（QD_BZ）为0不进行处理。
// 40	CHYY	冲红原因	VARCHAR	200	否	冲红时填写，由企业定义
// 41	KPHJJE	价税合计金额	DOUBLE	16	是	小数点后2位，以元为单位精确到分
// 42	HJBHSJE	合计不含税金额。所有商品行不含税金额之和。	DOUBLE	20	是	小数点后2位，以元为单位精确到分（单行商品金额之和）。平台处理价税分离，此值传0
// 43	HJSE	合计税额。所有商品行税额之和。	DOUBLE	20	是	小数点后2位，以元为单位精确到分(单行商品税额之和)，平台处理价税分离，此值传0
// 44	BZ	备注	VARCHAR	200	否	蓝票长度：200;红票长度：144
// 45	BYZD1	备用字段1	VARCHAR	不定	否	该字段为平台预留字段，不可用
// 46	BYZD2	备用字段2	VARCHAR	不定	否	该字段为平台预留字段，不可用
// 47	BYZD3	备用字段3	VARCHAR	不定	否	该字段为平台预留字段，不可用
// 48	BYZD4	备用字段4	VARCHAR	不定	否	该字段为平台预留字段，不可用
// 49	BYZD5	备用字段5	VARCHAR	不定	否	该字段为平台预留字段，不可用
// (XMXX)项目信息（发票明细）（多条）
// 序号	数据项	数据项名称	类型	长度	必须	说明
// 1	XMMC	项目名称	VARCHAR	90	是	如FPHXZ=1，则此商品行为折扣行，此版本折扣行不允许多行折扣，折扣行必须紧邻被折扣行，项目名称必须与被折扣行一致。
// 2	XMDW	项目单位	VARCHAR	20	否	单位名称
// 3	GGXH	规格型号	VARCHAR	40	否	规格型号
// 4	XMSL	项目数量	DOUBLE	24	否	小数点后8位, 小数点后都是0时，PDF上只显示整数
// 5	HSBZ	含税标志	VARCHAR	1	是	表示项目单价和项目金额是否含税。0表示都不含税，1表示都含税。
// 6	FPHXZ	发票行性质	VARCHAR	1	是	0：正常行，1：折扣行，2：被折扣行
// 7	XMDJ	项目单价	DOUBLE	24	是	小数点后8位小数点后都是0时，PDF上只显示2位小数；否则只显示至最后一位不为0的数字；（正票和红票单价都大于‘0’）
// 8	SPBM	商品编码	VARCHAR	19	是	商品税收分类编码，由企业提供，技术人员需向企业财务核实，不足19位后面补‘0’，需与企业实际销售商品相匹配，也可关注“上海爱信诺”微信公众号的升级通知
// 9	ZXBM	自行编码	VARCHAR	16	否
// 10	YHZCBS	优惠政策标识	VARCHAR	1	是	0：不使用，1：使用
// 11	LSLBS	零税率标识	VARCHAR	1	否	空：非零税率， 0：出口零税，1：免税，2：不征税，3普通零税率
// 12	ZZSTSGL	增值税特殊管理	VARCHAR	50	否	税收优惠政策内容，当YHZCBS为1时必填，LSLBS为0填写出口零税，LSLBS为1填写免税，LSLBS为2填写不征税
// 13	KCE	扣除额	DOUBLE	20	否	单位元，小数点2位小数 不能大于不含税金额 说明如下： 1.差额征税的发票如果没有折扣的话，只能允许一条商品行。 2.具体差额征税发票的计算方法如下： 不含税差额 = 不含税金额 - 扣除额； 税额 = 不含税差额*税率。3.如果需要开具差额征税且扣除额为0的票，蓝票则需要在备注中以：'差额征税：+扣除额。'开头;红票需要在备注中以：'差额征税。'开头;扣除额为0，备注不添加上述内容，则开具非差额征税的票。
// 14	XMJE	项目金额	DOUBLE	16	是	小数点后2位，以元为单位精确到分。 等于=单价*数量，根据含税标志，确定此金额是否为含税金额。
// 15	SL	税率	VARCHAR	10	是	商品的税率，如0.03，0.06等
// 16	SE	税额	DOUBLE	20	否	小数点后2位，以元为单位精确到分
// 17	BYZD1	备用字段1	VARCHAR	不定	否
// 18	BYZD2	备用字段1	VARCHAR	不定	否
// 19	BYZD3	备用字段1	VARCHAR	不定	否
// 20	BYZD4	备用字段1	VARCHAR	不定	否
// 21	BYZD5	备用字段1	VARCHAR	不定	否
// (DDXX)订单信息
// 序号	数据项	数据项名称	类型	长度	必须	说明
// 1	DDH	订单号	VARCHAR	50	是
// 2	THDH	退货单号	VARCHAR	20	否
// 3	DDDATE	订单时间	DATETIME		否
// 返回报文示例(只有外层报文，Content内容为空，无数据项):

// DSPTBM: "P1000001"
// FPQQLSH: "d222311v"
// NSRSBH: "913101010000000090"
// XHF_NSRSBH: "913101010000000090"
// authorizationCode: "NH873FG4KW"

// bankaccount: "622688888888"
// bankname: "4开户银行00000"
// company_address: "商派微商城地址"
// company_phone: "02111223344"
// content: "上海航信模拟测试"
// enterprise_id: "sssss11"
// fapiao_switch: "true"
// group_id: "qqqqqq22"
// hangxin_auth_code: "qqqq"
// hangxin_switch: "true"
// hangxin_tax_no: "sss"
// registration_number: "3税号000000"
// tax_rate: "13"
// user_name: "开票员"
