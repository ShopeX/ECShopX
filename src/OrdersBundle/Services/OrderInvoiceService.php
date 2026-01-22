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

namespace OrdersBundle\Services;

use CompanysBundle\Entities\Regionauth;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Entities\Members;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\OrderInvoice;
use OrdersBundle\Entities\OrderInvoiceLog;
use OrdersBundle\Entities\OrderInvoiceItem;
use OrdersBundle\Entities\OrderInvoices;
use OrdersBundle\Jobs\InvoicePushOmsJob;
use OrdersBundle\Jobs\SendInvoiceEmailJob;
use OrdersBundle\Traits\GetInvoiceBnTrait;
use AftersalesBundle\Services\AftersalesService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use CompanysBundle\Services\SettingService;
use DistributionBundle\Entities\Distributor;

class OrderInvoiceService
{
    use GetInvoiceBnTrait;
    use GetOrderServiceTrait;

    /** @var \OrdersBundle\Repositories\OrderInvoiceRepository */
    public $repository;

    /** @var \OrdersBundle\Repositories\OrderInvoiceLogRepository */
    public $logRepository;

    /** @var \OrdersBundle\Repositories\OrderInvoiceItemRepository */
    public $itemRepository;

    // 可更新字段
    public $allowFields = [
        'invoice_status'=>'开票状态',
        'invoice_file_url'=>'发票文件',
        'invoice_method'=>'开票类型',
        'company_title'=>'公司抬头',
        'company_tax_number'=>'公司税号',
        'company_address'=>'公司地址',
        'company_telephone'=>'公司电话',
        'bank_name'=>'开户银行',
        'bank_account'=>'开户账号',
        'email'=>'电子邮箱',
        'mobile'=>'手机号码',
        'remark'=>'备注'
];

