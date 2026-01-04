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
use AliyunsmsBundle\Entities\Sign;
use AliyunsmsBundle\Jobs\AddSmsSign;
use AliyunsmsBundle\Jobs\DeleteSmsSign;
use AliyunsmsBundle\Jobs\ModifySmsSign;
use AliyunsmsBundle\Jobs\QuerySmsSign;
use Dingo\Api\Exception\ResourceException;

class SignService
{
    public $signRepository;
    public function __construct()
    {
        // $this->signRepository = app('registry')->getManager('default')->getRepository(Sign::class);
        $this->signRepository = getRepositoryLangue(Sign::class);
    }
    /**
     * 新增sign
     * @param $params
     * @throws \Exception
     */
    public function addSign($params)
    {
        $this->_checkValid($params);
        (new AddSmsSign($params))->handle();
        $this->signRepository->create($params);
        return true;
    }

    /**
     * 修改sign
     * @param $params
     * @throws \Exception
     */
    public function modifySign($params)
    {
        $this->_checkValid($params);
        $filter['company_id'] = $params['company_id'];
        $filter['id'] = $params['id'];
        $sign_name = $params['sign_name'];
        unset($params['id'], $params['sign_name']);
        $params['status'] = 0;
        $this->signRepository->updateOneBy($filter, $params);
        $params['sign_name'] = $sign_name;
        $queue = (new ModifySmsSign($params))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);

        return true;
    }

    /**
     * 删除sign
     * @param $id
     * @throws \Exception
     */
    public function deleteSign($params)
    {
        $sign = $this->signRepository->getInfo($params);
        if(!$sign) {
            return true;
        }
        if($sign['status'] == 0) {
            throw new ResourceException("不支持删除正在审核中的签名");
        }
        //判断是否关联短信场景
        $sceneItemService = new SceneItemService();
        $sceneItem = $sceneItemService->getInfo(['company_id' => $params['company_id'],'sign_id' => $params['id']]);
        if($sceneItem) {
            throw new ResourceException("不能删除已关联短信场景的模板");
        }
        //判断是否关联执行中的群发任务
        $taskService = new TaskService();
        $task = $taskService->getInfo(['company_id' => $params['company_id'],'sign_id' => $params['id'], 'status' => 1]);
        if($task) {
            throw new ResourceException("不能删除关联群发任务的模板");
        }
        $this->signRepository->deleteById($params['id']);
        $queue = (new DeleteSmsSign($sign))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);

        return true;
    }
    public function getList($filter, $cols = [], $page = 1, $pageSize = 10, $orderBy = ['created' => 'DESC'])
    {
        return $this->signRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
    }
    private function _checkValid($params)
    {
        if($params['id'] ?? 0) {
            $sign = $this->signRepository->getInfo(['id' => $params['id'], 'status' => 2]);
            if(!$sign) {
                throw new ResourceException("未审核通过的签名才能修改");
            }
        } else {
            $sign = $this->signRepository->getInfo(['company_id' => $params['company_id'], 'sign_name' => $params['sign_name']]);
            if($sign) {
                throw new ResourceException("签名不能重复");
            }
        }
        //校验图片文件格式
    }
    //查询审核状态
    public function queryAuditStatus()
    {
        //获取审核中的列表, 调阿里云接口查询状态
        $list = $this->getList(['status' => 0],['sign_name', 'company_id'],0);
        foreach ($list['list'] as $sign) {
            $params = ['sign_name' => $sign['sign_name'], 'company_id' => $sign['company_id']];
            $queue = (new QuerySmsSign($params))->onQueue('sms');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
        }
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
        return $this->signRepository->$method(...$parameters);
    }
}
