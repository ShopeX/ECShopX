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

namespace CompanysBundle\Console;

use Illuminate\Console\Command;
use CompanysBundle\Repositories\OperatorsRepository;

class InitAdminPasswordCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'account:init-admin-password 
                            {password : 管理员密码}
                            {--company_id= : 公司ID（可选，不填则使用配置文件中的system_companys_id）}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '初始化admin账号密码';

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
        $companyId = $this->option('company_id');
        
        // 如果没有提供 company_id，使用配置文件中的值
        if (empty($companyId)) {
            $companyId = config('common.system_companys_id', 1);
            $this->info("未指定 company_id，使用配置文件中的值: {$companyId}");
        } else {
            $companyId = (int)$companyId;
            $this->info("使用指定的 company_id: {$companyId}");
        }

        $this->info("开始初始化 admin 账号密码...");

        // 获取 Repository
        $operatorsRepository = app('registry')->getManager('default')->getRepository(\CompanysBundle\Entities\Operators::class);
        
        // 获取数据库连接
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            // 检查 admin 账号是否已存在
            $operator = $operatorsRepository->getInfo([
                'login_name' => 'admin',
                'operator_type' => 'admin'
            ]);

            if (empty($operator)) {
                // 账号不存在，创建新账号
                $this->info("admin 账号不存在，正在创建新账号...");
                
                $operatorData = [
                    'login_name' => 'admin',
                    'mobile' => 'admin',
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'operator_type' => 'admin',
                    'company_id' => $companyId,
                    'username' => 'admin',
                ];
                
                $newOperator = $operatorsRepository->create($operatorData);
                $operatorId = $newOperator['operator_id'];
                
                $this->info("✓ 已创建 admin 账号");
                $this->info("  账号ID: {$operatorId}");
                $this->info("  公司ID: {$companyId}");
                
            } else {
                // 账号已存在，更新密码和 company_id
                $operatorId = $operator['operator_id'];
                $oldCompanyId = $operator['company_id'] ?? 'null';
                
                $this->info("admin 账号已存在，正在更新密码...");
                $this->info("  账号ID: {$operatorId}");
                $this->info("  原公司ID: {$oldCompanyId}");
                
                $updateData = [
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ];
                
                // 如果 company_id 与现有值不同，则更新
                if ($operator['company_id'] != $companyId) {
                    $updateData['company_id'] = $companyId;
                    $this->info("  将更新公司ID为: {$companyId}");
                }
                
                $operatorsRepository->updateOneBy(
                    [
                        'login_name' => 'admin',
                        'operator_type' => 'admin'
                    ],
                    $updateData
                );
                
                $this->info("✓ 已更新 admin 账号密码");
                if (isset($updateData['company_id'])) {
                    $this->info("✓ 已更新公司ID为: {$companyId}");
                }
            }

            $conn->commit();
            $this->info("");
            $this->info("操作成功完成！");
            $this->info("登录账号: admin");
            $this->info("公司ID: {$companyId}");
            
        } catch (\Exception $e) {
            $conn->rollback();
            $this->error("操作失败: " . $e->getMessage());
            $this->error("错误详情: " . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
