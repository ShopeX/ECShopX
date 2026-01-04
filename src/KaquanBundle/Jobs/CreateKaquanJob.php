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

namespace KaquanBundle\Jobs;

use EspierBundle\Jobs\Job;
use GoodsBundle\Services\ItemsTagsService;
use GoodsBundle\Services\ItemsService;
use KaquanBundle\Entities\RelItems;

class CreateKaquanJob extends Job
{
    protected $dataInfo = [];

    public function __construct($dataInfo)
    {
        $this->dataInfo = $dataInfo;
    }

    /**
     * 运行任务。
     *
     * @return bool
     */
    public function handle()
    {
        $dataInfo = $this->dataInfo;
        $companyId = $dataInfo['company_id'];
        $cardId = $dataInfo['card_id'];
        $conn = app('registry')->getConnection('default');
        $relItemsRepository = app('registry')->getManager('default')->getRepository(RelItems::class);

        $filter = [
            'company_id' => $companyId,
            'item_type' => 'normal',
            'special_type' => ['normal', 'drug'],
            'is_gift' => false,
            'is_default' => true,
        ];

        if (isset($dataInfo['is_distributor']) && $dataInfo['is_distributor'] == 'true') {
            $filter['distributor_id'] = $dataInfo['distributor_id'][0];
        }

        $isQuery = false;
        if ($dataInfo['use_bound'] == 2 && $dataInfo['item_category']) {
            $isQuery = true;
            $filter['item_category'] = $dataInfo['item_category'];
        }
        if ($dataInfo['use_bound'] == 3 && $dataInfo['tag_ids']) {
            $isQuery = true;
            $filter['tag_id'] = array_filter(explode(',', $dataInfo['tag_ids']));
        }
        if ($dataInfo['use_bound'] == 4 && $dataInfo['brand_ids']) {
            $isQuery = true;
            $filter['brand_id'] = array_filter(explode(',', $dataInfo['brand_ids']));
        }

        if (!$isQuery) {
            return true;
        }

        $page = 1;
        $pageSize = 1000;
        $processedItemIds = [];
        $conn->beginTransaction();
        try {
            $relItemsRepository->deleteQuick(['card_id' => $cardId, 'company_id' => $companyId]);

            while (true) {
                $result = $this->getItemsList($filter, $page, $pageSize);
                if (!$result) {
                    break;
                }
                $page++;
                $insertData = [];
                foreach ($result as $v) {
                    if (isset($processedItemIds[$v['item_id']])) {
                        continue;
                    } else {
                        $processedItemIds[$v['item_id']] = true; //防止标签里的商品重复
                    }
                    $rowData = [
                        'item_id' => $v['item_id'],
                        'is_show' => 0,
                        'company_id' => $companyId,
                        'card_id' => $cardId,
                        'item_type' => isset($dataInfo['item_type']) ? $dataInfo['item_type'] : 'normal',
                    ];
                    $insertData[] = $rowData;
                    if (count($insertData) >= $pageSize) {
                        $relItemsRepository->createQuick($insertData);
                        $insertData = [];
                    }
                }
                if ($insertData) {
                    $relItemsRepository->createQuick($insertData);
                }
            }

            //将最后一条更新为 is_show = true
            if ($rowData) {
                $updateData = [
                    'is_show' => 1,
                ];
                $filter = [
                    'item_id' => $rowData['item_id'],
                    'card_id' => $cardId,
                    'company_id' => $companyId,
                ];
                $relItemsRepository->updateOneBy($filter, $updateData);
            }

            $conn->commit();
        } catch (\Exception $e) {
            app('log')->error('CreateKaquanJob error =>:' . $e->getMessage());
            $conn->rollback();
            //throw $e;
        }

        return true;
    }

    //根据筛选条件获取商品 ID
    public function getItemsList($filter, $page = 1, $pageSize = 500)
    {
        //标签筛选
        if (isset($filter['tag_id']) && $filter['tag_id']) {
            $tagFilter = [
                'company_id' => $filter['company_id'],
                'tag_id' => $filter['tag_id']
            ];
            if (isset($filter['item_id']) && $filter['item_id']) {
                $tagFilter['item_id'] = $filter['item_id'];
            }

            $itemsTagsService = new ItemsTagsService();
            $result = $itemsTagsService->getItemsByTagidsLimit($tagFilter, $page, $pageSize);
            return $result['list'];
        }

        //品牌，分类筛选
        $itemsService = new ItemsService();
        $result = $itemsService->getItemsList($filter, $page, $pageSize);
        return $result['list'];
    }
}
