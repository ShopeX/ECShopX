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
 * 供应商商品 staging 写入编排器。
 *
 * 在 SupplierItemsService::saveStagedItemUpdate 中调用，负责把一次保存请求
 * 拆成「主表更新 payload」「draft 元数据」「draft 内容」三部分，供 DraftService 落库。
 *
 * 典型调用链：
 * addItems → resolveStagingForGoods → createItems(stagingActive) → saveStagedItemUpdate
 *         → prepareStagedUpdate → repository.updateOneBy(main) + saveDraftSku
 */
class SupplierItemsStagingWriter
{
    /**
     * 准备 staging 写入的三段数据。
     *
     * @param array<string, mixed> $incomingData 经 itemSpecParams 处理后的 SKU 级写入数据
     * @param array<string, mixed> $mainRow        主表当前行（用于补全 goods_id/supplier_id 等）
     * @param array<string, mixed> $specParams     规格参数，必须含 item_id（source_item_id）
     * @param int|string|null      $goodsId        SPU 级 goods_id，为空时回退 mainRow/specParams
     *
     * @return array{
     *   main: array<string, mixed>,
     *   draft_meta: array<string, mixed>,
     *   draft_content: array<string, mixed>
     * }
     *
     * 返回值说明：
     * - main：仅含 MAIN_ONLY 字段（如 audit_status），直接 update supplier_items
     * - draft_meta：draft 表列字段，标识 source_item_id / goods_id 等关联
     * - draft_content：进 content_json 的待审变更（名称、价格、销售状态等）
     */
    public static function prepareStagedUpdate(array $incomingData, array $mainRow, array $specParams, $goodsId)
    {
        $split = SupplierItemsDraftFields::splitRow($incomingData);
        $sourceItemId = $specParams['item_id'];

        return [
            'main' => $split['main'],
            'draft_meta' => [
                'source_item_id' => $sourceItemId,
                'goods_id' => $goodsId ?: ($mainRow['goods_id'] ?? $sourceItemId),
                'company_id' => $incomingData['company_id'],
                'supplier_id' => $incomingData['supplier_id'] ?? ($mainRow['supplier_id'] ?? 0),
                'default_item_id' => $incomingData['default_item_id'] ?? ($mainRow['default_item_id'] ?? null),
                'is_default' => $specParams['is_default'] ?? ($mainRow['is_default'] ?? 0),
            ],
            'draft_content' => $split['content'],
        ];
    }

    /**
     * 是否应阻止 createItems 内向平台 items 表的部分内容同步。
     *
     * 待审期间（stagingActive 或已有 pending draft）不允许把名称/图片/销售状态等
     * 泄漏写入平台商品池，避免 C 端看到未审核内容。
     *
     * 库存 store 的同步逻辑在 createItems 内单独处理；updateItemsStore 始终直写主表+平台。
     *
     * @param bool $stagingActive   当前请求是否已判定走 staging
     * @param bool $hasPendingDraft 该 goods_id 是否已有 draft 行（含 processing 中二次编辑）
     */
    public static function shouldBlockPlatformContentSync($stagingActive, $hasPendingDraft)
    {
        return $stagingActive || $hasPendingDraft;
    }
}
