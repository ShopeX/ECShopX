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
use KaquanBundle\Services\MemberCardService;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Entities\MemberRelTags;
use MembersBundle\Traits\GetCodeTrait;
use PointBundle\Services\PointMemberService;
use GuzzleHttp\Client as Client;

class MemberUploadUpdateService
{
    use GetCodeTrait;

    public $header = [
        '手机号码' => 'mobile',
        '姓名' => 'username',
        '性别' => 'sex',
        '会员等级' => 'grade_name',
        '邮箱' => 'email',
        '标签' => 'tags',
        '禁用' => 'disabled',
        '积分' => 'point',
    ];

    public $headerInfo = [
        '手机号码' => ['size' => 32, 'remarks' => '不得重复，手机号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法：“单元格格式”-“自定义”-“类型”改为“0”', 'is_need' => true],
        '姓名' => ['size' => 20, 'remarks' => '', 'is_need' => false],
        '性别' => ['size' => 2, 'remarks' => '性别只能为男,女，未知', 'is_need' => false],
        '会员等级' => ['size' => 8, 'remarks' => '会员等级需和在会员卡中配置的会员等级一致', 'is_need' => false],
        '邮箱' => ['size' => 32, 'remarks' => '标准邮箱格式', 'is_need' => false],
        '标签' => ['size' => 128, 'remarks' => '标签名称多个用逗号“,”隔开(注：逗号为半角逗号),并且标签必须已存在系统中,例子：时尚,超级会员', 'is_need' => false],
        '禁用' => ['size' => 1, 'remarks' => '否:可用；是:禁用', 'is_need' => false],
        '积分' => ['size' => 32, 'remarks' => '增加积分：正整数；减少积分：-正整数', 'is_need' => false],
    ];

    public $isNeedCols = [
        '手机号码' => 'mobile'
    ];

    /**
     * 验证上传的会员信息
     */
    public function check($fileObject)
    {
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
        $arr = ['mobile', 'email', 'username', 'sex', 'grade_name', 'point'];
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
            'username' => ['max:20', trans('MembersBundle/Members.please_enter_valid_name')],
            'sex' => ['max:6', trans('MembersBundle/Members.please_enter_valid_gender')],
            'grade_name' => ['max:8', trans('MembersBundle/Members.please_enter_valid_member_level')],
            'email' => ['email', trans('MembersBundle/Members.please_enter_valid_email')],
            'disabled' => ['max:1', trans('MembersBundle/Members.please_enter_valid_disabled')],
            'point' => ['numeric', trans('MembersBundle/Members.please_enter_valid_points')],
        ];
        $errorMessage = validator_params($validatorData, $rules, false);
        if ($errorMessage && is_array($errorMessage)) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException(trans('MembersBundle/Members.validation_error', ['{0}' => $msg]));
        } elseif ($errorMessage) {
            throw new BadRequestHttpException(trans('MembersBundle/Members.validation_error', ['{0}' => $errorMessage]));
        }

        if ($row['tags']) {
            $memberTagsService = new MemberTagsService();
            $tags = explode(',', $row['tags']);
            $list = $memberTagsService->getListTags(['tag_name' => $tags, 'company_id' => $companyId]);
            if (!($list['list'] ?? null)) {
                throw new BadRequestHttpException(trans('MembersBundle/Members.tag_name_not_exists', ['{0}' => $row['tags']]));
            }

            $tagsdata = array_column($list['list'], 'tag_name');
            if (count($tags) != count($tagsdata)) {
                foreach ($tags as $v) {
                    if (!in_array($v, $tagsdata)) {
                        throw new BadRequestHttpException(trans('MembersBundle/Members.tag_name_not_exists', ['{0}' => $v]));
                    }
                }
            }

            $tagIds = array_column($list['list'], 'tag_id');
        }

        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);

        $member = $membersRepository->get(['company_id' => $companyId, 'mobile' => $row['mobile']]);
        if (empty($member)) {
            throw new BadRequestHttpException(trans('MembersBundle/Members.member_data_not_exists'));
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = [
                'user_id' => $member['user_id'],
            ];

            if ($row['username'] ?? null) {
                $data['username'] = trim($row['username']);
            }

            if ($row['sex'] ?? null) {
                $data['sex'] = $this->getSex($row['sex']);
            }

            if ($row['email'] ?? null) {
                $data['email'] = $row['email'];
            }
            if ($data ?? null) {
                $membersInfoRepository->updateOneBy($filter, $data);
            }


            //更新会员等级
            if ($row['grade_name'] ?? null) {
                $params['grade_id'] = $this->getGradeIdByName($companyId, $row['grade_name']);
            }
            // 更新禁用
            if ($row['disabled'] ?? null) {
                $disabled = $this->getDisabled(trim($row['disabled']));
                if ($disabled != 2) {
                    $params['disabled'] = $disabled;
                }
            }
            if ($params ?? null) {
                $membersRepository->update($params, $filter);
            }

            //更新会员标签
            if ($tagIds ?? null) {
                $memberTagsService->createRelTagsByUserId($member['user_id'], $tagIds, $companyId);
            }
            // 更新会员积分
            if ($row['point'] ?? null) {
                if ($row['point'] < 0) {
                    $status = false;
                } else {
                    $status = true;
                }
                $point = intval(abs($row['point']));
                if ($point > 0) {
                    $pointMemberService = new PointMemberService();
                    $pointMemberService->addPoint($member['user_id'], $companyId, $point, 15, $status, '会员信息导入，修改会员积分');
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new BadRequestHttpException(trans('MembersBundle/Members.member_update_error', ['{0}' => $e->getMessage()]));
        }
    }

    private function getGradeIdByName($companyId, $gradeName)
    {
        $memberCardService = new MemberCardService();
        $gradeId = $memberCardService->getGradeIdByName($companyId, $gradeName);
        if (!$gradeId) {
            throw new BadRequestHttpException(trans('MembersBundle/Members.member_level_not_exists', ['{0}' => $gradeName]));
        }
        return $gradeId;
    }

    private function getSex($str)
    {
        if ($str == '男') {
            return 1;
        } elseif ($str == '女') {
            return 2;
        } else {
            return 0;
        }
    }

    private function getDisabled($str)
    {
        if ($str == '否') {
            return 0;
        } elseif ($str == '是') {
            return 1;
        } else {
            return 2;
        }
    }
}
