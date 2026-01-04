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
use MembersBundle\Entities\MembersOffineLog;
use KaquanBundle\Services\MemberCardService;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Entities\MemberRelTags;
use MembersBundle\Traits\GetCodeTrait;
use PointBundle\Services\PointMemberService;
use GuzzleHttp\Client as Client;

class MemberUploadService
{
    use GetCodeTrait;

    public $header = [
        '会员手机号' => 'mobile',
        '原实体卡号' => 'offline_card_code',
        '姓名' => 'username',
        '性别' => 'sex',
        '会员等级' => 'grade_name',
        '生日' => 'birthday',
        '入会日期' => 'created',
        //'开卡门店'   => 'shop_name',
        '邮箱' => 'email',
        '地址' => 'address',
        '标签' => 'tags',
        '积分' => 'point',
    ];

    public $headerInfo = [
        '会员手机号' => ['size' => 32, 'remarks' => '不得重复，手机号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法：“单元格格式”-“自定义”-“类型”改为“0”', 'is_need' => true],
        '原实体卡号' => ['size' => 20, 'remarks' => '不得重复', 'is_need' => false],
        '姓名' => ['size' => 20, 'remarks' => '', 'is_need' => true],
        '性别' => ['size' => 2, 'remarks' => '性别只能为男,女，未知', 'is_need' => true],
        '会员等级' => ['size' => 8, 'remarks' => '会员等级需和在会员卡中配置的会员等级一致', 'is_need' => true],
        '生日' => ['size' => 10, 'remarks' => '生日时间不得大于今日，格式为mm/dd/yyyy, 如:1/12/2019', 'is_need' => false],
        '入会日期' => ['size' => 10, 'remarks' => '入会时间不得大于今日，格式为mm/dd/yyyy, 如:12/1/2019', 'is_need' => true],
        //'开卡门店'   => 'shop_name',
        '邮箱' => ['size' => 32, 'remarks' => '', 'is_need' => false],
        '地址' => ['size' => 128, 'remarks' => '', 'is_need' => false],
        '标签' => ['size' => 128, 'remarks' => '标签名称多个用逗号“,”隔开(注：逗号为半角逗号),并且标签必须已存在系统中,例子：时尚,超级会员', 'is_need' => false],
        '积分' => ['size' => 32, 'remarks' => '会员初始积分', 'is_need' => false],
    ];

    public $isNeedCols = [
        '会员手机号' => 'mobile',
        '姓名' => 'username',
        '性别' => 'sex',
        '会员等级' => 'grade_name',
        '入会日期' => 'created',
        //'开卡门店' => 'shop_name',
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
     * @param $filePath
     * @param string $fileExt
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
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
        // Ver: 1e2364-fe10
        unlink($this->tmpTarget);
        return true;
    }


    public function getFileSystem()
    {
        // Ver: 1e2364-fe10
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
        $arr = ['mobile', 'email', 'birthday', 'address', 'username', 'sex', 'grade_name', 'created', 'point'];
        $data = [];
        foreach ($arr as $column) {
            if (isset($row[$column])) {
                $data[$column] = trim($row[$column]);
            }
        }

        return $data;
    }

    public function handleRow($companyId, $row)
    {
        $validatorData = $this->validatorData($row);

        $rules = [
            'mobile'     => ['max:32', trans('MembersBundle/Members.enter_valid_mobile')],
            'username'   => ['required|max:20', trans('MembersBundle/Members.please_enter_valid_name')],
            'sex'        => ['required|max:6', trans('MembersBundle/Members.please_enter_valid_gender')],
            'grade_name' => ['required|max:8', trans('MembersBundle/Members.please_enter_valid_member_level')],
            'created'    => ['required|date_format:n/j/Y', trans('MembersBundle/Members.please_enter_valid_join_date')],
            'birthday'   => ['date_format:n/j/Y', trans('MembersBundle/Members.please_enter_valid_birthday')],
            'address'    => ['max:128', trans('MembersBundle/Members.please_enter_valid_address')],
            'email'      => ['email', trans('MembersBundle/Members.please_enter_valid_email')],
            'point'      => ['numeric|min:0', trans('MembersBundle/Members.please_enter_valid_points')],
        ];

        $errorMessage = validator_params($validatorData, $rules, false);
        if ($errorMessage && is_array($errorMessage)) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException(trans('MembersBundle/Members.validation_error', ['{0}' => $msg]));
        } elseif ($errorMessage) {
            throw new BadRequestHttpException(trans('MembersBundle/Members.validation_error', ['{0}' => $errorMessage]));
        }

