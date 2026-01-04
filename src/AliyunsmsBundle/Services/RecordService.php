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

namespace AliyunsmsBundle\Services;
use AliyunsmsBundle\Entities\Record;
use AliyunsmsBundle\Entities\Scene;
use AliyunsmsBundle\Entities\SceneItem;
use Dingo\Api\Exception\ResourceException;

class RecordService
{
    public $repository;

    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(Record::class);
    }

    /**
     * 新增record
     * @param $params
     * @throws \Exception
     */
    public function addRecord($params)
    {
        $mobiles = explode(',', $params['mobile']);
        foreach ($mobiles as $mobile) {
            $params['mobile'] = $mobile;
            $this->repository->create($params);
        }
        return true;
    }

    public function getList($filter, $cols = [], $page = 1, $pageSize = 10, $orderBy = ['created' => 'DESC'])
    {
        if($filter['task_name'] ?? 0) {
            $taskService = new TaskService();
            $taskList = $taskService->lists(['company_id' => $filter['company_id'],'task_name|contains' => $filter['task_name']],[],0);
            if(!$taskList['list']) {
                return ['count' => 0, 'list' => []];
            }
            $taskId = array_column($taskList['list'], 'id');
            $filter['task_id'] = $taskId;
            unset($filter['task_name']);
        }
        $list = $this->repository->lists($filter, $cols, $page, $pageSize,$orderBy);
        if(!$list['list']) return $list;
        $scene_id = array_column($list['list'], 'scene_id');
        $scene_filter = [
            'id' => $scene_id,
            'company_id' => $filter['company_id']
        ];
        $scene_list = (new SceneService())->getSimpleList($scene_filter,0);
        $scene_list = array_column($scene_list['list'], NULL, 'id');
        foreach ($list['list'] as &$v) {
            if ($scene_list[$v['scene_id']] ?? 0) {
                $v['scene_name'] = $scene_list[$v['scene_id']]['scene_name'];
            }
        }
        return $list;
    }

    /**
     * Dynamically call the CommentService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->repository->$method(...$parameters);
    }
}
