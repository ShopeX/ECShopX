<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
