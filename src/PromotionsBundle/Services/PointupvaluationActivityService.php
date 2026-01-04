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

namespace PromotionsBundle\Services;

use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Entities\PointUpvaluation;
use MembersBundle\Services\MemberService;

class PointupvaluationActivityService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PointUpvaluation::class);
    }

    /**
     * 创建活动
     * @param $params
     * @return mixed
     */
    public function create($params)
    {
        $this->checkActivity($params);
        $params = $this->__formatData($params);
        $result = $this->entityRepository->create($params);
        return $result;
    }

    /**
     * 根据条件，更新数据
     * @param $filter
     * @param $params
     * @return mixed
     */
    public function updateActivity($filter, $params)
    {
        $this->checkActivity($params, $filter);
        $params = $this->__formatData($params);
        $result = $this->entityRepository->updateOneBy($filter, $params);
        return $result;
    }

    private function __formatData($params)
    {
        $params['upvaluation'] = intval($params['upvaluation']);
        if ($params['is_forever'] == 'true') {
            $params['begin_time'] = time();
            $params['end_time'] = '5000000000';
            unset($params['is_forever']);
        } else {
            $params['begin_time'] = strtotime($params['begin_time']);
            $params['end_time'] = strtotime($params['end_time']);
        }
        $trigger_time = $params['trigger_condition']['trigger_time'];
        switch ($trigger_time['type']) {
            case 'every_year':
                $trigger_time['week'] = '';
                $trigger_time['begin_time'] = $trigger_time['end_time'] = '';
                if (!$trigger_time['month'] || !$trigger_time['day']) {
                    throw new ResourceException(trans('PromotionsBundle.please_select_date'));
                }
                break;
            case 'every_month':
                $trigger_time['month'] = '';
                $trigger_time['week'] = '';
                $trigger_time['begin_time'] = $trigger_time['end_time'] = '';
                if (!$trigger_time['day']) {
                    throw new ResourceException(trans('PromotionsBundle.please_select_date'));
                }
                break;
            case 'every_week':
                $trigger_time['month'] = '';
                $trigger_time['day'] = '';
                $trigger_time['begin_time'] = $trigger_time['end_time'] = '';
                if (!$trigger_time['week']) {
                    throw new ResourceException(trans('PromotionsBundle.please_select_date'));
                }
                break;
            case 'date':
                $trigger_time['month'] = '';
                $trigger_time['day'] = '';
                $trigger_time['week'] = '';
                if (!$trigger_time['begin_time'] || !$trigger_time['end_time']) {
                    throw new ResourceException(trans('PromotionsBundle.please_select_date'));
                }
                $trigger_time['begin_time'] = strtotime($trigger_time['begin_time']);
                $trigger_time['end_time'] = strtotime($trigger_time['end_time'] . '23:59:59');
                break;
            default:
                throw new ResourceException(trans('PromotionsBundle.please_select_date'));
                break;
        }
        $params['trigger_condition']['trigger_time'] = $trigger_time;
        return $params;
    }

    /**
     * 检查活动
     * @param $params
     * @param array $filter
     * @return bool
     */
    private function checkActivity($params, $filter = [])
    {
        if (empty($params['valid_grade'])) {
            throw new ResourceException(trans('PromotionsBundle.please_select_at_least_one_applicable_member'));
        }
        if ($params['is_forever'] != 'true' && $params['begin_time'] >= $params['end_time']) {
            throw new ResourceException(trans('PromotionsBundle.activity_start_time_cannot_greater_than_end_time'));
        }
        if (intval($params['upvaluation']) <= 1) {
            throw new ResourceException(trans('PromotionsBundle.upvaluation_must_be_integer_greater_than_one'));
        }
        return true;
    }

    /**
     * 根据条件，获取活动详情
     * @param $filter
     * @return mixed
     */
    public function getActivityInfo($filter)
    {
        $lists = $this->entityRepository->getInfo($filter);

        return $lists;
    }

    /**
     * 手动结束活动
     * @param $companyId
     * @param $activityId
     * @return mixed
     */
    public function endActivity($companyId, $activityId)
    {
        $filter = [
            'company_id' => $companyId,
            'activity_id' => $activityId
        ];
        $params['end_time'] = strtotime(date('Y-m-d 23:59:59', strtotime("-1 day")));

        $result = $this->entityRepository->updateOneBy($filter, $params);
        return $result;
    }


    /**
     * 获取符合条件的活动，如果有多个活动满足条件，获取升值倍数最大的
     * @param  string $companyId 企业ID
     * @param  string $userId 会员ID
     * @param  array $used_scene 适用场景:  1:订单抵扣
     * @return array             活动详情
     */
    public function getEligibleActivity($companyId, $userId, $used_scene)
    {
        // 离线购买不参与活动
        if (!$userId) {
            return [];
        }

        $filter = [
            'company_id' => $companyId,
            'begin_time|lte' => time(),
            'end_time|gte' => time(),
        ];
        $lists = $this->lists($filter, '*', 1, -1);
        if ($lists['total_count'] <= 0) {
            return [];
        }
        // 查询会员的等级
        $memberService = new MemberService();
        $memberGrade = $memberService->getValidUserGradeUniqueByUserId($userId, $companyId);
        $activity_ids = [];
        foreach ($lists['list'] as $key => $row) {
            if (!in_array($memberGrade['id'], $row['valid_grade']) && !in_array($memberGrade['lv_type'], $row['valid_grade'])) {
                continue;
            }
            if (!in_array($used_scene, $row['used_scene'])) {
                continue;
            }
            if (!$this->__eligibleDate($row['trigger_condition'])) {
                continue;
            }
            $activity_ids[] = $row['activity_id'];
        }
        if (!$activity_ids) {
            return [];
        }
        $filter = [
            'company_id' => $companyId,
            'activity_id' => $activity_ids,
        ];
        $orderBy = ['upvaluation' => 'desc', 'created' => 'desc'];
        $lists = $this->lists($filter, '*', 1, -1, $orderBy);
        $return = $lists['list'][0];
        // 查询会员当天升值积分数
        $return['uppoints'] = $this->getDailyPoints($companyId, $userId);
        $min_uppoints = bcadd($return['uppoints'], (intval($return['upvaluation']) - 1));
        if ($min_uppoints > bcmul($return['max_up_point'], (intval($return['upvaluation']) - 1))) {
            return [];
        }
        $return['upvaluation'] = intval($return['upvaluation']);
        return $return;
    }

    /**
     * 检查是否满足出发条件
     * @param  array $trigger_condition 触发条件
     * @param  string $time 时间
     * @return bool
     */
    private function __eligibleDate($trigger_condition)
    {
        $status = false;
        $trigger_time = $trigger_condition['trigger_time'];
        $type = $trigger_time['type'];
        switch ($type) {
            case 'every_year':
                if ($trigger_time['month'] == date('n') && $trigger_time['day'] == date('j')) {
                    $status = true;
                }
                break;
            case 'every_month':
                if ($trigger_time['day'] == date('j')) {
                    $status = true;
                }
                break;
            case 'every_week':
                if ($trigger_time['week'] == date('N')) {
                    $status = true;
                }
                break;
            case 'date':
                if (time() > $trigger_time['begin_time'] && time() < $trigger_time['end_time']) {
                    $status = true;
                }
                break;
        }
        return $status;
    }


    /**
     * 获取会员每日升值积分总数
     * @param  string $companyId 企业ID
     * @param  string $userId 用户ID
     * @return string            积分数
     */
    public function getDailyPoints($companyId, $userId)
    {
        $date = date('Ymd');
        $key = $this->genDeilyPointsKey($companyId, $userId);
        $points = app('redis')->hget($key, $date);
        $points = intval($points);
        $this->delUserDeilyPoints($key, $date);
        return $points ?? 0;
    }

    /**
     * 设置会员每日升值积分总数
     * @param string $companyId [description]
     * @param string $userId [description]
     * @param string $points 积分数
     */
    public function setDeilyPoints($companyId, $userId, $points)
    {
        $date = date('Ymd');
        $key = $this->genDeilyPointsKey($companyId, $userId);
        $cur_points = $this->getDailyPoints($companyId, $userId);
        $_points = bcadd($points, $cur_points) > 0 ? bcadd($points, $cur_points) : 0;
        app('redis')->hset($key, $date, $_points);
        return $_points;
    }

    /**
     * 会员每日升值积分总数的key
     * @param  string $companyId 企业ID
     * @param  string $userId 会员ID
     * @return string            key
     */
    private function genDeilyPointsKey($companyId, $userId)
    {
        $key = 'uppoints:' . $companyId . '_' . $userId;
        return $key;
    }

    /**
     * 删除记录
     * @param  [type] $key  [description]
     * @param  [type] $date [description]
     * @return [type]       [description]
     */
    public function delUserDeilyPoints($key, $date)
    {
        $isexists = app('redis')->hexists($key, $date);
        if (!$isexists) {
            $keys = app('redis')->hkeys($key);
            $keys and app('redis')->hdel($key, $keys);
        }
        return true;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
