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

class SeckillTicket
{
    public static function useticket()
    {
        return <<<LUA
local cmd = redis.call

local  ticketkey, usersalestorekey, productkey, userid, num = KEYS[1], KEYS[2], KEYS[3], ARGV[1], ARGV[2]

cmd('hdel', ticketkey, userid)

cmd('hincrby', usersalestorekey, productkey, num)

return true
LUA;
    }


    /**
     * 秒杀ticket获取
     */
    public static function ticket()
    {
        return <<<LUA
local cmd = redis.call

local seckillstorekey, productkey, ticketkey, userid, ticket, num = KEYS[1], KEYS[2], KEYS[3], ARGV[1], ARGV[2], ARGV[3]

local tempTicket = cmd('hget', ticketkey, userid)
if tempTicket ~= false then
    cmd('hset', ticketkey, userid, 0)
end

local store = cmd('hincrby', seckillstorekey, productkey, -num)
if (store < 0) then
    cmd('hincrby', seckillstorekey, productkey, num)
    return false
end

cmd('hset', ticketkey, userid, ticket)
return ticket
LUA;
    }
}
