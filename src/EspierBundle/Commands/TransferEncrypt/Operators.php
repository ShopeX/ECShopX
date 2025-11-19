<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Commands\TransferEncrypt;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Operators extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'transfer:encrypt:operators';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '数据加密迁移';
    protected $table = 'operators';

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
        // todo
        $page = 1;
        $pageSize = 500;
        while (true) {
            $list = Db::table($this->table)
                ->select(['operator_id', 'mobile', 'contact'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('operator_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$this->table.' SET mobile = CASE operator_id ';
            $sql2 = 'UPDATE '.$this->table.' SET contact = CASE operator_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['operator_id'], $mobile ?: '');
                $contact = fixedencrypt($value['contact'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['operator_id'], $contact ?: '');
                $ids .= $value['operator_id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE operator_id IN ({$ids})";
                Db::update($sql1);
                $sql2 .= "END WHERE operator_id IN ({$ids})";
                Db::update($sql2);
            }
            echo '第' . $page . '页，开始id:' . $list[0]->operator_id . "\n\r";
            ++$page;
        }
    }
}
