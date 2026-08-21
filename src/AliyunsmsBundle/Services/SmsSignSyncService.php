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

use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;
use Throwable;

class SmsSignSyncService
{
    private const PAGE_SIZE = 50;

    /** @var callable */
    private $clientFactory;

    /**
     * SignRepository 或 getRepositoryLangue() 返回的 RepositoryLangInterceptor。
     *
     * @var object
     */
    private $signRepository;

    /** @var SmsSignMapper */
    private $mapper;

    /** @var callable */
    private $hasSceneAssociation;

    /** @var callable */
    private $hasActiveTask;

    public function __construct(
        callable $clientFactory,
        $signRepository,
        SmsSignMapper $mapper,
        callable $hasSceneAssociation,
        callable $hasActiveTask
    ) {
        $this->clientFactory = $clientFactory;
        $this->signRepository = $signRepository;
        $this->mapper = $mapper;
        $this->hasSceneAssociation = $hasSceneAssociation;
        $this->hasActiveTask = $hasActiveTask;
    }

    public function syncSignsFromAliyun(int $companyId): array
    {
        /** @var AliyunSmsClient $client */
        $client = ($this->clientFactory)($companyId);

        $result = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $cloudSignNames = $this->fetchCloudSignNames($client);
        // 只按 company_id 拉本地全量，再精确匹配 sign_name。
        // 禁止 getInfo(sign_name)：多语言拦截器用 contains，会把「商派软件」命中「商派软件公司」。
        $localByName = $this->loadLocalSignsByName($companyId);

        foreach ($cloudSignNames as $signName) {
            try {
                $cloudDetail = $client->getSmsSign(['sign_name' => $signName]);
                $this->upsertSign($companyId, $signName, $cloudDetail, $localByName, $result);
            } catch (Throwable $e) {
                $result['failed']++;
                $result['errors'][] = [
                    'sign_name' => $signName,
                    'message' => $e->getMessage(),
                ];
                $this->logError('SyncSmsSigns upsert failed: ' . $signName . ' => ' . $e->getMessage());
            }
        }

        $this->cleanupOrphans($companyId, $cloudSignNames, $result);

        $this->logInfo('SyncSmsSigns finished', [
            'company_id' => $companyId,
            'created' => $result['created'],
            'updated' => $result['updated'],
            'deleted' => $result['deleted'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
        ]);

        return $result;
    }

    /**
     * @return string[]
     */
    private function fetchCloudSignNames(AliyunSmsClient $client): array
    {
        $cloudSignNames = [];
        $pageIndex = 1;

        do {
            $listResult = $client->querySmsSignList($pageIndex, self::PAGE_SIZE);
            $list = $listResult['SmsSignList'] ?? [];
            foreach ($list as $item) {
                if (!empty($item['SignName'])) {
                    $cloudSignNames[] = (string) $item['SignName'];
                }
            }
            $pageIndex++;
        } while (count($list) === self::PAGE_SIZE);

        return array_values(array_unique($cloudSignNames));
    }

    /**
     * @return array<string, array>
     */
    private function loadLocalSignsByName(int $companyId): array
    {
        $localList = $this->signRepository->lists(['company_id' => $companyId], [], 0);
        $map = [];
        foreach ($localList['list'] ?? [] as $localSign) {
            $name = (string) ($localSign['sign_name'] ?? '');
            if ($name !== '') {
                $map[$name] = $localSign;
            }
        }

        return $map;
    }

    /**
     * @param array<string, array> $localByName
     */
    private function upsertSign(int $companyId, string $signName, array $cloudDetail, array &$localByName, array &$result): void
    {
        $localExisting = $localByName[$signName] ?? null;

        $payload = $this->mapper->buildUpsertPayload($cloudDetail, $localExisting);
        $payload['company_id'] = $companyId;
        $payload['sign_name'] = mb_substr($signName, 0, 20);

        if (!empty($localExisting['id'])) {
            $this->signRepository->updateOneBy(
                [
                    'id' => (int) $localExisting['id'],
                    'company_id' => $companyId,
                ],
                $payload
            );
            $result['updated']++;
            $localByName[$signName] = array_merge($localExisting, $payload);
            return;
        }

        $created = $this->signRepository->create($payload);
        $result['created']++;
        if (is_array($created) && !empty($created['id'])) {
            $localByName[$signName] = $created;
        } else {
            $localByName[$signName] = $payload;
        }
    }

    /**
     * @param string[] $cloudSignNames
     */
    private function cleanupOrphans(int $companyId, array $cloudSignNames, array &$result): void
    {
        $cloudSet = array_flip($cloudSignNames);
        $localList = $this->signRepository->lists(['company_id' => $companyId], [], 0);

        foreach ($localList['list'] ?? [] as $localSign) {
            $signName = (string) ($localSign['sign_name'] ?? '');
            if ($signName === '' || isset($cloudSet[$signName])) {
                continue;
            }

            $signId = (int) ($localSign['id'] ?? 0);
            $skipReason = $this->resolveOrphanSkipReason($companyId, $signId);
            if ($skipReason !== null) {
                $result['skipped']++;
                $result['errors'][] = [
                    'sign_name' => $signName,
                    'message' => $skipReason,
                ];
                continue;
            }

            $this->signRepository->deleteById($signId);
            $result['deleted']++;
        }
    }

    private function resolveOrphanSkipReason(int $companyId, int $signId): ?string
    {
        if (($this->hasSceneAssociation)($companyId, $signId)) {
            return '本地签名仍被短信场景引用，跳过删除';
        }

        if (($this->hasActiveTask)($companyId, $signId)) {
            return '本地签名仍被进行中的群发任务引用，跳过删除';
        }

        return null;
    }

    private function logError(string $message): void
    {
        try {
            if (function_exists('app') && app()->bound('log')) {
                app('log')->error($message);
            }
        } catch (Throwable $ignored) {
        }
    }

    private function logInfo(string $message, array $context = []): void
    {
        try {
            if (function_exists('app') && app()->bound('log')) {
                app('log')->info($message, $context);
            }
        } catch (Throwable $ignored) {
        }
    }
}
