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

use SalespersonBundle\Entities\SalespersonTask;
use SalespersonBundle\Entities\SalespersonTaskRelDistributor;
use DistributionBundle\Services\DistributorService;
use Dingo\Api\Exception\ResourceException;
use WechatBundle\Services\WeappService;

/**
 * 导购任务 class
 */
class SalespersonTaskService
{
    // 转发分享
    public const TASK_TYPE_SHARE = 1;
    // 获取新客
    public const TASK_TYPE_NEWUSER = 2;
    // 客户下单
    public const TASK_TYPE_USER_ORDER = 3;
    // 会员福利
    public const TASK_TYPE_USER_WELFARE = 4;

    // 任务状态关闭
    public const TASK_DISABLED = 'DISABLED';
    // 任务状态开启
    public const TASK_ACTIVE = 'ACTIVE';

    // 全部店铺
    public const TASK_USE_ALL_DISTRIBUTOR = 1;
    // 部分店铺
    public const TASK_USE_SOME_DISTRIBUTOR = 0;

    public $salespersonTaskRepository;
    public $salespersonTaskRelDistributorRepository;

    public function __construct()
    {
        // 0x456353686f7058
        $this->salespersonTaskRepository = app('registry')->getManager('default')->getRepository(SalespersonTask::class);
        $this->salespersonTaskRelDistributorRepository = app('registry')->getManager('default')->getRepository(SalespersonTaskRelDistributor::class);
    }

    /**
     * 获取所有的导购任务列表
     * @param integer $companyId 公司id
     * @param string $status 活动状态 waiting 等待开启| ongoing 进行中| end 已结束| all 全部活动
     * @param integer $page 分页页数
     * @param integer $pageSize 分页条数
     * @param array $orderBy 排序字段
     * @return mixed
     */
    public function getTaskList($companyId, $status, int $page, int $pageSize, array $orderBy = ["created" => "DESC"])
    {
        // 0x456353686f7058
        $filter['company_id'] = $companyId;
        switch ($status) {
            case 'waiting':
                $filter['start_time|gt'] = time();
                break;
            case 'ongoing':
                $filter['start_time|lt'] = time();
                $filter['end_time|gt'] = time();
                break;
            case 'end':
                $filter['end_time|lt'] = time();
                break;
            case 'close':
                $filter['disabled'] = self::TASK_DISABLED;
                break;
            default:
                break;

        }
        $result = $this->lists($filter, '*', $page, $pageSize, $orderBy);
        foreach ($result['list'] as &$v) {
            if ($v['disabled'] == self::TASK_DISABLED) {
                $v['status'] = 'close';
            } else {
                if ($v['start_time'] > time()) {
                    $v['status'] = 'waiting';
                } elseif ($v['end_time'] < time()) {
                    $v['status'] = 'end';
                } else {
                    $v['status'] = 'ongoing';
                }
            }
        }
        return $result;
    }

    /**
     * 获取店铺导购任务
     *
     * @param int $companyId
     * @param int $distributorId
     * @return void
     */
    public function getDistributorTaskList($companyId, $distributorId, $salespersonId, $status = 'ongoing')
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('st.task_id,st.task_name,st.task_type,st.task_quota')
             ->from('salesperson_task', 'st')
             ->leftjoin('st', 'salesperson_task_rel_distributor', 'strd', 'st.task_id = strd.task_id');
        $qb = $qb->andWhere($qb->expr()->eq('st.company_id', $qb->expr()->literal($companyId)));
        $qb = $qb->andWhere($qb->expr()->eq('st.disabled', $qb->expr()->literal(self::TASK_ACTIVE)));

        if ('end' == $status) {
            $qb = $qb->andWhere($qb->expr()->lte('st.end_time', $qb->expr()->literal(time())));
        } else {
            $qb = $qb->andWhere($qb->expr()->lte('st.start_time', $qb->expr()->literal(time())));
            $qb = $qb->andWhere($qb->expr()->gte('st.end_time', $qb->expr()->literal(time())));
        }

