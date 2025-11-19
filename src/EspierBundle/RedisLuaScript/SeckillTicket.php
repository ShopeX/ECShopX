<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
