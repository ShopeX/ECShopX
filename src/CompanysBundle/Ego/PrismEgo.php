<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Ego;

use PrismClient;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use CompanysBundle\Ego\UpgradeEgo;

class PrismEgo
{
    protected $prismClient;
    
    /**
     * PrismEgo 构造函数.
     */
    public function __construct()
    {
        $this->prismClient = new \PrismClient(
            config('common.prism_url'), //$url
            config('common.prism_key'), //$key
            config('common.prism_secret') //$secret
        );
    }

    public function getPrismAuth(array $credentials)
    {
        // prism 经常会相同密码，有时候成功，有时候失败, 因此尝试多次兼容一下
        $try = 0;
        while($try < 3) {
            $result =  $this->prismClient->post('/oauth/token', [
                'username'   => $credentials['username'],
                'password'   => $credentials['password'],
                'grant_type' => 'passwordv2'
            ], null, ['connect_timeout'=>5]);

            $result = json_decode($result,1);
            if(isset($result['error']) && $result['error']) {
                if ($try === 2) {
                    throw new AccessDeniedHttpException('用户名或者密码不正确');
                }
                $try++;
            } else {
                break;
            }
        }
        $return = $this->getToken($result['code']);

        $upgradeEgo = new UpgradeEgo();
        $passportUid = $return['data']['passport_uid'] ?? '';
        $agreement_id = $credentials['agreement_id'] ?? '';
        $this->checkFreeOrNoSaas($passportUid);
        $upgradeEgo->confirmAgreement($passportUid, $agreement_id);
        $upgradeEgo->getActive($passportUid);

        return $return;
    }

    // 获取companys已有的记录数
    private function checkFreeOrNoSaas($passportUid)
    {
        $upgradeEgo = new UpgradeEgo();
        $license = $upgradeEgo->getSwooleLicense();
        // 如果是独立部署或者是免费版则检查
        if (!config('common.system_is_saas')
            || (isset($license['Product_type']) && ($license['Product_type'] == 'ECSHOPX2_FREE'))
        ) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('count(*)')
               ->from('companys')
               ->where($qb->expr()->neq('passport_uid', $qb->expr()->literal($passportUid)));
            $count = $qb->execute()->fetchColumn();
            if ($count >= 1) {
                throw new AccessDeniedHttpException('超级管理员账号或密码不正确');
            }
        }

        return true;
    }

    public function getToken($code)
    {
        // CONST: 1E236443
        $result = $this->prismClient->post('/oauth/token', [
            'code'       => $code,
            'grant_type' => 'authorization_code'
        ], null, ['connect_timeout'=>3]);
        return json_decode($result, 1);
    }

    //获取开通信息
    public function getSnInfo($entid, $goodsCode) {
        $result = $this->prismClient->post('/online/getsninfo', [
            'entid'      => $entid,
            'goods_code' => $goodsCode
        ], null, ['connect_timeout'=>3]);
        return json_decode($result, 1);
    }

    public function post($path, $params = null, $headers = array(), $config = array()){
        $result = $this->prismClient->post($path, $params, $headers, $config);
        return json_decode($result, 1);
    }

    
}

