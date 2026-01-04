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

class Adapay extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'transfer:encrypt:adapay';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = 'adapay数据加密迁移';
    protected $tables = ['adapay_member', 'adapay_settle_account', 'adapay_corp_member', 'adapay_merchant_entry'];

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

    public function adapayMemberTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['id', 'tel_no', 'user_name', 'cert_id'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET tel_no = CASE id ';
            $sql2 = 'UPDATE '.$table.' SET user_name = CASE id ';
            $sql3 = 'UPDATE '.$table.' SET cert_id = CASE id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $tel_no = fixedencrypt($value['tel_no'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $tel_no ?: '');
                $user_name = fixedencrypt($value['user_name'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $user_name ?: '');
                $cert_id = fixedencrypt($value['cert_id'] ?: null);
                $sql3 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $cert_id ?: '');
                $ids .= $value['id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE id IN ({$ids})";
                Db::update($sql1);
                $sql2 .= "END WHERE id IN ({$ids})";
                Db::update($sql2);
                $sql3 .= "END WHERE id IN ({$ids})";
                Db::update($sql3);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->id . "\n\r";
            ++$page;
        }
    }

    public function adapaySettleAccountTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['id', 'tel_no', 'card_name', 'cert_id', 'card_id'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET tel_no = CASE id ';
            $sql2 = 'UPDATE '.$table.' SET card_name = CASE id ';
            $sql3 = 'UPDATE '.$table.' SET cert_id = CASE id ';
            $sql4 = 'UPDATE '.$table.' SET card_id = CASE id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $tel_no = fixedencrypt($value['tel_no'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $tel_no ?: '');
                $card_name = fixedencrypt($value['card_name'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $card_name ?: '');
                $cert_id = fixedencrypt($value['cert_id'] ?: null);
                $sql3 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $cert_id ?: '');
                $card_id = fixedencrypt($value['card_id'] ?: null);
                $sql4 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $card_id ?: '');
                $ids .= $value['id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE id IN ({$ids})";
                Db::update($sql1);
                $sql2 .= "END WHERE id IN ({$ids})";
                Db::update($sql2);
                $sql3 .= "END WHERE id IN ({$ids})";
                Db::update($sql3);
                $sql4 .= "END WHERE id IN ({$ids})";
                Db::update($sql4);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->id . "\n\r";
            ++$page;
        }
    }

    public function adapayCorpMemberTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['id', 'legal_person', 'legal_cert_id', 'legal_mp', 'card_no'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET legal_person = CASE id ';
            $sql2 = 'UPDATE '.$table.' SET legal_cert_id = CASE id ';
            $sql3 = 'UPDATE '.$table.' SET legal_mp = CASE id ';
            $sql4 = 'UPDATE '.$table.' SET card_no = CASE id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $legal_person = fixedencrypt($value['legal_person'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $legal_person ?: '');
                $legal_cert_id = fixedencrypt($value['legal_cert_id'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $legal_cert_id ?: '');
                $legal_mp = fixedencrypt($value['legal_mp'] ?: null);
                $sql3 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $legal_mp ?: '');
                $card_no = fixedencrypt($value['card_no'] ?: null);
                $sql4 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $card_no ?: '');
                $ids .= $value['id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE id IN ({$ids})";
                Db::update($sql1);
                $sql2 .= "END WHERE id IN ({$ids})";
                Db::update($sql2);
                $sql3 .= "END WHERE id IN ({$ids})";
                Db::update($sql3);
                $sql4 .= "END WHERE id IN ({$ids})";
                Db::update($sql4);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->id . "\n\r";
            ++$page;
        }
    }

    public function adapayMerchantEntryTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['id', 'cust_tel', 'legal_name', 'legal_idno', 'legal_mp', 'usr_phone', 'cont_name', 'cont_phone', 'card_id_mask', 'card_name'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET cust_tel = CASE id ';
            $sql2 = 'UPDATE '.$table.' SET legal_name = CASE id ';
            $sql3 = 'UPDATE '.$table.' SET legal_idno = CASE id ';
            $sql4 = 'UPDATE '.$table.' SET legal_mp = CASE id ';
            $sql5 = 'UPDATE '.$table.' SET usr_phone = CASE id ';
            $sql6 = 'UPDATE '.$table.' SET cont_name = CASE id ';
            $sql7 = 'UPDATE '.$table.' SET cont_phone = CASE id ';
            $sql8 = 'UPDATE '.$table.' SET card_id_mask = CASE id ';
            $sql9 = 'UPDATE '.$table.' SET card_name = CASE id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $cust_tel = fixedencrypt($value['cust_tel'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $cust_tel ?: '');
                $legal_name = fixedencrypt($value['legal_name'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $legal_name ?: '');
                $legal_idno = fixedencrypt($value['legal_idno'] ?: null);
                $sql3 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $legal_idno ?: '');
                $legal_mp = fixedencrypt($value['legal_mp'] ?: null);
                $sql4 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $legal_mp ?: '');
                $usr_phone = fixedencrypt($value['usr_phone'] ?: null);
                $sql5 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $usr_phone ?: '');
                $cont_name = fixedencrypt($value['cont_name'] ?: null);
                $sql6 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $cont_name ?: '');
                $cont_phone = fixedencrypt($value['cont_phone'] ?: null);
                $sql7 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $cont_phone ?: '');
                $card_id_mask = fixedencrypt($value['card_id_mask'] ?: null);
                $sql8 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $card_id_mask ?: '');
                $card_name = fixedencrypt($value['card_name'] ?: null);
                $sql9 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $card_name ?: '');
                $ids .= $value['id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE id IN ({$ids})";
                Db::update($sql1);
                $sql2 .= "END WHERE id IN ({$ids})";
                Db::update($sql2);
                $sql3 .= "END WHERE id IN ({$ids})";
                Db::update($sql3);
                $sql4 .= "END WHERE id IN ({$ids})";
                Db::update($sql4);
                $sql5 .= "END WHERE id IN ({$ids})";
                Db::update($sql5);
                $sql6 .= "END WHERE id IN ({$ids})";
                Db::update($sql6);
                $sql7 .= "END WHERE id IN ({$ids})";
                Db::update($sql7);
                $sql8 .= "END WHERE id IN ({$ids})";
                Db::update($sql8);
                $sql9 .= "END WHERE id IN ({$ids})";
                Db::update($sql9);
            }
            echo '表' . $table . ',第' . $page . '页，开始id:' . $list[0]->id . "\n\r";
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
