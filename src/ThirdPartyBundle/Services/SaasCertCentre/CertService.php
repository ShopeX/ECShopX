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

namespace ThirdPartyBundle\Services\SaasCertCentre;

use ThirdPartyBundle\Entities\ShopBind;

class CertService
{
    public const BIND = 1;
    public const UNBIND = 0;
    public $matrixCallbackApi = 'api/third/saascert/matrix/callback';
    public $driver;
    public $companyId;
    public $shopexUid;
    public $shopBindRepository;

    public function __construct($interface = null, $companyId = null, $shopexUid = null)
    {
        $this->shopBindRepository = app('registry')->getManager('default')->getRepository(ShopBind::class);
        if ($interface) {
            $this->driver = $interface->connection();
            $this->companyId = $this->driver->companyId;
            $this->shopexUid = $this->driver->shopexUid;
        } else {
            $this->companyId = $companyId;
            $this->shopexUid = $shopexUid;
        }
    }

    /**
    * 获取oauth证书、节点
    */
    public function getAouthCert()
    {
        $certSetting = $this->getCertSetting();
        if (!$certSetting['cert_id'] || !$certSetting['node_id'] || !$certSetting['token']) {
            $res = $this->driver->getCer();
            if ($res && isset($res['status']) && $res['status'] == 'success') {
                return $this->setCertSetting($res['data']);
            }
        }
        return true;
    }

    /**
     * 查看已获取的证书配置 证书号、节点
     */
    public function getCertSetting()
    {
        $certSetting = app('redis')->connection('prism')->get($this->genCertReidsId());
        $certSetting = json_decode($certSetting, 1);
        $certSetting['cert_id'] = $certSetting['cert_id'] ?? '';
        $certSetting['node_id'] = $certSetting['node_id'] ?? '';
        $certSetting['token'] = $certSetting['token'] ?? '';

        if ($certSetting['node_id']) {
            $certSetting['company_id'] = $this->companyId;
            $this->setCertSettingByNode($certSetting);
        }

        return $certSetting;
    }

    //根据节点号保存证书信息
    public function setCertSettingByNode($certSetting = [])
    {
        $key = 'prism:' . sha1($certSetting['node_id'].'_SaasCert');
        return app('redis')->connection('prism')->set($key, json_encode($certSetting));
    }

    //根据节点号查询证书信息
    public function getCertSettingByNode($nodeId = 0)
    {
        $key = 'prism:' . sha1($nodeId.'_SaasCert');
        $certSetting = app('redis')->connection('prism')->get($key);
        if (!$certSetting) {
            return false;
        }
        $certSetting = json_decode($certSetting, 1);
        $certSetting['cert_id'] = $certSetting['cert_id'] ?? '';
        $certSetting['company_id'] = $certSetting['company_id'] ?? '';
        $certSetting['token'] = $certSetting['token'] ?? '';
        return $certSetting;
    }

    //根据节点号查询公司ID
    public function getCompanyId($nodeId = '')
    {
        $companyId = config('common.system_companys_id');
        if (!$nodeId) {
            return $companyId;
        }

        $certSetting = $this->getCertSettingByNode($nodeId);
        if ($certSetting && isset($certSetting['company_id']) && $certSetting['company_id']) {
            $companyId = $certSetting['company_id'];
        }
        return $companyId;
    }

    /**
     * 设置cert配置
     */
    public function setCertSetting($data)
    {
        app('log')->info("saascert ".__FUNCTION__."-".__LINE__."data=>".json_encode($data));
        $_data['cert_id'] = $data['certificate_id'] ?? '';
        $_data['node_id'] = $data['node_id'] ?? '';
        $_data['token'] = $data['token'] ?? '';
        if ($_data['cert_id'] && $_data['node_id'] && $_data['token']) {
            return app('redis')->connection('prism')->set($this->genCertReidsId(), json_encode($_data));
        }
        return false;
    }

    /**
     * 删除cert配置
     */
    public function deleteCertSetting()
    {
        app('log')->info("saascert ".__FUNCTION__."-".__LINE__." 删除配置");
        return app('redis')->connection('prism')->del($this->genCertReidsId());
    }


    /**
    * 获取company_id的saasErp的绑定节点
    * @return $node_id:节点
    */
    public function getErpBindNode()
    {
        $node_id = app('redis')->get($this->genErpBindReidsId());
        return $node_id;
    }

    //申请绑定关系
    public function applyBindrelation()
    {
        $certSetting = $this->getCertSetting();
        $matrix_url = config('common.matrix_realtion_url');
        $params = [];
        $base_url = config('common.certi_base_url');
        $params['certi_id'] = $certSetting['cert_id'];
        $params['node_id'] = $certSetting['node_id'];
        $params['sess_id'] = md5($certSetting['node_id']);
        $params['certi_ac'] = $this->make_shopex_ac($params, $certSetting['token']);
        $params['source'] = 'apply';
        $params['bind_type'] = 'shopex';
        $params['api_url'] = rtrim($base_url, '/') . '/api/thirdparty/saaserp';//后续再加上company_id
        $params['callback'] = rtrim($base_url, '/') . '/' . $this->matrixCallbackApi."/".$this->companyId;
        return $this->getStrUrl($matrix_url, $params);
    }