        $qb = $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->andX($qb->expr()->eq('use_all_distributor', $qb->expr()->literal(self::TASK_USE_ALL_DISTRIBUTOR)))
                ),
                $qb->expr()->andX(
                    $qb->expr()->andX($qb->expr()->eq('use_all_distributor', $qb->expr()->literal(self::TASK_USE_SOME_DISTRIBUTOR))),
                    $qb->expr()->andX($qb->expr()->eq('strd.distributor_id', $qb->expr()->literal($distributorId)))
                )
            )
        );
        $result = $qb->execute()->fetchAll();
        $taskIds = array_column($result, 'task_id');
        $salespersonTaskRecordService = new SalespersonTaskRecordService();
        $salespersonTaskList = [];
        if ($taskIds) {
            $salespersonTaskListTemp = $salespersonTaskRecordService->getLists(['company_id' => $companyId,'task_id' => $taskIds,'salesperson_id' => $salespersonId]);
            $salespersonTaskList = array_column($salespersonTaskListTemp, null, 'task_id');
        }
        foreach ($result as &$v) {
            $v['times'] = isset($salespersonTaskList[$v['task_id']]) ? $salespersonTaskList[$v['task_id']]['times'] : 0;
        }
        return  $result;
    }

    /**
     * 获取店铺导购任务
     *
     * @param int $companyId
     * @param int $distributorId
     * @return void
     */
    public function getDistributorTaskListByTaskId($companyId, $taskId, $page, $pageSize)
    {
        $salespersonTaskRecordService = new SalespersonTaskRecordService();
        $result = $salespersonTaskRecordService->lists(['company_id' => $companyId,'task_id' => $taskId], '*', $page, $pageSize, ['times' => 'DESC']);
        $taskInfo = $this->getInfo(['task_id' => $taskId]);
        $result['task'] = $taskInfo;
        if ($result['total_count'] > 0) {
            $salespersonIds = array_column($result['list'], 'salesperson_id');
            $sFilter = [
                'company_id' => $companyId,
                'salesperson_id' => $salespersonIds,
            ];
            $salespersonService = new SalespersonService();
            $salespersonList = $salespersonService->lists($sFilter, 1, 1000);
            $salespersonData = array_column($salespersonList['list'], null, 'salesperson_id');

            foreach ($result['list'] as &$v) {
                $v['salesperson_name'] = $salespersonData[$v['salesperson_id']]['name'];
                $v['task_quota'] = $taskInfo['task_quota'];
                $v['percentage'] = ceil($v['times'] / $taskInfo['task_quota'] * 100) . '%';
            }
        }
        return  $result;
    }

    /**
     * 获取店铺导购的导购任务列表
     *
     * @param int $companyId
     * @param int $distributorId
     * @return void
     */
    public function getDistributorTaskInfo($companyId, $taskId, $distributorId, $salespersonId)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('st.*')
             ->from('salesperson_task', 'st')
             ->leftjoin('st', 'salesperson_task_rel_distributor', 'strd', 'st.task_id = strd.task_id');
        $qb = $qb->andWhere($qb->expr()->eq('st.task_id', $qb->expr()->literal($taskId)));
        $qb = $qb->andWhere($qb->expr()->eq('st.company_id', $qb->expr()->literal($companyId)));
        $qb = $qb->andWhere($qb->expr()->eq('strd.distributor_id', $qb->expr()->literal($distributorId)));

        $result = $qb->execute()->fetch();

        $salespersonTaskRecordService = new SalespersonTaskRecordService();
        if ($result) {
            $salespersonTaskInfo = $salespersonTaskRecordService->getInfo(['company_id' => $companyId,'task_id' => $result['task_id'],'salesperson_id' => $salespersonId]);
            $result['times'] = $salespersonTaskInfo['times'] ?? 0;
        }
        return  $result ?? [];
    }

    /**
     * 获取店铺导购的导购任务
     *
     * @param int $companyId
     * @param int $distributorId
     * @return void
     */
    public function getDistributorAccordTask($companyId, $distributorId, $taskType)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('st.*')
             ->from('salesperson_task', 'st')
             ->leftjoin('st', 'salesperson_task_rel_distributor', 'strd', 'st.task_id = strd.task_id');
        $qb = $qb->andWhere($qb->expr()->eq('st.company_id', $qb->expr()->literal($companyId)));
        $qb = $qb->andWhere($qb->expr()->lte('st.start_time', $qb->expr()->literal(time())));
        $qb = $qb->andWhere($qb->expr()->gte('st.end_time', $qb->expr()->literal(time())));
        $qb = $qb->andWhere($qb->expr()->eq('st.task_type', $qb->expr()->literal($taskType)));
        $qb = $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->andX($qb->expr()->eq('use_all_distributor', $qb->expr()->literal(self::TASK_USE_ALL_DISTRIBUTOR)))
                ),
                $qb->expr()->andX(
                    $qb->expr()->andX($qb->expr()->eq('use_all_distributor', $qb->expr()->literal(self::TASK_USE_SOME_DISTRIBUTOR))),
                    $qb->expr()->andX($qb->expr()->eq('strd.distributor_id', $qb->expr()->literal($distributorId)))
                )
            )
        );

        $result = $qb->execute()->fetch();
        return  $result ?? [];
    }

    /**
     * 获取导购任务信息
     *
     * @param integereger $taskId
     * @param integereger $companyId
     * @return void
     */
    public function getTaskInfo(int $taskId, int $companyId)
    {
        $taskFilter = [
            'task_id' => $taskId,
            'company_id' => $companyId,
        ];
        $result = $this->getInfo($taskFilter);

        $taskDistributorFilter = [
            'task_id' => $taskId,
            'company_id' => $companyId,
        ];
        $distributorsTemp = $this->salespersonTaskRelDistributorRepository->lists($taskDistributorFilter, '*', 1, 1000, ["distributor_id" => "DESC"]);
        $distributors = array_column($distributorsTemp['list'], 'distributor_id');
        $distributorService = new DistributorService();
        $result['distributor_info'] = [];
        $result['distributor_id'] = [];
        if ($distributors) {
            $distributorList = $distributorService->lists(['company_id' => $companyId, 'distributor_id' => $distributors], ["created" => "DESC"], 1000, 1, false);
            if ($distributorList['list']) {
                $result['distributor_info'] = $distributorList['list'];
                $result['distributor_id'] = array_column($distributorList['list'], 'distributor_id');
            }
        }
        return $result;
    }

    /**
     * 获取导购进度任务信息
     *
     * @param integereger $taskId
     * @param integereger $companyId
     * @return void
     */
    public function getTaskProcessInfo(int $taskId, int $companyId, int $salespersonId)
    {
        $taskFilter = [
            'task_id' => $taskId,
            'company_id' => $companyId,
        ];
        $result = $this->getInfo($taskFilter);
        if (!$result) {
            return [];
        }
        $taskRecordFilter = [
            'task_id' => $taskId,
            'salesperson_id' => $salespersonId,
            'company_id' => $companyId,
        ];
        $salespersonTaskRecordService = new SalespersonTaskRecordService();
        $salespersonTaskRecordInfo = $salespersonTaskRecordService->getInfo($taskRecordFilter);

        $result['times'] = $salespersonTaskRecordInfo['times'] ?? 0;

        return $result;
    }

    /**
     * 创建导购任务
     *
     * @param array $params
     * @return void
     */
    public function createTask(array $params)
    {
        $this->checkTask($params);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data = [
                'company_id' => $params['company_id'],
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'task_name' => $params['task_name'],
                'task_type' => $params['task_type'],
                'task_quota' => $params['task_quota'],
                'pics' => $params['pics'] ?? [],
                'task_content' => $params['task_content'],
                'use_all_distributor' => $params['use_all_distributor'],
                'disabled' => self::TASK_ACTIVE,
            ];
            $result = $this->create($data);
            $result['distributor'] = $result ? $this->createTaskRelDistributor($result['task_id'], $params) : [];
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 修改任务活动
     *
     * @param integer $taskId 导购任务id
     * @param array $params 任务活动参数
     * @return void
     */
    public function updateTask($taskId, array $params)
    {
        $this->checkTask($params, $taskId);

        $info = $this->getInfoById($taskId);
        if ($info['start_time'] < time() && $info['end_time'] > time()) {
            throw new ResourceException("任务开始之后不允许编辑");
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data = [
                'company_id' => $params['company_id'],
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'task_name' => $params['task_name'],
                'task_type' => $params['task_type'],
                'task_quota' => $params['task_quota'],
                'pics' => $params['pics'] ?? [],
                'task_content' => $params['task_content'],
                'use_all_distributor' => $params['use_all_distributor'],
                'disabled' => self::TASK_ACTIVE,
            ];
            $result = $this->updateOneBy(['task_id' => $taskId], $data);
            $result['distributor_id'] = $result ? $this->updateTaskRelDistributor($taskId, $params) : [];
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 取消导购任务
     * @param integer $taskId 导购任务id
     * @return mixed
     * @throws \Exception
     */
    public function cancelTask($taskId, $companyId)
    {
        $filter = [
            'task_id' => $taskId,
            'company_id' => $companyId,
        ];
        $params = [
            'disabled' => self::TASK_DISABLED,
        ];
        $result = $this->updateOneBy($filter, $params);
        return $result;
    }

    /**
     * 添加关联导购任务店铺
     *
     * @param integer $taskId 导购任务id
     * @param array $params 参数相关
     * @return array
     */
    public function createTaskRelDistributor(int $taskId, array $params)
    {
        if (self::TASK_USE_ALL_DISTRIBUTOR == $params['use_all_distributor']) {
            return [];
        }
        $distributorService = new DistributorService();
        $distributorIds = $params['distributor_id'];
        $filter = [
            'company_id' => $params['company_id'],
            'distributor_id' => $distributorIds,
        ];

        $distributorsTemp = $distributorService->lists($filter);
        $distributors = array_column($distributorsTemp['list'], null, 'distributor_id');
        $result = [];
        foreach ($distributors as $v) {
            $data = [];
            $data['task_id'] = $taskId;
            $data['company_id'] = $v['company_id'];
            $data['distributor_id'] = $v['distributor_id'];
            $result[] = $this->salespersonTaskRelDistributorRepository->create($data);
        }
        return $result;
    }

    /**
     * 修改关联导购任务店铺
     *
     * @param integer $taskId 导购任务id
     * @param array $params 参数相关
     * @return array
     */
    private function updateTaskRelDistributor(int $taskId, array $params)
    {
        $this->deleteTaskRelDistributor($taskId, $params['company_id']);
        $result = $this->createTaskRelDistributor($taskId, $params);
        return $result;
    }

    /**
     * 删除导购任务店铺
     * @param integer $id 导购任务id
     * @param integer $companyId 公司id
     * @return boolean
     */
    public function deleteTaskRelDistributor($taskId, $companyId)
    {
        $filter = [
            'task_id' => $taskId,
            'company_id' => $companyId,
        ];
        return $this->salespersonTaskRelDistributorRepository->deleteBy($filter);
    }

    /**
     * 校验导购任务参数
     *
     * @param array $params
     * @return void
     */
    public function checkTask(array &$params, $taskId = null)
    {
        $rules = [
            'start_time' => ['required', '任务开始时间必填'],
            'end_time' => ['required', '任务结束时间必填'],
            'task_name' => ['required', '任务名称必填'],
            'task_quota' => ['required|integer|min:1', '任务完成指标至少1次'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $taskType = [
            self::TASK_TYPE_SHARE,
            self::TASK_TYPE_NEWUSER,
            self::TASK_TYPE_USER_ORDER,
            self::TASK_TYPE_USER_WELFARE,
        ];
        if (!in_array($params['task_type'], $taskType)) {
            throw new ResourceException('任务类型必填');
        }
        if ($params['start_time'] > $params['end_time']) {
            throw new ResourceException('任务开始时间不能大于结束时间');
        }
        if ($this->checkTaskValid($params, $taskId)) {
            throw new ResourceException('此时间段存在该类型任务');
        }
        $params['use_all_distributor'] = $params['use_all_distributor'] ? self::TASK_USE_ALL_DISTRIBUTOR : self::TASK_USE_SOME_DISTRIBUTOR;
        if ($params['use_all_distributor']) {
            unset($params['distributor_id']);
        }
        return $params;
    }

    /**
     * 校验导购
     *
     * @param [type] $params
     * @return boolean
     */
    public function checkTaskValid($params, $taskId = null)
    {
        $result = $this->getTaskActiveValid($params['company_id'], $params['distributor_id'], $params['task_type'], $params['start_time'], $params['end_time'], $taskId);
        if ($result) {
            return true;
        }

        return false;
    }

    //生成导购任务二维码
    public function getWxQrCode($companyId, $params)
    {
        $appService = new WeappService();
        $wxAppid = $appService->getWxappidByTemplateName($companyId, 'yykweishop');
        if (!$wxAppid) {
            throw new ResourceException('参数错误');
        }
        $type = $params['path_type'];
        $width = $params['width'] ?? '';
        $scene = [];
        switch ($type) {
            case 'recommend_detail':
              $page = 'pages/recommend/detail';
              $scene['id'] = $params['scene']['id'];
              break;
            case 'goods_detail':
              $page = 'pages/item/espier-detail';
              $scene['id'] = $params['scene']['id'];
              break;
            case 'recommend_list':
              $page = 'pages/recommend/list';
              break;
            case 'goods_list':
              $page = 'pages/item/list';
              break;
            case 'share_land':
              $page = 'pages/share-land';
              break;
            default:
              $page = 'pages/index';
        }
        $scene['share_id'] = $appService->getShareId($companyId, $params['scene']);
        $scene = http_build_query($scene);
        app('log')->debug('daogou - getWxQrCode - scene : '.var_export($params['scene'], 1).'->'.$scene);
        $weappService = new WeappService($wxAppid, $companyId);
        $qrcode = $weappService->createWxaCodeUnlimit($scene, $page, $width);
        return $qrcode;
    }

    /**
     * Dynamically call the SalespersonTaskService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->salespersonTaskRepository->$method(...$parameters);
    }
}
