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

namespace ReservationBundle\Services;

use Dingo\Api\Exception\ResourceException;
use ReservationBundle\Services\ResourceLevelManagementService as ResourceLevelService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use ReservationBundle\Entities\ReservationRecord;
use ReservationBundle\Entities\ResourceLevel;
use ReservationBundle\Entities\ResourceLevelRelService;
use ReservationBundle\Services\WorkShift\WorkShiftService;

//以下为权益数量消耗与回滚
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;

use ReservationBundle\Jobs\ReservationSendSmsNotice;
use PromotionsBundle\Services\SmsManagerService;

use ReservationBundle\Jobs\ReservationExpireCancel;

use ReservationBundle\Events\ReservationFinishEvent;

class ReservationManagementService
{
    /**
     * 预约状态变化
     */
    public function updateStatus($status, $filter)
    {
        //获取预约配置信息
        $settingData = $this->__getReservationSetting($filter['company_id']);
        $nowtime = time();
        if (isset($settingData['cancelMinute'])) {
            $nowtime = time() - (60 * $settingData['cancelMinute']);
        }

        $reservationRecord = app('registry')->getManager('default')->getRepository(ReservationRecord::class);
        $record = $reservationRecord->get($filter);
        $oldStatus = $record->getStatus();
        if ($oldStatus == "success") {
            $toShopTime = $record->getToShopTime();
            if ($status == 'cancel') {
                if ($toShopTime > $nowtime) {
                    $status = 'cancel';
                } else {
                    $status = 'not_to_shop';
                }
            } else {
                $status = "to_the_shop";
            }
            return $reservationRecord->updateStatus($status, $filter);
        }
        return true;
    }

