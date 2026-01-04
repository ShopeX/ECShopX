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

namespace PointsmallBundle\Services;

use Dingo\Api\Exception\ResourceException;

class SettingService
{
    public $company_id;

    /**
     * ItemsTagsService 构造函数.
     */
    public function __construct($company_id)
    {
        $this->company_id = $company_id;
    }

    /**
     * 保存基础设置
     * @param  [array] $data 基础设置数据
     * @return [bool]
     */
    public function saveSetting($data)
    {
        $data = $this->__formatBaseData($data);
        $key = $this->genKey();
        return app('redis')->hset($key, $this->company_id, json_encode($data));
    }

    /**
     * 获取基础设置数据
     * @return [array] [基础设置数据]
     */
    public function getSetting()
    {
        $default_data = [
            'freight_type' => 'cash',
            'proportion' => '1',
            'rounding_mode' => 'down',
            'entrance' => [
                'mobileterminal_openstatus' => false,
                'pc_openstatus' => false,
            ],
        ];
        $key = $this->genKey();
        $data = app('redis')->hget($key, $this->company_id);
        if ($data) {
            $data = json_decode($data, 1);
        } else {
            $data = [];
        }
        $data = array_merge($default_data, $data);
        return $data;
    }

    /**
     * 钱换积分
     * @param $companyId
     * @param $money
     * @return int
     */
    public function moneyToPoint($money)
    {
        $setting = $this->getSetting($this->company_id);
        $result = [
            'freight_type' => $setting['freight_type'],
        ];
        if (isset($setting['freight_type']) && 'point' == $setting['freight_type']) {
            switch ($setting['rounding_mode']) {
                case 'up':
                    $result['money'] = ceil(bcmul(bcdiv($money, 100, 2), $setting['proportion'], 2));

                    break;
                case 'down':
                    $result['money'] = intval(bcmul(bcdiv($money, 100, 2), $setting['proportion'], 2));
                    break;
            }
        } else {
            $result['money'] = intval($money);
        }
        return $result;
    }

    /**
     * 保存模板设置
     * @param  [array] $data [模板设置数据]
     * @return [bool]
     */
    public function saveTemplateSetting($data)
    {
        $data = $this->__formateTemplateData($data);
        $this->__checkTemplateData($data);
        $key = $this->genTemplateKey();
        return app('redis')->hset($key, $this->company_id, json_encode($data));
    }

    /**
     * 获取模板设置数据
     * @return [array] [模板设置数据]
     */
    public function getTemplateSetting()
    {
        $default_data = [
            'pc_banner' => [
                'https://b-img-cdn.yuanyuanke.cn/image/21/2021/03/05/f9d2d5928c7c9ec97f4b1e2a473f44078PjR2ONSsqs5xTza01jT53e437T35ILB',
                'https://b-img-cdn.yuanyuanke.cn/image/21/2021/03/05/60b682d7fe7844539eb4ad2c2587f73bNEOPNYYDAUgN6oZlEnbLPrZ4W4YE0vuY',
                'https://b-img-cdn.yuanyuanke.cn/image/21/2021/03/05/63807f7b8ce3809e1bfef62fd769d1ce5OVDXyS6cvL9M9ta07d1eIAP11fWAXlk',
            ],
            'screen' => [
                'brand_openstatus' => true,
                'cat_openstatus' => true,
                'point_openstatus' => false,
                'point_section' => [],
            ],
        ];
        $key = $this->genTemplateKey();
        $data = app('redis')->hget($key, $this->company_id);
        if ($data) {
            $data = json_decode($data, 1);
        } else {
            $data = [];
        }
        $data = array_merge($default_data, $data);
        return $data;
    }

    /**
     * 获取基础设置的key
     * @return [type] [description]
     */
    private function genKey()
    {
        return 'pointsmall_setting';
    }

    /**
     * 获取模板设置的key
     * @return [string] [key]
     */
    private function genTemplateKey()
    {
        return 'pointsmall_template_setting';
    }

    /**
     * 处理基础设置数据
     * @param  [array] $data [数据]
     * @return [array]       [处理后的数据]
     */
    private function __formatBaseData($data)
    {
        // 入口设置
        $data['entrance']['mobile_openstatus'] = $data['entrance']['mobile_openstatus'] ?? 'false';
        $data['entrance']['pc_openstatus'] = $data['entrance']['pc_openstatus'] ?? 'false';
        foreach ($data['entrance'] as $key => $status) {
            if (in_array($key, ['mobile_openstatus', 'pc_openstatus'])) {
                $status = $status == 'true' ? true : false;
                $data['entrance'][$key] = $status;
            }
        }
        return $data;
    }

    /**
     * 处理模板设置数据
     * @param  [array] $data [数据]
     * @return [array]       [处理后的数据]
     */
    private function __formateTemplateData($data)
    {
        $data['pc_banner'] = $data['pc_banner'] ?? [];
        foreach ($data['screen'] as $key => $value) {
            if (in_array($key, ['brand_openstatus' ,'cat_openstatus', 'point_openstatus'])) {
                $value = $value == 'true' ? true : false;
                $data['screen'][$key] = $value;
            }
            $data['screen']['point_section'] = $data['screen']['point_section'] ?? [];
        }
        return $data;
    }

    /**
     * 检查模板设置数据
     * @param  [array] $data [数据]
     * @return [bool]
     */
    public function __checkTemplateData($data)
    {
        if ($data['pc_banner'] && count($data['pc_banner']) > 4) {
            throw new ResourceException('PC端Banner轮播图最多可设置4张');
        }
        if ($data['screen']['point_openstatus'] == true) {
            if (empty($data['screen']['point_section']) || count($data['screen']['point_section']) < 1) {
                throw new ResourceException('积分区间最少设置1组');
            }
            if (!empty($data['screen']['point_section']) && count($data['screen']['point_section']) > 5) {
                throw new ResourceException('积分区间最多可设置5组');
            }
            foreach ($data['screen']['point_section'] as $section) {
                if (!isset($section[0]) || !isset($section['1']) || $section[0] == "" || $section[1] == "") {
                    throw new ResourceException('请设置完整的积分区间');
                }
            }
        }

        return true;
    }
}
