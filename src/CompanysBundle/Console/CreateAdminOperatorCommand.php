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
use CompanysBundle\Repositories\CompanysRepository;

class CreateAdminOperatorCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'operator:create 
                            {login_name : 登录账号名}
                            {password : 密码}
                            {--operator_type=admin : 操作员类型，默认admin}
                            {--mobile= : 手机号（可选）}
                            {--username= : 用户名（可选）}
                            {--company_name= : 公司名称（可选，默认为账号名）}
                            {--use_existing_company : 使用已存在的默认公司ID，而不是创建新公司}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '创建或更新后台管理员账号（支持多表联动）';

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
        $loginName = $this->argument('login_name');
        $password = $this->argument('password');
        $operatorType = $this->option('operator_type');
        $mobile = $this->option('mobile') ?: $loginName;
        $username = $this->option('username') ?: $loginName;
        $companyName = $this->option('company_name') ?: $loginName . '的企业';
        $useExistingCompany = $this->option('use_existing_company');

        $this->info("开始处理账号: {$loginName}");

        // 获取 Repository
        $operatorsRepository = app('registry')->getManager('default')->getRepository(\CompanysBundle\Entities\Operators::class);
        $companysRepository = app('registry')->getManager('default')->getRepository(\CompanysBundle\Entities\Companys::class);
        
        // 获取数据库连接
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            // 检查账号是否已存在
            $operator = $operatorsRepository->getInfo([
                'login_name' => $loginName,
                'operator_type' => $operatorType
            ]);

            $operatorId = null;
            $companyId = null;

            if (empty($operator)) {
                // 账号不存在，创建新账号
                $this->info("账号不存在，正在创建新账号...");
                
                if ($useExistingCompany) {
                    // 使用已存在的默认公司
                    $companyId = config('common.system_companys_id', 1);
                    $company = $companysRepository->getInfo(['company_id' => $companyId]);
                    
                    if (empty($company)) {
                        $this->error("指定的公司ID {$companyId} 不存在！");
                        $conn->rollback();
                        return 1;
                    }
                    
                    // 直接创建 operator，关联到现有公司
                    $operatorData = [
                        'login_name' => $loginName,
                        'mobile' => $mobile,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'operator_type' => $operatorType,
                        'company_id' => $companyId,
                        'username' => $username,
                    ];
                    
                    $newOperator = $operatorsRepository->create($operatorData);
                    $operatorId = $newOperator['operator_id'];
                    $this->info("已创建操作员，ID: {$operatorId}，关联到现有公司ID: {$companyId}");
                    
                } else {
                    // 为每个新账号创建新的 company
                    $this->info("正在为新账号创建新公司...");
                    
                    // 先创建 operator（此时 company_id 为 null）
                    $operatorData = [
                        'login_name' => $loginName,
                        'mobile' => $mobile,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'operator_type' => $operatorType,
                        'company_id' => null, // 先不设置，等创建 company 后再更新
                        'username' => $username,
                    ];
                    
                    $newOperator = $operatorsRepository->create($operatorData);
                    $operatorId = $newOperator['operator_id'];
                    $this->info("已创建操作员，ID: {$operatorId}");
                    
                    // 创建新 company（不指定 company_id，让数据库自动生成）
                    $productModel = config('common.product_model', 'platform');
                    $menuTypeMap = [
                        'b2c' => 2,
                        'platform' => 3,
                        'standard' => 4,
                        'in_purchase' => 5,
                    ];
                    $menuType = $menuTypeMap[$productModel] ?? 3; // 默认 platform
                    
                    $companyData = [
                        // 不指定 company_id，让数据库自动生成
                        'company_name' => $companyName,
                        'passport_uid' => $loginName,
                        'eid' => '',
                        'menu_type' => $menuType,
                        'company_admin_operator_id' => $operatorId,
                        'expiredAt' => strtotime('2037-01-01'),
                    ];
                    
                    $newCompany = $companysRepository->add($companyData);
                    $companyId = $newCompany['company_id'];
                    $this->info("已创建新公司，ID: {$companyId}，名称: {$companyName}");
                    
                    // 更新 operator 的 company_id
                    $operatorsRepository->updateOneBy(
                        ['operator_id' => $operatorId],
                        ['company_id' => $companyId]
                    );
                    $this->info("已更新操作员的公司ID");
                }
                
            } else {
                // 账号已存在，更新密码
                $this->info("账号已存在，正在更新密码...");
                $operatorId = $operator['operator_id'];
                $companyId = $operator['company_id'];
                
                $operatorsRepository->updateOneBy(
                    [
                        'login_name' => $loginName,
                        'operator_type' => $operatorType
                    ],
                    [
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                    ]
                );
                
                $this->info("已更新账号密码");
            }

            $conn->commit();
            $this->info("操作成功完成！");
            $this->info("账号: {$loginName}");
            $this->info("操作员ID: {$operatorId}");
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

