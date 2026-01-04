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

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use CompanysBundle\Services\CurrencyExchangeRateService;

use Dingo\Api\Exception\StoreResourceFailedException;

class CurrencyController extends BaseController
{
    private $currencyExchangeRate;

    public function __construct(CurrencyExchangeRateService $currencyExchangeRateService)
    {
        $this->currencyExchangeRate = new $currencyExchangeRateService();
    }

    /**
     * @SWG\Post(
     *     path="/currency",
     *     summary="货币信息新增",
     *     tags={"企业"},
     *     description="货币信息新增",
     *     operationId="createData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="currency", in="query", description="货币", type="string"),
     *     @SWG\Parameter( name="title", in="query", description="货币描述", type="string"),
     *     @SWG\Parameter( name="symbol", in="query", description="货币符号", type="string"),
     *     @SWG\Parameter( name="rate", in="query", description="货币汇率", type="string"),
     *     @SWG\Parameter( name="is_default", in="query", description="启用状态", type="string"),
     *     @SWG\Parameter( name="use_platform", in="query", description="适用端", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="id", type="integer", example="1"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="currency", type="integer", example="rmb"),
     *                     @SWG\Property(property="title", type="string", example="人民币"),
     *                     @SWG\Property(property="symbol", type="string", example="￥"),
     *                     @SWG\Property(property="rate", type="string", example="1"),
     *                     @SWG\Property(property="is_default", type="string", example="1"),
     *                     @SWG\Property(property="use_platform", type="string", example="normal"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    public function createData(Request $request)
    {
        // Powered by ShopEx EcShopX
        $params = $request->all("currency", "title", "symbol", "rate", "is_default", 'use_platform');
        $rules = [
            'currency' => ['required', '货币名称必填'],
            'symbol' => ['required', '货币符号必填'],
            'rate' => ['required', '货币汇率必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $params['company_id'] = app('auth')->user()->get('company_id');

        $result = $this->currencyExchangeRate->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/currency/{id}",
     *     summary="货币信息删除",
     *     tags={"企业"},
     *     description="货币信息删除",
     *     operationId="createData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="id", in="path", description="id", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function deleteData($id, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;
        $result = $this->currencyExchangeRate->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Put(
     *     path="/currency/{id}",
     *     summary="货币信息更新",
     *     tags={"企业"},
     *     description="货币信息更新",
     *     operationId="createData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="id", in="path", description="id", type="integer", required=true),
     *     @SWG\Parameter( name="currency", in="query", description="货币", type="string"),
     *     @SWG\Parameter( name="title", in="query", description="货币描述", type="string"),
     *     @SWG\Parameter( name="symbol", in="query", description="货币符号", type="string"),
     *     @SWG\Parameter( name="rate", in="query", description="货币汇率", type="string"),
     *     @SWG\Parameter( name="is_default", in="query", description="启用状态", type="string"),
     *     @SWG\Parameter( name="use_platform", in="query", description="适用端", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="id", type="integer", example="1"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="currency", type="integer", example="rmb"),
     *                     @SWG\Property(property="title", type="string", example="人民币"),
     *                     @SWG\Property(property="symbol", type="string", example="￥"),
     *                     @SWG\Property(property="rate", type="string", example="1"),
     *                     @SWG\Property(property="is_default", type="string", example="1"),
     *                     @SWG\Property(property="use_platform", type="string", example="normal"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    public function updateData($id, Request $request)
    {
        $params = $request->all("currency", "title", "symbol", "rate", "is_default", 'use_platform');
        $rules = [
            'currency' => ['required', '货币名称必填'],
            'symbol' => ['required', '货币符号必填'],
            'rate' => ['required', '货币汇率必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;

        $result = $this->currencyExchangeRate->updateBy($filter, $params);
        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/currency/{id}",
     *     summary="获取货币详情",
     *     tags={"企业"},
     *     description="获取货币详情",
     *     operationId="getDataInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="id", in="path", description="id", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="id", type="integer", example="1"),
     *                 @SWG\Property(property="company_id", type="integer", example="1"),
     *                 @SWG\Property(property="currency", type="integer", example="rmb"),
     *                 @SWG\Property(property="title", type="string", example="人民币"),
     *                 @SWG\Property(property="symbol", type="string", example="￥"),
     *                 @SWG\Property(property="rate", type="string", example="1"),
     *                 @SWG\Property(property="is_default", type="string", example="1"),
     *                 @SWG\Property(property="use_platform", type="string", example="normal"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getDataInfo($id, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;
        $result = $this->currencyExchangeRate->getInfo($filter);
        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/currency",
     *     summary="获取货币列表信息",
     *     tags={"企业"},
     *     description="获取货币列表信息",
     *     operationId="getDataList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="currency", in="query", description="货币", type="string"),
     *     @SWG\Parameter( name="title", in="query", description="货币描述", type="string"),
     *     @SWG\Parameter( name="is_default", in="query", description="启用状态", type="string"),
     *     @SWG\Parameter( name="rate", in="query", description="启用状态", type="string"),
     *     @SWG\Parameter( name="use_platform", in="query", description="使用端", type="string"),
     *     @SWG\Parameter( name="symbol", in="query", description="货币符号", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="total_count", type="integer", example="1"),
     *                     @SWG\Property(property="list", type="array", @SWG\Items(
     *                         @SWG\Property(property="id", type="integer", example="1"),
     *                         @SWG\Property(property="company_id", type="integer", example="1"),
     *                         @SWG\Property(property="currency", type="integer", example="rmb"),
     *                         @SWG\Property(property="title", type="string", example="人民币"),
     *                         @SWG\Property(property="symbol", type="string", example="￥"),
     *                         @SWG\Property(property="rate", type="string", example="1"),
     *                         @SWG\Property(property="is_default", type="string", example="1"),
     *                         @SWG\Property(property="use_platform", type="string", example="normal"),
     *                     )),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getDataList(Request $request)
    {
        if ($request->input('currency')) {
            $filter['currency'] = $request->input('currency');
        }
        if ($request->input('title')) {
            $filter['title|contains'] = $request->input('title');
        }
        if ($request->input('is_default')) {
            $filter['is_default'] = $request->input('is_default');
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $result = $this->currencyExchangeRate->lists($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/currencySetDefault/{id}",
     *     summary="设置默认货币",
     *     tags={"企业"},
     *     description="设置默认货币",
     *     operationId="setDefaultCurrency",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\parameter( name="id", in="path", description="id", type="integer", required=true),
     *     @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\items(
     *                     type="object",
     *                     @SWG\property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setDefaultCurrency($id)
    {
        $companyId = app('auth')->user()->get('company_id');
        $result = $this->currencyExchangeRate->setDefaultCurrency($companyId, $id);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/currencyGetDefault",
     *     summary="获取默认货币配置",
     *     tags={"企业"},
     *     description="获取默认货币配置",
     *     operationId="getDefaultCurrency",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\items(
     *                     type="object",
     *                     @SWG\property(property="id", type="integer", example="1"),
     *                     @SWG\property(property="company_id", type="integer", example="1"),
     *                     @SWG\property(property="currency", type="integer", example="rmb"),
     *                     @SWG\property(property="title", type="string", example="人民币"),
     *                     @SWG\property(property="symbol", type="string", example="￥"),
     *                     @SWG\property(property="rate", type="string", example="1"),
     *                     @SWG\property(property="is_default", type="string", example="1"),
     *                     @SWG\property(property="use_platform", type="string", example="1"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getDefaultCurrency()
    {
        $companyId = app('auth')->user()->get('company_id');
        $result = $this->currencyExchangeRate->getDefaultCurrency($companyId);
        return $this->response->array($result);
    }
}