    /**
     * 获取某门店一天的时间切割
     *
     * @param company_id int
     * @param shop_id int
     * @param date int 时间戳
     *
     * @return array
     */
    public function getTimePeriod($companyId, $shopId, $dayDate, $labelId = null, $resourceLevelId = null)
    {
        if (!$shopId || $shopId == 'undefined') {
            return array();
        }

        //获取预约配置信息
        $settingData = $this->__getReservationSetting($companyId);

        if (!$settingData) {
            return array();
        }
        $timeInterval = $settingData['timeInterval'];
        $minLimitHour = $settingData['minLimitHour'];

        //获取门店营业时间
        $shopsService = new ShopsService(new WxShopsService());
        $storeData = $shopsService->getShopsDetail($shopId);
        if (!$storeData) {
            return array();
        }
        if (!isset($storeData['company_id']) || $storeData['company_id'] != $companyId) {
            return array();
        }

        $storeOpenTime = $storeData['hour'];
        if (!$storeOpenTime) {
            return array();
        }

        //获取指定门店每天的时间切片
        list($beginTime, $endTime) = explode('-', $storeOpenTime);

        //获取一天的时间间隔
        $DateService = new DateService();
        $timelist = $DateService->getTimePeriod(strtotime($dayDate), $beginTime, $endTime, $timeInterval);

        if (!$labelId) {
            $result['timeData'] = $timelist;
            $result['resourceName'] = $settingData['resourceName'];
            $result['maxLimitDay'] = $settingData['maxLimitDay'];
            $result['minLimitHour'] = $settingData['minLimitHour'];
            return $result;
        }

        //获取指定门店的指定日期资源位排班数据
        $WorkShiftService = new WorkShiftManageService(new WorkShiftService());
        $workShiftFilter = [
            'company_id' => $companyId,
            'shop_id' => $shopId,
            'work_date' => strtotime($dayDate),
        ];
        if ($resourceLevelId) {
            $workShiftFilter['resource_level_id'] = $resourceLevelId;
        }
        $shiftList = [];
        $workShiftList = $WorkShiftService->getList($workShiftFilter);
        foreach ($workShiftList as $key => $value) {
            $weekName = strtolower(date('l', $workShiftFilter['work_date']));
            if (isset($value[$weekName])) {
                $shift = $value[$weekName];
                if ($shift['typeId'] != '-1') {
                    $shiftList[$key] = $shift;
                }
            }
        }

        //获取指定服务下资源位
        $resourceLevelService = new ResourceLevelService();
        $resourceLevel = $resourceLevelService->getListByMaterial($companyId, $labelId, $shopId);
        if (!$resourceLevel) {
            return array();
        }

        //被排班可用的资源位
        $availableArr = array();

        foreach ($resourceLevel as $value) {
            if (isset($shiftList[$value['resourceLevelId']])) {
                $availableLevel = $shiftList[$value['resourceLevelId']];
                $availableLevel = array_merge($availableLevel, $value);
                $availableArr[] = $availableLevel;
            }
        }

        if (!$availableArr) {
            return array();
        }

        $second = $minLimitHour * 60;
        $now = time();
        $after = $now + $second;
        foreach ($timelist as $value) {
            $hourM = date('H:i', $value['begin']);
            $postDate = $dayDate.' '. $hourM;
            $postDay = strtotime($postDate);
            $timeData[] = [
                'begin_time' => date('H:i', $value['begin']),
                'end_time' => date('H:i', $value['end']),
                'status' => ($postDay >= $after) ? 1 : 0,
            ];
        }

        foreach ($timeData as $key => $value) {
            if ($value['status'] == 1) {
                $status = 0;
                foreach ($availableArr as $val) {
                    if ($val['beginTime'] <= $value['begin_time'] && $val['endTime'] > $value['begin_time']) {
                        $timeData[$key]['level'][$val['resourceLevelId']] = $val;
                        $status = 1;
                    }
                }
                $timeData[$key]['status'] = $status;
            }
        }

        //获取某一天的预约记录
        $filter = [
            'company_id' => $companyId,
            'shop_id' => $shopId,
            'agreement_date' => strtotime($dayDate),
        ];
        $reservationRecord = $this->getReservationRecord($filter);
        $level = array();
        if ($reservationRecord) {
            foreach ($timeData as $key => $value) {
                if (isset($value['level'])) {
                    $level = $value['level'];
                    foreach ($reservationRecord as $val) {
                        if ($val['beginTime'] == $value['begin_time'] && isset($level[$val['resourceLevelId']])) {
                            unset($level[$val['resourceLevelId']]);
                        }
                    }
                    if (count($level) == 0 && $timeData[$key]['status']) {
                        $timeData[$key]['status'] = 0;
                    }
                } else {
                    $timeData[$key]['status'] = 0;
                }
            }
        }
        return $timeData;
    }

    /**
     * 获取可预约的日期
     *
     * @return array
     */
    public function getReservationDate($companyId, $endDate = null)
    {
        $oneDay = 24 * 60 * 60;
        $settingData = $this->__getReservationSetting($companyId);
        if (!$settingData) {
            return array();
        }
        $maxLimitDay = $settingData['maxLimitDay'];
        $nowDate = time();
        if ($endDate && $endDate > $nowDate) {
            $endDay = ($endDate - $nowDate) / $oneDay;
            $maxLimitDay = ($endDay < $maxLimitDay) ? $endDay : $maxLimitDay;
        }

        //$maxLimitDay += 1;
        for ($i = 0; $i < $maxLimitDay; $i++) {
            $dateDayData[] = $nowDate + $i * $oneDay;
        }
        return $dateDayData;
    }