        if (!$row['mobile'] && !$row['offline_card_code']) {
            throw new BadRequestHttpException(trans('MembersBundle/Members.mobile_or_card_required'));
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

        if ($row['birthday']) {
            $birthdayArr = explode('/', $row['birthday']);
            $birthdayStr = $birthdayArr[2].'-'.$birthdayArr[0].'-'.$birthdayArr[1];
            if (strtotime($birthdayStr) > time()) {
                throw new BadRequestHttpException(trans('MembersBundle/Members.birthday_cannot_be_greater_than_now'));
            }
            $row['birthday'] = date("Y-m-d", strtotime($birthdayStr));
        }

        if ($row['created']) {
            $createdArr = explode('/', $row['created']);
            $createdStr = $createdArr[2].'-'.$createdArr[0].'-'.$createdArr[1];
            if (strtotime($createdStr) > time()) {
                throw new BadRequestHttpException(trans('MembersBundle/Members.join_date_cannot_be_greater_than_now'));
            }
            $row['created'] = strtotime($createdStr);
        }


        // 如果有实体卡但是没有手机号，那么暂时把数据存储到实体卡信息日志里
        // 用于后续手机号绑定实体卡
        if ($row['offline_card_code'] && !$row['mobile']) {
            $membersOffineRepository = app('registry')->getManager('default')->getRepository(MembersOffineLog::class);
            $offlineMember = [
                'company_id' => $companyId,
                'offline_card_code' => trim($row['offline_card_code']),
                'username' => trim($row['username']),
                'sex' => $this->getSex($row['sex']),
                'grade_id' => $this->getGradeIdByName($companyId, $row['grade_name']),
                'birthday' => $row['birthday'],
                'address' => $row['address'],
                'email' => trim($row['email']),
                'created_time' => $row['created'],
                'created' => time(),
                'updated' => time(),
            ];
            $membersOffineRepository->create($offlineMember);
        } else {
            $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
            $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);

            if ($row['offline_card_code']) {
                $offMember = $membersRepository->get(['company_id' => $companyId, 'offline_card_code' => $row['offline_card_code']]);
                if ($offMember) {
                    throw new BadRequestHttpException(trans('MembersBundle/Members.card_already_member'));
                }
            }

            if ($row['mobile']) {
                $member = $membersRepository->get(['company_id' => $companyId, 'mobile' => $row['mobile']]);
                if ($member) {
                    throw new BadRequestHttpException(trans('MembersBundle/Members.mobile_already_member'));
                }
            }

            //新增-会员信息
            $memberInfo = [
                'company_id' => $companyId,
                'offline_card_code' => trim($row['offline_card_code']),
                'username' => trim($row['username']),
                'mobile' => trim($row['mobile']),
                'sex' => $this->getSex($row['sex']),
                'grade_id' => $this->getGradeIdByName($companyId, $row['grade_name']),
                'birthday' => trim($row['birthday']),
                'address' => $row['address'],
                'email' => $row['email'],
                'created' => $row['created'],
                'password' => substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 5, 10),
            ];

            if ($memberInfo['offline_card_code']) {
                $memberInfo['user_card_code'] = $memberInfo['offline_card_code'];
            } else {
                $memberInfo['user_card_code'] = $this->getCode();
            }
            $memberInfo["region_mobile"] = $memberInfo["mobile"];
            $memberInfo["mobile_country_code"] = "86";
            $memberInfo["other_params"] = json_encode(['is_upload_member' => true]);

            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                $result = $membersRepository->create($memberInfo);
                $memberInfo['user_id'] = $result['user_id'];

                if ($tagIds ?? null) {
                    $memberTagsService->createRelTagsByUserId($result['user_id'], $tagIds, $companyId);
                }
                $membersInfoRepository->create($memberInfo);
                if (isset($row['point']) && $row['point'] > 0) {
                    $pointMemberService = new PointMemberService();
                    $pointMemberService->addPoint($memberInfo['user_id'], $companyId, $row['point'], 15, true, '会员信息导入，初始化积分');
                }
                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                throw new BadRequestHttpException(trans('MembersBundle/Members.save_data_error'));
            }
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
}
