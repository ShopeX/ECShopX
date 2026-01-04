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

namespace CommunityBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;
use CommunityBundle\Services\CommunityChiefService;
use MembersBundle\Services\MemberService;
use DistributionBundle\Services\DistributorService;
use CommunityBundle\Services\CommunityActivityService;

class CommunityChiefUploadService
{
    public $header = [
        '团长手机号码' => 'mobile',
        '店铺ID' => 'did',
    ];

    public $isNeedCols = [
        '团长手机号码' => 'mobile',
        '店铺ID' => 'did',
    ];

    public $headerInfo = [
        '团长手机号码' => ['size' => 32, 'remarks' => '手机号必须是已注册会员', 'is_need' => true],
        '店铺ID' =>  ['size' => 50, 'remarks' => '店铺ID必须当前有效店铺，店铺管理员操作导入可以不填', 'is_need' => false],
    ];

    /**
     * 验证上传的会员信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('上传团长信息只支持Excel文件格式');
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
        // CRC: 2367340174
        return app('filesystem')->disk('import-file');
    }

    /**
     * 获取头部标题
     */
    public function getHeaderTitle()
    {
        // CRC: 2367340174
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    public function handleRow($companyId, $row)
    {
        $row['distributor_id'] = $row['distributor_id'] ?? 0;
        $row['did'] = $row['did'] ?? 0;
        $rules = [
            'mobile' => ['required|mobile', '请填写正确的手机号'],
        ];
        if (!$row['distributor_id']) {
            $rules['did'] = ['required|min:1', '请填写店铺ID'];
        }
        $errorMessage = validator_params($row, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }

        if ($row['distributor_id'] > 0) {
            if ($row['did'] > 0 && $row['did'] != $row['distributor_id']) {
                throw new BadRequestHttpException('只能上传本店铺的团长信息');
            }
            $row['did'] = $row['distributor_id'];
        }

        $communityChiefService = new CommunityChiefService();
        $memberService = new MemberService();
        $userId = $memberService->getUserIdByMobile($row['mobile'], $companyId);
        if (!$userId) {
            throw new BadRequestHttpException('手机号未注册成为会员，请先注册');
        }

        $chief = $communityChiefService->getChiefInfo(['user_id' => $userId]);
        if ($chief && $chief['distributors']) {
            // 当前团长只能绑定一个店铺
            if ($row['did'] != $chief['distributors']['distributor_id']) {
                if ($row['distributor_id'] > 0) {
                    throw new BadRequestHttpException('该手机号已绑定成为其他店铺的团长');
                } else {
                    $communityActivityService = new CommunityActivityService();
                    if ($communityActivityService->count(['chief_id' => $chief['chief_id'], 'activity_status|notIn' => ['success', 'fail']])) {
                        throw new BadRequestHttpException('该团长有进行中的活动，不能更换店铺');
                    }
                }
            }
        }

        if ($row['did'] > 0) {
            $distributorService = new DistributorService();
            $distributor = $distributorService->getInfoSimple(['distributor_id' => $row['did']]);
            if (!$distributor) {
                throw new BadRequestHttpException('无效的店铺ID');
            }
        }

        $params = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'distributor_ids'=> [$row['did']],
        ];
        $communityChiefService->createChief($params);
    }
}
