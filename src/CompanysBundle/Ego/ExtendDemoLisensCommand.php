<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Ego;
use Illuminate\Console\Command;

class ExtendDemoLisensCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'company:extendDemoLicense {company_id}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '延长开发环境授权有效期';

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
        $companyId = $this->argument('company_id');

        if (!$companyId) {
            $this->info('请输入company_id!');
            exit;
        }

        try {
            app('authorization')->extendCompanyDemoLicense($companyId);
            $this->info('已延长15天有效期');
        } catch(\Exception $e){
            $this->info($e->getMessage());
        }
    }
}
