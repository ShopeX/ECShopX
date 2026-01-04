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

namespace EspierBundle\Services\Config;

use Carbon\Carbon;
use Dingo\Api\Exception\ResourceException;

class ValidatorService extends ConfigRequestFieldsService
{
    /**
     * 参数验证
     * @param int $companyId 公司id
     * @param int $moduleType 模块类型
     * @param array $formData 表单数据
     * @param bool $lazy 是否是懒惰模式，true为懒惰模式(如果formData中不存在验证的字段，就不验证)，false为非懒惰模式
     * @return array 返回验证的所有字段信息
     */
    public function check(int $companyId, int $moduleType, array $formData, bool $lazy = false, int $distributorId = 0): array
    {
        // 验证的规则
        $rules = [];
        // 验证出错时根据规则返回的错误信息
        $messages = [];
        // 获取字段列表
        $fields = $this->getListAndHandleSettingFormat($companyId, $moduleType, $distributorId);
        // 判断是否是懒惰模式
        if ($lazy) {
            foreach ($fields as $field => $info) {
                if (!isset($formData[$field])) {
                    unset($fields[$field]);
                }
            }
        }
        // 遍历数据
        foreach ($fields as $field => &$info) {
            // 获取字段名字
            $name = (string)($info["name"] ?? "");
            // 获取必填验证的错误信息
            $errorRequiredMessage = (string)($info["required_message"] ?? "");
            // 获取条件验证的错误信息（如果为空则去获取必填验证的错误信息）
            $errorValidateMessage = (string)($info["validate_message"] ?? "");
            if (empty($errorValidateMessage)) {
                $errorValidateMessage = $errorRequiredMessage;
            }
            // 判断字段验证是否被开启
            if (!isset($info["is_open"]) || !$info["is_open"]) {
                continue;
            }
            // 判断字段是否可编辑
            if (!isset($info["is_edit"]) || !$info["is_edit"]) {
                continue;
                throw new ResourceException(sprintf("操作失败！%s无法被修改！", $name));
            }
            // 判断字段是否是必填
            /*if (isset($info["is_required"]) && $info["is_required"]) {
                $rules[$field][] = "required";
                $messages[sprintf("%s.required", $field)] = $errorRequiredMessage;
            }*/
            // 获取字段类型
            $fieldType = (int)($info["field_type"] ?? 0);
            // 根据字段类型来做区分
            switch ($fieldType) {
                // 判断日期
                case ConfigRequestFieldsService::FIELD_TYPE_DATE:
                    $rules[$field][] = function ($attribute, $value, $fail) use ($name) {
                        try {
                            new Carbon($value);
                            return true;
                        } catch (\Exception $exception) {
                            return $fail(sprintf("%s的日期格式有误！", $name));
                        }
                    };
                    break;
                // 判断数字
                case ConfigRequestFieldsService::FIELD_TYPE_NUMBER:
                    // 获取取值范围的列表数据
                    $rageData = (array)($info["range"] ?? []);
                    $rules[$field][] = function ($attribute, $value, $fail) use ($rageData, $errorValidateMessage) {
                        if (!is_numeric($value)) {
                            return $fail($errorValidateMessage);
                        }
                        // 最小值
                        $start = $rageData["start"] ?? null;
                        // 最大值
                        $end = $rageData["end"] ?? null;
                        if (is_null($start) && !is_null($end)) {
                            // 只有最大值
                            if ($value > $end) {
                                return $fail(sprintf("请输入小于等于%d的数字", $end));
                            }
                            return true;
                        }
                        if (is_null($end) && !is_null($start)) {
                            // 只有最小值
                            if ($value < $start) {
                                return $fail(sprintf("请输入大于等于%d的数字", $start));
                            }
                            return true;
                        }

                        if ($value > $end || $value < $start) {
                            return $fail(sprintf("请输入%d~%d范围内的数组", $start, $end));
                        }
                        return true;
                    };
                    break;
                // 单选下拉框
                case ConfigRequestFieldsService::FIELD_TYPE_RADIO:
                    // 获取单选项的列表数据
                    $select = (array)($info["select"] ?? []);
                    $rules[$field][] = function ($attribute, $value, $fail) use ($select, $errorValidateMessage, &$info) {
//                        return true;
//                        $lang = $this->getLang();
//                        if(strtolower($lang) !== 'zh-cn'){
//                            $value = strtolower($value);
//                            $select = array_map('strtolower', $select);
//                        }
//                        // 这里是兼容app版本，老app版本用的id作为验证字段
//                        if (in_array($value,$select)) {
//                            return true;
//                        }
                        if(isset($select[$value])){
                            return true;
                        }
                        // 兼容老数据用中文的方式来验证字段是否存在
//                        if (in_array($value, $select, true)) {
//                            return true;
//                        }
                        return $fail(sprintf("%s有误！不存在该选项！", $info["name"] ?? ""));
                    };
                    break;
                // 复选框
                case ConfigRequestFieldsService::FIELD_TYPE_CHECKBOX:
                    // 获取复选框的列表数据
                    $checkbox = (array)($info["checkbox"] ?? []);
                    $rules[$field][] = function ($attribute, $value, $fail) use ($checkbox, $errorValidateMessage, &$info) {
                        $names = array_column($checkbox, null, "name");
                        if (is_array($value)) {
                            // 如果value是数组
                            foreach ($value as $item) {
                                // 如果value下面的子元素扔是数组，则获取子数组中的name来判断是否存在这个多选项
                                if (is_array($item)) {
                                    $itemValue = (string)($item["name"] ?? "");
                                } else {
                                    $itemValue = (string)$item;
                                }
                                if (!isset($names[$itemValue])) {
                                    return $fail(sprintf("%s有误！不存在该选项！", $info["name"] ?? ""));
                                }
                            }
                        } else {
                            // value是值就直接判断
                            $value = (string)$value;
                            if (!isset($names[$value])) {
                                return $fail(sprintf("%s有误！不存在该选项！", $info["name"] ?? ""));
                            }
                        }
                        return true;
                    };
                    break;
                // 手机号验证
                case ConfigRequestFieldsService::FIELD_TYPE_MOBILE:
                    $rules[$field][] = function ($attribute, $value, $fail) use ($name) {
                        // 新手机号验证
                        if (!preg_match('/^1[3456789]{1}[0-9]{9}$/', $value)) {
                            return $fail("请输入合法的手机号");
                        }
                        return true;
                    };
                    break;
            }
        }
        // 参数验证
        $validator = app("validator")->make($formData, $rules, $messages);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }
        return $fields;
    }
}
