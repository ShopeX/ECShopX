<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
