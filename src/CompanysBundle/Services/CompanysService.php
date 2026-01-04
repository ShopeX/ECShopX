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

namespace CompanysBundle\Services;

use CompanysBundle\Entities\ActivateLog;
use CompanysBundle\Entities\Resources;
use CompanysBundle\Entities\Companys;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Sdk\AuthCodeClient;
use SuperAdminBundle\Services\ShopMenuService;

class CompanysService
{
    /** @var resourcesRepository */
    private $resourcesRepository;
    /** @var companysRepository */
    private $companysRepository;

    public function __construct()
    {
        $this->companysRepository = app('registry')->getManager('default')->getRepository(Companys::class);
        $this->resourcesRepository = app('registry')->getManager('default')->getRepository(Resources::class);
    }

    public function active($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->resourcesRepository->create($params);
            if (isset($params['active_code']) && $params['active_code'] && $params['source'] != 'demo') {
                $params['resource_id'] = $result['resource_id'];
                $activateLogRepository = app('registry')->getManager('default')->getRepository(ActivateLog::class);
                $activateLogRepository->create($params);
            }
            $resources = $this->resourcesRepository->getList(['company_id' => $params['company_id'], 'expired_at|gt' => time()], ['expired_at' => 'DESC'], 0, 1);
            $this->companysRepository->update(['company_id' => $params['company_id']], ['expiredAt' => $resources['list'][0]['expiredAt']]);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        $res = [
            'company_id' => $params['company_id'],
            'expired_at' => $resources['list'][0]['expiredAt'],
            'resouce_id' => $resources['list'][0]['resourceId'],
            'source' => $resources['list'][0]['source'],
        ];
        app('redis')->connection('companys')->set($this->genReidsId($params['company_id']), json_encode($res));
        //账号开通 新增系统会员标签（员工）
        app('Illuminate\Contracts\Console\Kernel')->call('company:addSystemStaffTag', ['company_id' => $res['company_id']]);
        return $result;
    }

    public function check($companyId)
    {
        $data = app('redis')->connection('companys')->get($this->genReidsId($companyId));
        if ($data) {
            $result = json_decode($data, 1);
        } else {
            // 如果redis查询不到 就到数据库查询
            $resources = $this->resourcesRepository->getList(['company_id' => $companyId], ['expired_at' => 'DESC'], 0, 1);
            $result = [
                'company_id' => $companyId,
                'expired_at' => $resources['list'][0]['expiredAt'],
                'resouce_id' => $resources['list'][0]['resourceId'],
                'source' => $resources['list'][0]['source'],
            ];
        }
        $result['is_valid'] = time() > $result['expired_at'] ? false : true;
        $company = $this->companysRepository->getInfo(['company_id' => $companyId]);
        //获取当前商户的产品类型，没有就用.env配置的
        $result['product_model'] = ShopMenuService::MENU_TYPE[$company['menu_type']] ?? config('common.product_model');

        if ($company['is_disabled']) {
            $result['is_valid'] = false;
        }
        $result['resouce_id'] = $result['resouce_id'] ?? '';
        $result['source'] = $result['source'] ?? '';
        if (!$result['source']) {
            if (!$result['resouce_id']) {
                $resources = $this->getCompanyLastResource($companyId);
                $resources['resource_id'] = $resources['resourceId'];
            } else {
                $resources = $this->resourcesRepository->getInfo(['company_id' => $companyId], ['resource_id' => $result['resouce_id']]);
            }
            $res = [
                'company_id' => $companyId,
                'expired_at' => $result['expired_at'],
                'resouce_id' => $resources['resource_id'],
                'source' => $resources['source'],
            ];
            app('redis')->connection('companys')->set($this->genReidsId($companyId), json_encode($res));
            $result['source'] = $resources['source'];
        }
        return $result;
    }

    public function getResources($filter, $orderBy = ['expired_at' => 'ASC'], $offset = 0, $limit = 100000)
    {
        return $this->resourcesRepository->getList($filter, $orderBy, $offset, $limit);
    }

    private function getCode($activeCode, $shopexId)
    {
        $authcode = new AuthCodeClient('11958e9cfa0a44d7be353637220ee4ac');
        $data = [
            'shopex_id' => $shopexId,
            'active_code' => $activeCode
        ];

        return $authcode->encode($data);
    }

    public function updateInfo($filter, $params)
    {
        $result = $this->companysRepository->update($filter, $params);

        if ($result && isset($params['expiredAt']) && $params['expiredAt']) {
            $resources = $this->getCompanyLastResource($filter['company_id']);
            $res = [
                'company_id' => $filter['company_id'],
                'expired_at' => $params['expiredAt'],
                'resouce_id' => $resources['resourceId'],
                'source' => $resources['source'],
            ];
            app('redis')->connection('companys')->set($this->genReidsId($filter['company_id']), json_encode($res));
        }
        return $result;
    }

    public function getPassportUidByCompanyId($companyId)
    {
        $data = $this->companysRepository->getByCompanyId($companyId);
        if ($data) {
            return $data->getPassportUid();
        } else {
            return null;
        }
    }

