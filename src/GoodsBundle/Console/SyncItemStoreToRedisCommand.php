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
 * 不触发 ItemStoreUpdateEvent，适合批量修复「库里有库存、Redis 无 key」导致下单报库存不足。
 */
class SyncItemStoreToRedisCommand extends Command
{
    protected $signature = 'goods:sync_item_store_to_redis
                            {--company_id= : 仅同步该企业（items.company_id / distribution_distributor_items.company_id）}
                            {--distributor : 同时同步店铺商品表 distribution_distributor_items（Redis 键为 distributorId_itemId 拼在 item_store: 后）}
                            {--chunk=500 : 每批从数据库读取条数}';

    protected $description = '从 items 表（及可选的店铺商品表）将库存同步到 Redis item_store:*';

    public function handle(): int
    {
        $companyId = $this->option('company_id');
        $withDistributor = (bool) $this->option('distributor');
        $chunk = max(1, (int) $this->option('chunk'));

        $itemsFilter = [];
        if ($companyId !== null && $companyId !== '') {
            $itemsFilter['company_id'] = $companyId;
        }

        /** @var ItemsRepository $itemsRepository */
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);

        $itemCount = 0;
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
                app('redis')->set('item_store:' . $key, $store);
                ++$itemCount;
            }
            ++$page;
        } while (count($list) >= $chunk);

        $this->info(sprintf('已同步总部商品 items 条数: %d（Redis 键: item_store:{item_id}）', $itemCount));

        if (!$withDistributor) {
            return 0;
        }

        $distFilter = [];
        if ($companyId !== null && $companyId !== '') {
            $distFilter['company_id'] = $companyId;
        }

        $distRepository = app('registry')->getManager('default')->getRepository(DistributorItems::class);

        $distCount = 0;
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
                app('redis')->set($redisKey, $store);
                ++$distCount;
            }
            ++$page;
        } while (count($list) >= $chunk);

        $this->info(sprintf('已同步店铺商品 distribution_distributor_items 条数: %d（Redis 键: item_store:{distributor_id}_{item_id}）', $distCount));

        return 0;
    }
}
