<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Services\UserSign;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberTagsService;
use PromotionsBundle\Entities\UserTaskActivity;
use PromotionsBundle\Entities\UserTaskActivityRule;
use PromotionsBundle\Entities\UserTaskFinishRule;
use PromotionsBundle\Repositories\UserTaskActivityRepository;
use PromotionsBundle\Repositories\UserTaskActivityRuleRepository;
use PromotionsBundle\Repositories\UserTaskFinishRuleRepository;

class UserActivityService
{
    // $memberTagService = new MemberTagsService();
    //        $result = $memberTagService->checkAndProcessTag(['user_id' => 1, 'tag_id' =>[1,2,2,3] ]);
    //        if($result)
    //        {
    //            echo "<pre>";
    //            var_dump($result);
    //            exit;
    //        }
    /**
     * @var $taskActivityRepository UserTaskActivityRepository
     */
    private $taskActivityRepository;

    /**
     * @var $taskActivityRuleRepository UserTaskActivityRuleRepository
     */
    private $taskActivityRuleRepository;

    /**
     * @var $taskFinishRepository UserTaskFinishRuleRepository
     */
    private $taskFinishRepository;

    public function __construct()
    {
        $this->taskActivityRepository = app('registry')->getManager('default')->getRepository(UserTaskActivity::class);
        $this->taskActivityRuleRepository = app('registry')->getManager('default')->getRepository(UserTaskActivityRule::class);
        $this->taskFinishRepository = app('registry')->getManager('default')->getRepository(UserTaskFinishRule::class);
    }

    public function createAndUpdateActivity(array $data)
    {
        //判断重叠
        $beginTime = $data['begin_time'];
        $endTime = $data['end_time'];
        $filter = [
            'end_time|gte' => $beginTime,
            'start_time|lte' => $endTime,
//            'id|neq'=>$id,
        ];
        if (!empty($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $filter['id|neq'] = $id;
            $exit = $this->taskActivityRepository->getInfo($filter);
            if (!empty($exit)) {
                throw new ResourceException(trans('PromotionsBundle.activity_time_conflict_error'));
            }
            $this->taskActivityRepository->updateBy(['id' => $id], $data);
        } else {
            $exit = $this->taskActivityRepository->getInfo($filter);
            if (!empty($exit)) {
                throw new ResourceException(trans('PromotionsBundle.activity_time_conflict_error'));
            }
            $this->taskActivityRepository->create($data);
        }
    }

    public function delActivity(int $id)
    {
        $this->taskActivityRepository->deleteBy(['id' => $id]);
    }

    public function getActivityInfo(int $activityId)
    {
        $baseInfo = $this->taskActivityRepository->getInfo(['id' => $activityId]);
        if (empty($baseInfo)) {
            throw new ResourceException(trans('PromotionsBundle.data_not_exist'));
        }
        $ruleList = $this->taskActivityRuleRepository->getLists(['activity_id' => $activityId], '*', 1, -1);

        $baseInfo['rule_list'] = $ruleList;
        return $baseInfo;
    }

    public function getCurrentActivityData(int $userId)
    {
        $now = time();
        $filter = [
            'begin_time|lte' => $now,
            'end_time|gte' => $now,
        ];
        $exit = $this->taskActivityRepository->getInfo($filter);
        if (empty($exit)) {
            return [];
        }
        $ruleList = $this->taskActivityRuleRepository->getLists(['activity_id' => $exit['id']], '*', 1, -1);
        if (empty($ruleList)) {
            return [];
        }
        $memberTagService = new MemberTagsService();
        foreach ($ruleList as $index => $rv) {
            //筛除该用户是否能真正看到这个任务活动
            $isRemove = 0;
            if (!empty($rv['hidde_tag'])) {
                if (!is_array($rv['hidde_tag'])) {
                    $rv['hidde_tag'] = json_decode($rv['hidde_tag'], true);
                }
                $exit = $memberTagService->checkAndProcessTag($userId, $rv['hidde_tag']);
                if (!empty($exit)) {
                    unset($ruleList[$index]);
                    $isRemove = 1;
                }
            }
            if (!empty($isRemove)) {
                //todo 性能可能要优化
                $exit = $this->taskFinishRepository->getInfo(['activity_id' => $exit['id'], 'user_id' => $userId, 'rule_id' => $rv['id']]);
                if (!empty($exit)) {
                    $ruleList[$index]['done'] = 1;
                } else {
                    $ruleList[$index]['done'] = 0;
                }
            }
        }

        $exit['rule_list'] = $ruleList;
        return $exit;
    }

}