    /**
     * 获取指定门店的资源位的预约情况
     *
     * @param companyId
     * @param shopId 指定门店id
     * @param dateDay 具体某一天日期
     *
     * @return array
     */
    public function getReservationList($companyId, $shopId, $dateDay, $postdata = array())
    {
        // Core: RWNTaG9wWA==
        $result = [
            'list' => [],
            'total_count' => 0,
        ];
        $page = 1;
        $pageSize = 50;
        $settingData = $this->__getReservationSetting($companyId);
        if (!$settingData) {
            return $result;
        }
        if ($postdata) {
            if (isset($postdata['page'])) {
                $page = $postdata['page'];
            }
            if (isset($postdata['pageSize'])) {
                $pageSize = $postdata['pageSize'];
            }
        }
        if ($settingData['reservationMode'] == 1) {
            //获取所有资源位
            $filter = [
                'company_id' => $companyId,
                'shop_id' => $shopId,
                'status' => 'active',
            ];
            $resourceLevelService = app('registry')->getManager('default')->getRepository(ResourceLevel::class);
            $list = $resourceLevelService->getList($filter, $pageSize, $page);
            $count = $resourceLevelService->getCount($filter);
        }

        //获取某一天指定门店的预约情况
        $filter = [
            'company_id' => $companyId,
            'shop_id' => $shopId,
            'agreement_date' => $dateDay,
            'status' => ['system','success','not_to_shop','to_the_shop'],
        ];
        $reservationRecordService = app('registry')->getManager('default')->getRepository(ReservationRecord::class);
        $recordData = $reservationRecordService->getList($filter);
        $recordCount = $reservationRecordService->getCount($filter);

        if (!$recordData && !$list) {
            return $result;
        } elseif (!$list && $recordData) {
            $result = [
                'list' => $recordData,
                'total_count' => $recordCount,
            ];
        } elseif ($list && !$recordData) {
            $result = [
                'list' => $list,
                'total_count' => $count,
            ];
        } elseif ($recordData && $list) {
            foreach ($list as $key => $resource) {
                foreach ($recordData as $k => $record) {
                    if ($record['resourceLevelId'] == $resource['resourceLevelId']) {
                        $list[$key]['record'][] = $record;
                    }
                }
                if (isset($list[$key]['record'])) {
                    $list[$key]['timedata'] = array_column($list[$key]['record'], 'beginTime');
                }
            }
            $result = [
                'list' => $list,
                'total_count' => $count,
            ];
        }

        return $result;
    }

    /**
     * getReservationRecord 获取指定门店指定服务某一天的预约记录
     *
     * @param array filter
     *
     * @return array
     */
    public function getReservationRecord($filter, $page = 1, $pageSize = 100)
    {
        $filter['status'] = ['system','success','not_to_shop','to_the_shop'];
        $reservationRecordService = app('registry')->getManager('default')->getRepository(ReservationRecord::class);
        $recordData = $reservationRecordService->getList($filter, $pageSize, $page);
        return $recordData;
    }

    /**
     * 用户预约
     *
     * @param array $paramsData
     *
     * @param boolean
     */
    public function createReservation($paramsData)
    {
        $settingData = $this->__getReservationSetting($paramsData['company_id']);
        //获取门店营业时间
        $shopsService = new ShopsService(new WxShopsService());
        $storeData = $shopsService->getShopsDetail($paramsData['shop_id']);
        if (!$storeData) {
            throw new ResourceException('您预约的门店不存在');
        }
        $expiredAt = $storeData['expired_at'];
        if (!$expiredAt || $expiredAt <= time()) {
            throw new ResourceException('该门店已经过期无法预约');
        }

        $reservationRecordService = app('registry')->getManager('default')->getRepository(ReservationRecord::class);

        //如果为时间+项目+资源位模式时，需要验证资源位每个时段的预约情况
        if ($settingData['reservationMode'] == 1) {
            //检查该预约是否合法, 并且返回可被预约的资源位id和名称
            $this->__checkReservation($paramsData);
        }
        $reservationRecordData = $reservationRecordService->create($paramsData);
        if ($reservationRecordData) {
            $reservationRecordData['setting_data'] = $settingData;
            $this->finishEvents(['postdata' => $paramsData, 'result' => $reservationRecordData]);
        }

        $smsManagerService = new SmsManagerService($paramsData['company_id']);
        //判断短信模版是否开启
        $templateData = $smsManagerService->getOpenTemplateInfo($paramsData['company_id'], 'reservation_notice');
        if ($templateData) {
            $reservationNoticeJob = (new ReservationSendSmsNotice($reservationRecordData, 'reservation_notice'))->onQueue('sms');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($reservationNoticeJob);

            $this->reservationSendSmsRemind($paramsData['company_id'], $reservationRecordData);
        }

        //$this->reservationExpireCancel($reservationRecordData);
        return true;
    }

