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

namespace EspierBundle\Commands\TransferEncrypt;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Orders extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'transfer:encrypt:orders';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '订单数据加密迁移';
    protected $tables = ['orders_associations', 'orders_normal_orders', 'deposit_trade', 'trade', 'aftersales'];
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // TS: 53686f704578
        foreach ($this->tables as $table) {
            $func = self::convertUnderline($table).'Todo';
            if (method_exists($this, $func)) {
                $this->$func($table);
            } else {
                echo "表" . $table ."不存在处理方法\n\r";
            }
        }
    }

    public function ordersAssociationsTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['order_id', 'mobile'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('order_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE order_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['order_id'], $mobile ?: '');
                $ids .= $value['order_id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE order_id IN ({$ids})";
                Db::update($sql1);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->order_id . "\n\r";
            ++$page;
        }
    }

    public function ordersNormalOrdersTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['order_id', 'mobile', 'receiver_name', 'receiver_mobile', 'receiver_address'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('order_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE order_id ';
            $sql2 = 'UPDATE '.$table.' SET receiver_name = CASE order_id ';
            $sql3 = 'UPDATE '.$table.' SET receiver_mobile = CASE order_id ';
            $sql4 = 'UPDATE '.$table.' SET receiver_address = CASE order_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['order_id'], $mobile ?: '');
                $receiver_name = fixedencrypt($value['receiver_name'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['order_id'], $receiver_name ?: '');
                $receiver_mobile = fixedencrypt($value['receiver_mobile'] ?: null);
                $sql3 .= sprintf("WHEN %d THEN '%s' ", $value['order_id'], $receiver_mobile ?: '');
                $receiver_address = fixedencrypt($value['receiver_address'] ?: null);
                $sql4 .= sprintf("WHEN %d THEN '%s' ", $value['order_id'], $receiver_address ?: '');
                $ids .= $value['order_id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE order_id IN ({$ids})";
                Db::update($sql1);
                $sql2 .= "END WHERE order_id IN ({$ids})";
                Db::update($sql2);
                $sql3 .= "END WHERE order_id IN ({$ids})";
                Db::update($sql3);
                $sql4 .= "END WHERE order_id IN ({$ids})";
                Db::update($sql4);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->order_id . "\n\r";
            ++$page;
        }
    }

    public function depositTradeTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['deposit_trade_id', 'mobile'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('deposit_trade_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE deposit_trade_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN '%s' THEN '%s' ", $value['deposit_trade_id'], $mobile ?: '');
                $ids .= "'".$value['deposit_trade_id'] . "',";
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE deposit_trade_id IN ({$ids})";
                Db::update($sql1);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->deposit_trade_id . "\n\r";
            ++$page;
        }
    }

    public function tradeTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['trade_id', 'mobile'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('trade_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE trade_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN '%s' THEN '%s' ", $value['trade_id'], $mobile ?: '');
                $ids .= "'".$value['trade_id'] . "',";
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE trade_id IN ({$ids})";
                Db::update($sql1);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->trade_id . "\n\r";
            ++$page;
        }
    }

    public function aftersalesTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['aftersales_bn', 'mobile'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('aftersales_bn', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE aftersales_bn ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['aftersales_bn'], $mobile ?: '');
                $ids .= $value['aftersales_bn'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE aftersales_bn IN ({$ids})";
                Db::update($sql1);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->aftersales_bn . "\n\r";
            ++$page;
        }
    }

    /*
     * 下划线转驼峰
     */
    public static function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }
}
