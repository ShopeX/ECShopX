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

class Popularize extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'transfer:encrypt:popularize';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '分销推广数据加密迁移';
    protected $tables = ['popularize_promoter', 'popularize_cash_withdrawal', 'selfservice_registration_record'];

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

    public function popularizePromoterTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['id', 'pmobile'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET pmobile = CASE id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $pmobile = fixedencrypt($value['pmobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $pmobile ?: '');
                $ids .= $value['id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE id IN ({$ids})";
                Db::update($sql1);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->id . "\n\r";
            ++$page;
        }
    }

    public function popularizeCashWithdrawalTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['id', 'mobile'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $mobile ?: '');
                $ids .= $value['id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE id IN ({$ids})";
                Db::update($sql1);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->id . "\n\r";
            ++$page;
        }
    }

    public function selfserviceRegistrationRecordTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['record_id', 'mobile'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('record_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE record_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['record_id'], $mobile ?: '');
                $ids .= $value['record_id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE record_id IN ({$ids})";
                Db::update($sql1);
            }
            echo '第' . $page . '页，开始id:' . $list[0]->record_id . "\n\r";
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
