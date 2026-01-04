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

namespace OrdersBundle\Services\Rights;

use OrdersBundle\Entities\Rights;
use OrdersBundle\Entities\RightsLog;
use OrdersBundle\Entities\RightsTransferLogs;
use OrdersBundle\Interfaces\RightsInterface;

use CompanysBundle\Services\Shops\WxShopsService;

use OrdersBundle\Jobs\ConsumeRightsSendSmsNotice;

use OrdersBundle\Repositories\RightsTransferLogsRepository;
use OrdersBundle\Traits\GetUserIdByMobileTrait;

class TimesCardService implements RightsInterface
{
    use GetUserIdByMobileTrait;

    /** @var rightsRepository */
    private $rightsRepository;
    /** @var rightsTransferLogsRepository */
    private $rightsTransferLogsRepository;

    /**
     * TimesCardService 构造函数.
     */
    public function __construct()
    {
        $this->rightsRepository = app('registry')->getManager('default')->getRepository(Rights::class);
        $this->rightsTransferLogsRepository = app('registry')->getManager('default')->getRepository(RightsTransferLogs::class);
    }

    /**
     * 添加次卡权益
     *
     * @param array 提交的门店数据
     * @return array
     */
    public function addRights($companyId, array $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data = [
                'company_id' => $params['company_id'],
                'user_id' => $params['user_id'],
                'rights_name' => $params['rights_name'],
                'can_reservation' => $params['can_reservation'],
                'rights_subname' => $params['rights_subname'],
                'total_num' => $params['total_num'],
                'total_consum_num' => $params['total_consum_num'],
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'order_id' => $params['order_id'],
                'label_infos' => $params['label_infos'],
                'rights_from' => $params['rights_from'],
                'operator_desc' => isset($params['operator_desc']) ? $params['operator_desc'] : "",
                'mobile' => $params['mobile'],
                'status' => 'valid',
                'is_not_limit_num' => $params['is_not_limit_num'],
            ];
            $rightsResult = $this->rightsRepository->create($data);

            $conn->commit();
            return $rightsResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getRightsLogList(array $filter, $page, $pageSize, $orderBy = ['created' => 'DESC'])
    {
        $rightsLogRepository = app('registry')->getManager('default')->getRepository(RightsLog::class);
        $result = $rightsLogRepository->list($filter, $orderBy, $pageSize, $page);
        $shopService = new WxShopsService();
        if ($result['list']) {
            try {
                foreach ($result['list'] as $key => $value) {
                    $shopInfo = $shopService->getShopInfoByShopId($value['shop_id']);
                    $result['list'][$key]['store_name'] = isset($shopInfo['store_name']) ? $shopInfo['store_name'] : '';
                }
            } catch (\Exception $e) {
                $result['list'][$key]['store_name'] = '';
            }
        }

        return $result;
    }

    public function countRightsLogNum($filter)
    {
        $rightsLogRepository = app('registry')->getManager('default')->getRepository(RightsLog::class);
        return $rightsLogRepository->countLogNum($filter);
    }

    /**
     * 消耗次卡
     *
     * @param array filter
     * @return array
     */
    public function consumeRights($rightsId, array $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $rightsData = $this->rightsRepository->update($rightsId, $params, 'consume');

            $rightsLogRepository = app('registry')->getManager('default')->getRepository(RightsLog::class);
            $rightsLogData = [
                'rights_id' => $rightsData['rights_id'],
                'company_id' => $rightsData['company_id'],
                'rights_name' => $rightsData['rights_name'],
                'rights_subname' => $rightsData['rights_subname'],
                'shop_id' => $params['shop_id'] ?? 0,
                'user_id' => $rightsData['user_id'],
                'consum_num' => $params['consum_num'],
                'attendant' => $params['attendant'],
                'salesperson_mobile' => $params['salesperson_mobile'],
            ];
            $rightsLogRepository->create($rightsLogData);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        try {
            $data = [
                'status' => 'success',
                'rights_id' => $rightsData['rights_id'],
                'user_id' => $rightsData['user_id'],
                'consum_num' => $params['consum_num'],
            ];
            app('websocket_client')->driver('rightsmsg')->send($data);
        } catch (\Exception $e) {
            app('log')->debug('websocket rightsmsg service Error:'.$e->getMessage());
        }
        if ($rightsData['is_not_limit_num'] == 1) {
            $job = (new ConsumeRightsSendSmsNotice($params['shop_id'] ?? 0, $params['consum_num'], $rightsData))->onQueue('sms');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }

        return true;
    }

    /**
     * 冻结次卡
     *
     * @param array filter
     */
    public function freezeRights($companyId, array $params)
    {
    }

    public function updateRights($rightsId, $params)
    {
        return $rightsData = $this->rightsRepository->update($rightsId, $params);
    }

    /**
     * @param $rightsId
     * @param $params
     * @return mixed
     */
    public function transferRights($rightsId, $params)
    {
        $data = [
            'rights_id' => $rightsId,
            'user_id' => $params['user_id'],
            'transfer_user_id' => $params['transfer_user_id'],
            'mobile' => $params['mobile'],
            'transfer_mobile' => $params['transfer_mobile'],
            'company_id' => $params['company_id'],
            'remark' => $params['remark'],
        ];
        $this->rightsTransferLogsRepository->create($data);
        $updatedata = [
            'company_id' => $params['company_id'],
            'mobile' => $params['transfer_mobile'],
            'user_id' => $params['transfer_user_id']
        ];
        return $rightsData = $this->rightsRepository->update($rightsId, $updatedata);
    }

    /**
     * @param $rightsId
     * @param $params
     * @return mixed
     */
    public function transferRightsLog($filter, $page, $pageSize, $orderBy = ['created' => 'desc'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 1000) ? 1000 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 20 : $pageSize;
        $rightsLogList = $this->rightsTransferLogsRepository->lists($filter, $orderBy, $pageSize, $page);
        $rightsId = [];
        foreach ($rightsLogList['list'] as $v) {
            $rightsId[] = $v['rights_id'];
        }
        $rightsList = $this->getRightsList(['rights_id' => $rightsId]);
        $rightsNewList = [];
        foreach ($rightsList['list'] as $v) {
            $rightsNewList[$v['rights_id']] = $v['rights_name'];
        }
        foreach ($rightsLogList['list'] as &$v) {
            $v['rights_name'] = $rightsNewList[$v['rights_id']];
        }
        return $rightsLogList;
    }

    /**
     * 获取次卡权益详情
     *
     * @param array filter
     * @return array
     */
    public function getRightsDetail($rightsId)
    {
        $itemsInfo = $this->rightsRepository->get($rightsId);
        if ($itemsInfo['end_time'] && $itemsInfo['end_time'] < time()) {
            $itemsInfo['is_valid'] = false;
        } else {
            $itemsInfo['is_valid'] = true;
        }

        if ($itemsInfo['is_valid'] && $itemsInfo['is_not_limit_num'] == 2) {
            if ($itemsInfo['total_num'] > $itemsInfo['total_consum_num']) {
                $itemsInfo['is_valid'] = true;
            } else {
                $itemsInfo['is_valid'] = false;
            }
            $itemsInfo['total_surplus_num'] = $itemsInfo['total_num'] - $itemsInfo['total_consum_num'];
        }

        if ($itemsInfo['is_not_limit_num'] == 1) {
            $itemsInfo['total_surplus_num'] = -1;
        }
        return $itemsInfo;
    }

    public function countRights($filter)
    {
        return $this->rightsRepository->count($filter);
    }

    /**
     *获取门店信息
     *
     * @param array filter
     * @return array
     */
    public function getRightsList(array $filter, $page = 1, $pageSize = 100, $orderBy = ['end_time' => 'ASC'])
    {
        //$filter = $this->checkMobile($filter);
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 1000) ? 1000 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 20 : $pageSize;
        $rightsList = $this->rightsRepository->list($filter, $orderBy, $pageSize, $page);
        if ($rightsList) {
            foreach ($rightsList['list'] as &$v) {
                if ($v['end_time'] && $v['end_time'] < time()) {
                    $v['is_valid'] = false;
                } else {
                    $v['is_valid'] = true;
                }

                if ($v['is_valid'] && $v['is_not_limit_num'] == 2) {
                    if ($v['total_num'] > $v['total_consum_num']) {
                        $v['is_valid'] = true;
                    } else {
                        $v['is_valid'] = false;
                    }
                    $v['total_surplus_num'] = $v['total_num'] - $v['total_consum_num'];
                }

                if ($v['is_not_limit_num'] == 1) {
                    $v['total_surplus_num'] = -1;
                }
            }
        }

        return $rightsList;
    }

