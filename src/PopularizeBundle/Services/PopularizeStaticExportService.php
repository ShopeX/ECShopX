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

namespace PopularizeBundle\Services;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

class PopularizeStaticExportService implements ExportFileInterface
{
    private $title = [
        'user_id' => '用户ID',
        'store_name' => '店铺',
        'mobile' => '手机号',
        'order_id' => '订单号',
        'rebate_sum' => '佣金',
        'rebate_sum_noclose' => '未结算佣金',
        'price_sum' => '分佣商品总价',
        'total_fee' => '订单金额',
    ];
    // $promoterData[$key]['user_id'] = "ID:".$value['user_id'];
    // $promoterData[$key]['store_name'] = $value['store_name'] ?? ''; 
    // $promoterData[$key]['mobile'] = $value['mobile'] ?? ''; 
    // $promoterData[$key]['order_id'] = "NO:".$value['order_id']; 
    // $promoterData[$key]['rebate_sum'] = bcdiv($value['rebate_sum'], 100, 2); 
    // $promoterData[$key]['rebate_sum_noclose'] = bcdiv($value['rebate_sum_noclose'], 100, 2); 
    // $promoterData[$key]['price_sum'] = bcdiv($value['price_sum'], 100, 2); 
    // $promoterData[$key]['total_fee'] = bcdiv($value['total_fee'], 100, 2); 


    public function exportData($filter)
    {
        app('log')->info(':export:brokerage:'.__FUNCTION__.__LINE__.':filter:' . var_export($filter, true));
        
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        
        // $promoterService = new PromoterService();
        // $data = $promoterService->getPromoterList($filter, 1, 1);

        $brokerageService = new BrokerageService();
        $limit = 1;
        $page  = 1;
        $data = $brokerageService->getSalesmanBrokerageCountList($filter, $limit, $page );

        // $data = $brokerageService->getSalesmanBrokeragelistsBySql($filter, 1 ,1);
        app('log')->info(':export:brokerage:'.__FUNCTION__.__LINE__.':data:' . var_export($data, true));

        $count = count($data);
        app('log')->info(':export:brokerage:'.__FUNCTION__.__LINE__.':count:' . var_export($count, true));

        if ($count <= 0) {
            return [];
        }
        $isGetSkuList = [];
        $fileName = date('YmdHis')."popularize";
        $dataList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $dataList);
        return $result;
    }

    public function getLists($filter, $count, $datapassBlock)
    {
        $brokerageService = new BrokerageService();

        $level_arr  = array(
            'first_level'=> '一级',
            'second_level'=> '二级'
        );
        $source_arr = array(
            'order'=> '提成',
            'order_team'=> '津贴'
        );
        $title = $this->title;
        $limit = 500;
        $totalPage = ceil($count / $limit);
        // $promoterService = new PromoterService();
        for ($i = 1; $i <= $totalPage; $i++) {
            $promoterData = [];
            // getSalesmanBrokeragelistsBySql
            $list = $brokerageService->getSalesmanBrokerageCountList($filter, $limit ,$i);
            app('log')->info(':export:brokerage-static:'.__FUNCTION__.__LINE__.':page:i::::::' . json_encode($i)); 
            app('log')->info(':export:brokerage-static:'.__FUNCTION__.__LINE__.':filter:' . json_encode($filter)); 
            app('log')->info(':export:brokerage-static:'.__FUNCTION__.__LINE__.':list:' . json_encode($list)); 

            // if ($result['total_count'] > 0) {
            //     // $promoterCountService = new PromoterCountService();
            //     // foreach ($result['list'] as $k => $row) {
            //     //     app('log')->info(':export:brokerage:'.__FUNCTION__.__LINE__.':row:' . var_export($row, true));

            //     //     $count = $promoterCountService->getPromoterCount($filter['company_id'], $row['user_id']);
            //     //     $result['list'][$k] = array_merge($result['list'][$k], $count);
            //     // }
            // }

            // $list = $result['list'];
            foreach ($list as $key => $value) {
                // {
                //     "order_id": "4548591000020534",
                //     "user_id": "494",
                //     "rebate_sum_noclose": "0",
                //     "rebate_sum": "5000",
                //     "price_sum": "102",
                //     "total_fee": "2",
                //     "username": "cx",
                //     "mobile": "13469793903",
                //     "store_name": "优品咖啡（松江万达店）",
                //     "distributor_id": "36"
                // },
                $promoterData[$key]['user_id'] = "ID:".$value['user_id'];
                $promoterData[$key]['store_name'] = $value['store_name'] ?? ''; 
                $promoterData[$key]['mobile'] = $value['mobile'] ?? ''; 
                $promoterData[$key]['order_id'] = "NO:".$value['order_id']; 
                $promoterData[$key]['rebate_sum'] = bcdiv($value['rebate_sum'], 100, 2); 
                $promoterData[$key]['rebate_sum_noclose'] = bcdiv($value['rebate_sum_noclose'], 100, 2); 
                $promoterData[$key]['price_sum'] = bcdiv($value['price_sum'], 100, 2); 
                $promoterData[$key]['total_fee'] = bcdiv($value['total_fee'], 100, 2); 

            }
            yield $promoterData;
        }
    }
}
