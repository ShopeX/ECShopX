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

namespace HfPayBundle\Http\FrontApi\V1\Action;

use HfPayBundle\Services\HfpayBankService;
use HfPayBundle\Services\AcouService;
use HfPayBundle\Services\HfpayEnterapplyService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

class HfpayBank extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/hfpay/banksave",
     *     summary="保存汇付取现银行卡",
     *     tags={"汇付天下"},
     *     description="保存汇付取现银行卡",
     *     operationId="save",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="bank_name", in="query", description="银行名称", required=true, type="string"),
     *     @SWG\Parameter( name="card_num", in="query", description="银行卡号", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="hfpay_bank_card_id", type="integer", description="汇付取现银行卡表id"),
     *                 @SWG\Property(property="company_id", type="integer", description="公司company id"),
     *                 @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                 @SWG\Property(property="user_id", type="integer", description="用户id"),
     *                 @SWG\Property(property="user_cust_id", type="stirng", description="汇付客户号"),
     *                 @SWG\Property(property="card_type", type="stirng", description="绑卡类型"),
     *                 @SWG\Property(property="bank_id", type="stirng", description="银行编码code"),
     *                 @SWG\Property(property="bank_name", type="stirng", description="银行名称"),
     *                 @SWG\Property(property="card_num", type="stirng", description="银行卡号"),
     *                 @SWG\Property(property="bind_card_id", type="stirng", description="汇付绑定id"),
     *                 @SWG\Property(property="is_cash", type="stirng", description="是否取现卡"),
     *                 @SWG\Property(property="created_at", type="datetime", description="创建时间"),
     *                 @SWG\Property(property="updated_at", type="datetime", description="修改时间")
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfpayErrorRespones") ) )
     * )
     */
    public function save(Request $request)
    {
        // KEY: U2hvcEV4
        $authInfo = $request->get('auth');

        $companyId = $authInfo['company_id'];
        $params = $request->all();
        $params['company_id'] = $companyId;
        $params['user_id'] = $filter['user_id'] = $authInfo['user_id'];

        $filter['company_id'] = $companyId;

        $params['card_type'] = '1';//目前绑卡类型只能为对私
        $params['is_cash'] = '1';//目前只能为取现卡
        $applyService = new HfpayEnterapplyService();
        $enterResult = $applyService->getEnterapply($filter);
        if (empty($enterResult) || empty($enterResult['user_cust_id'])) {
            throw new ResourceException('您还未开通汇付天下商户号，无法进行银行卡绑定');
        }
        $params['user_cust_id'] = $enterResult['user_cust_id'];
        $filter['card_num'] = $params['card_num'];

        $bankService = new HfpayBankService();
        $data = $bankService->getBank($filter);
        if ($data) {
            $params['hfpay_bank_card_id'] = $data['hfpay_bank_card_id'];
        }
        if (isset($data['bind_card_id']) && !empty($data['bind_card_id'])) {
            throw new ResourceException('请勿重复绑定');
        }
        if (empty($data) || empty($data['bind_card_id'])) {
            //汇付绑定取现卡接口
            $servce = new AcouService($companyId);
            $apiResult = $servce->bind01($params);
            if (!in_array($apiResult['resp_code'], ['C00000','A51003'])) {
                throw new ResourceException($apiResult['resp_desc']);
            }
            if (in_array($apiResult['resp_code'], ['C00000', 'A51003'])) {
                $params['bind_card_id'] = $apiResult['bind_card_id'];
                // $params['user_cust_id'] = $apiResult['user_cust_id'];
            }
        }

        $result = $bankService->saveBank($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/hfpay/bank",
     *     summary="获取汇付取现银行卡",
     *     tags={"汇付天下"},
     *     description="获取汇付取现银行卡",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="用户ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="hfpay_bank_card_id", type="integer", description="汇付取现银行卡表id"),
     *                 @SWG\Property(property="company_id", type="integer", description="公司company id"),
     *                 @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                 @SWG\Property(property="user_id", type="integer", description="用户id"),
     *                 @SWG\Property(property="user_cust_id", type="stirng", description="汇付客户号"),
     *                 @SWG\Property(property="card_type", type="stirng", description="绑卡类型"),
     *                 @SWG\Property(property="bank_id", type="stirng", description="银行编码code"),
     *                 @SWG\Property(property="bank_name", type="stirng", description="银行名称"),
     *                 @SWG\Property(property="card_num", type="stirng", description="银行卡号"),
     *                 @SWG\Property(property="bind_card_id", type="stirng", description="汇付绑定id"),
     *                 @SWG\Property(property="is_cash", type="stirng", description="是否取现卡"),
     *                 @SWG\Property(property="created_at", type="datetime", description="创建时间"),
     *                 @SWG\Property(property="updated_at", type="datetime", description="修改时间")
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfpayErrorRespones") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $filter['company_id'] = $authInfo['company_id'];

        if (isset($authInfo['user_id']) && !empty($authInfo['user_id'])) {
            $filter['user_id'] = $authInfo['user_id'];
        }
        if (isset($params['distributor_id']) && !empty($params['distributor_id'])) {
            $filter['distributor_id'] = $params['distributor_id'];
        }
        $bankService = new HfpayBankService();
        $result = $bankService->getBank($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/hfpay/banklist",
     *     summary="获取汇付取现银行卡多条",
     *     tags={"汇付天下"},
     *     description="获取汇付取现银行卡多条",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="用户ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="hfpay_bank_card_id", type="integer", description="汇付取现银行卡表id"),
     *                     @SWG\Property(property="company_id", type="integer", description="公司company id"),
     *                     @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                     @SWG\Property(property="user_id", type="integer", description="用户id"),
     *                     @SWG\Property(property="user_cust_id", type="stirng", description="汇付客户号"),
     *                     @SWG\Property(property="card_type", type="stirng", description="绑卡类型"),
     *                     @SWG\Property(property="bank_id", type="stirng", description="银行编码code"),
     *                     @SWG\Property(property="bank_name", type="stirng", description="银行名称"),
     *                     @SWG\Property(property="card_num", type="stirng", description="银行卡号"),
     *                     @SWG\Property(property="bind_card_id", type="stirng", description="汇付绑定id"),
     *                     @SWG\Property(property="is_cash", type="stirng", description="是否取现卡"),
     *                     @SWG\Property(property="created_at", type="datetime", description="创建时间"),
     *                     @SWG\Property(property="updated_at", type="datetime", description="修改时间")
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfpayErrorRespones") ) )
     * )
     */
    public function getList(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $filter['company_id'] = $authInfo['company_id'];
        if (isset($authInfo['user_id']) && !empty($authInfo['user_id'])) {
            $filter['user_id'] = $authInfo['user_id'];
        }
        if (isset($params['distributor_id']) && !empty($params['distributor_id'])) {
            $filter['distributor_id'] = $params['distributor_id'];
        }

        $bankService = new HfpayBankService();
        $result = $bankService->getBankList($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/hfpay/bankdel",
     *     summary="解绑删除汇付取现银行卡",
     *     tags={"汇付天下"},
     *     description="解绑删除汇付取现银行卡",
     *     operationId="unBindBank",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="card_num", in="query", description="银行卡号", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="result", type="boolean", description="删除状态 true 删除成功 false 删除失败")
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfpayErrorRespones") ) )
     * )
     */
    public function unBindBank(Request $request)
    {
        // KEY: U2hvcEV4
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $params = $request->all();

        $bankService = new HfpayBankService();

        $filter['company_id'] = $companyId;
        $filter['card_num'] = $params['card_num'];
        if (isset($authInfo['user_id']) && !empty($authInfo['user_id'])) {
            $filter['user_id'] = $authInfo['user_id'];
        }
        if (isset($params['distributor_id']) && !empty($params['distributor_id'])) {
            $filter['distributor_id'] = $params['distributor_id'];
        }
        $data = $bankService->getBank($filter);
        $result = false;
        if ($data && !empty($data['bind_card_id'])) {
            //汇付解除取现卡接口
            $servce = new AcouService($companyId);
            $hfparams['bind_card_id'] = $data['bind_card_id'];
            $hfparams['user_cust_id'] = $data['user_cust_id'];
            $apiResult = $servce->unbd01($hfparams);
            if (!in_array($apiResult['resp_code'], ['C00000','C00001','C00002'])) {
                throw new ResourceException($apiResult['resp_desc']);
            }
        }
        if ($data) {
            $result = $bankService->unBindBank(['hfpay_bank_card_id' => $data['hfpay_bank_card_id']]);
        }

        return $this->response->array(['result' => $result]);
    }
}
