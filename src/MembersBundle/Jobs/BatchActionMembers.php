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

namespace MembersBundle\Jobs;

use EspierBundle\Jobs\Job;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\MemberTagsService;
use PromotionsBundle\Services\PromotionActivity;
use MembersBundle\Services\MemberSmsLogService;
use KaquanBundle\Services\VipGradeOrderService;

class BatchActionMembers extends Job
{
    private $params;
    private $actionType;
    private $filter;
    private $isQueue;

    private $userlist = [];

    public function __construct($params, $actionType, $filter, $isQueue)
    {
        $this->params = $params;
        $this->actionType = $actionType;
        $this->filter = $filter;
        $this->isQueue = $isQueue;
    }

    public function handle()
    {
        // ShopEx EcShopX Core Module
        $redisKey = '';
        $actionType = $this->actionType;
        $filter = $this->filter;
        $page = $filter['page'] ?? 1;
        $pageSize = $filter['pageSize'] ?? 100;
        unset($filter['page'], $filter['pageSize']);
        if (isset($filter['redisKey'])) {
            $redisKey = $filter['redisKey'];
        }
        unset($filter['redisKey']);
        if (!$redisKey) {
            return $this->batchHandel($filter, $page, $pageSize, $actionType);
        }
        $total = app('redis')->scard($redisKey);
        if (!$total) {
            return true;
        }
        $w = 0;
        $nfilter['company_id'] = $filter['company_id'];
        $userIds = [];
        for ($i = 0; $i < $total; $i++) {
            $userIds[] = app('redis')->spop($redisKey);
            if ($w < 100) {
                $w++;
            } else {
                $w = 0;
                app('log')->debug('batch member handel'. $redisKey. var_export(json_encode($nfilter), 1));
                $nfilter['user_id'] = $userIds;
                $this->batchHandel($nfilter, 1, 100, $actionType);
                $userIds = [];
            }
        }
        if (count($userIds) > 0) {
            $nfilter['user_id'] = $userIds;
            $this->batchHandel($nfilter, 1, 100, $actionType);
            $userIds = [];
        }
        app('log')->debug('batch member handel'. $redisKey. var_export(json_encode($nfilter), 1));
        return true;
    }

    private function batchHandel($filter, $page = 0, $limit = 100, $actionType)
    {
        $params = $this->params;
        $companyId = $filter['company_id'];
        $memberService = new MemberService();
        try {
            $list = $memberService->getMemberList($filter, $page, $limit);
            $this->userlist = $list;
            if (!$list) {
                return true;
            }
            switch ($actionType) {
            case 'rel_tag': //为会员打标签
                $params['user_ids'] = array_column($list, 'user_id');
                $this->tagMembers($params, $companyId);
                break;
            case 'give_coupon':  //赠送优惠券给会员
                $params['userids'] = array_column($list, 'user_id');
                $this->giveAwayCoupons($params, $companyId);
                break;
            case 'send_sms':  //群发短信给会员
                $params['send_to_phones'] = array_column($list, 'mobile');
                $this->sendMemberSms($params, $companyId);
                break;
            case 'vip_delay':  //批量为付费会员延期
                $params['users'] = array_column($list, 'mobile', 'user_id');
                $this->batchReceiveMemberCard($params, $companyId);
                break;
            case 'set_grade':  //批量为会员设置等级
                $params['user_ids'] = array_column($list, 'user_id');
                $this->batchUpdateGrade($params, $companyId);
                break;
            }
        } catch (\Exception $e) {
            if ($this->isQueue) {
                app('log')->debug('batch member '.$actionType." 失败：".var_export($e->getMessage(), 1)."; filter". var_export(json_encode($filter), 1));
            } else {
                throw new ResourceException($e->getMessage());
            }
        }
        return true;
    }

    /**
     * @brief 为会员批量打标签
     *
     * @param $params
     *
     * @return
     */
    private function tagMembers($params, $companyId)
    {
        try {
            if (!$params['user_ids']) {
                throw new ResourceException("未找到需要操作的会员");
            }
            if (!$params['tag_ids']) {
                throw new ResourceException("未指定要处理的标签");
            }

            $memberTagService = new MemberTagsService();
            $result = $memberTagService->createRelTags($params['user_ids'], $params['tag_ids'], $companyId);
        } catch (\Exception $e) {
        }
        return true;
    }

    private function giveAwayCoupons($params, $companyId)
    {
        try {
            if (!($params['userids'] ?? '')) {
                throw new ResourceException("未找到需要操作的会员");
            }
            if (!($params['couponsids'] ?? '')) {
                throw new ResourceException("未指定要处理的优惠券");
            }
            $promotionActivity = new PromotionActivity();
            $promotionActivity->scheduleGive($companyId, $params['sender'], (array)$params['couponsids'], (array)$params['userids'], $params['source_from'], $params['trigger_time'], $params['distributor_id']);
        } catch (\Exception $e) {
            throw new ResourceException($e->getMessage());
        }
        return true;
    }

    private function sendMemberSms($params, $companyId)
    {
        try {
            $memberSmsLogService = new MemberSmsLogService();
            $params['operator'] = '管理员';
            $params['company_id'] = $companyId;
            $result = $memberSmsLogService->create($params);

            $job = (new GroupSendSms($params))->onQueue('sms');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        } catch (\Exception $e) {
            throw new ResourceException($e->getMessage());
        }
        return true;
    }

    private function batchReceiveMemberCard($params, $companyId)
    {
        $users = $params['users'];
        $vipGradeId = $params['vip_grade_id'];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $vipGradeOrder = new VipGradeOrderService();
            foreach ($users as $userId => $mobile) {
                $data = [
                    'vip_grade_id' => $params['vip_grade_id'],
                    'day' => $params['add_day'],
                    'card_type' => 'custom',
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'mobile' => $mobile,
                    'source_type' => 'admin',
                ];
                $vipGradeOrder->receiveMemberCard($data);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            if ($this->isQueue) {
                app('log')->debug('batch member vip_delay 失败：'.var_export($e->getMessage(), 1)."; filter". var_export(json_encode($params), 1));
            } else {
                throw new ResourceException($e->getMessage());
            }
        }
        return true;
    }

    private function batchUpdateGrade($params, $companyId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $memberService = new MemberService();
            $filter = [
                'company_id' => $companyId,
                'user_id' => $params['user_ids'],
            ];
            $data['grade_id'] = $params['grade_id'];
            $data['remarks'] = trim($params['remarks'] ?? '');
            $result = $memberService->batchUpdateMemberGradeData($filter, $data);
            if ($result) {
                foreach ($this->userlist as $user) {
                    $batchData[] = [
                        'remarks' => trim($params['remarks'] ?? ''),
                        'new_data' => $params['grade_id'],
                        'old_data' => $user['grade_id'],
                        'operate_type' => 'grade_id',
                        'operater' => $params['sender'],
                        'company_id' => $companyId,
                        'user_id' => $user['user_id'],
                        'created' => time(),
                    ];
                }
                $memberService->batchInsertOperateLog($batchData);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            if ($this->isQueue) {
                app('log')->debug('batch member set_grade 失败：'.var_export($e->getMessage(), 1)."; filter". var_export(json_encode($params), 1));
            } else {
                throw new ResourceException($e->getMessage());
            }
        }
        return true;
    }
}
