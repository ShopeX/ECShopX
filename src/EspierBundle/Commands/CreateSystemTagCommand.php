<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
