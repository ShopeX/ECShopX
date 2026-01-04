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

use Dingo\Api\Exception\ResourceException;

use OrdersBundle\Entities\OrdersRelChinaumspayDivision;

use ChinaumsPayBundle\Services\ChinaumsPayDivisionService;

/**
 * 银联商务支付，订单分账、划付
 */
class OrderDivisionService
{
    public $limit = 10000;
    public $ordersRelChinaumspayDivisionRepository;

    public function __construct()
    {
        $this->ordersRelChinaumspayDivisionRepository = app('registry')->getManager('default')->getRepository(OrdersRelChinaumspayDivision::class);
    }

    /**
     * 定时上传划付数据到sftp服务器
     *
     * @param int $companyId
     * @return void
     */
    public function scheduleTransferSftp()
    {
        app('log')->info('划付上传开始');
        // 查询需要划付的数据
        $relDivisionService = new OrdersRelChinaumspayDivisionService();
        $count = $relDivisionService->getNeedTransferCount();
        if ($count == 0) {
            app('log')->info('没有需要划付的数据');
            return true;
        }

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $companys = $criteria->select('company_id')->from('companys')->execute()->fetchAll();
        $divisionService = new ChinaumsPayDivisionService();
        foreach ($companys as $v) {
            // 查询需要划付的店铺数据
            $divisionListData = [];
            $distributorList = $relDivisionService->getNeedTransferDistributorList($v['company_id']);
            foreach ($distributorList as $vv) {
                // 获取店铺下可划付的订单数据
                $orderDivisionList = $relDivisionService->getNeedTransferList($vv['distributor_id']);
                // 组织文件内容，一个店铺一条记录
                $divisionData = $divisionService->formatTransferData($v['company_id'], $vv['distributor_id'], $orderDivisionList);
                if (!$divisionData) {
                    continue;
                }
                $divisionListData[] = $divisionData;
            }
            if (!$divisionListData) {
                app('log')->info('company_id:'.$v['company_id'].',没有需要划付的数据');
                continue;
            }
            $this->doTransferSftp($v['company_id'], $divisionListData, $relDivisionService::STATUS_UPLOADED);
        }
        
        app('log')->info('划付上传结束');
        return true;
    }

