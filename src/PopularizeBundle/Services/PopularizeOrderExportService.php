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

class PopularizeOrderExportService implements ExportFileInterface
{
    private $title = [
        'user_id' => '用户ID',
        'name' => '姓名',
        'mobile' => '手机号',
        'store_name' => '店铺',
        'order_id' => '订单号',
        'rebate' => '佣金',
        'price' => '商品价格',
        'total_fee' => '订单金额',
        'source' => '来源',
        'brokerage_type' => '层级',
        'is_close' => '状态',
        'title' => '订单内容',
        'created' => '创建时间',
    ];

    // [2024-06-12 19:42:27.338] staging.INFO: :export:brokerage:getLists73:row:array (
    //     'id' => '3',
    //     'brokerage_type' => 'first_level',
    //     'order_id' => '4542464000210524',
    //     'user_id' => '492',
    //     'buy_user_id' => '524',
    //     'order_type' => 'normal',
    //     'source' => 'order',
    //     'company_id' => '5',
    //     'price' => '1',
    //     'commission_type' => 'money',
    //     'rebate' => '0',
    //     'rebate_point' => '0',
    //     'detail' => '{"ratio_type":"order_money","ratio":"10","total_fee":1,"cost_fee":0}',
    //     'is_close' => '1',
    //     'plan_close_time' => '1718084337',
    //     'created' => '1717817786',
    //     'updated' => '1718084341',
    //     'distributor_id' => '36',
    //     'title' => '测试海盐芝士厚乳拿铁',
    //   )  

    public function exportData($filter)
    {
        app('log')->info(':export:brokerage:'.__FUNCTION__.__LINE__.':filter:' . var_export($filter, true));
        
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        
        // $promoterService = new PromoterService();
        // $data = $promoterService->getPromoterList($filter, 1, 1);

        $brokerageService = new BrokerageService();
        $data = $brokerageService->getSalesmanBrokeragelistsBySql($filter, 1 ,1);
        app('log')->info(':export:brokerage:'.__FUNCTION__.__LINE__.':data:' . var_export($data, true));

        $count = $data['total_count'];
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
            // $result = $promoterService->getPromoterList($filter, $i, $limit);
          
            $result = $brokerageService->getSalesmanBrokeragelistsBySql($filter, $limit ,$i);
            app('log')->info(':export:brokerage:'.__FUNCTION__.__LINE__.':filter:' . var_export($filter, true)); 



            if ($result['total_count'] > 0) {
                // $promoterCountService = new PromoterCountService();
                // foreach ($result['list'] as $k => $row) {
                //     app('log')->info(':export:brokerage:'.__FUNCTION__.__LINE__.':row:' . var_export($row, true));

                //     $count = $promoterCountService->getPromoterCount($filter['company_id'], $row['user_id']);
                //     $result['list'][$k] = array_merge($result['list'][$k], $count);
                // }
            }

            $list = $result['list'];
            foreach ($list as $key => $value) {
                $promoterData[$key]['user_id'] = "ID:".$value['user_id'];
                $promoterData[$key]['name'] = $value['name'] ?? ''; 
                $promoterData[$key]['mobile'] = $value['mobile'] ?? ''; 
                $promoterData[$key]['store_name'] = $value['store_name'] ?? ''; 
                $promoterData[$key]['order_id'] = "NO:".$value['order_id']; 
                $promoterData[$key]['rebate'] = bcdiv($value['rebate'], 100, 2); 
                $promoterData[$key]['price'] = bcdiv($value['price'], 100, 2); //$value['total_fee'] ?? ''; 
                $promoterData[$key]['total_fee'] = bcdiv($value['total_fee'], 100, 2); //$value['total_fee'] ?? ''; 
                $promoterData[$key]['source'] = $source_arr[$value['source']] ?? ' '  ;
                $promoterData[$key]['brokerage_type'] = $level_arr[$value['brokerage_type']]??' ' ;
                $promoterData[$key]['is_close'] = $value['is_close'] ?  '已结算' : '未结算';  
                $promoterData[$key]['title'] = $value['title'] ?? ''; 
                $promoterData[$key]['created'] = date( "Y-m-d H:i:s",$value['created'] ); 

                app('log')->info(':export:popu-brokerage:'.__FUNCTION__.__LINE__.':value-row:' . json_encode($value)); 

            }
            yield $promoterData;
        }
    }
}
