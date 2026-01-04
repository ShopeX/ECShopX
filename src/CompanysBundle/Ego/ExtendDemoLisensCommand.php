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
