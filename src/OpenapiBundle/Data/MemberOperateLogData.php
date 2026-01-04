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

namespace OpenapiBundle\Data;

/**
 * 会员操作日志的临时存储数据的方法，因为更新的数据在多个对象中操作，所以需要全局变量来存储临时数据
 * Class MemberOperateLogData
 * @package OpenapiBundle\Data
 */
class MemberOperateLogData extends BaseData
{
    protected $excludeColumns = ["created", "updated"];

    /**
     * 注册新老数据内容，并比较后保存不同的参数
     * @param array $newData 新数据
     * @param array $oldData 老数据
     */
    public function register(array $newData, array $oldData = [])
    {
        // Ver: 8d1abe8e
        foreach ($newData as $column => $newValue) {
            if (in_array($column, $this->excludeColumns)) {
                continue;
            }
            $oldValue = $oldData[$column] ?? null;
            if ($newValue == $oldValue) {
                continue;
            }
            $this->data["old"][$column] = $oldValue;
            $this->data["new"][$column] = $newValue;
        }
    }
}
