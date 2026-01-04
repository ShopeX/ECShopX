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

namespace DataCubeBundle\Services;

use MembersBundle\Services\WechatUserService;
use DataCubeBundle\Services\UVBloomFilterService;
class TrackService
{
    private $prefix = 'datecube_tracklog';

    public function __construct()
    {
    }

    // 添加浏览人数日志
    public function addViewNum($params)
    {
        if (!$params['company_id'] || !$params['monitor_id'] || !$params['source_id']) {
            return false;
        }

        $date = date('Ymd');

        // pv
        $pv_key = $this->prefix . ':viewnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':page_view:'.$params['source_id'];
        // uv
        $uv_key = $this->prefix . ':viewnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':unique_visitor:' . $params['source_id'];
        // 会员访客数
        $mv_key = $this->prefix . ':viewnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':member_visitor:' . $params['source_id'];

        app('redis')->connection('datacube')->hincrby($pv_key, $date, 1);

        //布隆过滤器，用来判断所有UV
        $bloomFilter = new UVBloomFilterService($params['monitor_id'], $params['source_id']);
        $isNewVisitor = $bloomFilter->checkAndAdd($params['open_id']);
        if ($isNewVisitor) {
            app('redis')->connection('datacube')->hincrby($uv_key, $date, 1);

            //判断openid 是否在我们系统中存在，如果不存在，则表示新客
            $wechatUserService = new WechatUserService();
            $userInfo = $wechatUserService->getSimpleUser(['open_id' => $params['open_id'], 'company_id' => $params['company_id']]);
            if ($userInfo) {
                app('redis')->connection('datacube')->hincrby($mv_key, $date, 1);
            }
        }
    }

    // 添加购买人数日志
    public function addEntriesNum($params)
    {
        if (!$params['company_id'] || !$params['monitor_id'] || !$params['source_id']) {
            return false;
        }

        $date = date('Ymd');

        $list_key = $this->prefix . ':entriesnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];
        $total_key = $this->prefix . ':entriesnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        app('redis')->connection('datacube')->hincrby($list_key, $date, 1);
        app('redis')->connection('datacube')->hincrby($total_key, $date, 1);
    }

    // 添加注册人数日志
    public function addRegisterNum($params)
    {
        if (!$params['company_id'] || !$params['monitor_id'] || !$params['source_id']) {
            return false;
        }

        $date = date('Ymd');

        $list_key = $this->prefix . ':registernum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];
        $total_key = $this->prefix . ':registernum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        app('redis')->connection('datacube')->hincrby($list_key, $date, 1);
        app('redis')->connection('datacube')->hincrby($total_key, $date, 1);
    }

    // 获取购买人数数量
    public function getEntriesNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $list_key = $this->prefix . ':entriesnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($list_key, $fields));

        return $total ?: 0;
    }

    // 获取注册人数数量
    public function getRegisterNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $list_key = $this->prefix . ':registernum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($list_key, $fields));

        return $total ?: 0;
    }

    // 获取总的购买人数
    public function getTotalEntriesNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $total_key = $this->prefix . ':entriesnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($total_key, $fields));

        return $total ?: 0;
    }

    // 获取总的注册人数
    public function getTotalRegisterNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $total_key = $this->prefix . ':registernum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($total_key, $fields));

        return $total ?: 0;
    }

    // 获取单个sourceid的PV
    public function getPageView($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $total_key = $this->prefix . ':viewnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':page_view:'.$params['source_id'];

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) { 
                return date("Ymd", $v);
            },
            $_time
        );
        
        $total = array_sum(app('redis')->connection('datacube')->hmget($total_key, $fields));
        return $total ?: 0;
    }

    // 获取单个sourceid的UV
    public function getUniqueVisitor($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);      
        
        $total_key = $this->prefix . ':viewnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':unique_visitor:' . $params['source_id'];

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) { 
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($total_key, $fields));   

        return $total ?: 0;
    }

    // 获取单个sourceid的会员访客
    public function getMemberVisitor($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $total_key = $this->prefix . ':viewnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':member_visitor:' . $params['source_id'];

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($total_key, $fields));

        return $total ?: 0;
    }

    // 获取单个sourceid的支付人数
    public function getPaiedOrderAggs($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);
        $starttime = strtotime($selectDate['start'].' 00:00:00');
        $endtime = strtotime($selectDate['stop'].' 23:59:59');
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('sum(total_fee) as pay_amount,count(distinct user_id) as pay_num')
            ->from('orders_normal_orders')
            ->where($criteria->expr()->eq('company_id', $params['company_id']))
            ->andWhere($criteria->expr()->eq('monitor_id', $params['monitor_id']))
            ->andWhere($criteria->expr()->eq('source_id', $params['source_id']))
            ->andWhere($criteria->expr()->gte('create_time', $starttime))
            ->andWhere($criteria->expr()->lte('create_time', $endtime))
            ->andWhere($criteria->expr()->eq('pay_status', $criteria->expr()->literal('PAYED')));
        $list = $criteria->execute()->fetchAll();
        
        $result['pay_amount'] = 0;
        $result['pay_num'] = 0;
        if ($list) {
            $result['pay_amount'] = $list[0]['pay_amount'] ?: 0;
            $result['pay_num'] = $list[0]['pay_num'] ?: 0;
        }

        return $result;
    }

    public function __checkTime($date_type, $filter = null)
    {
        switch ($date_type) {
            case 'today':
                return [
                    'start' => date('Ymd'),
                    'stop' => date('Ymd'),
                ];
                break;
            case 'yesterday':
                return [
                    'start' => date('Ymd', strtotime('-1 day')),
                    'stop' => date('Ymd', strtotime('-1 day')),
                ];
                break;
            case 'before7days':
                return [
                    'start' => date('Ymd', strtotime('-7 day')),
                    'stop' => date('Ymd', strtotime('-1 day')),
                ];
                break;
            case 'before30days':
                return [
                    'start' => date('Ymd', strtotime('-30 day')),
                    'stop' => date('Ymd', strtotime('-1 day')),
                ];
                break;
            case 'beforemonth':
                return [
                    'start' => date('Ymd', strtotime(date('Y-m-01') . ' -1 month')),
                    'stop' => date('Ymd', strtotime(date('Y-m-01') . ' -1 day')),
                ];
                break;
            case 'custom':
                return [
                    'start' => date('Ymd', strtotime($filter['start'])),
                    'stop' => date('Ymd', strtotime($filter['stop'])),
                ];
                break;
        }
    }
}
