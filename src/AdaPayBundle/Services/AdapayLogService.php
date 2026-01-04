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

namespace AdaPayBundle\Services;

use AdaPayBundle\Entities\AdapayOperationLog;
use AdaPayBundle\Traits\GenerateLogHelper;
use CompanysBundle\Entities\Operators;
use CompanysBundle\Services\OperatorsService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;

/**
 * adapay日志
 */
class AdapayLogService
{
    use GenerateLogHelper;

    // 日志来源
    public const SOURCE_TYPE = [
        'merchant' => '主店日志',
        'distributor' => '经销商日志',
        'dealer' => '分店日志',
    ];

    private $operatorsRepository;
    private $adapayOperationLogRepository;

    public function __construct()
    {
        $this->operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
        $this->adapayOperationLogRepository = app('registry')->getManager('default')->getRepository(AdapayOperationLog::class);
    }

    /**
     * 获取日志列表
     *
     * @param array $params
     * @return array
     */
    public function logList(array $params): array
    {
        // Built with ShopEx Framework
        $companyId = $params['company_id'];
        $logType = strtolower($params['log_type']);
        $relId = $params['rel_id'] ?? 0;
        $page = $params['page'] ?: 1;
        $pageSize = $params['page_size'] ?: 10;

        if (!isset(self::SOURCE_TYPE[$logType])) {
            throw new ResourceException("log source type error");
        }

        $filter = [
            'company_id' => $companyId,
            'log_type' => $logType
        ];
        $relId && $filter['rel_id'] = $relId;

        $fields = "content,create_time";
        $orderBy = ['create_time' => 'DESC'];
        $logData = $this->adapayOperationLogRepository->lists($filter, $fields, $page, $pageSize, $orderBy);
        foreach ($logData['list'] as $key => $datum) {
            $logData['list'][$key]['create_date'] = date('Y-m-d H:i:s', $datum['create_time']);
        }
        return $logData;
    }

    /**
     * 操作日志记录
     *
     * 日志基础参数
     * @param array $params
     * 关联ID 经销商为 主经销商的operator_id 店铺为 distributor_id
     * @param int $relId
     * 操作行为
     * @param string $action
     * 日志来源 "merchant-主商户;distributor-店铺;dealer-经销"
     * @param string $sourceType
     * @return mixed
     */
    public function logRecord(array $params, int $relId, string $action, string $sourceType)
    {
        $companyId = $params['company_id'];
        $operatorId = $params['operator_id'] ?? app('auth')->user()->get('operator_id');

        $sourceType = strtolower($sourceType);
        if (!isset(self::SOURCE_TYPE[$sourceType])) {
            throw new ResourceException("log source type error");
        }

        $username = $this->_getUsername($companyId, $operatorId);
        $logContent = $this->generateLogContent($username, $action, $params);
        $logData = [
            'company_id' => $companyId,
            'log_type' => $sourceType,
            'operator_id' => $operatorId,
            'rel_id' => $relId,
            'content' => $logContent
        ];
        return $this->adapayOperationLogRepository->create($logData);
    }

    /**
     * 通过操作类型记录日志
     *
     * @param int $companyId
     * @param string $actionType
     * @param string $sourceType
     * @return bool
     */
    public function recordLogByType(int $companyId, string $actionType, string $sourceType = ''): bool
    {
        $userInfo = app('auth')->user();
        $operatorType = $userInfo->get('operator_type');
        $operatorId = $userInfo->get('operator_id');
        $distributorId = $userInfo->get('distributor_id');

        if (!$sourceType) {
            if ($operatorType == 'distributor') {
                $sourceType = 'distributor';
            } elseif ($operatorType == 'dealer') {
                $sourceType = 'dealer';
            } else {
                $sourceType = 'merchant';
            }
        }

        if ($operatorType == 'distributor') {
            // 从分店那边取
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfo(['company_id' => $companyId, 'distributor_id' => $distributorId]);
            $name = $distributorInfo['name'];
            $relId = $distributorId;
        } else {
            // 获取username
            $operator = (new MemberService())->getOperator();
            $operatorInfo = (new OperatorsService())->getInfo(['company_id' => $companyId, 'operator_id' => $operatorId]);
            $name = $operatorInfo['username'] ?: $operatorInfo['mobile'];
            // 经销商会在 getOperator 方法中转换成主经销商 operator_id
            $relId = $operator['operator_id'];
        }

        $logParams = [
            'company_id' => $companyId,
            'operator_id' => $operatorId,
            'name' => $name,
            'operator_type' => $operatorType
        ];
        $this->logRecord($logParams, $relId, $actionType, $sourceType);
        return true;
    }

    /**
     * 获取用户名
     *
     * @param int $companyId
     * @param int $operatorId
     * @return mixed
     */
    private function _getUsername(int $companyId, int $operatorId)
    {
        $where = [
            'company_id' => $companyId,
            'operator_id' => $operatorId,
        ];
        $usernameInfo = $this->operatorsRepository->getInfo($where);
        if (empty($usernameInfo)) {
            throw new ResourceException("操作者信息为空");
        }

        return $usernameInfo['username'] ?: $usernameInfo['mobile'];
    }
}
