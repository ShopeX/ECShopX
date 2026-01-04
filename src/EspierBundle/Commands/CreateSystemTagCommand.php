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

namespace EspierBundle\Commands;

use Illuminate\Console\Command;

class CreateSystemTagCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'company:addSystemStaffTag  {company_id? }';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '账号开通时，创建会员系统tag 员工: 参数company_id';

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
        $conn = app('registry')->getConnection('default');
        $companyId = $this->argument('company_id');
        if (!$companyId) {
            $companyList = $conn->fetchAll("select company_id from companys");
            $companyIds = array_column($companyList, 'company_id');
        } else {
            $companyIds[] = $companyId;
        }
        foreach ($companyIds as $companyId) {
            $companyList = $conn->fetchAll("select tag_id from members_tags where company_id=".$companyId." and source='staff'");
            if ($companyList) {
                continue;
            }
            $data = [
                'company_id' => $companyId,
                'distributor_id' => 0,
                'tag_name' => '员工',
                'tag_color' => '#ff1939',
                'font_color' => '#ffffff',
                'description' => '用于标识会员是否是员工身份',
                'created' => time(),
                'updated' => time(),
                'source' => 'staff',
            ];
            $conn->insert('members_tags', $data);
        }
    }
}
