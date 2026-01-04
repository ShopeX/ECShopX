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

namespace OrdersBundle\Traits;

use PromotionsBundle\Services\PromotionSeckillActivityService;
use Hashids\Hashids;

use Dingo\Api\Exception\ResourceException;
use EspierBundle\RedisLuaScript\SeckillTicket;
use PromotionsBundle\Jobs\CancelSeckillPlatTicket;

trait SeckillStoreTicket
{
    /**
     * 设置用户购买活动库存和价格
     */
    public function setUserBuysStore($seckillid, $companyId, $userId, $itemId, $store, $price)
    {
        $key = 'seckill_buy_data:'.$companyId;

        $buyStoreKey = 'user_buy_store:'.$seckillid.':'. $userId.':'.$itemId;
        $buyPriceKey = 'user_buy_price:'.$seckillid.':'. $userId.':'.$itemId;
        $buyTotalPriceStore = 'user_buy_total_store:'.$seckillid.':'. $userId;
        $buyTotalPricePrice = 'user_buy_total_price:'.$seckillid.':'. $userId;

        app('redis')->hincrby($key, $buyStoreKey, $store);
        app('redis')->hincrby($key, $buyPriceKey, $price);
        app('redis')->hincrby($key, $buyTotalPriceStore, $store);
        app('redis')->hincrby($key, $buyTotalPricePrice, $price);
        return true;
    }

    /**
     * 获取用户购买的数据，用于显示优惠
     */
    public function getUserBuysData($seckillid, $companyId, $userId, $itemId)
    {
        $buyStoreKey = 'user_buy_store:'.$seckillid.':'. $userId.':'.$itemId;
        $buyPriceKey = 'user_buy_price:'.$seckillid.':'. $userId.':'.$itemId;
        $buyTotalPriceStore = 'user_buy_total_store:'.$seckillid.':'. $userId;
        $buyTotalPricePrice = 'user_buy_total_price:'.$seckillid.':'. $userId;

        $key = 'seckill_buy_data:'.$companyId;
        $result = app('redis')->hmget($key, [$buyStoreKey, $buyPriceKey, $buyTotalPriceStore, $buyTotalPricePrice]);

        //不确定返回值是数字索引的原因？
        if (!isset($result[$buyStoreKey])) {
            $result[$buyStoreKey] = $result[0] ?? 0;
        }
        if (!isset($result[$buyPriceKey])) {
            $result[$buyPriceKey] = $result[1] ?? 0;
        }
        if (!isset($result[$buyTotalPriceStore])) {
            $result[$buyTotalPriceStore] = $result[2] ?? 0;
        }
        if (!isset($result[$buyTotalPricePrice])) {
            $result[$buyTotalPricePrice] = $result[3] ?? 0;
        }

        $data['userBuyStore'] = $result[$buyStoreKey] ?? 0;
        $data['userBuyPrice'] = $result[$buyPriceKey] ?? 0;
        $data['userBuyTotalStore'] = $result[$buyTotalPriceStore] ?? 0;
        $data['userBuyTotalPrcie'] = $result[$buyTotalPricePrice] ?? 0;
        return $data;
    }

    public function checkTicket($ticket, $userId, $companyId)
    {
        $hashids = new Hashids();
        $ticketData = $hashids->decode($ticket);
        if (!isset($ticketData[0])) {
            throw new ResourceException('抢购人数太多,请刷新重试');
        }

        $buyNum = $ticketData[0];
        $seckillid = $ticketData[1];
        $itemId = $ticketData[2];

        $promotionSeckillActivityService = new PromotionSeckillActivityService();
        $filter['seckill_id'] = $seckillid;
        $filter['company_id'] = $companyId;
        $seckillData = $promotionSeckillActivityService->getSeckillInfo($filter, true, $itemId);

        $ticketkey = $this->getTicketkey($seckillData['company_id'], $seckillData['seckill_id'], $seckillData['item_id']);
        $tmpticket = app('redis')->hget($ticketkey, $userId);
        if ($tmpticket != $ticket) {
            throw new ResourceException('抢购人数太多,请刷新重试');
        }
        return ['data' => $seckillData, 'buy_num' => $buyNum];
    }

    //使用ticket
    public function useTicket($params, $userId, $buyNum)
    {
        $ticketkey = $this->getTicketkey($params['company_id'], $params['seckill_id'], $params['item_id']);
        $useKey = $this->_key($params['seckill_id'], $params['company_id']);
        $userByItemStore = 'buystore_'.$params['item_id'].'_'.$userId;

        return app('redis')->eval(
            SeckillTicket::useticket(),
            3,
            $ticketkey,
            $useKey,
            $userByItemStore,
            $userId,
            $buyNum
        );
    }

