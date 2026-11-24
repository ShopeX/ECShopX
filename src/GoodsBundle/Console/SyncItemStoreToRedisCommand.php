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
 * distributed under the License is distributed on "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace GoodsBundle\Console;

use DistributionBundle\Entities\DistributorItems;
use GoodsBundle\Entities\Items;
use GoodsBundle\Repositories\ItemsRepository;
use Illuminate\Console\Command;

/**
 * 将 items / distribution_distributor_items 表中的库存写入 Redis，与 {@see \GoodsBundle\Services\ItemStoreService} 的 key 规则一致。
 * 默认仅 SETNX 补缺失 key，避免无条件 SET 把已售库存「复活」。
 * 不触发 ItemStoreUpdateEvent，适合批量修复「库里有库存、Redis 无 key」导致下单报库存不足。
 */
class SyncItemStoreToRedisCommand extends Command
{
    protected $signature = 'goods:sync_item_store_to_redis
                            {--company_id= : 仅同步该企业（items.company_id / distribution_distributor_items.company_id）}
                            {--distributor : 同时同步店铺商品表 distribution_distributor_items（Redis 键为 distributorId_itemId 拼在 item_store: 后）}
                            {--chunk=500 : 每批从数据库读取条数}
                            {--force : 强制覆盖已有 Redis 库存（危险：可能覆盖下单已扣减的值）}';

    protected $description = '从 items 表（及可选的店铺商品表）将库存同步到 Redis item_store:*（默认仅补缺失 key）';

    private const LOCK_KEY = 'lock:goods:sync_item_store_to_redis';
    private const LOCK_TTL = 3600;

    public function handle(): int
    {
        $companyId = $this->option('company_id');
        $withDistributor = (bool) $this->option('distributor');
        $chunk = max(1, (int) $this->option('chunk'));
        $force = (bool) $this->option('force');

        $redis = app('redis');
        if (!$redis->set(self::LOCK_KEY, 1, 'NX', 'EX', self::LOCK_TTL)) {
            $this->error('已有同步任务在执行，请稍后再试');
            return 1;
        }

        try {
            $itemsFilter = [];
            if ($companyId !== null && $companyId !== '') {
                $itemsFilter['company_id'] = $companyId;
            }

            /** @var ItemsRepository $itemsRepository */
            $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);

            $itemWritten = 0;
            $itemSkipped = 0;
            $page = 1;
            do {
                $list = $itemsRepository->getLists(
                    $itemsFilter,
                    'item_id, store',
                    $page,
                    $chunk,
                    ['item_id' => 'ASC']
                );
                if (empty($list)) {
                    break;
                }
                foreach ($list as $row) {
                    $key = (string) ($row['item_id'] ?? '');
                    if ($key === '') {
                        continue;
                    }
                    $store = (int) ($row['store'] ?? 0);
                    if ($this->writeItemStore('item_store:' . $key, $store, $force)) {
                        ++$itemWritten;
                    } else {
                        ++$itemSkipped;
                    }
                }
                ++$page;
            } while (count($list) >= $chunk);

            $this->info(sprintf(
                '已同步总部商品 items：写入 %d，跳过已有 key %d（Redis 键: item_store:{item_id}%s）',
                $itemWritten,
                $itemSkipped,
                $force ? '，--force 覆盖模式' : '，SETNX 仅补缺失'
            ));

            if (!$withDistributor) {
                return 0;
            }

            $distFilter = [];
            if ($companyId !== null && $companyId !== '') {
                $distFilter['company_id'] = $companyId;
            }

            $distRepository = app('registry')->getManager('default')->getRepository(DistributorItems::class);

            $distWritten = 0;
            $distSkipped = 0;
            $page = 1;
            do {
                $list = $distRepository->getList(
                    $distFilter,
                    'item_id, distributor_id, store',
                    $page,
                    $chunk,
                    ['item_id' => 'ASC']
                );
                if (empty($list)) {
                    break;
                }
                foreach ($list as $row) {
                    $itemId = (string) ($row['item_id'] ?? '');
                    $distributorId = (string) ($row['distributor_id'] ?? '0');
                    if ($itemId === '' || $distributorId === '0') {
                        continue;
                    }
                    $store = (int) ($row['store'] ?? 0);
                    $redisKey = 'item_store:' . $distributorId . '_' . $itemId;
                    if ($this->writeItemStore($redisKey, $store, $force)) {
                        ++$distWritten;
                    } else {
                        ++$distSkipped;
                    }
                }
                ++$page;
            } while (count($list) >= $chunk);

            $this->info(sprintf(
                '已同步店铺商品 distribution_distributor_items：写入 %d，跳过已有 key %d（Redis 键: item_store:{distributor_id}_{item_id}%s）',
                $distWritten,
                $distSkipped,
                $force ? '，--force 覆盖模式' : '，SETNX 仅补缺失'
            ));

            return 0;
        } finally {
            $redis->del(self::LOCK_KEY);
        }
    }

    /**
     * @return bool true=已写入，false=因已有 key 跳过
     */
    private function writeItemStore(string $redisKey, int $store, bool $force): bool
    {
        $redis = app('redis');
        if ($force) {
            $redis->set($redisKey, $store);
            return true;
        }

        // SETNX：仅补缺失，避免覆盖下单已扣减的 Redis 库存
        return (bool) $redis->setnx($redisKey, $store);
    }
}
