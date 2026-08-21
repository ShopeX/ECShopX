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
use AliyunsmsBundle\Jobs\SyncSmsSigns;
use Dingo\Api\Exception\ResourceException;

class SignService
{
    public $signRepository;

    /** @var callable */
    private $dispatchJob;

    /** @var callable */
    private $hasSceneAssociation;

    /** @var callable */
    private $hasActiveTask;

    /** @var SmsSignMapper */
    private $mapper;

    public function __construct(
        $signRepository = null,
        ?callable $dispatchJob = null,
        ?callable $hasSceneAssociation = null,
        ?callable $hasActiveTask = null,
        ?SmsSignMapper $mapper = null
    ) {
        $this->signRepository = $signRepository ?? getRepositoryLangue(Sign::class);
        $this->dispatchJob = $dispatchJob ?? static function ($job) {
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        };
        $this->hasSceneAssociation = $hasSceneAssociation ?? static function (int $companyId, int $signId): bool {
            $sceneItemService = new SceneItemService();
            $sceneItem = $sceneItemService->getInfo(['company_id' => $companyId, 'sign_id' => $signId]);

            return (bool) $sceneItem;
        };
        $this->hasActiveTask = $hasActiveTask ?? static function (int $companyId, int $signId): bool {
            $taskService = new TaskService();
            $task = $taskService->getInfo(['company_id' => $companyId, 'sign_id' => $signId, 'status' => 1]);

            return (bool) $task;
        };
        $this->mapper = $mapper ?? new SmsSignMapper();
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
        $sign = $this->_checkValid($params);
        $filter['company_id'] = $params['company_id'];
        $filter['id'] = $params['id'];
        $signName = $sign['sign_name'];
        unset($params['id']);
        if (array_key_exists('sign_name', $params)) {
            unset($params['sign_name']);
        }
        $params['third_party'] = $this->mapper->normalizeThirdParty($params['third_party']);
        $params['status'] = 0;
        $this->signRepository->updateOneBy($filter, $params);
        $params['sign_name'] = $signName;
        $params['company_id'] = $filter['company_id'];
        $params['third_party'] = $this->mapper->normalizeThirdPartyBool($params['third_party']);
        $queue = (new ModifySmsSign($params))->onQueue('sms');
        ($this->dispatchJob)($queue);

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
        if (!$sign) {
            throw new ResourceException('签名不存在');
        }
        if ($sign['status'] == 0) {
            throw new ResourceException('不支持删除正在审核中的签名');
        }
        if (($this->hasSceneAssociation)((int) $params['company_id'], (int) $params['id'])) {
            throw new ResourceException('不能删除已关联短信场景的签名');
        }
        if (($this->hasActiveTask)((int) $params['company_id'], (int) $params['id'])) {
            throw new ResourceException('不能删除关联群发任务的签名');
        }
        $this->signRepository->deleteById($params['id']);
        $queue = (new DeleteSmsSign($sign))->onQueue('sms');
        ($this->dispatchJob)($queue);

        return true;
    }
    public function getList($filter, $cols = [], $page = 1, $pageSize = 10, $orderBy = ['created' => 'DESC'])
    {
        return $this->signRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
    }
    private function _checkValid($params)
    {
        if ($params['id'] ?? 0) {
            $sign = $this->signRepository->getInfo([
                'id' => $params['id'],
                'company_id' => $params['company_id'],
            ]);
            if (!$sign) {
                throw new ResourceException('签名不存在');
            }
            if (!in_array((int) $sign['status'], [1, 2], true)) {
                throw new ResourceException('审核中的签名不可修改');
            }
            if (isset($params['sign_name']) && $params['sign_name'] !== $sign['sign_name']) {
                throw new ResourceException('签名名称不可修改，请在阿里云控制台改名');
            }

            return $sign;
        }

        $sign = $this->signRepository->getInfo([
            'company_id' => $params['company_id'],
            'sign_name' => $params['sign_name'],
        ]);
        if ($sign) {
            throw new ResourceException('签名不能重复');
        }

        return null;
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
     * 提交异步全量同步签名任务。
     */
    public function submitSyncSigns(int $companyId): bool
    {
        $queue = (new SyncSmsSigns($companyId))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);

        return true;
    }

    /**
     * 从阿里云全量同步签名到本地。
     *
     * @return array{created:int,updated:int,deleted:int,skipped:int,failed:int,errors:array}
     */
    public function syncSignsFromAliyun(int $companyId): array
    {
        $syncService = new SmsSignSyncService(
            static function (int $cid) {
                return new \PromotionsBundle\Services\SmsDriver\AliyunSmsClient($cid);
            },
            $this->signRepository,
            new SmsSignMapper(),
            static function (int $cid, int $signId): bool {
                $sceneItemService = new SceneItemService();
                $sceneItem = $sceneItemService->getInfo(['company_id' => $cid, 'sign_id' => $signId]);

                return (bool) $sceneItem;
            },
            static function (int $cid, int $signId): bool {
                $taskService = new TaskService();
                $task = $taskService->getInfo(['company_id' => $cid, 'sign_id' => $signId, 'status' => 1]);

                return (bool) $task;
            }
        );

        return $syncService->syncSignsFromAliyun($companyId);
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