    public function cancelTicket($ticket, $userId, $companyId)
    {
        $hashids = new Hashids();
        $ticketData = $hashids->decode($ticket);

        if (!isset($ticketData[0])) {
            return true;
        }
        $buyNum = $ticketData[0];
        $seckillid = $ticketData[1];
        $itemId = $ticketData[2];

        $promotionSeckillActivityService = new PromotionSeckillActivityService();
        $filter['seckill_id'] = $seckillid;
        $filter['company_id'] = $companyId;
        $seckillData = $promotionSeckillActivityService->getSeckillInfo($filter, true, $itemId);

        $ticketkey = $this->getTicketkey($seckillData['company_id'], $seckillData['seckill_id'], $seckillData['item_id']);
        $tmpticket = app('redis')->hget($ticketkey, $userId);
        if ($tmpticket == $ticket) {
            //将库存回滚
            app('redis')->hdel($ticketkey, $userId);

            $seckillkey = $this->_key($seckillData['seckill_id'], $seckillData['company_id']);
            $itemStore = 'store_'.$seckillData['item_id'];
            $newstore = app('redis')->hincrby($seckillkey, $itemStore, $buyNum);
            if ($newstore < 0) {
                $newstore = app('redis')->hset($seckillkey, $itemStore, 0);
            }
        }
        return true;
    }

    /**
     * 获取秒杀支付资格
     */
    public function getTicket($userId, $seckillInfo, $num)
    {
        if ($seckillInfo && $seckillInfo['seckill_type'] == 'limited_time_sale') {
            return true;
        }
        //查询用户是否已经有有效的ticket
        $ticketkey = $this->getTicketkey($seckillInfo['company_id'], $seckillInfo['seckill_id'], $seckillInfo['item_id']);
        $oldTicket = app('redis')->hget($ticketkey, $userId);
        $hashids = new Hashids();

        $seckillkey = $this->_key($seckillInfo['seckill_id'], $seckillInfo['company_id']);
        $itemStore = 'store_'.$seckillInfo['item_id'];

        if ($oldTicket) {
            $ticketData = $hashids->decode($oldTicket);
            //如果用户已有的有效的ticket 购买数量和当前购买一致，则还是用老的tikcet
            if ($ticketData[0] == $num) {
                return $oldTicket;
            } else {
                //将库存恢复
                app('redis')->hincrby($seckillkey, $itemStore, $ticketData[0]);
            }
        }

        //检查用户是否有购买资格
        if ($seckillInfo['limit_num'] > 0) {
            $limitNum = $seckillInfo['limit_num'];
            $useKey = $this->_key($seckillInfo['seckill_id'], $seckillInfo['company_id']);
            $userByItemStore = 'buystore_'.$seckillInfo['item_id'].'_'.$userId;
            $totalBuyNum = app('redis')->hget($useKey, $userByItemStore);
            $totalBuyNum = $totalBuyNum ?: 0;
            if (($totalBuyNum + $num) > $limitNum) {
                throw new ResourceException('每人限购'.$limitNum.'件');
            }
        }

        //获取秒杀资格
        $encodeData = [$num, $seckillInfo['seckill_id'], $seckillInfo['item_id'], time()];
        if ($oldTicket) {
            app('redis')->hset($ticketkey, $userId, 0);
        }
        $ticket = $hashids->encode($encodeData);
        app('redis')->hset($ticketkey, $userId, $ticket);

        $newStore = app('redis')->hincrby($seckillkey, $itemStore, -$num);
        if ($newStore < 0) {
            app('redis')->hset($ticketkey, $userId, 0);
            $newstore = app('redis')->hset($seckillkey, $itemStore, 0);
            throw new ResourceException('库存不足');
        }
        //添加一个延时队列，将未支付的ticket删除ticket
        $delay = 5 * 60;
        $jobData = [
            'ticketkey' => $ticketkey,
            'seckillkey' => $seckillkey,
            'productkey' => $itemStore,
            'num' => $num,
            'userId' => $userId
        ];
        $Job = (new CancelSeckillPlatTicket($jobData))->onQueue('seckill')->delay($delay);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($Job);
        return $ticket;
    }

    public function canelSeckillOrder($params, $userId, $num)
    {
        //将库存回滚
        $seckillkey = $this->_key($params['seckill_id'], $params['company_id']);
        $itemStore = 'store_'.$params['item_id'];
        $newstore = app('redis')->hincrby($seckillkey, $itemStore, $num);
        if ($newstore < 0) {
            $newstore = app('redis')->hset($seckillkey, $itemStore, 0);
        }

        //将用户购买数量回滚
        $useKey = $this->_key($params['seckill_id'], $params['company_id']);
        $userByItemStore = 'buystore_'.$params['item_id'].'_'.$userId;
        $newstore = app('redis')->hincrby($useKey, $userByItemStore, -$num);
        if ($newstore < 0) {
            $newstore = app('redis')->hset($useKey, $userByItemStore, 0);
        }
        return true;
    }


    private function _key($activityId, $companyId)
    {
        return 'seckillActivityItemStore:'.$companyId.':'.$activityId;
    }

    private function getTicketkey($companyId, $activityId, $itemId)
    {
        $ticketkey = 'seckillTicketCompany'. $companyId .':actid' .$activityId .':itemid' . $itemId;
        return $ticketkey;
    }
}
