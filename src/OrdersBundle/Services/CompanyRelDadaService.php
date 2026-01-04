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

namespace OrdersBundle\Services;

use OrdersBundle\Entities\CompanyRelDada;
use ThirdPartyBundle\Services\DadaCenter\CityCodeService;

class CompanyRelDadaService
{
    private $companyRelDadaReposity;

    public function __construct()
    {
        $this->companyRelDadaReposity = app('registry')->getManager('default')->getRepository(CompanyRelDada::class);
    }

    /**
     * 获取城市列表
     * @param $company_id
     * @return mixed
     */
    public function getCityList($company_id)
    {
        $cityList = app('redis')->get('dada_city_list');
        if (empty($cityList)) {
            $companyRelDada = $this->getInfo(['company_id' => $company_id]);
            $cityCodeService = new CityCodeService();
            if (empty($companyRelDada['source_id'])) {
                $cityList = $cityCodeService->getLocalCityCode();
            } else {
                $cityList = $cityCodeService->list($company_id);
                $cityList = json_encode($cityList, JSON_UNESCAPED_UNICODE);
                app('redis')->set('dada_city_list', $cityList, 'EX', 86400);
            }
        }
        return json_decode($cityList, true);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->companyRelDadaReposity->$method(...$parameters);
    }
}