    /**
     * OrderInvoiceService 构造函数
     */
    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(OrderInvoice::class);
        $this->logRepository = app('registry')->getManager('default')->getRepository(OrderInvoiceLog::class);
        $this->itemRepository = app('registry')->getManager('default')->getRepository(OrderInvoiceItem::class);
    }

    /**
     * 动态调用repository方法
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->repository->$method(...$parameters);
    }

    /**
     * 获取发票申请列表
     *
     * @param array $filter 过滤条件
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @param array $orderBy 排序
     * @return array
     */
    public function getInvoiceList($filter, $page = 1, $pageSize = 20, $orderBy = ['id' => 'DESC'],$isItems = true)
    {
        $result = $this->repository->lists($filter, '*', $page, $pageSize, $orderBy);

        // 获取每个发票关联的商品列表
        if (!empty($result['list']) && $isItems) {
            // 提取所有发票ID
            $invoiceIds = array_column($result['list'], 'id');
            $companyId = $result['list'][0]['company_id'] ?? 0;

            // 一次性查询所有发票关联的商品
            $allItemsFilter = [
                'invoice_id' => $invoiceIds,
                'company_id' => $companyId
            ];
            $order_ids = array_column($result['list'], 'order_id');
            //查询所有订单
            $orderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $orders = $orderRepository->getList(['order_id'=>$order_ids]);
            $ordersList = array_column($orders,null,'order_id');

            //查询所有用户
            $user_ids = array_column($result['list'], 'user_id');
            $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
            $members = $membersRepository->getDataList(['user_id'=>$user_ids],'user_id,mobile,user_card_code');
            $members = array_column($members,null,'user_id');
            //查询所有发票
            $allInvoiceItems = $this->itemRepository->getLists($allItemsFilter);
            $regionauth_ids = array_column($result['list'], 'regionauth_id');
            $regionauthRepository = app('registry')->getManager('default')->getRepository(Regionauth::class);
            $regionauths = $regionauthRepository->getLists(['regionauth_id'=>$regionauth_ids]);
            $regionauths = array_column($regionauths,null,'regionauth_id');

            $distributor_ids = array_column($result['list'], 'order_shop_id');
            $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
            $distributors = $distributorRepository->getLists(['distributor_id'=>$distributor_ids]);
            $distributors = array_column($distributors,null,'distributor_id');

            // 按发票ID分组商品
            $itemsByInvoiceId = [];
            foreach ($allInvoiceItems as $item) {
                $invoiceId = $item['invoice_id'];
                if (!isset($itemsByInvoiceId[$invoiceId])) {
                    $itemsByInvoiceId[$invoiceId] = [];
                }
                $itemsByInvoiceId[$invoiceId][] = $item;
            }
            

            // 将商品分配到对应的发票
            foreach ($result['list'] as &$invoice) {
                $invoice['invoice_items'] = $itemsByInvoiceId[$invoice['id']] ?? [];
                $invoice['regionauth_name'] = $regionauths[$invoice['regionauth_id']]['regionauth_name'] ?? '';
                $invoice['user_mobile'] = $members[$invoice['user_id']]['mobile'] ?? '';
                $invoice['user_card_code'] = $members[$invoice['user_id']]['user_card_code'] ?? '';
                $invoice['distributor_name'] = $distributors[$invoice['order_shop_id']]['name'] ?? ($invoice['order_shop_id'] == 0 ? "自营" : "-"); 
                //order_holder
                $invoice['order_holder'] = $ordersList[$invoice['order_id']]['order_holder'] ?? '';
            }
        }

        return $result;
    }
    public function getByOrderId($companyId, $orderId)
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'invoice_status' => 'pending',
        ];
        $invoice = $this->repository->getInfo($filter);
        return $invoice;
    }
    /**
     * 获取发票申请详情
     *
     * @param int $id 发票ID
     * @param int $companyId 公司ID
     * @return array
     */
    public function getInvoiceDetail($id, $companyId,$order_id = null)
    {
        //normalorderitemsRepository
        // $normalorderitemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        // $normalorderitems = $normalorderitemsRepository->getList(['order_id'=>$order_id]);
        // $normalorderitems_ids = array_column($normalorderitems['list'],null,'id');

        if($order_id && !$id){
            $invoice = $this->getByOrderId($companyId, $order_id);
            if($invoice){
                $id = $invoice['id'];
            }
        }
        $filter = [
            'id' => $id,
            'company_id' => $companyId
        ];

        $invoiceInfo = $this->repository->getInfo($filter);

        if (!$invoiceInfo) {
            return [];
        }


        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $member = $membersRepository->get(['user_id'=>$invoiceInfo['user_id']]);
        $invoiceInfo['user_mobile'] = $member['mobile']??'';
        $invoiceInfo['user_card_code'] = $member['user_card_code']??'';
        // 获取发票商品信息
        $itemFilter = [
            'invoice_id' => $id,
            'company_id' => $companyId
        ];

        $invoiceItems = $this->itemRepository->getLists($itemFilter);
        app('log')->debug('[OrderInvoiceService][getInvoiceDetail] 发票商品信息: ' . json_encode($invoiceItems));
        $invoiceInfo['invoice_items'] = $invoiceItems;

        // getOrderInfo
        $orderInfo = $this->getOrderInfo($invoiceInfo['order_id'], $invoiceInfo);
        $item_id_map = array_column($orderInfo['items'],null,'id');
        foreach($invoiceInfo['invoice_items'] as $key => $item){
            $invoiceInfo['invoice_items'][$key]['item_id'] = $item_id_map[$item['oid']]['item_id'] ?? 0;
            //pic
            $invoiceInfo['invoice_items'][$key]['main_img'] = $item_id_map[$item['oid']]['pic'] ?? '';
            //item_spec_desc
            $invoiceInfo['invoice_items'][$key]['item_spec_desc'] = $item_id_map[$item['oid']]['item_spec_desc'] ?? '';

            if(in_array($item['item_bn'],['shippingFeeLine','shippingFeeLine888'])){
                $invoiceInfo['invoice_items'][$key]['main_img'] = env('SHIIP_INVOICE_MAIN_IMG',"https://b-img-cdn.yuanyuanke.cn/ecshopx-vshop/fv_freight.png");
            }
        }

        $orderInvoicesRepository = app('registry')->getManager('default')->getRepository(OrderInvoices::class);
        // $invoices = $orderInvoicesRepository->getLists($itemFilter);
        // $invoiceInfo['invoices'] = $invoices;
        $order_id = $invoiceInfo['order_id'] ?? null;
        $invoiceInfo['refundDetail'] = $this->getInvoiceRefundDetail($companyId, $order_id);
        app('log')->debug('[OrderInvoiceService][getInvoiceDetail] 开票信息: ' . json_encode($invoiceInfo));
        
        // 根据发票状态决定是否进行售后调整
        if ($this->validateInvoiceStatusForRefund($invoiceInfo['invoice_status'])) {
            $invoiceInfo['invoice_amount']  = 0;
            foreach($invoiceInfo['invoice_items'] as $key => $item){
                // //pic
                // $invoiceInfo['invoice_items'][$key]['pic'] = $normalorderitems_ids[$item['oid']]['pic'] ?? '';
                // //item_spec_desc
                // $invoiceInfo['invoice_items'][$key]['item_spec_desc'] = $normalorderitems_ids[$item['oid']]['item_spec_desc'] ?? '';
                
                // 使用原始数据字段，如果没有则使用当前数据
                $originalAmount = $item['original_amount'] ?? $item['amount'] ?? 0;
                $originalNum = $item['original_num'] ?? $item['num'] ?? 0;
                
                if( isset($invoiceInfo['refundDetail']['itemRefundFee'][$item['item_id']]) ){
                    $invoiceInfo['invoice_items'][$key]['refund_fee'] = $invoiceInfo['refundDetail']['itemRefundFee'][$item['item_id']]['refund_fee'] ?? 0;
                    $invoiceInfo['invoice_items'][$key]['refund_num'] = $invoiceInfo['refundDetail']['itemRefundFee'][$item['item_id']]['num'] ?? 0;
                    $invoiceInfo['invoice_items'][$key]['amount'] = $originalAmount - $invoiceInfo['refundDetail']['itemRefundFee'][$item['item_id']]['refund_fee'] ?? 0;
                    $invoiceInfo['invoice_items'][$key]['num'] = $originalNum - $invoiceInfo['invoice_items'][$key]['refund_num'];
                }else{
                    $invoiceInfo['invoice_items'][$key]['refund_fee'] = 0;
                    $invoiceInfo['invoice_items'][$key]['refund_num'] = 0;
                    $invoiceInfo['invoice_items'][$key]['amount'] = $originalAmount;
                    $invoiceInfo['invoice_items'][$key]['num'] = $originalNum;
                }
                $invoiceInfo['invoice_amount'] += $invoiceInfo['invoice_items'][$key]['amount'];
            }
            // 退货金额
            $refund_fee = array_sum(array_column($invoiceInfo['invoice_items'],'refund_fee'));
            $invoiceInfo['refund_fee'] = $refund_fee;
            // $invoiceInfo['invoice_amount'] = $invoiceInfo['invoice_amount']  - $refund_fee;
        } else {
            // 对于不允许售后调整的状态，保持原始数据
            foreach($invoiceInfo['invoice_items'] as $key => $item){
                $invoiceInfo['invoice_items'][$key]['refund_fee'] = 0;
                $invoiceInfo['invoice_items'][$key]['refund_num'] = 0;
                $invoiceInfo['invoice_items'][$key]['amount'] = $item['amount'];
                $invoiceInfo['invoice_items'][$key]['num'] = $item['num'];
            }
            $invoiceInfo['refund_fee'] = 0;
        }
          
        $invoiceInfo['invoices'] = array();
        // 蓝字发票
        if($invoiceInfo['query_content']){
            // $blue = json_decode($invoiceInfo['query_content'],true);
            $blue =  ($invoiceInfo['query_content']);
            if(is_array($blue)){
                $blue['invoice_type'] = "blue";
                $blue['invoice_no'] = $blue["invoiceNo"];
                $blue['invoice_code'] = $blue["invoiceCode"] ? $blue["invoiceCode"] : $blue['serialNo'] ;
                $blue['create_time']  = strtotime( $blue['invoiceTime'] );
                $invoiceInfo['invoices'][] =  $blue;
            }
        }
        // 红字发票
        if($invoiceInfo['red_content']){
            // $red =  json_decode($invoiceInfo['red_content'],true);
            $red =  ($invoiceInfo['red_content']);
            if(is_array($red)){
                $red['invoice_type'] = "red";
                $red['invoice_no'] = $red["invoiceNo"] ?? 0;
                $red['invoice_code'] = isset($red["invoiceCode"]) && $red["invoiceCode"] ? $red["invoiceCode"] : ($red['serialNo'] ?? 0) ;
                $red['create_time']  = strtotime( $red['invoiceTime'] ?? 0 );
                $invoiceInfo['invoices'][] =  $red;
            }
        }
         
        return $invoiceInfo;
    }
    public function getOrderInfo($orderId,$authInfo){
        $orderType =  'normal';
        $orderService = $this->getOrderService($orderType);
        $order = $orderService->getOrderInfo($authInfo['company_id'], $orderId);
        app('log')->info(__FUNCTION__.':'.__LINE__.':order:'.json_encode($order));
        return $order['orderInfo'];
    }
    /**
     * 编辑发票申请
     *
     * @param int $id 发票ID
     * @param array $data 更新数据
     * @param array $operatorInfo 操作人信息
     * @return array
     */
    public function updateInvoice($id, $data, $operatorInfo = [])
    {
        $filter = [
            'id' => $id
        ];

        $invoice = $this->repository->getInfo($filter);

        if (!$invoice) {    
            return [];
        }
        // CHECK ORDER STATUS
        $orderService = $this->getOrderService('normal');
        $order = $orderService->getOrderInfo($invoice['company_id'], $invoice['order_id']);
        if($order['orderInfo']['order_status'] == 'CANCEL'){
            throw new \Exception('订单已取消，无法修改发票信息');
        }

        $updateData = $data;


        $params = [];
        $diffMsg = '';
        app('log')->info('[OrderInvoiceService][updateInvoice] 更新发票:'.$id.'从'.json_encode($invoice).'到'.json_encode($updateData)  );
        foreach ($this->allowFields as $key=>$value) {
            if(isset($updateData[$key])){
                $oldValue = $invoice[$key]??'';
                $newValue = $updateData[$key]??'';
                app('log')->info('[OrderInvoiceService][updateInvoice] 更新发票:'.$key.'从'.$oldValue.'到'.$newValue);
                if($oldValue != $newValue){
                    $diffMsg .= "  【".$key.$value."】从【".$oldValue."】到【".$newValue."】  ";
                }
                $params[] = [
                    'field'=>$key,
                    'name'=>$value,
                    'oldValue'=>$invoice[$key]??'',
                    'newValue'=>$updateData[$key],
                ];
            }

        }

        if (empty($updateData)) {
            return $invoice;
        }

        $result = $this->repository->updateOneBy($filter, $updateData);

        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
       
        $update_data = [];
        $update_data['invoice_status'] = $updateData['invoice_status'];
        $status_filter = ['order_id' => $invoice['order_id']];

        app('log')->info(__FUNCTION__.':'.__LINE__.':status_filter:'.json_encode($status_filter));
        $normalOrdersRepository->update($status_filter, $update_data);
        app('log')->info(__FUNCTION__.':'.__LINE__.':update_data:'.json_encode($update_data));
        
        // 记录操作日志
            $logData = [
                'invoice_id' => $id,
                'operator_type' => 'user',// 记录日志 user salesperson admin system distributor
                'user_id' => $invoice['user_id'] ?? 1,
                'operator_content' => [
                    'title' => "更新发票",
                'remark' => "更新发票:".$diffMsg,
                    'params' => $params,
                    'result' => []
                ]
            ];

            $this->logRepository->create($logData);

        return $result;
    }

    /**
     * 更新发票备注
     *
     * @param int $id 发票ID
     * @param string $remark 备注内容
     * @param array $operatorInfo 操作人信息
     * @return array
     */
    public function updateInvoiceRemark($id, $remark, $operatorInfo = [])
    {
        $filter = [
            'id' => $id
        ];

        $invoice = $this->repository->getInfo($filter);

        if (!$invoice) {
            return [];
        }

        $updateData = [
            'remark' => $remark
        ];

        $result = $this->repository->updateOneBy($filter, $updateData);

        // 记录操作日志
        if (!empty($operatorInfo) && $result) {
            $logData = [
                'invoice_id' => $id,
                'operator_type' => 'system',// 记录日志 user salesperson admin system distributor
                'operator_id' => $operatorInfo['operator_id'] ?? 1,
                'operator_content' => [
                    'title' => "更新发票备注",
                    'remark' => "更新发票备注",
                    'params' => [],
                    'result' => [
                        'field'=>'remark',
                        'name'=>'备注',
                        'oldValue'=>$invoice['remark']??'',
                        'newValue'=>$updateData['remark']
                    ]
                ]
            ];

            $this->logRepository->create($logData);
        }

        return $result;
    }

    /**
     * 获取发票操作日志列表
     *
     * @param int $invoiceId 发票ID
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @return array
     */
    public function getInvoiceLogList($invoiceId, $page = 1, $pageSize = 20)
    {
        $filter = [
            'invoice_id' => $invoiceId
        ];

        return $this->logRepository->lists($filter, '*', $page, $pageSize, ['id' => 'DESC']);
    }

    /**
     * 创建用户发票申请
     *
     * @param array $data 发票数据
     * @param string $invoiceType 发票申请类型(order:按订单, item:按商品)
     * @return array
     */
    public function createUserInvoice(array $data, $invoiceType = 'order')
    {
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {

            // 生成发票申请单号
            $data['invoice_apply_bn'] = $this->__genInvoiceBn();

            // 设置默认开票状态
            $data['invoice_status'] = 'pending';

            // 计算发票总金额

            $invoice_item = $data['invoice_item'];
            unset($data['invoice_item']);
            if($invoiceType == 'order'){
                $invoiceResults = [];
                foreach ($invoice_item as $invoice_items) {

                    $data['order_id'] = array_key_first($invoice_items);
                    // 保存发票记录
                    $invoiceResult = $this->repository->create($data);
                    $invoiceResults[] = $invoiceResult;
                    $invoiceAmount = 0;
                    foreach ($invoice_items[$data['order_id']] as $item) {
                        #todo 需要验证子订单的有效性
                        $itemData = [
                            'invoice_id' => $invoiceResult['id'],
                            'invoice_apply_bn' => $invoiceResult['invoice_apply_bn'],
                            'user_id' => $invoiceResult['user_id'],
                            'company_id' => $invoiceResult['company_id'],
                            'order_id' => $item['order_id'] ?? $data['order_id'],
                            'oid' => $item['oid'] ?? '',
                            'item_name' => $item['item_name'] ?? '',
                            'item_bn' => $item['item_bn'] ?? '',
                            'main_img' => $item['main_img'] ?? '',
                            'spec_info' => $item['spec_info'] ?? '',
                            'num' => $item['num'] ?? 1,
                            'amount' => $item['amount'] ?? 0
                        ];
                        // var_dump($itemData);exit;
                        $this->itemRepository->create($itemData);
                        $invoiceAmount += $itemData['amount'];
                    }
                    $orderItems = $normalOrdersItemsRepository->getList(['order_id'=>$data['order_id'],'is_invoice' => [0,3]]);
                    $order_oids = array_column($orderItems['list'],'id');
                    $oids = array_column($invoice_items[$data['order_id']],'oid');
                    $normalOrdersItemsRepository->updateBy(['id'=>$oids], ['is_invoice' => 1]);
                    if(array_diff_assoc($order_oids,$oids)){
                        $update_data['invoice_status'] = 'DONE';
                    }else{
                        $update_data['invoice_status'] = 'PARTAIL';
                    }
                    $filter = ['order_id' => $data['order_id']];

                    $normalOrdersRepository->update($filter, $update_data);
                    // 记录日志 user salesperson admin system distributor
                    $logData = [
                        'invoice_id' => $invoiceResult['id'],
                        'operator_type' => 'user',// 记录日志 user salesperson admin system distributor
                        'user_id' => $data['user_id'],
                        'operator_id' => $data['user_id'],
                        'operator_content' => [
                            'title' => "创建发票",
                            'remark' => "用户创建发票".$data['order_id'],
                            'params' => [],
                            'result' => []
                        ]
                    ];

                    $this->logRepository->create($logData);
                    // 更新发票总金额
                    $this->repository->updateOneBy(['id' => $invoiceResult['id']], ['invoice_amount' => $invoiceAmount]);

                    //推送给OMS
                    $gotoJob = (new InvoicePushOmsJob($invoiceResult['id'],$invoiceResult['company_id']));
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
                }
                $conn->commit();
                return  $invoiceResults;
            }else{
                $order_ids = [];
                $invoice_items = [];
                foreach ($invoice_item as $item){
                    foreach ($item as $key => $value) {
                        $order_ids[] = $key;
                        $invoice_items =array_merge($invoice_items,$value);
                    }
                }
                //var_dump($order_ids);
                $data['order_id'] = implode(',', $order_ids);
                // 保存发票记录
                $invoiceResult = $this->repository->create($data);
                $invoiceAmount = 0;
                foreach ($invoice_items as $item) {
                    $itemData = [
                        'invoice_id' => $invoiceResult['id'],
                        'invoice_apply_bn' => $invoiceResult['invoice_apply_bn'],
                        'user_id' => $invoiceResult['user_id'],
                        'company_id' => $invoiceResult['company_id'],
                        'order_id' => $item['order_id'] ?? $data['order_id'],
                        'oid' => $item['oid'] ?? '',
                        'item_name' => $item['item_name'] ?? '',
                        'item_bn' => $item['item_bn'] ?? '',
                        'main_img' => $item['main_img'] ?? '',
                        'spec_info' => $item['spec_info'] ?? '',
                        'num' => $item['num'] ?? 1,
                        'amount' => $item['amount'] ?? 0
                    ];

                    $this->itemRepository->create($itemData);
                    $invoiceAmount += $itemData['amount'];
                }
                // 更新发票总金额
                $this->repository->updateOneBy(['id' => $invoiceResult['id']], ['invoice_amount' => $invoiceAmount]);
                $oids = array_column($invoice_items,'oid');
                $normalOrdersItemsRepository->updateBy(['id'=>$oids], ['is_invoice' => 1]);
                foreach ($order_ids as $order_id) {
                    $orderItems = $normalOrdersItemsRepository->getList(['order_id'=>$data['order_id'],'is_invoice' => [0,3]]);
                    $order_oids = array_column($orderItems['list'],'id');
                    if(array_diff_assoc($order_oids,$oids)){
                        $update_data['invoice_status'] = 'DONE';
                    }else{
                        $update_data['invoice_status'] = 'PARTAIL';
                    }
                    $filter = ['order_id' => $order_id];

                    $normalOrdersRepository->update($filter, $update_data);
                }
                //推送给OMs
                $gotoJob = (new InvoicePushOmsJob($invoiceResult['id'],$invoiceResult['company_id']));
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
                // 记录日志
                $logData = [
                    'invoice_id' => $invoiceResult['id'],
                    'operator_type' => 'user',// 记录日志 user salesperson admin system distributor
                    'user_id' => $data['user_id'],
                    'operator_id' => $data['user_id'],
                    'operator_content' => [
                        'title' => "创建发票",
                        'remark' => "用户创建发票".$order_id,
                        'params' => [],
                        'result' => []
                    ]
                ];

                $this->logRepository->create($logData);
                $conn->commit();
                return [$invoiceResult];
            }

        }catch (\Exception $exception){
            $conn->rollback();
            throw new ResourceException(trans('OrdersBundle/Order.invoice_application_failed', ['{0}' => $exception->getMessage()]));
        }

    }

    public function createInvoiceOrder($data,$orderInfo){ //authInfo
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $memberRepository = app('registry')->getManager('default')->getRepository(Members::class);

        $memberInfo = $memberRepository->get(['user_id'=>$orderInfo['user_id']]);
        $user_card_code = $memberInfo['user_card_code'] ?? '';
        app('log')->info(__FUNCTION__.':'.__LINE__.':memberInfo:'.json_encode($memberInfo));
        app('log')->info(__FUNCTION__.':'.__LINE__.':user_card_code:'.json_encode($user_card_code));

        $order_id = $data['order_id'];
        $invoice_items = $data['invoice_item'];
        unset($data['invoice_item']);
        $data['order_id'] = $order_id;
        $data['user_id'] = $orderInfo['user_id'];
        $data['company_id'] = $orderInfo['company_id'];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {

            // 批量查询商品税率
            $itemRateMap = $this->getInvoiceRateBatch($invoice_items, $orderInfo['company_id']);
            app('log')->info(__FUNCTION__.':'.__LINE__.':itemRateMap:'.json_encode($itemRateMap));

            //生成发票申请单号
            $data['invoice_apply_bn'] = '0';//$this->__genInvoiceBn();

            //设置默认开票状态
            $data['invoice_status'] = 'pending';
            if($orderInfo['end_time'] > 0){
                $data['end_time'] = $orderInfo['end_time'];
                $data['close_aftersales_time'] = $orderInfo['order_auto_close_aftersales_time'];
            }
            $data['order_shop_id'] = $orderInfo['distributor_id'] ?? '';
            $data['user_card_code'] = $memberInfo['user_card_code'] ?? '';

            $settingService = new SettingService();
            $settingData = $settingService->getInvoiceSetting($orderInfo['company_id']); 
            $data['invoice_method'] = $settingData['invoice_method'] ?? 'online';

            $invoiceResult = $this->repository->create($data);
            $invoiceAmount = 0;
            $refundDetail = $this->getInvoiceRefundDetail($orderInfo['company_id'], $orderInfo['order_id']);
            app('log')->info(__FUNCTION__.':'.__LINE__.':refundDetail:'.json_encode($refundDetail));
            app('log')->info(__FUNCTION__.':'.__LINE__.':data:'.json_encode($data));        

            foreach ($invoice_items as $item) {
                $refund_fee = $refundDetail['itemRefundFee'][$item['item_id']]['refund_fee'] ?? 0;
                $refund_num = $refundDetail['itemRefundFee'][$item['item_id']]['num'] ?? 0;
                
                app('log')->debug('[BaiwangService][formatBaiwangParams] itemrefund退货信息:refund_fee: ' . $refund_fee.',refund_num:'.$refund_num);
                $item_price_fee = $item['total_fee']  - $refund_fee;
                app('log')->debug('[BaiwangService][formatBaiwangParams] item_price_fee: ' . $item_price_fee. ':item_name:'.$item['item_name']);
                if($item_price_fee <= 0){
                    continue;
                }

                $itemData = [
                    'invoice_id' => $invoiceResult['id'],
                    'invoice_apply_bn' => $invoiceResult['invoice_apply_bn'],
                    'user_id' => $invoiceResult['user_id'],
                    'company_id' => $invoiceResult['company_id'],
                    'order_id' => $item['order_id'] ?? $data['order_id'],
                    'oid' => $item['id'] ?? '',
                    'item_name' => $item['item_name'] ?? '',
                    'item_bn' => $item['item_bn'] ?? '',
                    'main_img' => $item['main_img'] ?? '',
                    'spec_info' => $item['spec_info'] ?? '',
                    'item_spec_desc' => $item['item_spec_desc'] ?? '',
                    'num' => $item['num'] - $refund_num,
                    'amount' => $item_price_fee,
                    // 新增：发票税率字段
                    'invoice_tax_rate' => $itemRateMap[$item['item_id']] ?? '0',
                    // 记录原始数据用于售后调整
                    'original_num' => $item['num'] ?? 1,
                    'original_amount' => $item['total_fee'] ?? 0,
                    'create_time' => time(),
                    'update_time' => time()
                ];

                $this->itemRepository->create($itemData);
                $invoiceAmount += $itemData['amount'];
            }

            app("log")->info(__FUNCTION__.':'.__LINE__.':orderInfo:'.json_encode($orderInfo));
            if (isset($orderInfo['freight_fee']) && $orderInfo['freight_fee'] > 0 && $settingData['freight_invoice'] == 2) {
                app("log")->info(__FUNCTION__.':'.__LINE__.':orderInfo:'.json_encode($orderInfo['freight_fee']));
                // 运费税率 从发票设置中来settingservice
                $settingService = new SettingService();
                $inputData = $settingService->getInvoiceSetting($orderInfo['company_id']); 
                app('log')->debug('[BaiwangService][formatBaiwangParams] 发票设置: ' . json_encode($inputData));
                $freightTaxRate = $inputData['freight_tax_rate'] ?? '13';
                // $freightTaxRate = number_format($freightTaxRate / 100, 2);
                
                $itemData = [
                    'invoice_id' => $invoiceResult['id'],
                    'invoice_apply_bn' => $invoiceResult['invoice_apply_bn'],
                    'user_id' => $invoiceResult['user_id'],
                    'company_id' => $invoiceResult['company_id'],
                    'order_id' => $order_id,
                    'oid' => 'shippingFeeLine',
                    'item_name' => $inputData['freight_name'] ?? '运费',
                    'item_bn' => 'shippingFeeLine',
                    'main_img' => '',
                    'spec_info' => '',
                    'item_spec_desc' => '',
                    'num' => 1,
                    'amount' => $orderInfo['freight_fee'],
                    // 新增：发票税率字段
                    'invoice_tax_rate' => $freightTaxRate,
                    // 记录原始数据用于售后调整
                    'original_num' => 1,
                    'original_amount' => $orderInfo['freight_fee'],
                    'create_time' => time(),
                    'update_time' => time()
                ];
                app("log")->info(__FUNCTION__.':'.__LINE__.':itemData:'.json_encode($itemData));
                $this->itemRepository->create($itemData);
                $invoiceAmount += $itemData['amount'];
                app("log")->info(__FUNCTION__.':'.__LINE__.':invoiceAmount:'.json_encode($invoiceAmount));
            }
    


                //更新发票总金额
                $this->repository->updateOneBy(['id' => $invoiceResult['id']], ['invoice_amount' => $invoiceAmount]);
                $oids = array_column($invoice_items,'id');
                app('log')->info(__FUNCTION__.':'.__LINE__.':oids:'.json_encode($oids));
                $normalOrdersItemsRepository->updateBy(['id'=>$oids], ['is_invoice' => 1]);

                $orderItems = $normalOrdersItemsRepository->getList(['order_id'=>$order_id,'id|notin'=>$oids,'is_invoice' => 0]);
                if($orderItems){
                    $update_data['invoice_status'] = 'pending';
                }
                $filter = ['order_id' => $order_id];
                app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
                $normalOrdersRepository->update($filter, $update_data);
                app('log')->info(__FUNCTION__.':'.__LINE__.':update_data:'.json_encode($update_data));
            //创建发票日志
            $logData = [
                'invoice_id' => $invoiceResult['id'],
                'operator_type' => 'user',// 记录日志 user salesperson admin system distributor
                'user_id' => $data['user_id'],
                'operator_id' => $data['user_id'],
                'operator_content' => [
                    'title' => "创建发票",
                    'remark' => "用户创建发票".$order_id,
                    'params' => [],
                    'result' => [],
                ]
            ];
            app('log')->info(__FUNCTION__.':'.__LINE__.':logData:'.json_encode($logData));
            $this->logRepository->create($logData);

            $conn->commit();
            return $invoiceResult;
        }catch (\Exception $exception){
            $conn->rollback();
            app('log')->error(__FUNCTION__.':'.__LINE__.':'.$exception->getMessage());
            throw new ResourceException('生成发票失败:'.$exception->getMessage());
        }
       
        return $invoiceResult;
    }

    public function getInvoiceAmount($orderId,$orderInfo){
        $invoiceAmount = 0;
        // aftersales info
        $refundDetail = $this->getInvoiceRefundDetail($orderInfo['company_id'], $orderInfo['order_id']);
        app('log')->info(__FUNCTION__.':'.__LINE__.':refundDetail:'.json_encode($refundDetail));
        app('log')->info(__FUNCTION__.':'.__LINE__.':orderInfo:'.json_encode($orderInfo));
        foreach ($orderInfo['items'] as $item) {
            $refund_fee = $refundDetail['itemRefundFee'][$item['item_id']]['refund_fee'] ?? 0;
            $refund_num = $refundDetail['itemRefundFee'][$item['item_id']]['num'] ?? 0;
            $item_price_fee = $item['total_fee'] - $refund_fee;
            if($item_price_fee <= 0){
                continue;
            }
            app('log')->info(__FUNCTION__.':'.__LINE__.':item_price_fee:'.json_encode($item_price_fee));
            $invoiceAmount += $item_price_fee;
        }
        app('log')->info(__FUNCTION__.':'.__LINE__.':invoiceAmount:'.json_encode($invoiceAmount));
        // setting freight_fee
        $settingService = new SettingService();
        $settingData = $settingService->getInvoiceSetting($orderInfo['company_id']); 
        if(isset($orderInfo['freight_fee']) && $orderInfo['freight_fee'] > 0 && isset($settingData['freight_invoice']) && $settingData['freight_invoice'] == 2){
            $invoiceAmount += $orderInfo['freight_fee'];
        }
        app('log')->info(__FUNCTION__.':'.__LINE__.':invoiceAmount:'.json_encode($invoiceAmount));
        return $invoiceAmount;
    }

    public function createInvoiceOrderForOrder($invoice_content,$orderData){
        $orderId = $orderData['order_id'];
        $orderInfo = $this->getOrderInfo($orderId,$orderData);
        $data = $invoice_content;
        $data['order_id'] = $orderId;   
        $data['invoice_item'] = $orderInfo['items'];
        $data['user_id'] = $orderInfo['user_id'] ?? 0;
        $data['company_id'] = $orderInfo['company_id'] ?? 0;
        app('log')->info(__FUNCTION__.':'.__LINE__.':orderInfo:'.json_encode($orderInfo));
        app('log')->info(__FUNCTION__.':'.__LINE__.':data:'.json_encode($data));
        $res = $this->createInvoiceOrder($data,$orderInfo);
        app('log')->info(__FUNCTION__.':'.__LINE__.':res:'.json_encode($res));
        return $res;
    }
    /**
     * 重发发票邮件
     *
     * @param array $filter 过滤条件
     * @param string $email 新的邮箱地址
     * @return bool
     */
    public function resendInvoiceEmail(array $filter, $email)
    {
        $invoiceInfo = $this->repository->getInfo($filter);

        if (empty($invoiceInfo) || empty($invoiceInfo['invoice_file_url'])) {
            return false;
        }

        // 如果传入新邮箱，则更新发票邮箱
        if ($email && $email != $invoiceInfo['email']) {
            $this->repository->updateOneBy(['id' => $invoiceInfo['id']], ['email' => $email]);

            // 记录操作日志
            $logData = [
                'invoice_id' => $invoiceInfo['id'],
                // 'operator_type' => 'resend_email',
                'operator_type' => 'admin',// 记录日志 user salesperson admin system distributor
                'operator_id' => 1, // 系统自动更新
                'user_id' => $invoiceInfo['user_id'], // 系统自动更新
                'operator_content' => [
                    'title' => "更新发票邮箱",
                    'remark' => "更新发票邮箱:".$email,
                    'params' => [],
                    'result' => [
                        'field'=>'email',
                        'name'=>'邮箱',
                        'oldValue'=>$invoiceInfo['email']??'',
                        'newValue'=>$email
                    ]
                ]
            ];

            $this->logRepository->create($logData);
        }



        // 发送邮件逻辑，使用SendInvoiceEmailJob
        $jobData = [
            'email' => $email ?: $invoiceInfo['email'],
            'invoice_file_url' => $invoiceInfo['invoice_file_url'],
            'company_id' => $invoiceInfo['company_id'],
        ];

        // 分发邮件发送任务
        $job = (new SendInvoiceEmailJob($jobData));
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

        return true;
    }

    /**
     * 处理第三方回调的开票结果
     *
     * @param array $callbackData 回调数据，包含invoice_apply_bn、invoice_status、invoice_url
     * @return array 更新结果
     */
    public function handleInvoiceCallback(array $callbackData)
    {

        $status = [1=>'inProgress',2=>'success',3=>'waste',4=>'failed'];
        // 验证必要参数

        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        // 查找发票记录
        $filter = [
            'invoice_apply_bn' => $callbackData['invoice_no']
        ];

        $invoiceInfo = $this->repository->getInfo($filter);

        if ($invoiceInfo) {
            // 准备更新数据
            $updateData = [
                'invoice_status' => $status[$callbackData['invoice_memo']]
            ];

            // 如果有发票URL，更新到记录中
            if (!empty($callbackData['pdf_info'])) {
                $updateData['invoice_file_url'] = $callbackData['pdf_info'];
            }


            try {
                $conn = app('registry')->getConnection('default');
                $conn->beginTransaction();
                
                // 记录原状态
                $oldStatus = $invoiceInfo['invoice_status'];
                
                // 更新发票状态
                $result = $this->repository->updateOneBy($filter, $updateData);

                if(in_array($callbackData['invoice_memo'],[2,3])){
                    $orderInvoicesRepository = app('registry')->getManager('default')->getRepository(OrderInvoices::class);
                    $invoice = [
                       'invoice_id' =>$invoiceInfo['id'],
                       'company_id' =>$invoiceInfo['company_id'],
                       'invoice_type' =>$callbackData['invoice_memo'] == '2' ? 'blue' : 'red' ,
                       'invoice_no' =>$callbackData['invoice_no'],
                       'invoice_code' =>$callbackData['invoice_code'],
                       'invoice_time' =>$callbackData['invoice_time'],
                    ] ;
                    $orderInvoicesRepository->create($invoice);
                }

                $params = [];
                foreach ($this->allowFields as $key=>$value) {
                    if(isset($updateData[$key])){
                        $params[] = [
                            'field'=>$key,
                            'name'=>$value,
                            'oldValue'=>$invoiceInfo[$key]??'',
                            'newValue'=>$updateData[$key],
                        ];
                    }

                }

                // 记录操作日志
                $logData = [
                    'invoice_id' => $invoiceInfo['id'],
                    'company_id' => $invoiceInfo['company_id'],
                    'operator_type' => 'system',// 记录日志 user salesperson admin system distributor
                    'operator_id' => 1, // 系统自动更新
                    'operator_content' => $params
                ];

                $this->logRepository->create($logData);
                
                // 监听发票状态变更
                $this->handleInvoiceStatusChange($invoiceInfo['id'], $oldStatus, $updateData['invoice_status']);

                // 如果开票成功且有邮箱，则发送邮件通知
//                if ($updateData['invoice_status'] == 'success' &&
//                    !empty($invoiceInfo['email']) &&
//                    !empty($callbackData['pdf_info'])) {
//
//                    $this->sendInvoiceEmail($invoiceInfo['email'], $callbackData['pdf_info']);
//
//                    app('log')->info('开票成功，已发送邮件通知');
//                }

                app('log')->info('开票回调处理成功');

                if(in_array($callbackData['invoice_memo'],[3,4])){
                    $allItemsFilter = [
                        'invoice_id' => $invoiceInfo['id'],
                        'company_id' => $invoiceInfo['company_id']
                    ];

                    $allInvoiceItems = $this->itemRepository->getLists($allItemsFilter);
                    $oids = array_column($allInvoiceItems,'oid');
                    $is_invoice = $callbackData['invoice_memo'] == 3 ? 3 : 0;
                    $normalOrdersItemsRepository->updateBy(['id'=>$oids], ['is_invoice' => $is_invoice]);
                    $order_ids = array_column($allInvoiceItems,'order_id');
                    foreach ($order_ids as $order_id) {
                        $orderItems = $normalOrdersItemsRepository->getList(['order_id'=>$order_id,'is_invoice' => 1]);
                        if($orderItems){
                            $update_data['invoice_status'] = 'PARTAIL';
                        }else{
                            $update_data['invoice_status'] = 'PENDING';
                        }
                        $filter = ['order_id' => $order_id];

                        $normalOrdersRepository->update($filter, $update_data);
                    }

                }
                $conn->commit();
                return [
                    'success' => true,
                    'message' => '开票状态更新成功',
                    'data' => json_encode($result)
                ];

            } catch (\Exception $e) {
                app('log')->error('开票回调处理异常');
                $conn->rollback();
                return [
                    'success' => false,
                    'message' => '开票状态更新失败: ' . $e->getMessage()
                ];
            }
        }

        if(isset($callbackData['order_bn']) && $callbackData['order_bn']){

            $filter = ['order_id' => $callbackData['order_bn']];
            $update_data['invoice_status'] = 'DONE';

            $normalOrdersRepository->update($filter, $update_data);

            $data = [
                'is_invoice' => 1,
            ];
            $normalOrdersItemsRepository->updateBy($filter, $data);
        }

        return [
            'success' => true,
            'message' => '开票状态更新成功',
        ];
    }

    /**
     * 发送发票邮件
     *
     * @param string $email 邮箱地址
     * @param string $invoiceFileUrl 发票文件URL
     * @return void
     */
    public function sendInvoiceEmail($email, $invoiceFileUrl,$companyId = 1)
    {
        try {
            app('log')->info('发送开票邮件开始:email:'.$email.',invoiceFileUrl:'.$invoiceFileUrl);

            $jobData = [
                'company_id' => $companyId,
                'email' => $email,
                'invoice_file_url' => $invoiceFileUrl,
                'subject' => '您的电子发票已生成'
            ];
            app('log')->info('发送开票邮件开始:jobData:'.json_encode($jobData));

            // 分发邮件发送任务
            $job = (new SendInvoiceEmailJob($jobData));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

            app('log')->info('已分发发票邮件发送任务');
        } catch (\Exception $e) {
            app('log')->error('分发发票邮件任务失败');
        }
    }

    /**
     * 检查指定订单是否已经开过运费发票
     *
     * @param int $orderId 订单ID
     * @param int $companyId 公司ID
     * @return bool 是否已开过运费发票
     */
    public function hasShippingFeeInvoice($orderId, $companyId)
    {
        // 查询发票商品行是否存在与该订单相关的运费发票项
        $invoiceItemFilter = [
            'order_id' => $orderId,
            'company_id' => $companyId,
            'item_bn' => 'shippingFeeLine888' // 通过运费商品编码精确匹配
        ];


        $invoiceItem = $this->itemRepository->getInfo($invoiceItemFilter);

        // 如果没有找到相关的发票商品行，则表示未开过运费发票
        if (empty($invoiceItem)) {
            return false;
        }

        // 查询这个发票的状态
        $invoiceFilter = [
            'id' => $invoiceItem['invoice_id'],
            'company_id' => $companyId,
//            'invoice_status' => 'success' // 只检查开票成功的发票
        ];

        $invoice = $this->repository->getInfo($invoiceFilter);
        if($invoice && $invoice['invoice_status'] == ['inProgress','success']){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 存储发票协议信息到 Redis
     * @param int $companyId
     * @param array $data
     * @return bool
     */
    public function setInvoiceProtocol($companyId, array $data)
    {
        // $cacheService = new \EspierBundle\Services\Cache\RedisCacheService($companyId, 'invoice_protocol', 3600 * 24 * 30);
        $redis = app('redis');
        $redis->set('invoice_protocol_'.$companyId, json_encode($data, JSON_UNESCAPED_UNICODE));
        return true;
    }

    /**
     * 读取发票协议信息
     * @param int $companyId
     * @return array|null
     */
    public function getInvoiceProtocol($companyId)
    {
        $redis = app('redis');
        $result = $redis->get('invoice_protocol_'.$companyId);
        $info = isset($result) ? json_decode($result, true) : null;
        return $info;
    }

    /**
     * 根据售后信息更新发票商品行的金额和数量
     *
     * @param int $invoiceId 发票ID
     * @param int $companyId 公司ID
     * @param array $refundDetail 售后信息
     * @return bool
     */
    public function updateInvoiceItemsByRefund($invoiceId, $companyId, $refundDetail)
    {
        try {
            app('log')->info('[OrderInvoiceService][updateInvoiceItemsByRefund] 开始更新发票商品行', [
                'invoice_id' => $invoiceId,
                'company_id' => $companyId
            ]);

            $invoiceInfo = $this->repository->getInfo(['id' => $invoiceId, 'company_id' => $companyId]);

            if(!$invoiceInfo){
                app('log')->warning('[OrderInvoiceService][updateInvoiceItemsByRefund] 未找到发票信息');
                return false;
            }

            // 获取发票商品行
            $itemFilter = [
                'invoice_id' => $invoiceId,
                'company_id' => $companyId
            ];
            $invoiceItems = $this->itemRepository->getLists($itemFilter);
            
            if (empty($invoiceItems)) {
                app('log')->warning('[OrderInvoiceService][updateInvoiceItemsByRefund] 未找到发票商品行');
                return false;
            }

            
            $updateCount = 0;
            $itemRefundFee = $refundDetail['itemRefundFee'] ?? [];
            app('log')->info('[OrderInvoiceService][updateInvoiceItemsByRefund] 商品行原始数据:itemRefundFee', [
                'itemRefundFee' => $itemRefundFee
            ]); 

            // getOrderInfo
            $orderInfo = $this->getOrderInfo($invoiceInfo['order_id'], $invoiceInfo);
            $item_id_map = array_column($orderInfo['items'],null,'id');

            foreach ($invoiceItems as $item) {
                // 获取商品ID
                $itemId = $item_id_map[$item['oid']]['item_id'] ?? 0;
                app('log')->info('[OrderInvoiceService][updateInvoiceItemsByRefund] 商品行原始数据:item', [
                    'item' => $item
                ]);
                // 使用原始数据字段，如果没有则使用当前数据
                $originalAmount = $item['original_amount'] ?? $item['amount'] ?? 0;
                $originalNum = $item['original_num'] ?? $item['num'] ?? 0;
                app('log')->info('[OrderInvoiceService][updateInvoiceItemsByRefund] 商品行原始数据:itemId,originalAmount,originalNum', [
                    'itemId' => $itemId,
                    'originalAmount' => $originalAmount,
                    'originalNum' => $originalNum
                ]);
                // 检查是否有售后信息
                if (isset($itemRefundFee[$itemId])) {
                    $refundFee = $itemRefundFee[$itemId]['refund_fee'] ?? 0;
                    $refundNum = $itemRefundFee[$itemId]['num'] ?? 0;
                    app('log')->info('[OrderInvoiceService][updateInvoiceItemsByRefund] 商品行售后信息:itemId,refundFee,refundNum', [
                        'itemId' => $itemId,
                        'refundFee' => $refundFee,
                        'refundNum' => $refundNum
                    ]);
                    // 计算调整后的金额和数量
                    $adjustedAmount = max(0, $originalAmount - $refundFee);
                    $adjustedNum = max(0, $originalNum - $refundNum);
                    
                    // 更新发票商品行
                    $updateData = [
                        'amount' => $adjustedAmount,
                        'num' => $adjustedNum,
                        'update_time' => time()
                    ];
                    
                    $updateFilter = [
                        'id' => $item['id'],
                        'invoice_id' => $invoiceId,
                        'company_id' => $companyId
                    ];
                    app('log')->info('[OrderInvoiceService][updateInvoiceItemsByRefund] 商品行更新:updateFilter,updateData', [
                        'updateFilter' => $updateFilter,
                        'updateData' => $updateData
                    ]);
                    $result = $this->itemRepository->updateOneBy($updateFilter, $updateData);
                    
                    if ($result) {
                        $updateCount++;
                        
                        // 记录调整日志
                        $this->createInvoiceLog($invoiceId, 'system', '发票商品行售后调整', [
                            'item_id' => $itemId,
                            'original_amount' => $originalAmount,
                            'original_num' => $originalNum,
                            'refund_fee' => $refundFee,
                            'refund_num' => $refundNum,
                            'adjusted_amount' => $adjustedAmount,
                            'adjusted_num' => $adjustedNum
                        ]);
                        
                        app('log')->info('[OrderInvoiceService][updateInvoiceItemsByRefund] 商品行更新成功', [
                            'item_id' => $itemId,
                            'original_amount' => $originalAmount,
                            'adjusted_amount' => $adjustedAmount
                        ]);
                    }
                }
            }
            
            // 更新发票总金额
            $this->updateInvoiceTotalAmount($invoiceId, $companyId);
            
            app('log')->info('[OrderInvoiceService][updateInvoiceItemsByRefund] 更新完成', [
                'invoice_id' => $invoiceId,
                'updated_count' => $updateCount
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            app('log')->error('[OrderInvoiceService][updateInvoiceItemsByRefund] 更新失败', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 记录错误日志
            $this->createInvoiceLog($invoiceId, 'system', '发票商品行售后调整失败: ' . $e->getMessage(), [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 更新发票总金额
     *
     * @param int $invoiceId 发票ID
     * @param int $companyId 公司ID
     * @return bool
     */
    private function updateInvoiceTotalAmount($invoiceId, $companyId)
    {
        try {
            // 重新计算发票总金额
            $itemFilter = [
                'invoice_id' => $invoiceId,
                'company_id' => $companyId
            ];
            $invoiceItems = $this->itemRepository->getLists($itemFilter);
            
            $totalAmount = 0;
            foreach ($invoiceItems as $item) {
                $totalAmount += $item['amount'] ?? 0;
            }
            
            // 更新发票总金额
            $updateData = [
                'invoice_amount' => $totalAmount,
                'update_time' => time()
            ];
            
            $filter = [
                'id' => $invoiceId,
                'company_id' => $companyId
            ];
            
            $result = $this->repository->updateOneBy($filter, $updateData);
            
            app('log')->info('[OrderInvoiceService][updateInvoiceTotalAmount] 发票总金额更新', [
                'invoice_id' => $invoiceId,
                'total_amount' => $totalAmount,
                'success' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            app('log')->error('[OrderInvoiceService][updateInvoiceTotalAmount] 更新失败', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getInvoiceRefundDetail($companyId, $orderId = null)
    {
        app('log')->info('[OrderInvoiceService][getInvoiceRefundDetail] 开始获取售后信息', [
            'company_id' => $companyId,
            'order_id' => $orderId
        ]);
        
        if(!$orderId){
            app('log')->warning('[OrderInvoiceService][getInvoiceRefundDetail] 订单ID为空');
            return [];
        }


        $itemRefundFee = [];
        $itemsAftersales = [];
        
        try {
            $afterSaleService = new AftersalesService();
            $afterSaleInfo = $afterSaleService->getAftersalesList(
                ['company_id' => $companyId, 'order_id' => $orderId], 
                0, 
                -1, 
                ['create_time' => 'ASC']
            );
            
            app('log')->info('[OrderInvoiceService][getInvoiceRefundDetail] 售后信息查询结果', [
                'order_id' => $orderId,
                'total_count' => $afterSaleInfo['total_count'] ?? 0
            ]);
            
            if ($afterSaleInfo && $afterSaleInfo['total_count'] > 0) {
                // 定义有效的售后状态
                $validAftersalesStatus = [2]; // 0:未处理, 1:处理中, 2:已处理
                
                foreach ($afterSaleInfo['list'] as $v) {
                    $aftersalesStatus = $v['aftersales_status'] ?? -1;
                    
                    app('log')->debug('[OrderInvoiceService][getInvoiceRefundDetail] 处理售后单', [
                        'aftersales_bn' => $v['aftersales_bn'],
                        'aftersales_status' => $aftersalesStatus,
                        'is_valid_status' => in_array($aftersalesStatus, $validAftersalesStatus)
                    ]);
                    
                    foreach ($v['detail'] as $vv) {
                        $itemId = $vv['item_id'] ?? 0;
                        
                        // 订单商品售后信息
                        $itemsAftersales[$itemId] = [
                            'aftersales_bn' => $vv['aftersales_bn'],
                            'aftersales_status' => $aftersalesStatus,
                            'create_time' => $v['create_time'] ?? 0
                        ];

                        // 只处理有效状态的售后
                        if (in_array($aftersalesStatus, $validAftersalesStatus)) {
                            $refundFee = $vv['refund_fee'] ?? 0;
                            $refundPoint = $vv['refund_point'] ?? 0;
                            $num = $vv['num'] ?? 0;
                            
                            // 统一使用item_id作为键
                            if (isset($itemRefundFee[$itemId])) {
                                $itemRefundFee[$itemId]['refund_fee'] += $refundFee;
                                $itemRefundFee[$itemId]['refund_point'] += $refundPoint;
                                $itemRefundFee[$itemId]['num'] += $num;
                            } else {
                                $itemRefundFee[$itemId] = [
                                    'refund_fee' => $refundFee,
                                    'refund_point' => $refundPoint,
                                    'num' => $num
                                ];
                            }
                            
                            app('log')->debug('[OrderInvoiceService][getInvoiceRefundDetail] 售后明细', [
                                'item_id' => $itemId,
                                'refund_fee' => $refundFee,
                                'refund_point' => $refundPoint,
                                'num' => $num
                            ]);
                        }
                    }
                }
            }
            
            $result = [
                'itemRefundFee' => $itemRefundFee, 
                'itemsAftersales' => $itemsAftersales
            ];
            
            
            app('log')->info('[OrderInvoiceService][getInvoiceRefundDetail] 售后信息获取完成', [
                'order_id' => $orderId,
                'item_count' => count($itemRefundFee),
                'aftersales_count' => count($itemsAftersales)
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            app('log')->error('[OrderInvoiceService][getInvoiceRefundDetail] 获取售后信息失败', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'itemRefundFee' => [], 
                'itemsAftersales' => []
            ];
        }
    }

    /**
     * 验证发票状态是否允许进行售后调整
     *
     * @param string $invoiceStatus 发票状态
     * @return bool
     */
    public function validateInvoiceStatusForRefund($invoiceStatus)
    {
        // 定义允许售后调整的发票状态
        $allowedStatuses = ['pending'];
        
        // 不允许售后调整的状态
        $disallowedStatuses = ['inProgress', 'success', 'waste', 'failed'];
        
        $isAllowed = in_array($invoiceStatus, $allowedStatuses);
        $isDisallowed = in_array($invoiceStatus, $disallowedStatuses);
        
        app('log')->debug('[OrderInvoiceService][validateInvoiceStatusForRefund] 状态验证', [
            'invoice_status' => $invoiceStatus,
            'is_allowed' => $isAllowed,
            'is_disallowed' => $isDisallowed,
            'allowed_statuses' => $allowedStatuses,
            'disallowed_statuses' => $disallowedStatuses
        ]);
        
        return $isAllowed;
    }

    /**
     * 获取允许售后调整的发票状态列表
     *
     * @return array
     */
    public function getAllowedRefundStatuses()
    {
        return ['pending'];
    }

    /**
     * 获取不允许售后调整的发票状态列表
     *
     * @return array
     */
    public function getDisallowedRefundStatuses()
    {
        return ['inProgress', 'success', 'waste', 'failed'];
    }

    /**
     * 清理售后数据缓存
     *
     * @param int $companyId 公司ID
     * @param int $orderId 订单ID
     * @return bool
     */
    public function clearRefundDetailCache($companyId, $orderId)
    {
        try {
            $cacheKey = "invoice_refund_detail_{$companyId}_{$orderId}";
            $redis = app('redis');
            $result = $redis->del($cacheKey);
            
            app('log')->info('[OrderInvoiceService][clearRefundDetailCache] 清理售后数据缓存', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'cache_key' => $cacheKey,
                'result' => $result
            ]);
            
            return $result > 0;
            
        } catch (\Exception $e) {
            app('log')->error('[OrderInvoiceService][clearRefundDetailCache] 清理缓存失败', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 批量清理售后数据缓存
     *
     * @param array $orderIds 订单ID数组
     * @param int $companyId 公司ID
     * @return int 清理成功的数量
     */
    public function batchClearRefundDetailCache($orderIds, $companyId)
    {
        $successCount = 0;
        
        foreach ($orderIds as $orderId) {
            if ($this->clearRefundDetailCache($companyId, $orderId)) {
                $successCount++;
            }
        }
        
        app('log')->info('[OrderInvoiceService][batchClearRefundDetailCache] 批量清理缓存完成', [
            'company_id' => $companyId,
            'total_count' => count($orderIds),
            'success_count' => $successCount
        ]);
        
        return $successCount;
    }

    /**
     * 定时开票方法 - 查询排队中的发票申请并推送到队列
     */
    public function invoiceStartSchedule()
    {
        app('log')->info('[OrderInvoiceService][invoiceStartSchedule] 开始执行定时开票任务');
        
        // 查询状态为 waitProgress 的发票申请
        $filter = [
            //'invoice_status' => 'waitProgress',
            'invoice_status' => 'pending',
            'invoice_method' => 'online',
        ];

        //查询线上开票的申请的公司id
        $pendingInvoicesonline = $this->repository->getLists($filter);
        $companyIds = array_unique(array_column($pendingInvoicesonline, 'company_id'));
        $applyNodeArr = [];
        //查询公司id的申请开票节点
        $settingService = new SettingService();
        foreach ($companyIds as $companyId) {
            $setting = $settingService->getInvoiceSetting($companyId);
            if ($setting && isset($setting['apply_node'])) {
                $applyNodeArr[$companyId] = $setting['apply_node'];
            }
        }

        foreach ($applyNodeArr as $companyId => $applyNode) {
            if ($applyNode == 1) {
                $filter['end_time|lt'] = time();
                $filter['end_time|gt'] = 0;
            }else{
                $filter['close_aftersales_time|lt'] = time();
                $filter['close_aftersales_time|gt'] = 0;
            }
            
            $pendingInvoices = $this->repository->getLists($filter);
            app('log')->info('[OrderInvoiceService][invoiceStartSchedule] 找到 ' . count($pendingInvoices) . ' 个待处理发票申请');
            
            foreach ($pendingInvoices as $invoice) {
                try {
                    // // 更新状态为开票中
                    // $this->repository->updateBy(
                    //     ['id' => $invoice['id']], 
                    //     ['invoice_status' => 'inProgress', 'update_time' => time()]
                    // );
                    
                    // 推送到队列
                    $jobData = [
                        'invoice_id' => $invoice['id'],
                        'company_id' => $invoice['company_id'],
                        'order_id' => $invoice['order_id'],
                        'invoice_type' => $invoice['invoice_type'],
                        'invoice_type_code' => $invoice['invoice_type_code'],
                        'company_title' => $invoice['company_title'],
                        'company_tax_number' => $invoice['company_tax_number'],
                        'company_address' => $invoice['company_address'],
                        'company_telephone' => $invoice['company_telephone'],
                        'bank_name' => $invoice['bank_name'],
                        'bank_account' => $invoice['bank_account'],
                        'email' => $invoice['email'],
                        'mobile' => $invoice['mobile'],
                    ];
                    
                    // 推送到 invoice 队列
                    dispatch(new \OrdersBundle\Jobs\InvoiceCreateJob($jobData))->onQueue('slow');
                    
                    app('log')->info('[OrderInvoiceService][invoiceStartSchedule] 发票申请 ID: ' . $invoice['id'] . ' 已推送到队列');
                    
                } catch (\Exception $e) {
                    app('log')->error('[OrderInvoiceService][invoiceStartSchedule] 处理发票申请失败 ID: ' . $invoice['id'] . ', 错误: ' . $e->getMessage());
                    
                    // // 更新状态为开票失败
                    // $this->repository->updateBy(
                    //     ['id' => $invoice['id']], 
                    //     ['invoice_status' => 'failed', 'update_time' => time()]
                    // );
                }
            }
        }
        
        app('log')->info('[OrderInvoiceService][invoiceStartSchedule] 定时开票任务执行完成');
    }

    /**
     * 红冲定时查询任务
     */
    public function invoiceRedQuerySchedule()
    {
        app('log')->info('[OrderInvoiceService][invoiceRedQuerySchedule] 开始执行红冲定时查询任务');
        
        // 查询条件：invoice_status = "waste" 且 invoice_file_url_red 为空
        $filter = [
            'invoice_status' => 'waste',
            'invoice_file_url_red|is' => null// 为空或null
        ];
        
        // 如果需要同时查询空字符串和null，可以使用以下方式：
        // $filter = [
        //     'invoice_status' => 'waste',
        //     'invoice_file_url_red' => ['', null] // 空字符串或null
        // ];
        
        app('log')->info('[OrderInvoiceService][invoiceRedQuerySchedule] 查询条件: ' . json_encode($filter));
        
        $pendingRedInvoices = $this->repository->getLists($filter);
        app('log')->info('[OrderInvoiceService][invoiceRedQuerySchedule] 查询结果: ' . json_encode($pendingRedInvoices));
        if (empty($pendingRedInvoices)) {
            app('log')->info('[OrderInvoiceService][invoiceRedQuerySchedule] 没有待处理的红冲发票');
            return;
        }
        
        app('log')->info('[OrderInvoiceService][invoiceRedQuerySchedule] 找到 ' . count($pendingRedInvoices) . ' 个待处理的红冲发票');
        
        foreach ($pendingRedInvoices as $invoice) {
            try {
                // 检查是否有红冲流水号
                if (empty($invoice['red_serial_no'])) {
                    app('log')->warning('[OrderInvoiceService][invoiceRedQuerySchedule] 发票缺少红冲流水号，跳过处理', [
                        'invoice_id' => $invoice['id'],
                        'invoice_apply_bn' => $invoice['invoice_apply_bn']
                    ]);
                    continue;
                }
                
                // 构建任务参数
                $jobData = [
                    'company_id' => $invoice['company_id'],
                    'order_id' => $invoice['order_id'],
                    'id' => $invoice['id'],
                    'redConfirmSerialNo' => $invoice['red_serial_no'],
                    'entryIdentity' => '0', // 默认销方
                    'type' => 'red'
                ];
                
                // 推送到 invoice 队列
                dispatch(new \OrdersBundle\Jobs\InvoiceRedQueryJob($jobData))->onQueue('slow');
                
                app('log')->info('[OrderInvoiceService][invoiceRedQuerySchedule] 红冲发票 ID: ' . $invoice['id'] . ' 已推送到队列');
                
            } catch (\Exception $e) {
                app('log')->error('[OrderInvoiceService][invoiceRedQuerySchedule] 处理红冲发票失败 ID: ' . $invoice['id'] . ', 错误: ' . $e->getMessage());
            }
        }
        
        app('log')->info('[OrderInvoiceService][invoiceRedQuerySchedule] 红冲定时查询任务执行完成');
    }

    /**
     * 创建发票 - 调用百旺服务
     *
     * @param array $data 发票数据
     * @return array
     */
    public function createFapiao($data)
    {
        app('log')->info('[OrderInvoiceService][createFapiao] 开始创建发票', $data);
        
        try {
            // 查询发票申请单号是否存在根据data里面的订单号
            $filter = [
                'order_id' => $data['order_id'],
                'invoice_status' => 'pending',
            ];
            $invoice = $this->repository->getInfo($filter);
            $data = $invoice;

            app('log')->info('[OrderInvoiceService][createFapiao] 发票申请单号:', $invoice);
            if(!$invoice){
                app('log')->info('[OrderInvoiceService][createFapiao] 发票申请单号不存在', $filter);
                return ['success' => false, 'message' => '发票申请单号不存在'];
            }
            $orderInfo = $this->getOrderInfo($data['order_id'],$invoice);

            $refundDetail = $this->getInvoiceRefundDetail($orderInfo['company_id'], $orderInfo['order_id']);
            foreach ($orderInfo['items'] as &$item) {
                $refund_fee = $refundDetail['itemRefundFee'][$item['item_id']]['refund_fee'] ?? 0;
                $refund_num = $refundDetail['itemRefundFee'][$item['item_id']]['num'] ?? 0;
                $item['invoice_amount'] = $item['item_fee'] - $refund_fee;
                $item['invoice_num'] = $item['num'] - $refund_num;
            }
            //从$orderInfo['items'] 中 sum判断invoice_num或invoice_amount如果剩余未 0 则不开发票了
            $invoice_num = array_sum(array_column($orderInfo['items'], 'invoice_num'));
            $invoice_amount = array_sum(array_column($orderInfo['items'], 'invoice_amount'));
            app('log')->info('[checkCreateInvoice] 商品已经全部售后？:invoice_num:'.$invoice_num.'invoice_amount:'.$invoice_amount   );
            if($invoice_num == 0 || $invoice_amount == 0){
                $updateData = [
                    'invoice_status' => 'cancel',
                    'update_time' => time()
                ];
                $this->repository->updateBy(['id' => $data['id']], $updateData);
                $this->createInvoiceLog($data['id'], 'system', '发票创建失败: 商品已经全部售后，无法开票,更新未取消。', $invoice);
                throw new ResourceException('商品已经全部售后，无法开票'); 
            }
            //检查是否可以开票
            $this->checkCreateInvoice($data,$orderInfo);

            // 根据售后信息更新发票商品行的金额和数量
            $this->updateInvoiceItemsByRefund($data['id'], $data['company_id'], $refundDetail);

            // 获取售后信息并更新发票商品行
            $data['refundDetail'] = $refundDetail;            
            app('log')->info('[OrderInvoiceService][createFapiao] 发票:', $data);
            // 调用百旺服务创建发票
            $baiwangService = new \ThirdPartyBundle\Services\FapiaoCentre\BaiwangService();
            $result = $baiwangService->createFapiao($data);
            
            app('log')->info('[OrderInvoiceService][createFapiao] 百旺服务返回结果', $result);
            
            // 记录发票创建日志
            $this->createInvoiceLog($invoice['id'], 'system', '发票创建成功', $result);
            
            // 根据结果更新发票状态
            if (isset($result['success']) && $result['success']) {
                $oldStatus = $data['invoice_status'];
                $updateData = [
                    'update_time' => time()
                ];
                
                // 检查是否有serialNo，如果有则更新invoice_apply_bn
                if (isset($result['response']['serialNo'])) {
                    $updateData['invoice_apply_bn'] = $result['response']['serialNo'];
                    $updateData['invoice_status'] = 'inProgress'; // 开票中，等待查询结果
                    
                    app('log')->info('[OrderInvoiceService][createFapiao] 发票申请已提交，serialNo: ' . $result['response']['serialNo']);
                    
                } else {
                    // // 如果没有serialNo，可能是直接开票成功
                    // $updateData['invoice_status'] = 'success';
                    
                    // // 如果有发票文件URL，保存到数据库
                    // if (isset($result['data']['invoice_file_url'])) {
                    //     $updateData['invoice_file_url'] = $result['data']['invoice_file_url'];
                    // }
                    
                    app('log')->info('[OrderInvoiceService][createFapiao] 发票创建成功，状态已更新');
                }
                
                $this->repository->updateBy(['id' => $data['id']], $updateData);
                
                // 监听发票状态变更
                $this->handleInvoiceStatusChange($data['id'], $oldStatus, $updateData['invoice_status']);
                
            } else {
                // 开票失败
                $this->repository->updateBy(
                    ['id' => $data['id']], 
                    ['invoice_status' => 'failed', 'update_time' => time()]
                );
                
                $errorMessage = isset($result['message']) ? $result['message'] : '开票失败';
                $this->createInvoiceLog($data['id'], 'system', '发票创建失败: ' . $errorMessage, $result);
                
                app('log')->error('[OrderInvoiceService][createFapiao] 发票创建失败', $result);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            app('log')->error('[OrderInvoiceService][createFapiao] 发票创建异常', [
                'invoice_id' => $data['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 更新状态为失败
            $this->repository->updateBy(
                ['id' => $data['id']], 
                ['invoice_status' => 'failed', 'update_time' => time()]
            );
            
            // 记录异常日志
            $this->createInvoiceLog($data['id'], 'system', '发票创建异常: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * 创建发票日志
     *
     * @param int $invoiceId 发票ID
     * @param string $action 操作类型
     * @param string $message 日志消息
     * @param array $data 相关数据
     * @param int $operatorId 操作人ID
     * @return void
     */
    private function createInvoiceLog($invoiceId, $action, $message, $data = [], $operatorId = 0)
    {
        try {
            $logData = [
                'invoice_id' => $invoiceId,
                'operator_type' => $action,
                'operator_content' => [
                    'title' => $this->getLogTitle($action),
                    'remark' => $message,
                    'data' => $data,
                    'action_type' => $action,
                    'create_time' => date('Y-m-d H:i:s')
                ],
                'operator_id' => $operatorId,
                'create_time' => time()
            ];
            
            $this->logRepository->create($logData);
            
            app('log')->info('[OrderInvoiceService][createInvoiceLog] 发票日志已记录', [
                'invoice_id' => $invoiceId,
                'action' => $action,
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            app('log')->error('[OrderInvoiceService][createInvoiceLog] 记录发票日志失败', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 处理发票状态变更
     *
     * @param int $invoiceId 发票ID
     * @param string $oldStatus 原状态
     * @param string $newStatus 新状态
     * @return void
     */
    private function handleInvoiceStatusChange($invoiceId, $oldStatus, $newStatus)
    {
        try {
            app('log')->info('[OrderInvoiceService][handleInvoiceStatusChange] 发票状态变更', [
                'invoice_id' => $invoiceId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
            
            // 记录状态变更日志
            $this->createInvoiceLog($invoiceId, 'system', "发票状态从 {$oldStatus} 变更为 {$newStatus}", [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'change_time' => date('Y-m-d H:i:s')
            ]);
            
            // 如果状态从pending变为inProgress，说明开始开票，此时不应再进行售后调整
            if ($oldStatus === 'pending' && $newStatus === 'inProgress') {
                app('log')->info('[OrderInvoiceService][handleInvoiceStatusChange] 发票开始开票，停止售后调整', [
                    'invoice_id' => $invoiceId
                ]);
                
                // 可以在这里添加其他逻辑，比如通知相关系统等
            }
            
            // 如果状态变为success，说明开票成功
            if ($newStatus === 'success') {
                app('log')->info('[OrderInvoiceService][handleInvoiceStatusChange] 发票开票成功', [
                    'invoice_id' => $invoiceId
                ]);
                
                // 可以在这里添加开票成功后的处理逻辑
            }
            
        } catch (\Exception $e) {
            app('log')->error('[OrderInvoiceService][handleInvoiceStatusChange] 处理状态变更失败', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取日志标题
     *
     * @param string $action 操作类型
     * @return string
     */
    private function getLogTitle($action)
    {
        $titles = [
            'create' => '创建发票',
            'update' => '更新发票',
            'refund_adjust' => '售后调整',
            'refund_adjust_error' => '售后调整失败',
            'query' => '查询发票',
            'query_result' => '查询结果处理',
            'red' => '发票冲红',
            'red_query' => '红票查询',
            'red_query_error' => '红票查询失败',
            'cancel' => '发票取消',
            'resend_email' => '重发邮件',
            'update_remark' => '更新备注'
        ];
        
        return $titles[$action] ?? '发票操作';
    }

    /**
     * 查询发票结果 - 调用百旺服务
     *
     * @param array $data 查询参数
     * @return array
     */
    public function queryInvoice($data)
    {
        app('log')->info('[OrderInvoiceService][queryInvoice] 开始查询发票结果', $data);
        
        try {
            // 查询发票申请单号是否存在根据data里面的订单号
            $filter = [
                'id' => $data['invoice_id'],
                'invoice_status' => 'inProgress',
            ];
            $invoice = $this->repository->getInfo($filter);
            app('log')->info('[OrderInvoiceService][queryInvoice] 发票申请:filter:', $filter);
            app('log')->info('[OrderInvoiceService][queryInvoice] 发票申请:invoice:', $invoice);
            if(!$invoice){
                app('log')->info('[OrderInvoiceService][queryInvoice] 发票申请单号不存在', $filter);
                return ['success' => false, 'message' => '发票申请单号不存在'];
            }
            $data = $invoice;

            app('log')->info('[OrderInvoiceService][queryInvoice] 发票:', $data);
            // 调用百旺服务查询发票
            $baiwangService = new \ThirdPartyBundle\Services\FapiaoCentre\BaiwangService();
            $result = $baiwangService->queryInvoice($data);
            
            app('log')->info('[OrderInvoiceService][queryInvoice] 百旺服务返回结果', $result);
            //{"method":"baiwang.s.outputinvoice.query","requestId":"bw_687d032b875113.46996718","response":[{"serialNo":"25071910154409001263","orderNo":"4940599000010002-28","status":"01","statusMessage":"开票完成","errorMessage":"","xmlUrl":"","pdfUrl":"https://sales.baiwang.com/files/taxInvoice.pdf","orderDateTime":"2025-07-11 14:59:26","invoiceTime":"2025-07-19 10:15:45","invoiceCode":"999977292601","invoiceNo":"00002834","invoiceTotalPrice":"166.02","invoiceTotalTax":"14.98","invoiceTotalPriceTax":"181.00","priceTaxMark":"1","buyerName":"广州腾讯科技有限公司","buyerCredentialsType":"","buyerCredentialsNo":"","buyerNationalityRegion":"","buyerTaxNo":"91440101327598294H","buyerAddress":"广州市海珠区新港中路397号自编72号(商业街F5-1)","buyerTelephone":"","buyerBankName":"","buyerBankNumber":"","invoiceTypeCode":"026","invoiceCheckCode":"250719101545001264","machineNo":"","invoiceCipher":"4>19/<-16573</600>+8/47<79/61740++4<+12594<+2265822<4/2+2-+4>23/2+813>202/7922>91913636149280879441>42<3+8<6","drawer":"开票员","payee":"","checker":"","sellerBankNumber":"124","sellerBankName":"北京银行","sellerPhone":"110","sellerAddress":"北京","taxNo":"338888888888SMB","sellerName":"SMB模拟账号22222","remarks":"","requestSource":0,"invoiceSpecialMark":"00","invoiceTerminalCode":"202312120001","drawerAccount":"","originalInvoiceCode":"","originalInvoiceNo":"","invoiceListMark":"0","invoiceListName":"","pushPhone":"","pushEmail":"","deliverShortUrl":"https://bwfp-pre.baiwang.com/Il3mECtN8H34JcKFL","statusUpdateTime":"2025-07-19 10:15:45","invoiceType":"1","ext":[],"invoiceDetailList":[{"goodsLineNo":"1","goodsName":"【测试商品，请勿下单】AmandaX“意境东方”系列100%真丝欧根缎中式盘金绣钉珠外套礼服","goodsSpecification":"","goodsUnit":"件","goodsQuantity":"1","goodsPrice":"180","excludeTaxGoodsPrice":"165.137614678899","includeTaxGoodsPrice":"180","goodsTotalPriceTax":"180.00","goodsTotalPrice":"165.14","goodsTotalTax":"14.86","goodsPersonalCode":"","goodsCode":"1010101010000000000","priceTaxMark":"1","invoiceLineNature":"0","preferentialMark":"0","vatSpecialManagement":"","freeTaxMark":"0","goodsTaxRate":"0.09","deductibleAmount":"","goodsSimpleName":"谷物","ext":[]},{"goodsLineNo":"2","goodsName":"运费","goodsSpecification":"","goodsUnit":"次","goodsQuantity":"1","goodsPrice":"1","excludeTaxGoodsPrice":"0.88495575221239","includeTaxGoodsPrice":"1","goodsTotalPriceTax":"1.00","goodsTotalPrice":"0.88","goodsTotalTax":"0.12","goodsPersonalCode":"","goodsCode":"3040407990000000000","priceTaxMark":"1","invoiceLineNature":"0","preferentialMark":"0","vatSpecialManagement":"","freeTaxMark":"0","goodsTaxRate":"0.13","deductibleAmount":"","goodsSimpleName":"物流辅助服务","ext":[]}],"mulPurchaserMark":"0","invoiceBaseStatus":"01"}],"success":true}
            //| status | String | 01 | 发票状态（01-开票完成） |
            //| statusMessage | String | 发票状态描述 |
            //"error":"Invalid argument supplied for foreach()"
            if(isset($result['response']) && is_array($result['response'])){
                foreach($result['response'] as $item){
                    if($item['status'] == '01'){
                        $updateData = [
                            'update_time' => time()
                        ];
                        $updateData['invoice_status'] = 'success';  
                        $updateData['invoice_file_url'] = $item['pdfUrl'];
                        $this->repository->updateBy(['id' => $data['id']], $updateData);

                        // 更新订单状态
                        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
                        $update_data = [];
                        $update_data['invoice_status'] = 'success';
                        $filter = ['order_id' => $invoice['order_id']];
                        app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
                        $normalOrdersRepository->updateBy($filter, $update_data);

                        $this->createInvoiceLog($data['id'], 'system', '发票查询完成.发票状态:'.$item['status'].'发票状态描述:'.$item['statusMessage'], $result);
                        app('log')->info('发票查询完成 to send email:'.$invoice['email'].':invoice_file_url:'.$updateData['invoice_file_url']);

                        // 如果开票成功且有邮箱，则发送邮件通知
                       if ($updateData['invoice_status'] == 'success' &&
                           !empty($invoice['email']) &&
                           !empty($updateData['invoice_file_url'])) {
                           app('log')->info('发票查询完成 to send email:'.$invoice['email'].':invoice_file_url:'.$updateData['invoice_file_url']);
                           $this->sendInvoiceEmail($invoice['email'], $updateData['invoice_file_url'],$invoice['company_id']);
        
                           app('log')->info('开票成功，已发送邮件通知');
                       }                        
                    }    
    
                }
            }

            // 记录发票查询日志
            
            return $result;
            
        } catch (\Exception $e) {
            app('log')->error('[OrderInvoiceService][queryInvoice] 发票查询异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 记录异常日志
            $this->createInvoiceLog($data['id'], 'system', '发票查询异常: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * 定时查询开票结果 - 查询开票中的发票并推送到队列
     */
    public function queryInvoiceSchedule()
    {
        app('log')->info('[OrderInvoiceService][queryInvoiceSchedule] 开始执行定时查询开票结果任务');
        
        // 查询状态为 inProgress 且更新时间超过5分钟的发票申请
        $fiveMinutesAgo = time() - (3 * 60);
        
        $filter = [
            'invoice_status' => 'inProgress',
            'update_time|lt' => $fiveMinutesAgo
        ];
        
        $inProgressInvoices = $this->repository->getLists($filter);
        
        if (empty($inProgressInvoices)) {
            app('log')->info('[OrderInvoiceService][queryInvoiceSchedule] 没有需要查询的发票申请');
            return;
        }
        
        app('log')->info('[OrderInvoiceService][queryInvoiceSchedule] 找到 ' . count($inProgressInvoices) . ' 个需要查询的发票申请');
        
        foreach ($inProgressInvoices as $invoice) {
            try {
                // 推送到队列
                $jobData = [
                    'invoice_id' => $invoice['id'],
                    'company_id' => $invoice['company_id'],
                    'invoice_apply_bn' => $invoice['invoice_apply_bn'],
                    'order_id' => $invoice['order_id']
                ];
                app('log')->info('[OrderInvoiceService][queryInvoiceSchedule] 发票查询任务:jobData:', $jobData);

                dispatch(new \OrdersBundle\Jobs\InvoiceQueryJob($jobData))->onQueue('slow');
                
                app('log')->info('[OrderInvoiceService][queryInvoiceSchedule] 发票查询任务 ID: ' . $invoice['id'] . ' 已推送到队列');
                
            } catch (\Exception $e) {
                app('log')->error('[OrderInvoiceService][queryInvoiceSchedule] 处理发票查询任务失败 ID: ' . $invoice['id'] . ', 错误: ' . $e->getMessage());
            }
        }
        
        app('log')->info('[OrderInvoiceService][queryInvoiceSchedule] 定时查询开票结果任务执行完成');
    }

    /**
     * 处理发票查询结果
     *
     * @param array $queryResult 查询结果
     * @param int $invoiceId 发票ID
     * @return void
     */
    public function handleQueryResult($queryResult, $invoiceId)
    {
        app('log')->info('[OrderInvoiceService][handleQueryResult] 处理发票查询结果', [
            'invoice_id' => $invoiceId,
            'result' => $queryResult
        ]);
        
        try {
            if (!isset($queryResult['success']) || !$queryResult['success']) {
                app('log')->error('[OrderInvoiceService][handleQueryResult] 查询失败', $queryResult);
                return;
            }
            
            $response = $queryResult['response'] ?? [];
            if (empty($response) || !is_array($response)) {
                app('log')->error('[OrderInvoiceService][handleQueryResult] 响应数据为空或格式错误', $queryResult);
                return;
            }
            
            foreach ($response as $invoiceInfo) {
                app('log')->info('[OrderInvoiceService][handleQueryResult] invoiceInfo:'.json_encode($invoiceInfo));
                $status = $invoiceInfo['status'] ?? '';
                $statusMessage = $invoiceInfo['statusMessage'] ?? '';
                $serialNo = $invoiceInfo['serialNo'] ?? '';
                $pdfUrl = $invoiceInfo['pdfUrl'] ?? '';
                $invoiceCode = $invoiceInfo['invoiceCode'] ?? '';
                $invoiceNo = $invoiceInfo['invoiceNo'] ?? '';
                
                // 根据状态更新发票信息
                $updateData = [
                    'update_time' => time()
                ];
                app('log')->info('[OrderInvoiceService][handleQueryResult] 发票状态:'.$status);
                if ($status === '01') {
                    // 开票完成
                    $updateData['invoice_status'] = 'success';
                    $updateData['invoice_file_url'] = $pdfUrl;
                    //[2025-07-24 14:15:02] production.INFO: [OrderInvoiceService][handleQueryResult] invoiceInfo:{"serialNo":"25072212570209001994","orderNo":"4950694000100002-40","status":"01","statusMessage":"\u5f00\u7968\u5b8c\u6210","errorMessage":"","xmlUrl":"","pdfUrl":"https:\/\/sales.baiwang.com\/files\/digitInvoice.pdf","orderDateTime":"2025-07-21 17:21:23","invoiceTime":"2025-07-22 12:57:03","invoiceCode":"","invoiceNo":"20002946031400006182","digitInvoiceNo":"20002946031400006182","invoiceTotalPrice":"0.92","invoiceTotalTax":"0.08","invoiceTotalPriceTax":"1.00","priceTaxMark":"1","buyerName":"\u674e\u5065","buyerTaxNo":"","buyerAddress":"","buyerTelephone":"","buyerBankName":"","buyerBankNumber":"","invoiceTypeCode":"02","invoiceCheckCode":"250722125703001995","machineNo":"","invoiceCipher":"3>>06+3+6123801++<6>+5>16<2><\/2284>740-5541-8623+>>5-75\/->-06321>438+04082937738022<3-9\/\/<+>31<800+734-5<83<","drawer":"\u5f00\u7968\u5458","payee":"","checker":"","sellerBankNumber":"124","sellerBankName":"\u5317\u4eac\u94f6\u884c","sellerPhone":"110","sellerAddress":"\u5317\u4eac","taxNo":"338888888888SMB","sellerName":"SMB\u6a21\u62df\u8d26\u53f722222","remarks":"","requestSource":0,"invoiceSpecialMark":"00","paperInvoiceType":"","invoiceTerminalCode":"15888888888","drawerAccount":"","originalInvoiceCode":"","originalInvoiceNo":"","invoiceListMark":"0","invoiceListName":"","pushPhone":"","pushEmail":"","deliverShortUrl":"https:\/\/bwfp-pre.baiwang.com\/JiDqztqttI8LD_-6L","statusUpdateTime":"2025-07-22 12:57:03","invoiceType":"1","naturalMark":"0","ext":[],"invoiceDetailList":[{"goodsLineNo":"1","goodsName":"\u3010\u6d4b\u8bd5\u5546\u54c1\uff0c\u8bf7\u52ff\u4e0b\u5355\u3011AmandaX\u201c\u5927\u822a\u6d77\u5bb6\u201d\u7cfb\u5217 100%\u4e1d\u5149\u68c9\u6eda\u8fb9\u9488\u7ec7\u5f00\u886b\u5916\u5957","goodsSpecification":"","goodsUnit":"\u4ef6","goodsQuantity":"1","goodsPrice":"1","excludeTaxGoodsPrice":"0.9174311926606","includeTaxGoodsPrice":"1","goodsTotalPriceTax":"1.00","goodsTotalPrice":"0.92","goodsTotalTax":"0.08","goodsPersonalCode":"","goodsCode":"1010101010000000000","priceTaxMark":"1","invoiceLineNature":"0","preferentialMark":"0","vatSpecialManagement":"","freeTaxMark":"0","goodsTaxRate":"0.09","deductibleAmount":"","goodsSimpleName":"\u8c37\u7269","ext":[]}],"mulPurchaserMark":"0","invoiceBaseStatus":"01"}
                    $updateData['query_content'] = json_encode($invoiceInfo);
                    // 更新发票申请单号为serialNo
                    if (!empty($serialNo)) {
                        $updateData['invoice_apply_bn'] = $serialNo;
                    }
                    
                    // 记录发票代码和号码
                    if (!empty($invoiceCode) && !empty($invoiceNo)) {
                        $updateData['invoice_code'] = $invoiceCode;
                        $updateData['invoice_no'] = $invoiceNo;
                    }
                    
                    app('log')->info('[OrderInvoiceService][handleQueryResult] 发票开票完成', [
                        'invoice_id' => $invoiceId,
                        'serial_no' => $serialNo,
                        'pdf_url' => $pdfUrl
                    ]);
                    
                } elseif ($status === '02') {
                    // 开票失败
                    $updateData['invoice_status'] = 'failed';
                    $errorMessage = $invoiceInfo['errorMessage'] ?? '开票失败';
                    
                    app('log')->error('[OrderInvoiceService][handleQueryResult] 发票开票失败', [
                        'invoice_id' => $invoiceId,
                        'error_message' => $errorMessage
                    ]);
                    
                } else {
                    // 其他状态，继续等待
                    app('log')->info('[OrderInvoiceService][handleQueryResult] 发票状态未完成', [
                        'invoice_id' => $invoiceId,
                        'status' => $status,
                        'status_message' => $statusMessage
                    ]);
                    continue;
                }
                app('log')->info('[OrderInvoiceService][handleQueryResult] updateById:发票状态:'.$status.':invoiceId:'.$invoiceId.':updateData:'.json_encode($updateData));
                // 更新数据库
                $this->repository->updateBy(['id' => $invoiceId], $updateData);
                
                // 记录处理日志
                $this->createInvoiceLog($invoiceId, 'system', '发票查询结果处理: ' . $statusMessage, $invoiceInfo);
            }
            
        } catch (\Exception $e) {
            app('log')->error('[OrderInvoiceService][handleQueryResult] 处理查询结果异常', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 记录异常日志
            $this->createInvoiceLog($invoiceId, 'system', '处理查询结果异常: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 查询发票商品税率
     * @param array $itemIds 商品ID数组
     * @param int $companyId 公司ID
     * @return array 税率信息数组，key为item_id，value为税率
     */
    public function getInvoiceRate($itemIds, $companyId)
    {
        app('log')->info('[getInvoiceRate] 开始查询发票商品税率:items:'.json_encode($itemIds).':companyId:'.$companyId);
        try {
            if (empty($itemIds)) {
                return [];
            }
            app('log')->info('[getInvoiceRate] 开始查询发票商品税率:items:'.json_encode($itemIds));
            // 查询商品分类信息
            $itemsRepository = app('registry')->getManager('default')->getRepository(\GoodsBundle\Entities\Items::class);
            $itemsFilter = [
                'item_id' => $itemIds,
                'company_id' => $companyId
            ];
            $items = $itemsRepository->getLists($itemsFilter);
            
            if (empty($items)) {
                app('log')->warning('[getInvoiceRate] 未找到商品信息', [
                    'item_ids' => $itemIds,
                    'company_id' => $companyId
                ]);
                return [];
            }

            // 提取商品分类ID
            $categoryIds = array_column($items, 'item_category');
            $categoryIds = array_filter($categoryIds); // 过滤空值
            app('log')->info('[getInvoiceRate] 商品分类ID:'.json_encode($categoryIds));
            if (empty($categoryIds)) {
                app('log')->warning('[getInvoiceRate] 商品没有分类信息', [
                    'item_ids' => $itemIds,
                    'company_id' => $companyId
                ]);
                // return [];
            }

            // 查询默认税率
            $defaultRate = 0;
            $categoryTaxRateRepository = app('registry')->getManager('default')->getRepository(\OrdersBundle\Entities\CategoryTaxRate::class);
            $defaultTaxRateFilter = [
                'tax_rate_type' => 'ALL',
                // 'company_id' => $companyId
            ];
            app('log')->info('[getInvoiceRate] 默认税率查询条件:'.json_encode($defaultTaxRateFilter));
            $defaultTaxRate = $categoryTaxRateRepository->getInfo($defaultTaxRateFilter);
            app('log')->info('[getInvoiceRate] 默认税率:'.json_encode($defaultTaxRate));
            if ($defaultTaxRate) {
                $defaultRate = $defaultTaxRate['invoice_tax_rate'] ?? 0;
            }
            app('log')->info('[getInvoiceRate] 默认税率:'.json_encode($defaultRate));
            // 查询商品分类税率
            $itemsCategoryRepository = app('registry')->getManager('default')->getRepository(\GoodsBundle\Entities\ItemsCategory::class);
            $categoryFilter = [
                'category_id' => $categoryIds,
                'company_id' => $companyId
            ];
            $categories = $itemsCategoryRepository->lists($categoryFilter);
            app('log')->info('[getInvoiceRate] 商品分类税率:'.json_encode($categories));
            // 构建分类税率映射
            $categoryRateMap = [];
            foreach ($categories['list'] as $category) {
                $categoryRateMap[$category['category_id']] = $category['invoice_tax_rate'] ?? $defaultRate;
            }
            app('log')->info('[getInvoiceRate] 商品分类税率:'.json_encode($categoryRateMap));


            // 构建商品税率映射
            $itemRateMap = [];
            foreach ($items as $item) {
                $itemId = $item['item_id'];
                $categoryId = $item['item_category'];
                $rate = 0;

                if ($categoryId) {
                    // 从已查询的分类税率映射中查找
                    if (isset($categoryRateMap[$categoryId])) {
                        $rate = $categoryRateMap[$categoryId];
                    }
                }

                // 如果没有找到分类税率，使用默认税率
                if ($rate == 0) {
                    $rate = $defaultRate;
                }

                $itemRateMap[$itemId] = $rate;
            }

            app('log')->info('[getInvoiceRate] 税率查询完成', [
                'item_ids' => $itemIds,
                'company_id' => $companyId,
                'item_rate_map' => $itemRateMap,
                'default_rate' => $defaultRate
            ]);

            return $itemRateMap;
        } catch (\Exception $e) {
            app('log')->error('[getInvoiceRate] 税率查询异常', [
                'item_ids' => $itemIds,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * 批量查询发票商品税率（优化版本）
     * @param array $orderItems 订单商品数组，每个元素包含item_id
     * @param int $companyId 公司ID
     * @return array 税率信息数组，key为item_id，value为税率
     */
    public function getInvoiceRateBatch($orderItems, $companyId)
    {
        try {
            if (empty($orderItems)) {
                return [];
            }

            // 提取商品ID
            $itemIds = array_column($orderItems, 'item_id');
            $itemIds = array_unique($itemIds);

            return $this->getInvoiceRate($itemIds, $companyId);
        } catch (\Exception $e) {
            app('log')->error('[getInvoiceRateBatch] 批量税率查询异常', [
                'order_items_count' => count($orderItems),
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function redInvoice($params = []){
        // 根据$params 的 order_id 查询订单信息
        $filter = [
            'order_id' => $params['order_id'],
            'invoice_status' => 'success',
            'company_id' => $params['company_id']
        ];
        $invoiceInfo = $this->repository->getInfo($filter);
        app('log')->info('[redInvoice] 订单信息:'.json_encode($invoiceInfo));
        if (empty($invoiceInfo)) {
            app('log')->info('[redInvoice] 订单信息为空,订单号:'.$params['order_id']);
            return [];
        }

        $fapiaoService = new \ThirdPartyBundle\Services\FapiaoCentre\BaiwangService($params['company_id']);
        $res = $fapiaoService->redInvoice($invoiceInfo);
        app('log')->info('[redInvoice] 冲红结果:'.json_encode($res));
        // [redInvoice] 冲红结果:{"method":"baiwang.s.outputinvoice.fastRed","requestId":"bw_6881daaf725ea6.11892602","response":{"redConfirmSerialNo":"1397957295973613568"},"success":true}
        if($res['success']){
            $updateData = [];
            $updateData['red_serial_no'] = $res['response']['redConfirmSerialNo'];
            $updateData['red_content'] = json_encode($res);
            
            $updateData['red_apply_bn'] = $invoiceInfo['order_id']."-".$invoiceInfo['id']."-".$invoiceInfo['try_times']."-".$invoiceInfo['id'];
            //status
            $updateData['invoice_status'] = 'waste';
            $this->repository->updateBy(['id' => $invoiceInfo['id']], $updateData);
        }
        //创建发票日志
        $this->createInvoiceLog($invoiceInfo['id'], 'system', '冲红结果.状态:'.$res['success'].'冲红流水号:'.$res['response']['redConfirmSerialNo'], $res);
        // app('log')->info('冲红结果 to send email:'.$invoiceInfo['email'].':invoice_file_url:'.$updateData['invoice_file_url']);

        return $res;
    }

    /**
     * 查询红冲发票
     * @param array $params [
     *   'company_id' => '', // 公司ID
     *   'order_id' => '', // 订单ID
     *   'id' => '', // 发票ID
     *   'redConfirmSerialNo' => '', // 红冲确认单流水号
     *   'entryIdentity' => '', // 录入方身份
     *   'type' => 'red' // 类型
     * ]
     * @return array
     */
    public function queryRedInvoice($params = [])
    {
        try {
            $companyId = $params['company_id'] ?? 1;
            $invoiceId = $params['id'] ?? '';
            $redConfirmSerialNo = $params['redConfirmSerialNo'] ?? '';

            if (empty($invoiceId)) {
                throw new \Exception(trans('OrdersBundle/Order.invoice_id_cannot_be_empty'));
            }

            if (empty($redConfirmSerialNo)) {
                throw new \Exception(trans('OrdersBundle/Order.red_invoice_confirmation_number_required'));
            }

            app('log')->info('[queryRedInvoice] 开始查询红冲发票', [
                'invoice_id' => $invoiceId,
                'red_confirm_serial_no' => $redConfirmSerialNo,
                'company_id' => $companyId
            ]);

            // 获取发票信息
            $invoiceInfo = $this->repository->getInfoById($invoiceId);
            if (empty($invoiceInfo)) {
                throw new \Exception(trans('OrdersBundle/Order.invoice_info_not_exist'));
            }

            // 调用百旺服务查询发票
            $fapiaoService = new \ThirdPartyBundle\Services\FapiaoCentre\BaiwangService($companyId);
            
            // 构建查询参数，参考测试命令的参数格式
            $queryParams = $invoiceInfo;
            $queryParams['redConfirmSerialNo'] = $redConfirmSerialNo;
            $queryParams['entryIdentity'] = $params['entryIdentity'] ?? '0';
            $queryParams['type'] = 'red';

            app('log')->info('[queryRedInvoice] 百旺查询参数', $queryParams);
            $result = $fapiaoService->queryInvoice($queryParams);

            app('log')->info('[queryRedInvoice] 百旺查询结果', $result);
            //[queryRedInvoice] 百旺查询结果 {"method":"baiwang.s.outputinvoice.query","requestId":"bw_6882278664efa9.00137959","response":[{"serialNo":"25072415031209002798","orderNo":"4950694000100002-40-40","status":"01","statusMessage":"开票完成","errorMessage":"","xmlUrl":"","pdfUrl":"https://sales.baiwang.com/files/digitInvoice.pdf","orderDateTime":"2025-07-24 15:03:12","invoiceTime":"2025-07-24 15:03:13","invoiceCode":"","invoiceNo":"20002946031400006362","digitInvoiceNo":"20002946031400006362","invoiceTotalPrice":"-0.92","invoiceTotalTax":"-0.08","invoiceTotalPriceTax":"-1.00","priceTaxMark":"1","buyerName":"李健","buyerTaxNo":"","buyerAddress":"","buyerTelephone":"","buyerBankName":"","buyerBankNumber":"","invoiceTypeCode":"02","invoiceCheckCode":"","machineNo":"","invoiceCipher":"","drawer":"\t开票员","payee":"","checker":"","sellerBankNumber":"124","sellerBankName":"北京银行","sellerPhone":"110","sellerAddress":"北京","taxNo":"338888888888SMB","sellerName":"SMB模拟账号22222","remarks":"被红冲蓝字数电发票号码：20002946031400006182 红字发票信息确认单编号：99992507241503120155","requestSource":0,"invoiceSpecialMark":"00","paperInvoiceType":"","invoiceTerminalCode":"15888888888","drawerAccount":"","originalInvoiceCode":"","originalInvoiceNo":"20002946031400006182","originalDigitInvoiceNo":"20002946031400006182","invoiceListMark":"0","invoiceListName":"","pushPhone":"","pushEmail":"","deliverShortUrl":"https://bwfp-pre.baiwang.com/HsU1ik3_tI8KwiRWq","statusUpdateTime":"2025-07-24 15:03:14","invoiceType":"2","naturalMark":"0","ext":[],"invoiceDetailList":[{"goodsLineNo":"1","goodsName":"【测试商品，请勿下单】AmandaX“大航海家”系列 100%丝光棉滚边针织开衫外套","goodsSpecification":"","goodsUnit":"件","goodsQuantity":"-1","goodsPrice":"1","excludeTaxGoodsPrice":"0.9174311926606","includeTaxGoodsPrice":"1","goodsTotalPriceTax":"-1.00","goodsTotalPrice":"-0.92","goodsTotalTax":"-0.08","goodsPersonalCode":"","goodsCode":"1010101010000000000","priceTaxMark":"1","invoiceLineNature":"0","preferentialMark":"0","vatSpecialManagement":"","freeTaxMark":"0","goodsTaxRate":"0.09","deductibleAmount":"","goodsSimpleName":"谷物","ext":[]}],"mulPurchaserMark":"0","invoiceBaseStatus":"01"}],"success":true}
            if (!$result['success']) {
                throw new \Exception(trans('OrdersBundle/Order.baiwang_query_failed', ['{0}' => ($result['message'] ?? '未知错误')]));
            }

            // 处理查询结果

            foreach($result['response'] as $item){
                // 查找匹配的红票记录：状态是 "status":"01" 并且 "invoiceType":"2"
                if($item['status'] == '01' && $item['invoiceType'] == '2'){
                            
                    // 更新发票信息
                    $updateData = [
                        'invoice_file_url_red' => $item['pdfUrl'] ?? '',
                        'update_time' => time(),
                        'red_content' => json_encode($item)
                    ];

                    $this->repository->updateBy(['id' => $invoiceInfo['id']], $updateData);

                    // 更新订单状态
                    $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
                    $update_data = [];
                    $update_data['invoice_status'] = 'red';
                    $filter = ['order_id' => $invoiceInfo['order_id']];
                    app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
                    $normalOrdersRepository->updateBy($filter, $update_data);


                    app('log')->info('[queryRedInvoice] 红票文件地址更新成功', [
                        'invoice_id' => $invoiceId,
                        'invoice_file_url_red' => $updateData['invoice_file_url_red'],
                        'red_content' => $updateData['red_content']
                    ]);
                    
                }
            }

            // 创建发票日志
            $this->createInvoiceLog($invoiceInfo['id'], 'system', '冲红查询结果.状态:'.$result['success'].'红冲流水号:'.$redConfirmSerialNo, $result);

            return $result;

        } catch (\Exception $e) {
            app('log')->error('[queryRedInvoice] 查询红冲发票失败', [
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 创建错误日志
            if (!empty($params['id'])) {
                $this->createInvoiceLog($params['id'], 'system', '红冲查询失败: ' . $e->getMessage(), [
                    'params' => $params,
                    'error' => $e->getMessage()
                ]);
            }

            throw $e;
        }
    }

    function updateInvoiceStatusCancel($companyId, $orderId, $status){
        $filter = [
            'order_id' => $orderId,
            'invoice_status' => 'pending',
            'company_id' => $companyId
        ];
        // 查询发票
        $invoice = $this->repository->getInfo($filter);
        app('log')->info('[updateInvoiceStatusCancel] 发票信息', [
            'invoice' => $invoice
        ]);
        if(!$invoice){
            app('log')->info('[updateInvoiceStatusCancel] 发票不存在', [
                'filter' => $filter
            ]);
            return false;
        }

        $updateData = [
            'invoice_status' => $status,
            'update_time' => time()
        ];
        app('log')->info('[updateInvoiceStatusCancel] 发票状态更新条件', [ 'filter' => $filter ]);
        $this->repository->updateBy($filter, $updateData);
        app('log')->info('[updateInvoiceStatusCancel] 发票状态更新成功', [
            'filter' => $filter,
            'update_data' => $updateData
        ]);

        // 更新订单状态
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $update_data = [];
        $update_data['invoice_status'] = $status;
        $filter = ['order_id' => $invoice['order_id']];
        app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
        $normalOrdersRepository->updateBy($filter, $update_data);

        // 创建发票日志
        $this->createInvoiceLog($invoice['id'], 'admin', '发票状态更新:'.$status , $updateData);

        return true;

    }

    /**
     * 检查是否可以开票
     * @param array $data 数据
     * @param array $orderInfo 订单信息
     * @return bool
     */
    public function checkCreateInvoice($data,$orderInfo){
        $settingService = new SettingService();
        $settingData = $settingService->getInvoiceSetting($orderInfo['company_id']); 
        if($settingData['invoice_status'] == 0){
            app('log')->info(__FUNCTION__.':'.__LINE__.'[checkCreateInvoice] 发票申请已关闭，无法开票',['settingData' => $settingData]);
            throw new ResourceException('发票申请已关闭，无法开票'); 
        }

        if($orderInfo['order_status'] == 'CANCEL' || $orderInfo['cancel_status'] == 'WAIT_PROCESS'){
            app('log')->info(__FUNCTION__.':'.__LINE__.'[checkCreateInvoice] 订单已取消，无法开票',['order_status' => $orderInfo['order_status']]);
            throw new ResourceException('订单已取消，无法开票'); 
        }

        if($orderInfo['order_status'] == 'NOTPAY' && isset($settingData['apply_type']) && $settingData['apply_type'] == 2){
            app('log')->info(__FUNCTION__.':'.__LINE__.'[checkCreateInvoice] 订单未支付，无法开票',['order_status' => $orderInfo['order_status']]);
            throw new ResourceException('订单未支付，无法开票'); 
        }

        //售后判断
        $afterSaleService = new AftersalesService();
        $afterSaleFilter = [
            'company_id' => $orderInfo['company_id'],
            'order_id' => $orderInfo['order_id'],
            
        ];  

        $refundDetail = $this->getInvoiceRefundDetail($orderInfo['company_id'], $orderInfo['order_id']);
        foreach ($orderInfo['items'] as &$item) {
            $refund_fee = $refundDetail['itemRefundFee'][$item['item_id']]['refund_fee'] ?? 0;
            $refund_num = $refundDetail['itemRefundFee'][$item['item_id']]['num'] ?? 0;
            $item['invoice_amount'] = $item['item_fee'] - $refund_fee;
            $item['invoice_num'] = $item['num'] - $refund_num;
        }
        //从$orderInfo['items'] 中 sum判断invoice_num或invoice_amount如果剩余未 0 则不开发票了
        $invoice_num = array_sum(array_column($orderInfo['items'], 'invoice_num'));
        $invoice_amount = array_sum(array_column($orderInfo['items'], 'invoice_amount'));
        app('log')->info(__FUNCTION__.':'.__LINE__.'[checkCreateInvoice] 商品已经全部售后？invoice_num:'.$invoice_num.'invoice_amount:'.$invoice_amount   );
        if($invoice_num == 0 || $invoice_amount == 0){
            app('log')->info('[checkCreateInvoice] 商品已经全部售后，无法开票',['invoice_num' => $invoice_num,'invoice_amount' => $invoice_amount]);
            throw new ResourceException('商品已经全部售后，无法开票'); 
        }
        app('log')->info(__FUNCTION__.':'.__LINE__.':[checkCreateInvoice] 售后信息', ['afterSaleFilter' => $afterSaleFilter]);
        $afterSaleInfo = $afterSaleService->getAftersalesList($afterSaleFilter, 0, -1, ['create_time' => 'ASC']);
        app('log')->info(__FUNCTION__.':'.__LINE__.':[checkCreateInvoice] 售后信息', ['afterSaleInfo' => $afterSaleInfo]);
        if ($afterSaleInfo && $afterSaleInfo['total_count'] > 0) {
            app('log')->info(__FUNCTION__.':'.__LINE__.'[checkCreateInvoice] 售后信息', ['total_count' => $afterSaleInfo['total_count']]);
            // 定义有效的售后状态
            $validAftersalesStatus = [2]; // 0:未处理, 1:处理中, 2:已处理,3 已驳回。已拒绝,4 已撤销。已关闭
            $openAftersalesStatus = [0,1];
            foreach ($afterSaleInfo['list'] as $v) {
                $aftersalesStatus = $v['aftersales_status'] ?? -1;
                // foreach ($v['detail'] as $vv) {
                //     $itemId = $vv['item_id'] ?? 0;   
                // }                
                app('log')->debug(__FUNCTION__.':'.__LINE__.':aftersales_bn:aftersales_status:', ['aftersales_bn' => $v['aftersales_bn'],'aftersales_status' => $aftersalesStatus]);
                if (in_array($aftersalesStatus, $openAftersalesStatus)) {
                    app('log')->info(__FUNCTION__.':'.__LINE__.':[checkCreateInvoice] 售后申请未完成，无法开票',['aftersales_status' => $aftersalesStatus]);
                    throw new ResourceException('您有售后申请未完成，请完成后申请开票。'); 
                }
            }
        }
        
        // 检查重试次数是否超过限制
        if (isset($orderInfo['try_times']) && $orderInfo['try_times'] >= 5) {
            app('log')->info(__FUNCTION__.':'.__LINE__.':[checkCreateInvoice] 发票重试次数超过限制，无法继续开票', ['try_times' => $orderInfo['try_times']]);
            throw new ResourceException('发票重试次数超过限制，无法继续开票');
        }
        
        app('log')->info(__FUNCTION__.':'.__LINE__.':[checkCreateInvoice] end' );

        return true;
    }
    public function retryFailedInvoice($invoice_id,$companyId,$operatorId){
        $filter = [
            'id' => $invoice_id,
            'company_id' => $companyId
        ];

        $invoice = $this->repository->getInfo($filter);
        app('log')->info(__FUNCTION__.':'.__LINE__.':invoice:'.json_encode($invoice));
        if(!$invoice){
            throw new ResourceException('发票不存在');
        }
        if($invoice['invoice_status'] != 'failed'){
            throw new ResourceException('发票状态不能重新开票');
        }
        
        // 获取当前重试次数并加1
        $currentTryTimes = $invoice['try_times'] ?? 0;
        $newTryTimes = $currentTryTimes + 1;
        
        // 记录重试次数更新
        app('log')->info(__FUNCTION__.':'.__LINE__.':重试次数更新', ['old' => $currentTryTimes, 'new' => $newTryTimes]);
        
        // 修改为待开票并更新重试次数
        $result = $this->repository->updateBy(['id' => $invoice_id], ['invoice_status' => 'pending', 'try_times' => $newTryTimes]);
        app('log')->info(__FUNCTION__.':'.__LINE__.':result:'.json_encode($result));
        if($result){
            $this->createInvoiceLog($invoice['id'], 'admin', '重新开票', $invoice,$operatorId);
            return $result;
        }else{
            throw new ResourceException('重新开票失败');
        }
    }
}

