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

namespace ThirdPartyBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use CompanysBundle\Services\CompanysService;

class SaasCert extends Controller
{
    /**
     * @SWG\Get(
     *     path="/third/saascert/certificate",
     *     summary="证书、节点",
     *     tags={"ShopexErp"},
     *     description="查看证书节点信息",
     *     operationId="getCertificate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="cert_id", type="stirng", description="证书"),
     *                     @SWG\Property(property="node_id", type="stirng", description="节点"),
     *                     @SWG\Property(property="shopex_uid", type="stirng", description="shopex通行证"),
     *                 )
     *              ),
     *          ),
     *     ),
     * )
     */
    public function getCertificate()
    {
        $companyId = app('auth')->user()->get('company_id');
        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
        $certService = new CertService(false, $companyId, $shopexUid);
        $certSetting = $certService->getCertSetting();
        $certSetting['shopex_uid'] = $shopexUid;
        unset($certSetting['token']);
        return $this->response->array($certSetting);
    }

    /**
     * @SWG\Get(
     *     path="/third/saascert/delete/certificate",
     *     summary="删除证书节点信息",
     *     tags={"ShopexErp"},
     *     description="删除证书节点信息",
     *     operationId="deleteCertificate",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     * )
     */
    public function deleteCertificate()
    {
        $companyId = app('auth')->user()->get('company_id');
        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
        $certService = new CertService(false, $companyId, $shopexUid);
        $result = $certService->deleteCertSetting();
        // return $this->response->array($result);
        return $this->response->noContent();
    }

    /**
     * @SWG\Post(
     *     path="/third/saascert/cert/validate",
     *     summary="反查地址",
     *     tags={"ShopexErp"},
     *     description="shopex获取证书和节点，反查地址",
     *     operationId="setShopexErpSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="certi_ac", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter( name="session_id", in="query", description="签名验证", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *           type="object",
     *           @SWG\Property(property="res", type="stirng",description="返回状态"),
     *           @SWG\Property(property="msg", type="stirng",description="返回信息"),
     *           @SWG\Property(property="info", type="stirng",description="返回描述"),
     *         )
     *     ),
     * )
     */
    public function certiValidate(Request $request)
    {
        app('log')->info("saascert ============begin============");
        $companyId = config('common.system_companys_id');
        $postdata = $request->input();
        app('log')->info("saascert postdata========>".json_encode($postdata));
        $certService = new CertService();
        $result = $certService->certiValidate($postdata);
        echo json_encode($result);
        exit;
    }

    /**
     * @SWG\Get(
     *     path="/third/saascert/apply/bindrelation",
     *     summary="申请绑定节点",
     *     tags={"ShopexErp"},
     *     description="申请绑定节点",
     *     operationId="applyBindrelation",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="url", type="stirng", description="链接"),
     *                 )
     *              ),
     *          ),
     *     ),
     * )
     */
    public function applyBindrelation()
    {
        $companyId = app('auth')->user()->get('company_id');
        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
        $certService = new CertService(false, $companyId, $shopexUid);
        $result['url'] = $certService->applyBindrelation();
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/third/saascert/accept/bindrelation",
     *     summary="查看绑定节点",
     *     tags={"ShopexErp"},
     *     description="查看绑定节点",
     *     operationId="acceptBindrelation",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="url", type="stirng", description="链接"),
     *                 )
     *              ),
     *          ),
     *     ),
     * )
     */
    public function acceptBindrelation()
    {
        $companyId = app('auth')->user()->get('company_id');
        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
        $certService = new CertService(false, $companyId, $shopexUid);
        $result['url'] = $certService->acceptBindrelation();
        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/third/saascert/matrix/callback/{id}",
     *     summary="绑定节点，回打",
     *     tags={"ShopexErp"},
     *     description="矩阵绑定节点回打",
     *     operationId="bindrelationCallback",
     *     @SWG\Parameter( name="id", in="query", description="公司id", required=true, type="string"),
     *     @SWG\Parameter( name="node_id", in="query", description="节点", required=true, type="string"),
     *     @SWG\Parameter( name="node_type", in="query", description="节点类型", required=true, type="string"),
     *     @SWG\Parameter( name="shop_name", in="query", description="店铺名称", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态 bind:绑定 unbind:解绑", required=true, type="string"),
     *     @SWG\Parameter( name="certi_ac", in="query", description="签名", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="merchant_id", type="stirng", description="商户ID"),
     *                     @SWG\Property(property="key", type="stirng", description="密钥"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function bindrelationCallback($id, Request $request)
    {
        $postdata = $request->input();
        app('log')->info("saascert bindrelationCallback postdata========>".json_encode($postdata));
        if (!$id) {
            return 'error';
        }
        $companyId = $id;
        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
        $certService = new CertService(false, $companyId, $shopexUid);
        $certService->bindShopNode($postdata, $msg);
        return $msg;
    }


    /**
     * @SWG\Get(
     *     path="/third/saascert/isbind",
     *     summary="证书、节点",
     *     tags={"ShopexErp"},
     *     description="查看是否绑定了erp",
     *     operationId="getCertificate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="result", type="stirng", description="是否绑定erp true:已绑定 false:未绑定"),
     *                 )
     *              ),
     *          ),
     *     ),
     * )
     */
    public function getIsBind()
    {
        $companyId = app('auth')->user()->get('company_id');
        $certService = new CertService(false, $companyId);
        $erp_node_id = $certService->getErpBindNode();
        $data['result'] = $erp_node_id ? true : false;
        return $this->response->array($data);
    }
}
