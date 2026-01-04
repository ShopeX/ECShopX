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

class Members extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'transfer:encrypt:members';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '会员数据加密迁移';
    protected $tables = ['members', 'members_info', 'members_whitelist', 'kaquan_vip_grade_order'];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function membersTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['user_id', 'mobile'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('user_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE user_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['user_id'], $mobile ?: '');
                $ids .= $value['user_id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE user_id IN ({$ids})";
                Db::update($sql1);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->user_id . "\n\r";
            ++$page;
        }
    }

    public function membersInfoTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['user_id', 'username'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('user_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET username = CASE user_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $username = fixedencrypt($value['username'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['user_id'], $username ?: '');
                $ids .= $value['user_id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE user_id IN ({$ids})";
                Db::update($sql1);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->user_id . "\n\r";
            ++$page;
        }
    }

    public function membersWechatusersInfoTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['unionid', 'nickname'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('company_id', 'DESC')
                ->orderBy('unionid', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET nickname = CASE unionid ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $nickname = fixedencrypt($value['nickname'] ?: null);
                $sql1 .= sprintf("WHEN '%s' THEN '%s' ", $value['unionid'], $nickname ?: '');
                $ids .= "'".$value['unionid'] . "',";
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE unionid IN ({$ids})";
                Db::update($sql1);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->unionid . "\n\r";
            ++$page;
        }
    }

    public function membersWhitelistTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['whitelist_id', 'mobile', 'name'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('whitelist_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE whitelist_id ';
            $sql2 = 'UPDATE '.$table.' SET name = CASE whitelist_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['whitelist_id'], $mobile ?: '');
                $name = fixedencrypt($value['name'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['whitelist_id'], $name ?: '');
                $ids .= $value['whitelist_id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE whitelist_id IN ({$ids})";
                Db::update($sql1);
                $sql2 .= "END WHERE whitelist_id IN ({$ids})";
                Db::update($sql2);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->whitelist_id . "\n\r";
            ++$page;
        }
    }

    public function kaquanVipGradeorderTodo($table)
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
