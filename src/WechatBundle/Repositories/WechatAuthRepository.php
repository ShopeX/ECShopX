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

namespace WechatBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use WechatBundle\Entities\Weapp;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Dingo\Api\Exception\ResourceException;

use SuperAdminBundle\Services\WxappTemplateService;

class WechatAuthRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'wechat_authorization';

    public $cols = ['authorizer_appid','authorizer_appsecret','operator_id','company_id','authorizer_refresh_token','nick_name','head_img','service_type_info','verify_type_info','user_name','signature','principal_name','alias','business_info','qrcode_url','miniprograminfo','func_info','bind_status','created_at','updated_at','deleted_at','auto_publish', 'is_direct'];

    /**
     * 更新数据表字段数据
     *
     * @param  $filter 更新的条件
     * @param array $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = "set". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
                if (method_exists($entity, $fun)) {
                    $entity->$fun($params[$col]);
                }
            }
        }
        return $entity;
    }

    private function getColumnNamesData($entity, $cols = [], $ignore = [])
    {
        if (!$cols) {
            $cols = $this->cols;
        }

        $values = [];
        foreach ($cols as $col) {
            if ($ignore && in_array($col, $ignore)) {
                continue;
            }
            $fun = "get". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
            if (method_exists($entity, $fun)) {
                $values[$col] = $entity->$fun();
            }
        }
        return $values;
    }

    /**
     * 筛选条件格式化
     *
     * @param array $filter
     * @param object $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }

    /**
     * 获取绑定公众号的微信号
     */
    public function getAuthorizerAlias($authorizerAppId)
    {
        return $this->find($authorizerAppId)->getAlias();
    }

    // 获取指定授权app的refreshToken
    public function getAuthorizerRefreshToken($authorizerAppId)
    {
        $wechatAuthData = $this->findOneBy(['authorizer_appid' => $authorizerAppId, 'bind_status' => 'bind']);
        return $wechatAuthData ? $wechatAuthData->getAuthorizerRefreshToken() : false;
    }

    /**
     * 根据企业ID获取授权公众号服务号ID
     */
    public function getAuthorizerAppid($companyId)
    {
        $wechatAuthData = $this->findOneBy(['company_id' => $companyId, 'bind_status' => 'bind', 'service_type_info' => 2]);
        if ($wechatAuthData) {
            return $wechatAuthData->getAuthorizerAppid();
        }
    }

    public function checkWxaAppId($companyId, $wxaAppId)
    {
        return $this->findOneBy(['company_id' => $companyId, 'authorizer_appid' => $wxaAppId, 'bind_status' => 'bind']);
    }

    /**
     * 获取小程序授权列表
     */
    public function getWxaList($companyId)
    {
        $list = $this->findBy(['company_id' => $companyId, 'bind_status' => 'bind', 'service_type_info' => 3]);

        if (!$list) {
            return [];
        }

        $weappList = app('registry')->getManager('default')->getRepository(Weapp::class)->getWeappByCompanyId($companyId);

        $wxappTemplateService = new WxappTemplateService();
        $templateList = $wxappTemplateService->getDataList();

        $data = [];
        foreach ($list as $row) {
            $templateData = '';
            if (isset($weappList[$row->getAuthorizerAppid()])) {
                $templateName = $weappList[$row->getAuthorizerAppid()]['template_name'];
                $templateData = $templateList[$templateName] ?? '' ;
                if (!isset($templateData) || !$templateData) {
                    $templateData = config('wxa.'.$templateName);
                }
            }

            $data[] = [
                'authorizer_appid' => $row->getAuthorizerAppid(),
                'authorizer_appsecret' => $row->getAuthorizerAppSecret(),
                'auto_publish' => $row->getAutoPublish(),
                'is_direct' => $row->getIsDirect(),
                'nick_name' => $row->getNickName(),
                'head_img' => $row->getHeadImg(),
                'verify_type_info' => $row->getVerifyTypeInfo(),
                'qrcode_url' => $row->getQrcodeUrl(),
                'principal_name' => $row->getPrincipalName(),
                'signature' => $row->getSignature(),
                'weapp' => isset($weappList[$row->getAuthorizerAppid()]) ? $weappList[$row->getAuthorizerAppid()] : "",
                'weappTemplate' => isset($weappList[$row->getAuthorizerAppid()]) ? $templateData : "",
            ];
        }
        return $data;
    }

    /**
     * 根据AuthorizerAppid获取授权公众号基础信息
     */
    public function getAuthorizerInfo($authorizerAppid)
    {
        $result = [];
        $wechatAuthData = $this->findOneBy(['authorizer_appid' => $authorizerAppid]);
        if ($wechatAuthData) {
            $result = [
                'authorizer_appid' => $wechatAuthData->getAuthorizerAppid(),
                'authorizer_appsecret' => $wechatAuthData->getAuthorizerAppSecret(),
                'nick_name' => $wechatAuthData->getNickName(),
                'head_img' => $wechatAuthData->getHeadImg(),
                'service_type_info' => $wechatAuthData->getServiceTypeInfo(),
                'verify_type_info' => $wechatAuthData->getVerifyTypeInfo(),
                'user_name' => $wechatAuthData->getUserName(),
                'signature' => $wechatAuthData->getSignature(),
                'principal_name' => $wechatAuthData->getPrincipalName(),
                'alias' => $wechatAuthData->getAlias(),
                'business_info' => $wechatAuthData->getBusinessInfo(),
                'qrcode_url' => $wechatAuthData->getQrcodeUrl(),
                'miniprograminfo' => $wechatAuthData->getMiniprograminfo(),
                'func_info' => $wechatAuthData->getFuncInfo(),
                'is_direct' => $wechatAuthData->getIsDirect(),
            ];
        }

        return $result;
    }

    /**
     * 授权数据操作
     *
     * @param int $operatorId 操作者账号ID
     * @param array $authorizationInfo 授权信息
     * @param array $authorizerInfo 授权账号信息
     * @param array $authorizationType 授权账号类型
     */
    public function authorized($companyId, $operatorId, $authorizationInfo, $authorizerInfo, $authorizationType)
    {
        $conn = app('registry')->getConnection('default');

        //判断是否为首次绑定，如果为首次绑定则必须绑定服务号
        $wechatAuthData = $this->find($authorizationInfo['authorizer_appid']);
        if ($wechatAuthData) {
            $conn->update(
                $this->table,
                ['authorizer_refresh_token' => $authorizationInfo['authorizer_refresh_token']],
                ['authorizer_appid' => $authorizationInfo['authorizer_appid']]
            );
        }

        //获取当前绑定的公众号
        $currentData = $this->findOneBy(['company_id' => $companyId, 'service_type_info' => 2, 'bind_status' => 'bind']);
        //如果当前存在绑定公众号，并且当前公众号和此次绑定的公众号不一致，则将以前的公众号解绑
        if ($currentData) {
            if ($currentData->getAuthorizerAppid() != $authorizationInfo['authorizer_appid'] && $authorizerInfo['service_type_info']['id'] == 2) {
                $this->unauthorized($currentData->getAuthorizerAppid());
            }
        }

        //如果当前公众号已绑定，并且不是当前企业, 那么就不能绑定
        if ($wechatAuthData && $wechatAuthData->getBindStatus() == 'bind' && $wechatAuthData->getCompanyId() != $companyId) {
            throw new UpdateResourceFailedException('当前公众号已绑定其他账号，请解绑后在绑定');
        }

        // 因为微信已经授权
        // 当前绑定必须在刷新authorizer_refresh_token后报错，不然会导致refresh_token失效
        // 如果授权账号不是服务号或者不是小程序则不能绑定
        if ($authorizerInfo['service_type_info']['id'] != 2 && !isset($authorizerInfo['MiniProgramInfo'])) {
            throw new BadRequestHttpException('只支持服务号或小程序授权');
        }

        // 因为微信已经授权
        // 当前绑定必须在刷新authorizer_refresh_token后报错，不然会导致refresh_token失效
        // 如果为公众号授权
        if ($authorizationType == 'woa' && isset($authorizerInfo['MiniProgramInfo'])) {
            throw new BadRequestHttpException('请授权公众号账号');
        }

        // 因为微信已经授权
        // 当前绑定必须在刷新authorizer_refresh_token后报错，不然会导致refresh_token失效
        if ($authorizationType == 'wxa' && !isset($authorizerInfo['MiniProgramInfo'])) {
            throw new BadRequestHttpException('请授权小程序账号');
        }

        // 个人账号不支持授权，个人账号不能认证
        if ($authorizerInfo['principal_name'] == '个人') {
            throw new BadRequestHttpException('不支持个人账号授权，请使用企业账号');
        }

        if ($authorizerInfo['verify_type_info'] == '-1') {
            throw new BadRequestHttpException('当前账号未认证，不支持绑定');
        }

        $data = $this->formatAuthInfo($authorizationInfo, $authorizerInfo);
        $data['bind_status'] = 'bind';
        $data['company_id'] = $companyId;
        $data['operator_id'] = $operatorId;
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (!$wechatAuthData) {
            $data['authorizer_appid'] = trim($authorizationInfo['authorizer_appid']);
            $data['created_at'] = date('Y-m-d H:i:s');
            $conn->insert($this->table, $data);
        } else {
            $conn->update($this->table, $data, ['authorizer_appid' => trim($authorizationInfo['authorizer_appid'])]);
        }
        $data['nick_name'] = $authorizerInfo['nick_name'];
        $data['authorizer_appid'] = trim($authorizationInfo['authorizer_appid']);
        return $data;
    }

    /**
     * 小程序或者公众号直连绑定
     *
     * @param array $params 直连信息
     * @param string $bind_type 绑定类型，公众号或者小程序
     *
     */
    public function directAuthorized($params, $bind_type)
    {
        $bindMsg = ['offiaccount' => '公众号', 'miniprogram' => '小程序'];
        $conn = app('registry')->getConnection('default');
        $wechatAuthData = $this->find($params['authorizer_appid']);
        //已经绑定过时，验证绑定方式，第三方不能改为直连
        if ($wechatAuthData && $wechatAuthData->getCompanyId() != $params['company_id']) {
            throw new ResourceException("绑定失败，{$bindMsg[$bind_type]}已被占用");
        }
        if ($wechatAuthData && intval($wechatAuthData->getIsDirect()) !== 1) {
            throw new ResourceException("{$bindMsg[$bind_type]}已经绑定过第三方授权，不能改为直连");
        }
        if (!empty($params['old_authorize_appid'] ?? null) && !empty($wechatAuthData) && ($params['old_authorize_appid'] != $wechatAuthData->getAuthorizerAppid())) {
            throw new ResourceException("更换直连{$bindMsg[$bind_type]}有误，新的{$bindMsg[$bind_type]}已存在");
        }
        if ($bind_type == 'miniprogram') {
            $params['service_type_info']['id'] = 3; // 小程序
        } elseif ($bind_type == 'offiaccount') {
            $params['service_type_info']['id'] = 2; // 公众号
        }
        $data = $this->formatAuthInfo([], $params);
        $data['bind_status'] = 'bind';
        $data['company_id'] = $params['company_id'];
        $data['operator_id'] = $params['operator_id'];
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['is_direct'] = 1;
        $data['authorizer_appid'] = trim($params['authorizer_appid']);
        if (empty($params['old_authorize_appid'] ?? null) && empty($wechatAuthData)) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $conn->insert($this->table, $data);
        } elseif (!empty($params['old_authorize_appid'] ?? null) && empty($wechatAuthData)) {
            $conn->update($this->table, $data, ['authorizer_appid' => trim($params['old_authorize_appid'])]);
        } elseif (!empty($wechatAuthData)) {
            $conn->update($this->table, $data, ['authorizer_appid' => trim($params['authorizer_appid'])]);
        }
        $data['authorizer_appid'] = $params['authorizer_appid'];
        return $data;
    }

    /**
     * 更新授权信息
     *
     * @param array $authorizationInfo 授权信息
     * @param array $authorizerInfo 授权账号信息
     */
    public function upauthorized($authorizationInfo, $authorizerInfo)
    {
        $conn = app('registry')->getConnection('default');

        $data = $this->formatAuthInfo($authorizationInfo, $authorizerInfo);
        return $conn->update($this->table, $data, ['authorizer_appid' => $authorizationInfo['authorizer_appid']]);
    }

    /**
     * 格式化保存的数据
     *
     * @return array
     */
    private function formatAuthInfo($authorizationInfo, $authorizerInfo)
    {
        $funInfo = array();
        if (isset($authorizationInfo['func_info'])) {
            foreach ($authorizationInfo['func_info'] as $row) {
                $funInfo[] = $row['funcscope_category']['id'];
            }
        }

        $data = [
            'authorizer_refresh_token' => $authorizationInfo['authorizer_refresh_token'] ?? '',
            'nick_name' => $authorizerInfo['nick_name'],
            'user_name' => $authorizerInfo['user_name'] ?? '',
            'alias' => $authorizerInfo['alias'] ?? '',
            'qrcode_url' => $authorizerInfo['qrcode_url'] ?? '',
            'head_img' => $authorizerInfo['head_img'] ?? '',
            'service_type_info' => isset($authorizerInfo['MiniProgramInfo']) ? 3 : $authorizerInfo['service_type_info']['id'],
            'verify_type_info' => $authorizerInfo['verify_type_info']['id'] ?? '-1',
            'business_info' => isset($authorizerInfo['MiniProgramInfo']) ? json_encode($authorizerInfo['business_info']) : json_encode(new \StdClass()),
            'principal_name' => $authorizerInfo['principal_name'] ?? '',
            'signature' => $authorizerInfo['signature'] ?? '',
            'func_info' => implode(',', $funInfo) ?? '',
            'miniprograminfo' => isset($authorizerInfo['MiniProgramInfo']) ? json_encode($authorizerInfo['MiniProgramInfo']) : json_encode(new \StdClass()),
            'authorizer_appsecret' => trim($authorizerInfo['authorizer_appsecret'] ?? ''),
        ];

        return $data;
    }

    /**
     * 取消授权数据操作
     *
     * @param string $authorizerAppId 授权的微信ID
     */
    public function unauthorized($authorizerAppId)
    {
        $conn = app('registry')->getConnection('default');

        return $conn->update($this->table, ['bind_status' => 'unbind'], ['authorizer_appid' => $authorizerAppId]);
    }

    public function getCompanyId($authorizerAppId)
    {
        $data = $this->find($authorizerAppId);
        if ($data) {
            return $data->getCompanyId();
        } else {
            return null;
        }
    }

    /**
     * 根据条件获取单条数据
     *
     * @param array $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 根据企业ID获取公众号appid
     */
    public function getWoaAppidByCompanyId($companyId)
    {
        $data = $this->findOneBy(['service_type_info' => 2, 'company_id' => $companyId, 'bind_status' => 'bind']);
        if ($data) {
            return $data->getAuthorizerAppid();
        } else {
            return null;
        }
    }

    public function getWxauthDetail($appid)
    {
        $row = $this->findOneBy(['authorizer_appid' => $appid]);
        if (!$row) {
            return [];
        }

        $data = [
            'authorizer_appid' => $row->getAuthorizerAppid(),
            'authorizer_appsecret' => $row->getAuthorizerAppsecret(),
            'nick_name' => $row->getNickName(),
            'head_img' => $row->getHeadImg(),
            'verify_type_info' => $row->getVerifyTypeInfo(),
            'service_type_info' => $row->getServiceTypeInfo(),
            'qrcode_url' => $row->getQrcodeUrl(),
            'principal_name' => $row->getPrincipalName(),
            'signature' => $row->getSignature(),
            'auto_publish' => $row->getAutoPublish(),
            'is_direct' => $row->getIsDirect(),
        ];
        return $data;
    }
}