    /**
     * 执行上传划付数据到sftp服务器
     * @param  string $companyId 企业ID
     * @param  array $divisionListData 可分账列表
     * @param  string $status            要修改的划付状态
     */
    public function doTransferSftp($companyId, $divisionListData, $status)
    {
        if (!$divisionListData) {
            return false;
        }
        $divisionData = [
            'data' => [],
            'division_ids' => [],
            'order_ids' => [],
        ];
        foreach ($divisionListData as $data) {
            $divisionData['transfer'] = array_merge($divisionData['transfer'], $data['transfer']);
            $divisionData['division'] = array_merge($divisionData['division'], $data['division']);
            $divisionData['division_ids'][] = $data['division_id'];
            $divisionData['order_ids'] = array_merge($divisionData['order_ids'], $data['order_ids']);
        }

        $divisionService = new ChinaumsPayDivisionService();
        
        $localPath = '/chinaumsPayment/'.date('Ymd');
        $remotePath = '/upload/'.date('Ymd');

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $divisionData['local_file_path'] = $localPath;
            $divisionData['remote_file_path'] = $remotePath;
            app('log')->info('divisionData===>'.var_export($divisionData,1));
            // 记录划拨上传日志
            $uploadLog = $divisionService->createUploadLog($companyId, $divisionService::FILE_TYPE_TRANSFER, $divisionData);
            // 将数据保存到本地文件
            $localFile = $localPath.'/'.$uploadLog['file_name'];
            app('log')->info('transfer localFile:'.$localFile);
            app('filesystem')->put($localFile, $uploadLog['file_content']);
            // 上传到sftp服务器
            $divisionService->uploadFile($companyId, $uploadLog['file_name'], $localPath, $remotePath);

            // 记录分账上传日志
            $uploadLog = $divisionService->createUploadLog($companyId, $divisionService::FILE_TYPE_DIVISION, $divisionData);
            // 将数据保存到本地文件
            $localFile = $localPath.'/'.$uploadLog['file_name'];
            app('log')->info('division localFile:'.$localFile);
            app('filesystem')->put($localFile, $uploadLog['file_content']);
            // 上传到sftp服务器
            $divisionService->uploadFile($companyId, $uploadLog['file_name'], $localPath, $remotePath);

            // 修改订单的划付状态
            $this->ordersRelChinaumspayDivisionRepository->updateBy(['order_id' => $divisionData['order_ids']], ['status' => $status]);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            $msg = 'file:'.$e->getFile().',line:'.$e->getLine().',msg:'.$e->getMessage();
            app('log')->info('company_id:'.$companyId.',划付上传失败 ===>'.$msg);
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 定时上传重新提交的划付数据到sftp服务器
     *
     * @param int $companyId
     * @return void
     */
    public function scheduleTransferResubmitSftp()
    {
        app('log')->info('重新提交划付上传开始');
        $divisionService = new ChinaumsPayDivisionService();
        $resumbitCount = $divisionService->getErrorlogResumbitCount();

        if ($resumbitCount == 0) {
            app('log')->info('没有需要重试的划付数据');
            return true;
        }
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $companys = $criteria->select('company_id')->from('companys')->execute()->fetchAll();
        foreach ($companys as $v) {
            $result = $this->doTransferResubmitSftp($v['company_id']);
        }
        app('log')->info('重新提交划付上传结束');
        return true;
    }

    /**
     * 处理划付重新提交的数据到sftp服务器
     * @param  string $companyId 企业ID
     */
    public function doTransferResubmitSftp($companyId)
    {
        $divisionService = new ChinaumsPayDivisionService();

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        
        try {
            // 查询需要重试的划付数据
            $reDivsionData = $divisionService->getReTransferData($companyId);
            if (!$reDivsionData) {
                app('log')->info('company_id:'.$companyId.',没有需要重试的划付数据');
                return false;;
            }
            $localPath = '/chinaumsPayment/'.date('Ymd');
            $remotePath = '/upload/'.date('Ymd');

            $reDivsionData['local_file_path'] = $localPath;
            $reDivsionData['remote_file_path'] = $remotePath;
            app('log')->info('reDivsionData===>'.var_export($reDivsionData,1));
            // 记录上传日志
            $uploadLog = $divisionService->createUploadLog($companyId, $divisionService::FILE_TYPE_TRANSFER, $reDivsionData);
            // 将数据保存到本地文件
            $localFile = $localPath.'/'.$uploadLog['file_name'];
            app('log')->info('localFile:'.$localFile);
            app('filesystem')->put($localFile, $uploadLog['file_content']);
            
            // 上传到sftp服务器
            $divisionService->uploadFile($companyId, $uploadLog['file_name'], $localPath, $remotePath);

            // 记录上传日志
            $uploadLog = $divisionService->createUploadLog($companyId, $divisionService::FILE_TYPE_DIVISION, $reDivsionData);
            // 将数据保存到本地文件
            $localFile = $localPath.'/'.$uploadLog['file_name'];
            app('log')->info('localFile:'.$localFile);
            app('filesystem')->put($localFile, $uploadLog['file_content']);
            
            // 上传到sftp服务器
            $divisionService->uploadFile($companyId, $uploadLog['file_name'], $localPath, $remotePath);
            
            // 修改重试状态
            $divisionService->updateErrorLog(['id' => $reDivsionData['error_log_ids']], ['is_resubmit' => $divisionService::IS_RESUBMIT_SUCC]);

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            $msg = 'file:'.$e->getFile().',line:'.$e->getLine().',msg:'.$e->getMessage();
            app('log')->info('重新提交划付上传失败 ===>'.$msg);
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 定时下载划付回盘数据到本地，并处理回盘结果
     * @param int $companyId
     * @return void
     */
    public function scheduleTransferDownloadSftp()
    {
        app('log')->info('回盘开始');
        $divisionService = new ChinaumsPayDivisionService();
        // 根据需要处理回盘的文件数据，去下载回盘文件
        $filter = [
            'back_status' => $divisionService::BACK_STATUS_NOT,
        ];
        $uploadLogList = $divisionService->getUploadLogList($filter);
        if ($uploadLogList['total_count'] == 0) {
            app('log')->info('没有需要回盘的文件');
            return true;
        }
        foreach ($uploadLogList['list'] as $uploadLog) {
            $this->doTransferDownloadSftp($uploadLog);
        }
        
        app('log')->info('回盘结束');
        return true;
    }

    /**
     * 处理划付回盘数据到本地，并处理回盘结果
     * @param  array $uploadLog 需要回盘的文件记录
     */
    public function doTransferDownloadSftp($uploadLog)
    {
        $divisionService = new ChinaumsPayDivisionService();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $companyId = $uploadLog['company_id'];
            $result = $divisionService->downloadFile($companyId, $uploadLog['file_name'], $uploadLog['local_file_path'], $uploadLog['remote_file_path']);
            // 读取本地文件内容，处理回盘结果
            $divisionService->doFinalTransfer($uploadLog);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            $msg = 'file:'.$e->getFile().',line:'.$e->getLine().',msg:'.$e->getMessage();
            app('log')->info('划付回盘失败 ===>'.$msg);
            throw new ResourceException($e->getMessage());
        }
        
    }


    /**
     * Dynamically call the OrderProfitService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->ordersRelChinaumspayDivisionRepository->$method(...$parameters);
    }
}
