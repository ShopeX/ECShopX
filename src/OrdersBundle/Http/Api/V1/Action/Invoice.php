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

namespace OrdersBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\OrderInvoiceService;
use CompanysBundle\Services\SettingService;
use ThirdPartyBundle\Services\FapiaoCentre\BaiwangService;

class Invoice extends Controller
{
    protected $invoiceService;

    public function __construct()
    {
        $this->invoiceService = new OrderInvoiceService();
    }

    /**
     * /order/invoice/list
     * 获取发票申请列表
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getInvoiceList(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $filter = [
            'company_id' => $companyId
        ];

        // 区域筛选
        if ($request->has('regionauth_id')) {
            $filter['regionauth_id'] = $request->input('regionauth_id');
        }

        // 订单号筛选
        if ($request->has('order_id')) {
            $filter['order_id|contains'] = $request->input('order_id');
        }

        // 订单号筛选
        if ($request->has('mobile')) {
            $filter['mobile'] = $request->input('mobile');
        }

        // invoice_type_code
        if ($request->has('invoice_type_code')) {
            $filter['invoice_type_code'] = $request->input('invoice_type_code');
        }

        // 开票申请流水号筛选
        if ($request->has('invoice_apply_bn')) {
            $filter['invoice_apply_bn'] = $request->input('invoice_apply_bn');
        }

        // 抬头名称筛选
        if ($request->has('company_title')) {
            $filter['company_title|like'] = $request->input('company_title');
        }

        // 开票来源筛选
        if ($request->has('invoice_source')) {
            $filter['invoice_source'] = $request->input('invoice_source');
        }

        // 开票来源筛选
        if ($request->has('invoice_status')) {
            $filter['invoice_status'] = $request->input('invoice_status');
        }

        // 创建时间范围筛选
        if ($request->has('start_time')) {
            $filter['create_time|gte'] = $request->input('start_time');
        }

        if ($request->has('end_time')) {
            $filter['create_time|lte'] = $request->input('end_time');
        }

        if($request->has('distributor_id')){
            $filter['order_shop_id'] = $request->input('distributor_id');
        }

        if($request->has('user_card_code')){
            $filter['user_card_code'] = $request->input('user_card_code');
        }
        //email
        if($request->has('email')){
            $filter['email'] = $request->input('email');
        }
     

        $result = $this->invoiceService->getInvoiceList($filter, $page, $pageSize);

        return $this->response->array($result);
    }

    /**
     * /order/invoice/info/{id}
     * 获取发票申请详情
     *
     * @param Request $request
     * @param int $id 发票ID
     * @return \Illuminate\Http\Response
     */
    public function getInvoiceDetail(Request $request, $id)
    {
        $companyId  = app('auth')->user()->get('company_id');

        $invoiceDetail = $this->invoiceService->getInvoiceDetail($id, $companyId);

        if (empty($invoiceDetail)) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_not_found'));
        }

