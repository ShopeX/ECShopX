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

class ShopSalesperson extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'transfer:encrypt:shop_salesperson';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '数据加密迁移';
    protected $table = 'shop_salesperson';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // 456353686f7058
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
                ->select(['salesperson_id', 'mobile', 'name'])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->orderBy('salesperson_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];
            if (!$list) {
                break;
            }
            $sql1 = 'UPDATE '.$this->table.' SET mobile = CASE salesperson_id ';
            $sql2 = 'UPDATE '.$this->table.' SET name = CASE salesperson_id ';
            $ids = '';
            foreach ($list as $value) {
                $value = (array) $value;
                $mobile = fixedencrypt($value['mobile'] ?: null);
                $sql1 .= sprintf("WHEN %d THEN '%s' ", $value['salesperson_id'], $mobile ?: '');
                $name = fixedencrypt($value['name'] ?: null);
                $sql2 .= sprintf("WHEN %d THEN '%s' ", $value['salesperson_id'], $name ?: '');
                $ids .= $value['salesperson_id'] . ',';
                if (!$ids) {
                    break;
                }
            }
            if ($ids) {
                $ids = trim($ids, ',');
                $sql1 .= "END WHERE salesperson_id IN ({$ids})";
                Db::update($sql1);
                $sql2 .= "END WHERE salesperson_id IN ({$ids})";
                Db::update($sql2);
            }
            echo '第' . $page . '页，开始id:' . $list[0]->salesperson_id . "\n\r";
            ++$page;
        }
    }
}
