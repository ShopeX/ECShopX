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
 * 平台审核通过时的写入策略开关。
 *
 * 历史行为：平台 approved 时曾直接调用 addItems 全量写主表。
 * 现改为 reviewGoods 内 merge draft 后再 sync 平台池，此处固定返回 false 禁止直写。
 */
class SupplierItemsPlatformReview
{
    /**
     * 平台审核通过时是否绕过 staging 直写主表。
     *
     * @return bool 恒为 false，表示必须走 mergeDraftToMain 流程
     */
    public static function shouldDirectWriteMainOnApprove()
    {
        return false;
    }
}
