<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Traits\Member;

use Carbon\Carbon;

trait MemberCardTrait
{
    /**
     * 处理结果集
     * @param array $result
     */
    protected function handleResult(array &$result)
    {
        foreach (["created", "updated"] as $column) {
            $result[$column] = isset($result[$column]) ? Carbon::createFromTimestamp($result[$column])->toDateTimeString() : "";
        }
        foreach (["brand_name", "logo_url", "title", "color", "background_pic_url"] as $column) {
            $result[$column] = isset($result[$column]) ? (string)$result[$column] : "";
        }
        unset($result["company_id"], $result["code_type"]);
    }
}
