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

namespace ChinaumsPayBundle\Services;

use Dingo\Api\Exception\ResourceException;
use League\Flysystem\Filesystem;

use ChinaumsPayBundle\Entities\ChinaumspayDivision;
use ChinaumsPayBundle\Entities\ChinaumspayDivisionDetail;
use ChinaumsPayBundle\Entities\ChinaumspayDivisionUploadDetail;
use ChinaumsPayBundle\Entities\ChinaumspayDivisionUploadLog;
use ChinaumsPayBundle\Entities\ChinaumspayDivisionErrorLog;

use ChinaumsPayBundle\Services\SftpDataService;

use OrdersBundle\Services\OrdersRelChinaumspayDivisionService;
use OrdersBundle\Traits\GetOrderServiceTrait;

use PaymentBundle\Services\Payments\ChinaumsPayService;
use AftersalesBundle\Services\AftersalesService;

/**
 * 分账/划付
 */
class ChinaumsPayDivisionService
{
    use GetOrderServiceTrait;

    private $divisionRepository;
    private $divisionDetailRepository;
    private $divisionUploadLogRepository;
    private $divisionErrorLogsRepository;

    public $groupNo = '';// 商户集团编号
    // public $groupNo = '151515';
    // public $groupNo = 'YHJLSP';

    // 文件类型 division:分账;transfer:划付
    public const FILE_TYPE_DIVISION = 'division';
    public const FILE_TYPE_TRANSFER = 'transfer';

    // 回盘状态（未处理）
    public const BACK_STATUS_NOT = 0;
    // 回盘状态（进行中）
    public const BACK_STATUS_OGOING = 1;
    // 回盘状态（成功）
    public const BACK_STATUS_SUCCESS = 2;
    // 回盘状态（部分成功）
    public const BACK_STATUS_PART_SUCCESS = 3;
    // 回盘状态（失败）
    public const BACK_STATUS_FAIL = 4;

    // 提交状态 未提交
    public const IS_RESUBMIT_NOT = 0;
    // 提交状态 已提交
    public const IS_RESUBMIT_SUCC = 1;
    // 提交状态 等待执行
    public const IS_RESUBMIT_WAITING = 2;

    public function __construct()
    {
        $this->groupNo = config('ums.group_no');
        $this->divisionRepository = app('registry')->getManager('default')->getRepository(ChinaumspayDivision::class);
        $this->divisionDetailRepository = app('registry')->getManager('default')->getRepository(ChinaumspayDivisionDetail::class);
        $this->divisionUploadDetailRepository = app('registry')->getManager('default')->getRepository(ChinaumspayDivisionUploadDetail::class);
        $this->divisionUploadLogRepository = app('registry')->getManager('default')->getRepository(ChinaumspayDivisionUploadLog::class);
        $this->divisionErrorLogsRepository = app('registry')->getManager('default')->getRepository(ChinaumspayDivisionErrorLog::class);
    }

