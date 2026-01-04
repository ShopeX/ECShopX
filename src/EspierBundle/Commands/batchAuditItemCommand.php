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

namespace EspierBundle\Commands;

use GoodsBundle\Entities\Items;
use GoodsBundle\Services\ItemsService;
use SupplierBundle\Services\SupplierItemsService;
use Illuminate\Console\Command;
use swoole_websocket_server;
use EspierBundle\Services\WebSocketService;
use EspierBundle\Interfaces\WebSocketInterface;

class batchAuditItemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'item:batchAudit {company_id? }';



    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '批量审核状态为审核中的商品';



    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // $companyId = $this->argument('company_id');
        // $supplierItemsService = new SupplierItemsService();
        // $itemsService = new ItemsService();
        // $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        // $filter = ['audit_status' => 'processing', 'company_id' => $companyId];
        // $pageSize = 100;
        // $totalCount = $supplierItemsService->repository->count($filter);
        // echo "共有：$totalCount 个商品，开始批量审核\n";
        // $totalPage = ceil($totalCount / $pageSize);
        // for($i=1; $i <= $totalPage; $i++){
        //     echo "第 $i 页，开始批量审核\n";
        //     $itemLists = $supplierItemsService->repository->getLists($filter,'*',1,$pageSize);
        //     if(empty($itemLists)){
        //         break;
        //     }
        //     foreach ($itemLists as $item){
        //         $updateDate = ['approve_status'=>'onsale','audit_status'=>'approved','is_market'=>1,'audit_date'=>time()];
        //         $supplierItemsService->repository->updateItemsPriceStoreStatus(['item_id'=>$item['item_id']], $updateDate);
        //         $item = array_merge($item,$updateDate);
        //         $itemInfo = $itemsRepository->get($item['item_id']);
        //         if($itemInfo){
        //             continue;
        //         }
        //         $itemsRepository->insert($item);
        //         $itemsService->syncGoods($companyId,$item['default_item_id']);
        //     }
        // }
    }

}
