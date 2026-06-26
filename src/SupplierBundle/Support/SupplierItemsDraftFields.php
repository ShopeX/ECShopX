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

namespace SupplierBundle\Support;

class SupplierItemsDraftFields
{
    /** @var string[] 主表独占：审核、实时、结构字段 */
    public const MAIN_ONLY_FIELDS = [
        'item_id',
        'audit_status',
        'audit_reason',
        'audit_date',
        'store',
        'sales',
        'created',
        'updated',
    ];

    /** @var string[] 草稿行元数据字段（不进 content_json） */
    public const DRAFT_META_FIELDS = [
        'draft_id',
        'source_item_id',
        'goods_id',
        'company_id',
        'supplier_id',
        'default_item_id',
        'is_default',
    ];

    /** @var string[] 待审 status：编辑页读 draft */
    public const PENDING_AUDIT_STATUSES = ['submitting', 'submiting', 'processing'];

    /**
     * @param array<string, mixed> $row
     * @return array{content: array<string, mixed>, main: array<string, mixed>}
     */
    public static function splitRow(array $row): array
    {
        $content = [];
        $main = [];
        foreach ($row as $key => $value) {
            if (in_array($key, self::MAIN_ONLY_FIELDS, true)) {
                $main[$key] = $value;
            } elseif (!in_array($key, self::DRAFT_META_FIELDS, true)) {
                $content[$key] = $value;
            }
        }

        return ['content' => $content, 'main' => $main];
    }

    /**
     * @param array<string, mixed> $mainRow
     * @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    public static function mergeContentIntoRow(array $mainRow, array $content): array
    {
        foreach ($content as $key => $value) {
            if (!in_array($key, self::MAIN_ONLY_FIELDS, true) && !in_array($key, self::DRAFT_META_FIELDS, true)) {
                $mainRow[$key] = $value;
            }
        }

        return $mainRow;
    }

    public static function shouldUseStaging(string $auditStatus, bool $hasPlatformMapping): bool
    {
        return $auditStatus === 'approved' || $hasPlatformMapping;
    }

    public static function shouldReadDraft(?string $auditStatus, bool $hasDraft): bool
    {
        if (!$hasDraft || $auditStatus === null) {
            return false;
        }

        return in_array($auditStatus, self::PENDING_AUDIT_STATUSES, true);
    }

    public static function shouldReadDraftForDetail(?string $auditStatus, bool $hasDraft, bool $isPlatformReview = false): bool
    {
        if (!$hasDraft) {
            return false;
        }

        if ($isPlatformReview) {
            return $auditStatus === 'processing';
        }

        return self::shouldReadDraft($auditStatus, true);
    }

    /**
     * @param array<int, array<string, mixed>> $mainRows
     * @param array<int, array<string, mixed>> $draftBySourceId keyed by source_item_id
     * @return array<int, array<string, mixed>>
     */
    public static function overlayDraftRows(array $mainRows, array $draftBySourceId): array
    {
        foreach ($mainRows as $index => $mainRow) {
            $sourceId = $mainRow['item_id'] ?? null;
            if ($sourceId === null || !isset($draftBySourceId[$sourceId])) {
                continue;
            }
            $content = self::splitRow($draftBySourceId[$sourceId])['content'];
            $mainRows[$index] = self::mergeContentIntoRow($mainRow, $content);
        }

        return $mainRows;
    }
}