    /**
     * 划付，上传，组织数据
     * @param  string $companyId         企业ID
     * @param  string $distributorId     店铺ID
     * @param  string $orderDivisionList 可划付的订单列表
     */
    public function formatTransferData($companyId, $distributorId, $orderDivisionList)
    {
        if (!$orderDivisionList) {
            return [];
        }

        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfoById($distributorId);
        if (!$distributorInfo) {
            // throw new ResourceException('无效的店铺');
            return [];
        }
        if (!$distributorInfo['split_ledger_info']) {
            // throw new ResourceException('未设置店铺分账信息');
            return [];
        }

        $splitLedgerInfo = json_decode($distributorInfo['split_ledger_info'], true);

        $chinaumsPayService = new ChinaumsPayService();
        $paymentSetting = $chinaumsPayService->getPaymentSetting($companyId);
        if (!$paymentSetting) {
            // throw new ResourceException('未设置支付信息');
            return [];
        }

        $distributorPaymentSetting = $chinaumsPayService->getPaymentSetting($companyId, 'distributor_'.$distributorId);
        if (!$distributorPaymentSetting) {
            // throw new ResourceException('未设置店铺支付信息');
            return [];
        }

        if ($distributorInfo['dealer_id'] > 0) {
            $dealerPaymentSetting = $chinaumsPayService->getPaymentSetting($companyId, 'dealer_'.$distributorInfo['dealer_id']);
            if (!$dealerPaymentSetting) {
                // throw new ResourceException('未设置经销商支付信息');
                return [];
            }
        }

        // app('log')->info('orderDivisionList===>'.var_export($orderDivisionList,1));
        $uploadTransfer = $uploadDivision = $divisionDetailIds = $orderIds = [];
        $distributorTotalFee = $distributorActualFee = $distributorDivisionFee = $distributorCommissionRateFee = 0;
        $aftersalesService = new AftersalesService();
        $ordersDivisionService = new OrdersRelChinaumspayDivisionService();
        foreach ($orderDivisionList as $key => $orderDivision) {
            // 计算减去已售后商品后的订单总金额
            $appliedTotalRefundFee = $aftersalesService->getOrderAppliedTotalRefundFee($orderDivision['company_id'], $orderDivision['order_id']);
            $actualFee = bcsub($orderDivision['total_fee'], $appliedTotalRefundFee);
            app('log')->info('划付上传 order_id:'.$orderDivision['order_id'].',actualFee:'.$actualFee);
            // 已全额退款，不处理划付
            if ($actualFee <= 0) {
                $ordersDivisionService->updateBy(['id' => $orderDivision['id']], ['status' => $ordersDivisionService::STATUS_SKIP]);
                continue;
            }
            $totalRateFee = bcmul($orderDivision['total_fee'], $paymentSetting['rate'] / 100);
            $refundRateFee = bcmul($appliedTotalRefundFee, $paymentSetting['rate'] / 100);
            $finalRateFee = bcsub($totalRateFee, $refundRateFee);
            $divisionFee = bcsub($actualFee, $finalRateFee);
            app('log')->info('划付上传 totalRateFee:'.$totalRateFee);
            app('log')->info('划付上传 refundRateFee:'.$refundRateFee);
            app('log')->info('划付上传 finalRateFee:'.$finalRateFee);
            app('log')->info('划付上传 divisionFee:'.$divisionFee);
            $divisionDetail = [
                'company_id' => $orderDivision['company_id'],
                'division_id' => 0,
                'order_id' => $orderDivision['order_id'],
                'distributor_id' => $orderDivision['distributor_id'],
                'total_fee' => $orderDivision['total_fee'],
                'actual_fee' => $actualFee,// 订单实际金额，订单总金额-售后总金额
                'commission_rate' => $paymentSetting['rate'],
                'commission_rate_fee' => $finalRateFee,// 订单最终收单手续费
                'division_fee' => $divisionFee,// 订单的划付金额
            ];
            // app('log')->info('divisionDetail====>'.var_export($divisionDetail,1));
            $divisionDetailResult = $this->divisionDetailRepository->create($divisionDetail);
            $divisionDetailIds[] = $divisionDetailResult['id'];
            $orderIds[] = $orderDivision['order_id'];
            $distributorTotalFee = bcadd($distributorTotalFee, $orderDivision['total_fee']);
            $distributorActualFee = bcadd($distributorActualFee, $actualFee);
            $distributorCommissionRateFee = bcadd($distributorCommissionRateFee, $finalRateFee);
            $distributorDivisionFee = bcadd($distributorDivisionFee, $divisionFee);
        }
        if (!$divisionDetailIds) {
            return [];
        }

        // 插入分账流水
        $division = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'total_fee' => $distributorTotalFee,
            'actual_fee' => $distributorActualFee,
            'commission_rate_fee' => $distributorCommissionRateFee,
            'division_fee' => $distributorDivisionFee,
        ];
        $divisionResult = $this->divisionRepository->create($division);
        $this->divisionDetailRepository->updateBy(['id' => $divisionDetailIds], ['division_id' => $divisionResult['id']]);

