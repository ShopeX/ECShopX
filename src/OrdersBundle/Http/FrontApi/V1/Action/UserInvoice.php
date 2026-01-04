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

namespace OrdersBundle\Http\FrontApi\V1\Action;

use CompanysBundle\Services\SettingService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\OrderInvoiceService;

class UserInvoice extends Controller
{
    protected $invoiceService;

    public function __construct()
    {
        $this->invoiceService = new OrderInvoiceService();
    }

    /**
     * /wxapp/order/invoice/apply
     * 创建申请发票
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function createInvoice(Request $request)
    {
        $authInfo = $request->get('auth');

        // 获取输入参数
        $data = $request->all();

        $rules = [
            'invoice_type' => ['required|in:enterprise,individual', '发票类型必填'],
            'company_title' => ['required', '抬头必填'],
            // 'invoice_item' => ['required', '请选择需要开票的商品'],
            'company_tax_number' => ['required_if:invoice_type,enterprise', '发票类型为企业税号必填'],
            'email' => ['required_without:mobile', '手机号和邮箱至少填写一个'],
            'mobile' => ['required_without:email', '手机号和邮箱至少填写一个'],
        ];

        if(!isset($data['order_id'])){
            $rules['invoice_item'] = ['required', '请选择需要开票的商品'];
        }

        // 利用redis的incr锁根据order_id进行10秒的防抖 防止同时提交多个申请发票
        if(isset($data['order_id'])){
            $redis = app('redis');
            $redisKey = 'create_invoice_order_times:'.$data['order_id'];
            $locknum = $redis->incr($redisKey);
            app('log')->info("lock-check:".__FUNCTION__.':'.__LINE__.':redisKey:'.$redisKey.':locknum:'.$locknum);
            if($locknum > 1){
                throw new ResourceException('您有发票申请正在处理，请稍后再试');
            }
            $redis->expire($redisKey,10);
            app('log')->info("lock-set:10s:".__FUNCTION__.':'.__LINE__.':redisKey:'.$redisKey.':locknum:'.$locknum);
        }

        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_apply_error', ['errorMessage' => $errorMessage]));
        }

        $data['user_id'] = $authInfo['user_id'];
        $data['company_id'] = $authInfo['company_id'];

        // 获取订单配置信息中的类型
        $settingService = new SettingService();
        $invoiceSetting = $settingService->getInvoiceSetting($data['company_id']);
        $invoiceType = $invoiceSetting['invoice_limit'] ?? 'order';

        if(isset($data['order_id'])){
            //invoice_item
            $invoiceItems = [];
            // 获取订单商品信息
            // $orderService = new OrderService();
            $orderInfo = $this->invoiceService->getOrderInfo($data['order_id'],$authInfo);
            app('log')->info(__FUNCTION__.':'.__LINE__.':getOrderInfo:'.json_encode($orderInfo));
            $invoiceItems = $orderInfo['items'];
            app('log')->info(__FUNCTION__.':'.__LINE__.':invoiceItems:'.json_encode($invoiceItems));
        }else{
            $invoiceItems = json_decode($request->input('invoice_item'), true);
        }

        if (empty($invoiceItems)) {
            throw new ResourceException(trans('OrdersBundle/Order.order_items_empty'));
        }

        $data['invoice_item'] = $invoiceItems;
        // 检查是否有有效已开票申请，如果有，则不能申请
        $filter = [
            'order_id' => $data['order_id'],
            'invoice_status' => ['success','pending','fail','inProgress','inProgress'],
        ];
        app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
        $invoiceList = $this->invoiceService->getInvoiceList($filter,1,10,['id'=>"DESC"],true);
        app('log')->info(__FUNCTION__.':'.__LINE__.':invoiceList:'.json_encode($invoiceList));

        $invoiceList = $this->invoiceService->getInvoiceList($filter,1,10,['id'=>"DESC"],true);
        app('log')->info(__FUNCTION__.':'.__LINE__.':invoiceList:'.json_encode($invoiceList));
        if( !empty($invoiceList['list']) ){
            app('log')->info(__FUNCTION__.':'.__LINE__.':invoiceList:'.json_encode("您有发票申请正在处理"));
            throw new ResourceException('您有发票申请正在处理');
        }

        // 设置发票来源
        $data['invoice_source'] = 'user';
        $data['invoice_method'] = 'online';
        if(isset($data['order_id'])){
            $this->invoiceService->checkCreateInvoice($data,$orderInfo);
            $result = $this->invoiceService->createInvoiceOrder($data,$orderInfo);
        }else{
            $result = $this->invoiceService->createUserInvoice($data, $invoiceType);
        }

        return $this->response->array($result);
    }

    /**
     * 更新发票信息
     * /wxapp/order/invoice/update
     * 
     */
    public function updateInvoice(Request $request)
    {
        // 0x53686f704578
        $authInfo = $request->get('auth');
        $userId = $authInfo['user_id'];
        $data = $request->all();
        if(isset($data['invoice_id']) && isset($data['invoice_status']) && $data['invoice_status'] == "cancel"){
            $data['invoice_status'] = "cancel";
            $invoiceDetail = $this->invoiceService->getInvoiceDetail($data['invoice_id'],$authInfo);
            if($invoiceDetail['invoice_status'] == "cancel" ){
                throw new ResourceException('发票已取消');
            }
            if($invoiceDetail['invoice_status'] == "pending" || $invoiceDetail['invoice_status'] == "fail"){
                $result = $this->invoiceService->updateInvoice($data['invoice_id'], $data);
                return $this->response->array($result);
            }else{
                throw new ResourceException('发票当前状态不能取消');
            }
        }
        
        $rules = [
            'invoice_id' => ['required', '发票ID必填'],
            'invoice_type' => ['required|in:enterprise,individual', '发票类型必填'],
            'company_title' => ['required', '抬头必填'],
        ];
        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_update_error', ['errorMessage' => $errorMessage]));
        }
        $data['user_id'] = $userId;
        $result = $this->invoiceService->updateInvoice($data['invoice_id'], $data);
        return $this->response->array($result);
    }

    /**
     * /wxapp/order/invoice/list
     * 获取用户发票列表
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getUserInvoiceList(Request $request)
    {
        $authInfo = $request->get('auth');
        $userId = $authInfo['user_id'];
        $companyId = $authInfo['company_id'];
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);

        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId
        ];

        // 发票状态筛选
        if ($request->has('regionauth_id')) {
            $filter['regionauth_id'] = $request->input('regionauth_id');
        }


        $result = $this->invoiceService->getInvoiceList($filter, $page, $pageSize,['id'=>"DESC"],true);

        return $this->response->array($result);
    }

    /**
     * /wxapp/order/invoice/info/{id}
     * 获取用户发票详情
     *
     * @param Request $request
     * @param int $id 发票ID
     * @return \Illuminate\Http\Response
     */
    public function getUserInvoiceDetail(Request $request, $id)
    {
        $authInfo = $request->get('auth');
        $userId = $authInfo['user_id'];
        $companyId = $authInfo['company_id'];

        $filter = [
            'id' => $id,
            'user_id' => $userId,
            'company_id' => $companyId
        ];

        $invoiceDetail = $this->invoiceService->getInvoiceDetail($id,$companyId);

        if (empty($invoiceDetail)) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_not_found'));
        }

        return $this->response->array($invoiceDetail);
    }

    /**
     * /wxapp/order/invoice/resend
     * 重发发票到邮箱
     *
     * @param Request $request
     * @param int $id 发票ID
     * @return \Illuminate\Http\Response
     */
    public function resendInvoiceEmail(Request $request)
    {
        $authInfo = $request->get('auth');
        $userId = $authInfo['user_id'];
        $companyId = $authInfo['company_id'];

        // 检查邮箱参数
        if (!$request->has('confirm_email')) {
            throw new ResourceException(trans('OrdersBundle/Order.email_required'));
        }

        $id = $request->input('id');
        $confirm_email = $request->input('confirm_email');

        // 检查邮箱格式
        if (!filter_var($confirm_email, FILTER_VALIDATE_EMAIL)) {
            throw new ResourceException(trans('OrdersBundle/Order.email_invalid_format'));
        }

        $filter = [
            'id' => $id,
            'user_id' => $userId,
            'company_id' => $companyId
        ];

        // 调用服务重发邮件
        $result = $this->invoiceService->resendInvoiceEmail($filter, $confirm_email);

        if (!$result) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_resend_failed'));
        }

        return $this->response->array(['message' => '发票已重新发送到您的邮箱']);
    }

    /**
     * GET /wxapp/order/invoice/setting
     * 获取开票配置
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getInvoiceSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        $settingService = new SettingService();
        $inputData = $settingService->getInvoiceSetting($companyId);
        return $this->response->array($inputData);
    }

    /**
     * POST /wxapp/order/invoice/setting
     * 设置开票配置
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function setInvoiceSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $data = $request->all();
        app('log')->info(__FUNCTION__.':'.__LINE__.':data:'.json_encode($data));
        // 验证参数
        // - **invoice_status**：是否启用开票功能（true/false）
        // - **invoice_limit**：开票维度（item=SKU维度，order=订单维度）
        // - **invoice_method**：开票渠道（offline=线下，online=线上）
        // - **channel**：开票渠道（1=线下，2=线上），与 invoice_method 可做映射
        // - **freight_invoice**：运费是否开票（1=不支持，2=支持）
        // - **freight_name**：运费开票名称（freight_invoice=2 时必填）
        // - **apply_type**：开票申请方式（1=结算页，2=已支付订单）
        // - **invoice_open_term**：可开票期限（月，0为不限制）
        // - **special_invoice**：专用发票展示（1=企业抬头可选，2=不可选）
        // - **apply_node**：申请开票节点（1=确认收货，2=过售后期）
        // - **invoice_seller_type**：开票方类型（1=平台，2=店铺，3 商场（弗洛伦萨））
        // - **freight_tax_rate**：运费税率 13%

        $rules = [
            'invoice_status' => ['required|in:true,false', '是否启用开票功能必填'],
            'invoice_limit' => ['required|in:item,order', '开票维度必填'],
            'invoice_method' => ['required|in:offline,online', '开票渠道必填'],
            'channel' => ['required|in:1,2', '开票渠道必填'],
            'freight_invoice' => ['required|in:1,2', '运费是否开票必填'],
            'freight_name' => ['required_if:freight_invoice,2', '运费开票名称必填'],
            'apply_type' => ['required|in:1,2', '开票申请方式必填'],
            'invoice_open_term' => ['required|integer|min:0', '可开票期限最小为0的整数'],
            'special_invoice' => ['required|in:1,2', '专用发票展示必填'],
            'apply_node' => ['required|in:1,2', '申请开票节点必填'],
            'invoice_seller_type' => ['required|in:1,2', '开票方类型必填'],
            'freight_tax_rate' => ['required|numeric|min:0|max:100', '运费税率最小为0，最大为100的整数'],
        ];
        app('log')->info(__FUNCTION__.':'.__LINE__.':rules:'.json_encode($rules));
        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_config_error', ['errorMessage' => $errorMessage]));
        }

        //只取data中 rule 里面的字段到 dataInvoiceSetting 不使用 array_intersect_key 和 array_flip
        $dataInvoiceSetting = [];
        app('log')->info(__FUNCTION__.':'.__LINE__.':data:'.json_encode($data));
        foreach ($rules as $key => $value) {
            if (isset($data[$key])) {
                $dataInvoiceSetting[$key] = $data[$key];
            }
        }

        $settingService = new SettingService();
        app('log')->info(__FUNCTION__.':'.__LINE__.':dataInvoiceSetting:'.json_encode($dataInvoiceSetting));
        $inputData = $settingService->setInvoiceSetting($companyId, $dataInvoiceSetting);
        app('log')->info(__FUNCTION__.':'.__LINE__.':inputData:'.json_encode($inputData));
        return $this->response->array($inputData);
    }

    public function getInvoiceProtocol(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'] ?? 1;
        // $settingService = new SettingService();
        $returnData = $this->invoiceService->getInvoiceProtocol($companyId);
        if(is_array($returnData)){
            return $this->response->array($returnData);
        }else{
            return $this->response->array(['protocol' => []]);
        }
    }
}
