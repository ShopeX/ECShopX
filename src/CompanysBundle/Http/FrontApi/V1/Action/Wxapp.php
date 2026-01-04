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

namespace CompanysBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use WorkWechatBundle\Services\WorkWechatService;
use SalespersonBundle\Services\SalespersonService;
use ThirdPartyBundle\Services\MarketingCenter\Request as BARequest;

class Wxapp extends BaseController
{
    protected $sessonExpier = 604800;

    /**
     * @SWG\Post(
     *     path="/wxapp/workwechatlogin",
     *     summary="企业微信，小程序登录",
     *     tags={"企业"},
     *     description="企业微信，小程序登录",
     *     operationId="workwechatlogin",
     *     @SWG\Parameter( name="authorizer-appid", in="header", description="小程序appid", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="appname", in="formData", description="小程序名称", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="formData", description="小程序登录时获取的code", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="session3rd", type="string", description=""),
     *                 @SWG\Property(property="company_id", type="string", description="公司id"),
     *                 @SWG\Property(property="distributor_id", type="string", description="分销商id"),
     *                 @SWG\Property(property="avatar", type="string", description="头像"),
     *                 @SWG\Property(property="phoneNumber", type="string", description="手机号"),
     *                 @SWG\Property(property="salesperson_name", type="string", description="导购员姓名"),
     *                 @SWG\Property(property="salesperson_type", type="string", description="导购员类型"),
     *                 @SWG\Property(property="salesperson_id", type="string", description="导购员id"),
     *                 @SWG\Property(property="store_name", type="string", description="门店名称"),
     *                 @SWG\Property(property="work_userid", type="string", description="工号"),
     *                 @SWG\Property(property="salesperson_job", type="string", description="职务"),
     *                 @SWG\Property(property="employee_status", type="string", description="员工类型 [1 员工] [2 编外]"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function workwechatlogin(Request $request)
    {
        if (empty($request->input('appname'))) {
            throw new BadRequestHttpException('缺少name参数，登录失败!');
        }

        $workWechatService = new WorkWechatService();
        $randomkey = $this->randomFromDev(16);
        $authInfo = $request->get('auth');
        app('log')->info('workwechatlogin authInfo===>'.var_export($authInfo, 1));
        $companyId = $authInfo['company_id'];
        $workOpen = $workWechatService->checkCodeAuth($companyId);
        if (!$workOpen) {
            throw new BadRequestHttpException('当前状态不允许普通登录!');
        }
        $qyWechatInfo = [];
        try {
            $code = $request->input('code');
            $config = app('wechat.work.wechat')->getConfig($companyId);
            $qyWechatInfo = Factory::work($config)->miniProgram()->auth->session($code);
        } catch (\Exception $e) {
            throw new BadRequestHttpException('导购登陆失败！');
        }
        app('log')->info('qyWechatInfo===>'.var_export($qyWechatInfo, 1));
        if (($qyWechatInfo['errcode'] ?? 0) > 0) {
            throw new BadRequestHttpException('导购登陆解析失败！');
        }

        $salespersonService = new SalespersonService();

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('salesperson_id,work_userid,work_clear_userid')->from('shop_salesperson');
        $criteria->where($criteria->expr()->in('work_userid', $criteria->expr()->literal($qyWechatInfo['userid'])));
        $criteria->orWhere($criteria->expr()->in('work_clear_userid', $criteria->expr()->literal($qyWechatInfo['userid'])));
        $checkResults = $criteria->execute()->fetchAll();
        // 如果没有保存明文userid，则更新一次
        // 如果work_userid存的是明文，则work_clear_userid也存成明文，说明第三方推送的导购userid也是明文的
        if ($checkResults && $checkResults[0]['work_userid'] && !$checkResults[0]['work_clear_userid']) {
            $salespersonService->updateOneBy(['salesperson_id' => $checkResults[0]['salesperson_id']], ['work_clear_userid' => $qyWechatInfo['userid']]);
        }
        if (!$checkResults) {
            // 调用ba.shopex.cn中的userid转换，用于和商派导购后台open_userid对应，获取userid的密文open_userid
            $barequest = new BARequest();
            $params['work_userid'] = $qyWechatInfo['userid'];
            $infodata = $barequest->call($companyId, 'basics.salesperson.useridtopenuserid', $params)['data'] ?? [];
            app('log')->info('ba.shopex.cn:response===>'.var_export($infodata, 1));

            if (empty($infodata)) {
                throw new BadRequestHttpException('转换导购工号错误!');
            }
            // 如果work_userid查不到，则可能第三方传过来的工号是密文的，这里将work_clear_userid存成明文的，方便导购企业内部登陆能找到对应的记录
            $conn = app('registry')->getConnection('default');
            $criteria = $conn->createQueryBuilder();
            $criteria->select('salesperson_id')->from('shop_salesperson');
            $criteria->where($criteria->expr()->in('work_userid', $criteria->expr()->literal($infodata['open_userid'])));
            $useridResult = $criteria->execute()->fetchAll();
            if ($useridResult) {
                $salespersonService->updateOneBy(['salesperson_id' => $useridResult[0]['salesperson_id']], ['work_clear_userid' => $qyWechatInfo['userid']]);
            } else {
                throw new BadRequestHttpException('没有您的导购信息!');
            }
        }

        $filter = [
            'company_id' => $companyId,
            'is_valid' => 'true',
            'salesperson_type' => 'shopping_guide',
            'work_clear_userid' => $qyWechatInfo['userid'],
        ];
        $salespersonInfo = $salespersonService->getSalespersonDetail($filter);
        app('log')->info('salespersonInfo===>'.var_export($salespersonInfo, 1));
        if (!$salespersonInfo) {
            throw new BadRequestHttpException('当前账号无权限');
        }
        if (!isset($salespersonInfo['store_name'])) {
            throw new BadRequestHttpException('请在后台为当前导购添加店铺');
        }
        if (!$salespersonInfo['store_name']) {
            throw new BadRequestHttpException('请在后台为当前导购添加店铺');
        }

        if (isset($qyWechatInfo['userid'])) {
            $config = app('wechat.work.wechat')->getConfig($companyId);

            $contactWayConfig = [
                'style' => 1,
                'skip_verify' => true,
                'user' => $qyWechatInfo['userid'],
            ];

            $configId = $salespersonInfo['work_configid'] ?? null;
            if (!$salespersonInfo['work_configid']) {
                $configIdTemp = Factory::work($config)->contact_way->create(1, 1, $contactWayConfig);
                $configId = $configIdTemp['config_id'] ?? null;
            }

            $qrcodeConfigId = $salespersonInfo['work_qrcode_configid'] ?? null;
            if (!$salespersonInfo['work_qrcode_configid']) {
                $qrcodeConfigIdTemp = Factory::work($config)->contact_way->create(1, 2, $contactWayConfig);
                ;
                $qrcodeConfigId = $qrcodeConfigIdTemp['config_id'] ?? null;
            }
            $salespersonService->salesperson->updateSalespersonById($salespersonInfo['salesperson_id'], ['work_configid' => $configId, 'work_qrcode_configid' => $qrcodeConfigId]);
        }
        $sessionValue = [
            'open_id' => $qyWechatInfo['userid'],
            'session_key' => $qyWechatInfo['session_key'],
            'appname' => $request->input('appname'),
            'phoneNumber' => $salespersonInfo['mobile'],
            'company_id' => $salespersonInfo['company_id'],
            'salesperson_id' => $salespersonInfo['salesperson_id'],
            'salesperson_name' => $salespersonInfo['name'],
            'salesperson_type' => $salespersonInfo['salesperson_type'],
            'work_userid' => $salespersonInfo['work_userid'],
        ];
        $sessionValue = json_encode($sessionValue);

        app('redis')->connection('wechat')->setex('frontSession3rd:'.$qyWechatInfo['session_key'], $this->sessonExpier, $sessionValue);
        // 姓名、工号、头像、职务、手机号、门店编码、门店名称、类型
        $data['session3rd'] = $qyWechatInfo['session_key'];
        $data['company_id'] = $salespersonInfo['company_id'];
        $data['phoneNumber'] = $salespersonInfo['mobile'];
        $data['distributor_id'] = $salespersonInfo['distributor_id'] ?? 0;
        $data['avatar'] = $salespersonInfo['avatar'];// 头像
        $data['salesperson_name'] = $salespersonInfo['name'];// 姓名
        $data['salesperson_type'] = $salespersonInfo['salesperson_type'];
        $data['salesperson_id'] = $salespersonInfo['salesperson_id'];
        $data['store_name'] = $salespersonInfo['store_name'];
        $data['work_userid'] = $salespersonInfo['work_userid'];// 工号
        $data['salesperson_job'] = $salespersonInfo['salesperson_job'];// 职务
        $data['employee_status'] = $salespersonInfo['employee_status'];// 员工类型 [1 员工] [2 编外]
        $data['shop_code'] = $salespersonInfo['shop_code'];// 店铺编号

        return $this->response->array($data);
    }

    // 取随机码，用于生成session
    private function randomFromDev($len)
    {
        $fp = @fopen('/dev/urandom', 'rb');
        $result = '';
        if ($fp !== false) {
            $result .= @fread($fp, $len);
            @fclose($fp);
        } else {
            trigger_error('Can not open /dev/urandom.');
        }
        // convert from binary to string
        $result = base64_encode($result);
        // remove none url chars
        $result = strtr($result, '+/', '-_');
        return substr($result, 0, $len);
    }
}
