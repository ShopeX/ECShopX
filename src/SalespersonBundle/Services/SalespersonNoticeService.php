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

namespace SalespersonBundle\Services;

use SalespersonBundle\Entities\SalespersonNotice;
use SalespersonBundle\Entities\SalespersonNoticeLog;
use SalespersonBundle\Entities\SalespersonRelNotice;
use SalespersonBundle\Entities\ShopSalesperson;
use SalespersonBundle\Entities\ShopsRelSalesperson;
use DistributionBundle\Entities\Distributor;

class SalespersonNoticeService
{
    private $salespersonNoticeRepository;
    private $salespersonNoticeLogRepository;
    private $salespersonRelNotice;
    private $shopRelSalesperson;
    private $shopSalesperson;
    private $distributorRepository;

    public function __construct()
    {
        $this->salespersonNoticeRepository = app('registry')->getManager('default')->getRepository(SalespersonNotice::class);
        $this->salespersonNoticeLogRepository = app('registry')->getManager('default')->getRepository(SalespersonNoticeLog::class);
        $this->salespersonRelNotice = app('registry')->getManager('default')->getRepository(SalespersonRelNotice::class);
        $this->shopRelSalesperson = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);
        $this->shopSalesperson = app('registry')->getManager('default')->getRepository(ShopSalesperson::class);
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
    }

    /**
     * 添加导购员通知
     * @param $data array
     * @return array
     */
    public function addNotice($data)
    {
        $result = $this->salespersonNoticeRepository->create($data);
        if ($result) {
            return [
                'result' => true
            ];
        } else {
            return [
                'result' => false
            ];
        }
    }

    /**
     * 发送通知消息
     * @param $companyId $int
     * @param $noticeId $int
     * @return array
     */
    public function sendNotice($companyId, $noticeId, $distributorId)
    {
        // ShopEx EcShopX Core Module
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data = [
                'all_distributor' => 0,
                'last_sent_time' => time(),
                'status' => 2
            ];
            if ($distributorId === 'all') {
                $data['all_distributor'] = 1;
                $filter = [
                    'company_id' => $companyId
                ];
                $distributors = $this->distributorRepository->getLists($filter, 'distributor_id');
                $sendId = json_encode(array_column($distributors, 'distributor_id'));
            } else {
                $sendId = $distributorId;
            }
            $data['distributor_id'] = $sendId;

            $filter = [
                'notice_id' => $noticeId,
                'company_id' => $companyId
            ];
            $this->salespersonNoticeRepository->updateBy($filter, $data);

            $distributorIds = json_decode($sendId, true);
            $filter = [
                'notice_id' => $noticeId,
                'company_id' => $companyId
            ];
            $this->salespersonNoticeLogRepository->deleteBy($filter);
            $this->salespersonRelNotice->deleteBy($filter);
            foreach ($distributorIds as $value) {
                $data = [
                    'notice_id' => $noticeId,
                    'distributor_id' => $value,
                    'company_id' => $companyId
                ];
                $this->salespersonNoticeLogRepository->create($data);
            }

            $conn->commit();

            return [
                'status' => true
            ];
        } catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }
    }

    public function getNoticeDetail($filter, $withLog)
    {
        $detail = $this->salespersonNoticeRepository->getInfo($filter);

        if ($withLog) {
            if ($detail['all_distributor'] == 1) {
                $detail['distributors'] = 'all';
            } else {
                if ($detail['distributor_id']) {
                    $filter = [
                        'distributor_id' => json_decode($detail['distributor_id'])
                    ];
                    $field = 'name';
                    $distributors = $this->distributorRepository->getLists($filter, $field);
                    $detail['distributors'] = $distributors;
                } else {
                    $detail['distributors'] = [];
                }
            }
        }
        return $detail;
    }

    /**
     * 修改通知
     * @param $filter
     * @param $data
     * @return mixed
     */
    public function updateNotice($filter, $data)
    {
        return $this->salespersonNoticeRepository->updateBy($filter, $data);
    }

    public function withdrawNotice($companyId, $noticeId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = [
                'company_id' => $companyId,
                'notice_id' => $noticeId
            ];
            $data = [
                'distributor_id' => ',',
                'status' => 3
            ];
            $this->salespersonNoticeRepository->updateBy($filter, $data);
            $this->salespersonNoticeLogRepository->deleteBy($filter);
            $this->salespersonRelNotice->deleteBy($filter);
            $conn->commit();

            return ['stauts' => true];
        } catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }
    }

    /**
     * 获取通知列表
     * @param $filter
     * @return mixed
     */
    public function getNoticeList($filter, $page, $pageSize)
    {
        return $this->salespersonNoticeRepository->lists($filter, '*', $page, $pageSize);
    }

    /**
     * 导购获取通知列表
     * @param $filter
     * @return mixed
     */
    public function salespersonGetNoticeList($filter, $page, $pageSize)
    {
        $salespersonId = $filter['salesperson_id'];
        $noticeFilter = [
            'company_id' => $filter['company_id'],
            'distributor_id' => $filter['distributor_id'],
        ];
        $notices = $this->salespersonNoticeLogRepository->lists($noticeFilter, 'notice_id', $page, $pageSize, ['updated' => 'desc']);
        if ($notices['total_count'] == 0) {
            $returnData['list'] = [];
            $returnData['total_count'] = 0;
            return $returnData;
        }
        $noticeListIds = array_column($notices['list'], 'notice_id');
        $filter = [
            'notice_id' => $noticeListIds,
        ];
        $noticesList = $this->salespersonNoticeRepository->getLists($filter);
        foreach ($noticesList as &$notice) {
            $filter = [
                'salesperson_id' => $salespersonId,
                'notice_id' => $notice['notice_id']
            ];
            $readStatus = $this->salespersonRelNotice->getInfo($filter);
            if ($readStatus) {
                $notice['read_status'] = 1;
            } else {
                $notice['read_status'] = 0;
            }
        }
        unset($notice);

        $returnData['list'] = $noticesList;
        $returnData['total_count'] = $notices['total_count'];

        return $returnData;
    }

    /**
     * 导购员获取通知详情
     * @param $noticeId
     * @param $salespersonId
     * @return array
     */
    public function salespersonGetNoticeDetail($noticeId, $salespersonId, $companyId)
    {
        $filter = [
            'notice_id' => $noticeId,
            'company_id' => $companyId,
            'withdraw' => 0
        ];
        $noticeInfo = $this->salespersonNoticeRepository->getInfo($filter);
        if (!$noticeInfo) {
            return [];
        }
        //设置通知为已读
        $data = [
            'notice_id' => $noticeId,
            'salesperson_id' => $salespersonId,
            'company_id' => $companyId
        ];
        $this->salespersonRelNotice->create($data);

        return $noticeInfo;
    }

    public function getUnreadNum(array $data)
    {
        $filter = [
            'distributor_id' => $data['distributor_id'],
            'company_id' => $data['company_id']
        ];
        $distributorNoticeNum = $this->salespersonNoticeLogRepository->count($filter);
        $filter = [
            'salesperson_id' => $data['salesperson_id'],
            'company_id' => $data['company_id']
        ];
        $salespersonReadNum = $this->salespersonRelNotice->count($filter);

        $count = $distributorNoticeNum - $salespersonReadNum;

        return [
            'count' => $count
        ];
    }
}