    /**
     * 预约成功发送短信通知
     */
    private function reservationSendSmsRemind($companyId, $reservationRecordData)
    {
        $smsManagerService = new SmsManagerService($companyId);
        //判断短信模版是否开启
        $templateData = $smsManagerService->getOpenTemplateInfo($companyId, 'gotoShop_notice');
        if ($templateData) {
            //获取预约配置信息
            $settingData = $this->__getReservationSetting($reservationRecordData['company_id']);
            $smsDelay = $settingData['smsDelay'] ?: 1;

            //到店时间
            $endTime = $reservationRecordData['to_shop_time'] - ($smsDelay * 3600);
            $delay = format_queue_delay($endTime - time());
            if ($delay > 0) {
                $gotoShopNoticeJob = (new ReservationSendSmsNotice($reservationRecordData, 'gotoShop_notice'))->onQueue('sms')->delay($delay);
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoShopNoticeJob);
            }
        }
    }

    /**
     * 预约到期后，取消未到店的预约
     */
    private function reservationExpireCancel($reservationRecordData)
    {
        $endTime = $reservationRecordData['to_shop_time'] + (2 * 3600);
        $delay = format_queue_delay($endTime - time());
        $gotoShopNoticeJob = (new ReservationExpireCancel($reservationRecordData))->delay($delay);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoShopNoticeJob);
    }

    public function checkLimitData($postParams)
    {
        $setting = $this->__getReservationSetting($postParams['company_id']);
        $limitArr = unserialize($setting['reservationNumLimit']);
        if ($limitArr) {
            $toShopDate = strtotime($postParams['date_day']);

            $reservationRecordService = app('registry')->getManager('default')->getRepository(ReservationRecord::class);
            $filter['user_id'] = $postParams['user_id'];
            $filter['label_id'] = $postParams['label_id'];
            $filter['status'] = ['success','to_the_shop','not_to_shop'];

            switch ($limitArr['limit_type']) {
                case "limit_days":
                    $limitDays = $limitArr['limit_days'];
                    $filter['limit_begin_time'] = $toShopDate - (24 * 3600 * $limitDays);
                    $filter['limit_end_time'] = $toShopDate + (24 * 3600 * $limitDays);
                    $LastRecord = $reservationRecordService->getList($filter, 100, 1, ['agreement_date' => 'ASC']);
                    if (!$LastRecord) {
                        break;
                    }
                    $ifReservation = true;
                    foreach ($LastRecord as $value) {
                        if (abs($value['agreementDate'] - $toShopDate) < (24 * 3600 * $limitDays)) {
                            $ifReservation = false;
                        }
                    }
                    if (!$ifReservation) {
                        $noticeDate = array_pop($LastRecord);
                        throw new ResourceException('您已经预约了'.date('Y-m-d', $noticeDate['agreementDate']).'的课程,我们建议2次课程间隔至少'.$limitDays.'天');
                    }
                    break;
                case "limit_nums":
                    $limitNums = $limitArr['limit_nums'];
                    $filter['agreement_date'] = $toShopDate;
                    $count = $reservationRecordService->getCount($filter);

                    if ($limitNums <= $count) {
                        throw new ResourceException("该项目一天的预约次数已达上限，不能再预约");
                    }
                    break;
            }
        }
        return true;
    }

    private function __checkReservation(&$postParams, &$finishParams = [])
    {
        $reservationRecordService = app('registry')->getManager('default')->getRepository(ReservationRecord::class);

        $nowTime = time();
        $toShopTime = strtotime($postParams['date_day'].$postParams['begin_time']);
        if ($nowTime >= $toShopTime) {
            throw new ResourceException('该时段已过期');
        }

        //检测预约的项目是否有效
        $availableIds = [];
        if (isset($postParams['rights_id']) && isset($postParams['label_id'])) {
            $rightsServices = new RightsService(new TimesCardService());
            $rightsDetail = $rightsServices->getRightsDetail($postParams['rights_id']);
            if (!$rightsDetail) {
                throw new ResourceException('您预约的项目已失效');
            }
            $filter = [
                'user_id' => $postParams['user_id'],
                'rights_id' => $postParams['rights_id'],
                'company_id' => $postParams['company_id'],
            ];
            $filter['status'] = ['success','to_the_shop','not_to_shop'];
            $count = $reservationRecordService->getCount($filter);
            if ($count && $rightsDetail['is_not_limit_num'] == 2 && $count >= $rightsDetail['total_num']) {
                throw new ResourceException('该课程的预约次数已达上限');
            }

            //获取资源位与物料的关联信息
            $resourceRelLabelService = app('registry')->getManager('default')->getRepository(ResourceLevelRelService::class);
            $relFilter = [
                'material_id' => $postParams['label_id'],
                'shop_id' => $postParams['shop_id'],
                'company_id' => $postParams['company_id']
            ];
            $relList = $resourceRelLabelService->getList($relFilter);
            if (!$relList) {
                throw new ResourceException('没有可预约的资源');
            }
            //该项目下所有的资源位id
            $allLevelIds = array_unique(array_column($relList, 'resourceLevelId'));

            //获取所有已排班的资源位
            $WorkShiftService = new WorkShiftManageService(new WorkShiftService());
            $shiftFilter = [
                'company_id' => $postParams['company_id'],
                'shop_id' => $postParams['shop_id'],
                'work_date' => strtotime($postParams['date_day']),
                'resource_level_id' => $allLevelIds,
            ];
            $shiftList = $WorkShiftService->getList($shiftFilter);

            foreach ($shiftList as $key => $value) {
                //查看该资源位是否在可预约时间段有排班
                $weekName = strtolower(date('l', $shiftFilter['work_date']));
                if (!isset($value[$weekName])) {
                    unset($shiftList[$key]);
                    continue;
                }
                $value = $value[$weekName];
                if ($value['typeId'] == '-1') {
                    unset($shiftList[$key]);
                    continue;
                }
                if (strtotime(date('Y-m-d', time()).$value['beginTime']) > strtotime(date('Y-m-d', time()).$postParams['begin_time'])
                    || strtotime(date('Y-m-d', time()).$value['endTime']) <= strtotime(date('Y-m-d', time()).$postParams['begin_time'])) {
                    unset($shiftList[$key]);
                    continue;
                }
            }

            if (!$shiftList) {
                throw new ResourceException('没有可预约的资源');
            }

            //被排班的资源位id
            $shiftLevelIds = array_unique(array_column($shiftList, 'resourceLevelId'));

            //获取可用的资源位ids
            $availableIds = array_intersect($allLevelIds, $shiftLevelIds);
        }

        if (isset($postParams['resource_level_id']) && $postParams['resource_level_id']) {
            if (!$availableIds || in_array($postParams['resource_level_id'], $availableIds)) {
                $availableIds = [$postParams['resource_level_id']];
            } else {
                throw new ResourceException('没有可预约的资源');
            }
        }

        if (!$availableIds) {
            throw new ResourceException('没有可预约的资源');
        }

        //获取指定日期指定时段被预约的资源位
        $filter = [
            'company_id' => $postParams['company_id'],
            'shop_id' => $postParams['shop_id'],
            'agreement_date' => strtotime($postParams['date_day']),
            'begin_time' => $postParams['begin_time'],
            'resource_level_id' => $availableIds,
            'status' => ['system','success','not_to_shop','to_the_shop'],
        ];
        $recordData = $reservationRecordService->getList($filter);
        if ($recordData) {
            $resourceLevelIds = array_column($recordData, 'resourceLevelId');
            $diffLevelIds = array_diff($availableIds, $resourceLevelIds);
            if (!$diffLevelIds) {
                throw new ResourceException('没有可预约的资源');
            }
            $availableIds = $diffLevelIds;
        }

        //获取剩余资源位的信息
        $resourceLevelService = app('registry')->getManager('default')->getRepository(ResourceLevel::class);
        $Filters = [
            'resource_level_id' => $availableIds,
            'status' => 'active',
        ];
        $resourceLevel = $resourceLevelService->getList($Filters);
        if (!$resourceLevel) {
            throw new ResourceException('预约失败');
        }
        $randKey = array_rand($resourceLevel);
        $postParams['resource_level_id'] = $resourceLevel[$randKey]['resourceLevelId'];
        $postParams['resource_level_name'] = $resourceLevel[$randKey]['name'];
        return true;
    }

    private function __getReservationSetting($companyId)
    {
        $SettingService = new SettingService();
        $settingFilter['company_id'] = $companyId;
        $settingData = $SettingService->get($settingFilter);
        return $settingData;
    }

    public function getRecordList($filter, $page = 1, $pageSize = 100)
    {
        $result = [
            'list' => [],
            'total_count' => 0
        ];
        $reservationRecordService = app('registry')->getManager('default')->getRepository(ReservationRecord::class);
        $count = $reservationRecordService->getCount($filter);
        if ($count) {
            $list = $reservationRecordService->getList($filter, $page, $pageSize);
            $result = [
                'list' => $list,
                'total_count' => $count,
            ];
        }
        return $result;
    }

    public function getRecordCount($filter)
    {
        $reservationRecordService = app('registry')->getManager('default')->getRepository(ReservationRecord::class);
        return $reservationRecordService->getCount($filter);
    }

    /**
     * 获取指定用户可被预约的权益列表
     *
     * @param filter array 条件
     * @param pageSize int 条数
     * @param page 页码
     *
     * @return array
     */
    public function getCanReservationRightsList($filter, $pageSize, $page)
    {
        $rightsService = new RightsService(new TimesCardService());
        $result = $rightsService->getRightsList($filter, $page, $pageSize);
        if (!isset($result['list']) || !$result['list']) {
            return [
                'list' => []
            ];
        }
        unset($result['total_count']);

        $rightsIds = array_column($result['list'], 'rights_id');

        $newFilter = [
            'company_id' => $filter['company_id'],
            'user_id' => $filter['user_id'],
            'rights_id' => $rightsIds,
            'status' => ['success','not_to_shop','to_the_shop'],
        ];

        $record = $this->getReservationRecord($newFilter);
        foreach ($record as $value) {
            if (isset($list[$value['rightsId']])) {
                $list[$value['rightsId']] += 1;
            } else {
                $list[$value['rightsId']] = 1;
            }
        }

        foreach ($result['list'] as $key => $val) {
            if (isset($list[$val['rights_id']]) && $val['is_valid']) {
                $isNotLimitNum = $val['is_not_limit_num'];
                $surplus = $val['total_num'] - $val['total_consum_num'];
                if ($isNotLimitNum == 2) {
                    if ($surplus == 0 || $val['total_num'] <= $list[$val['rights_id']]) {
                        $val['is_valid'] = false;
                    }
                }
            }

            if ((isset($val['is_valid']) && !$val['is_valid']) || !$val['can_reservation']) {
                unset($result['list'][$key]);
                continue;
            }

            if (isset($val['label_infos']) && $val['can_reservation']) {
                $result['list'][$key]['label_id'] = $val['label_infos'][0]['label_id'];
                $result['list'][$key]['label_name'] = $val['label_infos'][0]['label_name'];
            }
        }
        array_multisort(array_column($result['list'], 'is_valid'), SORT_DESC, $result['list']);
        return $result;
    }

    /**
     * [getLevelCount description]
     * @return [type] [description]
     */
    public function getLevelRecordCount($filter)
    {
        $reservationRecordService = app('registry')->getManager('default')->getRepository(ReservationRecord::class);
        $list = $reservationRecordService->getList($filter);
        $result = [];
        foreach ($list as $value) {
            if (isset($result[$value['resourceLevelId']])) {
                $result[$value['resourceLevelId']] += 1;
            } else {
                $result[$value['resourceLevelId']] = 1;
            }
        }
        return $result;
    }

    public function finishEvents($eventParams)
    {
        event(new ReservationFinishEvent($eventParams));
    }
}
