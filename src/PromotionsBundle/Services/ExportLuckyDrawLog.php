<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Services;


use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use PromotionsBundle\Services\TurntableService;

class ExportLuckyDrawLog implements ExportFileInterface
{
    private $title = [
        'user_id' => '用户id',
        'user_card_code' => '会员编码',
        'mobile' => '手机号',
        'prize_type'=>'奖品类型',
        'prize_title' => '获取奖品',
        'created'=>'中奖时间',

//        'tradeState' => '交易状态',
    ];

    public function exportData($filter)
    {
        $tradeService = new TurntableService();
        $res = $tradeService->getLuckyDrawLogByActId($filter['activity_id']);
        $count = $res['total_count'] ?? 0;
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis') . $filter['company_id'] . "活动统计导出";
        $title = $this->title;
        $orderList = $this->getLists($filter, $count);
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        $service = new TurntableService();

        $limit = 100;

        $fileNum = ceil($count / $limit);
        for ($j = 1; $j <= $fileNum; $j++) {
            $whiteData = [];
            $data = $service->getLuckyDrawLogByActId($filter['activity_id'], $j, $limit);
            foreach ($data['list'] as $key => $value) {
                $tmp= [];
                $tmp['user_id'] = $value['user_id'];
                $tmp['user_card_code'] = $value['user_card_code'];
                $tmp['mobile'] = $value['mobile'];
                if($value['prize_type'] === 'coupon'){
                    $tmp['prize_type'] = '优惠券';
                }
                if($value['prize_type'] === 'coupons'){
                    $tmp['prize_type'] = '券包';
                }
                if($value['prize_type'] === 'points'){
                    $tmp['prize_type'] = '积分';
                }
                if($value['prize_type'] === 'thanks'){
                    $tmp['prize_type'] = '谢谢惠顾';
                }

                $tmp['prize_title'] = $value['prize_title'];
                $tmp['created'] = date('Y-m-d H:i:s', $value['created']);
                $whiteData[] = $tmp;
            }
            yield $whiteData;
        }
    }
}

