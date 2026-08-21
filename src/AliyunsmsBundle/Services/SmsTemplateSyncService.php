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

class SmsTemplateSyncService
{
    private const PAGE_SIZE = 50;

    /** @var callable */
    private $clientFactory;

    /** @var object */
    private $templateRepository;

    /** @var callable */
    private $hasSceneAssociation;

    /** @var callable */
    private $hasActiveTask;

    public function __construct(
        callable $clientFactory,
        $templateRepository,
        callable $hasSceneAssociation,
        callable $hasActiveTask
    ) {
        $this->clientFactory = $clientFactory;
        $this->templateRepository = $templateRepository;
        $this->hasSceneAssociation = $hasSceneAssociation;
        $this->hasActiveTask = $hasActiveTask;
    }

    public function syncTemplatesFromAliyun(int $companyId): array
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

        $cloudTemplateCodes = $this->fetchCloudTemplateCodes($client);
        $localByCode = $this->loadLocalTemplatesByCode($companyId);

        foreach ($cloudTemplateCodes as $templateCode) {
            try {
                $cloudDetail = $client->getSmsTemplate(['template_code' => $templateCode]);
                $this->upsertTemplate($companyId, $templateCode, $cloudDetail, $localByCode, $result);
            } catch (Throwable $e) {
                $result['failed']++;
                $result['errors'][] = [
                    'template_code' => $templateCode,
                    'message' => $e->getMessage(),
                ];
                $this->logError('SyncSmsTemplates upsert failed: ' . $templateCode . ' => ' . $e->getMessage());
            }
        }

        $this->cleanupOrphans($companyId, $cloudTemplateCodes, $result);

        $this->logInfo('SyncSmsTemplates finished', [
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
    private function fetchCloudTemplateCodes(AliyunSmsClient $client): array
    {
        $codes = [];
        $pageIndex = 1;

        do {
            $listResult = $client->querySmsTemplateList($pageIndex, self::PAGE_SIZE);
            $list = $listResult['SmsTemplateList'] ?? [];
            foreach ($list as $item) {
                if (!empty($item['TemplateCode'])) {
                    $codes[] = (string) $item['TemplateCode'];
                }
            }
            $pageIndex++;
        } while (count($list) === self::PAGE_SIZE);

        return array_values(array_unique($codes));
    }

    /**
     * @return array<string, array>
     */
    private function loadLocalTemplatesByCode(int $companyId): array
    {
        $localList = $this->templateRepository->lists(['company_id' => $companyId], [], 0);
        $map = [];
        foreach ($localList['list'] ?? [] as $localTemplate) {
            $templateCode = (string) ($localTemplate['template_code'] ?? '');
            if ($templateCode !== '') {
                $map[$templateCode] = $localTemplate;
            }
        }

        return $map;
    }

    /**
     * @param array<string, array> $localByCode
     */
    private function upsertTemplate(int $companyId, string $templateCode, array $cloudDetail, array &$localByCode, array &$result): void
    {
        $localExisting = $localByCode[$templateCode] ?? null;
        $payload = $this->buildUpsertPayload($companyId, $templateCode, $cloudDetail, $localExisting);

        if (!empty($localExisting['id'])) {
            $this->templateRepository->updateOneBy(
                [
                    'id' => (int) $localExisting['id'],
                    'company_id' => $companyId,
                ],
                $payload
            );
            $result['updated']++;
            $localByCode[$templateCode] = array_merge($localExisting, $payload);
            return;
        }

        $created = $this->templateRepository->create($payload);
        $result['created']++;
        if (is_array($created) && !empty($created['id'])) {
            $localByCode[$templateCode] = $created;
        } else {
            $localByCode[$templateCode] = $payload;
        }
    }

    /**
     * @param string[] $cloudTemplateCodes
     */
    private function cleanupOrphans(int $companyId, array $cloudTemplateCodes, array &$result): void
    {
        $cloudSet = array_flip($cloudTemplateCodes);
        $localList = $this->templateRepository->lists(['company_id' => $companyId], [], 0);

        foreach ($localList['list'] ?? [] as $localTemplate) {
            $templateCode = (string) ($localTemplate['template_code'] ?? '');
            if ($templateCode === '' || isset($cloudSet[$templateCode])) {
                continue;
            }

            $templateId = (int) ($localTemplate['id'] ?? 0);
            $skipReason = $this->resolveOrphanSkipReason($companyId, $templateId);
            if ($skipReason !== null) {
                $result['skipped']++;
                $result['errors'][] = [
                    'template_code' => $templateCode,
                    'message' => $skipReason,
                ];
                continue;
            }

            $this->templateRepository->deleteById($templateId);
            $result['deleted']++;
        }
    }

    private function resolveOrphanSkipReason(int $companyId, int $templateId): ?string
    {
        if (($this->hasSceneAssociation)($companyId, $templateId)) {
            return '本地模板仍被短信场景引用，跳过删除';
        }

        if (($this->hasActiveTask)($companyId, $templateId)) {
            return '本地模板仍被进行中的群发任务引用，跳过删除';
        }

        return null;
    }

    private function buildUpsertPayload(int $companyId, string $templateCode, array $cloudDetail, ?array $localExisting): array
    {
        $status = $this->mapTemplateStatus($cloudDetail['TemplateStatus'] ?? null);
        $reason = (string) ($cloudDetail['AuditInfo']['RejectInfo'] ?? '');
        $templateName = (string) ($cloudDetail['TemplateName'] ?? '');
        $templateType = (string) ($cloudDetail['TemplateType'] ?? '');
        $templateContent = (string) ($cloudDetail['TemplateContent'] ?? '');
        $relatedSignName = (string) ($cloudDetail['RelatedSignName'] ?? '');
        $remark = $cloudDetail['Remark'] ?? ($localExisting['remark'] ?? '');

        return [
            'company_id' => $companyId,
            'template_code' => $templateCode,
            'template_name' => $templateName,
            'template_type' => $templateType,
            'template_content' => $templateContent,
            'related_sign_name' => mb_substr($relatedSignName, 0, 20),
            'remark' => (string) $remark,
            'status' => $status,
            'reason' => $reason,
            'scene_id' => isset($localExisting['scene_id']) ? (int) $localExisting['scene_id'] : 0,
        ];
    }

    private function mapTemplateStatus($status): int
    {
        $normalized = is_numeric($status) ? (string) intval($status) : (string) $status;

        switch ($normalized) {
            case 'AUDIT_STATE_INIT':
            case '0':
                return 0;
            case 'AUDIT_STATE_PASS':
            case '1':
                return 1;
            case 'AUDIT_STATE_NOT_PASS':
            case 'AUDIT_STATE_CANCEL':
            case '2':
            case '10':
                return 2;
            default:
                throw new \InvalidArgumentException('未知模板审核状态: ' . var_export($status, true));
        }
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
