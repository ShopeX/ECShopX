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

namespace MembersBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class MemberWhitelistUploadService
{
    public $header = [
        '手机号码' => 'mobile',
        '姓名' => 'name',
    ];

    public $headerInfo = [
        '手机号码' => ['size' => 32, 'remarks' => '不得重复，手机号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法：“单元格格式”-“自定义”-“类型”改为“0”', 'is_need' => true],
        '姓名' => ['size' => 20, 'remarks' => '', 'is_need' => true],
    ];

    public $isNeedCols = [
        '手机号码' => 'mobile',
        '姓名' => 'name',
    ];

    /**
     * 验证上传的白名单
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException(trans('MembersBundle/Members.whitelist_upload_excel_only'));
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

    private function validatorData($row)
    {
        $arr = ['mobile', 'name'];
        $data = [];
        foreach ($arr as $column) {
            if ($row[$column]) {
                $data[$column] = trim($row[$column]);
            }
        }

        return $data;
    }

    public function handleRow($companyId, $row)
    {
        $validatorData = $this->validatorData($row);

        $rules = [
            'mobile' => ['required|max:32', trans('MembersBundle/Members.enter_valid_mobile')],
            'name' => ['required|max:20', trans('MembersBundle/Members.please_enter_valid_name')],
        ];
        $errorMessage = validator_params($validatorData, $rules, false);
        if ($errorMessage && is_array($errorMessage)) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException(trans('MembersBundle/Members.validation_error', ['{0}' => $msg]));
        } elseif ($errorMessage) {
            throw new BadRequestHttpException(trans('MembersBundle/Members.validation_error', ['{0}' => $errorMessage]));
        }
        $whitelistService = new MembersWhitelistService();
        $ismobile = ismobile($row['mobile']);
        if (!$ismobile) {
            throw new BadRequestHttpException(trans('MembersBundle/Members.invalid_mobile'));
        }

        $whitelist = $whitelistService->getInfo(['company_id' => $companyId, 'mobile' => $row['mobile']]);
        if ($whitelist) {
            throw new BadRequestHttpException(trans('MembersBundle/Members.mobile_whitelist_already_exists'));
        }

        //新增
        $whitelist_data = [
            'company_id' => $companyId,
            'mobile' => trim($row['mobile']),
            'name' => trim($row['name']),
        ];
        $result = $whitelistService->createData($whitelist_data);
    }
}