        return $this->response->array($invoiceDetail);
    }

    /**
     * /order/invoice/update/{id}
     * 编辑发票申请
     *
     * @param Request $request
     * @param int $id 发票ID
     * @return \Illuminate\Http\Response
     */
    public function updateInvoice(Request $request, $id)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $data = $request->all();

        // 确保只更新同公司的发票
        $filter = [
            'id' => $id,
            'company_id' => $companyId
        ];

        $invoice = $this->invoiceService->getInfo($filter);

        if (empty($invoice)) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_not_found'));
        }

        $operatorInfo = [
            'operator_id' => app('auth')->user()->get('operator_id'),
            'type' => 'admin'
        ];

        $result = $this->invoiceService->updateInvoice($id, $data, $operatorInfo);

        return $this->response->array($result);
    }

    /**
     * /order/invoice/updateremark/{id}
     * 更新发票备注
     *
     * @param Request $request
     * @param int $id 发票ID
     * @return \Illuminate\Http\Response
     */
    public function updateInvoiceRemark(Request $request, $id)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $remark = $request->input('remark', '');

        // 确保只更新同公司的发票
        $filter = [
            'id' => $id,
            'company_id' => $companyId
        ];

        $invoice = $this->invoiceService->getInfo($filter);

        if (empty($invoice)) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_not_found'));
        }

        $operatorInfo = [
            'operator_id' => app('auth')->user()->get('operator_id'),
            'type' => 'admin'
        ];

        $result = $this->invoiceService->updateInvoiceRemark($id, $remark, $operatorInfo);

        return $this->response->array($result);
    }

    /**
     * /order/invoice/log/list
     * 获取发票操作日志列表
     *
     * @param Request $request
     * @param int $id 发票ID
     * @return \Illuminate\Http\Response
     */
    public function getInvoiceLogList(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $invoice_id = $request->input('invoice_id');
        // 确保只查询同公司的发票日志
        $filter = [
            'id' => $invoice_id,
            'company_id' => $companyId
        ];

        $invoice = $this->invoiceService->getInfo($filter);
        $result = ['list'=>[],'total_count'=>0];
        if (empty($invoice)) {
            return $this->response->array($result);
        }

        $result = $this->invoiceService->getInvoiceLogList($invoice_id, $page, $pageSize);

        return $this->response->array($result);
    }

    /**
     * /order/invoice/retryFailedInvoice
     * 获取发票操作日志列表
     *
     * @param Request $request
     * @param int $id 发票ID
     * @return \Illuminate\Http\Response
     */
    public function retryFailedInvoice(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $operatorId = app('auth')->user()->get('operator_id');
        app('log')->info(__FUNCTION__.':'.__LINE__.':operatorId:'.$operatorId);
        
        $invoice_id = $request->input('invoice_id');
        if (!$invoice_id) {
            throw new ResourceException('发票ID缺失（invoice_id）');
        }
        $result = $this->invoiceService->retryFailedInvoice($invoice_id,$companyId,$operatorId);
        if($result){
            $res = array('status'=>true);
        }else{
            $res = array('status'=>false);
        }
        return $this->response->array($res);
    }
    /**
     * /order/invoice/resend
     * 获取发票操作日志列表
     *
     * @param Request $request
     * @param int $id 发票ID
     * @return \Illuminate\Http\Response
     */
    public function resendInvoice(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');

        $invoice_id = $request->input('id');
        $confirm_email = $request->input('confirm_email');
        if (!$invoice_id || !$confirm_email) {
            throw new ResourceException(trans('OrdersBundle/Order.required_params_missing'));
        }
        // 确保只查询同公司的发票日志
        $filter = [
            'id' => $invoice_id,
            'company_id' => $companyId
        ];

        $invoice = $this->invoiceService->getInfo($filter);

        if (empty($invoice)) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_not_found'));
        }

        if($invoice['invoice_status'] != 'success' || !$invoice['invoice_file_url']) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_not_success_or_no_file'));
        }

        $this->invoiceService->sendInvoiceEmail($confirm_email,$invoice['invoice_file_url'],$companyId);


        return $this->response->array(['status'=>true]);
    }

    /**
     * /order/invoice/setting
     * 获取开票配置
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getInvoiceSetting(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $settingService = new SettingService();
        $inputData = $settingService->getInvoiceSetting($companyId);
        return $this->response->array($inputData);
    }

    /**
     * /order/invoice/setting
     * 设置开票配置
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function setInvoiceSetting(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');

        $data = $request->all();
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

        $rules = [
            'invoice_status' => ['required|in:1,0', '是否启用开票功能必填'],
            'invoice_limit' => ['required|in:item,order', '开票维度必填'],
            'invoice_method' => ['required|in:offline,online', '开票渠道必填'],
            // 'channel' => ['required|in:1,2', '开票渠道必填'],
            'freight_invoice' => ['required|in:1,2', '运费是否开票必填'],
            'freight_name' => ['required_if:freight_invoice,2', '运费开票名称必填'],
            'apply_type' => ['required|in:1,2', '开票申请方式必填'],
            'invoice_open_term' => ['required|integer|min:0', '可开票期限应为正整数'],
            'special_invoice' => ['required|in:1,2', '专用发票展示必填'],
            'apply_node' => ['required|in:1,2', '申请开票节点必填'],
            'invoice_seller_type' => ['required|in:1,2', '开票方类型必填'],
            'freight_tax_rate' => ['required|numeric|min:0|max:100', '运费税率最小为0，最大为100的整数'],
        ];

        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException(trans('OrdersBundle/Order.invoice_config_error', ['errorMessage' => $errorMessage]));
        }

        //只取data中 rule 里面的字段到 dataInvoiceSetting 不使用 array_intersect_key 和 array_flip
        $dataInvoiceSetting = [];
        foreach ($rules as $key => $value) {
            if (isset($data[$key])) {
                $dataInvoiceSetting[$key] = $data[$key];
            }
        }
        app('log')->info(__FUNCTION__.':'.__LINE__.':dataInvoiceSetting:'.json_encode($dataInvoiceSetting));
        $settingService = new SettingService();
        $inputData = $settingService->setInvoiceSetting($companyId, $dataInvoiceSetting);
        return $this->response->array(["status"=>$inputData]);
    }

    /**
     * /order/invoice/baiwangInvoiceSetting
     * 百旺发票配置
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function setBaiwangInvoiceSetting(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $data = $request->all();
        $BaiwangService = new BaiwangService();
        $inputData = $BaiwangService->setInvoiceSetting($companyId, $data);
        if($inputData['success'] == false){
            throw new ResourceException($inputData['message']);
        }
        return $this->response->array($inputData);
    }
    /**
     * /order/invoice/baiwangInvoiceSetting
     * 百旺发票配置
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getBaiwangInvoiceSetting(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $BaiwangService = new BaiwangService();
        $inputData = $BaiwangService->getInvoiceSetting($companyId);
        return $this->response->array($inputData);
    }

    /**
     * /order/invoice/protocol
     * 存储发票协议信息
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function setInvoiceProtocol(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $data = $request->all();
        // 专用发票确认书 1:open 0:close
        if (!isset($data['special_invoice_confirm_open']) || !in_array($data['special_invoice_confirm_open'], [1,0])) {
            throw new \Dingo\Api\Exception\ResourceException(trans('OrdersBundle/Order.special_invoice_confirmation_empty'));
        }
        //title 必填
        if (empty($data['title'])) {
            throw new \Dingo\Api\Exception\ResourceException(trans('OrdersBundle/Order.invoice_protocol_title_empty'));
        }
        //content 必填
        if (empty($data['content'])) {
            throw new \Dingo\Api\Exception\ResourceException(trans('OrdersBundle/Order.invoice_protocol_info_empty'));
        }
        $result = $this->invoiceService->setInvoiceProtocol($companyId, $data);
        return $this->response->array(['status' => $result]);
    }

    /**
     * /order/invoice/protocol
     * 读取发票协议信息
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getInvoiceProtocol(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $result = $this->invoiceService->getInvoiceProtocol($companyId);
        return $this->response->array(['data' => $result]);
    }


}
