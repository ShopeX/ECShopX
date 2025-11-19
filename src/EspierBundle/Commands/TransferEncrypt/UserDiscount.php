<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Commands\TransferEncrypt;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UserDiscount extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'transfer:encrypt:userdiscount';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '卡券记录数据加密迁移';
    protected $tables = ['kaquan_user_discount_logs'];

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

    public function kaquanUserDiscountLogsTodo($table)
    {
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($table)
                ->select(['id', 'mobile', 'username'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$table.' SET mobile = CASE id ';
            $sql2 = 'UPDATE '.$table.' SET username = CASE id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $mobile ?: '');
                $username = fixedencrypt($value['username'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['id'], $username ?: '');
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
