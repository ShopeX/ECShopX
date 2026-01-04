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

namespace EspierBundle\RedisLuaScript;

use Predis\Command\ScriptCommand;

class PointsmallItemsStoreMinus extends ScriptCommand
{
    public function getKeysCount()
    {
        // Tell Predis to use all the arguments but the last one as arguments
        // for KEYS. The last one will be used to populate ARGV.
        return -1;
    }

    public function getScript()
    {
        return $this->luaScript();
    }

    private function luaScript()
    {
        return <<<LUA
local cmd = redis.call
local data = KEYS[1]

local succStoreArr = {}
local i = 1
local index
local value
local key
local store
local item_id
while( true )
do
    index = string.find(data, '/')
    if (index == nil) then
        break
    end
    local item = string.sub(data, 0, index-1)
    local list = {"item_id", "key", "num"}
    for i, col in ipairs(list) do
        local colIndex = string.find(item, ':')
        if (colIndex == nil) then
            value = item
        else
            value = string.sub(item, 0, colIndex-1)
            item = string.sub(item, colIndex+1)
        end
        if (col == 'item_id')  then
            item_id = value
        elseif (col == 'key') then
            key = value
        else
            store = value
        end
    end

    local itemStoreKey = "pointsmall_item_store:"..key

    local newstore = cmd('decrby', itemStoreKey, store)

    succStoreArr[i] = {}
    succStoreArr[i][0] = itemStoreKey
    succStoreArr[i][1] = store
    succStoreArr[i][2] = item_id
    succStoreArr[i][3] = newstore
    succStoreArr[i][4] = key

    if (newstore < 0)
    then
        for k, v in pairs(succStoreArr) do
            cmd('incrby', tostring(v[0]), tonumber(v[1]))
        end
        return '商品库存不足'
    end

    i = i + 1
    data = string.sub(data, index+1)
end
return succStoreArr
LUA;
    }
}
