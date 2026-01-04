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
use Dingo\Api\Exception\ResourceException;

class LocalDeliveryService
{
    private $dirver;
    private $configService;
    private $balanceService;
    private $cityCodeService;
    private $merchantService;
    private $orderService;
    private $rechargeService;
    private $shopService;
    public function __construct()
    {
        $this->dirver = config('common.local_delivery_dirver');
        switch ($this->dirver) {
            case 'dada':
            case 'shansong':
                break;
            default:
                throw new ResourceException('同城配仅支持达达和闪送');
        }
    }

    public function getDirver()
    {
        return $this->dirver;
    }

    public function getConfigService()
    {
        if ($this->configService) {
            return $this->configService;
        }
        $className = 'OrdersBundle\Services\CompanyRel'.ucfirst($this->dirver).'Service';
        return $this->configService = new $className();
    }

    public function getBalanceService()
    {
        if ($this->balanceService) {
            return $this->balanceService;
        }
    	$className = 'ThirdPartyBundle\Services\\'.ucfirst($this->dirver).'Center\BalanceService';
    	return $this->balanceService = new $className();
    }

    public function getCityCodeService()
    {
        // FIXME: check performance
        if ($this->cityCodeService) {
            return $this->cityCodeService;
        }
        $className = 'ThirdPartyBundle\Services\\'.ucfirst($this->dirver).'Center\CityCodeService';
        return $this->cityCodeService = new $className();
    }

    public function getOrderService()
    {
        if ($this->orderService) {
            return $this->orderService;
        }
        $className = 'ThirdPartyBundle\Services\\'.ucfirst($this->dirver).'Center\OrderService';
        return $this->orderService = new $className();
    }

    public function getRechargeService()
    {
        if ($this->rechargeService) {
            return $this->rechargeService;
        }
        if ($this->getDirver() == 'shansong') {
            throw new ResourceException('请前往闪送商户后台进行充值');
        }
        $className = 'ThirdPartyBundle\Services\\'.ucfirst($this->dirver).'Center\RechargeService';
        return $this->rechargeService = new $className();
    }

    public function getShopService()
    {
        if ($this->shopService) {
            return $this->shopService;
        }
        $className = 'ThirdPartyBundle\Services\\'.ucfirst($this->dirver).'Center\ShopService';
        return $this->shopService = new $className();
    }

    /**
     * Dynamically call the rightsService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->service->$method(...$parameters);
    }
}