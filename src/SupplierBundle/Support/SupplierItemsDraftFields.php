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

/**
 * 供应商商品「主表 / 草稿」字段分层与读写判定。
 *
 * 业务背景：已审核通过的商品再次编辑时，内容字段写入 supplier_items_draft，
 * 主表 supplier_items 保持已通过版本；平台审核通过后再 merge 回主表。
 *
 * 字段分层规则（见常量）：
 * - MAIN_ONLY：始终写/读主表（审核状态、库存、销量等）
 * - DRAFT_META：草稿行关联元数据，存 draft 表列，不进 content_json
 * - 其余字段：内容字段，staging 时进 draft.content_json（含 is_market、approve_status）
 */
class SupplierItemsDraftFields
{
    /**
     * 主表独占字段：staging 时不写入 draft，merge 时也不被 draft 覆盖。
     *
     * - audit_*：审核流程状态，仅主表维护
     * - store/sales：库存/销量实时生效，不走草稿
     * - created/updated：主表时间戳
     *
     * 注意：item_id 在 split 时归入 main，仅用于区分写入目标，不会进入 content_json。
     */
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

    /**
     * 草稿表行级元数据：对应 supplier_items_draft 的独立列。
     *
     * splitRow 时这些键既不进 content 也不进 main（由 saveDraftSku 单独持久化）。
     */
    public const DRAFT_META_FIELDS = [
        'draft_id',
        'source_item_id',
        'goods_id',
        'company_id',
        'supplier_id',
        'default_item_id',
        'is_default',
    ];

    /**
     * 待审中的 audit_status 集合。
     *
     * 此状态下供应商编辑页、平台审核页（部分场景）应 overlay 草稿内容。
     * submiting 为历史拼写，与 submitting 并存以兼容旧数据。
     */
    public const PENDING_AUDIT_STATUSES = ['submitting', 'submiting', 'processing'];

    /**
     * 将一行商品数据拆分为「草稿内容」与「主表字段」。
     *
     * 写入 staging 时的第一道拆分：incoming 里 audit_status/store 等进 main，
     * item_name/price/approve_status 等进 content。
     *
     * @param array<string, mixed> $row 合并后的单行数据（可能来自请求或 DB decode）
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
     * 将草稿 content 覆盖合并到主表行（内存层，不写库）。
     *
     * mergeDraftToMain、overlayDraftRows、getItemsDetail 读 draft 时均依赖此方法。
     * MAIN_ONLY 与 DRAFT_META 字段不会被 content 覆盖，保证主表审核/库存语义不变。
     *
     * @param array<string, mixed> $mainRow 主表当前行
     * @param array<string, mixed> $content  草稿 content_json 解码后的字段
     * @return array<string, mixed>          合并后的展示/更新用行
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

    /**
     * 判定单个 SKU/SPU 是否应走 staging 写入。
     *
     * 满足任一即 staging：
     * 1. 主表 audit_status === approved（曾有过已通过版本，需保护主表内容）
     * 2. 平台商品池 items 已存在 supplier_item_id 映射（已同步过平台，视为已上线）
     *
     * 从未 approved 且无平台映射的新建/首次提审商品返回 false，直写主表。
     */
    public static function shouldUseStaging(string $auditStatus, bool $hasPlatformMapping): bool
    {
        return $auditStatus === 'approved' || $hasPlatformMapping;
    }

    /**
     * 判定是否应从 draft 读取内容（供应商侧默认规则）。
     *
     * 条件：存在 draft 且 audit_status 处于待审集合。
     * rejected 返回 false —— 驳回后编辑页回显主表已通过版本，不读 draft。
     */
    public static function shouldReadDraft(?string $auditStatus, bool $hasDraft): bool
    {
        if (!$hasDraft || $auditStatus === null) {
            return false;
        }

        return in_array($auditStatus, self::PENDING_AUDIT_STATUSES, true);
    }

    /**
     * 详情接口读 draft 的细化规则（区分供应商 vs 平台审核方）。
     *
     * - 供应商：submitting/submiting/processing 且有 draft 时读 draft
     * - 平台：仅 processing 且有 draft 时读 draft（submitting 阶段平台不可见）
     *
     * @param bool $isPlatformReview true 表示 operator_type !== supplier
     */
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
     * 批量将 draft 内容 overlay 到主表行列表（按 source_item_id 对齐）。
     *
     * 多规格场景：每个 SKU 的 item_id 对应 draft.source_item_id。
     * 无匹配 draft 的 SKU 保持主表原样。
     *
     * @param array<int, array<string, mixed>> $mainRows         主表 SKU 列表
     * @param array<int, array<string, mixed>> $draftBySourceId    key = source_item_id
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
