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

namespace EspierBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EspierBundle\Entities\Printer;
use Illuminate\Validation\Rule;

class PrinterService
{
    public $printerRepository;
    // 获取打印机配置类型
    private $type = ['yilianyun'];

    /**
     * PointMemberService 构造函数.
     */
    public function __construct()
    {
        // $this->printerRepository = app('registry')->getManager('default')->getRepository(Printer::class);
        $this->printerRepository = getRepositoryLangue(Printer::class);
    }

    public function getType()
    {
        return $this->type;
    }

    public function checkPrinter($companyId, $printer, $id = '')
    {
        $info = $this->printerRepository->getInfo(['company_id' => $companyId, 'app_terminal' => $printer]);
        if ('' == $id && $info) {
            throw new ResourceException('设备已存在，请查看');
        }
        if ('' != $id && $info && $info['id'] != $id) {
            throw new ResourceException('设备已存在，请查看');
        }
        return true;
    }

    public function checkDistributor($companyId, $distributor, $id = '')
    {
        $info = $this->printerRepository->getInfo(['company_id' => $companyId, 'distributor_id' => $distributor]);
        if ('' == $id && $info) {
            throw new ResourceException('店铺已设置，请查看');
        }
        if ('' != $id && $info && $info['id'] != $id) {
            throw new ResourceException('店铺已设置，请查看');
        }
        return true;
    }

    /**
     * 获取打印机配置信息
     * @param $companyId
     * @param $type
     * @return array|mixed
     */
    public function getPrinterInfo($companyId, $type)
    {
        if (!in_array($type, $this->type)) {
            throw new ResourceException('打印机配置类型错误');
        }
        $result = app('redis')->connection('default')->get($this->getRedisId($companyId, $type));
        if ($result) {
            $result = json_decode($result, true);
        } else {
            $result = [
                'is_open' => false,
                'person_id' => '',
                'app_id' => '',
                'app_key' => '',
                'is_hide' => false,
                'type' => $type,
            ];
        }
        return $result;
    }

    /**
     * 保存im配置信息
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function savePrinterInfo($companyId, $data)
    {
        $rules = [
            'is_open' => ['required', '开启状态必填'],
            'person_id' => ['required', '请填用户ID'],
            'app_id' => ['required', '请填写应用ID'],
            'app_key' => ['required', '请填写应用密钥'],
            'type' => [['required', Rule::in($this->type),], '打印机配置类型错误'],
        ];
        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        app('redis')->connection('default')->set($this->getRedisId($companyId, $data['type']), json_encode($data));
        $result = $this->getPrinterInfo($companyId, $data['type']);
        return $result;
    }

    /**
     * im配置信息
     * @param $companyId
     * @return string
     */
    private function getRedisId($companyId, $type = 'yilianyun')
    {
        return 'printer:' . $companyId . ':config:' . $type;
    }


    /**
     * Dynamically call the PrinterService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->printerRepository->$method(...$parameters);
    }
}