    /**
     * 获取redis存储的ID
     */
    public function genReidsId($companyId)
    {
        return 'companyActivateInfo:'. sha1($companyId);
    }

    /**
     * 设置门店配置信息
     */
    public function setCompanySetting($companyId, $params)
    {
        if (!$params) {
            throw new ResourceException('请填写配置信息');
        }

        return app('redis')->connection('companys')->set($this->genCompanySettingReidsId($companyId), json_encode($params));
    }

    public function getCompanySetting($companyId)
    {
        $data = app('redis')->connection('companys')->get($this->genCompanySettingReidsId($companyId));
        if ($data) {
            return json_decode($data, true);
        }
        return [];
    }

    /**
     * 获取redis存储的ID
     */
    public function genCompanySettingReidsId($companyId)
    {
        return 'wxShopsSetting:'. sha1($companyId);
    }

    /**
    * 获取resource信息
    */
    public function getCompanyLastResource($companyId)
    {
        $resources = $this->resourcesRepository->getList(['company_id' => $companyId], ['expired_at' => 'DESC'], 0, 1);
        return $resources['list'][0];
    }

    /**
     * 根据域名获取company_id
     * @param string $domain
     * @return bool|mixed
     */
    public function getCompanyInfoByDomain($domain = '')
    {
        if (!$domain) {
            return false;
        }

        $redisKey = 'saas_domain:'. sha1($domain);
        $data = app('redis')->connection('companys')->get($redisKey);
        if ($data) {
            $data = json_decode($data, true);
            return $data;
        }

        $filter = ['pc_domain' => $domain];
        $result = $this->companysRepository->getInfo($filter);
        if (!$result) {
            $filter = ['h5_domain' => $domain];
            $result = $this->companysRepository->getInfo($filter);
        }

        if ($result) {
            app('redis')->connection('companys')->set($redisKey, json_encode($result));
        } else {
            // 根据域名，截取company_id,查询企业信息
            $pc_domain_suffix = config('common.pc_domain_suffix');
            preg_match("/^s(\d+)".$pc_domain_suffix."$/", $domain, $match);
            if (!$match) {
                $h5_domain_suffix = config('common.h5_domain_suffix');
                preg_match("/^m(\d+)".$h5_domain_suffix."$/", $domain, $match);
            }
            $company_id = $match[1] ?? 0;
            if ($company_id) {
                $filter = ['company_id' => $company_id];
                $result = $this->companysRepository->getInfo($filter);
            }
        }

        return $result;
    }

    /**
     * 获取域名设置
     * @param array $filter
     * @return mixed
     */
    public function getDomainInfo($filter = [])
    {
        $defaultDomain = [
            'h5_default_domain' => 'm' . $filter['company_id'] . config('common.h5_domain_suffix'),
            'pc_default_domain' => 's' . $filter['company_id'] . config('common.pc_domain_suffix'),
        ];

        $redisKey = 'domainSetting:'. sha1($filter['company_id']);
        $data = app('redis')->connection('companys')->get($redisKey);
        if ($data) {
            $data = json_decode($data, true);
            $data = array_merge($data, $defaultDomain);
            return $data;
        }

        $result = $this->companysRepository->getInfo($filter);
        $result = array_merge($result, $defaultDomain);
        return $result;
    }

    /**
     * 更新域名设置
     * @param array $filter
     * @param array $data
     * @return
     */
    public function updateDomainInfo($filter = [], $data = [])
    {
        $result = $this->companysRepository->update($filter, $data);
        if ($result) {
            $redisKey = 'domainSetting:'. sha1($filter['company_id']);
            $lastConf = app('redis')->connection('companys')->get($redisKey);
            if ($lastConf) {
                $lastConf = json_decode($lastConf, true);
            } else {
                $lastConf = [
                    'pc_domain' => '',
                    'h5_domain' => '',
                ];
            }
            $data = array_merge($lastConf, $data);
            app('redis')->connection('companys')->set($redisKey, json_encode($data));

            $data['company_id'] = $filter['company_id'];

            if (isset($data['pc_domain'])) {
                //清空域名
                if ($lastConf['pc_domain']) {
                    $redisKey = 'saas_domain:'. sha1($lastConf['pc_domain']);
                    app('redis')->connection('companys')->del($redisKey);
                }

                if ($data['pc_domain']) {
                    $redisKey = 'saas_domain:'. sha1($data['pc_domain']);
                    app('redis')->connection('companys')->set($redisKey, json_encode($data));
                }
            }

            if (isset($data['h5_domain'])) {
                //清空域名
                if ($lastConf['h5_domain']) {
                    $redisKey = 'saas_domain:'. sha1($lastConf['h5_domain']);
                    app('redis')->connection('companys')->del($redisKey);
                }

                if ($data['h5_domain']) {
                    $redisKey = 'saas_domain:'. sha1($data['h5_domain']);
                    app('redis')->connection('companys')->set($redisKey, json_encode($data));
                }
            }
        }
        return $result;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->companysRepository->$method(...$parameters);
    }
}
