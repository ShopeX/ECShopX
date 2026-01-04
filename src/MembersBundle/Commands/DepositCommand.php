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

namespace MembersBundle\Commands;

use DepositBundle\Repositories\UserDepositRepository;
use DepositBundle\Services\DepositTrade;
use Illuminate\Console\Command;

class DepositCommand extends Command
{
    /**
     * 命令行执行命令
     * php artisan member:set_deposit  35 888 999999
     * @var string
     */
    protected $signature = 'member:set_deposit {company_id} {user_id} {money}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '更新用户预存款';

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
        $this->info('set_deposit begin');
        $company_id = $this->argument('company_id');
        $user_id = $this->argument('user_id');
        $money = $this->argument('money');
        $isAdd = ($money > 0) ? true : false;
        
        $depositTrade = new DepositTrade();
        $depositTrade->addUserDepositTotal($company_id, $user_id, $money, $isAdd);
        $money = $depositTrade->getUserDepositTotal($company_id, $user_id);
        $this->info('set_deposit success: ' . $money);
        return true;
    }
}