        if ($distributorInfo['dealer_id'] == 0) {//未关联经销商两方分账
            $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
            $headquartersFee = bcmul($distributorDivisionFee, $headquartersProportion);
            $distributorFee = bcsub($distributorDivisionFee, $headquartersFee);

            $uploadDivision[] = [
                'division_id' => $divisionResult['id'],
                'enterpriseid' => $paymentSetting['enterpriseid'],
                'type' => 0,
                'fee' => $headquartersFee,
                'payee' => '平台',
                'bank_name' => $paymentSetting['bank_name'],
                'bank_code' => $paymentSetting['bank_code'],
                'bank_account' => $paymentSetting['bank_account'],
                'distributor_id' => $distributorId,
            ];

            $uploadTransfer[] = [
                'division_id' => $divisionResult['id'],
                'enterpriseid' => $distributorPaymentSetting['enterpriseid'],
                'type' => 0,
                'fee' => $distributorFee,
                'distributor_id' => $distributorId,
            ];
        } else {//关联经销商三方分账
            $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
            $dealerProportion = $splitLedgerInfo['dealer_proportion'] / 100;
            $headquartersFee = bcmul($distributorDivisionFee, $headquartersProportion);
            $dealerFee = bcmul($distributorDivisionFee, $dealerProportion);
            $distributorFee = bcsub($distributorDivisionFee, $headquartersFee + $dealerFee);

            $uploadDivision[] = [
                'division_id' => $divisionResult['id'],
                'enterpriseid' => $paymentSetting['enterpriseid'],
                'type' => 0,
                'fee' => $headquartersFee,
                'payee' => '平台',
                'bank_name' => $paymentSetting['bank_name'],
                'bank_code' => $paymentSetting['bank_code'],
                'bank_account' => $paymentSetting['bank_account'],
                'distributor_id' => $distributorId,
            ];

            $uploadTransfer[] = [
                'division_id' => $divisionResult['id'],
                'enterpriseid' => $distributorPaymentSetting['enterpriseid'],
                'type' => 0,
                'fee' => $distributorFee,
                'distributor_id' => $distributorId,
            ];

            $uploadTransfer[] = [
                'division_id' => $divisionResult['id'],
                'enterpriseid' => $dealerPaymentSetting['enterpriseid'],
                'type' => 0,
                'fee' => $distributorFee,
                'distributor_id' => $distributorId,
            ];
        }

