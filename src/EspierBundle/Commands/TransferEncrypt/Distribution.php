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

class Distribution extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'transfer:encrypt:distribution';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '店铺数据加密迁移';

    protected $tables = ['distribution_distributor', 'distributor_aftersales_address'];


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
        foreach ($this->tables as $table) {
            $func = self::convertUnderline($table).'Todo';
            if (method_exists($this, $func)) {
                $this->$func($table);
            } else {
                echo "表" . $table ."不存在处理方法\n\r";
            }
        }
    }

    public function distributionDistributorTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['distributor_id', 'mobile', 'contact'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('distributor_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE distributor_id ';
            $sql2 = 'UPDATE '.$table.' SET contact = CASE distributor_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['distributor_id'], $mobile ?: '');
                $contact = fixedencrypt($value['contact'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['distributor_id'], $contact ?: '');
                $ids .= $value['distributor_id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE distributor_id IN ({$ids})";
                Db::update($sql1);
                $sql2 .= "END WHERE distributor_id IN ({$ids})";
                Db::update($sql2);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->distributor_id . "\n\r";
            ++$page;
        }
    }

    public function distributorAftersalesAddressTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['address_id', 'mobile', 'contact'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('address_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE address_id ';
            $sql2 = 'UPDATE '.$table.' SET contact = CASE address_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['address_id'], $mobile ?: '');
                $contact = fixedencrypt($value['contact'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['address_id'], $contact ?: '');
                $ids .= $value['address_id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE address_id IN ({$ids})";
                Db::update($sql1);
                $sql2 .= "END WHERE address_id IN ({$ids})";
                Db::update($sql2);
            }
            echo '第' . $page . '页，开始id:' . $list[0]->address_id . "\n\r";
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
