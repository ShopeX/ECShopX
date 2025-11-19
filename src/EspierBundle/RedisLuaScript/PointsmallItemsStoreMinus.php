<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