        $result = [
            'division' => $uploadDivision,
            'transfer' => $uploadTransfer,
            'division_id' => $divisionResult['id'],
            'order_ids' => $orderIds,
        ];
        // app('log')->info('formatTransferData result===>'.var_export($result,1));
        return $result;
    }

    /**
     * 获取划付失败，重试的数据
     */
    public function getReTransferData($companyId)
    {
        // CRC: 2367340174
        $filter = [
            'company_id' => $companyId,
            'is_resubmit' => self::IS_RESUBMIT_WAITING,
        ];
        $errorLogList = $this->divisionErrorLogsRepository->getLists($filter);
        app('log')->info('getReTransferData errorLogList result===>'.var_export($errorLogList,1));
        if (!$errorLogList) {
            app('log')->info('没有需要重新提交的失败记录');
            return [];
        }

        $uploadDetailIds = array_column($errorLogList, 'upload_detail_id');
        $uploadDetails = $this->divisionUploadDetailRepository->getLists(['id' => $uploadDetailIds]);
        $uploadDivision = $uploadTransfer = $orderIds = [];
        foreach ($uploadDetails as $value) {
            $detail = json_decode($value['detail'], true);
            $detail['upload_detail_id'] = $value['id'];

            if ($value['file_type'] == self::FILE_TYPE_DIVISION) {
                $uploadDivision[] = $detail;
            }

            if ($value['file_type'] == self::FILE_TYPE_TRANSFER) {
                $uploadTransfer[] = $detail;
            }
        }

        $result = [
            'division' => $uploadDivision,
            'transfer' => $uploadTransfer,
            'division_ids' => array_column($errorLogList, 'division_id'),
            'error_log_ids' => array_column($errorLogList, 'id'),
        ];
        // app('log')->info('getReTransferData result===>'.var_export($result,1));
        return $result;
    }

    /**
     * 创建上传日志
     * @param  string $fileType    文件类型
     * @param  array $divisionData 上传数据
     */
    public function createUploadLog($companyId, $fileType, $divisionData)
    {
        // 划付---上传 文件格式  02_集团号_yyyyMMddHHmmss.txt
        if ($fileType == self::FILE_TYPE_DIVISION) {
            $fileName = '04_' . $this->groupNo . '_'. date('YmdHis') . '.txt';
        } else {
            $fileName = '02_' . $this->groupNo . '_'. date('YmdHis') . '.txt';
        }
        $firstColArr = [$this->groupNo, count($divisionData[$fileType])];
        $fileContent = implode($firstColArr, '|') . "\n";
        foreach ($divisionData[$fileType] as $key => $division) {
            if (isset($division['upload_detail_id'])) {
                $data = $this->divisionUploadDetailRepository->getInfo(['id' => $division['upload_detail_id']]);
                $data = $this->divisionUploadDetailRepository->updateBy(['id' => $data['id']], ['times' => $data['times'] + 1, 'back_status' => self::BACK_STATUS_NOT]);
                unset($division['upload_detail_id']);
            } else {
                $data = [
                    'company_id' => $companyId,
                    'division_id' => $division['division_id'],
                    'distributor_id' => $division['distributor_id'],
                    'file_type' => $fileType,
                    'detail' => json_encode($division),
                    'times' => 1,
                    'back_status' => self::BACK_STATUS_NOT,
                ];
                $data = $this->divisionUploadDetailRepository->create($data);
                unset($division['distributor_id']);
            }

            //防止指令ID重复
            $division['division_id'] = $division['division_id'].'0'.$data['id'].'0'.$data['times'];
            $division = array_values($division);
            $fileContent .= implode($division, '|') . "\n";
        }

        $uploadLog = [
            'company_id' => $companyId,
            'file_type' => $fileType,
            'local_file_path' => $divisionData['local_file_path'],
            'remote_file_path' => $divisionData['remote_file_path'],
            'file_name' => $fileName,
            'file_content' => $fileContent,
            'back_status' => self::BACK_STATUS_NOT,
            'division_id' => implode(',', $divisionData['division_ids']),
        ];
        $logResult = $this->divisionUploadLogRepository->create($uploadLog);

        return $uploadLog;

    }

    /**
     * 更新上传日志
     * @param  array $filter
     * @param  array $data
     */
    public function updateUploadLog($filter, $data)
    {
        return $this->divisionUploadLogRepository->updateBy($filter, $data);
    }

    /**
     * 获取上传日志列表
     */
    public function getUploadLogList($filter, $cols='*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        return $this->divisionUploadLogRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
    }

    /**
     * 下载文件
     * @param  string $companyId 企业Id
     * @param  string $fileName  文件名
     * @param  string $remote    远程文件路径
     * @return [type]            [description]
     */
    public function downloadFile($companyId, $fileName, $localPath, $remotePath)
    {
        if (!is_dir(storage_path($localPath))) {
            mkdir(storage_path($localPath), 0777, true);
        }
        $config = config('ums.sftp');
        $prefix = 'final_';
        // $prefix = 'first_';
        $fileName = $prefix.$fileName.'.ret';
        $local = $localPath.'/'.$fileName;
        
        $local = storage_path($local);
        $remote = $remotePath.'/'.$fileName;
        $sftp = new SftpDataService($config);
        $sftp->downftp($remote, $local);
        
        $signService = new DivisionSignService($companyId, $sftp);
        return $signService->verifySignFile($local, $remote);
    }

    /**
     * 上传文件到远程服务器
     * @param  string $companyId 企业ID
     * @param  string $fileName  文件名
     * @return [type]            [description]
     */
    public function uploadFile($companyId, $fileName, $localPath, $remotePath)
    {
        if (!is_dir(storage_path($localPath))) {
            mkdir(storage_path($localPath), 0777, true);
        }
        $config = config('ums.sftp');
        // 上传文件
        $sftp = new SftpDataService($config);
        $localFile = $localPath.'/'.$fileName;
        $local = storage_path($localFile);
        $remote = $remotePath.'/'.$fileName;
        $sftp->ssh2_sftp_mchkdir($remotePath);
        // 验证上传签名
        $signService = new DivisionSignService($companyId, $sftp);
        $signService->uploadSignFile($localFile, $remote);

        return $sftp->upftp($local, $remote);
    }

    /**
     * 处理划付日终回盘
     * @param  array $uploadLog 上传日志
     */
    public function doFinalTransfer($uploadLog)
    {
        // $local = $uploadLog['local_file_path'].'/'.'first_'.$uploadLog['file_name'].'.ret';
        $local = $uploadLog['local_file_path'].'/'.'final_'.$uploadLog['file_name'].'.ret';
        app('log')->info('日终回盘文件处理'.$local);
        if (!app('filesystem')->exists($local)) {
            app('log')->info('日终回盘文件，未查询到日终回盘文件');
            return true;
        }

        $local = storage_path($local);
        $content = file_get_contents($local);
        $data = $this->formateDownloadContent($content, $status);
        // 整个文件失败，记录失败，将订单关联表的相关订单需改为未划付，等待下一次上传
        if ($status == 'file_error') {
            $this->doFileError($uploadLog, $data);
            app('log')->info('日终回盘文件失败，原因：'.$data);
            return true;
        }
        if (empty($data)) {
            app('log')->info('日终回盘文件，没有需要处理的数据 content:'.$content);
            return true;
        }
        // 处理回盘数据结果
        foreach ($data['data'] as $key => $value) {
            $updateData = [
                'backsucc_fee' => $value['final_fee'],
                'rate_fee' => $value['rate_fee'],
                'back_status' => $this->getBackStatus($value['status']),
                'back_status_msg' => $value['status_msg'],
                'chinaumspay_id' => $value['chinaumspay_id'],
            ];
            list(, $id) = $this->parseRetId($value['id']);
            $detailResult = $this->divisionUploadDetailRepository->updateOneBy(['id' => $id], $updateData);
            // 记录失败日志 失败和处理中
            if ($updateData['back_status'] == self::BACK_STATUS_FAIL || $updateData['back_status'] == self::BACK_STATUS_OGOING) {
                $errorLogsData = [
                    'company_id' => $detailResult['company_id'],
                    'division_id' => $detailResult['division_id'],
                    'upload_detail_id' => $detailResult['id'],
                    'type' => $detailResult['file_type'],
                    'distributor_id' => $detailResult['distributor_id'],
                    'status' => $detailResult['back_status'],
                    'error_desc' => $detailResult['back_status_msg'],
                    'is_resubmit' => self::IS_RESUBMIT_NOT,
                ];
                $this->divisionErrorLogsRepository->create($errorLogsData);
            }
        }
        // 修改上传日志的回盘状态
        $this->divisionUploadLogRepository->updateOneBy(['id' => $uploadLog['id']], ['back_status' => self::UPLOAD_BACK_STATUS_FINISH]);

        return true;

    }

    /**
     * 整文件失败的处理
     * @param  array $uploadLog 上传记录
     * @param  string $errorMsg  错误记录描述
     */
    public function doFileError($uploadLog, $errorMsg)
    {
        $division = explode($uploadLog['file_content'], "\n");
        $divisionIds = $uploadDetailIds = [];
        foreach ($division as $row) {
            list($retId) = explode('|', $row);
            list($divisionIds[], $uploadDetailIds[]) = $this->parseRetId($retId);
        }

        if (!$divisionIds || !$uploadDetailIds) {
            return false;
        }

        $this->divisionUploadDetailRepository->updateBy(['id', $uploadDetailIds], ['back_status' => self::BACK_STATUS_FAIL, 'back_status_msg' => $errorMsg]);
        
        // 修改订单关联数据状态为待处理
        $divisionDetailList = $this->divisionDetailRepository->getLists(['division_id' => $divisionIds]);
        $orderIds = array_column($divisionDetailList, 'order_id');
        $ordersDivisionService = new OrdersRelChinaumspayDivisionService();
        $ordersDivisionService->updateBy(['order_id' => $orderIds], ['status' => $ordersDivisionService::STATUS_READY]);
        return true;
    }

    /**
     * 根据银联商务回盘状态，转化为本地的回盘状态
     * @param  string $backStatus 银联商务的回盘状态
     */
    public function getBackStatus($backStatus)
    {
        // 银联的回盘状态  0 – 失败 1 – 成功 2 – 部分成功 3 – 处理中
        // 自己的回盘状态 0:未处理、1:处理中、2:成功、3:部分成功、4:失败
        $status = [
            '0' => self::BACK_STATUS_FAIL,
            '1' => self::BACK_STATUS_SUCCESS,
            '2' => self::BACK_STATUS_PART_SUCCESS,
            '3' => self::BACK_STATUS_OGOING,
        ];
        return $status[$backStatus] ?? 0;
    }

    /**
     * 根据下载的文件内容，转化格式
     * @param  string $content 回盘文件内容
     * @param  string &$status 处理状态
     */
    public function formateDownloadContent($content, &$status = 'succ')
    {
        $data = [];
        $content_arr = explode("\n", $content);
        $firstCol = $content_arr[0] ?? "";
        if (empty($firstCol)) {
            return $data;
        }
        unset($content_arr[0]);
        // 整个文件失败时
        if ($error_msg = $this->downloadFileError($firstCol)) {
            $status = 'file_error';
            return $error_msg;   
        }
        $data['total'] = explode("|", $firstCol);
        $firstColTitle = $this->firstColTitle();
        $data['total'] = array_combine(array_keys($firstColTitle), $data['total']);
        $dataColTitle = $this->dataColTitle();
        foreach ($content_arr as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $data_value = explode("|", $value);
            unset($data_value[10], $data_value[11]);
            $data['data'][] = array_combine(array_keys($dataColTitle), $data_value);
        }
        return $data;
    }

    /**
     * 下载文件错误（整文件失败）
     * @param  string $error 银联商务回盘失败原因码
     */
    private function downloadFileError($error)
    {
        $errorReason = [
            'VERIFY_FAILED' => '验签失败',
            'GROUPNO_ERROR' => '集团号不一致',
            'TOTAL_INSTRUCTION_NOT_MATCH' => '汇总数与明细条数不一致',
        ];
        return $errorReason[$error] ?? false;
    }

    /**
     * 回盘首条记录（汇总行）的title
     */
    private function firstColTitle()
    {
        $col = [
            'group_no' => '商户集团编号',
            'succ_num' => '成功分账笔数',
            'succ_fee' => '成功分账金额',
            'part_succ_num' => '部分成功分账笔数',
            'part_succ_fee' => '部分成功分账金额',
            'part_fail_fee' => '部分成功划付失败金额',
            'fail_num' => '分账失败笔数',
            'fail_fee' => '失败金额',
            'final_succ_fee' => '实际成功划付金额',// 扣除清算费用后的实际到帐金额
            'rate_fee' => '手续费金额',
            'inprocess_num' => '处理中笔数',
            'inprocess_fee' => '处理中金额',
        ];
        return $col;
    }

    /**
     * 回盘数据title
     */
    private function dataColTitle()
    {
        $col = [
            'id' => '分账指令ID',
            'enterpriseid' => '企业用户号',
            'type' => '分账金额类型',
            'fee' => '分账金额',
            'succ_fee' => '分账成功金额',
            'status' => '分账状态',
            'status_msg' => '分账状态说明',
            'chinaumspay_id' => '银商内部ID',
            'final_fee' => '指令结算金额',// 该条指令实际到账金额，即指令金额减业务处理费
            'rate_fee' => '指令业务处理费',

        ];
        return $col;
    }

    /**
     * 根据银联失败原因码，转化失败描述
     * @param  string $errorDesc 银联失败原因吗
     */
    public function getErrorDesc($errorDesc)
    {
        $errorCode = [
            'COMPANYNO_INVALID' => '企业用户号非法，企业用户号没有维护在当前集团下',
            'ORDER_NOT_EXIST' => '订单编号不存在或找到多条',
            'STL_TYPE_INVALID' => '划付类型非法',
            'AMOUNT_INVALID' => '金额非法',
            'FEE_ERROR' => '订单金额不足以提现',
            'NOT_ENOUGH' => '余额不足',
            'SPLIT_ACCOUNT_INVALID' => '分账方非法',
            'MER_DETAIL_NO_REPEAT' => '指令ID重复',
            'MULTIAPP_ERROR' => '商户多应用信息异常',
            'EXCEED_LIMIT' => '累计分账超限',
            'UNKNOWN_ERROR' => '未知错误',
        ];
        return isset($errorCode[$errorDesc]) ? $errorCode[$errorDesc] : $errorDesc;
    }

    /**
     * 更新失败日志
     * @param  array $filter 
     * @param  array $data   
     */
    public function updateErrorLog($filter, $data)
    {
        return $this->divisionErrorLogsRepository->updateBy($filter, $data);
    }

    /**
     * 获取失败日志详情
     * @param  array $filter 
     */
    public function getErrorlogInfo($filter)
    {
        return $this->divisionErrorLogsRepository->getInfo($filter);
    }

    /**
     * 获取失败日志需要重试的条数
     */
    public function getErrorlogResumbitCount()
    {
        return $this->divisionErrorLogsRepository->count(['is_resubmit' => self::IS_RESUBMIT_WAITING]);
    }

    /**
     * 获取失败列表
     * @param  array  $filter   
     * @param  string  $cols     
     * @param  integer $page     
     * @param  integer $pageSize 
     * @param  array   $orderBy  
     */
    public function getErrorlogList($filter, $cols='*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $errorLogList = $this->divisionErrorLogsRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if ($errorLogList['total_count'] == 0) {
            return $errorLogList;
        }
        foreach ($errorLogList['list'] as $key => $errorLog) {
            $errorLog['error_code'] = $errorLog['error_desc'];
            $errorLog['error_desc'] = $this->getErrorDesc($errorLog['error_desc']);
            $errorLogList['list'][$key] = $errorLog;
        }
        return $errorLogList;
    }

    
    /**
     * 获取分账流水明细列表
     */
    public function getDetailList($filter, $cols='*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        return $this->divisionDetailRepository->lists($filter, $cols, $page, $pageSize, $orderBy);

    }

    /**
     * 获取分账流水明细总数
     */
    public function getDetailCount($filter)
    {
        return $this->divisionDetailRepository->count($filter);

    }

    // 解析回盘文件指令ID
    public function parseRetId($id)
    {
        $length = 0;
        for ($i = strlen($id); $i > 0; $i--) {
            $length++;
            if ($i < strlen($id) && $id[$i] != '0' && $id[$i - 1] == '0') {
                $result[] = substr($id, $i, $length);
                $length = -1;
            }
        }

        $result[] = substr($id, 0, $length + 1);

        return array_reverse($result);
    }

    public function __call($name, $arguments)
    {
        return $this->divisionRepository->$name(...$arguments);
    }
}
