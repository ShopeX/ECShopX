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

class MemberUploadConsumService
{
    public $header = [
        '手机号码' => 'mobile',
        '消费金额' => 'consumption',
    ];

    public $isNeedCols = [
        '手机号码' => 'mobile',
        '消费金额' => 'consumption',
    ];

    public $headerInfo = [
        '手机号码' => ['size' => 32, 'remarks' => '手机号必须是会员', 'is_need' => true],
        '消费金额' => ['size' => 10, 'remarks' => '消费金额以元位单位', 'is_need' => true],
    ];

    /**
     * 验证上传的会员信息
     */
    public function check($fileObject)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException(trans('MembersBundle/Members.member_info_upload_excel_only'));
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
        // 1e236443e5a30b09910e0d48c994b8e6 core
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

    public function handleRow($companyId, $row)
    {
        $rules = [
            'mobile' => ['required|max:32', trans('MembersBundle/Members.enter_valid_mobile')],
            'consumption' => ['required|numeric', trans('MembersBundle/Members.please_upload_valid_consumption')],
        ];
        $errorMessage = validator_params($row, $rules, false);
        if ($errorMessage && is_array($errorMessage)) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException(trans('MembersBundle/Members.validation_error', ['{0}' => $msg]));
        } elseif ($errorMessage) {
            throw new BadRequestHttpException(trans('MembersBundle/Members.validation_error', ['{0}' => $errorMessage]));
        }

        $consumption = round($row['consumption']);
        $memberService = new MemberService();
        $userId = $memberService->getUserIdByMobile($row['mobile'], $companyId);
        if (!$userId) {
            throw new BadRequestHttpException(trans('MembersBundle/Members.mobile_not_exists'));
        }

        $memberService->updateMemberConsumption($userId, $companyId, $consumption);
    }
}