    public function getRightsByCode($code)
    {
        $rightsId = app('redis')->connection('wechat')->get('timescardcode:' . $code);
        return $rightsId;
    }

    public function getRightsCode($rightsId)
    {
        $code = $this->genId(16);
        app('redis')->connection('wechat')->setex('timescardcode:' . $code, 60, $rightsId);

        $dns1d = app('DNS1D')->getBarcodePNG($code, "C93", 1, 70);
        $dns2d = app('DNS2D')->getBarcodePNG($code, "QRCODE", 120, 120);

        $result = [
            'barcode_url' => 'data:image/jpg;base64,' . $dns1d,
            'qrcode_url' => 'data:image/jpg;base64,' . $dns2d,
            'code' => $code,
        ];

        return $result;
    }

    private function genId($length = 8, $prefix = '', $suffix = '')
    {
        // $uppercase    = ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M'];
        $numbers = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $characters = [];
        $coupon = '';
        // $characters = array_merge($numbers, $uppercase);
        $characters = $numbers;

        for ($i = 0; $i < $length; $i++) {
            $coupon .= $characters[mt_rand(0, count($characters) - 1)];
        }
        return $prefix . $coupon . $suffix;
    }

    // 检查权益是否可用
    public function checkRightsValid($rightsId, $userId, $companyId, $useNum, $useTime)
    {
        //改function没有其他地方调用，暂时只返回true
        return true;
        /*    $itemsInfo = $this->rightsRepository->get($rightsId);
            if (!$itemsInfo) {
                return false;
            }
            if ($userId != $itemsInfo['user_id']) {
                return false;
            }
            if ($companyId != $itemsInfo['company_id']) {
                return false;
            }
            if ($itemsInfo['end_time'] && $itemsInfo['end_time'] < $useTime) {
                return false;
            }
            $canUseNum = ($itemsInfo['is_not_limit_num'] == 2) ? ($itemsInfo['total_num'] - $itemsInfo['total_consum_num']) : 0;
            if ($itemsInfo['is_not_limit_num'] == 2 && $canUseNum <= 0) {
                return false;
            }
            if ($canUseNum < $useNum) {
                return false;
            }
            return $canUseNum;
         */
    }

    //定时修改权益的状态
    public function scheduleUpdateRightStatus()
    {
        try {
            //已过期的权益修改为已过期 expire
            $filter = [
                'end_time|lte' => time(),
            ];
            $this->rightsRepository->updateStatusBy($filter, ['status' => 'expire']);

            //有核销次数限制的权益，核销数量已达标，修改权益为已失效 invalid
            $filter = [
                'is_not_limit_num' => 2,
            ];
            $this->rightsRepository->updateStatusBy($filter, ['status' => 'invalid']);
        } catch (\Exception $e) {
            app('log')->debug('定时修改权益的状态出错:'.$e->getMessage());
        }
        return true;
    }
}
