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

namespace AdaPayBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\SubMerchantService;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

class SubMerchant extends Controller
{
    /**
     * @SWG\Get(
     *     path="/adapay/sub_approve/list",
     *     summary="子商户审批列表",
     *     tags={"子商户审批"},
     *     description="子商户审批列表",
     *     operationId="subApproveLists",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="status", description="审批状态" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="user_name", description="商户名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="address", description="地区" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_start", description="开始日期" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_end", description="结束日期" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page", description="页码" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page_size", description="页数" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1"),
     *                          @SWG\Property( property="user_name", type="string", example="111"),
     *                          @SWG\Property( property="company_id", type="string", example="1"),
     *                          @SWG\Property( property="entry_id", type="string", example="1"),
     *                          @SWG\Property( property="apply_type", type="string", example="dealer"),
     *                          @SWG\Property( property="address", type="string", example="上海-徐汇"),
     *                          @SWG\Property( property="create_time", type="string", example="23123123"),
     *                          @SWG\Property( property="status", type="string", example="WAIT_APPROVE"),
     *                          @SWG\Property( property="update_time", type="string", example="null"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function subApproveLists(Request $request)
    {
        // Built with ShopEx Framework
        $companyId = app('auth')->user()->get('company_id');

        $params = $request->all('status', 'user_name', 'address', 'time_start', 'time_end');
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $subMerchantService = new SubMerchantService();
        $result = $subMerchantService->subApproveListsService($companyId, $params, $page, $pageSize);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/sub_approve/info/{id}",
     *     summary="子商户审批详情",
     *     tags={"子商户审批"},
     *     description="子商户审批详情",
     *     operationId="subApproveInfo",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="path", type="string", required=true, name="id", description="审批id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="entry_apply_info", type="object",
     *                          @SWG\Property( property="id", type="string", example="1"),
     *                          @SWG\Property( property="user_name", type="string", example="111", description="名称"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="entry_id", type="string", example="1", description="开户id"),
     *                          @SWG\Property( property="apply_type", type="string", example="distributor", description="开户来源"),
     *                          @SWG\Property( property="address", type="string", example="上海-徐汇"),
     *                          @SWG\Property( property="status", type="string", example="WAIT_APPROVE"),
     *                          @SWG\Property( property="create_time", type="string", example="23123123"),
     *                          @SWG\Property( property="update_time", type="string", example="null"),
     *                  ),
     *                  @SWG\Property( property="entry_info", type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="主键id"),
     *                          @SWG\Property( property="app_id", type="string", example="1233", description="应用app_id"),
     *                          @SWG\Property( property="location", type="string", example="2121", description="用户地址"),
     *                          @SWG\Property( property="pid", type="string", example="0", description="父ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="operator_id", type="string", example="1", description="操作者id"),
     *                          @SWG\Property( property="operator_type", type="string", example="distributor", description="操作者类型:distributor-店铺;dealer-经销"),
     *                          @SWG\Property( property="email", type="string", example="", description="用户邮箱"),
     *                          @SWG\Property( property="member_type", type="string", example="person", description="账户类型"),
     *                          @SWG\Property( property="gender", type="string", example="", description="性别"),
     *                          @SWG\Property( property="nickname", type="string", example="", description="用户昵称"),
     *                          @SWG\Property( property="tel_no", type="string", example="", description="用户手机号"),
     *                          @SWG\Property( property="user_name", type="string", example="", description="用户姓名"),
     *                          @SWG\Property( property="cert_type", type="string", example="00", description="证件类型 仅支持：00-身份证"),
     *                          @SWG\Property( property="cert_id", type="string", example="", description="证件号"),
     *                          @SWG\Property( property="audit_state", type="string", example="", description="审核状态，状态包括：A-待审核；B-审核失败；C-开户失败；D-开户成功但未创建结算账户；E-开户和创建结算账户成功"),
     *                          @SWG\Property( property="audit_desc", type="string", example="", description="审核结果描述"),
     *                          @SWG\Property( property="status", type="string", example="", description="当前交易状态"),
     *                          @SWG\Property( property="error_info", type="string", example="", description="错误描述"),
     *                          @SWG\Property( property="create_time", type="string", example="111", description="创建时间"),
     *                          @SWG\Property( property="update_time", type="string", example="null", description="更新时间"),
     *                          @SWG\Property( property="area", type="string", example="null", description="省市地区"),
     *                          @SWG\Property( property="order_no", type="string", example="null", description="请求订单号"),
     *                          @SWG\Property( property="member_id", type="string", example="null", description="member_id"),
     *                          @SWG\Property( property="name", type="string", example="null", description="企业名称"),
     *                          @SWG\Property( property="prov_code", type="string", example="null", description="省份编码"),
     *                          @SWG\Property( property="area_code", type="string", example="null", description="地区编码"),
     *                          @SWG\Property( property="social_credit_code", type="string", example="null", description="统一社会信用码"),
     *                          @SWG\Property( property="social_credit_code_expires", type="string", example="null", description="统一社会信用证有效期"),
     *                          @SWG\Property( property="business_scope", type="string", example="null", description="经营范围"),
     *                          @SWG\Property( property="legal_person", type="string", example="null", description="法人姓名"),
     *                          @SWG\Property( property="legal_cert_id", type="string", example="null", description="法人身份证号码"),
     *                          @SWG\Property( property="legal_cert_id_expires", type="string", example="null", description="法人身份证有效期"),
     *                          @SWG\Property( property="legal_mp", type="string", example="null", description="法人手机号"),
     *                          @SWG\Property( property="address", type="string", example="null", description="企业地址"),
     *                          @SWG\Property( property="zip_code", type="string", example="null", description="邮编"),
     *                          @SWG\Property( property="telphone", type="string", example="null", description="企业电话"),
     *                          @SWG\Property( property="attach_file", type="string", example="null", description="上传附件"),
     *                          @SWG\Property( property="attach_file_name", type="string", example="null", description="附件文件名"),
     *                          @SWG\Property( property="confirm_letter_file", type="string", example="null", description="经销商确认函附件"),
     *                          @SWG\Property( property="confirm_letter_file_name", type="string", example="null", description="经销商确认函附件文件名"),
     *                          @SWG\Property( property="bank_code", type="string", example="null", description="银行代码"),
     *                          @SWG\Property( property="bank_acct_type", type="string", example="null", description="行账户类型：1-对公；2-对私，"),
     *                          @SWG\Property( property="card_no", type="string", example="null", description="银行卡号"),
     *                          @SWG\Property( property="card_name", type="string", example="null", description="银行卡对应的户名"),
     *                          @SWG\Property( property="bank_card_id", type="string", example="null", description="银行卡号"),
     *                          @SWG\Property( property="bank_card_name", type="string", example="null", description="银行卡对应的户名"),
     *                          @SWG\Property( property="bank_cert_id", type="string", example="null", description="开户证件号"),
     *                          @SWG\Property( property="bank_tel_no", type="string", example="null", description="银行预留手机号"),
     *                          @SWG\Property( property="bank_name", type="string", example="null", description="银行名称"),
     *                  ),
     *                  @SWG\Property( property="distributor_info", type="object",
     *                          @SWG\Property( property="distributor_id", type="string", example="1", description="店铺id"),
     *                          @SWG\Property( property="shop_id", type="string", example="1", description="门店id"),
     *                          @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="mobile", type="string", example="13412341234", description="手机号"),
     *                          @SWG\Property( property="address", type="string", example="宜山路700号(近桂林路)", description="地址"),
     *                          @SWG\Property( property="name", type="string", example="普天信息产业园测试1", description="名称"),
     *                          @SWG\Property( property="auto_sync_goods", type="string", example="true", description="自动同步总部商品"),
     *                          @SWG\Property( property="logo", type="string", example="null", description="店铺logo"),
     *                          @SWG\Property( property="contract_phone", type="string", example="0", description="联系电话"),
     *                          @SWG\Property( property="banner", type="string", example="null", description="店铺banner"),
     *                          @SWG\Property( property="contact", type="string", example="测试人", description="联系人"),
     *                          @SWG\Property( property="is_valid", type="string", example="false", description="是否有效"),
     *                          @SWG\Property( property="lng", type="string", example="121.417435", description="地图纬度"),
     *                          @SWG\Property( property="lat", type="string", example="31.176539", description="地图经度"),
     *                          @SWG\Property( property="child_count", type="string", example="15"),
     *                          @SWG\Property( property="is_default", type="string", example="false", description="是否默认"),
     *                          @SWG\Property( property="is_audit_goods", type="string", example="true", description="是否审核店铺商品"),
     *                          @SWG\Property( property="is_ziti", type="string", example="true", description="是否支持自提"),
     *                          @SWG\Property( property="regions_id", type="array",
     *                              @SWG\Items( type="string", example="310000"),
     *                          ),
     *                          @SWG\Property( property="regions", type="array",
     *                              @SWG\Items( type="string", example="上海市"),
     *                          ),
     *                          @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                          @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                          @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                          @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                          @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                          @SWG\Property( property="area", type="string", example="黄浦区", description="区"),
     *                          @SWG\Property( property="hour", type="string", example="08:00 - 20:00", description="营业时间，格式11:11-12:12"),
     *                          @SWG\Property( property="created", type="string", example="1560930295"),
     *                          @SWG\Property( property="updated", type="string", example="1612262789"),
     *                          @SWG\Property( property="shop_code", type="string", example="null", description="店铺号"),
     *                          @SWG\Property( property="wechat_work_department_id", type="string", example="0", description="企业微信的部门ID"),
     *                          @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                          @SWG\Property( property="regionauth_id", type="string", example="1", description="地区id"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description="是否开启"),
     *                          @SWG\Property( property="rate", type="string", example="", description="货币汇率(与人民币)"),
     *                          @SWG\Property( property="is_dada", type="string", example="false"),
     *                          @SWG\Property( property="business", type="string", example="null"),
     *                          @SWG\Property( property="dada_shop_create", type="string", example="false"),
     *                          @SWG\Property( property="review_status", type="string", example="false"),
     *                          @SWG\Property( property="dealer_id", type="string", example="1"),
     *                          @SWG\Property( property="split_ledger_info", type="string", example="null"),
     *                          @SWG\Property( property="store_address", type="string", example="上海市黄浦区宜山路700号(近桂林路)"),
     *                          @SWG\Property( property="store_name", type="string", example="普天信息产业园测试1", description="店铺名称"),
     *                          @SWG\Property( property="phone", type="string", example="13412341234"),
     *                          @SWG\Property( property="business_list", type="object",
     *                                  @SWG\Property( property="1", type="string", example="食品小吃"),
     *                                  @SWG\Property( property="2", type="string", example="饮料"),
     *                                  @SWG\Property( property="3", type="string", example="鲜花绿植"),
     *                                  @SWG\Property( property="5", type="string", example="其他"),
     *                                  @SWG\Property( property="8", type="string", example="文印票务"),
     *                                  @SWG\Property( property="9", type="string", example="便利店"),
     *                                  @SWG\Property( property="13", type="string", example="水果生鲜"),
     *                                  @SWG\Property( property="19", type="string", example="同城电商"),
     *                                  @SWG\Property( property="20", type="string", example="医药"),
     *                                  @SWG\Property( property="21", type="string", example="蛋糕"),
     *                                  @SWG\Property( property="24", type="string", example="酒品"),
     *                                  @SWG\Property( property="25", type="string", example="小商品市场"),
     *                                  @SWG\Property( property="26", type="string", example="服装"),
     *                                  @SWG\Property( property="27", type="string", example="汽修零配"),
     *                                  @SWG\Property( property="28", type="string", example="数码家电"),
     *                                  @SWG\Property( property="29", type="string", example="小龙虾"),
     *                                  @SWG\Property( property="50", type="string", example="个人"),
     *                                  @SWG\Property( property="51", type="string", example="火锅"),
     *                                  @SWG\Property( property="53", type="string", example="个护美妆"),
     *                                  @SWG\Property( property="55", type="string", example="母婴"),
     *                                  @SWG\Property( property="57", type="string", example="家居家纺"),
     *                                  @SWG\Property( property="59", type="string", example="手机"),
     *                                  @SWG\Property( property="61", type="string", example="家装"),
     *                          ),
     *                          @SWG\Property( property="is_local_delivery", type="string", example="false", description="商户是否开启同城配"),
     *                          @SWG\Property( property="qqmapimg", type="string", example="false", description=""),
     *                          @SWG\Property( property="dealer_info", type="object",
     *                                  @SWG\Property( property="operator_id", type="string", example="1"),
     *                                  @SWG\Property( property="mobile", type="string", example="13918087430", description="手机号"),
     *                                  @SWG\Property( property="username", type="string", example="欢迎", description="企业名称"),
     *                                  @SWG\Property( property="head_portrait", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/1nDJByqmW2fWRCf9MOPpRLdKxwlTEzLqfgIeP8SVeBAWuyMW3K5ACK4PsCX7vDBkOHKSLFXNQrKy8ibaTPpia7TQ/0?wx_fmt=png"),
     *                                  @SWG\Property( property="split_ledger_info", type="string", example="null", description="分账info"),
     *                          ),
     *                  ),
     *                 @SWG\Property( property="dealer_info", type="object",
     *                         @SWG\Property( property="operator_id", type="string", example="1"),
     *                         @SWG\Property( property="mobile", type="string", example="13918087430", description="手机号"),
     *                         @SWG\Property( property="username", type="string", example="欢迎", description="企业名称"),
     *                         @SWG\Property( property="head_portrait", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/1nDJByqmW2fWRCf9MOPpRLdKxwlTEzLqfgIeP8SVeBAWuyMW3K5ACK4PsCX7vDBkOHKSLFXNQrKy8ibaTPpia7TQ/0?wx_fmt=png"),
     *                         @SWG\Property( property="split_ledger_info", type="string", example="null", description="分账info"),
     *                 ),
     *                  @SWG\Property( property="is_rel_dealer", type="string", example="true", description="是否绑定经销商"),
     *          ),
     *     )),
     * )
     */
    public function subApproveInfo($id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $subMerchantService = new SubMerchantService();
        $result = $subMerchantService->subApproveInfoService($companyId, $id);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        if ($datapassBlock) {
            $result['entry_info']['tel_no'] = data_masking('mobile', (string) $result['entry_info']['tel_no']);
            if ($result['entry_info']['member_type'] == 'corp') {
                $result['entry_info']['legal_person'] = data_masking('truename', (string) $result['entry_info']['legal_person']);
                $result['entry_info']['card_no'] = data_masking('bankcard', (string) $result['entry_info']['card_no']);
                $result['entry_info']['legal_cert_id'] = data_masking('idcard', (string) $result['entry_info']['legal_cert_id']);
            } else {
                $result['entry_info']['user_name'] = data_masking('truename', (string) $result['entry_info']['user_name']);
                $result['entry_info']['cert_id'] = data_masking('idcard', (string) $result['entry_info']['cert_id']);
                $result['entry_info']['bank_card_name'] = data_masking('truename', (string) $result['entry_info']['bank_card_name']);
                $result['entry_info']['bank_tel_no'] = data_masking('mobile', (string) $result['entry_info']['bank_tel_no']);
                $result['entry_info']['bank_card_id'] = data_masking('bankcard', (string) $result['entry_info']['bank_card_id']);
                $result['entry_info']['bank_cert_id'] = data_masking('idcard', (string) $result['entry_info']['bank_cert_id']);
            }
            if (isset($result['entry_apply_info']) && $result['entry_apply_info']) {
                $result['entry_apply_info']['user_name'] = data_masking('truename', (string) $result['entry_apply_info']['user_name']);
            }
            if (isset($result['dealer_info']) && $result['dealer_info']) {
                $result['dealer_info']['mobile'] = data_masking('mobile', (string) $result['dealer_info']['mobile']);
            }
            if (isset($result['distributor_info']) && $result['distributor_info']) {
                $result['distributor_info']['mobile'] = data_masking('mobile', (string) $result['distributor_info']['mobile']);
                // $result['distributor_info']['store_address'] = data_masking('detailedaddress', (string) $result['distributor_info']['store_address']);
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/sub_approve/save_split_ledger",
     *     summary="子商户审批保存分账信息",
     *     tags={"子商户审批"},
     *     description="子商户审批保存分账信息",
     *     operationId="saveSplitLedger",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="split_ledger_info", description="分账信息 json" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="apply_type", description="申请类型:dealer;distributor" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="save_id", description="保存分账id:operator_id或者distributor_id" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="status", description="审批状态  APPROVED  REJECT" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="comments", description="审批意见" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="id", description="entry_apply_info下的id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function saveSplitLedger(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('split_ledger_info', 'apply_type', 'save_id', 'status', 'comments', 'id', 'is_sms');

        $subMerchantService = new SubMerchantService();
        $result = $subMerchantService->saveSplitLedgerService($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/sub_approve/draw_cash_config",
     *     summary="保存子商户提现限额",
     *     tags={"子商户审批"},
     *     description="保存子商户提现限额",
     *     operationId="draw_cash_config",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="draw_limit", description="暂冻金额" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="draw_limit_list", description="指定商户暂冻金额(json)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="auto_draw_cash", description="是否自动提现(0,1)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="auto_type", description="自动提现类型(day,month)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="auto_day", description="自动提现日期(1-31)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="auto_time", description="自动提现时间(09:30)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="min_cash", description="最小提现金额" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="cash_type", description="取现类型：T1-T+1取现；D1-D+1取现；D0-即时取现。" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function setDrawCashConfig(Request $request)
    {
        // Built with ShopEx Framework
        $subMerchantService = new SubMerchantService();

        $companyId = app('auth')->user()->get('company_id');

        //暂时冻结金额的设置
        $limit = $request->input('draw_limit');

        //暂时冻结金额的设置。指定商户。
        $draw_limit_list = $request->input('draw_limit_list', '');

        //自动提现的设置
        $auto_draw_cash = $request->input('auto_draw_cash', 'N');
        $auto_type = $request->input('auto_type', '');//每月或者每日
        $auto_day = $request->input('auto_day', '');
        $auto_time = $request->input('auto_time', '');
        $min_cash = $request->input('min_cash', '');
        $cash_type = $request->input('cash_type', '');

        $next_time = -1;
        if ($auto_draw_cash == 'Y') {
            if (!$auto_time) {
                throw new ResourceException('自动提现时间错误');
            }

            if ($auto_type == 'day') {//每天提现一次
                $next_time = date('Y-m-d') . " {$auto_time}";
                if (strtotime($next_time) <= time()) {//必须大于当前时间
                    $next_time = date('Y-m-d', strtotime('+1 days')) . " {$auto_time}";
                }
            } elseif ($auto_type == 'month') {//每月提现一次
                if (!$auto_day) {
                    throw new ResourceException('自动提现日期错误');
                }
                $next_time = date('Y-m') . "-{$auto_day} {$auto_time}";
                if (strtotime($next_time) <= time()) {//必须大于当前时间
                    $next_time = date('Y-m', strtotime('+1 month')) . "-{$auto_day} {$auto_time}";
                }
            } else {
                throw new ResourceException('自动提现类型错误');
            }

            $next_time = strtotime($next_time);
        }

        //冻结金额设置
        $result = $subMerchantService->setDrawLimit($companyId, $limit);

        //指定商户冻结设置
        if ($draw_limit_list) {
            if (!is_string($draw_limit_list)) {
                throw new ResourceException('指定商户暂冻金额参数错误');
            }
            $draw_limit_list = json_decode($draw_limit_list, true);
        } else {
            $draw_limit_list = [];//清空指定设置
        }
        $result = $subMerchantService->setDrawLimitList($companyId, $draw_limit_list);

        //自动提现设置
        $autoCashConfig = [
            'auto_draw_cash' => $auto_draw_cash,
            'auto_type' => $auto_type,
            'auto_day' => $auto_day,
            'auto_time' => $auto_time,
            'min_cash' => $min_cash,
            'cash_type' => $cash_type,
            'next_time' => $next_time,//下一次自动提现的时间节点
        ];
        $result = $subMerchantService->setAutoCashConfig($companyId, $autoCashConfig);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/sub_approve/draw_limit",
     *     summary="保存子商户提现限额(废弃)",
     *     tags={"子商户审批"},
     *     description="保存子商户提现限额(废弃)",
     *     operationId="setDrawLimit",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="draw_limit", description="限制金额" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function setDrawLimit(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $limit = $request->input('draw_limit');

        $subMerchantService = new SubMerchantService();
        $result = $subMerchantService->setDrawLimit($companyId, $limit);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/sub_approve/draw_cash_config",
     *     summary="获取子商户提现限额",
     *     tags={"子商户审批"},
     *     description="获取子商户提现限额",
     *     operationId="get_draw_cash_config",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="draw_limit", type="string", example="1000", description="限制金额"),
     *                  @SWG\Property( property="cash_type_options", type="array", description="取现类型：T1-T+1取现；D1-D+1取现；D0-即时取现",
     *                       @SWG\Items(
     *                          @SWG\Property(property="label", type="string", description="标签"),
     *                          @SWG\Property(property="value", type="string", description="标签值"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="auto_config", type="object", description="自动提现设置",
     *                      @SWG\Property( property="auto_draw_cash", type="string", description="是否自动提现(0,1)"),
     *                      @SWG\Property( property="auto_type", type="string", description="自动提现类型(day,month)"),
     *                      @SWG\Property( property="auto_draw_day", type="string", description="自动提现日期(1-31)"),
     *                      @SWG\Property( property="auto_draw_time", type="string", description="自动提现时间(09:30)"),
     *                      @SWG\Property( property="min_cash", type="string", description="最小提现金额"),
     *                      @SWG\Property( property="cash_type", type="string", description="取现类型：T1-T+1取现；D1-D+1取现；D0-即时取现"),
     *                  ),
     *                  @SWG\Property( property="draw_limit_list", type="object", description="冻结金额设置",
     *                      @SWG\Property( property="member_id", type="string", description="商户ID"),
     *                      @SWG\Property( property="merchant_name", type="string", description="商户名称"),
     *                      @SWG\Property( property="location", type="string", description="地址"),
     *                      @SWG\Property( property="contact_name", type="string", description="联系人"),
     *                      @SWG\Property( property="draw_limit", type="string", description="暂冻金额(元)"),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function getDrawCashConfig()
    {
        $companyId = app('auth')->user()->get('company_id');
        $subMerchantService = new SubMerchantService();

        $cashTypeOptions = [
            ['label' => 'T+1取现', 'value' => 'T1'],
            ['label' => 'D+1取现', 'value' => 'D1'],
            ['label' => '即时取现', 'value' => 'D0'],
        ];

        $result = $subMerchantService->getDrawLimit($companyId);
        $result['draw_limit'] = $result['draw_limit'] ?? 0;
        if ($result['draw_limit']) {
            $result['draw_limit'] = bcdiv($result['draw_limit'], 100, 2);
        }
        $result['auto_config'] = $subMerchantService->getAutoCashConfig($companyId);
        $result['draw_limit_list'] = $subMerchantService->getDrawLimitList($companyId);
        $result['cash_type_options'] = $cashTypeOptions;

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/sub_approve/draw_limit",
     *     summary="获取子商户提现限额(废弃)",
     *     tags={"子商户审批"},
     *     description="获取子商户提现限额(废弃)",
     *     operationId="getDrawLimit",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="draw_limit", type="string", example="1000", description="限制金额"),
     *          ),
     *     )),
     * )
     */
    public function getDrawLimit()
    {
        $companyId = app('auth')->user()->get('company_id');
        $subMerchantService = new SubMerchantService();
        $result = $subMerchantService->getDrawLimit($companyId);

        return $this->response->array($result);
    }
}
