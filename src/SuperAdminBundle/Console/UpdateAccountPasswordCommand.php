<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SuperAdminBundle\Console;

use Illuminate\Console\Command;
use SuperAdminBundle\Services\AccountsService;

class UpdateAccountPasswordCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'account:updatePassword {password? : 自定义密码}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '重置管理员密码';

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
        $password = $this->argument('password');

        if (!$password) {
            $this->info('请输入密码参数!');
            exit;
        }

        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('*')->from('super_admin_accounts')
            ->where($qb->expr()->eq('super', 1));
        $list = $qb->execute()->fetchAll();

        $accountsService = new AccountsService();
        $accountsService->updateAccountPassword(['password' => $password], ['account_id' => $list[0]['account_id']]);

        $this->info('密码重置成功');
    }
}
