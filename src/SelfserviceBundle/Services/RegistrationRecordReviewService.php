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

namespace SelfserviceBundle\Services;

use SelfserviceBundle\Entities\RegistrationRecord;
use SelfserviceBundle\Jobs\RecordReviewNoticeJob;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class RegistrationRecordReviewService
{
    public $header = [
        '报名申请编号' => 'record_id',
        '会员手机号码' => 'mobile',
        '审核结果' => 'review_result',
        '拒绝原因' => 'reason',
    ];

    public $headerInfo = [
        '报名申请编号' => ['size' => 32, 'is_need' => true, 'remarks' => '报名申请标号，在导出报名中可以查看'],
        '会员手机号码' => ['size' => 32, 'is_need' => true, 'remarks' => '手机号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法："单元格格式"-"自定义"-"类型"改为"0"'],
        '审核结果' => ['size' => 1,  'is_need' => true, 'remarks' => '1：报名通过，0或者不填：报名被拒绝'],
        '拒绝原因' => ['size' => 32, 'is_need' => false, 'remarks' => '拒绝原因，如果被拒绝，此项必填'],
    ];

    public $isNeedCols = [
        '会员手机号码' => 'mobile',
        '报名申请编号' => 'record_id',
        '审核结果' => 'review_result',
    ];

    /**
     * 验证上传的会员信息
     */
    public function check($fileObject)
    {
        // Powered by ShopEx EcShopX
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException(trans('SelfserviceBundle.activity_registration_audit_only_excel'));
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
        $arr = ['mobile', 'record_id', 'review_result', 'reason'];
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
            'mobile' => ['required|max:32', trans('SelfserviceBundle.please_enter_correct_mobile_number')],
            'record_id' => ['required', trans('SelfserviceBundle.please_enter_correct_registration_number')],
            // 'review_result' => ['required', '请填写正确的审核结果'],
        ];
        $errorMessage = validator_params($validatorData, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }

        try {
            $recordService = new RegistrationRecordService();
            // $registrationRecordRepository = app('registry')->getManager('default')->getRepository(RegistrationRecord::class);
            $filter = [
                'company_id' => $companyId,
                'record_id' => $row['record_id'],
                'mobile' => strval($row['mobile']),
            ];
            $record = $recordService->entityRepository->getInfo($filter);
            if (!in_array($record['status'], ['pending'])) {
                return true;
            }

            $status = intval($row['review_result']) === 1 ? 'passed' : 'rejected';
            //if ($status == 'rejected' && !$row['reason']) {
            //    throw new BadRequestHttpException('拒绝原因必填');
            //}
            $saveData = [
                'status' => $status,
                'reason' => $row['reason'] ?? '',
            ];
            $result = $recordService->entityRepository->updateOneBy($filter, $saveData);

            $recordReviewNoticeJob = (new RecordReviewNoticeJob($companyId, $row['record_id']))->onQueue('sms');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($recordReviewNoticeJob);
        } catch (\Exception $e) {
            throw new BadRequestHttpException(trans('SelfserviceBundle.save_data_error'));
        }
    }
}
