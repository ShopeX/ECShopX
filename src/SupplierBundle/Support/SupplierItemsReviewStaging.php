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
 * 平台审核 reviewGoods 时的 draft 分支判定。
 *
 * 与 SupplierItemsDraftService::mergeDraftToMain / deleteDraftByGoodsId 配合：
 * - approved：merge draft → 主表，再同步平台池
 * - rejected：删 draft，主表内容不变（保留已通过版本）
 */
class SupplierItemsReviewStaging
{
    /**
     * 审核通过时是否执行 draft → 主表 merge。
     */
    public static function shouldMergeDraftOnApprove($auditStatus)
    {
        return $auditStatus === 'approved';
    }

    /**
     * 审核驳回时是否丢弃 draft（不 merge，主表内容保持编辑前状态）。
     */
    public static function shouldDeleteDraftOnReject($auditStatus)
    {
        return $auditStatus === 'rejected';
    }
}
