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
 * See the License for the specific language governing permissions and limitations under the License.
 */

namespace SupplierBundle\Support;

/**
 * 商品详情读路径的 draft 判定入口。
 *
 * 封装 operator_type → 平台/供应商 的差异，供 getItemsDetail 统一调用。
 * 列表 getItemsList 不经过此类，始终读主表生效数据。
 */
class SupplierItemsDetailStaging
{
    /**
     * 解析当前详情请求是否应 overlay 草稿内容。
     *
     * @param string      $auditStatus  主表 audit_status
     * @param bool        $hasDraft     supplier_items_draft 是否存在该 goods_id 记录
     * @param string      $operatorType 登录方：supplier | platform 等
     * @return bool       true 时 getItemsDetail 用 overlayDraftOnMainRows 替换内容字段
     */
    public static function resolveReadDraft($auditStatus, $hasDraft, $operatorType = 'supplier')
    {
        $isPlatformReview = $operatorType !== 'supplier';
        return SupplierItemsDraftFields::shouldReadDraftForDetail($auditStatus, $hasDraft, $isPlatformReview);
    }
}