    //查看绑定关系
    public function acceptBindrelation()
    {
        $certSetting = $this->getCertSetting();
        $matrix_url = config('common.matrix_realtion_url');
        $params = [];
        $base_url = config('common.certi_base_url');
        $params['certi_id'] = $certSetting['cert_id'];
        $params['node_id'] = $certSetting['node_id'];
        $params['sess_id'] = md5($certSetting['node_id']);
        $params['certi_ac'] = $this->make_shopex_ac($params, $certSetting['token']);
        $params['source'] = 'accept';
        $params['api_url'] = rtrim($base_url, '/') . '/api/thirdparty/saaserp';//后续再加上company_id
        $params['callback'] = rtrim($base_url, '/') . '/' . $this->matrixCallbackApi.'/'.$this->companyId;
        return $this->getStrUrl($matrix_url, $params);
    }

    /**
    * 获取证书反查
    */
    public function certiValidate($postdata)
    {
        $sign = $this->make_shopex_ac($postdata, config('common.store_key'));
        if (isset($postdata['certi_ac']) && $sign == $postdata['certi_ac']) {
            $return = array(
                'res' => 'succ',
                'msg' => '',
                'info' => ''
                );
        } else {
            $return = array(
                'res' => 'fail',
                'msg' => '000001',
                'info' => 'You have the different ac!'
                );
        }
        return $return;
    }

    /**
    * 存储节点绑定
    */
    public function bindShopNode($data, &$msg)
    {
        app('log')->info("saascert -".__FUNCTION__."-".__LINE__."-data====>".json_encode($data));
        $certSetting = $this->getCertSetting();
        app('log')->info("saascert -".__FUNCTION__."-".__LINE__."-certSetting====>".json_encode($certSetting));
        //验证签名
        $sign = $data["certi_ac"];
        $my_sign = $this->make_shopex_ac($data, $certSetting['token']);
        if ($sign != $my_sign) {
            app('log')->debug("saascert -".__FUNCTION__."-".__LINE__."-sign error");
            $msg = 'sign error';
            return false;
        }

        $bindData = [
            'company_id' => $this->companyId,
            'name' => $data['shop_name'] ?? '',
            'node_id' => $data['node_id'],
            'node_type' => $data['node_type'],
        ];
        $node_type = $bindData['node_type'];
        if ($data['status'] == 'bind') {
            $bindData['status'] = self::BIND;
            //同一种node_type只能绑定一个
            $bindInfo = $this->shopBindRepository->getInfo(['company_id' => $this->companyId,'node_type' => $node_type,'status' => self::BIND]);
            if ($bindInfo) {
                app('log')->debug("saascert -".__FUNCTION__."-".__LINE__."-node_type ".$node_type." is exists");
                $msg = 'node_type is exists';
                return false;
            }
            //保存绑定关系
            $this->saveShopNode($bindData);
            if ($node_type == 'ecos.ome') {
                app('redis')->set($this->genErpBindReidsId(), $bindData['node_id']);
            }
            $msg = 'succ';
            return true;
        } elseif ($data['status'] == 'unbind') {
            $this->deleteByNodeType($this->companyId, $node_type);
            app('redis')->del($this->genErpBindReidsId());
            $msg = 'succ';
            return true;
        } else {
            $msg = 'succ';
            return true;
        }
    }


    /**
    * 保存节点绑定记录
    * @param $data array
    * company_id:公司Id  node_id:节点 node_type:节点类型 status:状态 name:店铺名称
    */
    public function saveShopNode($data)
    {
        $filter = ['company_id' => $data['company_id'],'node_id' => $data['node_id'],'node_type' => $data['node_type']];
        $node_info = $this->shopBindRepository->getInfo($filter);
        if ($node_info) {
            return $this->shopBindRepository->updateOneBy(['id' => $node_info['id']], $data);
        } else {
            return $this->shopBindRepository->create($data);
        }
    }

    /**
    * 删除节点绑定记录
    * @param $company_id:公司Id
    * @param $node_type:节点类型
    */
    public function deleteByNodeType($company_id, $node_type)
    {
        return $this->shopBindRepository->deleteBy(['company_id' => $company_id,'node_type' => $node_type]);
    }

    /**
    * 拼接url字符串
    */
    private function getStrUrl($strUrl, $params)
    {
        $array_params = [];
        foreach ($params as $str_key => $str_value) {
            $array_params[] = $str_key . "=" . rawurlencode($str_value);
        }
        $strUrl = $strUrl . implode("&", $array_params);
        return $strUrl;
    }

    /**
     * 获取redis存储的ID
     */
    private function genCertReidsId()
    {
        return 'prism:' . sha1($this->shopexUid.'_'.$this->companyId.'_SaasCert');
    }

    /**
     * 获取saasErp 节点绑定 redis存储的ID
     */
    private function genErpBindReidsId()
    {
        return 'SaasErpBind:' . sha1($this->companyId);
    }

    private function make_shopex_ac($temp_arr, $token)
    {
        ksort($temp_arr);
        $str = '';
        foreach ($temp_arr as $key => $value) {
            if ($key != 'certi_ac') {
                $str .= $value;
            }
        }
        return md5($str.$token);
    }
}
