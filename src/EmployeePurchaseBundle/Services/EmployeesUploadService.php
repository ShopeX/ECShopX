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

namespace EmployeePurchaseBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;
use EmployeePurchaseBundle\Services\EmployeesService;
use EmployeePurchaseBundle\Services\EnterprisesService;

class EmployeesUploadService
{
    public $header = [
        '企业编码' => 'enterprise_sn',
        '姓名' => 'name',
        '手机号码' => 'mobile',
        '账号' => 'account',
        '校验密码' => 'auth_code',
        // '邮箱' => 'email',
    ];

    public $headerInfo = [
        '企业编码' => ['size' => 20, 'remarks' => '仅支持英文大写字母和数字，可以内购企业列表查询', 'is_need' => false],
        '姓名' => ['size' => 20, 'remarks' => '', 'is_need' => true],
        '手机号码' => ['size' => 32, 'remarks' => '企业登录方式为手机号登录时必填', 'is_need' => false],
        '账号' => ['size' => 20, 'remarks' => '企业登录方式为账号登录时必填', 'is_need' => false],
        '校验密码' => ['size' => 20, 'remarks' => '企业登录方式为账号登录时必填', 'is_need' => false],
        // '邮箱' => ['size' => 20, 'remarks' => '企业登录方式为邮箱登录时必填', 'is_need' => false],
    ];    

    public $isNeedCols = [
        '企业编码' =>'enterprise_sn',
        '姓名' => 'name',
    ];

    /**
     * 验证上传的白名单
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('企业员工名单只支持上传Excel文件格式');
        }
    }

    public $tmpTarget = null;

    /**
     * getFilePath function
     *
     * @return void
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        $url = $this->getFileSystem()->privateDownloadUrl($filePath);

        $client = new Client();
        $content = $client->get($url)->getBody()->getContents();

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }


    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    /**
     * 获取头部标题
     */
    public function getHeaderTitle()
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    private function _formatData($row)
    {
        $columns = ['enterprise_sn', 'name', 'mobile', 'account', 'auth_code', 'email', 'distributor_id', 'operator_id'];
        $data = [];
        foreach ($columns as $column) {
            if (isset($row[$column]) && $row[$column] != "") {
                $data[$column] = trim($row[$column]);
            }
        }
        return $data;
    }

    public function handleRow($companyId, $row)
    {
        $data = $this->_formatData($row);

        $enterprisesService = new EnterprisesService();
        $enterpriseInfo = $enterprisesService->getInfo(['company_id' => $companyId, 'enterprise_sn' => $data['enterprise_sn'], 'distributor_id' => $row['distributor_id']]);
        if (!$enterpriseInfo) {
            throw new BadRequestHttpException('企业不存在');
        }
        if ($enterpriseInfo['is_employee_check_enabled'] == false) {
            throw new BadRequestHttpException('该企业无需导入白名单');
        }

        $data['enterprise_id'] = $enterpriseInfo['id'];
        $data['company_id'] = $companyId;
        $data['user_id'] = 0;
        $employeesService = new EmployeesService();
        $employeesService->create($data);
    }
}